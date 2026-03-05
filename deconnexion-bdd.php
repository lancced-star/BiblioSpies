<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Vider toutes les variables de session
$_SESSION = [];

// 2. Supprimer le cookie de session si il existe
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 3. Détruire la session côté serveur
session_destroy();

// 4. Rediriger vers la page de connexion
header('Location: login.php');
exit;