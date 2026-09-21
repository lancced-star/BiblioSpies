?php
require 'session_init.php';
require_once 'connexion-bdd.php';

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$errors  = [];
$success = [];

// ─── Récupération de l'utilisateur ────────────────────────────────
$stmt = $bdd->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();
if (!$user) { session_destroy(); header('Location: login.php'); exit; }

// ─── Traitement : infos générales ────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'update_infos') {
    $prenom = trim($_POST['prenom'] ?? '');
    $nom    = trim($_POST['nom']    ?? '');
    $pseudo = trim($_POST['pseudo'] ?? '');
    $bio    = trim($_POST['bio']    ?? '');

    if ($prenom === '' || mb_strlen($prenom) > 64) $errors[] = 'Prénom invalide.';
    if ($nom    === '' || mb_strlen($nom)    > 64) $errors[] = 'Nom invalide.';
    if (!preg_match('/^[a-zA-Z0-9_]{3,32}$/', $pseudo)) $errors[] = 'Pseudo invalide (3-32 car., lettres/chiffres/underscore).';
    if (mb_strlen($bio) > 300) $errors[] = 'Bio trop longue (300 caractères max).';

    if (empty($errors)) {
        // Vérifier unicité du pseudo (hors soi-même)
        $chk = $bdd->prepare('SELECT 1 FROM users WHERE pseudo = ? AND id != ?');
        $chk->execute([$pseudo, $userId]);
        if ($chk->fetch()) {
            $errors[] = 'Ce pseudo est déjà pris.';
        } else {
            $upd = $bdd->prepare('UPDATE users SET prenom=?, nom=?, pseudo=?, bio=? WHERE id=?');
            $upd->execute([$prenom, $nom, $pseudo, $bio, $userId]);
            $_SESSION['pseudo'] = $pseudo;
            $_SESSION['prenom'] = $prenom;
            $success[] = 'Profil mis à jour avec succès !';
            // Rafraîchir les données
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
        }
    }
}

// ─── Traitement : upload avatar ───────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'update_avatar') {
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $allowed   = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize   = 2 * 1024 * 1024; // 2 Mo
        $mimeType  = mime_content_type($_FILES['avatar']['tmp_name']);

        if (!in_array($mimeType, $allowed)) {
            $errors[] = 'Format non supporté (JPG, PNG, GIF, WEBP uniquement).';
        } elseif ($_FILES['avatar']['size'] > $maxSize) {
            $errors[] = 'Image trop lourde (2 Mo max).';
        } else {
            $ext     = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $dir     = 'uploads/avatars/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $filename = 'avatar_' . $userId . '_' . time() . '.' . $ext;
            $dest     = $dir . $filename;

            // Supprimer l'ancien avatar s'il existe
            if (!empty($user['avatar']) && file_exists($user['avatar'])) {
                unlink($user['avatar']);
            }

            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $dest)) {
                $bdd->prepare('UPDATE users SET avatar=? WHERE id=?')->execute([$dest, $userId]);
                $success[] = 'Avatar mis à jour !';
                $stmt->execute([$userId]);
                $user = $stmt->fetch();
            } else {
                $errors[] = 'Erreur lors de l\'upload.';
            }
        }
    } else {
        $errors[] = 'Aucun fichier reçu.';
    }
}

// ─── Avatar par défaut ────────────────────────────────────────────
$avatarSrc = !empty($user['avatar']) && file_exists($user['avatar'])
    ? htmlspecialchars($user['avatar'])
    : 'https://ui-avatars.com/api/?name=' . urlencode($user['prenom'] . '+' . $user['nom']) . '&background=c9a96e&color=fff&size=200&bold=true';

$cardMasked = substr($user['card_code'], 0, 4) . '-****-****-' . substr($user['card_code'], -4);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mon Profil — Biblio'Spies</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
    --cream:   #faf6f0;
    --warm:    #f2ebe0;
    --border:  #e4d9c8;
    --brown:   #5c3d2e;
    --gold:    #c9a96e;
    --gold-d:  #a8834a;
    --text:    #3a2a1e;
    --muted:   #8a7060;
    --red:     #c0392b;
    --green:   #2e7d5e;
    --white:   #ffffff;
    --radius:  14px;
    --shadow:  0 4px 24px rgba(92,61,46,0.10);
}

body {
    background: var(--cream);
    font-family: 'Lato', sans-serif;
    color: var(--text);
    min-height: 100vh;
}

/* ── Layout ── */
.page-wrapper {
    max-width: 860px;
    margin: 0 auto;
    padding: 48px 20px 80px;
}

