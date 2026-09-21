<?php
require 'admin_auth.php';
require 'header.php';

// Mode : ajout ou modification ?
$isbn_get = isset($_GET['isbn']) ? trim($_GET['isbn']) : null;
$isEdit   = !empty($isbn_get);
$livre    = null;
$editeurs = $bdd->query('SELECT id, libelle FROM Editeur ORDER BY libelle')->fetchAll(PDO::FETCH_ASSOC);
$genres   = $bdd->query('SELECT id, libelle FROM Genre   ORDER BY libelle')->fetchAll(PDO::FETCH_ASSOC);
$langues  = $bdd->query('SELECT id, libelle FROM Langue  ORDER BY libelle')->fetchAll(PDO::FETCH_ASSOC);

if ($isEdit) {
    $stmt = $bdd->prepare('SELECT * FROM Livre WHERE isbn = :isbn');
    $stmt->execute([':isbn' => $isbn_get]);
    $livre = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$livre) {
        $_SESSION['flash'] = 'Livre introuvable.';
        header('Location: admin_livres.php');
        exit;
    }
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>

<main class="container" style="max-width:760px; padding:40px 20px;">

  <!-- Breadcrumb -->
  <p style="margin-bottom:20px;">
    <a href="admin_livres.php" style="color:var(--accent-2);">← Retour à la liste</a>
  </p>

  <h2 style="color:var(--accent); margin-bottom:24px;">
    <?php echo $isEdit ? '✏️ Modifier le livre' : '➕ Ajouter un livre'; ?>
  </h2>

  <?php if ($flash): ?>
    <div style="background:#f8d7da; color:#721c24; padding:12px 18px; border-radius:8px; margin-bottom:20px;">
      <?php echo htmlspecialchars($flash); ?>
    </div>
  <?php endif; ?>

  <form action="admin_livre_save.php" method="post" enctype="multipart/form-data"
        style="background:var(--card); border-radius:12px; padding:30px; box-shadow:var(--shadow); display:flex; flex-direction:column; gap:18px;">

    <!-- ISBN (lecture seule en mode édition) -->
    <div>
      <label style="font-weight:600; color:var(--accent); display:block; margin-bottom:6px;">ISBN *</label>
      <input type="text" name="isbn" required maxlength="15"
             value="<?php echo htmlspecialchars($livre['isbn'] ?? ''); ?>"
             <?php echo $isEdit ? 'readonly style="background:#f5f5f5; cursor:not-allowed;"' : ''; ?>
             placeholder="Ex : 9782867465444"
             style="width:100%; padding:10px 14px; border:1px solid rgba(132,106,83,0.3); border-radius:8px; font-size:0.95rem; color:var(--muted);">
      <?php if ($isEdit): ?>
        <input type="hidden" name="isbn_original" value="<?php echo htmlspecialchars($livre['isbn']); ?>">
      <?php endif; ?>
    </div>

    <!-- Titre -->
    <div>
      <label style="font-weight:600; color:var(--accent); display:block; margin-bottom:6px;">Titre *</label>
      <input type="text" name="titre" required maxlength="500"
             value="<?php echo htmlspecialchars($livre['titre'] ?? ''); ?>"
             placeholder="Titre du livre"
             style="width:100%; padding:10px 14px; border:1px solid rgba(132,106,83,0.3); border-radius:8px; font-size:0.95rem; color:var(--muted);">
    </div>

    <!-- Ligne : Éditeur + Année -->
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
      <div>
        <label style="font-weight:600; color:var(--accent); display:block; margin-bottom:6px;">Éditeur *</label>
        <select name="editeur" required
                style="width:100%; padding:10px 14px; border:1px solid rgba(132,106,83,0.3); border-radius:8px; font-size:0.95rem; background:var(--accent-5); color:var(--muted);">
          <option value="">— Choisir —</option>
          <?php foreach ($editeurs as $e): ?>
            <option value="<?php echo $e['id']; ?>" <?php echo (isset($livre['editeur']) && $livre['editeur'] == $e['id']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($e['libelle']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label style="font-weight:600; color:var(--accent); display:block; margin-bottom:6px;">Année</label>
        <input type="number" name="annee" min="1800" max="2099"
               value="<?php echo htmlspecialchars($livre['annee'] ?? ''); ?>"
               placeholder="Ex : 2023"
               style="width:100%; padding:10px 14px; border:1px solid rgba(132,106,83,0.3); border-radius:8px; font-size:0.95rem; background:var(--accent-5);color:var(--muted);">
      </div>
    </div>

    <!-- Ligne : Genre + Langue + Nb pages -->
    <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px;">
      <div>
        <label style="font-weight:600; color:var(--accent); display:block; margin-bottom:6px;">Genre</label>
        <select name="genre" style="width:100%; padding:10px 14px; border:1px solid rgba(132,106,83,0.3); border-radius:8px; font-size:0.95rem; background:var(--accent-5); color:var(--muted);">
          <option value="">— Choisir —</option>
          <?php foreach ($genres as $g): ?>
            <option value="<?php echo $g['id']; ?>" <?php echo (isset($livre['genre']) && $livre['genre'] == $g['id']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($g['libelle']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label style="font-weight:600; color:var(--accent); display:block; margin-bottom:6px;">Langue</label>
        <select name="langue" style="width:100%; padding:10px 14px; border:1px solid rgba(132,106,83,0.3); border-radius:8px; font-size:0.95rem; background:var(--accent-5); color:var(--muted);">
          <option value="">— Choisir —</option>
          <?php foreach ($langues as $l): ?>
            <option value="<?php echo $l['id']; ?>" <?php echo (isset($livre['langue']) && $livre['langue'] == $l['id']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($l['libelle']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label style="font-weight:600; color:var(--accent); display:block; margin-bottom:6px;">Nb pages</label>
        <input type="number" name="nbpages" min="1"
               value="<?php echo htmlspecialchars($livre['nbpages'] ?? ''); ?>"
               placeholder="Ex : 320"
               style="width:100%; padding:10px 14px; border:1px solid rgba(132,106,83,0.3); border-radius:8px; font-size:0.95rem; background:var(--accent-5);color:var(--muted);">
      </div>
    </div>

    <!-- Résumé -->
    <div>
      <label style="font-weight:600; color:var(--accent); display:block; margin-bottom:6px;">Résumé</label>
      <textarea name="resume" rows="5"
                placeholder="Résumé du livre..."
                style="width:100%; padding:10px 14px; border:1px solid rgba(132,106,83,0.3); border-radius:8px; font-size:0.95rem; resize:vertical; color:var(--muted);"><?php echo htmlspecialchars($livre['resume'] ?? ''); ?></textarea>
    </div>

    <!-- Upload couverture -->
    <div>
      <label style="font-weight:600; color:var(--accent); display:block; margin-bottom:6px;">Image de couverture</label>
      <?php if ($isEdit && !empty($livre['image'])): ?>
        <div style="margin-bottom:10px; display:flex; align-items:center; gap:16px;">
          <img src="<?php echo htmlspecialchars($livre['image']); ?>" alt="couverture actuelle"
               style="width:60px; height:85px; object-fit:cover; border-radius:4px; border:1px solid #ddd;">
          <span style="font-size:0.85rem; color:var(--muted);">Image actuelle — laissez vide pour la conserver</span>
        </div>
      <?php endif; ?>
      <input type="file" name="image" accept="image/jpeg,image/png,image/webp"
             style="width:100%; padding:8px; border:1px dashed rgba(132,106,83,0.4); border-radius:8px; background:var(--accent-5); color:var(--muted);">
      <p style="font-size:0.78rem; color:var(--muted); margin-top:4px;">JPG, PNG ou WEBP — max 2 Mo. Le fichier sera nommé automatiquement d'après l'ISBN.</p>
    </div>

    <!-- Boutons -->
    <div style="display:flex; gap:12px; justify-content:flex-end; padding-top:8px;">
      <a href="admin_livres.php"
         style="padding:10px 22px; border-radius:8px; border:1px solid rgba(132,106,83,0.3); color:var(--muted); text-decoration:none; font-weight:600;">
        Annuler
      </a>
      <button type="submit"
              style="padding:10px 28px; background:var(--accent-2); color:white; border:none; border-radius:8px; font-weight:700; font-size:0.95rem; cursor:pointer;">
        <?php echo $isEdit ? '💾 Enregistrer les modifications' : '➕ Ajouter le livre'; ?>
      </button>
    </div>

  </form>
</main>

<?php require 'footer.php'; ?>
