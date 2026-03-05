<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require 'connexion-bdd.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: register.php'); exit; }

// ── 1. Récupération ──────────────────────────────────────────
$prenom   = ucfirst(strtolower(trim($_POST['prenom']   ?? '')));
$nom      = ucfirst(strtolower(trim($_POST['nom']      ?? '')));
$username = trim($_POST['username'] ?? '');

// ── 2. Validations ───────────────────────────────────────────
$erreurs = [];
if (empty($prenom))   $erreurs[] = 'Le prénom est obligatoire.';
if (empty($nom))      $erreurs[] = 'Le nom est obligatoire.';
if (empty($username)) $erreurs[] = 'Le pseudo est obligatoire.';
if (!preg_match('/^[a-zA-Z0-9_\-]{3,50}$/', $username))
    $erreurs[] = 'Le pseudo ne peut contenir que des lettres, chiffres, _ et - (3 à 50 caractères).';

if (!empty($erreurs)) {
    $_SESSION['flash'] = implode('<br>', $erreurs);
    $_SESSION['old']   = compact('prenom', 'nom', 'username');
    header('Location: register.php');
    exit;
}

// ── 3. Vérification unicité du pseudo ────────────────────────
$stmtCheck = $bdd->prepare('SELECT id FROM users WHERE username = :username');
$stmtCheck->execute([':username' => $username]);
if ($stmtCheck->fetch()) {
    $_SESSION['flash'] = '⚠️ Ce pseudo est déjà pris, choisissez-en un autre.';
    $_SESSION['old']   = compact('prenom', 'nom', 'username');
    header('Location: register.php');
    exit;
}

// ── 4. Génération du code de carte unique ────────────────────
// Format : BSP-[3 lettres pseudo]-[8 hex aléatoires]
// Exemple : BSP-AGE-4f9a2b1c
function genererCarteCode(string $username): string {
    $lettres = preg_replace('/[^a-zA-Z]/', '', $username);
    $prefix  = strtoupper(str_pad(substr($lettres, 0, 3), 3, 'X'));
    return 'BSP-' . $prefix . '-' . bin2hex(random_bytes(4));
}

// Boucle pour garantir l'unicité absolue
do {
    $carteCode = genererCarteCode($username);
    $stmtCode  = $bdd->prepare('SELECT id FROM users WHERE carte_code = :code');
    $stmtCode->execute([':code' => $carteCode]);
} while ($stmtCode->fetch());

// ── 5. Insertion en base ─────────────────────────────────────
try {
    $stmt = $bdd->prepare('
        INSERT INTO users (prenom, nom, username, carte_code)
        VALUES (:prenom, :nom, :username, :carte_code)
    ');
    $stmt->execute([
        ':prenom'     => $prenom,
        ':nom'        => $nom,
        ':username'   => $username,
        ':carte_code' => $carteCode,
    ]);
} catch (Exception $e) {
    $_SESSION['flash'] = '❌ Erreur lors de la création du compte.';
    $_SESSION['old']   = compact('prenom', 'nom', 'username');
    header('Location: register.php');
    exit;
}

// ── 6. Stockage pour la page carte (affiché une seule fois) ──
$_SESSION['nouvelle_carte'] = compact('prenom', 'nom', 'username', 'carteCode');

header('Location: carte.php');
exit;
