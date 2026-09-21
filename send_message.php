<?php
require_once 'connexion-bdd.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Non connecté']);
    exit;
}

$senderId    = (int)$_SESSION['user_id'];
$receiverId  = (int)($_POST['receiver_id'] ?? 0);
$contenu     = trim($_POST['contenu'] ?? '');

if ($receiverId <= 0 || $contenu === '') {
    echo json_encode(['error' => 'Données manquantes']);
    exit;
}
if ($receiverId === $senderId) {
    echo json_encode(['error' => 'Impossible de se envoyer un message à soi-même']);
    exit;
}
if (mb_strlen($contenu) > 1000) {
    echo json_encode(['error' => 'Message trop long (1000 caractères max)']);
    exit;
}

// Créer table si besoin
try {
    $bdd->exec("CREATE TABLE IF NOT EXISTS messages_prives (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        receiver_id INT NOT NULL,
        contenu TEXT NOT NULL,
        lu TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT NOW()
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {}

// Vérifier que le receiver existe
$chk = $bdd->prepare('SELECT id FROM users WHERE id = ?');
$chk->execute([$receiverId]);
if (!$chk->fetch()) {
    echo json_encode(['error' => 'Utilisateur introuvable']);
    exit;
}

$stmt = $bdd->prepare('INSERT INTO messages_prives (sender_id, receiver_id, contenu) VALUES (?, ?, ?)');
$stmt->execute([$senderId, $receiverId, $contenu]);

echo json_encode(['status' => 'sent', 'id' => $bdd->lastInsertId()]);
