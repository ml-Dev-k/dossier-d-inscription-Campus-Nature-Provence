<?php

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

require_once 'functions/drive.php';
require_once 'functions/utils.php';


use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Traitement principal
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    popup('error');
    exit;
}

nettoyerChampsConditionnels($_POST);

$client = getClient();
$drive = new Drive($client);

$nom = strtoupper(trim($_POST['nom_usage']));
$prenom = ucfirst(trim($_POST['prenom']));
$date = date('Y-m-d');

$nir = '';
if (!empty($_POST['secu'])) {
    $nir = preg_replace('/\s+/', '', $_POST['secu']);
} elseif (!empty($_POST['msa'])) {
    $nir = preg_replace('/\s+/', '', $_POST['msa']);
} else {
    $nir = '???_' . time();
}

$folderName = "{$nom}_{$prenom}_{$nir}_{$date}";

/////////////

$client = getClient($_ENV['GOOGLE_CREDENTIALS_PATH']);

// 1. Définir le nom du dossier principal
$rootFolderName = $_ENV['GOOGLE_DRIVE_ROOT_NAME'];

// 2. Regroupe les formations par catégories
$groupesFormations = [
    "✔ Métiers du Paysage" => [
        "BTS Aménagements Paysagers",
        "BP Aménagements Paysagers",
        "BPAOSP - Brevet Professionnel Agricole (BPA) Ouvrier Spécialisé du Paysage",
        "Bac Professionnel - Aménagements Paysagers",
        "CAPa jardinier paysagiste"
    ],
    "✔ Métiers de l'Environnement" => [
        "BTS GPN - BTSA Gestion et Protection de la Nature",
        "Bachelor GPMM"
    ],
    "✔ Métiers du Monde Animal" => [
        "ASV - Auxiliaire Spécialisé Vétérinaire",
        "CAPA Maréchal Ferrant",
        "ACACED - Attestation de Connaissances pour les Animaux de Compagnie d’Espèces Domestiques"
    ],
    "✔ Métiers de l'Agriculture et de l'Alimentation" => [
        "BP Responsable d'Entreprise Agricole [Apprentissage]",
        "BP Responsable d'Entreprise Agricole [Formation Continue]",
        "Brevet de Technicien Supérieur Agricole (BTSa) Viticulture-Oenologie [Apprentissage]"
    ],
    "✔ Métiers du Service à la Personne" => [
        "CAPa SAPVER"
    ],
    "✔ Formations courtes" => [
        "Certibiocide/Certiphyto",
        "Formations courtes agriculture",
        "Hygiène Alimentaire",
        "Validation des Acquis de l'Expérience (VAE)"
    ]
];

$formation = $_POST['formation'] ?? 'Autre';
$groupe = "Autres";

foreach ($groupesFormations as $nomGroupe => $formations) {
    if (in_array($formation, $formations)) {
        $groupe = $nomGroupe;
        break;
    }
}




$rootId = $_ENV['GOOGLE_ROOT_FOLDER_ID'];
$groupeId = getOrCreateDriveFolder($drive, $groupe, $rootId);
$formationId = getOrCreateDriveFolder($drive, $formation, $groupeId);


$parentId = $formationId;

// Si dossier déjà existant
if (!empty($_POST['secu'])) {
    $folderId = $_POST['secu'];
} else {
    $folderId = $_POST['msa'];
}
if (checkDrive($drive, $parentId, $folderId)) {
    popup('exist');
    exit;
}


