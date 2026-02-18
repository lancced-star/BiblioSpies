<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require 'connexion-bdd.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: edit_profile.php');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id !== (int)$_SESSION['user_id']) {
    header('Location: index.php');
    exit;
}

$fullname = isset($_POST['fullname']) ? trim($_POST['fullname']) : null;
$email = isset($_POST['email']) ? trim($_POST['email']) : null;
$bio = isset($_POST['bio']) ? trim($_POST['bio']) : null;

if (empty($email)) {
    $_SESSION['flash'] = 'L\'email est requis.';
    header('Location: edit_profile.php');
    exit;
}

try {
    $stmt = $bdd->prepare('UPDATE users SET fullname = :fullname, email = :email, bio = :bio WHERE id = :id');
    $stmt->execute([
        ':fullname' => $fullname,
        ':email' => $email,
        ':bio' => $bio,
        ':id' => $id,
    ]);
    $_SESSION['flash'] = 'Profil mis à jour.';
} catch (Exception $e) {
    $_SESSION['flash'] = 'Erreur lors de la mise à jour du profil.';
}

header('Location: profile.php');
exit;
