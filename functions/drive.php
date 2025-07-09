<?php
require_once __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}


// Google Client (Service Account)
function getClient()
{
    $client = new Google_Client();
    $client->setApplicationName($_ENV['GOOGLE_APP_NAME']);
    $client->setScopes($_ENV['GOOGLE_DRIVE_SCOPE']);
    //$client->setAuthConfig($_ENV['GOOGLE_CREDENTIALS_PATH']);
    $serviceAccountJson = $_ENV['GOOGLE_SERVICE_ACCOUNT_JSON'];
    if ($serviceAccountJson) {
        $client->setAuthConfig(json_decode($serviceAccountJson, true));
    } else {
        throw new Exception('Service account JSON not found');
    }
    
    $client->useApplicationDefaultCredentials();
    return $client;
}

// Vérifie si un dossier existe déjà dans le Drive

function checkDrive($drive, $parentId, $secu = null, $msa = null)
{
    // On utilise le NIR (numéro sécurité sociale), sinon MSA
    $identifiant = $secu ?: $msa;

    // Si aucun identifiant disponible
    if (!$identifiant) {
        return false; // pas de contrôle possible
    }

    $identifiant = preg_replace('/\s+/', '', $identifiant); // Nettoyage

    // Requête pour chercher un dossier contenant cet identifiant
    $params = [
        'q' => sprintf(
            "name contains '%s' and mimeType='application/vnd.google-apps.folder' and '%s' in parents and trashed=false",
            addslashes($identifiant),
            $parentId
        ),
        'spaces' => 'drive',
        'fields' => 'files(id, name)',
    ];

    $results = $drive->files->listFiles($params);

    return count($results->getFiles()) > 0;
}

function deleteDriveFolder($drive, $folderId)
{
    try {
        $drive->files->delete($folderId);
    } catch (Exception $e) {
        // On peut logger l'erreur mais on n'interrompt pas le processus
    }
}


function getOrCreateDriveFolder($drive, $name, $parentId)
{
    $params = [
        'q' => sprintf("name = '%s' and mimeType = 'application/vnd.google-apps.folder' and '%s' in parents and trashed = false", addslashes($name), $parentId),
        'spaces' => 'drive',
        'fields' => 'files(id, name)',
    ];
    $results = $drive->files->listFiles($params);

    if (count($results->getFiles()) > 0) {
        return $results->getFiles()[0]->getId();
    }

    $folderMetadata = new Google\Service\Drive\DriveFile([
        'name' => $name,
        'mimeType' => 'application/vnd.google-apps.folder',
        'parents' => [$parentId],
    ]);
    $folder = $drive->files->create($folderMetadata, ['fields' => 'id']);

    return $folder->id;
}




?>
