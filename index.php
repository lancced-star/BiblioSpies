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
      <input type="text" id="searchInput" placeholder="Rechercher un livre, une enquête, un auteur..."
             oninput="searchBooks(this.value)" autocomplete="off">
      <button id="searchBtn" onclick="searchBooks(document.getElementById('searchInput').value)">Rechercher</button>
    </section>
    <div id="searchNoResult" style="display:none;text-align:center;padding:40px;color:var(--muted);font-size:1rem;">
      Aucun livre trouvé pour cette recherche.
    </div>

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
        <a href="book-page.php?isbn=<?php echo urlencode($book['isbn']); ?>" class="book-link"
           data-isbn="<?php echo htmlspecialchars($book['isbn']); ?>"
           data-titre="<?php echo strtolower(htmlspecialchars($titre)); ?>"
           data-auteur="<?php echo strtolower(htmlspecialchars($auteur)); ?>"
           data-resume="<?php echo strtolower(htmlspecialchars(substr($book['resume'] ?? '', 0, 200))); ?>">
          <div class="livre">
            <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($titre); ?>">
            <h3><?php echo htmlspecialchars($titre); ?></h3>
            <p>Écrit par <?php echo htmlspecialchars($auteur); ?> — <?php echo htmlspecialchars((string)$date); ?></p>
          </div>
        </a>
        <?php } ?>
      </div>
    </section>


  <script>
  function normalizeStr(s) {
    return String(s)
      .replace(/[\u2018\u2019\u201A\u201B\u2032\u0060]/g, "'")
      .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
      .toLowerCase();
  }

  function searchBooks(q) {
    const query = normalizeStr(q.trim());
    const links = document.querySelectorAll('.book-link');
    let visible = 0;
    links.forEach(link => {
      const t = normalizeStr((link.dataset.titre || '') + ' ' + (link.dataset.auteur || '') + ' ' + (link.dataset.resume || ''));
      if (!query || t.includes(query)) {
        link.style.display = '';
        visible++;
      } else {
        link.style.display = 'none';
      }
    });
    const noResult = document.getElementById('searchNoResult');
    if (noResult) noResult.style.display = (visible === 0 && query) ? 'block' : 'none';
  }
  // Allow Enter key
  document.addEventListener('DOMContentLoaded', () => {
    const inp = document.getElementById('searchInput');
    if (inp) inp.addEventListener('keydown', e => {
      if (e.key === 'Enter') searchBooks(inp.value);
    });
  });
  </script>
  <?php require 'footer.php'; ?>