<?php
require 'header.php';
require 'connexion-bdd.php';

if (!isset($_GET['isbn']) || empty($_GET['isbn'])) {
  header('Location: index.php');
  exit;
}

$isbn = $_GET['isbn'];

$sql = "SELECT
  Livre.isbn,
  Livre.titre,
  Livre.resume,
  Livre.image,
  Livre.annee,
  GROUP_CONCAT(CONCAT_WS(' ', Personne.prenom, Personne.nom) SEPARATOR ' • ') AS auteur,
  Editeur.libelle AS editeur,
  Langue.libelle AS langue,
  Livre.nbpages
FROM Livre
LEFT JOIN Auteur ON Livre.isbn = Auteur.idLivre
LEFT JOIN Personne ON Auteur.idPersonne = Personne.id
LEFT JOIN Editeur ON Livre.editeur = Editeur.id
LEFT JOIN Langue ON Livre.langue = Langue.id
WHERE Livre.isbn = :isbn
GROUP BY Livre.isbn
LIMIT 1";

$stmt = $bdd->prepare($sql);
$stmt->execute([':isbn' => $isbn]);
$book = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$book) {
  echo '<main style="padding:40px; text-align:center;"><h2>Livre non trouvé</h2><p><a href="index.php">Retour à l\'accueil</a></p></main>';
  require 'footer.php';
  exit;
}

// render page
?>
<main style="max-width:900px;margin:40px auto;padding:0 20px;">
  <a href="index.php" style="display:inline-block;margin-bottom:20px;color:var(--accent);">← Retour</a>
  <div class="book-page" style="display:flex;gap:30px;align-items:flex-start;">
    <?php if (!empty($book['image'])): ?>
      <div style="flex:0 0 320px;">
        <img src="<?php echo htmlspecialchars($book['image']); ?>" alt="<?php echo htmlspecialchars($book['titre']); ?>" style="width:100%;border-radius:12px;box-shadow:0 8px 30px rgba(0,0,0,0.08);">
      </div>
    <?php endif; ?>
    <div style="flex:1;">
      <h1 style="color:var(--accent);margin-top:0"><?php echo htmlspecialchars($book['titre']); ?></h1>
      <p style="color:var(--muted);font-weight:600;"><?php echo htmlspecialchars($book['auteur'] ?? 'Auteur inconnu'); ?> <?php echo !empty($book['annee']) ? '— ' . htmlspecialchars($book['annee']) : ''; ?></p>
      <?php if (!empty($book['editeur'])): ?><p style="margin-top:6px;color:var(--muted);">Éditeur: <?php echo htmlspecialchars($book['editeur']); ?></p><?php endif; ?>
      <?php if (!empty($book['nbpages'])): ?><p style="margin-top:6px;color:var(--muted);"><?php echo htmlspecialchars($book['nbpages']); ?> pages</p><?php endif; ?>

      <section style="margin-top:18px;color: #4b3a2b;line-height:1.7;">
        <?php echo nl2br(htmlspecialchars($book['resume'] ?? '')); ?>
      </section>
    </div>
  </div>
</main>
<?php require 'footer.php'; ?>
