<?php
require 'connexion-bdd.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$isbn = isset($_POST['isbn']) ? trim($_POST['isbn']) : '';
$name = isset($_POST['name']) ? trim($_POST['name']) : null;
$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : null;
$review = isset($_POST['review']) ? trim($_POST['review']) : '';

if ($id <= 0 || empty($review)) {
    header('Location: book-page.php?isbn=' . urlencode($isbn) . '&edited=0');
    exit;
}

try {
    $stmt = $bdd->prepare('UPDATE review SET name = :name, rating = :rating, review = :review WHERE id = :id');
    $stmt->execute([
        ':name' => $name,
        ':rating' => $rating,
        ':review' => $review,
        ':id' => $id
    ]);

    header('Location: book-page.php?isbn=' . urlencode($isbn) . '&edited=1');
    exit;
} catch (Exception $e) {
    header('Location: book-page.php?isbn=' . urlencode($isbn) . '&edited=0');
    exit;
}

?>
