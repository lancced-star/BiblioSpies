<?php
require 'admin_auth.php';

$isbn = trim($_GET['isbn'] ?? '');

if (empty($isbn)) {
    header('Location: admin_livres.php');
    exit;
}

try {
    // Récupérer le nom de l'image avant suppression pour la supprimer du disque
    $stmt = $bdd->prepare('SELECT image FROM Livre WHERE isbn = :isbn');
    $stmt->execute([':isbn' => $isbn]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        $_SESSION['flash'] = '⚠️ Livre introuvable.';
        header('Location: admin_livres.php');
        exit;
    }

    // Supprimer les auteurs liés (table Auteur)
    $bdd->prepare('DELETE FROM Auteur WHERE idLivre = :isbn')->execute([':isbn' => $isbn]);

    // Supprimer le livre
    $bdd->prepare('DELETE FROM Livre WHERE isbn = :isbn')->execute([':isbn' => $isbn]);

    // Supprimer l'image du serveur si elle existe (et n'est pas un placeholder)
    if (!empty($row['image'])) {
        $imgFile = __DIR__ . '/' . $row['image'];
        if (file_exists($imgFile)) {
            unlink($imgFile);
        }
    }

    $_SESSION['flash'] = '✅ Livre supprimé avec succès.';

} catch (Exception $e) {
    $_SESSION['flash'] = '❌ Erreur lors de la suppression : ' . $e->getMessage();
}

header('Location: admin_livres.php');
exit;
