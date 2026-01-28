<?php require 'header.php'; ?>

<main style="padding: 50px; text-align: center;">
    
    <?php
    // On vérifie si le formulaire a été soumis (si le champ 'user_name' existe dans les données reçues)
    if (isset($_POST['user_name'])) {
        
        // On nettoie les données reçues pour la sécurité
        $nom = htmlspecialchars($_POST['user_name']);
        
        // Affichage du message de remerciement (Consigne)
        echo "<h1>Merci " . $nom . " !</h1>";
        echo "<p>Votre message a bien été envoyé à l'équipe Biblio'Steak.</p>";
        
    } else {
        // Cas où l'utilisateur arrive ici sans envoyer le formulaire
        echo "<h1>Erreur</h1>";
        echo "<p>Vous devez remplir le formulaire de contact pour voir cette page.</p>";
    }
    ?>

    <br><br>
    <a href="index.php" style="background-color: #333; color: white; padding: 10px 20px; text-decoration: none;">Retour à l'accueil</a>

</main>

<?php require 'footer.php'; ?>