<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require 'connexion-bdd.php';

// Bloquer l'accès aux comptes admin et aux noms contenant "admin"
$bloquerAcces = false;
if (isset($_SESSION['user_id'])) {
    if (!empty($_SESSION['is_admin'])) {
        $bloquerAcces = true;
    } else {
        // Vérifier si le nom/prénom/pseudo contient "admin" (insensible à la casse)
        $nomComplet = strtolower(($_SESSION['prenom'] ?? '') . ' ' . ($_SESSION['nom'] ?? '') . ' ' . ($_SESSION['username'] ?? ''));
        if (strpos($nomComplet, 'admin') !== false) {
            $bloquerAcces = true;
        }
    }
}

if ($bloquerAcces) {
    http_response_code(403);
    die('<h1 style="font-family:sans-serif;text-align:center;margin-top:80px">
         🚫 Accès refusé — Cette fonctionnalité n\'est pas disponible pour les comptes administrateur
         <br><br><a href="index.php">← Retour à l\'accueil</a></h1>');
}

require 'header.php';

// Mode passé en GET (depuis login.php) ou en POST (après soumission)
$mode     = $_GET['mode']  ?? $_POST['mode'] ?? 'pseudo';
$resultat = null;
$erreur   = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($mode === 'pseudo') {
        $carteCode = trim($_POST['carte_code'] ?? '');
        if (empty($carteCode)) {
            $erreur = 'Veuillez entrer votre code de carte.';
        } else {
            $stmt = $bdd->prepare('SELECT username FROM users WHERE carte_code = :code AND is_admin = 0 AND LOWER(CONCAT(prenom, " ", nom, " ", username)) NOT LIKE "%admin%"');
            $stmt->execute([':code' => $carteCode]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                $erreur = '❌ Aucun agent trouvé avec ce code de carte.';
            } else {
                $resultat = ['type' => 'pseudo', 'username' => $row['username']];
            }
        }

    } elseif ($mode === 'carte') {
        $username = trim($_POST['username'] ?? '');
        if (empty($username)) {
            $erreur = 'Veuillez entrer votre pseudo.';
        } else {
            $stmt = $bdd->prepare('SELECT username, carte_code FROM users WHERE LOWER(username) = LOWER(:username) AND is_admin = 0 AND LOWER(CONCAT(prenom, " ", nom, " ", username)) NOT LIKE "%admin%"');
            $stmt->execute([':username' => $username]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                $erreur = '❌ Aucun agent trouvé avec ce pseudo.';
            } else {
                $resultat = ['type' => 'carte', 'data' => $row];
            }
        }
    }
}

// Titres et descriptions selon le mode
$config = [
    'pseudo'   => ['titre' => '🕵️ Pseudo oublié',         'desc' => 'Entrez votre code de carte pour retrouver votre pseudo.'],
    'carte'    => ['titre' => '🗂️ Code de carte perdu',    'desc' => 'Entrez votre pseudo pour retrouver votre code de carte.'],
    'les-deux' => ['titre' => '❓ Pseudo et code oubliés', 'desc' => 'Voici comment retrouver votre accès.'],
];
$current = $config[$mode] ?? $config['pseudo'];
?>

