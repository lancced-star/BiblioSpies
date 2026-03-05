<?php
require 'admin_auth.php';
require 'header.php';

// ── Actions ──────────────────────────────────────────────────
if (isset($_GET['suppr']) && is_numeric($_GET['suppr'])) {
    try {
        $bdd->prepare('DELETE FROM review WHERE id = :id')->execute([':id' => (int)$_GET['suppr']]);
        $_SESSION['flash'] = '🗑️ Avis supprimé.';
    } catch(Exception $e) {
        $_SESSION['flash'] = '❌ Erreur lors de la suppression.';
    }
    header('Location: admin_avis.php'); exit;
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// ── Récupération des avis avec le titre du livre ──────────────
try {
    $search = trim($_GET['q'] ?? '');
    if ($search) {
        $stmt = $bdd->prepare('
            SELECT r.*, l.titre FROM review r
            LEFT JOIN Livre l ON r.isbn = l.isbn
            WHERE r.name LIKE :q OR r.review LIKE :q OR l.titre LIKE :q
            ORDER BY r.created_at DESC
        ');
        $stmt->execute([':q' => "%$search%"]);
    } else {
        $stmt = $bdd->query('
            SELECT r.*, l.titre FROM review r
            LEFT JOIN Livre l ON r.isbn = l.isbn
            ORDER BY r.created_at DESC
        ');
    }
    $avis = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {
    $avis = [];
}
?>

<main class="container" style="padding:40px 20px;">

  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:12px;">
    <div>
      <h2 style="color:var(--accent); font-size:1.8rem;">💬 Modération des avis</h2>
      <p style="color:var(--muted); font-size:0.88rem;"><?php echo count($avis); ?> avis publié(s)</p>
    </div>
    <a href="admin_dashboard.php" style="color:var(--muted); font-size:0.88rem; text-decoration:none;">← Dashboard</a>
  </div>

  <?php if ($flash): ?>
    <div style="background:#d4edda; color:#155724; padding:12px 18px; border-radius:8px; margin-bottom:20px;">
      <?php echo htmlspecialchars($flash); ?>
    </div>
  <?php endif; ?>

  <!-- Recherche -->
  <form method="get" action="admin_avis.php" style="margin-bottom:24px; display:flex; gap:10px;">
    <input type="text" name="q" value="<?php echo htmlspecialchars($search ?? ''); ?>"
           placeholder="Rechercher par auteur, contenu ou titre du livre..."
           style="flex:1; padding:10px 16px; border:1px solid rgba(132,106,83,0.3); border-radius:8px; background:var(--bg); color:var(--muted); font-size:0.9rem;">
    <button type="submit" style="background:var(--accent); color:white; padding:10px 20px; border:none; border-radius:8px; cursor:pointer; font-weight:600;">🔍</button>
    <?php if (!empty($search)): ?><a href="admin_avis.php" style="padding:10px 16px; border-radius:8px; border:1px solid rgba(132,106,83,0.3); color:var(--muted); text-decoration:none;">✕</a><?php endif; ?>
  </form>

  <?php if (empty($avis)): ?>
    <div style="text-align:center; padding:60px; background:var(--card); border-radius:12px; color:var(--muted);">
      💬 Aucun avis pour le moment.
    </div>
  <?php else: ?>
    <div style="display:flex; flex-direction:column; gap:14px;">
      <?php foreach ($avis as $a): ?>
        <div style="background:var(--card); border-radius:12px; padding:20px 24px; box-shadow:var(--shadow); display:flex; justify-content:space-between; align-items:flex-start; gap:16px; flex-wrap:wrap;">
          <div style="flex:1; min-width:0;">
            <!-- En-tête -->
            <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:8px;">
              <strong style="color:var(--accent);"><?php echo htmlspecialchars($a['name'] ?? 'Anonyme'); ?></strong>
              <span style="color:#f1c40f;">
                <?php echo str_repeat('★', (int)($a['rating'] ?? 0)) . str_repeat('☆', 5 - (int)($a['rating'] ?? 0)); ?>
              </span>
              <span style="color:var(--muted); font-size:0.78rem;">
                <?php echo date('d/m/Y à H:i', strtotime($a['created_at'])); ?>
              </span>
            </div>
            <!-- Livre -->
            <p style="color:var(--accent-2); font-size:0.82rem; margin-bottom:8px;">
              📖 <?php echo htmlspecialchars($a['titre'] ?? $a['isbn']); ?>
            </p>
            <!-- Contenu -->
            <p style="color:var(--muted); font-size:0.9rem; line-height:1.6; background:rgba(132,106,83,0.05); padding:10px 14px; border-radius:8px;">
              <?php echo nl2br(htmlspecialchars($a['review'])); ?>
            </p>
          </div>
          <!-- Bouton supprimer -->
          <div style="flex-shrink:0;">
            <a href="admin_avis.php?suppr=<?php echo $a['id']; ?>"
               onclick="return confirm('Supprimer cet avis définitivement ?')"
               style="background:#c0392b; color:white; padding:8px 14px; border-radius:8px; font-size:0.82rem; text-decoration:none; font-weight:600; white-space:nowrap;">
              🗑️ Supprimer
            </a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</main>

<?php require 'footer.php'; ?>
