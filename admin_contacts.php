<?php
require 'admin_auth.php';
require 'header.php';

// ── Actions GET ──────────────────────────────────────────────
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id     = (int)$_GET['id'];
    $action = $_GET['action'];

    if ($action === 'lu') {
        $bdd->prepare('UPDATE contacts SET lu = 1 WHERE id = :id')->execute([':id' => $id]);
    } elseif ($action === 'nonlu') {
        $bdd->prepare('UPDATE contacts SET lu = 0 WHERE id = :id')->execute([':id' => $id]);
    } elseif ($action === 'suppr') {
        $bdd->prepare('DELETE FROM contacts WHERE id = :id')->execute([':id' => $id]);
    }
    header('Location: admin_contacts.php'); exit;
}

// ── Enregistrer une réponse ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['repondre'])) {
    $id      = (int)($_POST['contact_id'] ?? 0);
    $reponse = trim($_POST['reponse'] ?? '');
    if ($id > 0 && !empty($reponse)) {
        $bdd->prepare('UPDATE contacts SET reponse = :r, repondu_le = NOW(), lu = 1 WHERE id = :id')
            ->execute([':r' => $reponse, ':id' => $id]);
        $_SESSION['flash'] = '✅ Réponse envoyée.';
    }
    header('Location: admin_contacts.php'); exit;
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// Filtre lu/non lu
$filtre = $_GET['filtre'] ?? 'tous';
if ($filtre === 'nonlu') {
    $contacts = $bdd->query('SELECT * FROM contacts WHERE lu = 0 ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
} elseif ($filtre === 'lu') {
    $contacts = $bdd->query('SELECT * FROM contacts WHERE lu = 1 ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
} else {
    $contacts = $bdd->query('SELECT * FROM contacts ORDER BY lu ASC, created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
}

$nbNonLus = $bdd->query('SELECT COUNT(*) FROM contacts WHERE lu = 0')->fetchColumn();
?>

<main class="container" style="padding:40px 20px;">

  <!-- En-tête -->
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:12px;">
    <div>
      <h2 style="color:var(--accent); font-size:1.8rem;">📨 Messages de contact</h2>
      <p style="color:var(--muted); font-size:0.88rem;">
        <?php echo count($contacts); ?> message(s) —
        <strong style="color:var(--accent-2);"><?php echo $nbNonLus; ?> non lu(s)</strong>
      </p>
    </div>
    <a href="admin_dashboard.php" style="color:var(--muted); font-size:0.88rem; text-decoration:none;">← Dashboard</a>
  </div>

  <?php if ($flash): ?>
    <div style="background:#d4edda; color:#155724; padding:12px 18px; border-radius:8px; margin-bottom:20px;">
      <?php echo htmlspecialchars($flash); ?>
    </div>
  <?php endif; ?>

  <!-- Filtres -->
  <div style="display:flex; gap:8px; margin-bottom:24px;">
    <?php
    $filtres = ['tous' => 'Tous', 'nonlu' => '🔴 Non lus', 'lu' => '✅ Lus'];
    foreach ($filtres as $val => $label):
      $actif = $filtre === $val;
    ?>
      <a href="admin_contacts.php?filtre=<?php echo $val; ?>"
         style="padding:7px 18px; border-radius:20px; font-size:0.85rem; font-weight:600; text-decoration:none;
                background:<?php echo $actif ? 'var(--accent-4)' : 'var(--card)'; ?>;
                color:<?php echo $actif ? 'white' : 'var(--muted)'; ?>;
                box-shadow:var(--shadow);">
        <?php echo $label; ?>
      </a>
    <?php endforeach; ?>
  </div>

  <?php if (empty($contacts)): ?>
    <div style="text-align:center; padding:60px; color:var(--muted); background:var(--card); border-radius:12px;">
      📭 Aucun message ici.
    </div>

  <?php else: ?>
    <div style="display:flex; flex-direction:column; gap:20px;">
      <?php foreach ($contacts as $c):
        $aRepondu = !empty($c['reponse']);
        $estLu    = !empty($c['lu']);
        if (!$estLu)        $bordure = 'var(--accent-2)';
        elseif ($aRepondu)  $bordure = '#27ae60';
        else                $bordure = 'rgba(132,106,83,0.25)';
      ?>
        <div style="background:var(--card); border-radius:12px; padding:24px 28px; box-shadow:var(--shadow); border-left:4px solid <?php echo $bordure; ?>;">

          <!-- En-tête -->
          <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:10px; margin-bottom:14px;">
            <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
              <?php if (!$estLu): ?>
                <span style="background:var(--accent-2); color:white; font-size:0.68rem; font-weight:700; padding:2px 8px; border-radius:20px;">NOUVEAU</span>
              <?php elseif ($aRepondu): ?>
                <span style="background:#27ae60; color:white; font-size:0.68rem; font-weight:700; padding:2px 8px; border-radius:20px;">✅ RÉPONDU</span>
              <?php else: ?>
                <span style="background:rgba(132,106,83,0.15); color:var(--muted); font-size:0.68rem; font-weight:700; padding:2px 8px; border-radius:20px;">LU</span>
              <?php endif; ?>

              <strong style="color:var(--accent);"><?php echo htmlspecialchars($c['prenom'] . ' ' . $c['nom']); ?></strong>

              <?php if (!empty($c['pseudo'])): ?>
                <span style="color:var(--muted); font-size:0.85rem;">@<?php echo htmlspecialchars($c['pseudo']); ?></span>
              <?php endif; ?>
              <?php if (!empty($c['carte_code'])): ?>
                <span style="font-family:'Courier New',monospace; color:#e8c97a; background:#1a1a2e; font-size:0.75rem; padding:2px 8px; border-radius:6px;">
                  <?php echo htmlspecialchars($c['carte_code']); ?>
                </span>
              <?php endif; ?>
            </div>
            <span style="color:var(--muted); font-size:0.78rem;">
              <?php echo date('d/m/Y à H:i', strtotime($c['created_at'])); ?>
            </span>
          </div>

          <!-- Sujet -->
          <p style="color:var(--accent); font-weight:600; font-size:0.9rem; margin-bottom:12px;">
            📌 <?php echo htmlspecialchars($c['sujet']); ?>
          </p>

          <!-- Message -->
          <div style="background:rgba(132,106,83,0.05); border-radius:8px; padding:14px 16px; margin-bottom:16px;">
            <p style="color:var(--muted); font-size:0.72rem; text-transform:uppercase; letter-spacing:1px; margin-bottom:6px;">Message de l'utilisateur</p>
            <p style="color:var(--muted); font-size:0.9rem; line-height:1.6; white-space:pre-wrap;"><?php echo htmlspecialchars($c['message']); ?></p>
          </div>

          <!-- Réponse existante -->
          <?php if ($aRepondu): ?>
            <div style="background:rgba(39,174,96,0.07); border:1px solid rgba(39,174,96,0.2); border-radius:8px; padding:14px 16px; margin-bottom:16px;">
              <p style="color:#27ae60; font-size:0.72rem; font-weight:700; text-transform:uppercase; letter-spacing:1px; margin-bottom:6px;">
                🛡️ Votre réponse — <?php echo date('d/m/Y à H:i', strtotime($c['repondu_le'])); ?>
              </p>
              <p style="color:var(--muted); font-size:0.9rem; line-height:1.6; white-space:pre-wrap;"><?php echo htmlspecialchars($c['reponse']); ?></p>
            </div>
          <?php endif; ?>

          <!-- Formulaire réponse -->
          <form method="post" action="admin_contacts.php" style="margin-bottom:14px;">
            <input type="hidden" name="contact_id" value="<?php echo $c['id']; ?>">
            <input type="hidden" name="repondre" value="1">
            <label style="font-size:0.82rem; font-weight:600; color:var(--accent); display:block; margin-bottom:6px;">
              <?php echo $aRepondu ? '✏️ Modifier la réponse' : '✉️ Répondre'; ?>
            </label>
            <textarea name="reponse" rows="3" required placeholder="Tapez votre réponse..."
                      style="width:100%; padding:10px 14px; border:1px solid rgba(132,106,83,0.25); border-radius:8px; color:var(--muted); font-size:0.9rem; resize:vertical; font-family:inherit; box-sizing:border-box; margin-bottom:8px;"><?php echo htmlspecialchars($c['reponse'] ?? ''); ?></textarea>
            <button type="submit" style="background:<?php echo $aRepondu ? '#2c7be5' : '#27ae60'; ?>; color:white; padding:8px 20px; border:none; border-radius:8px; font-weight:600; cursor:pointer; font-size:0.88rem;">
              <?php echo $aRepondu ? '✏️ Modifier' : '✉️ Envoyer la réponse'; ?>
            </button>
          </form>

          <!-- Actions lu / non lu / supprimer -->
          <div style="display:flex; gap:10px; padding-top:12px; border-top:1px solid rgba(132,106,83,0.1); flex-wrap:wrap;">
            <?php if ($estLu): ?>
              <a href="admin_contacts.php?action=nonlu&id=<?php echo $c['id']; ?>"
                 style="background:rgba(132,106,83,0.1); color:var(--muted); padding:6px 14px; border-radius:6px; font-size:0.82rem; text-decoration:none; font-weight:600;">
                🔴 Marquer non lu
              </a>
            <?php else: ?>
              <a href="admin_contacts.php?action=lu&id=<?php echo $c['id']; ?>"
                 style="background:rgba(132,106,83,0.1); color:var(--muted); padding:6px 14px; border-radius:6px; font-size:0.82rem; text-decoration:none; font-weight:600;">
                ✅ Marquer comme lu
              </a>
            <?php endif; ?>
            <a href="admin_contacts.php?action=suppr&id=<?php echo $c['id']; ?>"
               onclick="return confirm('Supprimer ce message définitivement ?')"
               style="background:#c0392b; color:white; padding:6px 14px; border-radius:6px; font-size:0.82rem; text-decoration:none; font-weight:600;">
              🗑️ Supprimer
            </a>
          </div>

        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</main>

<?php require 'footer.php'; ?>
