<?php require 'connexion-bdd.php'; ?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Biblio'Spies - Découvrez notre bibliothèque">
  <link href="style.css" rel="stylesheet">
  <title>Biblio'Spies - Bibliothèque</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
</head>

<body>

  <header class="navbar">
    <a href="index.php"> <img src="logo-bibliosteak.png" class="nav-image" alt="Logo">
    </a>
  
    <nav class="nav-links">
      <a href="index.php"><h2>Accueil</h2></a>
      <a href="index.php#livre"><h2>Livres</h2></a>
      <a href="#"><h2>Recettes</h2></a>
      <a href="contact.php"><h2>Contact</h2></a> 
      <a href="#"><h2>À propos</h2></a>
    </nav>

    <button class="nav-toggle" id="navToggle">☰</button>
  </header>