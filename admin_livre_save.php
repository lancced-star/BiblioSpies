<?php
require 'admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin_livres.php');
    exit;
}

// --- Récupération et nettoyage des données ---
$isbn      = trim($_POST['isbn']      ?? '');
$titre     = trim($_POST['titre']     ?? '');
$editeur   = intval($_POST['editeur'] ?? 0);
$annee     = !empty($_POST['annee'])   ? intval($_POST['annee'])   : null;
$genre     = !empty($_POST['genre'])   ? intval($_POST['genre'])   : null;
$langue    = !empty($_POST['langue'])  ? intval($_POST['langue'])  : null;
$nbpages   = !empty($_POST['nbpages']) ? intval($_POST['nbpages']) : null;
$resume    = trim($_POST['resume']    ?? '');
$isEdit    = !empty($_POST['isbn_original']);
$isbnOrig  = trim($_POST['isbn_original'] ?? $isbn);

// --- Validations ---
if (empty($isbn) || empty($titre) || $editeur <= 0) {
    $_SESSION['flash'] = '⚠️ ISBN, titre et éditeur sont obligatoires.';
    $redirect = $isEdit ? "admin_livre_form.php?isbn=$isbnOrig" : "admin_livre_form.php";
    header("Location: $redirect");
    exit;
}

// --- Gestion de l'image ---
$imagePath = null; // null = pas de changement

if (!empty($_FILES['image']['tmp_name'])) {
    $file     = $_FILES['image'];
    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed  = ['jpg', 'jpeg', 'png', 'webp'];
    $maxSize  = 2 * 1024 * 1024; // 2 Mo

    if (!in_array($ext, $allowed)) {
        $_SESSION['flash'] = '⚠️ Format d\'image non autorisé (JPG, PNG, WEBP uniquement).';
        header("Location: admin_livre_form.php" . ($isEdit ? "?isbn=$isbnOrig" : ''));
        exit;
    }
    if ($file['size'] > $maxSize) {
        $_SESSION['flash'] = '⚠️ L\'image dépasse 2 Mo.';
        header("Location: admin_livre_form.php" . ($isEdit ? "?isbn=$isbnOrig" : ''));
        exit;
    }

    // Nom de fichier = ISBN.extension (ex: 9782867465444.jpg)
    $filename  = $isbn . '.' . $ext;
    $uploadDir = __DIR__ . '/'; // même dossier que le projet
    
    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        $_SESSION['flash'] = '⚠️ Erreur lors de l\'upload de l\'image.';
        header("Location: admin_livre_form.php" . ($isEdit ? "?isbn=$isbnOrig" : ''));
        exit;
    }
    $imagePath = $filename;
}

try {
    if ($isEdit) {
        // ---- MODE MODIFICATION ----
        if ($imagePath !== null) {
            $sql = 'UPDATE Livre SET titre=:titre, editeur=:editeur, annee=:annee, genre=:genre,
                    langue=:langue, nbpages=:nbpages, resume=:resume, image=:image
                    WHERE isbn=:isbn';
            $params = [
                ':titre'=>$titre, ':editeur'=>$editeur, ':annee'=>$annee,
                ':genre'=>$genre, ':langue'=>$langue, ':nbpages'=>$nbpages,
                ':resume'=>$resume, ':image'=>$imagePath, ':isbn'=>$isbnOrig
            ];
        } else {
            // Pas de nouvelle image → on ne touche pas au champ image
            $sql = 'UPDATE Livre SET titre=:titre, editeur=:editeur, annee=:annee, genre=:genre,
                    langue=:langue, nbpages=:nbpages, resume=:resume
                    WHERE isbn=:isbn';
            $params = [
                ':titre'=>$titre, ':editeur'=>$editeur, ':annee'=>$annee,
                ':genre'=>$genre, ':langue'=>$langue, ':nbpages'=>$nbpages,
                ':resume'=>$resume, ':isbn'=>$isbnOrig
            ];
        }
        $stmt = $bdd->prepare($sql);
        $stmt->execute($params);
        $_SESSION['flash'] = '✅ Livre modifié avec succès.';

    } else {
        // ---- MODE AJOUT ----
        // Vérifier que l'ISBN n'existe pas déjà
        $check = $bdd->prepare('SELECT isbn FROM Livre WHERE isbn = :isbn');
        $check->execute([':isbn' => $isbn]);
        if ($check->fetch()) {
            $_SESSION['flash'] = '⚠️ Cet ISBN existe déjà dans la base.';
            header('Location: admin_livre_form.php');
            exit;
        }

        $sql = 'INSERT INTO Livre (isbn, titre, editeur, annee, genre, langue, nbpages, resume, image)
                VALUES (:isbn, :titre, :editeur, :annee, :genre, :langue, :nbpages, :resume, :image)';
        $stmt = $bdd->prepare($sql);
        $stmt->execute([
            ':isbn'=>$isbn, ':titre'=>$titre, ':editeur'=>$editeur, ':annee'=>$annee,
            ':genre'=>$genre, ':langue'=>$langue, ':nbpages'=>$nbpages,
            ':resume'=>$resume, ':image'=>$imagePath
        ]);
        $_SESSION['flash'] = '✅ Livre ajouté avec succès.';
    }
} catch (Exception $e) {
    $_SESSION['flash'] = '❌ Erreur BDD : ' . $e->getMessage();
}

header('Location: admin_livres.php');
exit;
