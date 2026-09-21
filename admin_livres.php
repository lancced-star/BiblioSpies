<?php
require 'admin_auth.php';
require 'header.php';

// Message flash
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// Récupération de tous les livres avec auteur, éditeur, genre
$sql = "
  SELECT
    Livre.isbn, Livre.titre, Livre.annee, Livre.image,
    Editeur.libelle AS editeur,
    Genre.libelle   AS genre,
    GROUP_CONCAT(CONCAT_WS(' ', Personne.prenom, Personne.nom) SEPARATOR ', ') AS auteurs
  FROM Livre
  LEFT JOIN Editeur  ON Livre.editeur = Editeur.id
  LEFT JOIN Genre    ON Livre.genre   = Genre.id
  LEFT JOIN Auteur   ON Livre.isbn    = Auteur.idLivre
  LEFT JOIN Personne ON Auteur.idPersonne = Personne.id
  GROUP BY Livre.isbn
  ORDER BY Livre.titre
";
$livres = $bdd->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="container" style="padding: 40px 20px;">

  <!-- En-tête admin -->
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:30px; flex-wrap:wrap; gap:12px;">
    <div>
      <h2 style="color:var(--accent); font-size:1.8rem;">📚 Gestion des livres</h2>
      <p style="color:var(--muted); font-size:0.9rem;"><?php echo count($livres); ?> livre(s) dans la bibliothèque</p>
    </div>
    <a href="admin_livre_form.php" style="
      background: var(--accent-2); color: white;
      padding: 10px 20px; border-radius: 8px;
      font-weight: 600; font-size: 0.95rem; text-decoration:none;">
      + Ajouter un livre
    </a>
  </div>

  <!-- Message flash -->
  <?php if ($flash): ?>
    <div style="background:#d4edda; color:#155724; padding:12px 18px; border-radius:8px; margin-bottom:20px; border:1px solid #c3e6cb;">
      <?php echo htmlspecialchars($flash); ?>
    </div>
  <?php endif; ?>

  <!-- Tableau des livres -->
  <div style="overflow-x:auto;">
    <table style="width:100%; border-collapse:collapse; background:var(--card); border-radius:12px; overflow:hidden; box-shadow:var(--shadow);">
      <thead>
        <tr style="background:var(--accent-4); color:white; text-align:left;">
          <th style="padding:14px 16px;">Couverture</th>
          <th style="padding:14px 16px;">Titre</th>
          <th style="padding:14px 16px;">Auteur(s)</th>
          <th style="padding:14px 16px;">Éditeur</th>
          <th style="padding:14px 16px;">Année</th>
          <th style="padding:14px 16px;">Genre</th>
          <th style="padding:14px 16px; text-align:center;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($livres as $i => $livre): ?>
        <tr style="border-bottom:1px solid rgba(132,106,83,0.1); <?php echo $i % 2 === 0 ? '' : 'background:rgba(132,106,83,0.04)'; ?>">
          
          <!-- Couverture -->
          <td style="padding:10px 16px;">
            <?php if (!empty($livre['image'])): ?>
              <img src="<?php echo htmlspecialchars($livre['image']); ?>"
                   alt="couverture" style="width:45px; height:65px; object-fit:cover; border-radius:4px;">
            <?php else: ?>
              <div style="width:45px;height:65px;background:#eee;border-radius:4px;display:flex;align-items:center;justify-content:center;font-size:0.7rem;color:#999;">N/A</div>
            <?php endif; ?>
          </td>

          <!-- Titre + ISBN -->
          <td style="padding:10px 16px;">
            <strong style="color:var(--accent); font-size:0.95rem;"><?php echo htmlspecialchars($livre['titre']); ?></strong>
            <br><span style="font-size:0.75rem; color:var(--muted);">ISBN: <?php echo htmlspecialchars($livre['isbn']); ?></span>
          </td>

          <td style="padding:10px 16px; font-size:0.9rem; color:var(--accent);"><?php echo htmlspecialchars($livre['auteurs'] ?? '—'); ?></td>
          <td style="padding:10px 16px; font-size:0.9rem; color:var(--accent);"><?php echo htmlspecialchars($livre['editeur'] ?? '—'); ?></td>
          <td style="padding:10px 16px; font-size:0.9rem; color:var(--accent);"><?php echo htmlspecialchars($livre['annee'] ?? '—'); ?></td>
          <td style="padding:10px 16px; font-size:0.9rem; color:var(--accent);"><?php echo htmlspecialchars($livre['genre'] ?? '—'); ?></td>

          <!-- Actions -->
          <td style="padding:10px 16px; text-align:center; white-space:nowrap;">
            <a href="admin_livre_form.php?isbn=<?php echo urlencode($livre['isbn']); ?>"
               style="background:#2c7be5; color:white; padding:6px 12px; border-radius:6px; font-size:0.82rem; margin-right:6px; text-decoration:none;">
              ✏️ Modifier
            </a>
            <a href="admin_livre_delete.php?isbn=<?php echo urlencode($livre['isbn']); ?>"
               onclick="return confirm('Supprimer définitivement « <?php echo addslashes($livre['titre']); ?> » ?')"
               style="background:#c0392b; color:white; padding:6px 12px; border-radius:6px; font-size:0.82rem; text-decoration:none;">
              🗑️ Supprimer
            </a>
          </td>
        </tr>
        <?php endforeach; ?>

        <?php if (empty($livres)): ?>
          <tr><td colspan="7" style="text-align:center; padding:40px; color:var(--muted);">Aucun livre dans la base.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

</main>

<?php require 'footer.php'; ?>
