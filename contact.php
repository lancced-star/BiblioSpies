<?php require 'header.php'; ?>

<main style="padding: 20px; max-width: 800px; margin: 0 auto;">
    <h1>Contactez-nous</h1>
    
    <form action="reponse.php" method="POST">
        
        <fieldset style="border: 2px solid #333; padding: 20px; border-radius: 10px;">
            <legend style="padding: 0 10px; font-weight: bold; font-size: 1.2em;">Formulaire de contact</legend>
            
            <div style="margin-bottom: 15px;">
                <label for="nom">Votre Nom ou Pseudo :</label><br>
                <input type="text" id="nom" name="user_name" required placeholder="Votre nom" style="width: 100%; padding: 8px;">
            </div>

            <div style="margin-bottom: 15px;">
                <label for="email">Votre Email :</label><br>
                <input type="email" id="email" name="user_email" required placeholder="exemple@email.com" style="width: 100%; padding: 8px;">
            </div>

            <div style="margin-bottom: 15px;">
                <label for="message">Votre message :</label><br>
                <textarea id="message" name="user_message" rows="5" required style="width: 100%; padding: 8px;"></textarea>
            </div>

            <button type="submit" style="background-color: #333; color: white; padding: 10px 20px; border: none; cursor: pointer;">Envoyer</button>
        
        </fieldset>
    </form>
</main>

<?php require 'footer.php'; ?>