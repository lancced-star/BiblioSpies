<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
require 'connexion-bdd.php';
require 'verification-mots.php';

// Vérifie automatiquement tout $_POST sur toutes les pages
$signalement = verifierToutLePost($bdd);

if (isset($signalement['detecte']) && $signalement['detecte']) {
    // Bloque l'envoi et affiche un message d'erreur
    $erreurMotInterdit = "⚠️ Votre message contient des termes non autorisés.";
}
?>