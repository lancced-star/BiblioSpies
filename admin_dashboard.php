<?php
require 'admin_auth.php';
require 'header.php';

// ── Répondre à un message depuis le dashboard ────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['repondre'])) {
    $id      = (int)($_POST['contact_id'] ?? 0);
    $reponse = trim($_POST['reponse'] ?? '');
    if ($id > 0 && !empty($reponse)) {
        $bdd->prepare('UPDATE contacts SET reponse = :r, repondu_le = NOW(), lu = 1 WHERE id = :id')
            ->execute([':r' => $reponse, ':id' => $id]);
        $_SESSION['flash'] = '✅ Réponse envoyée.';
    }
    header('Location: admin_dashboard.php'); exit;
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// ── Statistiques ─────────────────────────────────────────────
$nbUsers  = $bdd->query('SELECT COUNT(*) FROM users')->fetchColumn();
$nbLivres = $bdd->query('SELECT COUNT(*) FROM Livre')->fetchColumn();
try { $nbAvis = $bdd->query('SELECT COUNT(*) FROM review')->fetchColumn(); } catch(Exception $e) { $nbAvis = 0; }
try { $nbMessages = $bdd->query('SELECT COUNT(*) FROM contacts WHERE lu = 0 AND (reponse IS NULL OR reponse = "")')->fetchColumn(); } catch(Exception $e) { $nbMessages = 0; }

