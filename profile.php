<?php
require_once 'connexion-bdd.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId  = (int) $_SESSION['user_id'];
$errors  = [];
$success = [];

// Ajouter colonne bio si elle n'existe pas
try {
    $bdd->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS bio TEXT NULL");
} catch (Exception $e) {}

// Recup user
$stmt = $bdd->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) { session_destroy(); header('Location: login.php'); exit; }

// Traitement update infos
if (isset($_POST['action']) && $_POST['action'] === 'update_infos') {
    $prenom   = trim($_POST['prenom']   ?? '');
    $nom      = trim($_POST['nom']      ?? '');
    $username = trim($_POST['username'] ?? '');
    $bio      = trim($_POST['bio']      ?? '');

    if ($prenom === '' || mb_strlen($prenom) > 64) $errors[] = 'Prénom invalide.';
    if ($nom    === '' || mb_strlen($nom)    > 64) $errors[] = 'Nom invalide.';
    if (!preg_match('/^[a-zA-Z0-9_]{3,32}$/', $username)) $errors[] = 'Pseudo invalide (3-32 car., lettres/chiffres/underscore).';
    if (mb_strlen($bio) > 300) $errors[] = 'Bio trop longue (300 max).';

    if (empty($errors)) {
        $chk = $bdd->prepare('SELECT 1 FROM users WHERE username = ? AND id != ?');
        $chk->execute([$username, $userId]);
        if ($chk->fetch()) {
            $errors[] = 'Ce pseudo est déjà pris.';
        } else {
            try {
                $bdd->prepare('UPDATE users SET prenom=?, nom=?, username=?, bio=? WHERE id=?')
                    ->execute([$prenom, $nom, $username, $bio, $userId]);
            } catch (Exception $e) {
                $bdd->prepare('UPDATE users SET prenom=?, nom=?, username=? WHERE id=?')
                    ->execute([$prenom, $nom, $username, $userId]);
            }
            $_SESSION['username'] = $username;
            $_SESSION['prenom']   = $prenom;
            header('Location: profile.php?updated=1');
            exit;
        }
    }
}

if (isset($_GET['updated'])) {
    $success[] = 'Profil mis à jour avec succès !';
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Upload avatar
if (isset($_POST['action']) && $_POST['action'] === 'update_avatar') {
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $allowed  = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize  = 2 * 1024 * 1024;
        $mimeType = mime_content_type($_FILES['avatar']['tmp_name']);
        if (!in_array($mimeType, $allowed)) {
            $errors[] = 'Format non supporté.';
        } elseif ($_FILES['avatar']['size'] > $maxSize) {
            $errors[] = 'Image trop lourde (2 Mo max).';
        } else {
            $ext  = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $dir  = 'uploads/avatars/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $dest = $dir . 'avatar_' . $userId . '_' . time() . '.' . $ext;
            if (!empty($user['avatar']) && file_exists($user['avatar'])) unlink($user['avatar']);
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $dest)) {
                $bdd->prepare('UPDATE users SET avatar=? WHERE id=?')->execute([$dest, $userId]);
                header('Location: profile.php?updated=1');
                exit;
            } else {
                $errors[] = "Erreur lors de l'upload.";
            }
        }
    } else {
        $errors[] = 'Aucun fichier reçu.';
    }
}

// Avatar
$avatarSrc = (!empty($user['avatar']) && file_exists($user['avatar']))
    ? htmlspecialchars($user['avatar'])
    : 'https://ui-avatars.com/api/?name=' . urlencode(($user['prenom'] ?? '') . '+' . ($user['nom'] ?? '')) . '&background=c9a96e&color=fff&size=200&bold=true';

$carteCode  = $user['carte_code'] ?? '';
$cardMasked = strlen($carteCode) >= 8
    ? substr($carteCode, 0, 4) . '-****-****-' . substr($carteCode, -4)
    : $carteCode;

