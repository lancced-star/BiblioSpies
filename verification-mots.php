<?php
function verifierMotsInterdits($contenu, $localisation, $bdd) {
    $q = $bdd->query('SELECT mot FROM mots_interdits');
    $mots = $q->fetchAll(PDO::FETCH_COLUMN);

    $motsDetectes = [];
    $contenuLower = mb_strtolower($contenu);

    foreach ($mots as $mot) {
        if (str_contains($contenuLower, mb_strtolower($mot))) {
            $motsDetectes[] = $mot;
        }
    }

    if (!empty($motsDetectes)) {
        $userId = $_SESSION['user_id'] ?? null;
        $motsStr = implode(', ', $motsDetectes);

        $stmt = $bdd->prepare('
            INSERT INTO signalements (user_id, contenu_original, mots_detectes, localisation)
            VALUES (:user_id, :contenu, :mots, :localisation)
        ');
        $stmt->execute([
            ':user_id'      => $userId,
            ':contenu'      => $contenu,
            ':mots'         => $motsStr,
            ':localisation' => $localisation
        ]);

        return [
            'detecte' => true,
            'mots'    => $motsDetectes
        ];
    }

    return ['detecte' => false];
}

// ====================================
// VÉRIFICATION AUTOMATIQUE DU $_POST
// ====================================
function verifierToutLePost($bdd) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return null;

    // Récupère la page actuelle
    $localisation = $_SERVER['PHP_SELF'] ?? 'inconnu';

    // Champs à ignorer (mots de passe, tokens, etc.)
    $champsIgnores = ['password', 'mot_de_passe', 'token', 'csrf'];

    foreach ($_POST as $champ => $valeur) {
        // Ignore les champs sensibles
        if (in_array(strtolower($champ), $champsIgnores)) continue;

        // Ignore les valeurs non textuelles
        if (!is_string($valeur) || empty(trim($valeur))) continue;

        $resultat = verifierMotsInterdits($valeur, $localisation . ' [' . $champ . ']', $bdd);

        if ($resultat['detecte']) {
            return $resultat; // Retourne dès le premier mot interdit trouvé
        }
    }

    return ['detecte' => false];
}
?>