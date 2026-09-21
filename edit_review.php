<?php
require 'connexion-bdd.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }

$id     = (int)($_POST['id']     ?? 0);
$isbn   = trim($_POST['isbn']    ?? '');
$rating = (int)($_POST['rating'] ?? 3);
$text   = trim($_POST['review']  ?? '');

if ($id <= 0 || empty($text)) { header('Location: book-page.php?isbn=' . urlencode($isbn) . '&edited=0'); exit; }
if (empty($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$userId  = (int)$_SESSION['user_id'];
$isAdmin = !empty($_SESSION['is_admin']);

try {
    if ($isAdmin) {
        $bdd->prepare("UPDATE `avis` SET `rating`=?, `contenu`=? WHERE `id`=?")
            ->execute([$rating, $text, $id]);
    } else {
        $bdd->prepare("UPDATE `avis` SET `rating`=?, `contenu`=? WHERE `id`=? AND `user_id`=?")
            ->execute([$rating, $text, $id, $userId]);
    }
    header('Location: book-page.php?isbn=' . urlencode($isbn) . '&edited=1'); exit;
} catch (Exception $e) {
    header('Location: book-page.php?isbn=' . urlencode($isbn) . '&edited=0'); exit;
}
