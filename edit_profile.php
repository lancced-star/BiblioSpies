<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require 'connexion-bdd.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$id = (int)$_SESSION['user_id'];
try {
    $stmt = $bdd->prepare('SELECT id, username, email, fullname, bio FROM users WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $user = false;
}

require 'header.php';
?>
<main class="container">
  <section class="profile-edit">
    <h2>Modifier mon profil</h2>
    <?php if (!$user): ?>
      <p>Impossible de charger vos informations.</p>
    <?php else: ?>
      <form action="save_profile.php" method="post">
        <label for="fullname">Nom complet</label>
        <input type="text" id="fullname" name="fullname" value="<?php echo htmlspecialchars($user['fullname'] ?? ''); ?>">

        <label for="email">Email</label>
        <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">

        <label for="bio">À propos</label>
        <textarea id="bio" name="bio" rows="6"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>

        <input type="hidden" name="id" value="<?php echo (int)$user['id']; ?>">
        <button type="submit">Enregistrer</button>
        <a href="profile.php">Annuler</a>
      </form>
    <?php endif; ?>
  </section>
</main>

<?php require 'footer.php'; ?>
