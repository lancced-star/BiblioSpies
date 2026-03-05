<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require 'connexion-bdd.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); exit;
}

require 'header.php';

// Récupérer les messages de cet utilisateur via son pseudo
$username = $_SESSION['username'] ?? '';
try {
    $stmt = $bdd->prepare('SELECT * FROM contacts WHERE pseudo = :pseudo ORDER BY created_at DESC');
    $stmt->execute([':pseudo' => $username]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {
    $messages = [];
}
?>

<main class="container" style="max-width:760px; padding:40px 20px;">

  <p style="margin-bottom:20px;">
    <a href="profile.php" style="color:var(--accent-2); font-size:0.88rem;">← Retour au profil</a>
  </p>

  <div style="margin-bottom:28px;">
    <h2 style="color:var(--accent); font-size:1.6rem;">📨 Mes messages</h2>
    <p style="color:var(--muted); font-size:0.88rem; margin-top:4px;">
      Retrouvez ici vos demandes envoyées à l'Agence et les réponses des administrateurs.
    </p>
  </div>

  <?php if (empty($messages)): ?>
    <div style="text-align:center; padding:60px; background:var(--card); border-radius:12px; color:var(--muted); box-shadow:var(--shadow);">
      <p style="font-size:1.1rem; margin-bottom:12px;">📭 Aucun message envoyé pour le moment.</p>
      <a href="contact.php" style="background:var(--accent-2); color:white; padding:10px 24px; border-radius:8px; text-decoration:none; font-weight:600; font-size:0.9rem;">
        ✉️ Contacter l'Agence
      </a>
    </div>

  <?php else: ?>
    <div style="display:flex; flex-direction:column; gap:18px;">
      <?php foreach ($messages as $m):
        $aReponse = !empty($m['reponse']);
      ?>
        <div style="
          background:var(--card); border-radius:12px; padding:22px 26px;
          box-shadow:var(--shadow);
          border-left:4px solid <?php echo $aReponse ? '#27ae60' : 'rgba(132,106,83,0.3)'; ?>;
        ">
          <!-- En-tête -->
          <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px; margin-bottom:14px;">
            <div style="display:flex; align-items:center; gap:8px;">
              <?php if ($aReponse): ?>
                <span style="background:#27ae60; color:white; font-size:0.68rem; font-weight:700; padding:2px 8px; border-radius:20px;">✅ RÉPONDU</span>
              <?php else: ?>
                <span style="background:rgba(132,106,83,0.15); color:var(--muted); font-size:0.68rem; font-weight:700; padding:2px 8px; border-radius:20px;">⏳ EN ATTENTE</span>
              <?php endif; ?>
              <strong style="color:var(--accent); font-size:0.95rem;">
                📌 <?php echo htmlspecialchars($m['sujet']); ?>
              </strong>
            </div>
            <span style="color:var(--muted); font-size:0.78rem;">
              <?php echo date('d/m/Y à H:i', strtotime($m['created_at'])); ?>
            </span>
          </div>

          <!-- Votre message -->
          <div style="background:rgba(132,106,83,0.05); border-radius:8px; padding:12px 16px; margin-bottom:<?php echo $aReponse ? '14px' : '0'; ?>;">
            <p style="color:var(--muted); font-size:0.72rem; text-transform:uppercase; letter-spacing:1px; margin-bottom:6px;">Votre message</p>
            <p style="color:var(--muted); font-size:0.9rem; line-height:1.6; white-space:pre-wrap;"><?php echo htmlspecialchars($m['message']); ?></p>
          </div>

          <!-- Réponse de l'admin -->
          <?php if ($aReponse): ?>
            <div style="background:rgba(39,174,96,0.07); border:1px solid rgba(39,174,96,0.25); border-radius:8px; padding:14px 16px;">
              <p style="color:#27ae60; font-size:0.72rem; font-weight:700; text-transform:uppercase; letter-spacing:1px; margin-bottom:8px;">
                🛡️ Réponse de l'Agence — <?php echo date('d/m/Y à H:i', strtotime($m['repondu_le'])); ?>
              </p>
              <p style="color:var(--muted); font-size:0.9rem; line-height:1.6; white-space:pre-wrap;"><?php echo htmlspecialchars($m['reponse']); ?></p>
            </div>
          <?php else: ?>
            <p style="color:var(--muted); font-size:0.8rem; margin-top:10px; font-style:italic;">
              ⏳ En attente de réponse de l'Agence...
            </p>
          <?php endif; ?>

        </div>
      <?php endforeach; ?>
    </div>

    <div style="text-align:center; margin-top:28px;">
      <a href="contact.php" style="background:var(--accent-2); color:white; padding:10px 24px; border-radius:8px; text-decoration:none; font-weight:600; font-size:0.9rem;">
        ✉️ Envoyer un nouveau message
      </a>
    </div>
  <?php endif; ?>

</main>

<?php require 'footer.php'; ?>
