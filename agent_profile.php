<?php
require_once 'connexion-bdd.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$targetId  = (int)($_GET['id'] ?? 0);
$currentId = (int)($_SESSION['user_id'] ?? 0);

if ($targetId <= 0) { header('Location: agents.php'); exit; }
if ($targetId === $currentId) { header('Location: profile.php'); exit; }

// Récupérer le profil cible
$stmt = $bdd->prepare('SELECT id, prenom, nom, username, created_at FROM users WHERE id = ?');
$stmt->execute([$targetId]);
$agent = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$agent) { header('Location: agents.php'); exit; }

// Créer tables si besoin
try {
    $bdd->exec("CREATE TABLE IF NOT EXISTS suivis (
        id INT AUTO_INCREMENT PRIMARY KEY,
        follower_id INT NOT NULL,
        followed_id INT NOT NULL,
        created_at DATETIME DEFAULT NOW(),
        UNIQUE KEY unique_suivi (follower_id, followed_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $bdd->exec("CREATE TABLE IF NOT EXISTS messages_prives (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        receiver_id INT NOT NULL,
        contenu TEXT NOT NULL,
        lu TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT NOW()
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $bdd->exec("CREATE TABLE IF NOT EXISTS favoris (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        isbn VARCHAR(20) NOT NULL,
        created_at DATETIME DEFAULT NOW(),
        UNIQUE KEY unique_favori (user_id, isbn)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {}

// Stats

// Préparer stats avec PDO propre
$s1 = $bdd->prepare('SELECT COUNT(*) FROM suivis WHERE followed_id = ?');
$s1->execute([$targetId]); $nbFollowers = (int)$s1->fetchColumn();
$s2 = $bdd->prepare('SELECT COUNT(*) FROM suivis WHERE follower_id = ?');
$s2->execute([$targetId]); $nbFollowing = (int)$s2->fetchColumn();

// Est-ce que je le suis ?
$isFollowing = false;
if ($currentId) {
    $chk = $bdd->prepare('SELECT 1 FROM suivis WHERE follower_id = ? AND followed_id = ?');
    $chk->execute([$currentId, $targetId]);
    $isFollowing = (bool)$chk->fetch();
}

// Traitement AJAX : toggle follow
if (isset($_POST['ajax_follow']) && $currentId) {
    header('Content-Type: application/json');
    if ($currentId === $targetId) { echo json_encode(['error' => 'invalide']); exit; }
    $chk2 = $bdd->prepare('SELECT 1 FROM suivis WHERE follower_id = ? AND followed_id = ?');
    $chk2->execute([$currentId, $targetId]);
    if ($chk2->fetch()) {
        $bdd->prepare('DELETE FROM suivis WHERE follower_id = ? AND followed_id = ?')->execute([$currentId, $targetId]);
        echo json_encode(['status' => 'unfollowed']);
    } else {
        $bdd->prepare('INSERT IGNORE INTO suivis (follower_id, followed_id) VALUES (?, ?)')->execute([$currentId, $targetId]);
        echo json_encode(['status' => 'followed']);
    }
    exit;
}

// Traitement AJAX : envoyer message
if (isset($_POST['ajax_message']) && $currentId) {
    header('Content-Type: application/json');
    $contenu = trim($_POST['contenu'] ?? '');
    if (empty($contenu) || mb_strlen($contenu) > 1000) { echo json_encode(['error' => 'Contenu invalide']); exit; }
    $bdd->prepare('INSERT INTO messages_prives (sender_id, receiver_id, contenu) VALUES (?, ?, ?)')->execute([$currentId, $targetId, $contenu]);
    echo json_encode(['status' => 'sent', 'time' => date('H:i'), 'contenu' => htmlspecialchars($contenu)]);
    exit;
}

// Traitement AJAX : charger conversation
if (isset($_GET['ajax_conv']) && $currentId) {
    header('Content-Type: application/json');
    $bdd->prepare('UPDATE messages_prives SET lu=1 WHERE sender_id=? AND receiver_id=?')->execute([$targetId, $currentId]);
    $msgs = $bdd->prepare("SELECT m.*, u.username AS sender_name FROM messages_prives m JOIN users u ON m.sender_id=u.id
        WHERE (m.sender_id=? AND m.receiver_id=?) OR (m.sender_id=? AND m.receiver_id=?)
        ORDER BY m.created_at ASC LIMIT 100");
    $msgs->execute([$currentId, $targetId, $targetId, $currentId]);
    echo json_encode($msgs->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// Favoris de l'agent
$favoris = [];
try {
    $cols = $bdd->query("SHOW COLUMNS FROM review")->fetchAll(PDO::FETCH_COLUMN);
} catch(Exception $e) { $cols = []; }
try {
    $favStmt = $bdd->prepare("
        SELECT f.isbn, l.titre, l.image, l.annee,
               GROUP_CONCAT(CONCAT_WS(' ', p.prenom, p.nom) SEPARATOR ' • ') AS auteur
        FROM favoris f
        LEFT JOIN Livre l ON f.isbn = l.isbn
        LEFT JOIN Auteur a ON l.isbn = a.idLivre
        LEFT JOIN Personne p ON a.idPersonne = p.id
        WHERE f.user_id = ?
        GROUP BY f.isbn, l.titre, l.image, l.annee
        ORDER BY f.created_at DESC
    ");
    $favStmt->execute([$targetId]);
    $favoris = $favStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $favoris = []; }

$avatarSrc = 'https://ui-avatars.com/api/?name=' . urlencode(($agent['prenom'] ?? '') . '+' . ($agent['nom'] ?? '')) . '&background=c9a96e&color=fff&size=200&bold=true';

require 'header.php';
?>
<style>
.ap-wrap { max-width:1060px; margin:0 auto; padding:36px 20px 80px; display:flex; gap:22px; align-items:flex-start; }
.ap-main { flex:1; min-width:0; }
.ap-chat { width:300px; flex-shrink:0; background:var(--card); border-radius:12px; box-shadow:var(--shadow); overflow:hidden; display:flex; flex-direction:column; height:520px; position:sticky; top:80px; }

/* Hero card */
.ap-hero { background:var(--card); border-radius:12px; padding:28px 30px; box-shadow:var(--shadow); margin-bottom:20px; display:flex; align-items:center; gap:22px; flex-wrap:wrap; border-top:4px solid var(--accent); }
.ap-avatar { width:80px; height:80px; border-radius:50%; object-fit:cover; border:3px solid #c9a96e; flex-shrink:0; }
.ap-name { font-size:1.4rem; font-weight:800; color:var(--accent); margin-bottom:4px; }
.ap-pseudo { font-size:0.85rem; color:#a8834a; font-weight:700; margin-bottom:8px; }
.ap-bio { font-size:0.85rem; color:var(--muted); font-style:italic; line-height:1.6; }
.ap-actions { display:flex; gap:9px; flex-wrap:wrap; margin-top:14px; }
.ap-btn { display:inline-flex; align-items:center; gap:6px; padding:9px 18px; border-radius:8px; font-size:0.85rem; font-weight:700; cursor:pointer; border:none; font-family:inherit; transition:all .2s; }
.ap-btn-follow  { background:var(--accent); color:white; }
.ap-btn-follow:hover  { opacity:.88; }
.ap-btn-unfollow { background:transparent; border:1.5px solid rgba(132,106,83,0.3) !important; color:var(--muted); }
.ap-btn-unfollow:hover { border-color:var(--accent) !important; color:var(--accent); }
.ap-btn-msg { background:rgba(132,106,83,0.1); border:1.5px solid rgba(132,106,83,0.2) !important; color:var(--muted); }
.ap-btn-msg:hover { background:rgba(132,106,83,0.18); }

/* Stats */
.ap-stats { display:flex; gap:14px; flex-wrap:wrap; margin-bottom:20px; }
.ap-stat { background:var(--card); border-radius:10px; padding:14px 20px; box-shadow:var(--shadow); text-align:center; flex:1; min-width:100px; }
.ap-stat-val { font-size:1.5rem; font-weight:800; color:#a8834a; }
.ap-stat-label { font-size:0.7rem; color:var(--muted); text-transform:uppercase; letter-spacing:.07em; margin-top:2px; }

/* Favoris */
.ap-card { background:var(--card); border-radius:12px; box-shadow:var(--shadow); overflow:hidden; margin-bottom:20px; }
.ap-card-head { padding:14px 20px; border-bottom:1px solid rgba(132,106,83,0.08); display:flex; align-items:center; gap:9px; background:rgba(132,106,83,0.03); }
.ap-card-head h2 { font-size:0.95rem; font-weight:700; color:var(--accent); }
.ap-card-body { padding:18px 20px; }
.fav-row { display:flex; align-items:center; gap:12px; padding:9px 10px; border-radius:8px; border:1px solid rgba(132,106,83,0.08); margin-bottom:8px; background:rgba(132,106,83,0.02); transition:background .15s; }
.fav-row:hover { background:rgba(132,106,83,0.06); }

/* Chat */
.chat-header { padding:14px 16px; background:var(--accent); color:white; font-weight:700; font-size:0.9rem; }
.chat-msgs { flex:1; overflow-y:auto; padding:12px; display:flex; flex-direction:column; gap:7px; background:var(--bg); }
.chat-input-row { padding:9px 10px; border-top:1px solid rgba(132,106,83,0.12); display:flex; gap:7px; }
.chat-input { flex:1; padding:7px 12px; border:1.5px solid rgba(132,106,83,0.18); border-radius:20px; background:var(--card); color:var(--accent); font-size:0.83rem; outline:none; font-family:inherit; }
.chat-send { background:var(--accent); color:white; border:none; border-radius:50%; width:32px; height:32px; cursor:pointer; font-size:0.9rem; flex-shrink:0; }
.bubble { max-width:82%; padding:7px 11px; border-radius:10px; font-size:0.83rem; line-height:1.45; word-break:break-word; }
.bubble-me { background:var(--accent); color:white; align-self:flex-end; border-radius:10px 10px 2px 10px; }
.bubble-other { background:var(--card); color:var(--accent); align-self:flex-start; border-radius:10px 10px 10px 2px; box-shadow:0 1px 4px rgba(0,0,0,0.06); }
.bubble-time { font-size:0.62rem; opacity:.6; margin-top:3px; text-align:right; }

@media(max-width:768px) { .ap-wrap { flex-direction:column; } .ap-chat { width:100%; position:relative; top:0; } }
</style>

<div class="ap-wrap">
  <div class="ap-main">

    <!-- Retour -->
    <a href="agents.php" style="display:inline-flex;align-items:center;gap:5px;color:var(--muted);font-size:0.85rem;margin-bottom:18px;text-decoration:none;">← Retour aux agents</a>

    <!-- Hero -->
    <div class="ap-hero">
      <img src="<?= $avatarSrc ?>" alt="" class="ap-avatar">
      <div style="flex:1;min-width:0;">
        <div class="ap-name"><?= htmlspecialchars($agent['prenom'] . ' ' . $agent['nom']) ?></div>
        <div class="ap-pseudo">@<?= htmlspecialchars($agent['username']) ?></div>
        <?php if (!empty($agent['bio'] ?? '')): ?>
        <div class="ap-bio"><?= htmlspecialchars($agent['bio'] ?? '') ?></div>
        <?php endif; ?>
        <?php if ($currentId): ?>
        <div class="ap-actions">
          <button id="followBtn" class="ap-btn <?= $isFollowing ? 'ap-btn-unfollow' : 'ap-btn-follow' ?>"
                  style="border:none;" onclick="toggleFollow()"
                  data-following="<?= $isFollowing ? '1' : '0' ?>">
            <?= $isFollowing ? '✓ Suivi' : '+ Suivre' ?>
          </button>
          <button class="ap-btn ap-btn-msg" style="border:1.5px solid rgba(132,106,83,0.2);"
                  onclick="document.getElementById('chatInput').focus()">
            💬 Message
          </button>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Stats -->
    <div class="ap-stats">
      <div class="ap-stat">
        <div class="ap-stat-val" id="nbFollowers"><?= $nbFollowers ?></div>
        <div class="ap-stat-label">Abonnés</div>
      </div>
      <div class="ap-stat">
        <div class="ap-stat-val"><?= $nbFollowing ?></div>
        <div class="ap-stat-label">Abonnements</div>
      </div>
      <div class="ap-stat">
        <div class="ap-stat-val"><?= count($favoris) ?></div>
        <div class="ap-stat-label">Favoris</div>
      </div>
    </div>

    <!-- Favoris -->
    <div class="ap-card">
      <div class="ap-card-head"><span>❤️</span><h2>Livres favoris</h2></div>
      <div class="ap-card-body">
        <?php if (empty($favoris)): ?>
          <p style="color:var(--muted);font-size:0.88rem;">Cet agent n'a pas encore de favoris.</p>
        <?php else: ?>
          <div style="margin-bottom:12px;">
            <input type="text" id="favSearch" placeholder="🔍 Filtrer les favoris..."
                   oninput="filterFavs(this.value)"
                   style="width:100%;max-width:340px;padding:8px 12px;border:1.5px solid rgba(132,106,83,0.18);border-radius:8px;font-size:0.85rem;color:var(--accent);background:var(--bg);outline:none;">
          </div>
          <div id="favList">
            <?php foreach ($favoris as $fav): ?>
            <div class="fav-row"
                 data-titre="<?= htmlspecialchars(mb_strtolower($fav['titre'] ?? '')) ?>"
                 data-auteur="<?= htmlspecialchars(mb_strtolower($fav['auteur'] ?? '')) ?>">
              <?php if (!empty($fav['image'])): ?>
              <img src="<?= htmlspecialchars($fav['image']) ?>" alt=""
                   style="width:38px;height:54px;object-fit:cover;border-radius:4px;flex-shrink:0;">
              <?php else: ?>
              <div style="width:38px;height:54px;background:rgba(132,106,83,0.1);border-radius:4px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:1.2rem;">📖</div>
              <?php endif; ?>
              <div style="flex:1;min-width:0;">
                <a href="book-page.php?isbn=<?= urlencode($fav['isbn']) ?>"
                   style="font-weight:700;font-size:0.88rem;color:var(--accent);text-decoration:none;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                  <?= htmlspecialchars($fav['titre'] ?? 'Titre inconnu') ?>
                </a>
                <p style="font-size:0.78rem;color:var(--muted);margin-top:1px;"><?= htmlspecialchars($fav['auteur'] ?? '') ?></p>
                <?php if (!empty($fav['annee'])): ?><p style="font-size:0.72rem;color:var(--muted);margin-top:1px;"><?= htmlspecialchars($fav['annee']) ?></p><?php endif; ?>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <div id="favNoResult" style="display:none;color:var(--muted);font-size:0.85rem;padding:10px 0;">Aucun résultat.</div>
        <?php endif; ?>
      </div>
    </div>

  </div><!-- /ap-main -->

  <!-- Chat -->
  <?php if ($currentId): ?>
  <div class="ap-chat">
    <div class="chat-header">💬 @<?= htmlspecialchars($agent['username']) ?></div>
    <div class="chat-msgs" id="chatMsgs">
      <div style="text-align:center;color:var(--muted);font-size:0.8rem;padding:20px 0;" id="chatPlaceholder">
        Chargement…
      </div>
    </div>
    <div class="chat-input-row">
      <input id="chatInput" class="chat-input" type="text" placeholder="Votre message…" maxlength="1000"
             onkeydown="if(event.key==='Enter')sendMsg()">
      <button class="chat-send" onclick="sendMsg()">➤</button>
    </div>
  </div>
  <?php endif; ?>

</div>

<script>
const currentUserId = <?= $currentId ?>;
const targetUserId  = <?= $targetId ?>;

// ── Charger conversation au chargement ───────────────────────
<?php if ($currentId): ?>
document.addEventListener('DOMContentLoaded', loadMsgs);
function loadMsgs() {
    fetch('agent_profile.php?id=<?= $targetId ?>&ajax_conv=1')
        .then(r => r.json())
        .then(msgs => {
            const box = document.getElementById('chatMsgs');
            document.getElementById('chatPlaceholder')?.remove();
            if (msgs.length === 0) {
                box.innerHTML = '<div style="text-align:center;color:var(--muted);font-size:0.8rem;padding:20px 0;">Démarrez la conversation !</div>';
            } else {
                msgs.forEach(m => appendBubble(m, m.sender_id == currentUserId));
            }
            box.scrollTop = box.scrollHeight;
        });
}

function appendBubble(m, isMine) {
    const box  = document.getElementById('chatMsgs');
    const time = m.time || (m.created_at ? new Date(m.created_at).toLocaleTimeString('fr-FR',{hour:'2-digit',minute:'2-digit'}) : '');
    const div  = document.createElement('div');
    div.className = 'bubble ' + (isMine ? 'bubble-me' : 'bubble-other');
    div.innerHTML = `<div>${escHtml(m.contenu || '')}</div><div class="bubble-time">${time}</div>`;
    box.appendChild(div);
}

function sendMsg() {
    const inp = document.getElementById('chatInput');
    const msg = inp.value.trim();
    if (!msg) return;
    inp.value = '';
    const fd = new FormData();
    fd.append('ajax_message', '1');
    fd.append('contenu', msg);
    fetch('agent_profile.php?id=<?= $targetId ?>', { method:'POST', body:fd })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'sent') {
                appendBubble({ contenu: data.contenu, time: data.time }, true);
                const box = document.getElementById('chatMsgs');
                box.scrollTop = box.scrollHeight;
            }
        });
}
<?php endif; ?>

// ── Suivre / ne plus suivre ────────────────────────────────────
function toggleFollow() {
    if (!currentUserId) { window.location.href = 'login.php'; return; }
    const btn = document.getElementById('followBtn');
    const fd  = new FormData();
    fd.append('ajax_follow', '1');
    fetch('agent_profile.php?id=<?= $targetId ?>', { method:'POST', body:fd })
        .then(r => r.json())
        .then(data => {
            const nbEl = document.getElementById('nbFollowers');
            if (data.status === 'followed') {
                btn.dataset.following = '1';
                btn.textContent = '✓ Suivi';
                btn.className = 'ap-btn ap-btn-unfollow';
                btn.style.border = '1.5px solid rgba(132,106,83,0.3)';
                if (nbEl) nbEl.textContent = parseInt(nbEl.textContent || 0) + 1;
            } else {
                btn.dataset.following = '0';
                btn.textContent = '+ Suivre';
                btn.className = 'ap-btn ap-btn-follow';
                btn.style.border = 'none';
                if (nbEl) nbEl.textContent = Math.max(0, parseInt(nbEl.textContent || 1) - 1);
            }
        });
}

// ── Filtrer favoris ────────────────────────────────────────────
function normalizeStr(s) {
    return String(s).replace(/[\u2018\u2019\u201A\u201B]/g,"'").normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase();
}
function filterFavs(q) {
    const query = normalizeStr(q.trim());
    let visible = 0;
    document.querySelectorAll('.fav-row').forEach(el => {
        const t = normalizeStr((el.dataset.titre||'') + ' ' + (el.dataset.auteur||''));
        const show = !query || t.includes(query);
        el.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    const nr = document.getElementById('favNoResult');
    if (nr) nr.style.display = (visible===0 && query) ? 'block' : 'none';
}

function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
</script>

<?php require 'footer.php'; ?>
