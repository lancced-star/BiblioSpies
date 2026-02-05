<?php require 'header.php'; ?>
<?php
$sql = <<<SQL
SELECT
  Livre.isbn,
  Livre.titre,
  Livre.resume,
  Livre.annee AS date_publication,
  Livre.image,
  GROUP_CONCAT(CONCAT_WS(' ', Personne.prenom, Personne.nom) SEPARATOR ' • ') AS auteur
FROM Livre
LEFT JOIN Auteur ON Livre.isbn = Auteur.idLivre
LEFT JOIN Personne ON Auteur.idPersonne = Personne.id
GROUP BY Livre.isbn
ORDER BY Livre.titre;
SQL;

$req = $bdd->query($sql);
$livre = $req->fetchAll(); ?>

  <section class="search">
    <h2 class="visually-hidden">Recherche</h2>
    <input type="text" id="searchInput" placeholder="Rechercher un livre, une enquête">
    <button id="searchBtn">Rechercher</button>
  </section>

  <div id="bookModal" class="modal" style="display:none">
    <div class="modal-content" id="bookModalContent"></div>
  </div>

  <section id="livre">
    <h2>Nos livres d'Espionnages </h2>

    <div class="grille-livres">
      <?php foreach ($livre as $book) {
        $img = !empty($book['image']) ? $book['image'] : 'placeholder.jpg';
        $titre = isset($book['titre']) ? $book['titre'] : '';
        $auteur = !empty($book['auteur']) ? $book['auteur'] : 'Auteur inconnu';
        $date = !empty($book['date_publication']) ? $book['date_publication'] : (!empty($book['annee']) ? $book['annee'] : '—');
      ?>
      <a href="book-page.php?isbn=<?php echo urlencode($book['isbn']); ?>" class="book-link" data-isbn="<?php echo htmlspecialchars($book['isbn']); ?>">
        <div class="livre">
          <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($titre); ?>">
          <h3><?php echo htmlspecialchars($titre); ?></h3>
          <p>Écrit par <?php echo htmlspecialchars($auteur); ?> — <?php echo htmlspecialchars((string)$date); ?></p>
        </div>
      </a>
      <?php } ?>
    </div>
  </section>


<?php require 'footer.php'; ?>