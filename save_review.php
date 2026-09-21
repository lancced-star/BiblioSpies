<?php
require 'connexion-bdd.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php'); exit;
}

$isbn   = trim($_POST['isbn']   ?? '');
$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 3;
$text   = trim($_POST['review'] ?? '');

if (empty($isbn) || empty($text)) {
    header('Location: book-page.php?isbn=' . urlencode($isbn) . '&saved=0'); exit;
}

if (empty($_SESSION['user_id'])) {
    header('Location: login.php'); exit;
}

$userId   = (int)$_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Agent';

try {
    // ── Étape 1 : créer la table proprement ──────────────────────
    $bdd->exec("CREATE TABLE IF NOT EXISTS `avis` (
        `id`         INT AUTO_INCREMENT PRIMARY KEY,
        `isbn`       VARCHAR(20) NOT NULL,
        `user_id`    INT DEFAULT NULL,
        `name`       VARCHAR(255) DEFAULT NULL,
        `rating`     TINYINT DEFAULT NULL,
        `contenu`    TEXT NOT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // ── Étape 2 : migration depuis l'ancienne table "review" ─────
    try {
        $tables = $bdd->query("SHOW TABLES LIKE 'review'")->fetchAll();
        if (!empty($tables)) {
            // Vérifie si avis est vide mais review a des données
            $countAvis   = (int)$bdd->query("SELECT COUNT(*) FROM `avis`")->fetchColumn();
            $countReview = (int)$bdd->query("SELECT COUNT(*) FROM `review`")->fetchColumn();
            if ($countReview > 0 && $countAvis === 0) {
                // Détecte le bon nom de colonne dans review
                $reviewCols = $bdd->query("SHOW COLUMNS FROM `review`")->fetchAll(PDO::FETCH_COLUMN);
                $textCol    = in_array('review_text', $reviewCols) ? 'review_text' : '`review`';
                $uidCol     = in_array('user_id', $reviewCols) ? 'user_id' : 'NULL';
                $bdd->exec("INSERT INTO `avis` (isbn, user_id, name, rating, contenu, created_at)
                    SELECT isbn, {$uidCol}, name, rating, {$textCol}, created_at FROM `review`");
            }
        }
    } catch (Exception $em) {}

    // ── Étape 3 : upsert avis ────────────────────────────────────
    $chk = $bdd->prepare("SELECT id FROM `avis` WHERE isbn = ? AND user_id = ?");
    $chk->execute([$isbn, $userId]);
    $existing = $chk->fetch();

    if ($existing) {
        $bdd->prepare("UPDATE `avis` SET `name`=?, `rating`=?, `contenu`=? WHERE `isbn`=? AND `user_id`=?")
            ->execute([$username, $rating, $text, $isbn, $userId]);
    } else {
        $bdd->prepare("INSERT INTO `avis` (`isbn`, `user_id`, `name`, `rating`, `contenu`) VALUES (?,?,?,?,?)")
            ->execute([$isbn, $userId, $username, $rating, $text]);
    }

    header('Location: book-page.php?isbn=' . urlencode($isbn) . '&saved=1'); exit;

} catch (Exception $e) {
    error_log('save_review error: ' . $e->getMessage());
    header('Location: book-page.php?isbn=' . urlencode($isbn) . '&saved=0&err=' . urlencode($e->getMessage())); exit;
}
