<?php
require_once 'connexion-bdd.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) || 
          (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

if (empty($_SESSION['user_id'])) {
    if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['error' => 'Non connecté', 'auth' => false]); }
    else { header('Location: login.php'); }
    exit;
}

$userId  = (int)$_SESSION['user_id'];
$isbn    = trim($_POST['isbn'] ?? '');
$referer = $_SERVER['HTTP_REFERER'] ?? 'profile.php';

if (empty($isbn)) {
    if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['error' => 'ISBN manquant']); }
    else { header('Location: ' . $referer); }
    exit;
}

try {
    $bdd->exec("CREATE TABLE IF NOT EXISTS favoris (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        user_id    INT NOT NULL,
        isbn       VARCHAR(20) NOT NULL,
        created_at DATETIME DEFAULT NOW(),
        UNIQUE KEY unique_favori (user_id, isbn)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {}

$chk = $bdd->prepare('SELECT id FROM favoris WHERE user_id = ? AND isbn = ?');
$chk->execute([$userId, $isbn]);
$existing = $chk->fetch();

if ($existing) {
    $bdd->prepare('DELETE FROM favoris WHERE user_id = ? AND isbn = ?')->execute([$userId, $isbn]);
    $status = 'removed';
} else {
    $bdd->prepare('INSERT INTO favoris (user_id, isbn) VALUES (?, ?)')->execute([$userId, $isbn]);
    $status = 'added';
}

if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode(['status' => $status]);
} else {
    // Redirect back to where we came from
    if (strpos($referer, 'book-page.php') !== false) {
        header('Location: ' . $referer);
    } else {
        header('Location: profile.php?updated=1');
    }
}