try {
    $dossier = new DriveFile([
        'name' => $folderName,
        'mimeType' => 'application/vnd.google-apps.folder',
        'parents' => [$parentId]
    ]);

    //$createdFolder = $drive->files->create($dossier, ['fields' => 'id']);
    $createdFolder = $drive->files->create($dossier, [
        'fields' => 'id',
        'supportsAllDrives' => true
    ]);

    $folderId = $createdFolder->id;

    $allowedMimeTypes = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/jpg',
        'image/webp'
    ];

    //nomenclature des fichiers joints
    $mapping = [
        'cv'         => "CV_{$nom}_{$prenom}",
        'id_recto'   => "Piece_identite_{$nom}_{$prenom}",
        'id_verso'   => "Piece_identite_verso_{$nom}_{$prenom}",
        'secu_file'  => "Attestation_secu_{$nom}_{$prenom}",
        'diplomes'   => "Diplomes_{$nom}_{$prenom}",
        'militaire'  => "Situation_militaire_{$nom}_{$prenom}",
        'rqth'       => "RQTH_{$nom}_{$prenom}",
        'certif'     => "Certificat_medical_{$nom}_{$prenom}",
    ];
    // Upload des fichiers
    foreach ($_FILES as $cle => $file) {
        if ($file['error'] === UPLOAD_ERR_NO_FILE || empty($file['tmp_name'])) {
            continue; // On passe au fichier suivant
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);


        $validExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'bmp', 'tiff'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, $validExtensions)) {
            if (isset($folderId)) {
                deleteDriveFolder($drive, $folderId);
            }
            popup(
                'error',
                "<i class='fa-solid fa-triangle-exclamation error-icon fa-2x'></i>
            <p id='msg'> le fichier : </p>
            <p id='fichier-defaillant'>" . htmlspecialchars($file['name']) . "</p>
            <p id='msg'> est dans un format non autorisé.<br>
            Veuillez joindre un fichier PDF ou une image.</p>"
            );
            exit;
        }


        //Upload des fichiers joints
        if ($file['error'] === UPLOAD_ERR_OK) {
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $nomFichier = $mapping[$cle] ?? "Fichier_{$nom}_{$prenom}";
            $nomFichier .= '.' . $extension;


            $metadata = new DriveFile([
                'name' => $nomFichier,
                'parents' => [$folderId]
            ]);
            $content = file_get_contents($file['tmp_name']);

            $drive->files->create($metadata, [
                'data' => $content,
                'uploadType' => 'multipart',
                'fields' => 'id',
                'supportsAllDrives' => true
            ]);
        }
    }

    // Génération Excel
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle("Dossier utilisateur");

    $financementStandards = ['individuel', 'compte personnel de formation', 'cap emploi', 'france travail', 'transition professionnelle', 'region', ''];
    $indemniteStandards = ['ARE', 'RSA', ''];

    // Transforme tout le tableau en minuscules une seule fois
    $financementStandardsLower = array_map('strtolower', $financementStandards);
    $indemniteStandardsLower = array_map('strtolower', $indemniteStandards);

    if (!empty($_POST['financement']) && is_array($_POST['financement'])) {
        foreach ($_POST['financement'] as $index => $val) {
            $valTrimmed = strtolower(trim($val));
            if (!in_array($valTrimmed, $financementStandardsLower)) {
                $_POST['financement'][$index] = 'Autres (' . ucfirst($val) . ')';
            }
        }
    }

    if (!empty($_POST['type_indem']) && is_array($_POST['type_indem'])) {
        foreach ($_POST['type_indem'] as $index => $val) {
            $valTrimmed = strtolower(trim($val));
            if (!in_array($valTrimmed, $indemniteStandardsLower)) {
                $_POST['type_indem'][$index] = 'Autres (' . ucfirst($val) . ')';
            }
        }
    }


    $row = 2;
    foreach ($_POST as $cle => $valeur) {

        if (is_array($valeur)) {
            $valeur = implode(', ', $valeur);
        }

        $sheet->setCellValue("A$row", traduireChamp($cle));
        $sheet->setCellValue("B$row", $valeur);
        $row++;
    }

    // Entête en première ligne
    $sheet->setCellValue("A1", "Champ");
    $sheet->setCellValue("B1", "Valeur");

    $spreadsheet->getDefaultStyle()->applyFromArray([
        'font' => [
            'name' => 'Calibri',
            'size' => 11,
        ],
        'alignment' => [
            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            'wrapText' => true,
        ],
    ]);

    $sheet->getStyle('A1:B1')->applyFromArray([
        'font' => ['bold' => true, 'size' => 12],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'D9E1F2']
        ],
        'borders' => [
            'bottom' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]
        ],
    ]);

    for ($i = 2; $i < $row + 1; $i++) {
        $sheet->getStyle("A$i")->getFont()->setBold(true);
    }

    $sheet->getColumnDimension('A')->setAutoSize(true);
    $sheet->getColumnDimension('B')->setAutoSize(true);

    $sheet->getStyle("A1:B" . ($row - 1))->getBorders()->getAllBorders()->setBorderStyle(
        \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
    );

    $excelTemp = __DIR__ . '/temp_dossier.xlsx';
    $writer = new Xlsx($spreadsheet);
    $writer->save($excelTemp);

    $excelMetadata = new DriveFile([
        'name' => "Infos_personnelles_" . strtoupper($nom) . "_" . ucfirst($prenom) . ".xlsx",
        'parents' => [$folderId]
    ]);
    $excelContent = file_get_contents($excelTemp);
    $drive->files->create($excelMetadata, [
        'data' => $excelContent,
        'uploadType' => 'multipart',
        'fields' => 'id',
        'supportsAllDrives' => true
    ]);

    unlink($excelTemp);

    popup('success');
} catch (Throwable $e) {
    if (isset($folderId)) {
        deleteDriveFolder($drive, $folderId);
    }
    popup('error');
}
