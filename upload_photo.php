<?php
require 'session_init.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION["utilisateur_id"])) {
    header("Location: connexion.php");
    exit;
}

// Connexion à la base de données
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "bibliospies";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

$id = $_SESSION["utilisateur_id"];
$erreurs = [];
$succes = "";

// Si un fichier est envoyé
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["photo"])) {

    $photo = $_FILES["photo"];

    // Vérifier s'il n'y a pas d'erreur
    if ($photo["error"] !== 0) {
        $erreurs[] = "Erreur lors de l'upload.";
    } else {

        // Vérifier la taille (max 5 Mo)
        if ($photo["size"] > 5 * 1024 * 1024) {
            $erreurs[] = "Le fichier est trop volumineux (max 5 Mo).";
        }

        // Vérifier l'extension
        $extensions_valides = ["jpg", "jpeg", "png", "webp"];
        $extension = strtolower(pathinfo($photo["name"], PATHINFO_EXTENSION));

        if (!in_array($extension, $extensions_valides)) {
            $erreurs[] = "Format non autorisé. Formats acceptés : JPG, JPEG, PNG, WEBP.";
        }

        // Vérifier le type MIME réel
        $mime_types_valides = ["image/jpeg", "image/png", "image/webp"];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $photo["tmp_name"]);
        finfo_close($finfo);

        if (!in_array($mime_type, $mime_types_valides)) {
            $erreurs[] = "Type de fichier non autorisé.";
        }

        // Si tout est bon
        if (empty($erreurs)) {

            // Nouveau nom unique
            $nouveau_nom = "profil_" . $id . "_" . time() . "." . $extension;

            // Dossier de stockage
            $dossier = "uploads/";

            // Créer le dossier si inexistant
            if (!is_dir($dossier)) {
                mkdir($dossier, 0777, true);
            }

            // Chemin final
            $chemin_final = $dossier . $nouveau_nom;

            // Déplacer le fichier
            if (move_uploaded_file($photo["tmp_name"], $chemin_final)) {

                // Récupérer l'ancienne photo pour la supprimer
                $stmt = $conn->prepare("SELECT photo_profil FROM utilisateurs WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->bind_result($ancienne_photo);
                $stmt->fetch();
                $stmt->close();

                // Supprimer l'ancienne photo si ce n'est pas la photo par défaut
                if ($ancienne_photo !== "default.png" && file_exists("uploads/" . $ancienne_photo)) {
                    unlink("uploads/" . $ancienne_photo);
                }

                // Mettre à jour la base
                $stmt = $conn->prepare("UPDATE utilisateurs SET photo_profil = ? WHERE id = ?");
                $stmt->bind_param("si", $nouveau_nom, $id);

                if ($stmt->execute()) {
                    $succes = "Photo de profil mise à jour avec succès.";
                } else {
                    $erreurs[] = "Erreur lors de la mise à jour en base.";
                }

                $stmt->close();

            } else {
                $erreurs[] = "Impossible de déplacer le fichier.";
            }
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Photo de profil</title>
</head>
<body>

<h2>Modifier</h2>

<?php
if (!empty($erreurs)) {
    foreach ($erreurs as $e) {
        echo "<p style='color:red;'>" . htmlspecialchars($e) . "</p>";
    }
}

if (!empty($succes)) {
    echo "<p style='color:green;'>" . htmlspecialchars($succes) . "</p>";
}
?>

<form method="POST" enctype="multipart/form-data">
    <label>Choisir une photo :</label><br>
    <input type="file" name="photo" required><br><br>

    <button type="submit">Mettre à jour</button>
</form>

<br>
<a href="tableau_de_bord.php">Retour au Dashboard</a>

</body>
</html>