/* ── Header profil ── */
.profile-hero {
    display: flex;
    align-items: center;
    gap: 32px;
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 36px;
    box-shadow: var(--shadow);
    margin-bottom: 32px;
    flex-wrap: wrap;
}

.avatar-wrapper {
    position: relative;
    flex-shrink: 0;
}

.avatar-img {
    width: 110px;
    height: 110px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid var(--gold);
    display: block;
}

.avatar-edit-btn {
    position: absolute;
    bottom: 2px;
    right: 2px;
    background: var(--gold);
    border: 2px solid var(--white);
    border-radius: 50%;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 14px;
    transition: background .2s;
}
.avatar-edit-btn:hover { background: var(--gold-d); }

.avatar-form input[type="file"] { display: none; }

.profile-info h1 {
    font-family: 'Playfair Display', serif;
    font-size: 26px;
    color: var(--brown);
    margin-bottom: 6px;
}

.profile-info .pseudo {
    font-size: 14px;
    color: var(--gold-d);
    font-weight: 700;
    letter-spacing: .05em;
    margin-bottom: 10px;
}

.profile-info .bio-preview {
    font-size: 14px;
    color: var(--muted);
    font-style: italic;
    line-height: 1.6;
    max-width: 480px;
}

/* ── Sections ── */
.sections { display: flex; flex-direction: column; gap: 24px; }

.section-card {
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
    box-shadow: var(--shadow);
}

.section-head {
    padding: 18px 28px;
    border-bottom: 1px solid var(--border);
    background: var(--warm);
    display: flex;
    align-items: center;
    gap: 10px;
}

.section-head .icon { font-size: 18px; }

.section-head h2 {
    font-family: 'Playfair Display', serif;
    font-size: 17px;
    color: var(--brown);
    font-weight: 700;
}

.section-body { padding: 28px; }

/* ── Formulaire ── */
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
}

.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-group.full { grid-column: 1 / -1; }

label {
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: var(--muted);
}

input[type="text"],
textarea {
    width: 100%;
    padding: 11px 14px;
    border: 1.5px solid var(--border);
    border-radius: 8px;
    font-family: 'Lato', sans-serif;
    font-size: 14px;
    color: var(--text);
    background: var(--cream);
    transition: border-color .2s, box-shadow .2s;
    outline: none;
    resize: none;
}

input[type="text"]:focus,
textarea:focus {
    border-color: var(--gold);
    box-shadow: 0 0 0 3px rgba(201,169,110,.15);
    background: var(--white);
}

textarea { min-height: 90px; line-height: 1.6; }

.char-count {
    font-size: 11px;
    color: var(--muted);
    text-align: right;
    margin-top: 2px;
}
.char-count.warn { color: var(--red); }

/* ── Bouton ── */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 11px 26px;
    border-radius: 8px;
    font-family: 'Lato', sans-serif;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    border: none;
    transition: background .2s, transform .1s;
}
.btn:active { transform: scale(.98); }

.btn-gold {
    background: var(--gold);
    color: var(--white);
}
.btn-gold:hover { background: var(--gold-d); }

.btn-outline {
    background: transparent;
    border: 1.5px solid var(--border);
    color: var(--muted);
}
.btn-outline:hover { border-color: var(--gold); color: var(--gold-d); }

.form-actions {
    margin-top: 22px;
    display: flex;
    justify-content: flex-end;
}