<main class="auth-wrapper">
  <section class="auth-card" style="max-width:500px;">

    <!-- Retour -->
    <p style="margin-bottom:18px;">
      <a href="login.php" style="color:var(--accent-2); font-size:0.88rem;">← Retour à la connexion</a>
    </p>

    <div style="text-align:center; margin-bottom:1.8rem;">
      <h2><?php echo $current['titre']; ?></h2>
      <p style="color:var(--muted); font-size:0.88rem; margin-top:6px;"><?php echo $current['desc']; ?></p>
    </div>

    <!-- ══ RÉSULTAT ══ -->
    <?php if ($resultat): ?>
      <?php if ($resultat['type'] === 'pseudo'): ?>
        <div style="background:#d4edda; border-radius:12px; padding:24px; text-align:center; margin-bottom:20px;">
          <p style="color:#155724; font-size:0.85rem; margin-bottom:10px;">✅ Votre pseudo est :</p>
          <p style="font-family:'Courier New',monospace; font-size:1.5rem; font-weight:700; color:var(--accent); letter-spacing:2px;">
            @<?php echo htmlspecialchars($resultat['username']); ?>
          </p>
          <a href="login.php" style="display:inline-block; margin-top:16px; background:var(--accent-2); color:white; padding:10px 28px; border-radius:8px; font-weight:600; font-size:0.9rem; text-decoration:none;">
            → Se connecter
          </a>
        </div>

      <?php elseif ($resultat['type'] === 'carte'): ?>
        <div style="background:#1a1a2e; border-radius:12px; padding:24px; text-align:center; margin-bottom:20px;">
          <p style="color:rgba(255,255,255,0.5); font-size:0.82rem; margin-bottom:12px;">✅ Votre code de carte :</p>
          <p id="code-recupere" style="font-family:'Courier New',monospace; font-size:1.3rem; font-weight:700; color:#e8c97a; letter-spacing:3px;">
            <?php echo htmlspecialchars($resultat['data']['carte_code']); ?>
          </p>
          <div style="margin-top:16px; display:flex; gap:10px; justify-content:center; flex-wrap:wrap;">
            <button onclick="copierCode()" style="background:rgba(232,201,122,0.15); border:1px solid rgba(232,201,122,0.4); color:#e8c97a; padding:8px 20px; border-radius:8px; cursor:pointer; font-size:0.88rem; font-weight:600;">
              📋 Copier
            </button>
            <a href="login.php" style="background:var(--accent-2); color:white; padding:8px 20px; border-radius:8px; font-weight:600; font-size:0.88rem; text-decoration:none;">
              → Se connecter
            </a>
          </div>
        </div>
      <?php endif; ?>
    <?php endif; ?>

    <!-- ══ ERREUR ══ -->
    <?php if ($erreur): ?>
      <div style="background:#f8d7da; color:#721c24; padding:12px 16px; border-radius:8px; margin-bottom:20px; font-size:0.9rem;">
        <?php echo htmlspecialchars($erreur); ?>
      </div>
    <?php endif; ?>

    <!-- ══ FORMULAIRE PSEUDO OUBLIÉ ══ -->
    <?php if ($mode === 'pseudo'): ?>
      <form method="post" action="recuperation.php?mode=pseudo" class="auth-form">
        <input type="hidden" name="mode" value="pseudo">
        <div class="form-group">
          <label>Code de carte *</label>
          <input type="text" name="carte_code" required
                 placeholder="Ex : BSP-AGE-4f9a2b1c"
                 style="font-family:'Courier New',monospace; letter-spacing:1px;">
          <small style="color:var(--muted); font-size:0.78rem;">Votre code unique reçu à l'inscription</small>
        </div>
        <button type="submit" class="btn btn--full">🔍 Retrouver mon pseudo</button>
      </form>

    <!-- ══ FORMULAIRE CODE OUBLIÉ ══ -->
    <?php elseif ($mode === 'carte'): ?>
      <form method="post" action="recuperation.php?mode=carte" class="auth-form">
        <input type="hidden" name="mode" value="carte">
        <div class="form-group">
          <label>Pseudo *</label>
          <input type="text" name="username" required placeholder="Ex : Agent007">
        </div>
        <button type="submit" class="btn btn--full" style="background:var(--accent-2);">🔍 Retrouver mon code de carte</button>
      </form>

    <!-- ══ LES DEUX OUBLIÉS ══ -->
    <?php elseif ($mode === 'les-deux'): ?>
      <div style="display:flex; flex-direction:column; gap:16px;">

        <!-- Explication -->
        <div style="background:rgba(185,33,33,0.08); border:1px solid rgba(185,33,33,0.2); border-radius:10px; padding:18px;">
          <p style="font-weight:700; color:var(--accent); margin-bottom:8px;">😬 Vous avez oublié les deux ?</p>
          <p style="color:var(--muted); font-size:0.88rem; line-height:1.6;">
            Sans pseudo ni code de carte, il est impossible de vous identifier automatiquement.
            <br><br>
            La seule solution est de <strong>contacter un administrateur</strong> du site.
            Il pourra retrouver votre compte en base de données et vous communiquer votre code.
          </p>
        </div>

        <!-- Étapes -->
        <div style="background:var(--card); border-radius:10px; padding:18px; box-shadow:var(--shadow);">
          <p style="font-weight:700; color:var(--accent); margin-bottom:12px;">📋 Que faire ?</p>
          <ol style="color:var(--muted); font-size:0.88rem; line-height:2; padding-left:18px;">
            <li>Allez sur la page <a href="contact.php" style="color:var(--accent-2); font-weight:600;">Contact</a></li>
            <li>Indiquez votre <strong>prénom</strong> et votre <strong>nom</strong></li>
            <li>Précisez que vous avez perdu votre accès</li>
            <li>Un admin retrouvera votre compte et vous donnera votre code</li>
          </ol>
        </div>

        <!-- Ou recommencer -->
        <div style="text-align:center; padding-top:4px;">
          <p style="color:var(--muted); font-size:0.85rem; margin-bottom:12px;">
            — ou —
          </p>
          <a href="register.php" style="background:var(--accent); color:white; padding:10px 28px; border-radius:8px; font-weight:600; font-size:0.9rem; text-decoration:none; display:inline-block;">
            🆕 Créer un nouveau compte
          </a>
        </div>

      </div>
    <?php endif; ?>

    <!-- Liens vers les autres modes -->
    <div style="margin-top:22px; padding-top:18px; border-top:1px solid rgba(132,106,83,0.15); display:flex; flex-direction:column; gap:6px; text-align:center; font-size:0.83rem;">
      <?php if ($mode !== 'pseudo'):   ?><a href="recuperation.php?mode=pseudo"   style="color:var(--muted);">🕵️ J'ai oublié mon pseudo</a><?php endif; ?>
      <?php if ($mode !== 'carte'):    ?><a href="recuperation.php?mode=carte"    style="color:var(--muted);">🗂️ J'ai perdu mon code de carte</a><?php endif; ?>
      <?php if ($mode !== 'les-deux'): ?><a href="recuperation.php?mode=les-deux" style="color:var(--accent-2); font-weight:600;">❓ J'ai oublié les deux</a><?php endif; ?>
    </div>

  </section>
</main>

<script>
function copierCode() {
  const code = document.getElementById('code-recupere').innerText.trim();

  if (navigator.clipboard && window.isSecureContext) {
    navigator.clipboard.writeText(code).then(() => {
      alert('✅ Code copié dans le presse-papiers !');
    }).catch(() => {
      fallbackCopy(code);
    });
  } else {
    fallbackCopy(code);
  }
}

function fallbackCopy(text) {
  const el = document.createElement('textarea');
  el.value = text;
  el.style.position = 'fixed';
  el.style.opacity  = '0';
  document.body.appendChild(el);
  el.focus();
  el.select();
  try {
    document.execCommand('copy');
    alert('✅ Code copié !');
  } catch (e) {
    alert('❌ Impossible de copier automatiquement. Votre code : ' + text);
  }
  document.body.removeChild(el);
}
</script>

<?php require 'footer.php'; ?>