// Stats
$nbAbonnements = 0; $nbAbonnes = 0;
try {
    $bdd->exec("CREATE TABLE IF NOT EXISTS suivis (
        id INT AUTO_INCREMENT PRIMARY KEY,
        follower_id INT NOT NULL,
        followed_id INT NOT NULL,
        created_at DATETIME DEFAULT NOW(),
        UNIQUE KEY unique_suivi (follower_id, followed_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    // Abonnements = personnes que JE suis
    $r = $bdd->prepare('SELECT COUNT(*) FROM suivis WHERE follower_id = ?');
    $r->execute([$userId]); $nbAbonnements = (int)$r->fetchColumn();
    // Abonnés = personnes qui ME suivent
    $r = $bdd->prepare('SELECT COUNT(*) FROM suivis WHERE followed_id = ?');
    $r->execute([$userId]); $nbAbonnes = (int)$r->fetchColumn();

    // Liste des abonnements (gens que je suis)
    $listAbonnements = $bdd->prepare(
        'SELECT u.id, u.username, u.prenom, u.nom FROM suivis s JOIN users u ON s.followed_id = u.id WHERE s.follower_id = ? ORDER BY u.username');
    $listAbonnements->execute([$userId]);
    $listAbonnements = $listAbonnements->fetchAll(PDO::FETCH_ASSOC);

    // Liste des abonnés (gens qui me suivent)
    $listAbonnes = $bdd->prepare(
        'SELECT u.id, u.username, u.prenom, u.nom FROM suivis s JOIN users u ON s.follower_id = u.id WHERE s.followed_id = ? ORDER BY u.username');
    $listAbonnes->execute([$userId]);
    $listAbonnes = $listAbonnes->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $listAbonnements = []; $listAbonnes = []; }

// Favoris
$favoris = [];
try {
    $bdd->exec("CREATE TABLE IF NOT EXISTS favoris (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        isbn VARCHAR(20) NOT NULL,
        created_at DATETIME DEFAULT NOW(),
        UNIQUE KEY unique_favori (user_id, isbn)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $favStmt = $bdd->prepare("
        SELECT f.isbn, f.created_at, l.titre, l.image, l.annee,
               GROUP_CONCAT(CONCAT_WS(' ', p.prenom, p.nom) SEPARATOR ' + ') AS auteur
        FROM favoris f
        LEFT JOIN Livre l ON f.isbn = l.isbn
        LEFT JOIN Auteur a ON l.isbn = a.idLivre
        LEFT JOIN Personne p ON a.idPersonne = p.id
        WHERE f.user_id = ?
        GROUP BY f.isbn, f.created_at, l.titre, l.image, l.annee
        ORDER BY f.created_at DESC
    ");
    $favStmt->execute([$userId]);
    $favoris = $favStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $favoris = []; }

// Avis écrits par l'utilisateur
$mesAvis = [];
try {
    $bdd->exec("CREATE TABLE IF NOT EXISTS `avis` (
        `id` INT AUTO_INCREMENT PRIMARY KEY, `isbn` VARCHAR(20) NOT NULL,
        `user_id` INT DEFAULT NULL, `name` VARCHAR(255) DEFAULT NULL,
        `rating` TINYINT DEFAULT NULL, `contenu` TEXT NOT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $avisStmt = $bdd->prepare("
        SELECT a.id, a.isbn, a.rating, a.contenu, a.created_at,
               l.titre, l.image
        FROM `avis` a
        LEFT JOIN Livre l ON a.isbn = l.isbn
        WHERE a.user_id = ?
        ORDER BY a.created_at DESC
    ");
    $avisStmt->execute([$userId]);
    $mesAvis = $avisStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $mesAvis = []; }
require_once 'header.php';
?>

<?php if (!empty($erreurMotInterdit)): ?>
  <div class="erreur"><?php echo htmlspecialchars($erreurMotInterdit); ?></div>
<?php endif; ?>

<style>
.profile-main { max-width: var(--max-width, 1200px); margin: 0 auto; padding: 40px 20px 100px; }
.p-hero { display:flex;align-items:center;gap:28px;background:var(--card);border-radius:12px;padding:28px 32px;box-shadow:var(--shadow);margin-bottom:24px;flex-wrap:wrap;border-top:4px solid #c9a96e; }
.p-avatar-wrapper { position:relative;flex-shrink:0; }
.p-avatar-img { width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid #c9a96e;display:block; }
.p-avatar-edit { position:absolute;bottom:0;right:0;background:#c9a96e;border:2px solid var(--card);border-radius:50%;width:26px;height:26px;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:11px;transition:background .2s; }
.p-avatar-edit:hover { background:#a8834a; }
.avatar-form input[type="file"] { display:none; }
.p-info h1 { font-size:1.5rem;font-weight:800;color:var(--accent);margin-bottom:4px; }
.p-info .p-pseudo { font-size:0.85rem;color:#a8834a;font-weight:700;margin-bottom:6px; }
.p-info .p-bio { font-size:0.85rem;color:var(--muted);font-style:italic;line-height:1.6; }
.p-grid { display:grid;grid-template-columns:1fr 1fr;gap:18px; }
.p-full { grid-column:1/-1; }
.p-card { background:var(--card);border-radius:12px;box-shadow:var(--shadow);overflow:hidden; }
.p-card-head { padding:14px 20px;border-bottom:1px solid rgba(132,106,83,0.1);display:flex;align-items:center;gap:9px;background:rgba(132,106,83,0.04); }
.p-card-head h2 { font-size:0.95rem;font-weight:700;color:var(--accent); }
.p-card-body { padding:20px; }
.p-form-row { display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px; }
.p-form-row.full { grid-template-columns:1fr; }
.p-form-group { display:flex;flex-direction:column;gap:4px; }
.p-label { font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--muted); }
.p-input, .p-textarea { padding:9px 12px;border:1.5px solid rgba(132,106,83,0.22);border-radius:8px;font-size:0.88rem;color:var(--accent);background:var(--bg);outline:none;transition:border-color .2s;font-family:inherit; }
.p-input:focus, .p-textarea:focus { border-color:#c9a96e;background:var(--card); }
.p-textarea { resize:none;min-height:75px;line-height:1.6;width:100%; }
.p-char { font-size:0.7rem;color:var(--muted);text-align:right;margin-top:2px; }
.p-char.warn { color:#c0392b; }
.p-btn { display:inline-flex;align-items:center;gap:6px;padding:9px 20px;border-radius:8px;font-size:0.85rem;font-weight:700;cursor:pointer;border:none;transition:background .2s;font-family:inherit; }
.p-btn-gold { background:#c9a96e;color:white; }
.p-btn-gold:hover { background:#a8834a; }
.p-btn-danger { background:transparent;border:1.5px solid #c0392b;color:#c0392b;padding:9px 18px;border-radius:8px;font-size:0.85rem;font-weight:700;cursor:pointer;font-family:inherit;transition:background .2s,color .2s; }
.p-btn-danger:hover { background:#c0392b;color:white; }
.p-form-actions { margin-top:16px;display:flex;justify-content:flex-end; }
.p-card-agent { background:linear-gradient(135deg,#3a2a1e 0%,#5c3d2e 60%,#c9a96e 100%);border-radius:12px;padding:22px 26px;color:#fff;position:relative;overflow:hidden;max-width:340px; }
.p-card-agent::before { content:'';position:absolute;top:-35px;right:-35px;width:130px;height:130px;border-radius:50%;background:rgba(255,255,255,0.06); }
.p-card-agent-label { font-size:9px;letter-spacing:.15em;text-transform:uppercase;opacity:.7;margin-bottom:16px; }
.p-card-code { font-size:17px;font-weight:700;letter-spacing:.18em;margin-bottom:14px;font-family:monospace; }
.p-card-name { font-size:11px;opacity:.85;text-transform:uppercase;letter-spacing:.1em; }
.p-card-btns { margin-top:16px;display:flex;gap:7px; }
.p-btn-card { font-size:11px;padding:6px 13px;border-radius:6px;cursor:pointer;border:1.5px solid rgba(255,255,255,.4);background:transparent;color:#fff;font-weight:700;transition:background .2s;font-family:inherit; }
.p-btn-card:hover { background:rgba(255,255,255,.15); }
.p-stats { display:grid;grid-template-columns:repeat(3,1fr);gap:12px; }
.p-stat { background:rgba(132,106,83,0.05);border:1px solid rgba(132,106,83,0.1);border-radius:10px;padding:14px;text-align:center; }
.p-stat-btn { cursor:pointer;font-family:inherit;transition:background .15s,border-color .15s; }
.p-stat-btn:hover { background:rgba(132,106,83,0.12);border-color:rgba(132,106,83,0.3); }
.p-stat-val { font-size:1.5rem;font-weight:800;color:#a8834a; }
.p-stat-label { font-size:0.7rem;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;margin-top:3px; }
/* Modals */
.p-modal-overlay { display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:99999;align-items:center;justify-content:center; }
.p-modal-overlay.open { display:flex; }
.p-modal { background:var(--card);border-radius:14px;width:100%;max-width:380px;max-height:70vh;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,0.25);margin:20px; }
.p-modal-head { padding:18px 20px;border-bottom:1px solid rgba(132,106,83,0.1);display:flex;align-items:center;justify-content:space-between;flex-shrink:0; }
.p-modal-head h3 { font-size:1rem;font-weight:800;color:var(--accent);display:flex;align-items:center;gap:8px; }
.p-modal-count { background:rgba(132,106,83,0.1);color:var(--muted);font-size:0.75rem;font-weight:600;padding:2px 8px;border-radius:20px; }
.p-modal-close { background:none;border:none;cursor:pointer;font-size:1.1rem;color:var(--muted);padding:4px;line-height:1; }
.p-modal-close:hover { color:var(--accent); }
.p-modal-body { overflow-y:auto;padding:10px 12px;display:flex;flex-direction:column;gap:4px; }
.p-modal-user { display:flex;align-items:center;gap:12px;padding:10px 12px;border-radius:10px;text-decoration:none;transition:background .15s; }
.p-modal-user:hover { background:rgba(132,106,83,0.07); }
.p-modal-avatar { width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,#c9a96e,#a8834a);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.9rem;color:white;flex-shrink:0; }
.p-alerts { margin-bottom:18px;display:flex;flex-direction:column;gap:7px; }
.p-alert { padding:11px 15px;border-radius:8px;font-size:0.88rem;display:flex;align-items:center;gap:8px; }
.p-alert-error { background:#fdecea;border:1px solid #f5c6c2;color:#c0392b; }
.p-alert-success { background:#e8f5ef;border:1px solid #b2dfcc;color:#2e7d5e; }
.fav-item { display:flex;align-items:center;gap:12px;padding:11px 13px;border:1px solid rgba(132,106,83,0.1);border-radius:9px;background:rgba(132,106,83,0.02);transition:background .15s; }
.fav-item:hover { background:rgba(132,106,83,0.05); }
.avis-item { padding:12px 14px;border:1px solid rgba(132,106,83,0.1);border-radius:9px;background:rgba(132,106,83,0.02);transition:background .15s; }
.avis-item:hover { background:rgba(132,106,83,0.05); }
@media (max-width:768px) {
    .p-grid { grid-template-columns:1fr; }
    .p-form-row { grid-template-columns:1fr; }
    .p-stats { grid-template-columns:1fr; }
    .p-hero { flex-direction:column;text-align:center; }
}
</style>

<main class="profile-main">

    <?php if (!empty($errors) || !empty($success)): ?>
    <div class="p-alerts">
        <?php foreach ($errors as $e): ?><div class="p-alert p-alert-error">⚠️ <?= htmlspecialchars($e) ?></div><?php endforeach; ?>
        <?php foreach ($success as $s): ?><div class="p-alert p-alert-success">✅ <?= htmlspecialchars($s) ?></div><?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="p-hero">
        <form class="avatar-form" method="POST" enctype="multipart/form-data" action="profile.php">
            <input type="hidden" name="action" value="update_avatar">
            <div class="p-avatar-wrapper">
                <img src="<?= $avatarSrc ?>" alt="Avatar" class="p-avatar-img" id="avatar-preview">
                <label class="p-avatar-edit" for="avatar-input" title="Changer">✏️</label>
                <input type="file" id="avatar-input" name="avatar" accept="image/*" onchange="previewAvatar(this); this.form.submit();">
            </div>
        </form>
        <div class="p-info">
            <h1><?= htmlspecialchars(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? '')) ?></h1>
            <div class="p-pseudo">@<?= htmlspecialchars($user['username'] ?? '') ?></div>
            <div class="p-bio"><?= !empty($user['bio']) ? htmlspecialchars($user['bio']) : '<em style="opacity:.5">Aucune bio renseignée.</em>' ?></div>
        </div>
    </div>

    <div class="p-grid">

        <!-- Infos personnelles -->
        <div class="p-card">
            <div class="p-card-head"><span>👤</span><h2>Informations personnelles</h2></div>
            <div class="p-card-body">
                <form method="POST" action="profile.php">
                    <input type="hidden" name="action" value="update_infos">
                    <div class="p-form-row">
                        <div class="p-form-group"><label class="p-label">Prénom</label><input class="p-input" type="text" name="prenom" maxlength="64" value="<?= htmlspecialchars($user['prenom'] ?? '') ?>" required></div>
                        <div class="p-form-group"><label class="p-label">Nom</label><input class="p-input" type="text" name="nom" maxlength="64" value="<?= htmlspecialchars($user['nom'] ?? '') ?>" required></div>
                    </div>
                    <div class="p-form-row full">
                        <div class="p-form-group"><label class="p-label">Pseudo</label><input class="p-input" type="text" name="username" maxlength="32" pattern="[a-zA-Z0-9_]{3,32}" value="<?= htmlspecialchars($user['username'] ?? '') ?>" required></div>
                    </div>
                    <div class="p-form-row full">
                        <div class="p-form-group">
                            <label class="p-label">Bio <span style="font-weight:300;text-transform:none">(max 300 car.)</span></label>
                            <textarea style = background:var(--accent-5);color:var(--accent); class="p-textarea" name="bio" maxlength="300" oninput="updateCount(this)"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                            <div class="p-char" id="bio-count"><?= mb_strlen($user['bio'] ?? '') ?> / 300</div>
                        </div>
                    </div>
                    <div class="p-form-actions"><button type="submit" class="p-btn p-btn-gold">💾 Sauvegarder</button></div>
                </form>
            </div>
        </div>

        <!-- Carte agent -->
        <div class="p-card">
            <div class="p-card-head"><span>🪪</span><h2>Ma carte d'agent</h2></div>
            <div class="p-card-body">
                <p style="font-size:0.83rem;color:var(--muted);margin-bottom:16px;line-height:1.7;">Ton identifiant de connexion unique. Ne la partage jamais.</p>
                <div class="p-card-agent">
                    <div class="p-card-agent-label">🔍 Biblio&apos;Spies — Carte Agent</div>
                    <div class="p-card-code" id="card-code-display"><?= htmlspecialchars($cardMasked) ?></div>
                    <div class="p-card-name"><?= htmlspecialchars(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? '')) ?></div>
                    <div class="p-card-btns">
                        <button class="p-btn-card" id="toggle-btn" onclick="toggleCard()">👁️ Révéler</button>
                        <button class="p-btn-card" onclick="copyCard()">📋 Copier</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="p-card">
            <div class="p-card-head"><span>📊</span><h2>Mes statistiques</h2></div>
            <div class="p-card-body">
                <div class="p-stats">
                    <button class="p-stat p-stat-btn" onclick="openModal('modal-abonnements')" title="Voir mes abonnements">
                        <div class="p-stat-val"><?= $nbAbonnements ?></div>
                        <div class="p-stat-label">Abonnements</div>
                    </button>
                    <button class="p-stat p-stat-btn" onclick="openModal('modal-abonnes')" title="Voir mes abonnés">
                        <div class="p-stat-val"><?= $nbAbonnes ?></div>
                        <div class="p-stat-label">Abonnés</div>
                    </button>
                    <div class="p-stat">
                        <div class="p-stat-val"><?= htmlspecialchars(date('d/m/Y', strtotime($user['created_at'] ?? 'now'))) ?></div>
                        <div class="p-stat-label">Membre depuis</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Abonnements -->
        <div id="modal-abonnements" class="p-modal-overlay" onclick="closeModal('modal-abonnements')">
            <div class="p-modal" onclick="event.stopPropagation()">
                <div class="p-modal-head">
                    <h3>Abonnements <span class="p-modal-count"><?= $nbAbonnements ?></span></h3>
                    <button onclick="closeModal('modal-abonnements')" class="p-modal-close">✕</button>
                </div>
                <div class="p-modal-body">
                    <?php if (empty($listAbonnements)): ?>
                        <p style="color:var(--muted);font-size:0.88rem;text-align:center;padding:20px 0;">Tu ne suis personne pour l'instant.</p>
                    <?php else: ?>
                        <?php foreach ($listAbonnements as $u): ?>
                        <a href="agent_profile.php?id=<?= $u['id'] ?>" class="p-modal-user">
                            <div class="p-modal-avatar"><?= strtoupper(mb_substr($u['prenom'] ?? '?', 0, 1)) ?></div>
                            <div>
                                <div style="font-weight:700;font-size:0.9rem;color:var(--accent);">@<?= htmlspecialchars($u['username']) ?></div>
                                <div style="font-size:0.78rem;color:var(--muted);"><?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?></div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Modal Abonnés -->
        <div id="modal-abonnes" class="p-modal-overlay" onclick="closeModal('modal-abonnes')">
            <div class="p-modal" onclick="event.stopPropagation()">
                <div class="p-modal-head">
                    <h3>Abonnés <span class="p-modal-count"><?= $nbAbonnes ?></span></h3>
                    <button onclick="closeModal('modal-abonnes')" class="p-modal-close">✕</button>
                </div>
                <div class="p-modal-body">
                    <?php if (empty($listAbonnes)): ?>
                        <p style="color:var(--muted);font-size:0.88rem;text-align:center;padding:20px 0;">Personne ne te suit encore.</p>
                    <?php else: ?>
                        <?php foreach ($listAbonnes as $u): ?>
                        <a href="agent_profile.php?id=<?= $u['id'] ?>" class="p-modal-user">
                            <div class="p-modal-avatar"><?= strtoupper(mb_substr($u['prenom'] ?? '?', 0, 1)) ?></div>
                            <div>
                                <div style="font-weight:700;font-size:0.9rem;color:var(--accent);">@<?= htmlspecialchars($u['username']) ?></div>
                                <div style="font-size:0.78rem;color:var(--muted);"><?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?></div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Zone danger -->
        <div class="p-card">
            <div class="p-card-head"><span>⚠️</span><h2>Zone de danger</h2></div>
            <div class="p-card-body">
                <p style="font-size:0.85rem;color:var(--muted);line-height:1.7;margin-bottom:14px;">La suppression de ton compte est irréversible. Toutes tes données seront effacées.</p>
                <button class="p-btn-danger" onclick="confirmDelete()">🗑️ Supprimer mon compte</button>
            </div>
        </div>

        <!-- Favoris -->
        <div class="p-card p-full">
            <div class="p-card-head"><span>❤️</span><h2>Mes Favoris</h2></div>
            <div class="p-card-body">
                <?php if (empty($favoris)): ?>
                    <p style="color:var(--muted);font-size:0.88rem;">Aucun livre en favori. Ajoute des livres depuis leur page !</p>
                <?php else: ?>
                <div style="margin-bottom:14px;">
                    <input type="text" id="favSearch" placeholder="🔍 Rechercher dans mes favoris..."
                           oninput="filterFavs(this.value)" class="p-input" style="width:100%;max-width:380px;">
                </div>
                <div id="favsList" style="display:flex;flex-direction:column;gap:9px;">
                    <?php foreach ($favoris as $fav): ?>
                    <div class="fav-item"
                         data-title="<?= htmlspecialchars(mb_strtolower($fav['titre'] ?? '')) ?>"
                         data-auteur="<?= htmlspecialchars(mb_strtolower($fav['auteur'] ?? '')) ?>">
                        <?php if (!empty($fav['image'])): ?>
                        <img src="<?= htmlspecialchars($fav['image']) ?>" alt="" style="width:40px;height:56px;object-fit:cover;border-radius:4px;flex-shrink:0;">
                        <?php else: ?>
                        <div style="width:40px;height:56px;background:rgba(132,106,83,0.1);border-radius:4px;flex-shrink:0;display:flex;align-items:center;justify-content:center;">📖</div>
                        <?php endif; ?>
                        <div style="flex:1;min-width:0;">
                            <a href="book-page.php?isbn=<?= urlencode($fav['isbn']) ?>"
                               style="font-weight:700;font-size:0.88rem;color:var(--accent);text-decoration:none;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                <?= htmlspecialchars($fav['titre'] ?? 'Titre inconnu') ?>
                            </a>
                            <p style="font-size:0.78rem;color:var(--muted);margin-top:2px;"><?= htmlspecialchars($fav['auteur'] ?? 'Auteur inconnu') ?></p>
                        </div>
                        <form method="POST" action="toggle_favoris.php" style="flex-shrink:0;">
                            <input type="hidden" name="isbn" value="<?= htmlspecialchars($fav['isbn']) ?>">
                            <button type="submit" title="Retirer" style="background:none;border:none;cursor:pointer;font-size:15px;color:#c0392b;padding:4px;">🗑️</button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div id="favNoResult" style="display:none;color:var(--muted);font-size:0.88rem;padding:12px 0;">Aucun résultat.</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Mes Avis -->
        <div class="p-card p-full">
            <div class="p-card-head"><span>💬</span><h2>Mes Avis</h2></div>
            <div class="p-card-body">
                <?php if (empty($mesAvis)): ?>
                    <p style="color:var(--muted);font-size:0.88rem;">Tu n'as pas encore écrit d'avis. Découvre des livres et partage ton ressenti !</p>
                <?php else: ?>
                <div style="margin-bottom:14px;">
                    <input type="text" id="avisSearch" placeholder="🔍 Rechercher dans mes avis..."
                           oninput="filterAvis(this.value)" class="p-input" style="width:100%;max-width:380px;">
                </div>
                <div id="avisList" style="display:flex;flex-direction:column;gap:10px;">
                    <?php foreach ($mesAvis as $av):
                        $rating = max(1, min(5, (int)($av['rating'] ?? 3)));
                    ?>
                    <div class="avis-item"
                         data-titre="<?= htmlspecialchars(mb_strtolower($av['titre'] ?? '')) ?>">
                        <div style="display:flex;align-items:flex-start;gap:13px;">
                            <?php if (!empty($av['image'])): ?>
                            <a href="book-page.php?isbn=<?= urlencode($av['isbn']) ?>" style="flex-shrink:0;">
                                <img src="<?= htmlspecialchars($av['image']) ?>" alt=""
                                     style="width:40px;height:56px;object-fit:cover;border-radius:4px;">
                            </a>
                            <?php else: ?>
                            <div style="width:40px;height:56px;background:rgba(132,106,83,0.1);border-radius:4px;flex-shrink:0;display:flex;align-items:center;justify-content:center;">📖</div>
                            <?php endif; ?>
                            <div style="flex:1;min-width:0;">
                                <div style="display:flex;align-items:center;gap:9px;flex-wrap:wrap;margin-bottom:5px;">
                                    <a href="book-page.php?isbn=<?= urlencode($av['isbn']) ?>"
                                       style="font-weight:700;font-size:0.88rem;color:var(--accent);text-decoration:none;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:260px;display:block;">
                                        <?= htmlspecialchars($av['titre'] ?? 'Livre inconnu') ?>
                                    </a>
                                    <span style="color:#f6b01e;font-size:0.85rem;white-space:nowrap;">
                                        <?= str_repeat('★', $rating) ?><span style="color:#ddd;"><?= str_repeat('★', 5 - $rating) ?></span>
                                    </span>
                                    <span style="color:var(--muted);font-size:0.74rem;"><?= date('d/m/Y', strtotime($av['created_at'])) ?></span>
                                </div>
                                <!-- Affichage -->
                                <div id="avis-content-<?= $av['id'] ?>"
                                     style="color:var(--muted);font-size:0.86rem;line-height:1.65;">
                                    <?= nl2br(htmlspecialchars($av['contenu'])) ?>
                                </div>
                                <!-- Boutons -->
                                <div style="display:flex;gap:7px;margin-top:7px;">
                                    <button onclick="toggleEditProfileAvis(<?= $av['id'] ?>)"
                                            style="background:none;border:1px solid rgba(132,106,83,0.2);border-radius:6px;padding:3px 10px;cursor:pointer;font-size:0.75rem;color:var(--muted);">✏️ Modifier</button>
                                    <form method="POST" action="delete_review.php" style="margin:0;" onsubmit="return confirm('Supprimer cet avis ?')">
                                        <input type="hidden" name="id"   value="<?= $av['id'] ?>">
                                        <input type="hidden" name="isbn" value="<?= htmlspecialchars($av['isbn']) ?>">
                                        <button type="submit" style="background:none;border:1px solid rgba(192,57,43,.2);border-radius:6px;padding:3px 10px;cursor:pointer;font-size:0.75rem;color:#c0392b;">🗑️ Supprimer</button>
                                    </form>
                                </div>
                                <!-- Formulaire édition inline -->
                                <form id="avis-edit-<?= $av['id'] ?>" method="POST" action="edit_review.php"
                                      style="display:none;margin-top:10px;">
                                    <input type="hidden" name="id"   value="<?= $av['id'] ?>">
                                    <input type="hidden" name="isbn" value="<?= htmlspecialchars($av['isbn']) ?>">
                                    <select name="rating" style="padding:6px 10px;border:1.5px solid rgba(132,106,83,0.18);border-radius:7px;background:var(--bg);color:var(--accent);font-size:0.83rem;margin-bottom:7px;display:block;">
                                        <?php for ($i=5;$i>=1;$i--): ?>
                                        <option value="<?= $i ?>" <?= $rating===$i?'selected':'' ?>><?= $i ?> — <?= ['','Mauvais','Moyen','Bien','Très bien','Excellent'][$i] ?></option>
                                        <?php endfor; ?>
                                    </select>
                                    <textarea name="review" rows="3" required
                                              style="width:100%;padding:8px 10px;border:1.5px solid rgba(132,106,83,0.18);border-radius:7px;background:var(--bg);color:var(--accent);font-size:0.85rem;resize:none;margin-bottom:7px;"><?= htmlspecialchars($av['contenu']) ?></textarea>
                                    <div style="display:flex;gap:7px;">
                                        <button type="submit" style="padding:6px 15px;background:var(--accent);color:white;border:none;border-radius:7px;cursor:pointer;font-size:0.82rem;font-weight:700;">💾 Sauvegarder</button>
                                        <button type="button" onclick="toggleEditProfileAvis(<?= $av['id'] ?>)"
                                                style="padding:6px 11px;background:rgba(132,106,83,0.07);border:none;border-radius:7px;cursor:pointer;font-size:0.82rem;color:var(--muted);">Annuler</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div id="avisNoResult" style="display:none;color:var(--muted);font-size:0.88rem;padding:10px 0;">Aucun résultat.</div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</main>

<?php require_once 'footer.php'; ?>

<script>
function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => document.getElementById('avatar-preview').src = e.target.result;
        reader.readAsDataURL(input.files[0]);
    }
}

function updateCount(el) {
    const count = document.getElementById('bio-count');
    count.textContent = el.value.length + ' / 300';
    count.classList.toggle('warn', el.value.length > 270);
}

// Révéler UNIQUEMENT via le bouton
const fullCard = '<?= addslashes($carteCode) ?>';
const masked   = '<?= addslashes($cardMasked) ?>';
let   revealed = false;

function toggleCard() {
    revealed = !revealed;
    document.getElementById('card-code-display').textContent = revealed ? fullCard : masked;
    document.getElementById('toggle-btn').textContent = revealed ? '🙈 Masquer' : '👁️ Révéler';
}

function copyCard() {
    navigator.clipboard.writeText(fullCard).then(() => {
        const btn = event.currentTarget;
        const orig = btn.textContent;
        btn.textContent = '✅ Copié !';
        setTimeout(() => btn.textContent = orig, 2000);
    });
}

// Filtrer favoris avec normalisation apostrophes
function normalizeStr(s) {
    return String(s)
        .replace(/[\u2018\u2019\u201A\u201B\u2032\u0060]/g, "'")
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .toLowerCase();
}

function filterFavs(q) {
    const query = normalizeStr(q.trim());
    const items = document.querySelectorAll('.fav-item');
    let visible = 0;
    items.forEach(item => {
        const t = normalizeStr((item.dataset.title || '') + ' ' + (item.dataset.auteur || ''));
        const show = !query || t.includes(query);
        item.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    const nr = document.getElementById('favNoResult');
    if (nr) nr.style.display = (visible === 0 && query) ? 'block' : 'none';
}

function toggleEditProfileAvis(id) {
    const c = document.getElementById('avis-content-' + id);
    const f = document.getElementById('avis-edit-'    + id);
    if (!c || !f) return;
    const showing = f.style.display === 'block';
    f.style.display = showing ? 'none'  : 'block';
    c.style.display = showing ? 'block' : 'none';
}

function filterAvis(q) {
    const query = normalizeStr(q.trim());
    const items = document.querySelectorAll('.avis-item');
    let visible = 0;
    items.forEach(item => {
        const t = normalizeStr(item.dataset.titre || '');
        const show = !query || t.includes(query);
        item.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    const nr = document.getElementById('avisNoResult');
    if (nr) nr.style.display = (visible === 0 && query) ? 'block' : 'none';
}

function openModal(id) {
    document.getElementById(id).classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeModal(id) {
    document.getElementById(id).classList.remove('open');
    document.body.style.overflow = '';
}
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') document.querySelectorAll('.p-modal-overlay.open').forEach(m => m.classList.remove('open'));
});

function confirmDelete() {
    if (confirm('Es-tu sûr(e) de vouloir supprimer définitivement ton compte ?')) {
        window.location.href = 'parametres_compte.php?action=delete';
    }
}

// Auto-masquer alertes succès
document.querySelectorAll('.p-alert-success').forEach(el => {
    setTimeout(() => { el.style.transition = 'opacity .5s'; el.style.opacity = '0'; setTimeout(() => el.remove(), 500); }, 4000);
});
</script>
