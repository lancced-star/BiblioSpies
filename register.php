<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_SESSION['user_id'])) { header('Location: index.php'); exit; }
require 'header.php';
?>

<main class="auth-wrapper">
  <section class="auth-card">

    <div class="auth-header" style="text-align:center; margin-bottom:1.5rem;">
      <h2>🕵️ Rejoindre l'Agence</h2>
      <p style="color:var(--muted); font-size:0.88rem; margin-top:6px;">
        Un code de carte unique vous sera généré après inscription
      </p>
    </div>

    <?php if (!empty($_SESSION['flash'])): ?>
      <div style="background:#f8d7da; color:#721c24; padding:12px 16px; border-radius:8px; margin-bottom:1.2rem; font-size:0.9rem;">
        <?php echo htmlspecialchars($_SESSION['flash']); unset($_SESSION['flash']); ?>
      </div>
    <?php endif; ?>

    <form action="register_action.php" method="post" class="auth-form">

      <div class="form-row" style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
        <div class="form-group">
          <label for="prenom">Prénom *</label>
          <input type="text" id="prenom" name="prenom" placeholder="Ex : James"
                 value="<?php echo htmlspecialchars($_SESSION['old']['prenom'] ?? ''); ?>"
                 required autocomplete="given-name">
        </div>
        <div class="form-group">
          <label for="nom">Nom *</label>
          <input type="text" id="nom" name="nom" placeholder="Ex : Bond"
                 value="<?php echo htmlspecialchars($_SESSION['old']['nom'] ?? ''); ?>"
                 required autocomplete="family-name">
        </div>
      </div>

      <div class="form-group">
        <label for="username">Pseudo (unique) *</label>
        <input type="text" id="username" name="username" placeholder="Ex : Agent007"
               value="<?php echo htmlspecialchars($_SESSION['old']['username'] ?? ''); ?>"
               required minlength="3" maxlength="50"
               pattern="[a-zA-Z0-9_\-]{3,50}"
               title="Lettres, chiffres, _ et - uniquement">
        <small style="color:var(--muted); font-size:0.78rem;">Lettres, chiffres, _ et - uniquement (3–50 caractères)</small>
      </div>

      <?php unset($_SESSION['old']); ?>

      <button type="submit" class="btn btn--full" style="margin-top:10px;">
        🔐 Générer ma carte d'agent
      </button>
    </form>

    <div class="auth-footer">
      <p>Déjà agent ? <a href="login.php">Se connecter</a></p>
    </div>

  </section>
</main>

<?php require 'footer.php'; ?>