// ── Messages non répondus ────────────────────────────────────
try {
    $derniersMsgs = $bdd->query('
        SELECT * FROM contacts
        WHERE reponse IS NULL OR reponse = ""
        ORDER BY created_at DESC LIMIT 5
    ')->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) { $derniersMsgs = []; }

// ── Derniers avis ────────────────────────────────────────────
try {
    $derniersAvis = $bdd->query('
        SELECT r.*, l.titre FROM review r
        LEFT JOIN Livre l ON r.isbn = l.isbn
        ORDER BY r.created_at DESC LIMIT 5
    ')->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) { $derniersAvis = []; }

// ── Derniers inscrits ────────────────────────────────────────
$derniersUsers = $bdd->query('SELECT id, prenom, nom, username, created_at FROM users ORDER BY created_at DESC LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);

// ── Signalements ─────────────────────────────────────────────
try {
    $signalements = $bdd->query('
        SELECT s.*, u.username as pseudo
        FROM signalements s
        LEFT JOIN users u ON s.user_id = u.id
        WHERE s.statut = "en_attente"
        ORDER BY s.created_at DESC
    ')->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {
    $signalements = [];
}
?>

<table>
  <tbody>
    <?php foreach ($signalements as $s): ?>
    <tr>
      <td><?php echo $s['created_at']; ?></td>
      <td><?php echo htmlspecialchars($s['pseudo'] ?? 'Anonyme'); ?></td>
      <td style="color:red;"><?php echo htmlspecialchars($s['mots_detectes']); ?></td>
      <td><?php echo htmlspecialchars($s['localisation']); ?></td>
      <td><?php echo htmlspecialchars(substr($s['contenu_original'], 0, 100)); ?>...</td>
      <td>
        <a href="traiter-signalement.php?id=<?php echo $s['id']; ?>&action=traite">✅ Traité</a>
        <a href="traiter-signalement.php?id=<?php echo $s['id']; ?>&action=ignore">❌ Ignorer</a>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<main class="container" style="padding:40px 20px;">

  <div style="margin-bottom:36px;">
    <h2 style="color:var(--accent); font-size:2rem;">🛡️ Tableau de bord Admin</h2>
    <p style="color:var(--muted); font-size:0.9rem; margin-top:4px;">
      Bienvenue, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
    </p>
  </div>

  <?php if ($flash): ?>
    <div style="background:#d4edda; color:#155724; padding:12px 18px; border-radius:8px; margin-bottom:24px;">
      <?php echo htmlspecialchars($flash); ?>
    </div>
  <?php endif; ?>

  <!-- ══ STATS ══ -->
  <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:16px; margin-bottom:40px;">

    <a href="admin_users.php" style="text-decoration:none;">
      <div style="background:var(--card); border-radius:12px; padding:24px; box-shadow:var(--shadow); text-align:center; border-top:4px solid #3498db;">
        <div style="font-size:2rem; margin-bottom:8px;">👥</div>
        <div style="font-size:2rem; font-weight:800; color:#3498db;"><?php echo $nbUsers; ?></div>
        <div style="color:var(--muted); font-size:0.85rem; margin-top:4px;">Agents inscrits</div>
      </div>
    </a>

    <a href="admin_livres.php" style="text-decoration:none;">
      <div style="background:var(--card); border-radius:12px; padding:24px; box-shadow:var(--shadow); text-align:center; border-top:4px solid #27ae60;">
        <div style="font-size:2rem; margin-bottom:8px;">📚</div>
        <div style="font-size:2rem; font-weight:800; color:#27ae60;"><?php echo $nbLivres; ?></div>
        <div style="color:var(--muted); font-size:0.85rem; margin-top:4px;">Livres en catalogue</div>
      </div>
    </a>

    <a href="admin_avis.php" style="text-decoration:none;">
      <div style="background:var(--card); border-radius:12px; padding:24px; box-shadow:var(--shadow); text-align:center; border-top:4px solid #9b59b6;">
        <div style="font-size:2rem; margin-bottom:8px;">💬</div>
        <div style="font-size:2rem; font-weight:800; color:#9b59b6;"><?php echo $nbAvis; ?></div>
        <div style="color:var(--muted); font-size:0.85rem; margin-top:4px;">Avis publiés</div>
      </div>
    </a>

    <a href="admin_contacts.php" style="text-decoration:none;">
      <div style="background:var(--card); border-radius:12px; padding:24px; box-shadow:var(--shadow); text-align:center; border-top:4px solid <?php echo $nbMessages > 0 ? 'var(--accent-2)' : 'rgba(132,106,83,0.3)'; ?>; position:relative;">
        <?php if ($nbMessages > 0): ?>
          <span style="position:absolute; top:12px; right:12px; background:var(--accent-2); color:white; border-radius:50%; width:22px; height:22px; font-size:0.72rem; font-weight:700; display:flex; align-items:center; justify-content:center;">
            <?php echo $nbMessages; ?>
          </span>
        <?php endif; ?>
        <div style="font-size:2rem; margin-bottom:8px;">📨</div>
        <div style="font-size:2rem; font-weight:800; color:<?php echo $nbMessages > 0 ? 'var(--accent-2)' : 'var(--muted)'; ?>;"><?php echo $nbMessages; ?></div>
        <div style="color:var(--muted); font-size:0.85rem; margin-top:4px;">Sans réponse</div>
      </div>
    </a>

  </div>

  <!-- ══ GRILLE PRINCIPALE ══ -->
  <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px;">

    <!-- Colonne gauche -->
    <div style="display:flex; flex-direction:column; gap:24px;">

      <!-- ══ MESSAGES À RÉPONDRE ══ -->
      <div style="background:var(--card); border-radius:12px; padding:24px; box-shadow:var(--shadow);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px;">
          <h3 style="color:var(--accent); font-size:1rem;">📨 Messages sans réponse</h3>
          <a href="admin_contacts.php" style="color:var(--accent-2); font-size:0.82rem; text-decoration:none; font-weight:600;">Voir tout & répondre →</a>
        </div>

        <?php if (empty($derniersMsgs)): ?>
          <p style="color:var(--muted); font-size:0.88rem; text-align:center; padding:20px 0;">✅ Tous les messages ont une réponse</p>
        <?php else: ?>
          <div style="display:flex; flex-direction:column; gap:12px;">
          <?php foreach ($derniersMsgs as $msg): ?>
            <a href="admin_contacts.php" style="text-decoration:none;">
              <div style="border:1px solid rgba(185,33,33,0.2); border-radius:10px; padding:14px 16px; background:rgba(185,33,33,0.03); transition:background 0.2s;" onmouseover="this.style.background='rgba(185,33,33,0.07)'" onmouseout="this.style.background='rgba(185,33,33,0.03)'">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:6px;">
                  <div>
                    <span style="background:var(--accent-2); color:white; font-size:0.65rem; font-weight:700; padding:2px 7px; border-radius:20px; margin-right:6px;">NOUVEAU</span>
                    <strong style="color:var(--accent); font-size:0.9rem;">
                      <?php echo htmlspecialchars($msg['prenom'] . ' ' . $msg['nom']); ?>
                    </strong>
                    <?php if (!empty($msg['pseudo'])): ?>
                      <span style="color:var(--muted); font-size:0.8rem; margin-left:6px;">@<?php echo htmlspecialchars($msg['pseudo']); ?></span>
                    <?php endif; ?>
                  </div>
                  <span style="color:var(--muted); font-size:0.72rem; white-space:nowrap; margin-left:8px;">
                    <?php echo date('d/m H:i', strtotime($msg['created_at'])); ?>
                  </span>
                </div>
                <p style="color:var(--accent-2); font-size:0.78rem; margin-bottom:4px;">📌 <?php echo htmlspecialchars($msg['sujet']); ?></p>
                <p style="color:var(--muted); font-size:0.82rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                  <?php echo htmlspecialchars(substr($msg['message'], 0, 100)) . (strlen($msg['message']) > 100 ? '...' : ''); ?>
                </p>
              </div>
            </a>
          <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Derniers inscrits -->
      <div style="background:var(--card); border-radius:12px; padding:24px; box-shadow:var(--shadow);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
          <h3 style="color:var(--accent); font-size:1rem;">👥 Derniers agents inscrits</h3>
          <a href="admin_users.php" style="color:var(--accent-2); font-size:0.82rem; text-decoration:none; font-weight:600;">Gérer →</a>
        </div>
        <?php foreach ($derniersUsers as $u): ?>
          <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 0; border-bottom:1px solid rgba(132,106,83,0.1);">
            <div>
              <strong style="color:var(--accent); font-size:0.9rem;"><?php echo htmlspecialchars($u['prenom'] . ' ' . $u['nom']); ?></strong>
              <span style="color:var(--muted); font-size:0.82rem;"> @<?php echo htmlspecialchars($u['username']); ?></span>
            </div>
            <span style="color:var(--muted); font-size:0.75rem;"><?php echo date('d/m/Y', strtotime($u['created_at'])); ?></span>
          </div>
        <?php endforeach; ?>
      </div>

    </div>

    <!-- Colonne droite -->
    <div style="display:flex; flex-direction:column; gap:24px;">

      <!-- Derniers avis -->
      <div style="background:var(--card); border-radius:12px; padding:24px; box-shadow:var(--shadow);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
          <h3 style="color:var(--accent); font-size:1rem;">💬 Derniers avis</h3>
          <a href="admin_avis.php" style="color:var(--accent-2); font-size:0.82rem; text-decoration:none; font-weight:600;">Modérer →</a>
        </div>
        <?php if (empty($derniersAvis)): ?>
          <p style="color:var(--muted); font-size:0.88rem; text-align:center; padding:20px 0;">Aucun avis pour le moment</p>
        <?php else: ?>
          <?php foreach ($derniersAvis as $avis): ?>
            <div style="padding:12px 0; border-bottom:1px solid rgba(132,106,83,0.1);">
              <div style="display:flex; justify-content:space-between;">
                <strong style="color:var(--accent); font-size:0.88rem;"><?php echo htmlspecialchars($avis['name'] ?? 'Anonyme'); ?></strong>
                <span style="color:#f1c40f; font-size:0.82rem;">
                  <?php echo str_repeat('★', (int)($avis['rating'] ?? 0)) . str_repeat('☆', 5 - (int)($avis['rating'] ?? 0)); ?>
                </span>
              </div>
              <p style="color:var(--muted); font-size:0.78rem; margin-top:2px;">📖 <?php echo htmlspecialchars($avis['titre'] ?? $avis['isbn']); ?></p>
              <p style="color:var(--muted); font-size:0.82rem; margin-top:4px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                <?php echo htmlspecialchars(substr($avis['review'], 0, 90)) . (strlen($avis['review']) > 90 ? '...' : ''); ?>
              </p>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- Accès rapides -->
      <div style="background:var(--card); border-radius:12px; padding:24px; box-shadow:var(--shadow);">
        <h3 style="color:var(--accent); font-size:1rem; margin-bottom:16px;">⚡ Accès rapides</h3>
        <div style="display:flex; flex-direction:column; gap:10px;">
          <a href="admin_livres.php" style="display:flex; align-items:center; gap:12px; padding:12px 16px; background:rgba(39,174,96,0.08); border:1px solid rgba(39,174,96,0.2); border-radius:8px; text-decoration:none; color:var(--accent); font-size:0.9rem; font-weight:600;">
            📚 Gérer les livres <span style="margin-left:auto; color:#27ae60;">→</span>
          </a>
          <a href="admin_users.php" style="display:flex; align-items:center; gap:12px; padding:12px 16px; background:rgba(52,152,219,0.08); border:1px solid rgba(52,152,219,0.2); border-radius:8px; text-decoration:none; color:var(--accent); font-size:0.9rem; font-weight:600;">
            👥 Gérer les utilisateurs <span style="margin-left:auto; color:#3498db;">→</span>
          </a>
          <a href="admin_avis.php" style="display:flex; align-items:center; gap:12px; padding:12px 16px; background:rgba(155,89,182,0.08); border:1px solid rgba(155,89,182,0.2); border-radius:8px; text-decoration:none; color:var(--accent); font-size:0.9rem; font-weight:600;">
            💬 Modérer les avis <span style="margin-left:auto; color:#9b59b6;">→</span>
          </a>
          <a href="admin_contacts.php" style="display:flex; align-items:center; gap:12px; padding:12px 16px; background:rgba(185,33,33,0.08); border:1px solid rgba(185,33,33,0.2); border-radius:8px; text-decoration:none; color:var(--accent); font-size:0.9rem; font-weight:600;">
            📨 Tous les messages
            <?php if ($nbMessages > 0): ?>
              <span style="background:var(--accent-2); color:white; border-radius:20px; padding:1px 8px; font-size:0.75rem;"><?php echo $nbMessages; ?></span>
            <?php endif; ?>
            <span style="margin-left:auto; color:var(--accent-2);">→</span>
          </a>
          <a href="admin_livre_form.php" style="display:flex; align-items:center; gap:12px; padding:12px 16px; background:rgba(132,106,83,0.06); border:1px solid rgba(132,106,83,0.15); border-radius:8px; text-decoration:none; color:var(--accent); font-size:0.9rem; font-weight:600;">
            ➕ Ajouter un livre <span style="margin-left:auto; color:var(--muted);">→</span>
          </a>
          <a href="analytics.php" style="display:flex; align-items:center; gap:12px; padding:12px 16px; background:rgba(201,169,110,0.08); border:1px solid rgba(201,169,110,0.2); border-radius:8px; text-decoration:none; color:var(--accent); font-size:0.9rem; font-weight:600;">
            📡 Analytics <span style="margin-left:auto; color:var(--accent);">→</span>
          </a>
        </div>
      </div>

    </div>
  </div>

</main>

<?php require 'footer.php'; ?>