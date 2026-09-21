<?php
require 'header.php';
require 'connexion-bdd.php';

if (!isset($_GET['isbn']) || empty($_GET['isbn'])) { header('Location: index.php'); exit; }

$isbn    = $_GET['isbn'];
$userId  = (int)($_SESSION['user_id'] ?? 0);
$isAdmin = !empty($_SESSION['is_admin']);

// ── Données du livre ──────────────────────────────────────────────
$stmtBook = $bdd->prepare("
    SELECT l.isbn, l.titre, l.resume, l.image, l.annee,
           GROUP_CONCAT(CONCAT_WS(' ', p.prenom, p.nom) SEPARATOR ' • ') AS auteur,
           e.libelle AS editeur, lg.libelle AS langue, l.nbpages
    FROM Livre l
    LEFT JOIN Auteur   a  ON l.isbn = a.idLivre
    LEFT JOIN Personne p  ON a.idPersonne = p.id
    LEFT JOIN Editeur  e  ON l.editeur = e.id
    LEFT JOIN Langue   lg ON l.langue  = lg.id
    WHERE l.isbn = ? GROUP BY l.isbn LIMIT 1");
$stmtBook->execute([$isbn]);
$book = $stmtBook->fetch(PDO::FETCH_ASSOC);

if (!$book) {
    echo '<main style="padding:60px;text-align:center;"><h2>Livre introuvable</h2><a href="index.php">← Retour</a></main>';
    require 'footer.php'; exit;
}

// ── Créer table avis ─────────────────────────────────────────────
try {
    $bdd->exec("CREATE TABLE IF NOT EXISTS `avis` (
        `id`         INT AUTO_INCREMENT PRIMARY KEY,
        `isbn`       VARCHAR(20) NOT NULL,
        `user_id`    INT DEFAULT NULL,
        `name`       VARCHAR(255) DEFAULT NULL,
        `rating`     TINYINT DEFAULT NULL,
        `contenu`    TEXT NOT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {}

// ── Lire les avis ────────────────────────────────────────────────
try {
    $stmtCount = $bdd->prepare("SELECT COUNT(*) FROM `avis` WHERE isbn = ?");
    $stmtCount->execute([$isbn]);
    $totalAvis = (int)$stmtCount->fetchColumn();

    $stmtAvis = $bdd->prepare("SELECT * FROM `avis` WHERE isbn = ? ORDER BY created_at DESC LIMIT 3");
    $stmtAvis->execute([$isbn]);
    $avisList = $stmtAvis->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $avisList = []; $totalAvis = 0; }

// ── L'utilisateur a-t-il déjà un avis ? ─────────────────────────
$userHasAvis = false;
if ($userId) {
    foreach ($avisList as $a) {
        if ((int)($a['user_id'] ?? 0) === $userId) { $userHasAvis = true; break; }
    }
    if (!$userHasAvis) {
        try {
            $chk = $bdd->prepare("SELECT 1 FROM `avis` WHERE isbn=? AND user_id=?");
            $chk->execute([$isbn, $userId]);
            if ($chk->fetch()) $userHasAvis = true;
        } catch (Exception $e) {}
    }
}

// ── Favori ? ─────────────────────────────────────────────────────
$isFavori = false;
if ($userId) {
    try {
        $bdd->exec("CREATE TABLE IF NOT EXISTS `favoris` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL, isbn VARCHAR(20) NOT NULL,
            created_at DATETIME DEFAULT NOW(),
            UNIQUE KEY uf (user_id, isbn)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $chkFav = $bdd->prepare("SELECT 1 FROM `favoris` WHERE user_id=? AND isbn=?");
        $chkFav->execute([$userId, $isbn]);
        $isFavori = (bool)$chkFav->fetch();
    } catch (Exception $e) {}
}

$saved   = $_GET['saved']   ?? null;
$deleted = $_GET['deleted'] ?? null;
$edited  = $_GET['edited']  ?? null;
?>

<style>
.notif { padding:11px 16px;border-radius:8px;font-size:0.88rem;margin-bottom:14px; }
.notif-ok  { background:#e8f5ef;border:1px solid #b2dfcc;color:#2e7d5e; }
.notif-err { background:#fdecea;border:1px solid #f5c6c2;color:#c0392b; }
.stars-select { display:flex;gap:4px;margin-bottom:12px; }
.stars-select input { display:none; }
.stars-select label { font-size:1.5rem;cursor:pointer;color:#ddd;transition:color .1s; }
.stars-select input:checked ~ label,
.stars-select label:hover,
.stars-select label:hover ~ label { color:#ddd; }
.stars-select label:hover,
.stars-select input:checked + label,
.stars-select label:has(~ input:checked) { color:#f6b01e; }
</style>

<main style="max-width:940px;margin:36px auto;padding:0 20px 80px;">

  <a href="index.php" style="display:inline-flex;align-items:center;gap:5px;color:var(--muted);font-size:0.85rem;margin-bottom:22px;text-decoration:none;">← Retour aux livres</a>

  <!-- ── Fiche livre ─────────────────────────────────────────── -->
  <div style="display:flex;gap:30px;align-items:flex-start;flex-wrap:wrap;background:var(--card);border-radius:14px;padding:30px;box-shadow:var(--shadow);margin-bottom:24px;">
    <?php if (!empty($book['image'])): ?>
    <div style="flex:0 0 190px;">
      <img src="<?= htmlspecialchars($book['image']) ?>" alt="<?= htmlspecialchars($book['titre']) ?>"
           style="width:100%;border-radius:10px;box-shadow:0 8px 28px rgba(0,0,0,0.12);">
    </div>
    <?php endif; ?>
    <div style="flex:1;min-width:0;">
      <h1 style="color:var(--accent);margin:0 0 8px;font-size:1.65rem;font-weight:800;"><?= htmlspecialchars($book['titre']) ?></h1>
      <p style="color:var(--muted);font-weight:600;margin-bottom:5px;"><?= htmlspecialchars($book['auteur'] ?? 'Auteur inconnu') ?>
        <?= !empty($book['annee']) ? ' — ' . htmlspecialchars($book['annee']) : '' ?>
      </p>
      <?php if (!empty($book['editeur'])): ?><p style="color:var(--muted);font-size:0.85rem;">Éditeur : <?= htmlspecialchars($book['editeur']) ?></p><?php endif; ?>
      <?php if (!empty($book['nbpages'])): ?><p style="color:var(--muted);font-size:0.85rem;"><?= htmlspecialchars($book['nbpages']) ?> pages</p><?php endif; ?>
      <?php if (!empty($book['langue'])): ?><p style="color:var(--muted);font-size:0.85rem;">Langue : <?= htmlspecialchars($book['langue']) ?></p><?php endif; ?>
      <p style="color:var(--muted);line-height:1.8;margin-top:14px;font-size:0.93rem;"><?= nl2br(htmlspecialchars($book['resume'] ?? '')) ?></p>

      <?php if ($userId): ?>
      <button id="favBtn" onclick="toggleFavori('<?= htmlspecialchars($book['isbn']) ?>')"
              style="margin-top:20px;display:inline-flex;align-items:center;gap:7px;padding:9px 18px;border-radius:8px;font-size:0.87rem;font-weight:600;cursor:pointer;transition:all .2s;
                     background:<?= $isFavori?'#c0392b':'transparent' ?>;
                     color:<?= $isFavori?'white':'var(--muted)' ?>;
                     border:1.5px solid <?= $isFavori?'#c0392b':'rgba(132,106,83,0.28)' ?>;">
        <?= $isFavori ? '❤️ Retirer des favoris' : '🤍 Ajouter aux favoris' ?>
      </button>
      <?php else: ?>
      <div style="margin-top:20px;">
        <a href="login.php" style="display:inline-flex;align-items:center;gap:6px;padding:9px 18px;border-radius:8px;border:1.5px solid rgba(132,106,83,0.2);color:var(--muted);font-size:0.87rem;text-decoration:none;">🤍 Connectez-vous pour ajouter aux favoris</a>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- ── Section Avis ───────────────────────────────────────── -->
  <div style="background:var(--card);border-radius:14px;padding:26px 30px;box-shadow:var(--shadow);">

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;flex-wrap:wrap;gap:10px;">
      <h2 style="color:var(--accent);font-size:1.15rem;font-weight:800;display:flex;align-items:center;gap:10px;">
        💬 Avis des agents
        <?php if ($totalAvis > 0): ?>
        <span style="background:rgba(132,106,83,0.1);color:var(--muted);font-size:0.73rem;font-weight:600;padding:2px 9px;border-radius:20px;"><?= $totalAvis ?></span>
        <?php endif; ?>
      </h2>
      <?php if ($totalAvis > 3): ?>
      <a href="all_reviews.php?isbn=<?= urlencode($isbn) ?>"
         style="color:#a8834a;font-size:0.85rem;font-weight:600;text-decoration:none;">
        Voir tous les avis (<?= $totalAvis ?>) →
      </a>
      <?php endif; ?>
    </div>

    <!-- Notifications -->
    <?php if ($saved==='1'):   ?><div class="notif notif-ok"  id="n1">✅ Votre avis a été enregistré.</div><?php endif; ?>
    <?php if ($saved==='0'):   ?><div class="notif notif-err" id="n2">❌ Erreur lors de l'enregistrement. Réessayez.</div><?php endif; ?>
    <?php if ($deleted==='1'): ?><div class="notif notif-ok"  id="n3">🗑️ Avis supprimé.</div><?php endif; ?>
    <?php if ($edited==='1'):  ?><div class="notif notif-ok"  id="n4">✅ Avis modifié.</div><?php endif; ?>

    <!-- Formulaire dépôt d'avis -->
    <?php if ($userId && !$userHasAvis): ?>
    <div style="margin-bottom:22px;">
      <button id="toggleAvisBtn"
              style="background:var(--accent-4);color:white;border:none;padding:9px 18px;border-radius:8px;cursor:pointer;font-size:0.87rem;font-weight:700;">
        ✍️ Déposer un avis
      </button>
      <form id="avisForm" action="save_review.php" method="POST"
            style="display:none;margin-top:14px;padding:18px;background:rgba(132,106,83,0.04);border-radius:10px;border:1px solid rgba(132,106,83,0.11);">
        <input type="hidden" name="isbn" value="<?= htmlspecialchars($isbn) ?>">

        <div style="margin-bottom:12px;">
          <label style="display:block;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--muted);margin-bottom:7px;">Note</label>
          <div class="stars-select" id="starsInput">
            <?php for ($i = 1; $i <= 5; $i++): ?>
            <input type="radio" name="rating" id="star<?= $i ?>" value="<?= $i ?>" <?= $i===3?'checked':'' ?>>
            <label for="star<?= $i ?>">★</label>
            <?php endfor; ?>
          </div>
        </div>

        <div style="margin-bottom:12px;">
          <label style="display:block;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--muted);margin-bottom:6px;">Votre avis</label>
          <textarea name="review" id="avisTextarea" rows="4" required maxlength="2000"
                    style="width:100%;padding:9px 12px;border:1.5px solid rgba(132,106,83,0.18);border-radius:8px;background:var(--accent-5);color:var(--accent);font-size:0.9rem;resize:none;outline:none;font-family:inherit;"
                    placeholder="Partagez votre ressenti sur ce livre..."></textarea>
        </div>
        <div style="display:flex;gap:8px;">
          <button type="submit" style="padding:9px 20px;background:var(--accent-4);color:white;border:none;border-radius:8px;cursor:pointer;font-size:0.87rem;font-weight:700;">Publier</button>
          <button type="button" id="cancelAvisBtn" style="padding:9px 14px;background:rgba(132,106,83,0.07);border:none;border-radius:8px;cursor:pointer;color:var(--muted);font-size:0.87rem;">Annuler</button>
        </div>
      </form>
    </div>

    <?php elseif (!$userId): ?>
    <p style="color:var(--muted);font-size:0.88rem;margin-bottom:20px;">
      <a href="login.php" style="color:#a8834a;font-weight:600;">Connectez-vous</a> pour déposer un avis.
    </p>
    <?php else: ?>
    <div style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;background:rgba(46,125,94,0.07);border-radius:8px;color:#2e7d5e;font-size:0.85rem;font-weight:600;margin-bottom:18px;">
      ✅ Vous avez déjà déposé un avis pour ce livre.
    </div>
    <?php endif; ?>

    <!-- Liste des avis (3 max) -->
    <?php if (empty($avisList)): ?>
      <p style="color:var(--muted);font-size:0.9rem;padding:16px 0;">Aucun avis pour le moment. Soyez le premier !</p>
    <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:13px;">
      <?php foreach ($avisList as $a):
        $aid     = (int)$a['id'];
        $rating  = max(1, min(5, (int)($a['rating'] ?? 3)));
        $isOwner = $userId && (int)($a['user_id'] ?? 0) === $userId;
        $canEdit = $isOwner || $isAdmin;
        $contenu = $a['contenu'] ?? '';
      ?>
      <div style="border:1px solid rgba(132,106,83,0.1);border-radius:10px;padding:15px 17px;background:rgba(132,106,83,0.02);">
        <!-- En-tête avis -->
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;flex-wrap:wrap;margin-bottom:10px;">
          <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
            <strong style="color:var(--accent);font-size:0.92rem;"><?= htmlspecialchars($a['name'] ?? 'Agent anonyme') ?></strong>
            <?php if ($isOwner): ?>
            <span style="background:rgba(201,169,110,.15);color:#a8834a;font-size:0.68rem;font-weight:700;padding:2px 8px;border-radius:20px;">Mon avis</span>
            <?php endif; ?>
            <span style="color:#f6b01e;font-size:0.9rem;"><?= str_repeat('★', $rating) ?><span style="color:#ddd;"><?= str_repeat('★', 5 - $rating) ?></span></span>
            <span style="color:var(--muted);font-size:0.76rem;"><?= date('d/m/Y', strtotime($a['created_at'])) ?></span>
          </div>
          <?php if ($canEdit): ?>
          <div style="display:flex;align-items:center;gap:6px;">
            <button onclick="toggleEditAvis(<?= $aid ?>)"
                    style="background:none;border:1px solid rgba(132,106,83,0.18);border-radius:6px;padding:4px 10px;cursor:pointer;font-size:0.75rem;color:var(--muted);">✏️ Modifier</button>
            <form method="POST" action="delete_review.php" style="margin:0;" onsubmit="return confirm('Supprimer cet avis ?')">
              <input type="hidden" name="id"   value="<?= $aid ?>">
              <input type="hidden" name="isbn" value="<?= htmlspecialchars($isbn) ?>">
              <button type="submit" style="background:none;border:1px solid rgba(192,57,43,.2);border-radius:6px;padding:4px 10px;cursor:pointer;font-size:0.75rem;color:#c0392b;">🗑️</button>
            </form>
          </div>
          <?php endif; ?>
        </div>

        <!-- Contenu -->
        <div id="content-<?= $aid ?>" style="color:var(--muted);font-size:0.9rem;line-height:1.7;">
          <?= nl2br(htmlspecialchars($contenu)) ?>
        </div>

        <!-- Formulaire édition inline -->
        <form id="edit-<?= $aid ?>" method="POST" action="edit_review.php" style="display:none;margin-top:12px;">
          <input type="hidden" name="id"   value="<?= $aid ?>">
          <input type="hidden" name="isbn" value="<?= htmlspecialchars($isbn) ?>">
          <select name="rating" style="padding:7px 11px;border:1.5px solid rgba(132,106,83,0.18);border-radius:8px;background:var(--bg);color:var(--accent);font-size:0.85rem;margin-bottom:9px;display:block;">
            <?php for ($i = 5; $i >= 1; $i--): ?>
            <option value="<?= $i ?>" <?= $rating===$i?'selected':'' ?>><?= $i ?> — <?= ['','Mauvais','Moyen','Bien','Très bien','Excellent'][$i] ?></option>
            <?php endfor; ?>
          </select>
          <textarea name="review" rows="3" required
                    style="width:100%;padding:8px 11px;border:1.5px solid rgba(132,106,83,0.18);border-radius:8px;background:var(--bg);color:var(--accent);font-size:0.88rem;resize:none;margin-bottom:9px;"><?= htmlspecialchars($contenu) ?></textarea>
          <div style="display:flex;gap:7px;">
            <button type="submit" style="padding:7px 16px;background:var(--accent);color:white;border:none;border-radius:7px;cursor:pointer;font-size:0.82rem;font-weight:700;">💾 Sauvegarder</button>
            <button type="button" onclick="toggleEditAvis(<?= $aid ?>)" style="padding:7px 12px;background:rgba(132,106,83,0.07);border:none;border-radius:7px;cursor:pointer;font-size:0.82rem;color:var(--muted);">Annuler</button>
          </div>
        </form>
      </div>
      <?php endforeach; ?>
    </div>

    <?php if ($totalAvis > 3): ?>
    <div style="text-align:center;margin-top:16px;">
      <a href="all_reviews.php?isbn=<?= urlencode($isbn) ?>"
         style="display:inline-flex;align-items:center;gap:7px;padding:10px 24px;border-radius:8px;border:1.5px solid rgba(132,106,83,0.2);color:var(--muted);font-size:0.87rem;text-decoration:none;font-weight:600;transition:all .2s;"
         onmouseover="this.style.borderColor='var(--accent)';this.style.color='var(--accent)'"
         onmouseout="this.style.borderColor='rgba(132,106,83,0.2)';this.style.color='var(--muted)'">
        📖 Voir tous les <?= $totalAvis ?> avis
      </a>
    </div>
    <?php endif; ?>
    <?php endif; ?>

  </div><!-- /avis -->
</main>

<script>
// ── Toggle formulaire avis ────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    const btn    = document.getElementById('toggleAvisBtn');
    const form   = document.getElementById('avisForm');
    const cancel = document.getElementById('cancelAvisBtn');
    if (btn && form) {
        btn.addEventListener('click', () => {
            const open = form.style.display === 'block';
            form.style.display = open ? 'none' : 'block';
            if (!open) document.getElementById('avisTextarea')?.focus();
        });
    }
    if (cancel && form) cancel.addEventListener('click', () => form.style.display = 'none');

    // Auto-hide notifications
    ['n1','n2','n3','n4'].forEach(id => {
        const el = document.getElementById(id);
        if (el) setTimeout(() => { el.style.transition = 'opacity .5s'; el.style.opacity = '0'; setTimeout(() => el.remove(), 500); }, 5000);
    });
});

// ── Toggle édition inline ────────────────────────────────────
function toggleEditAvis(id) {
    const c = document.getElementById('content-' + id);
    const f = document.getElementById('edit-'    + id);
    if (!c || !f) return;
    const showing = f.style.display === 'block';
    f.style.display = showing ? 'none'  : 'block';
    c.style.display = showing ? 'block' : 'none';
}

// ── Favori toggle ─────────────────────────────────────────────
function toggleFavori(isbn) {
    const btn = document.getElementById('favBtn');
    if (!btn) return;
    const fd = new FormData();
    fd.append('isbn', isbn);
    fetch('toggle_favoris.php', { method:'POST', body:fd, headers:{'X-Requested-With':'XMLHttpRequest'} })
        .then(r => r.json())
        .then(data => {
            if (data.auth === false) { window.location.href = 'login.php'; return; }
            if (data.status === 'added') {
                btn.innerHTML = '❤️ Retirer des favoris';
                btn.style.cssText += ';background:#c0392b;color:white;border-color:#c0392b';
            } else {
                btn.innerHTML = '🤍 Ajouter aux favoris';
                btn.style.cssText += ';background:transparent;color:var(--muted);border-color:rgba(132,106,83,0.28)';
            }
        }).catch(() => {});
}

document.querySelectorAll('.stars-select').forEach(wrapper => {
  const inputs = wrapper.querySelectorAll('input[type="radio"]');
  const labels = wrapper.querySelectorAll('label');

  function updateStars(value) {
    labels.forEach((lbl, i) => {
      lbl.style.color = (i < value) ? '#f6b01e' : '#ddd';
    });
  }

  // Affichage initial
  inputs.forEach(input => {
    if (input.checked) updateStars(parseInt(input.value));
  });

  // Au clic
  inputs.forEach(input => {
    input.addEventListener('change', () => updateStars(parseInt(input.value)));
  });

  // Au survol
  labels.forEach((lbl, i) => {
    lbl.addEventListener('mouseenter', () => updateStars(i + 1));
    lbl.addEventListener('mouseleave', () => {
      const checked = wrapper.querySelector('input:checked');
      updateStars(checked ? parseInt(checked.value) : 0);
    });
  });
});
</script>

<?php require 'footer.php'; ?>
