<?php
require 'connexion-bdd.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$isbn = isset($_POST['isbn']) ? trim($_POST['isbn']) : '';
$name = isset($_POST['name']) ? trim($_POST['name']) : null;
$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : null;
$review = isset($_POST['review']) ? trim($_POST['review']) : '';

if (empty($isbn) || empty($review)) {
    header('Location: book-page.php?isbn=' . urlencode($isbn) . '&saved=0');
    exit;
}

try {
    // Ensure table exists
    $bdd->exec("CREATE TABLE IF NOT EXISTS review (
        id INT AUTO_INCREMENT PRIMARY KEY,
        isbn VARCHAR(15) NOT NULL,
        name VARCHAR(255) DEFAULT NULL,
        rating TINYINT DEFAULT NULL,
        review TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $stmt = $bdd->prepare('INSERT INTO review (isbn, name, rating, review) VALUES (:isbn, :name, :rating, :review)');
    $stmt->execute([
        ':isbn' => $isbn,
        ':name' => $name,
        ':rating' => $rating,
        ':review' => $review
    ]);

    header('Location: book-page.php?isbn=' . urlencode($isbn) . '&saved=1');
    exit;
} catch (Exception $e) {
    header('Location: book-page.php?isbn=' . urlencode($isbn) . '&saved=0');
    exit;
}

?>
