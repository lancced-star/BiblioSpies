<?php
require 'session_init.php';

if (!isset($_SESSION["utilisateur_id"])) {
    header("Location: connexion.php");
    exit;
}

echo "<h1>Dashboard</h1>";
echo "<p>Bienvenue, " . htmlspecialchars($_SESSION["utilisateur_email"]) . "</p>";

echo "<ul>
        <li><a href='modifier_profil.php'>Modifier mon profil</a></li>
        <li><a href='parametres_compte.php'>Paramètres du compte</a></li>
        <li><a href='abonnements.php'>Mes abonnements</a></li>
        <li><a href='abonnes.php'>Mes abonnés</a></li>
        <li><a href='profil.php?user=" . $_SESSION["utilisateur_id"] . "'>Voir mon profil public</a></li>
        <li><a href='deconnexion.php'>Se déconnecter</a></li>
      </ul>"; 