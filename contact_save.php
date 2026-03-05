<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require 'connexion-bdd.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: contact.php');
    exit;
}

// ── Récupération & nettoyage ─────────────────────────────────
$prenom     = ucfirst(strtolower(trim($_POST['prenom']     ?? '')));
$nom        = ucfirst(strtolower(trim($_POST['nom']        ?? '')));
$pseudo     = trim($_POST['pseudo']     ?? '');
$carte_code = trim($_POST['carte_code'] ?? '');
$sujet      = trim($_POST['sujet']      ?? '');
$message    = trim($_POST['message']    ?? '');

// ── Validations ──────────────────────────────────────────────
$erreurs = [];
if (empty($prenom))              $erreurs[] = 'Le prénom est obligatoire.';
if (empty($nom))                 $erreurs[] = 'Le nom est obligatoire.';
if (empty($sujet))               $erreurs[] = 'Veuillez choisir un sujet.';
if (empty($message))             $erreurs[] = 'Le message est obligatoire.';
if (strlen($message) < 10)       $erreurs[] = 'Le message est trop court (10 caractères minimum).';

if (!empty($erreurs)) {
    $_SESSION['flash_contact_err'] = implode(' — ', $erreurs);
    $_SESSION['old_contact'] = compact('prenom', 'nom', 'pseudo', 'carte_code', 'sujet', 'message');
    header('Location: contact.php');
    exit;
}

// ── Sauvegarde en BDD ────────────────────────────────────────
try {
    $stmt = $bdd->prepare('
        INSERT INTO contacts (prenom, nom, pseudo, carte_code, sujet, message)
        VALUES (:prenom, :nom, :pseudo, :carte_code, :sujet, :message)
    ');
    $stmt->execute([
        ':prenom'     => $prenom,
        ':nom'        => $nom,
        ':pseudo'     => $pseudo,
        ':carte_code' => $carte_code,
        ':sujet'      => $sujet,
        ':message'    => $message,
    ]);

    $_SESSION['flash_contact_ok'] = "Merci $prenom ! Un administrateur vous répondra dès que possible.";

} catch (Exception $e) {
    $_SESSION['flash_contact_err'] = '❌ Erreur lors de l\'envoi. Veuillez réessayer.';
    $_SESSION['old_contact'] = compact('prenom', 'nom', 'pseudo', 'carte_code', 'sujet', 'message');
}

header('Location: contact.php');
exit;
