<?php
require 'connexion-bdd.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$isbn = trim($_GET['isbn'] ?? '');
if (empty($isbn)) { header('Location: index.php'); exit; }

$userId  = (int)($_SESSION['user_id'] ?? 0);
$isAdmin = !empty($_SESSION['is_admin']);

// Livre
$stmtBook = $bdd->prepare("
    SELECT l.isbn, l.titre, l.image,
           GROUP_CONCAT(CONCAT_WS(' ', p.prenom, p.nom) SEPARATOR ' • ') AS auteur
    FROM Livre l
    LEFT JOIN Auteur a ON l.isbn = a.idLivre
    LEFT JOIN Personne p ON a.idPersonne = p.id
    WHERE l.isbn = ? GROUP BY l.isbn");
$stmtBook->execute([$isbn]);
$book = $stmtBook->fetch(PDO::FETCH_ASSOC);
if (!$book) { header('Location: index.php'); exit; }

// Tous les avis
try {
    $bdd->exec("CREATE TABLE IF NOT EXISTS `avis` (
        `id` INT AUTO_INCREMENT PRIMARY KEY, `isbn` VARCHAR(20) NOT NULL,
        `user_id` INT DEFAULT NULL, `name` VARCHAR(255) DEFAULT NULL,
        `rating` TINYINT DEFAULT NULL, `contenu` TEXT NOT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $stmtA = $bdd->prepare("SELECT * FROM `avis` WHERE isbn=? ORDER BY created_at DESC");
    $stmtA->execute([$isbn]);
    $avisList = $stmtA->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $avisList = []; }

require 'header.php';
?>
<main style="max-width:820px;margin:0 auto;padding:36px 20px 80px;">

  <a href="book-page.php?isbn=<?= urlencode($isbn) ?>"
     style="display:inline-flex;align-items:center;gap:5px;color:var(--muted);font-size:0.85rem;margin-bottom:22px;text-decoration:none;">
    ← Retour à la fiche
  </a>

  <!-- Livre -->
  <div style="display:flex;align-items:center;gap:16px;background:var(--card);border-radius:12px;padding:18px 22px;box-shadow:var(--shadow);margin-bottom:24px;">
    <?php if (!empty($book['image'])): ?>
    <img src="<?= htmlspecialchars($book['image']) ?>" alt="" style="width:48px;height:68px;object-fit:cover;border-radius:5px;flex-shrink:0;">
    <?php endif; ?>
    <div>
      <h1 style="color:var(--accent);font-size:1.2rem;font-weight:800;margin-bottom:3px;"><?= htmlspecialchars($book['titre']) ?></h1>
      <p style="color:var(--muted);font-size:0.85rem;"><?= htmlspecialchars($book['auteur'] ?? '') ?></p>
      <p style="color:var(--muted);font-size:0.8rem;margin-top:3px;"><?= count($avisList) ?> avis</p>
    </div>
  </div>

  <?php if (empty($avisList)): ?>
  <div style="text-align:center;padding:60px;background:var(--card);border-radius:12px;color:var(--muted);">Aucun avis.</div>
  <?php else: ?>
  <div style="display:flex;flex-direction:column;gap:13px;">
    <?php foreach ($avisList as $a):
      $aid     = (int)$a['id'];
      $rating  = max(1, min(5, (int)($a['rating'] ?? 3)));
      $isOwner = $userId && (int)($a['user_id'] ?? 0) === $userId;
      $canEdit = $isOwner || $isAdmin;
      $contenu = $a['contenu'] ?? '';
    ?>
    <div style="background:var(--card);border-radius:12px;padding:18px 22px;box-shadow:var(--shadow);">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;flex-wrap:wrap;margin-bottom:10px;">
        <div style="display:flex;align-items:center;gap:9px;flex-wrap:wrap;">
          <strong style="color:var(--accent);font-size:0.92rem;"><?= htmlspecialchars($a['name'] ?? 'Agent') ?></strong>
          <?php if ($isOwner): ?><span style="background:rgba(201,169,110,.15);color:#a8834a;font-size:0.68rem;font-weight:700;padding:2px 8px;border-radius:20px;">Mon avis</span><?php endif; ?>
          <span style="color:#f6b01e;"><?= str_repeat('★', $rating) ?><span style="color:#ddd;"><?= str_repeat('★', 5-$rating) ?></span></span>
          <span style="color:var(--muted);font-size:0.76rem;"><?= date('d/m/Y à H:i', strtotime($a['created_at'])) ?></span>
        </div>
        <?php if ($canEdit): ?>
        <div style="display:flex;gap:6px;">
          <button onclick="toggleEditAvis(<?= $aid ?>)" style="background:none;border:1px solid rgba(132,106,83,0.18);border-radius:6px;padding:4px 10px;cursor:pointer;font-size:0.75rem;color:var(--muted);">✏️ Modifier</button>
          <form method="POST" action="delete_review.php" style="margin:0;" onsubmit="return confirm('Supprimer ?')">
            <input type="hidden" name="id"   value="<?= $aid ?>">
            <input type="hidden" name="isbn" value="<?= htmlspecialchars($isbn) ?>">
            <button type="submit" style="background:none;border:1px solid rgba(192,57,43,.2);border-radius:6px;padding:4px 10px;cursor:pointer;font-size:0.75rem;color:#c0392b;">🗑️</button>
          </form>
        </div>
        <?php endif; ?>
      </div>
      <div id="content-<?= $aid ?>" style="color:var(--muted);font-size:0.9rem;line-height:1.7;"><?= nl2br(htmlspecialchars($contenu)) ?></div>
      <form id="edit-<?= $aid ?>" method="POST" action="edit_review.php" style="display:none;margin-top:12px;">
        <input type="hidden" name="id"   value="<?= $aid ?>">
        <input type="hidden" name="isbn" value="<?= htmlspecialchars($isbn) ?>">
        <select name="rating" style="padding:7px 11px;border:1.5px solid rgba(132,106,83,0.18);border-radius:8px;background:var(--bg);color:var(--accent);font-size:0.85rem;margin-bottom:9px;display:block;">
          <?php for($i=5;$i>=1;$i--): ?><option value="<?= $i ?>" <?= $rating===$i?'selected':'' ?>><?= $i ?> — <?= ['','Mauvais','Moyen','Bien','Très bien','Excellent'][$i] ?></option><?php endfor; ?>
        </select>
        <textarea name="review" rows="4" required style="width:100%;padding:9px 12px;border:1.5px solid rgba(132,106,83,0.18);border-radius:8px;background:var(--bg);color:var(--accent);font-size:0.9rem;resize:none;margin-bottom:9px;"><?= htmlspecialchars($contenu) ?></textarea>
        <div style="display:flex;gap:7px;">
          <button type="submit" style="padding:8px 17px;background:var(--accent);color:white;border:none;border-radius:7px;cursor:pointer;font-size:0.83rem;font-weight:700;">💾 Sauvegarder</button>
          <button type="button" onclick="toggleEditAvis(<?= $aid ?>)" style="padding:8px 13px;background:rgba(132,106,83,0.07);border:none;border-radius:7px;cursor:pointer;font-size:0.83rem;color:var(--muted);">Annuler</button>
        </div>
      </form>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

</main>
<script>
function toggleEditAvis(id) {
    const c = document.getElementById('content-'+id);
    const f = document.getElementById('edit-'+id);
    const showing = f.style.display==='block';
    f.style.display = showing ? 'none' : 'block';
    c.style.display = showing ? 'block' : 'none';
}
</script>
<?php require 'footer.php'; ?>
