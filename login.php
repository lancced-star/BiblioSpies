<?php
session_start();

// Connexion à la base de données
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "bibliospies";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Erreur de connexion à la base de données : " . $conn->connect_error);
}

$erreurs = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // On force le type string pour éviter l'avertissement Intelephense
    $email = (string) trim($_POST["email"] ?? "");
    $motdepasse = (string) trim($_POST["motdepasse"] ?? "");

    if (empty($email) || empty($motdepasse)) {
        $erreurs[] = "Tous les champs sont obligatoires.";
    } else {

        // Requête préparée pour éviter les injections SQL
        $stmt = $conn->prepare("SELECT id, motdepasse FROM utilisateurs WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
if ($stmt->num_rows === 1) {

            // Initialisation pour éviter les warnings statiques et l'usage de get_result() incorrect
            $id = null;
            $mdp_bdd = null;
            $stmt->bind_result($id, $mdp_bdd);
            $stmt->fetch();

            // Vérification du mot de passe hashé
            if ($mdp_bdd !== null && password_verify($motdepasse, $mdp_bdd)) {

                // Connexion réussie
                $_SESSION["utilisateur_id"] = $id;
                $_SESSION["utilisateur_email"] = $email;

                header("Location: dashboard.php");
                exit;

            } else {
                $erreurs[] = "Mot de passe incorrect.";
            }

        } else {
            $erreurs[] = "Aucun compte trouvé avec cet e-mail.";
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion</title>
</head>
<body>

<h2>Connexion</h2>

<?php
if (!empty($erreurs)) {
    echo "<ul>";
    foreach ($erreurs as $e) {
        echo "<li style='color:red;'>" . $e . "</li>";
    }
    echo "</ul>";
}
?>

<form method="POST" action="">
    <label>Email :</label><br>
    <input type="email" name="email" required><br><br>

    <label>Mot de passe :</label><br>
    <input type="password" name="motdepasse" required><br><br>

    <button type="submit">Se connecter</button>
</form>
</body>
</html>