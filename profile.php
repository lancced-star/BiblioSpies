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
    <div class="profile-header">
      <h2>Mon profil</h2>
      <!-- Bouton de déconnexion dans l'onglet profil -->
      <a href="deconnexion-bdd.php" class="btn-logout" onclick="return confirm('Voulez-vous vraiment vous déconnecter ?')">
        🔐 Se déconnecter
      </a>
    </div>

    <?php if (isset($_SESSION['flash'])): ?>
      <p class="flash-message"><?php echo htmlspecialchars($_SESSION['flash']); unset($_SESSION['flash']); ?></p>
    <?php endif; ?>

    <?php if (!$user): ?>
      <p>Informations utilisateur introuvables.</p>
    <?php else: ?>

      <!-- Avatar -->
      <div class="profile-avatar">
        <?php
          $avatar = !empty($user['avatar']) ? $user['avatar'] : 'avatar-placeholder.svg';
        ?>
        <img src="<?php echo htmlspecialchars($avatar); ?>" alt="Photo de profil">
      </div>

      <p><strong>Nom d'utilisateur :</strong> <?php echo htmlspecialchars($user['username']); ?></p>
      <p><strong>Nom complet :</strong> <?php echo htmlspecialchars($user['fullname'] ?? ''); ?></p>
      <p><strong>Email :</strong> <?php echo htmlspecialchars($user['email']); ?></p>
      <p><strong>À propos :</strong><br><?php echo nl2br(htmlspecialchars($user['bio'] ?? '')); ?></p>

      <div class="profile-actions" style="display:flex; gap:12px; flex-wrap:wrap; margin-top:16px;">
        <a href="edit_profile.php" class="btn-edit">✏️ Modifier mon profil</a>
        <a href="mes_messages.php" style="display:inline-block; background:rgba(132,106,83,0.1); color:var(--accent); padding:8px 16px; border-radius:6px; text-decoration:none; font-weight:600;">
          📨 Mes messages
        </a>
      </div>
    <?php endif; ?>
  </section>
</main>

<?php require 'footer.php'; ?>
