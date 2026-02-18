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

// récupérer les avis pour ce livre (silencieux si la table n'existe pas encore)
try {
  $stmtReviews = $bdd->prepare('SELECT id, name, rating, review, created_at FROM review WHERE isbn = :isbn ORDER BY created_at DESC');
  $stmtReviews->execute([':isbn' => $isbn]);
  $reviews = $stmtReviews->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  $reviews = [];
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

      <section style="margin-top:18px;color: var(--muted);line-height:1.7;">
        <?php echo nl2br(htmlspecialchars($book['resume'] ?? '')); ?>
      </section>

      <?php $saved = isset($_GET['saved']) ? $_GET['saved'] : null; $deleted = isset($_GET['deleted']) ? $_GET['deleted'] : null; $edited = isset($_GET['edited']) ? $_GET['edited'] : null; ?>
      <section style="margin-top:20px;">
        <?php if ($saved === '1'): ?>
          <div id="msg-saved" class="notification-fade" style="padding:12px;border-radius:8px;background:var(--card);color:var(--accent);border:1px solid var(--accent);max-width:720px;">Merci — votre avis a été enregistré.</div>
        <?php else: ?>
          <?php if ($saved === '0'): ?>
            <div id="msg-saved-fail" class="notification-fade" style="padding:12px;border-radius:8px;background:var(--card);color:var(--accent-2);border:1px solid var(--accent-2);max-width:720px;margin-bottom:12px;">Erreur lors de l'enregistrement. Veuillez réessayer.</div>
          <?php endif; ?>

          <button id="toggleReviewBtn" style="background:var(--accent);color:var(--card);border:none;padding:10px 14px;border-radius:8px;cursor:pointer;">Déposer un avis</button>

          <form id="reviewForm" class="review-form" action="save_review.php" method="post" style="display:none;margin-top:14px;max-width:720px;">
            <input type="hidden" name="isbn" value="<?php echo htmlspecialchars($book['isbn']); ?>">
            <div style="margin-bottom:8px;">
              <label for="review_name" style="display:block;margin-bottom:6px;color:var(--muted);font-weight:600;">Votre nom (facultatif)</label>
              <input id="review_name" name="name" type="text" style="width:100%;padding:8px;border-radius:6px;border:1px solid var(--muted);background:var(--card);color:var(--muted);">
            </div>
            <div style="margin-bottom:8px;">
              <label for="review_rating" style="display:block;margin-bottom:6px;color:var(--muted);font-weight:600;">Note</label>
              <select id="review_rating" name="rating" style="padding:8px;border-radius:6px;border:1px solid var(--muted);background:var(--card);color:var(--muted);">
                <option value="5">5 — Excellent</option>
                <option value="4">4 — Très bien</option>
                <option value="3">3 — Bien</option>
                <option value="2">2 — Moyen</option>
                <option value="1">1 — Mauvais</option>
              </select>
            </div>
            <div style="margin-bottom:8px;">
              <label for="review_text" style="display:block;margin-bottom:6px;color:var(--muted);font-weight:600;">Votre avis</label>
              <textarea id="review_text" name="review" rows="5" style="width:100%;padding:8px;border-radius:6px;border:1px solid var(--muted);background:var(--card);color:var(--muted);" required></textarea>
            </div>
            <div>
              <button type="submit" style="background:var(--accent);color:var(--card);padding:10px 14px;border-radius:8px;border:none;cursor:pointer;">Envoyer l'avis</button>
              <button type="button" id="cancelReviewBtn" style="margin-left:8px;background:var(--muted);color:var(--card);padding:10px 14px;border-radius:8px;border:none;cursor:pointer;">Annuler</button>
            </div>
          </form>

          <script>
            document.addEventListener('DOMContentLoaded', function(){
              var btn = document.getElementById('toggleReviewBtn');
              var form = document.getElementById('reviewForm');
              var cancel = document.getElementById('cancelReviewBtn');
              if (!btn || !form) return;
              btn.addEventListener('click', function(){
                if (form.style.display === 'none' || form.style.display === '') {
                  form.style.display = 'block';
                  var ta = document.getElementById('review_text'); if (ta) ta.focus();
                } else {
                  form.style.display = 'none';
                }
              });
              if (cancel) cancel.addEventListener('click', function(){ form.style.display = 'none'; });
            });
          </script>
        <?php endif; ?>
      </section>

      <section style="margin-top:20px;max-width:720px;">
        <h2 style="margin:0 0 12px 0;color:var(--accent);">Avis</h2>
        <?php if ($deleted === '1'): ?>
          <div id="msg-deleted" class="notification-fade" style="padding:10px;border-radius:8px;background:var(--card);color:var(--accent-2);border:1px solid var(--accent-2);margin-bottom:12px;">Avis supprimé.</div>
        <?php elseif ($deleted === '0'): ?>
          <div id="msg-deleted-fail" class="notification-fade" style="padding:10px;border-radius:8px;background:var(--card);color:var(--accent-2);border:1px solid var(--accent-2);margin-bottom:12px;">Impossible de supprimer l'avis.</div>
        <?php endif; ?>
        <?php if ($edited === '1'): ?>
          <div id="msg-edited" class="notification-fade" style="padding:10px;border-radius:8px;background:var(--card);color:var(--accent);border:1px solid var(--accent);margin-bottom:12px;">Avis modifié.</div>
        <?php elseif ($edited === '0'): ?>
          <div id="msg-edited-fail" class="notification-fade" style="padding:10px;border-radius:8px;background:var(--card);color:var(--accent-2);border:1px solid var(--accent-2);margin-bottom:12px;">Impossible de modifier l'avis.</div>
        <?php endif; ?>

        <?php if (empty($reviews)): ?>
          <p style="color:var(--muted);">Aucun avis pour le moment.</p>
        <?php else: ?>
          <?php foreach ($reviews as $r): $rid = (int)$r['id']; ?>
            <div class="review-item" style="border:1px solid var(--muted);padding:12px;border-radius:8px;margin-bottom:10px;position:relative;background:var(--card);">
              <div style="display:flex;justify-content:space-between;align-items:center;">
                <div>
                  <strong><?php echo htmlspecialchars($r['name'] ?: 'Anonyme'); ?></strong>
                  <span style="color:var(--muted);margin-left:8px;font-size:0.95em;"><?php echo date('d/m/Y H:i', strtotime($r['created_at'])); ?></span>
                </div>
                <div style="display:flex;align-items:center;gap:10px;">
                  <div style="color:#f6b01e;font-weight:700;"><?php echo str_repeat('★', max(1, min(5, (int)$r['rating']))); ?></div>
                  <div class="review-menu" style="position:relative;">
                    <button type="button" class="menu-toggle" data-id="<?php echo $rid; ?>" aria-expanded="false" style="background:transparent;border:none;font-size:18px;cursor:pointer;">⋯</button>
                    <div class="menu-list" id="menu-<?php echo $rid; ?>" style="display:none;position:absolute;right:0;top:22px;background:var(--card);border:1px solid var(--muted);border-radius:6px;box-shadow:0 6px 20px rgba(0,0,0,0.08);">
                      <button type="button" class="menu-edit" data-id="<?php echo $rid; ?>" style="display:block;padding:8px 12px;background:transparent;border:none;cursor:pointer;width:100%;text-align:left;">Modifier</button>
                      <form method="post" action="delete_review.php" class="delete-form" style="margin:0;">
                        <input type="hidden" name="id" value="<?php echo $rid; ?>">
                        <input type="hidden" name="isbn" value="<?php echo htmlspecialchars($book['isbn']); ?>">
                        <button type="button" class="menu-delete" data-id="<?php echo $rid; ?>" style="display:block;padding:8px 12px;background:transparent;border:none;cursor:pointer;width:100%;text-align:left;color:#c53030;">Supprimer</button>
                      </form>
                    </div>
                  </div>
                </div>
              </div>

              <div class="review-content" id="content-<?php echo $rid; ?>" style="margin-top:8px;"><?php echo nl2br(htmlspecialchars($r['review'])); ?></div>

              <form class="edit-form review-form" id="edit-<?php echo $rid; ?>" action="edit_review.php" method="post" style="display:none;margin-top:10px;">
                <input type="hidden" name="id" value="<?php echo $rid; ?>">
                <input type="hidden" name="isbn" value="<?php echo htmlspecialchars($book['isbn']); ?>">
                <div style="margin-bottom:8px;">
                  <label style="display:block;margin-bottom:6px;color:var(--muted);font-weight:600;">Votre nom (facultatif)</label>
                  <input name="name" type="text" value="<?php echo htmlspecialchars($r['name']); ?>" style="width:100%;padding:8px;border-radius:6px;border:1px solid var(--muted);background:var(--card);color:var(--muted);">
                </div>
                <div style="margin-bottom:8px;">
                  <label style="display:block;margin-bottom:6px;color:var(--muted);font-weight:600;">Note</label>
                  <select name="rating" style="padding:8px;border-radius:6px;border:1px solid var(--muted);background:var(--card);color:var(--muted);">
                    <?php for ($i=5;$i>=1;$i--): ?>
                      <option value="<?php echo $i; ?>" <?php echo ((int)$r['rating']=== $i)?'selected':''; ?>><?php echo $i; ?> — <?php echo ($i===5)?'Excellent':(($i===4)?'Très bien':(($i===3)?'Bien':(($i===2)?'Moyen':'Mauvais'))); ?></option>
                    <?php endfor; ?>
                  </select>
                </div>
                <div style="margin-bottom:8px;">
                  <label style="display:block;margin-bottom:6px;color:var(--muted);font-weight:600;">Votre avis</label>
                  <textarea name="review" rows="4" style="width:100%;padding:8px;border-radius:6px;border:1px solid var(--muted);background:var(--card);color:var(--muted);" required><?php echo htmlspecialchars($r['review']); ?></textarea>
                </div>
                <div>
                  <button type="submit" style="background:var(--accent);color:var(--card);padding:8px 12px;border-radius:6px;border:none;cursor:pointer;">Enregistrer</button>
                  <button type="button" class="edit-cancel" data-id="<?php echo $rid; ?>" style="margin-left:8px;background:var(--muted);color:var(--card);padding:8px 12px;border-radius:6px;border:none;cursor:pointer;">Annuler</button>
                </div>
              </form>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </section>
    </div>
  </div>
</main>
<?php require 'footer.php'; ?>

<style>
  .notification-fade {
    transition: opacity 0.6s ease-out;
  }
</style>

<!-- Confirmation modal personnalisé -->
<div id="confirmModal" style="display:none;position:fixed;inset:0;z-index:1200;align-items:center;justify-content:center;background:rgba(11,10,8,0.45);">
  <div style="background:#fff;width:100%;max-width:480px;border-radius:12px;padding:18px;box-shadow:0 20px 60px rgba(0,0,0,0.25);">
    <h3 style="margin:0 0 8px 0;color:var(--accent);">Confirmer la suppression</h3>
    <p style="margin:0 0 16px;color:#333;">Êtes-vous sûr de vouloir supprimer cet avis ? Cette action est irréversible.</p>
    <div style="display:flex;gap:10px;justify-content:flex-end;">
      <button id="confirmNo" type="button" style="background:#eee;color:#333;padding:8px 12px;border-radius:8px;border:none;cursor:pointer;">Annuler</button>
      <button id="confirmYes" type="button" style="background:#ff6b6b;color:#fff;padding:8px 12px;border-radius:8px;border:none;cursor:pointer;">Supprimer</button>
    </div>
  </div>
</div>

<script>
// gestion des menus, édition inline et modal de confirmation
document.addEventListener('DOMContentLoaded', function(){
  document.querySelectorAll('.menu-toggle').forEach(function(btn){
    btn.addEventListener('click', function(){
      var id = btn.getAttribute('data-id');
      var menu = document.getElementById('menu-' + id);
      if (!menu) return;
      var open = menu.style.display === 'block';
      // fermer tous
      document.querySelectorAll('.menu-list').forEach(function(m){ m.style.display = 'none'; });
      if (!open) menu.style.display = 'block';
    });
  });

  // clic sur Modifier: afficher le formulaire d'édition
  document.querySelectorAll('.menu-edit').forEach(function(btn){
    btn.addEventListener('click', function(){
      var id = btn.getAttribute('data-id');
      var content = document.getElementById('content-' + id);
      var form = document.getElementById('edit-' + id);
      if (!form || !content) return;
      content.style.display = 'none';
      form.style.display = 'block';
      // fermer menu
      var menu = document.getElementById('menu-' + id); if (menu) menu.style.display = 'none';
    });
  });

  // Annuler édition
  document.querySelectorAll('.edit-cancel').forEach(function(btn){
    btn.addEventListener('click', function(){
      var id = btn.getAttribute('data-id');
      var content = document.getElementById('content-' + id);
      var form = document.getElementById('edit-' + id);
      if (!form || !content) return;
      form.style.display = 'none';
      content.style.display = 'block';
    });
  });

  // fermer menus en cliquant ailleurs
  document.addEventListener('click', function(e){
    if (!e.target.closest || e.target.closest('.review-menu')) return;
    document.querySelectorAll('.menu-list').forEach(function(m){ m.style.display = 'none'; });
  });

  // Modal de confirmation pour suppression
  var confirmModal = document.getElementById('confirmModal');
  var confirmYes = document.getElementById('confirmYes');
  var confirmNo = document.getElementById('confirmNo');
  var pendingForm = null;

  // ouvrir le modal quand on clique sur un bouton supprimer
  document.querySelectorAll('.menu-delete').forEach(function(btn){
    btn.addEventListener('click', function(e){
      e.preventDefault();
      // trouver le formulaire parent
      var form = btn.closest('form');
      if (!form) return;
      pendingForm = form;
      if (confirmModal) confirmModal.style.display = 'flex';
      // fermer tous les menus ouverts
      document.querySelectorAll('.menu-list').forEach(function(m){ m.style.display = 'none'; });
    });
  });

  if (confirmNo) confirmNo.addEventListener('click', function(){ if (confirmModal) confirmModal.style.display = 'none'; pendingForm = null; });
  if (confirmYes) confirmYes.addEventListener('click', function(){
    if (pendingForm) pendingForm.submit();
  });

  // fermer modal en cliquant en dehors
  if (confirmModal) confirmModal.addEventListener('click', function(e){ if (e.target === confirmModal) { confirmModal.style.display = 'none'; pendingForm = null; } });

  // auto-hide notifications after 5s with fade-out effect
  ['msg-saved','msg-saved-fail','msg-deleted','msg-deleted-fail','msg-edited','msg-edited-fail'].forEach(function(id){
    var el = document.getElementById(id);
    if (el) setTimeout(function(){
      el.style.opacity = '0';
      setTimeout(function(){ el.style.display = 'none'; }, 600);
    }, 5000);
  });
});
</script>
