<?php
require 'session_init.php';
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "site_web";

$conn = new mysqli($host, $user, $pass, $dbname);

if (!isset($_SESSION["utilisateur_id"])) {
    header("Location: connexion.php");
    exit;
}

$id = $_SESSION["utilisateur_id"];
$erreurs = [];
$succes = "";

// Récupération des infos actuelles
$stmt = $conn->prepare("SELECT username, bio, genre, instagram, twitter, photo_profil FROM utilisateurs WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($username, $bio, $genre, $instagram, $twitter, $photo_profil);
$stmt->fetch();
$stmt->close();

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"]);
    $bio = trim($_POST["bio"]);
    $genre = trim($_POST["genre"]);
    $instagram = trim($_POST["instagram"]);
    $twitter = trim($_POST["twitter"]);

    // Mise à jour
    $stmt = $conn->prepare("UPDATE utilisateurs SET username=?, bio=?, genre=?, instagram=?, twitter=? WHERE id=?");
    $stmt->bind_param("sssssi", $username, $bio, $genre, $instagram, $twitter, $id);

    if ($stmt->execute()) {
        $succes = "Profil mis à jour avec succès.";
    } else {
        $erreurs[] = "Erreur lors de la mise à jour.";
    }

    $stmt->close();
}

$conn->close();
?>

<h2>Modifier mon profil</h2>

<?php
if (!empty($erreurs)) {
    foreach ($erreurs as $e) echo "<p style='color:red;'>" . htmlspecialchars($e) . "</p>";
}
if (!empty($succes)) {
    echo "<p style='color:green;'>" . htmlspecialchars($succes) . "</p>";
}
?>

<form method="POST">
    <label>Nom d'utilisateur :</label><br>
    <input type="text" name="username" value="<?= htmlspecialchars($username) ?>"><br><br>

    <label>Biographie :</label><br>
    <textarea name="bio"><?= htmlspecialchars($bio) ?></textarea><br><br>

    <label>Genre :</label><br>
    <input type="text" name="genre" value="<?= htmlspecialchars($genre) ?>"><br><br>

    <label>Instagram :</label><br>
    <input type="text" name="instagram" value="<?= htmlspecialchars($instagram) ?>"><br><br>

    <label>Twitter :</label><br>
    <input type="text" name="twitter" value="<?= htmlspecialchars($twitter) ?>"><br><br>

    <button type="submit">Mettre à jour / Enregistrer</button>
</form>