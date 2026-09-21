<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require 'connexion-bdd.php';

if (isset($_SESSION['user_id'])) { header('Location: index.php'); exit; }

// Générer token CSRF si inexistant
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$erreur = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier token CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $erreur = 'Session expirée. Actualisez la page.';
    } else {
        $username  = trim($_POST['username']   ?? '');
        $carteCode = trim($_POST['carte_code'] ?? '');

        if (empty($username) || empty($carteCode)) {
            $erreur = 'Les deux champs sont obligatoires.';
        } else {
            $stmt = $bdd->prepare('SELECT id, prenom, nom, username, is_admin FROM users WHERE username = :username AND carte_code = :carte_code');
            $stmt->execute([':username' => $username, ':carte_code' => $carteCode]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Régénérer l'ID de session pour éviter session fixation
                session_regenerate_id(true);
                // Connexion réussie
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['username']  = $user['username'];
                $_SESSION['is_admin']  = $user['is_admin'];
                header('Location: index.php');
                exit;
            } else {
                $erreur = '❌ Pseudo ou code de carte incorrect.';
            }
        }
    }
}

require 'header.php';
?>
<?php if (!empty($erreurMotInterdit)): ?>
  <div class="erreur"><?php echo htmlspecialchars($erreurMotInterdit); ?></div>
<?php endif; ?>

<main class="auth-wrapper">
  <section class="auth-card">

    <div class="auth-header" style="text-align:center; margin-bottom:1.5rem;">
      <h2>🔐 Accès aux archives</h2>
      <p style="color:var(--muted); font-size:0.88rem; margin-top:6px;">
        Entrez votre pseudo et votre code de carte d'agent
      </p>
    </div>

    <?php if ($erreur): ?>
      <div style="background:#f8d7da; color:#721c24; padding:12px 16px; border-radius:8px; margin-bottom:1.2rem; font-size:0.9rem;">
        <?php echo htmlspecialchars($erreur); ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['flash'])): ?>
      <div style="background:#d4edda; color:#155724; padding:12px 16px; border-radius:8px; margin-bottom:1.2rem; font-size:0.9rem;">
        <?php echo htmlspecialchars($_SESSION['flash']); unset($_SESSION['flash']); ?>
      </div>
    <?php endif; ?>

    <form method="post" action="login.php" class="auth-form">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

      <div class="form-group">
        <label for="username">Pseudo</label>
        <input type="text" id="username" name="username"
               placeholder="Ex : Agent007"
               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
               required autocomplete="username">
      </div>

      <div class="form-group">
        <label for="carte_code">Code de carte</label>
        <input type="text" id="carte_code" name="carte_code"
               placeholder="Ex : BSP-AGE-4f9a2b1c"
               style="font-family:'Courier New',monospace; letter-spacing:1px;"
               required autocomplete="off">
        <small style="color:var(--muted); font-size:0.78rem;">
          Le code généré lors de votre inscription (format BSP-XXX-xxxxxxxx)
        </small>
      </div>

  <button type="submit" style="padding:11px 24px;background: var(--accent-3); font-size:0.9rem;color: white;cursor: pointer;border-radius:8px;font-weight: 600; text-align:center;width:100%;">
        → Entrer dans les archives
      </button>

    </form>

    <div class="auth-footer">
      <p>Pas encore agent ? <a href="register.php">Créer mon identité</a></p>
      <p style="margin-top:8px;"><a href="recuperation.php?mode=pseudo" style="color:var(--muted); font-size:0.85rem;">🕵️ Pseudo oublié ?</a></p>
      <p style="margin-top:8px;"><a href="recuperation.php?mode=carte" style="color:var(--muted); font-size:0.85rem;">🗂️ Code de carte perdu ?</a></p>
      <p style="margin-top:8px;"><a href="recuperation.php?mode=les-deux" style="color:var(--muted); font-size:0.85rem;">❓ Pseudo et code oubliés ?</a></p>

    </div>

  </section>
</main>

<?php require 'footer.php'; ?>
