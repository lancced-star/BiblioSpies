<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require 'header.php';
?>

<main class="auth-wrapper">
  <section class="auth-card">
    <div class="auth-header">
      <h2>Rejoindre la Confrérie</h2>
    </div>

    <?php if (!empty($_SESSION['flash'])) { 
        echo '<div class="flash-message">'.htmlspecialchars($_SESSION['flash']).'</div>'; 
        unset($_SESSION['flash']); 
    } ?>

    <form action="register_action.php" method="post" class="auth-form">
      
      <div class="form-group">
        <label for="username">Nom de code (Utilisateur)</label>
        <input type="text" id="username" name="username" placeholder="Ex: AgentLivre" required>
      </div>

      <div class="form-group">
        <label for="email">Canal de contact (Email)</label>
        <input type="email" id="email" name="email" placeholder="exemple@biblio.com" required>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="password">Mot de passe</label>
          <input type="password" id="password" name="password" required>
        </div>

        <div class="form-group">
          <label for="password_confirm">Confirmation</label>
          <input type="password" id="password_confirm" name="password_confirm" required>
        </div>
      </div>

      <div class="form-group">
        <label for="fullname">Identité réelle (Optionnel)</label>
        <input type="text" id="fullname" name="fullname" placeholder="Prénom Nom">
      </div>

      <button type="submit" class="btn btn--full">Signer le registre</button>
    </form>

    <div class="auth-footer">
      <p>Déjà initié ? <a href="login.php">Accéder aux archives</a></p>
    </div>
  </section>
</main>

<?php require 'footer.php'; ?>