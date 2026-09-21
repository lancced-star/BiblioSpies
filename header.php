<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Timeout de session : 10 minutes (600 secondes)
$session_timeout = 60; // 10 minutes en secondes

// Vérifier si l'utilisateur est connecté
if (isset($_SESSION['user_id'])) {
  // Vérifier si la session a expiré
  if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $session_timeout) {
    // Session expirée - détruire la session et rediriger
    session_destroy();
    header('Location: login.php?expired=1');
    exit;
  }
  
  // Mettre à jour l'heure de dernière activité
  $_SESSION['last_activity'] = time();
}

require 'connexion-bdd.php';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Biblio'Spies - La Bibliothèque Secrète">
  <link href="style.css" rel="stylesheet">
  <title>Biblio'Spies - Bibliothèque Secrète</title>
  <link href="logoimage.png" rel="icon">
  <script src="https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
</head>

<body>
  
  <!-- Boutons langue (fixe haut gauche) -->
  <div id="lang-switcher">
    <button onclick="traduire('fr', event)">🇫🇷 FR</button>
    <button onclick="traduire('en', event)">🇬🇧 EN</button>
  </div>

  <!-- Bouton RGPD (fixe haut gauche, sous les langues) -->
  <a href="droits.php" class="btn-droits">Mes droits & RGPD</a>

    <!-- Bouton thème (fixe haut droite) -->
  <button id="theme-toggle" class="theme-toggle-btn" aria-label="Basculer le thème" title="Basculer entre thème clair et sombre">
    <svg id="theme-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <circle cx="12" cy="12" r="5"></circle>
      <path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"></path>
    </svg>
  </button>

  <!-- Élément Google caché -->
  <div id="google_translate_element" style="display:none;"></div>

  <header class="navbar">
    <a href="index.php">
      <img src="logoimage.png" class="nav-image" alt="Logo">
    </a>

    <div class="nav-bottom">
      <nav class="nav-links">
        <a href="index.php">
          <h2>Accueil</h2>
        </a>
        <a href="agents.php">
          <h2>Agents</h2>
        </a>
        <a href="contact.php">
          <h2>Contact</h2>
        </a>
      </nav>





      <?php
      // Préparer l'avatar à afficher
      $avatarPath = 'avatar-placeholder.svg';
      if (isset($_SESSION['user_id'])) {
        try {
          $q = $bdd->prepare('SELECT avatar FROM users WHERE id = :id');
          $q->execute([':id' => (int)$_SESSION['user_id']]);
          $r = $q->fetch(PDO::FETCH_ASSOC);
          if (!empty($r['avatar'])) $avatarPath = $r['avatar'];
        } catch (Exception $e) {
          // ignore
        }
      }
      ?>

      <div class="profile-container">
        <button id="profileBtn" class="profile-btn" aria-haspopup="true" aria-expanded="false" style="position:relative;">
          <img src="<?php echo htmlspecialchars($avatarPath); ?>" alt="Profil">
          <?php if (!empty($_SESSION['is_admin'])): ?>
            <span style="
                position:absolute; bottom:-4px; right:-4px;
                background:linear-gradient(135deg,#1a1a2e,#0f3460);
                color:#e8c97a; font-size:0.55rem; font-weight:800;
                padding:2px 5px; border-radius:10px;
                border:1.5px solid #e8c97a;
                letter-spacing:0.5px; white-space:nowrap;
                pointer-events:none;
              ">🛡️ ADMIN</span>
          <?php endif; ?>
        </button>

        <div id="profileMenu" class="profile-menu" aria-hidden="true">
          <?php if (isset($_SESSION['user_id'])): ?>
            <?php if (!empty($_SESSION['is_admin'])): ?>
              <a href="admin_dashboard.php" style="color:var(--accent-2); font-weight:600;">⚙️ Dashboard admin</a>
            <?php endif; ?>
            <a href="profile.php">👤 Mon profil</a>
            <a href="deconnexion-bdd.php">🔐 Déconnexion</a>
          <?php else: ?>
            <a href="login.php">Se connecter</a>
            <a href="register.php">S'inscrire</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
    </div>
  </header>
  <script>
    function googleTranslateElementInit() {
      new google.translate.TranslateElement({
          pageLanguage: 'fr',
          includedLanguages: 'en,fr'
        },
        'google_translate_element'
      );
    }

    function traduire(langue) {
      const select = document.querySelector('.goog-te-combo');
      if (select) {
        select.value = langue;
        select.dispatchEvent(new Event('change'));
      }
      document.querySelectorAll('#lang-switcher button').forEach(btn => {
        btn.classList.remove('actif');
      });
      event.target.classList.add('actif');
    }
  </script>
</body>