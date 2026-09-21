<?php
// Initialisation de session avec timeout
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Timeout de session : 10 minutes (600 secondes)
$session_timeout = 10 * 60; // 10 minutes en secondes

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
?>