/* ── Carte ── */
.card-display {
    background: linear-gradient(135deg, #3a2a1e 0%, #5c3d2e 60%, #c9a96e 100%);
    border-radius: 14px;
    padding: 28px 32px;
    color: #fff;
    position: relative;
    overflow: hidden;
    max-width: 380px;
}

.card-display::before {
    content: '';
    position: absolute;
    top: -40px; right: -40px;
    width: 160px; height: 160px;
    border-radius: 50%;
    background: rgba(255,255,255,0.06);
}

.card-display::after {
    content: '';
    position: absolute;
    bottom: -30px; left: -20px;
    width: 120px; height: 120px;
    border-radius: 50%;
    background: rgba(255,255,255,0.04);
}

.card-label {
    font-size: 10px;
    letter-spacing: .15em;
    text-transform: uppercase;
    opacity: .7;
    margin-bottom: 20px;
}

.card-code {
    font-family: 'Lato', monospace;
    font-size: 20px;
    font-weight: 700;
    letter-spacing: .18em;
    margin-bottom: 20px;
    transition: opacity .3s;
}

.card-name {
    font-size: 13px;
    opacity: .85;
    text-transform: uppercase;
    letter-spacing: .1em;
}

.card-actions { margin-top: 20px; display: flex; gap: 10px; }

.btn-small {
    font-size: 12px;
    padding: 7px 16px;
    border-radius: 6px;
    cursor: pointer;
    border: 1.5px solid rgba(255,255,255,.4);
    background: transparent;
    color: #fff;
    font-family: 'Lato', sans-serif;
    font-weight: 700;
    transition: background .2s;
}
.btn-small:hover { background: rgba(255,255,255,.15); }

/* ── Alertes ── */
.alerts { margin-bottom: 24px; display: flex; flex-direction: column; gap: 10px; }

.alert {
    padding: 13px 18px;
    border-radius: 8px;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.alert-error   { background: #fdecea; border: 1px solid #f5c6c2; color: var(--red); }
.alert-success { background: #e8f5ef; border: 1px solid #b2dfcc; color: var(--green); }

/* ── Stats ── */
.stats-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
}

.stat-box {
    background: var(--warm);
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 18px;
    text-align: center;
}

.stat-val {
    font-family: 'Playfair Display', serif;
    font-size: 28px;
    font-weight: 700;
    color: var(--gold-d);
}

.stat-label {
    font-size: 11px;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: .08em;
    margin-top: 4px;
}

/* ── Danger zone ── */
.danger-text {
    font-size: 14px;
    color: var(--muted);
    line-height: 1.7;
    margin-bottom: 18px;
}

.btn-danger {
    background: transparent;
    border: 1.5px solid var(--red);
    color: var(--red);
    padding: 10px 22px;
    border-radius: 8px;
    font-family: 'Lato', sans-serif;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    transition: background .2s, color .2s;
}
.btn-danger:hover { background: var(--red); color: #fff; }

/* ── Responsive ── */
@media (max-width: 600px) {
    .form-grid { grid-template-columns: 1fr; }
    .profile-hero { flex-direction: column; text-align: center; }
    .stats-row { grid-template-columns: 1fr; }
}
</style>
</head>
<body>

<?php
// ── Inclure ta navbar si tu en as une ──
// require_once 'navbar.php';
?>

<div class="page-wrapper">

    <!-- Alertes -->
    <?php if (!empty($errors) || !empty($success)): ?>
    <div class="alerts">
        <?php foreach ($errors  as $e): ?><div class="alert alert-error">⚠️ <?= htmlspecialchars($e) ?></div><?php endforeach; ?>
        <?php if ($success): ?><div class="alert alert-success">✅ Profil mis à jour !</div><?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Hero profil -->
    <div class="profile-hero">
        <!-- Avatar + formulaire upload -->
        <form class="avatar-form" method="POST" enctype="multipart/form-data" action="profil.php">
            <input type="hidden" name="action" value="update_avatar">
            <div class="avatar-wrapper">
                <img src="<?= $avatarSrc ?>" alt="Avatar" class="avatar-img" id="avatar-preview">
                <label class="avatar-edit-btn" for="avatar-input" title="Changer l'avatar">✏️</label>
                <input type="file" id="avatar-input" name="avatar" accept="image/*"
                       onchange="previewAvatar(this); this.form.submit();">
            </div>
        </form>

        <div class="profile-info">
            <h1><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></h1>
            <div class="pseudo">@<?= htmlspecialchars($user['pseudo']) ?></div>
            <div class="bio-preview">
                <?= !empty($user['bio'])
                    ? htmlspecialchars($user['bio'])
                    : '<em>Aucune bio renseignée.</em>' ?>
            </div>
        </div>
    </div>

    <div class="sections">

        <!-- ── Informations personnelles ── -->
        <div class="section-card">
            <div class="section-head">
                <span class="icon">👤</span>
                <h2>Informations personnelles</h2>
            </div>
            <div class="section-body">
                <form method="POST" action="profil.php">
                    <input type="hidden" name="action" value="update_infos">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="prenom">Prénom</label>
                            <input type="text" id="prenom" name="prenom" maxlength="64"
                                   value="<?= htmlspecialchars($user['prenom']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="nom">Nom</label>
                            <input type="text" id="nom" name="nom" maxlength="64"
                                   value="<?= htmlspecialchars($user['nom']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="pseudo">Pseudo</label>
                            <input type="text" id="pseudo" name="pseudo" maxlength="32"
                                   pattern="[a-zA-Z0-9_]{3,32}"
                                   value="<?= htmlspecialchars($user['pseudo']) ?>" required>
                        </div>
                        <div class="form-group full">
                            <label for="bio">Bio <span style="font-weight:300;text-transform:none">(max 300 car.)</span></label>
                            <textarea id="bio" name="bio" maxlength="300"
                                      oninput="updateCount(this)"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                            <div class="char-count" id="bio-count">
                                <?= mb_strlen($user['bio'] ?? '') ?> / 300
                            </div>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-gold">💾 Sauvegarder</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ── Ma carte d'agent ── -->
        <div class="section-card">
            <div class="section-head">
                <span class="icon">🪪</span>
                <h2>Ma carte d'agent</h2>
            </div>
            <div class="section-body">
                <p style="font-size:14px;color:var(--muted);margin-bottom:20px;line-height:1.7">
                    Cette carte est ton identifiant de connexion unique. Ne la partage jamais.
                </p>
                <div class="card-display">
                    <div class="card-label">🔍 Biblio'Spies — Carte Agent</div>
                    <div class="card-code" id="card-code-display"><?= htmlspecialchars($cardMasked) ?></div>
                    <div class="card-name"><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></div>
                    <div class="card-actions">
                        <button class="btn-small" onclick="toggleCard()" id="toggle-btn">👁️ Révéler</button>
                        <button class="btn-small" onclick="copyCard()">📋 Copier</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Statistiques ── -->
        <div class="section-card">
            <div class="section-head">
                <span class="icon">📊</span>
                <h2>Mes statistiques</h2>
            </div>
            <div class="section-body">
                <div class="stats-row">
                    <?php
                    // Adapter ces requêtes selon tes tables
                    $nbLivres = 0; $nbAbonnes = 0; $nbAbonnements = 0;
                    try {
                        $r = $pdo->prepare('SELECT COUNT(*) FROM abonnements WHERE user_id = ?');
                        $r->execute([$userId]); $nbAbonnements = $r->fetchColumn();
                        $r = $pdo->prepare('SELECT COUNT(*) FROM abonnes WHERE user_id = ?');
                        $r->execute([$userId]); $nbAbonnes = $r->fetchColumn();
                    } catch (Exception $e) { /* tables pas encore créées */ }
                    ?>
                    <div class="stat-box">
                        <div class="stat-val"><?= $nbAbonnements ?></div>
                        <div class="stat-label">Abonnements</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-val"><?= $nbAbonnes ?></div>
                        <div class="stat-label">Abonnés</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-val"><?= htmlspecialchars(date('d/m/Y', strtotime($user['created_at']))) ?></div>
                        <div class="stat-label">Membre depuis</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Zone danger ── -->
        <div class="section-card">
            <div class="section-head">
                <span class="icon">⚠️</span>
                <h2>Zone de danger</h2>
            </div>
            <div class="section-body">
                <p class="danger-text">
                    La suppression de ton compte est irréversible. Toutes tes données seront effacées définitivement.
                </p>
                <button class="btn-danger" onclick="confirmDelete()">🗑️ Supprimer mon compte</button>
            </div>
        </div>

    </div><!-- /sections -->
</div><!-- /page-wrapper -->

<script>
// ── Preview avatar avant upload ──────────────────────────────────
function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => document.getElementById('avatar-preview').src = e.target.result;
        reader.readAsDataURL(input.files[0]);
    }
}

// ── Compteur bio ─────────────────────────────────────────────────
function updateCount(el) {
    const count = document.getElementById('bio-count');
    const len   = el.value.length;
    count.textContent = len + ' / 300';
    count.classList.toggle('warn', len > 270);
}

// ── Révéler / masquer la carte ───────────────────────────────────
const fullCard  = '<?= htmlspecialchars($user['card_code']) ?>';
const masked    = '<?= htmlspecialchars($cardMasked) ?>';
let   revealed  = false;

function toggleCard() {
    revealed = !revealed;
    document.getElementById('card-code-display').textContent = revealed ? fullCard : masked;
    document.getElementById('toggle-btn').textContent = revealed ? '🙈 Masquer' : '👁️ Révéler';
}

// ── Copier la carte ──────────────────────────────────────────────
function copyCard() {
    navigator.clipboard.writeText(fullCard).then(() => {
        const btn = event.target;
        btn.textContent = '✅ Copié !';
        setTimeout(() => btn.textContent = '📋 Copier', 2000);
    });
}

// ── Supprimer le compte ──────────────────────────────────────────
function confirmDelete() {
    if (confirm('Es-tu sûr(e) de vouloir supprimer définitivement ton compte ? Cette action est irréversible.')) {
        window.location.href = 'parametres_compte.php?action=delete';
    }
}
</script>
</body>
</html>
