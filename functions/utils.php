<?php

require_once __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

// Nettoyage des champs conditionnels
function nettoyerChampsConditionnels(&$post)
{
    if (!isset($post['financement']) || !in_array('region', (array)$post['financement'])) {
        unset($post['validation']);
    }
    if (!isset($post['france-travail'])) {
        unset($post['date_inscription_france_travail']);
    }
    if (!isset($post['indemnites'])) {
        unset($post['type_indem']);
    }
    unset($post['opt-financement-autre'], $post['opt-autre-indemnite'], $post['consentement']);
    unset($post['indemnites'], $post['lieu_naissance_cp'], $post['lieu_naissance_ville']);
}

function traduireChamp($cle)
{
    $traductions = [
        'nom' => "Nom",
        'prenom' => "Prénom",
        'nom_usage' => "Nom d’usage",
        'nom_jeune_fille' => "Nom de jeune fille",
        'email' => "Adresse e-mail",
        'tel' => "Numéro de téléphone",
        'adresse' => "Adresse postale",
        'code_postal' => "Code postal",
        'ville' => "Ville",
        'date_naissance' => "Date de naissance",
        'lieu_naissance_cp' => "Code postal de naissance",
        'lieu_naissance_ville' => "Ville de naissance",
        'secu' => "N° Sécurité sociale",
        'msa' => "N° MSA",
        'nationalite' => "Nationalité",
        'situation_familiale' => "Situation familiale",
        'niveau_etude' => "Niveau d’études",
        'date_fin_scolarite' => "Date de fin de scolarité",
        'diplome_obtenu' => "Diplôme obtenu",
        'identifiant_france_travail' => "Identifiant France Travail",
        'france-travail' => "Inscrit à France Travail",
        'date_inscription_france_travail' => "Date d’inscription à France Travail",
        'rqth' => "Reconnaissance Travailleur Handicapé (RQTH)",
        'type_indem' => "Type d’indemnités perçues",
        'financement' => "Mode de financement",
        'validation' => "Validation du financement Région",
        'mission_locale_coordonnees' => "Coordonnées de la Mission Locale",
        'situation' => "Situation professionnelle",
    ];

    return $traductions[$cle] ?? ucfirst(str_replace('_', '  ', $cle));
}

// Affichage d'un message HTML stylisé
function popup($type, $customMessage = null)
{
    $contactEmail = htmlspecialchars($_ENV['CONTACT_EMAIL'] ?? 'contact@example.com');
    $contactPhone = htmlspecialchars($_ENV['CONTACT_PHONE'] ?? '+33 0 00 00 00 00');
    $retourUrl = htmlspecialchars($_ENV['RETOUR_URL'] ?? '#');

    $messages = [
        'success' => "<i class='fa-solid fa-square-check success-icon fa-2x'></i> <p id='msg'>Votre dossier a été envoyé avec succès !</p>",
        'exist'   => "
            <i class='fa-solid fa-folder folder-icon fa-2x'></i>
            <p id='msg'>Nous avons déjà reçu un dossier à votre nom.</p>
            <p>Si vous souhaitez <strong>modifier</strong> ou <strong>supprimer</strong> votre dossier, n’hésitez pas à nous contacter :</p>
            <div id='contacts'>
                <div class='contact-item'>
                    <i class='fa-solid fa-envelope'></i>
                    <span>{$contactEmail}</span>
                </div>
                <div class='contact-item'>
                    <i class='fa-solid fa-phone'></i>
                    <span>{$contactPhone}</span>
                </div>
            </div>
            <p class='info-delay'>Votre demande sera traitée dans les plus brefs délais.</p>",
        'error' => "<i class='fa-solid fa-triangle-exclamation error-icon fa-2x'></i> <p id='msg'>Une erreur s'est produite lors de la soumission de votre dossier.</p>"
    ];

    $msg = $customMessage ?: $messages[$type];

    echo <<<HTML
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title>Confirmation</title>
        <link rel="stylesheet" href="form.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
        <meta name="viewport" content="width=device-width, initial-scale=1">
    </head>
    <body id="msg-body">
        <div class="message {$type}-msg">
            <p>{$msg}</p>
            <a href="{$retourUrl}" class="btn-retour">Poursuivre ma navigation</a>
        </div>
    </body>
    </html>
    HTML;
}
