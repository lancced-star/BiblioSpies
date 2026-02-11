<?php
require 'connexion-bdd.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$isbn = isset($_POST['isbn']) ? trim($_POST['isbn']) : '';

if ($id <= 0) {
    header('Location: book-page.php?isbn=' . urlencode($isbn) . '&deleted=0');
    exit;
}

try {
    $stmt = $bdd->prepare('DELETE FROM review WHERE id = :id');
    $stmt->execute([':id' => $id]);
    header('Location: book-page.php?isbn=' . urlencode($isbn) . '&deleted=1');
    exit;
} catch (Exception $e) {
    header('Location: book-page.php?isbn=' . urlencode($isbn) . '&deleted=0');
    exit;
}

?>
