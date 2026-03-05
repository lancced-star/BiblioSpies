<?php
require 'admin_auth.php';
require 'header.php';

// ── Actions ──────────────────────────────────────────────────
if (isset($_GET['action']) && isset($_GET['id'])) {
    $uid    = (int)$_GET['id'];
    $action = $_GET['action'];

    // On ne peut pas s'auto-modifier
    if ($uid === (int)$_SESSION['user_id']) {
        $_SESSION['flash'] = '⚠️ Vous ne pouvez pas modifier votre propre compte ici.';
        header('Location: admin_users.php'); exit;
    }

    if ($action === 'bannir') {
        $bdd->prepare('UPDATE users SET is_banned = 1 WHERE id = :id')->execute([':id' => $uid]);
        $_SESSION['flash'] = '🚫 Utilisateur banni.';
    } elseif ($action === 'debannir') {
        $bdd->prepare('UPDATE users SET is_banned = 0 WHERE id = :id')->execute([':id' => $uid]);
        $_SESSION['flash'] = '✅ Utilisateur débanni.';
    } elseif ($action === 'supprimer') {
        // Supprimer ses avis aussi
        try { $bdd->prepare('DELETE FROM review WHERE name = (SELECT username FROM users WHERE id = :id)')->execute([':id' => $uid]); } catch(Exception $e) {}
        $bdd->prepare('DELETE FROM users WHERE id = :id')->execute([':id' => $uid]);
        $_SESSION['flash'] = '🗑️ Utilisateur supprimé.';
    } elseif ($action === 'admin') {
        $bdd->prepare('UPDATE users SET is_admin = 1 WHERE id = :id')->execute([':id' => $uid]);
        $_SESSION['flash'] = '🛡️ Utilisateur promu administrateur.';
    } elseif ($action === 'deadmin') {
        $bdd->prepare('UPDATE users SET is_admin = 0 WHERE id = :id')->execute([':id' => $uid]);
        $_SESSION['flash'] = '👤 Droits admin retirés.';
    }
    header('Location: admin_users.php'); exit;
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// ── Recherche ─────────────────────────────────────────────────
$search = trim($_GET['q'] ?? '');
if ($search) {
    $stmt = $bdd->prepare('SELECT * FROM users WHERE username LIKE :q OR prenom LIKE :q OR nom LIKE :q ORDER BY created_at DESC');
    $stmt->execute([':q' => "%$search%"]);
} else {
    $stmt = $bdd->query('SELECT * FROM users ORDER BY created_at DESC');
}
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="container" style="padding:40px 20px;">

  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:12px;">
    <div>
      <h2 style="color:var(--accent); font-size:1.8rem;">👥 Gestion des utilisateurs</h2>
      <p style="color:var(--muted); font-size:0.88rem;"><?php echo count($users); ?> agent(s)</p>
    </div>
    <a href="admin_dashboard.php" style="color:var(--muted); font-size:0.88rem; text-decoration:none;">← Dashboard</a>
  </div>

  <?php if ($flash): ?>
    <div style="background:#d4edda; color:#155724; padding:12px 18px; border-radius:8px; margin-bottom:20px;">
      <?php echo htmlspecialchars($flash); ?>
    </div>
  <?php endif; ?>

  <!-- Barre de recherche -->
  <form method="get" action="admin_users.php" style="margin-bottom:24px; display:flex; gap:10px;">
    <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>"
           placeholder="Rechercher par pseudo, prénom ou nom..."
           style="flex:1; padding:10px 16px; border:1px solid rgba(132,106,83,0.3); border-radius:8px; background:var(--bg); color:var(--muted); font-size:0.9rem;">
    <button type="submit" style="background:var(--accent); color:white; padding:10px 20px; border:none; border-radius:8px; cursor:pointer; font-weight:600;">🔍</button>
    <?php if ($search): ?><a href="admin_users.php" style="padding:10px 16px; border-radius:8px; border:1px solid rgba(132,106,83,0.3); color:var(--muted); text-decoration:none; font-size:0.9rem;">✕</a><?php endif; ?>
  </form>

  <!-- Tableau -->
  <div style="overflow-x:auto;">
    <table style="width:100%; border-collapse:collapse; background:var(--card); border-radius:12px; overflow:hidden; box-shadow:var(--shadow);">
      <thead>
        <tr style="background:var(--accent); color:white; text-align:left;">
          <th style="padding:14px 16px;">Agent</th>
          <th style="padding:14px 16px;">Pseudo</th>
          <th style="padding:14px 16px;">Code carte</th>
          <th style="padding:14px 16px;">Inscrit le</th>
          <th style="padding:14px 16px;">Statut</th>
          <th style="padding:14px 16px; text-align:center;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $i => $u):
          $isSelf   = $u['id'] == $_SESSION['user_id'];
          $isBanned = !empty($u['is_banned']);
          $isAdmin  = !empty($u['is_admin']);
        ?>
        <tr style="border-bottom:1px solid rgba(132,106,83,0.1); <?php echo $i%2===0?'':'background:rgba(132,106,83,0.03)'; ?> <?php echo $isBanned?'opacity:0.6':''; ?>">
          <td style="padding:12px 16px;">
            <strong style="color:var(--accent);"><?php echo htmlspecialchars($u['prenom'] . ' ' . $u['nom']); ?></strong>
            <?php if ($isSelf): ?><span style="font-size:0.7rem; background:#27ae60; color:white; padding:1px 6px; border-radius:10px; margin-left:6px;">Vous</span><?php endif; ?>
          </td>
          <td style="padding:12px 16px; font-family:'Courier New',monospace; font-size:0.88rem; color:var(--muted);">
            @<?php echo htmlspecialchars($u['username']); ?>
          </td>
          <td style="padding:12px 16px; font-family:'Courier New',monospace; font-size:0.78rem; color:var(--muted);">
            <?php echo htmlspecialchars($u['carte_code']); ?>
          </td>
          <td style="padding:12px 16px; font-size:0.82rem; color:var(--muted);">
            <?php echo date('d/m/Y', strtotime($u['created_at'])); ?>
          </td>
          <td style="padding:12px 16px;">
            <?php if ($isBanned): ?>
              <span style="background:#c0392b; color:white; font-size:0.72rem; padding:3px 8px; border-radius:10px; font-weight:700;">🚫 Banni</span>
            <?php elseif ($isAdmin): ?>
              <span style="background:#2c7be5; color:white; font-size:0.72rem; padding:3px 8px; border-radius:10px; font-weight:700;">🛡️ Admin</span>
            <?php else: ?>
              <span style="background:rgba(39,174,96,0.15); color:#27ae60; font-size:0.72rem; padding:3px 8px; border-radius:10px; font-weight:700;">✅ Actif</span>
            <?php endif; ?>
          </td>
          <td style="padding:12px 16px; text-align:center; white-space:nowrap;">
            <?php if (!$isSelf): ?>
              <?php if ($isBanned): ?>
                <a href="admin_users.php?action=debannir&id=<?php echo $u['id']; ?>"
                   style="background:#27ae60; color:white; padding:5px 10px; border-radius:6px; font-size:0.78rem; text-decoration:none; margin:2px;">✅ Débannir</a>
              <?php else: ?>
                <a href="admin_users.php?action=bannir&id=<?php echo $u['id']; ?>"
                   onclick="return confirm('Bannir @<?php echo addslashes($u['username']); ?> ?')"
                   style="background:#e67e22; color:white; padding:5px 10px; border-radius:6px; font-size:0.78rem; text-decoration:none; margin:2px;">🚫 Bannir</a>
              <?php endif; ?>
              <?php if (!$isAdmin): ?>
                <a href="admin_users.php?action=admin&id=<?php echo $u['id']; ?>"
                   onclick="return confirm('Promouvoir @<?php echo addslashes($u['username']); ?> admin ?')"
                   style="background:#2c7be5; color:white; padding:5px 10px; border-radius:6px; font-size:0.78rem; text-decoration:none; margin:2px;">🛡️ Admin</a>
              <?php else: ?>
                <a href="admin_users.php?action=deadmin&id=<?php echo $u['id']; ?>"
                   onclick="return confirm('Retirer les droits admin ?')"
                   style="background:#7f8c8d; color:white; padding:5px 10px; border-radius:6px; font-size:0.78rem; text-decoration:none; margin:2px;">👤 Retirer</a>
              <?php endif; ?>
              <a href="admin_users.php?action=supprimer&id=<?php echo $u['id']; ?>"
                 onclick="return confirm('Supprimer définitivement @<?php echo addslashes($u['username']); ?> ?')"
                 style="background:#c0392b; color:white; padding:5px 10px; border-radius:6px; font-size:0.78rem; text-decoration:none; margin:2px;">🗑️</a>
            <?php else: ?>
              <span style="color:var(--muted); font-size:0.78rem;">—</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

</main>

<?php require 'footer.php'; ?>
