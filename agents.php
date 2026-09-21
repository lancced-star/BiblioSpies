<?php
require_once 'connexion-bdd.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$currentUserId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

// ─── Créer les tables nécessaires ────────────────────────────────
try {
    $bdd->exec("CREATE TABLE IF NOT EXISTS messages_prives (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        receiver_id INT NOT NULL,
        contenu TEXT NOT NULL,
        lu TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT NOW()
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $bdd->exec("CREATE TABLE IF NOT EXISTS suivis (
        id INT AUTO_INCREMENT PRIMARY KEY,
        follower_id INT NOT NULL,
        followed_id INT NOT NULL,
        created_at DATETIME DEFAULT NOW(),
        UNIQUE KEY unique_suivi (follower_id, followed_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $bdd->exec("CREATE TABLE IF NOT EXISTS favoris (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        isbn VARCHAR(20) NOT NULL,
        created_at DATETIME DEFAULT NOW(),
        UNIQUE KEY unique_favori (user_id, isbn)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {}

// ─── Traitement AJAX : envoyer message ──────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_message'])) {
    header('Content-Type: application/json');
    if (!$currentUserId) { echo json_encode(['error' => 'Non connecté']); exit; }
    $receiverId = (int)($_POST['receiver_id'] ?? 0);
    $contenu    = trim($_POST['contenu'] ?? '');
    if ($receiverId <= 0 || $contenu === '') { echo json_encode(['error' => 'Données manquantes']); exit; }
    if ($receiverId === $currentUserId) { echo json_encode(['error' => 'Impossible']); exit; }
    $stmt = $bdd->prepare('INSERT INTO messages_prives (sender_id, receiver_id, contenu) VALUES (?, ?, ?)');
    $stmt->execute([$currentUserId, $receiverId, $contenu]);
    echo json_encode(['status' => 'sent', 'id' => $bdd->lastInsertId(), 'contenu' => htmlspecialchars($contenu), 'time' => date('H:i')]);
    exit;
}

// ─── Traitement AJAX : charger conversation ─────────────────────
if (isset($_GET['ajax_conv']) && $currentUserId) {
    header('Content-Type: application/json');
    $otherId = (int)$_GET['ajax_conv'];
    // Marquer comme lus
    $bdd->prepare('UPDATE messages_prives SET lu = 1 WHERE sender_id = ? AND receiver_id = ?')
        ->execute([$otherId, $currentUserId]);
    $msgs = $bdd->prepare("
        SELECT m.*        
        FROM messages_prives m
        JOIN users u ON m.sender_id = u.id
        WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)
        ORDER BY m.created_at ASC LIMIT 100
    ");
    $msgs->execute([$currentUserId, $otherId, $otherId, $currentUserId]);
    echo json_encode($msgs->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// ─── Traitement AJAX : suivre / ne plus suivre ──────────────────
if (isset($_POST['ajax_follow']) && $currentUserId) {
    header('Content-Type: application/json');
    $targetId = (int)($_POST['target_id'] ?? 0);
    if ($targetId <= 0 || $targetId === $currentUserId) { echo json_encode(['error' => 'Invalide']); exit; }
    $chk = $bdd->prepare('SELECT 1 FROM suivis WHERE follower_id = ? AND followed_id = ?');
    $chk->execute([$currentUserId, $targetId]);
    if ($chk->fetch()) {
        $bdd->prepare('DELETE FROM suivis WHERE follower_id = ? AND followed_id = ?')->execute([$currentUserId, $targetId]);
        echo json_encode(['status' => 'unfollowed']);
    } else {
        $bdd->prepare('INSERT IGNORE INTO suivis (follower_id, followed_id) VALUES (?, ?)')->execute([$currentUserId, $targetId]);
        echo json_encode(['status' => 'followed']);
    }
    exit;
}

// ─── Recherche d'agents ─────────────────────────────────────────
$search      = trim($_GET['q']     ?? '');
$filterGenre = trim($_GET['genre'] ?? '');
$agents      = [];

// Activer l'émulation PDO pour que les paramètres nommés répétés fonctionnent
$bdd->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

if ($search !== '' || $filterGenre !== '') {
    try {
        $like = '%' . $search . '%';
        $genreLike = '%' . $filterGenre . '%';

        if ($filterGenre !== '' && $search !== '') {
            // Genre + texte
            $stmt = $bdd->prepare("
                SELECT DISTINCT u.id, u.username, u.prenom, u.nom,
                    (SELECT COUNT(*) FROM suivis sx WHERE sx.followed_id = u.id) AS nb_abonnes
                FROM users u
                JOIN favoris f  ON f.user_id = u.id
                JOIN Livre   l  ON f.isbn = l.isbn
                JOIN Genre   g  ON l.isbn = g.idLivre
                JOIN TypeGenre tg ON g.idTypeGenre = tg.id
                WHERE tg.libelle LIKE ?
                AND (u.username LIKE ? OR u.prenom LIKE ? OR u.nom LIKE ?)
                ORDER BY nb_abonnes DESC LIMIT 30");
            $stmt->execute([$genreLike, $like, $like, $like]);

        } elseif ($filterGenre !== '') {
            // Genre seulement
            $stmt = $bdd->prepare("
                SELECT DISTINCT u.id, u.username, u.prenom, u.nom,
                    (SELECT COUNT(*) FROM suivis sx WHERE sx.followed_id = u.id) AS nb_abonnes
                FROM users u
                JOIN favoris f  ON f.user_id = u.id
                JOIN Livre   l  ON f.isbn = l.isbn
                JOIN Genre   g  ON l.isbn = g.idLivre
                JOIN TypeGenre tg ON g.idTypeGenre = tg.id
                WHERE tg.libelle LIKE ?
                ORDER BY nb_abonnes DESC LIMIT 30");
            $stmt->execute([$genreLike]);

        } else {
            // Texte seulement — recherche insensible à la casse et aux accents
            $stmt = $bdd->prepare("
                SELECT u.id, u.username, u.prenom, u.nom,
                    (SELECT COUNT(*) FROM suivis sx WHERE sx.followed_id = u.id) AS nb_abonnes
                FROM users u
                WHERE (u.username LIKE ? OR u.prenom LIKE ? OR u.nom LIKE ?)
                ORDER BY nb_abonnes DESC LIMIT 30");
            $stmt->execute([$like, $like, $like]);
        }

        $agents = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Exclure l'utilisateur courant côté PHP (plus simple et fiable)
        if ($currentUserId) {
            $agents = array_filter($agents, fn($a) => (int)$a['id'] !== $currentUserId);
            $agents = array_values($agents);
        }

    } catch (Exception $e) {
        $agents = [];
        $searchError = $e->getMessage();
    }
}

// ─── Liste genres pour filtre ───────────────────────────────────
$genres = [];
try {
    $genres = $bdd->query("SELECT DISTINCT libelle FROM TypeGenre ORDER BY libelle")->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) { $genres = []; }

// ─── Agents suivis (pour liste conversations) ───────────────────
$suivis = [];
$conversationsData = [];
if ($currentUserId) {
    try {
        $suiviStmt = $bdd->prepare("
            SELECT u.id, u.username, u.prenom, u.nom,
                (SELECT COUNT(*) FROM messages_prives m
                 WHERE m.sender_id = u.id AND m.receiver_id = :uid AND m.lu = 0) AS unread
            FROM suivis s
            JOIN users u ON s.followed_id = u.id
            WHERE s.follower_id = :uid
            ORDER BY u.username
        ");
        $suiviStmt->execute([':uid' => $currentUserId]);
        $suivis = $suiviStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { $suivis = []; }

    // Agents qui nous suivent aussi (conversations mutuelles)
    try {
        $convStmt = $bdd->prepare("
            SELECT DISTINCT u.id, u.username, u.prenom, u.nom,
                (SELECT COUNT(*) FROM messages_prives m
                 WHERE m.sender_id = u.id AND m.receiver_id = :uid AND m.lu = 0) AS unread,
                (SELECT MAX(m2.created_at) FROM messages_prives m2
                 WHERE (m2.sender_id = u.id AND m2.receiver_id = :uid2)
                    OR (m2.sender_id = :uid3 AND m2.receiver_id = u.id)) AS last_msg
            FROM messages_prives mp
            JOIN users u ON (mp.sender_id = u.id OR mp.receiver_id = u.id)
            WHERE (mp.sender_id = :uid4 OR mp.receiver_id = :uid5)
            AND u.id != :uid6
            ORDER BY last_msg DESC
            LIMIT 20
        ");
        $convStmt->execute([':uid' => $currentUserId, ':uid2' => $currentUserId, ':uid3' => $currentUserId, ':uid4' => $currentUserId, ':uid5' => $currentUserId, ':uid6' => $currentUserId]);
        $conversationsData = $convStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { $conversationsData = []; }

    // Qui est-ce qu'on suit (IDs) pour afficher bouton correct
    try {
        $mySuivisStmt = $bdd->prepare('SELECT followed_id FROM suivis WHERE follower_id = ?');
        $mySuivisStmt->execute([$currentUserId]);
        $mySuivisIds = array_column($mySuivisStmt->fetchAll(PDO::FETCH_ASSOC), 'followed_id');
    } catch (Exception $e) { $mySuivisIds = []; }
} else {
    $mySuivisIds = [];
}

require 'header.php';
?>

<main style="max-width:1100px;margin:0 auto;padding:32px 20px 80px;display:flex;gap:24px;align-items:flex-start;">

  <!-- ══ COLONNE PRINCIPALE ══ -->
  <div style="flex:1;min-width:0;">

    <div style="margin-bottom:28px;">
      <h1 style="color:var(--accent);font-size:1.8rem;margin-bottom:6px;">🕵️ Agents</h1>
      <p style="color:var(--muted);font-size:0.9rem;">Recherchez un agent, suivez-le et discutez avec lui.</p>
    </div>

    <!-- Barre de recherche + filtres -->
    <form method="GET" action="agents.php" style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:28px;">
      <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
             placeholder="🔍 Rechercher par pseudo, prénom ou nom..."
             style="flex:1;min-width:200px;padding:11px 16px;border:1.5px solid rgba(132,106,83,0.25);border-radius:8px;background:var(--accent-5); color: var(--accent);font-size:0.9rem;outline:none;">
      <?php if (!empty($genres)): ?>
      <select name="genre"
              style="padding:11px 14px;border:1.5px solid rgba(132,106,83,0.25);border-radius:8px;background:var(--card);color:var(--muted);font-size:0.9rem;outline:none;">
        <option value="">Tous les genres</option>
        <?php foreach ($genres as $g): ?>
        <option value="<?= htmlspecialchars($g) ?>" <?= $filterGenre === $g ? 'selected' : '' ?>><?= htmlspecialchars($g) ?></option>
        <?php endforeach; ?>
      </select>
      <?php endif; ?>
      <button type="submit"
              style="padding:11px 24px;background: var(--accent-3); font-size:0.9rem;color: white;cursor: pointer;border-radius:8px;font-weight:600;">
        Rechercher
      </button>

      <?php if ($search || $filterGenre): ?>
      <a href="agents.php" style="padding:11px 16px;border:1.5px solid rgba(0, 0, 0, 0.25);border-radius:8px;color:var(--accent);font-size:0.9rem;display:flex;align-items:center;">✕ Effacer</a>
      <?php endif; ?>
    </form>

    <!-- Résultats -->
    <?php if ($search !== '' || $filterGenre !== ''): ?>
      <?php if (empty($agents)): ?>
        <div style="text-align:center;padding:60px;background:var(--card);border-radius:12px;color:var(--muted);">
          <p style="font-size:1.1rem;">Aucun agent trouvé.</p>
          <?php if (!empty($searchError)): ?>
          <p style="font-size:0.8rem;color:#c0392b;margin-top:10px;"><?= htmlspecialchars($searchError) ?></p>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:14px;">
          <?php foreach ($agents as $agent):
            $isFollowing = in_array($agent['id'], $mySuivisIds);
            $agAvatar = 'https://ui-avatars.com/api/?name=' . urlencode(($agent['prenom'] ?? '') . '+' . ($agent['nom'] ?? '')) . '&background=c9a96e&color=fff&size=80&bold=true';
          ?>
          <div style="background:var(--card);border-radius:12px;padding:18px 22px;box-shadow:var(--shadow);display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
            <a href="agent_profile.php?id=<?= $agent['id'] ?>" style="flex-shrink:0;">
              <img src="<?= $agAvatar ?>" alt="<?= htmlspecialchars($agent['username']) ?>"
                   style="width:54px;height:54px;border-radius:50%;object-fit:cover;border:2px solid rgba(132,106,83,0.2);display:block;">
            </a>
            <div style="flex:1;min-width:0;">
              <a href="agent_profile.php?id=<?= $agent['id'] ?>" style="font-weight:700;color:var(--accent);font-size:1rem;text-decoration:none;">@<?= htmlspecialchars($agent['username']) ?></a>
              <div style="color:var(--muted);font-size:0.88rem;margin-top:2px;"><?= htmlspecialchars($agent['prenom'] . ' ' . $agent['nom']) ?></div>
              <?php if (!empty($agent['bio'])): ?>
              <div style="color:var(--muted);font-size:0.82rem;margin-top:4px;font-style:italic;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:400px;"><?= htmlspecialchars($agent['bio']) ?></div>
              <?php endif; ?>
              <div style="color:var(--muted);font-size:0.78rem;margin-top:4px;">👥 <?= (int)$agent['nb_abonnes'] ?> abonné(s)</div>
            </div>
            <div style="display:flex;gap:8px;flex-shrink:0;">
              <?php if ($currentUserId && $currentUserId !== (int)$agent['id']): ?>
              <button onclick="toggleFollow(<?= $agent['id'] ?>, this)"
                      data-following="<?= $isFollowing ? '1' : '0' ?>"
                      style="padding:8px 16px;border-radius:8px;font-size:0.85rem;font-weight:600;cursor:pointer;
                             background:<?= $isFollowing ? 'transparent' : 'var(--accent)' ?>;
                             color:<?= $isFollowing ? 'var(--muted)' : 'white' ?>;
                             border:<?= $isFollowing ? '1.5px solid rgba(132,106,83,0.3)' : 'none' ?>;">
                <?= $isFollowing ? '✓ Suivi' : '+ Suivre' ?>
              </button>
              <a href="agent_profile.php?id=<?= $agent['id'] ?>"
                 style="display:inline-flex;align-items:center;gap:5px;padding:7px 14px;border-radius:7px;border:1.5px solid rgba(132,106,83,0.22);color:var(--muted);font-size:0.8rem;font-weight:600;text-decoration:none;transition:all .2s;"
                 onmouseover="this.style.borderColor='var(--accent)';this.style.color='var(--accent)'"
                 onmouseout="this.style.borderColor='rgba(132,106,83,0.22)';this.style.color='var(--muted)'">
                👤 Profil
              </a>
              <button onclick="openChat(<?= $agent['id'] ?>, '<?= addslashes($agent['username']) ?>')"
                      style="padding:8px 16px;border-radius:8px;font-size:0.85rem;font-weight:600;cursor:pointer;background:rgba(132,106,83,0.1);border:1.5px solid rgba(132,106,83,0.2);color:var(--muted);">
                💬 Message
              </button>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    <?php else: ?>
      <div style="text-align:center;padding:60px 40px;background:var(--card);border-radius:12px;color:var(--muted);">
        <div style="font-size:3rem;margin-bottom:16px;">🔍</div>
        <p style="font-size:1rem;">Entrez un pseudo, prénom ou nom pour trouver un agent.</p>
        <p style="font-size:0.88rem;margin-top:8px;opacity:0.7;">Vous pouvez aussi filtrer par genre de livre favori.</p>
      </div>
    <?php endif; ?>
  </div>

  <!-- ══ PANNEAU DE MESSAGES ══ -->
  <?php if ($currentUserId): ?>
  <div id="chatPanel" style="width:320px;flex-shrink:0;background:var(--card);border-radius:12px;box-shadow:var(--shadow);overflow:hidden;display:flex;flex-direction:column;height:600px;position:sticky;top:20px;">
    
    <!-- Header -->
    <div style="padding:16px 18px;background:var(--accent-4);color:white;display:flex;align-items:center;justify-content:space-between;">
      <a id="chatTitle" href="agents.php"
        style="font-weight:700;font-size:0.95rem;color:white;text-decoration:none;">💬 Messages</a>
      <button id="chatBackBtn" onclick="showConvList()" style="display:none;background:none;border:none;color:white;cursor:pointer;font-size:0.8rem;">← Retour</button>
    </div>

    <!-- Liste conversations -->
    <div id="convListPanel" style="flex:1;overflow-y:auto;padding:8px 0;">
      <?php if (empty($conversationsData) && empty($suivis)): ?>
        <div style="text-align:center;padding:30px 16px;color:var(--muted);font-size:0.85rem;">
          Suivez des agents pour leur envoyer des messages !
        </div>
      <?php else: ?>
        <!-- Conversations existantes -->
        <?php foreach ($conversationsData as $conv):
          $convAvatar = 'https://ui-avatars.com/api/?name=' . urlencode($conv['username'] ?? '') . '&background=846a53&color=fff&size=60';
        ?>
        <div style="display:flex;align-items:center;gap:10px;padding:10px 14px;border-bottom:1px solid rgba(132,106,83,0.08);"
             onmouseover="this.style.background='var(--bg)'" onmouseout="this.style.background=''">
          <div onclick="openChat(<?= $conv['id'] ?>, '<?= addslashes($conv['username']) ?>')" style="display:flex;align-items:center;gap:10px;flex:1;cursor:pointer;min-width:0;">
            <img src="<?= $convAvatar ?>" style="width:36px;height:36px;border-radius:50%;object-fit:cover;flex-shrink:0;">
            <div style="flex:1;min-width:0;">
              <div style="font-weight:600;font-size:0.88rem;color:var(--accent);">@<?= htmlspecialchars($conv['username']) ?></div>
            </div>
          </div>
          <?php if ($conv['unread'] > 0): ?>
          <span style="background:var(--accent-2);color:white;border-radius:50%;width:20px;height:20px;font-size:0.7rem;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><?= $conv['unread'] ?></span>
          <?php endif; ?>
          <a href="agent_profile.php?id=<?= $conv['id'] ?>" title="Voir le profil"
             style="flex-shrink:0;width:28px;height:28px;border-radius:50%;background:rgba(132,106,83,0.1);display:flex;align-items:center;justify-content:center;text-decoration:none;font-size:0.75rem;transition:background .15s;"
             onmouseover="this.style.background='rgba(132,106,83,0.22)'" onmouseout="this.style.background='rgba(132,106,83,0.1)'">👤</a>
        </div>
        <?php endforeach; ?>

        <!-- Agents suivis sans conv -->
        <?php foreach ($suivis as $suivi):
          $already = array_filter($conversationsData, fn($c) => $c['id'] == $suivi['id']);
          if (!empty($already)) continue;
          $suiviAvatar = 'https://ui-avatars.com/api/?name=' . urlencode($suivi['username'] ?? '') . '&background=846a53&color=fff&size=60';
        ?>
        <div style="display:flex;align-items:center;gap:10px;padding:10px 14px;border-bottom:1px solid rgba(132,106,83,0.08);"
             onmouseover="this.style.background='var(--bg)'" onmouseout="this.style.background=''">
          <div onclick="openChat(<?= $suivi['id'] ?>, '<?= addslashes($suivi['username']) ?>')" style="display:flex;align-items:center;gap:10px;flex:1;cursor:pointer;min-width:0;">
            <img src="<?= $suiviAvatar ?>" style="width:36px;height:36px;border-radius:50%;object-fit:cover;flex-shrink:0;">
            <div style="flex:1;min-width:0;">
              <div style="font-weight:600;font-size:0.88rem;color:var(--accent);">@<?= htmlspecialchars($suivi['username']) ?></div>
            </div>
          </div>
          <a href="agent_profile.php?id=<?= $suivi['id'] ?>" title="Voir le profil"
             style="flex-shrink:0;width:28px;height:28px;border-radius:50%;background:rgba(132,106,83,0.1);display:flex;align-items:center;justify-content:center;text-decoration:none;font-size:0.75rem;transition:background .15s;"
             onmouseover="this.style.background='rgba(132,106,83,0.22)'" onmouseout="this.style.background='rgba(132,106,83,0.1)'">👤</a>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Vue conversation -->
    <div id="convView" style="display:none;flex-direction:column;flex:1;overflow:hidden;">
      <div id="msgList" style="flex:1;overflow-y:auto;padding:12px;display:flex;flex-direction:column;gap:8px;background:var(--bg);"></div>
      <div style="padding:10px 12px;border-top:1px solid rgba(132,106,83,0.15);display:flex;gap:8px;">
        <input id="msgInput" type="text" placeholder="Votre message..." maxlength="1000"
               style="flex:1;padding:8px 12px;border:1.5px solid rgba(132,106,83,0.2);border-radius:20px;background:var(--card);color:var(--accent);font-size:0.85rem;outline:none;"
               onkeydown="if(event.key==='Enter')sendMsg()">
        <button onclick="sendMsg()" style="background:var(--accent);color:white;border:none;border-radius:50%;width:34px;height:34px;cursor:pointer;font-size:1rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;">➤</button>
      </div>
    </div>

  </div>
  <?php endif; ?>

</main>

<script>
const currentUserId = <?= $currentUserId ?>;
let activeChatId = null;
let activeChatName = null;

function toggleFollow(userId, btn) {
    if (!currentUserId) { window.location.href = 'login.php'; return; }
    const fd = new FormData();
    fd.append('ajax_follow', '1');
    fd.append('target_id', userId);
    fetch('agents.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'followed') {
                btn.dataset.following = '1';
                btn.textContent = '✓ Suivi';
                btn.style.background = 'transparent';
                btn.style.color = 'var(--muted)';
                btn.style.border = '1.5px solid rgba(132,106,83,0.3)';
            } else {
                btn.dataset.following = '0';
                btn.textContent = '+ Suivre';
                btn.style.background = 'var(--accent)';
                btn.style.color = 'white';
                btn.style.border = 'none';
            }
        });
}

function openChat(userId, username) {
    if (!currentUserId) { window.location.href = 'login.php'; return; }
    activeChatId = userId;
    activeChatName = username;
    const titleEl = document.getElementById('chatTitle');
    titleEl.textContent = '@' + username;
    titleEl.href = 'agent_profile.php?id=' + userId;
    titleEl.style.textDecoration = 'underline';
    titleEl.style.textDecorationColor = 'rgba(255,255,255,0.45)';
    document.getElementById('chatBackBtn').style.display = 'inline';
    document.getElementById('convListPanel').style.display = 'none';
    const cv = document.getElementById('convView');
    cv.style.display = 'flex';

    loadMessages(userId);
}

function showConvList() {
    activeChatId = null;
    const titleEl = document.getElementById('chatTitle');
    titleEl.textContent = '💬 Messages';
    titleEl.href = 'agents.php';
    titleEl.style.textDecoration = 'none';
    document.getElementById('chatBackBtn').style.display = 'none';
    document.getElementById('convListPanel').style.display = 'block';
    document.getElementById('convView').style.display = 'none';
}

function loadMessages(userId) {
    fetch('agents.php?ajax_conv=' + userId)
        .then(r => r.json())
        .then(msgs => {
            const list = document.getElementById('msgList');
            list.innerHTML = '';
            if (msgs.length === 0) {
                list.innerHTML = '<div style="text-align:center;color:var(--muted);font-size:0.82rem;padding:20px;">Démarrez la conversation !</div>';
            } else {
                msgs.forEach(m => appendMsg(m, parseInt(m.sender_id) === currentUserId));
            }
            list.scrollTop = list.scrollHeight;
        });
}

function appendMsg(m, isMine) {
    const list = document.getElementById('msgList');
    const bubble = document.createElement('div');
    const time = m.time || new Date(m.created_at).toLocaleTimeString('fr-FR', {hour:'2-digit',minute:'2-digit'});
    bubble.style.cssText = `
        max-width:80%; padding:8px 12px; border-radius:${isMine ? '12px 12px 2px 12px' : '12px 12px 12px 2px'};
        background:${isMine ? 'var(--accent)' : 'var(--card)'};
        color:${isMine ? 'white' : 'var(--accent)'};
        align-self:${isMine ? 'flex-end' : 'flex-start'};
        font-size:0.85rem; line-height:1.4; box-shadow:0 2px 6px rgba(0,0,0,0.05);
    `;
    bubble.innerHTML = `<div>${escapeHtml(m.contenu || m.content || '')}</div><div style="font-size:0.65rem;opacity:0.6;margin-top:3px;text-align:right;">${time}</div>`;
    list.appendChild(bubble);
}

function sendMsg() {
    if (!activeChatId) return;
    const inp = document.getElementById('msgInput');
    const msg = inp.value.trim();
    if (!msg) return;
    inp.value = '';
    const fd = new FormData();
    fd.append('ajax_message', '1');
    fd.append('receiver_id', activeChatId);
    fd.append('contenu', msg);
    fetch('agents.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'sent') {
                appendMsg({ contenu: data.contenu, sender_id: currentUserId, time: data.time }, true);
                const list = document.getElementById('msgList');
                list.scrollTop = list.scrollHeight;
            }
        });
}

function normalizeStr(s) {
    return String(s)
        .replace(/[‘’‚‛′`]/g, "'")
        .normalize('NFD').replace(/[̀-ͯ]/g, '')
        .toLowerCase();
}

function escapeHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
</script>

<?php require 'footer.php'; ?>
