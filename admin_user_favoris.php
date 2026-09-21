<?php
require 'admin_auth.php';
require 'header.php';

$uid = (int)($_GET['id'] ?? 0);
if ($uid <= 0) { header('Location: admin_users.php'); exit; }

// Récupérer l'utilisateur
$userStmt = $bdd->prepare('SELECT id, prenom, nom, username FROM users WHERE id = ?');
$userStmt->execute([$uid]);
$userInfo = $userStmt->fetch(PDO::FETCH_ASSOC);
if (!$userInfo) { header('Location: admin_users.php'); exit; }

// Récupérer ses favoris
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
        SELECT f.isbn, f.created_at,
               l.titre, l.image, l.annee,
               GROUP_CONCAT(CONCAT_WS(' ', p.prenom, p.nom) SEPARATOR ' • ') AS auteur
        FROM favoris f
        LEFT JOIN Livre l ON f.isbn = l.isbn
        LEFT JOIN Auteur a ON l.isbn = a.idLivre
        LEFT JOIN Personne p ON a.idPersonne = p.id
        WHERE f.user_id = ?
        GROUP BY f.isbn, f.created_at, l.titre, l.image, l.annee
        ORDER BY f.created_at DESC
    ");
    $favStmt->execute([$uid]);
    $favoris = $favStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $favoris = []; }
?>

<main class="container" style="padding:40px 20px; max-width:900px;">

  <div style="display:flex; align-items:center; gap:16px; margin-bottom:28px; flex-wrap:wrap;">
    <a href="admin_users.php" style="color:var(--muted); font-size:0.88rem; text-decoration:none;">← Retour aux agents</a>
    <div>
      <h2 style="color:var(--accent); font-size:1.6rem;">
        ❤️ Favoris de <span style="color:var(--accent-2);">@<?= htmlspecialchars($userInfo['username']) ?></span>
      </h2>
      <p style="color:var(--muted); font-size:0.88rem; margin-top:4px;">
        <?= htmlspecialchars($userInfo['prenom'] . ' ' . $userInfo['nom']) ?> — <?= count($favoris) ?> favori(s)
      </p>
    </div>
  </div>

  <?php if (empty($favoris)): ?>
    <div style="text-align:center; padding:60px; background:var(--card); border-radius:12px; color:var(--muted); box-shadow:var(--shadow);">
      <p style="font-size:1rem;">Cet agent n'a aucun livre en favori.</p>
    </div>
  <?php else: ?>
    <!-- Barre de recherche interne (lecture seule, filtre visuel) -->
    <div style="margin-bottom:20px;">
      <input type="text" id="adminFavSearch" placeholder="🔍 Filtrer les favoris..."
             oninput="filterAdminFavs(this.value)"
             style="width:100%; max-width:400px; padding:10px 14px; border:1px solid rgba(132,106,83,0.3); border-radius:8px; background:var(--bg); color:var(--muted); font-size:0.9rem; outline:none;">
    </div>

    <div id="adminFavsList" style="display:flex; flex-direction:column; gap:12px;">
      <?php foreach ($favoris as $fav): ?>
      <div class="admin-fav-item"
           data-titre="<?= strtolower(htmlspecialchars($fav['titre'] ?? '')) ?>"
           data-auteur="<?= strtolower(htmlspecialchars($fav['auteur'] ?? '')) ?>"
           style="display:flex; align-items:center; gap:16px; padding:14px 18px; background:var(--card); border-radius:10px; box-shadow:var(--shadow);">

        <?php if (!empty($fav['image'])): ?>
        <img src="<?= htmlspecialchars($fav['image']) ?>" alt="<?= htmlspecialchars($fav['titre'] ?? '') ?>"
             style="width:48px; height:68px; object-fit:cover; border-radius:4px; flex-shrink:0;">
        <?php else: ?>
        <div style="width:48px; height:68px; background:rgba(132,106,83,0.1); border-radius:4px; flex-shrink:0; display:flex; align-items:center; justify-content:center; font-size:1.5rem;">📖</div>
        <?php endif; ?>

        <div style="flex:1; min-width:0;">
          <a href="book-page.php?isbn=<?= urlencode($fav['isbn']) ?>" target="_blank"
             style="font-weight:700; font-size:0.95rem; color:var(--accent); text-decoration:none;">
            <?= htmlspecialchars($fav['titre'] ?? 'Titre inconnu') ?>
          </a>
          <p style="color:var(--muted); font-size:0.84rem; margin-top:3px;"><?= htmlspecialchars($fav['auteur'] ?? 'Auteur inconnu') ?></p>
          <?php if (!empty($fav['annee'])): ?>
          <p style="color:var(--muted); font-size:0.78rem; margin-top:2px;">📅 <?= htmlspecialchars($fav['annee']) ?></p>
          <?php endif; ?>
        </div>

        <div style="flex-shrink:0; text-align:right;">
          <span style="background:rgba(132,106,83,0.08); color:var(--muted); font-size:0.72rem; padding:3px 8px; border-radius:20px;">
            Ajouté le <?= date('d/m/Y', strtotime($fav['created_at'])) ?>
          </span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div id="adminFavNoResult" style="display:none; color:var(--muted); font-size:0.9rem; padding:20px; text-align:center;">Aucun résultat.</div>
  <?php endif; ?>

</main>

<script>
function filterAdminFavs(q) {
    const query = q.toLowerCase().trim();
    const items = document.querySelectorAll('.admin-fav-item');
    let visible = 0;
    items.forEach(item => {
        const t = (item.dataset.titre || '') + ' ' + (item.dataset.auteur || '');
        if (!query || t.includes(query)) {
            item.style.display = '';
            visible++;
        } else {
            item.style.display = 'none';
        }
    });
    const nr = document.getElementById('adminFavNoResult');
    if (nr) nr.style.display = (visible === 0 && query) ? 'block' : 'none';
}
</script>

<?php require 'footer.php'; ?>
