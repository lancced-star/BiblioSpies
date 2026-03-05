<?php
// ============================================================
// admin_auth.php — À inclure en tête de chaque page admin
// Vérifie que l'utilisateur est connecté ET administrateur
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'connexion-bdd.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Vérification en BDD (on ne fait pas confiance à la session seule)
$stmtAdmin = $bdd->prepare('SELECT is_admin FROM users WHERE id = :id');
$stmtAdmin->execute([':id' => (int)$_SESSION['user_id']]);
$adminRow = $stmtAdmin->fetch(PDO::FETCH_ASSOC);

if (!$adminRow || $adminRow['is_admin'] != 1) {
    http_response_code(403);
    die('<h1 style="font-family:sans-serif;text-align:center;margin-top:80px">
         🚫 Accès refusé — Zone réservée aux administrateurs
         <br><br><a href="index.php">← Retour à l\'accueil</a></h1>');
}
