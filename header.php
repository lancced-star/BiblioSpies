<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
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
</head>

<body>

  <header class="navbar">
    <a href="index.php"> <img src="logoimage.png" class="nav-image" alt="Logo">
    </a>

    <div class="nav-bottom">
      <nav class="nav-links">
        <a href="index.php"><h2>Accueil</h2></a>
        <a href="index.php#livre"><h2>Livres</h2></a>
        <a href="contact.php"><h2>Contact</h2></a>
        <a href="#"><h2>À propos</h2></a>
      </nav>

      <div class="nav-buttons">
        <button id="theme-toggle" class="theme-toggle-btn" aria-label="Basculer le thème" title="Basculer entre thème clair et sombre">
          <svg id="theme-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="5"></circle>
            <path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"></path>
          </svg>
        </button>
        <button class="nav-toggle" id="navToggle">☰</button>

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
          <button id="profileBtn" class="profile-btn" aria-haspopup="true" aria-expanded="false">
            <img src="<?php echo htmlspecialchars($avatarPath); ?>" alt="Profil">
          </button>
          <div id="profileMenu" class="profile-menu" aria-hidden="true">
            <?php if (isset($_SESSION['user_id'])): ?>
              <a href="profile.php">Mon profil</a>
              <a href="deconnexion-bdd.php">Déconnexion</a>
            <?php else: ?>
              <a href="login.php">Se connecter</a>
              <a href="register.php">S'inscrire</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </header>