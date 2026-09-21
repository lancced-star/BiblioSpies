<?php
require 'admin_auth.php';
require 'header.php';

// Supprimer
if (isset($_GET['suppr']) && is_numeric($_GET['suppr'])) {
    try {
        $bdd->prepare("DELETE FROM `avis` WHERE id=?")->execute([(int)$_GET['suppr']]);
        $_SESSION['flash'] = '🗑️ Avis supprimé.';
    } catch(Exception $e) { $_SESSION['flash'] = '❌ Erreur.'; }
    header('Location: admin_avis.php'); exit;
}

// Modifier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $id     = (int)$_POST['edit_id'];
    $rating = (int)($_POST['rating'] ?? 3);
    $text   = trim($_POST['contenu'] ?? '');
    if ($id > 0 && !empty($text)) {
        try {
            $bdd->prepare("UPDATE `avis` SET rating=?, contenu=? WHERE id=?")->execute([$rating, $text, $id]);
            $_SESSION['flash'] = '✅ Avis modifié.';
        } catch(Exception $e) { $_SESSION['flash'] = '❌ Erreur modification.'; }
    }
    header('Location: admin_avis.php'); exit;
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// Créer table si besoin
try {
    $bdd->exec("CREATE TABLE IF NOT EXISTS `avis` (
        `id` INT AUTO_INCREMENT PRIMARY KEY, `isbn` VARCHAR(20) NOT NULL,
        `user_id` INT DEFAULT NULL, `name` VARCHAR(255) DEFAULT NULL,
        `rating` TINYINT DEFAULT NULL, `contenu` TEXT NOT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch(Exception $e) {}

$search = trim($_GET['q'] ?? '');
try {
    if ($search) {
        $stmt = $bdd->prepare("
            SELECT a.*, l.titre FROM `avis` a
            LEFT JOIN Livre l ON a.isbn = l.isbn
            WHERE a.name LIKE :q OR a.contenu LIKE :q OR l.titre LIKE :q
            ORDER BY a.created_at DESC");
        $stmt->execute([':q' => "%$search%"]);
    } else {
        $stmt = $bdd->query("
            SELECT a.*, l.titre FROM `avis` a
            LEFT JOIN Livre l ON a.isbn = l.isbn
            ORDER BY a.created_at DESC");
    }
    $avisList = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) { $avisList = []; }
?>
<main class="container" style="padding:40px 20px;">

  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:22px;flex-wrap:wrap;gap:12px;">
    <div>
      <h2 style="color:var(--accent);font-size:1.7rem;">💬 Modération des avis</h2>
      <p style="color:var(--muted);font-size:0.85rem;"><?= count($avisList) ?> avis publié(s)</p>
    </div>
    <a href="admin_dashboard.php" style="color:var(--muted);font-size:0.85rem;text-decoration:none;">← Dashboard</a>
  </div>

  <?php if ($flash): ?>
  <div style="background:#d4edda;color:#155724;padding:12px 18px;border-radius:8px;margin-bottom:18px;"><?= htmlspecialchars($flash) ?></div>
  <?php endif; ?>

  <form method="get" style="margin-bottom:22px;display:flex;gap:9px;">
    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Rechercher…"
           style="flex:1;padding:9px 14px;border:1px solid rgba(132,106,83,0.25);border-radius:8px;color:var(--muted);font-size:0.9rem;">
    <button type="submit" style="background:var(--accent);color:white;padding:9px 18px;border:none;border-radius:8px;cursor:pointer;font-weight:600;">🔍</button>
    <?php if ($search): ?><a href="admin_avis.php" style="padding:9px 14px;border-radius:8px;border:1px solid rgba(132,106,83,0.25);color:var(--muted);text-decoration:none;">✕</a><?php endif; ?>
  </form>

  <?php if (empty($avisList)): ?>
  <div style="text-align:center;padding:60px;background:var(--card);border-radius:12px;color:var(--muted);">💬 Aucun avis.</div>
  <?php else: ?>
  <div style="display:flex;flex-direction:column;gap:13px;">
    <?php foreach ($avisList as $a):
      $aid    = (int)$a['id'];
      $rating = max(1, min(5, (int)($a['rating'] ?? 3)));
    ?>
    <div style="background:var(--card);border-radius:12px;padding:18px 22px;box-shadow:var(--shadow);">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:14px;flex-wrap:wrap;">
        <div style="flex:1;min-width:0;">
          <div style="display:flex;align-items:center;gap:9px;flex-wrap:wrap;margin-bottom:7px;">
            <strong style="color:var(--accent);"><?= htmlspecialchars($a['name'] ?? 'Anonyme') ?></strong>
            <span style="color:#f6b01e;"><?= str_repeat('★', $rating) ?><span style="color:#ddd;"><?= str_repeat('★', 5-$rating) ?></span></span>
            <span style="color:var(--muted);font-size:0.76rem;"><?= date('d/m/Y à H:i', strtotime($a['created_at'])) ?></span>
          </div>
          <p style="color:#a8834a;font-size:0.8rem;margin-bottom:8px;">📖 <?= htmlspecialchars($a['titre'] ?? $a['isbn']) ?></p>
          <div id="ac-<?= $aid ?>" style="color:var(--muted);font-size:0.9rem;line-height:1.6;background:rgba(132,106,83,0.05);padding:10px 13px;border-radius:8px;">
            <?= nl2br(htmlspecialchars($a['contenu'])) ?>
          </div>
          <form id="ae-<?= $aid ?>" method="POST" action="admin_avis.php" style="display:none;margin-top:12px;">
            <input type="hidden" name="edit_id" value="<?= $aid ?>">
            <select name="rating" style="padding:7px 11px;border:1px solid rgba(132,106,83,0.2);border-radius:7px;background:var(--bg);color:var(--muted);font-size:0.85rem;margin-bottom:8px;display:block;">
              <?php for($i=5;$i>=1;$i--): ?><option value="<?= $i ?>" <?= $rating===$i?'selected':'' ?>><?= $i ?> étoile<?= $i>1?'s':'' ?></option><?php endfor; ?>
            </select>
            <textarea name="contenu" rows="3" required style="width:100%;padding:8px;border:1px solid rgba(132,106,83,0.2);border-radius:7px;background:var(--bg);color:var(--muted);font-size:0.88rem;resize:none;margin-bottom:8px;"><?= htmlspecialchars($a['contenu']) ?></textarea>
            <div style="display:flex;gap:7px;">
              <button type="submit" style="padding:7px 16px;background:#27ae60;color:white;border:none;border-radius:7px;cursor:pointer;font-size:0.82rem;font-weight:600;">💾 Sauvegarder</button>
              <button type="button" onclick="toggleAdminEdit(<?= $aid ?>)" style="padding:7px 12px;background:rgba(132,106,83,0.07);border:none;border-radius:7px;cursor:pointer;font-size:0.82rem;color:var(--muted);">Annuler</button>
            </div>
          </form>
        </div>
        <div style="flex-shrink:0;display:flex;flex-direction:column;gap:6px;">
          <button onclick="toggleAdminEdit(<?= $aid ?>)"
                  style="background:rgba(52,152,219,.1);border:1px solid rgba(52,152,219,.3);color:#2c7be5;padding:7px 13px;border-radius:8px;font-size:0.8rem;cursor:pointer;font-weight:600;">✏️ Modifier</button>
          <a href="admin_avis.php?suppr=<?= $aid ?>" onclick="return confirm('Supprimer ?')"
             style="background:#c0392b;color:white;padding:7px 13px;border-radius:8px;font-size:0.8rem;text-decoration:none;font-weight:600;text-align:center;">🗑️ Supprimer</a>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</main>
<script>
function toggleAdminEdit(id) {
    const c = document.getElementById('ac-'+id);
    const f = document.getElementById('ae-'+id);
    const showing = f.style.display==='block';
    f.style.display = showing?'none':'block';
    c.style.display = showing?'block':'none';
}
</script>
<?php require 'footer.php'; ?>
