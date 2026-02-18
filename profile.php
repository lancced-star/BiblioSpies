<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require 'connexion-bdd.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$id = (int)$_SESSION['user_id'];
try {
    $stmt = $bdd->prepare('SELECT id, username, email, fullname, bio, avatar FROM users WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $user = false;
}

require 'header.php';
?>
<main class="container">
  <section class="profile">
    <h2>Mon profil</h2>
    <?php if (!$user): ?>
      <p>Informations utilisateur introuvables.</p>
    <?php else: ?>
      <p><strong>Nom d'utilisateur :</strong> <?php echo htmlspecialchars($user['username']); ?></p>
      <p><strong>Nom complet :</strong> <?php echo htmlspecialchars($user['fullname'] ?? ''); ?></p>
      <p><strong>Email :</strong> <?php echo htmlspecialchars($user['email']); ?></p>
      <p><strong>À propos :</strong><br><?php echo nl2br(htmlspecialchars($user['bio'] ?? '')); ?></p>

      <p><a href="edit_profile.php">Modifier mon profil</a></p>
    <?php endif; ?>
  </section>
</main>

<?php require 'footer.php'; ?>
