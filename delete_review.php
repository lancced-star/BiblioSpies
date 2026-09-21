<?php
require 'connexion-bdd.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }

$id   = (int)($_POST['id']   ?? 0);
$isbn = trim($_POST['isbn']  ?? '');

if ($id <= 0) { header('Location: book-page.php?isbn=' . urlencode($isbn) . '&deleted=0'); exit; }
if (empty($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$userId  = (int)$_SESSION['user_id'];
$isAdmin = !empty($_SESSION['is_admin']);

try {
    if ($isAdmin) {
        $bdd->prepare("DELETE FROM `avis` WHERE `id`=?")->execute([$id]);
    } else {
        $bdd->prepare("DELETE FROM `avis` WHERE `id`=? AND `user_id`=?")->execute([$id, $userId]);
    }
    header('Location: book-page.php?isbn=' . urlencode($isbn) . '&deleted=1'); exit;
} catch (Exception $e) {
    header('Location: book-page.php?isbn=' . urlencode($isbn) . '&deleted=0'); exit;
}
