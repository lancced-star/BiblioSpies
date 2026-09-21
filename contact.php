<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require 'connexion-bdd.php';
require 'header.php';

// Pré-remplir si l'utilisateur est connecté
$pseudo_connecte = $_SESSION['username'] ?? '';
?>

<main class="auth-wrapper">
  <section class="auth-card" style="max-width:560px;">

    <div style="text-align:center; margin-bottom:1.5rem;">
      <h2>📨 Contacter l'Agence</h2>
      <p style="color:var(--muted); font-size:0.88rem; margin-top:6px;">
        Une question, un problème d'accès ? Laissez-nous un message.
      </p>
    </div>

    <?php if (!empty($_SESSION['flash_contact_ok'])): ?>
      <div style="background:#d4edda; color:#155724; padding:16px; border-radius:10px; text-align:center; margin-bottom:1rem;">
        <p style="font-size:1rem; font-weight:600;">✅ Message envoyé !</p>
        <p style="font-size:0.88rem; margin-top:6px;"><?php echo htmlspecialchars($_SESSION['flash_contact_ok']); unset($_SESSION['flash_contact_ok']); ?></p>
        <a href="index.php" style="display:inline-block; margin-top:14px; background:var(--accent); color:white; padding:8px 24px; border-radius:8px; font-size:0.88rem; text-decoration:none; font-weight:600;">← Retour à l'accueil</a>
      </div>

    <?php else: ?>

      <?php if (!empty($_SESSION['flash_contact_err'])): ?>
        <div style="background:#f8d7da; color:#721c24; padding:12px 16px; border-radius:8px; margin-bottom:1rem; font-size:0.9rem;">
          <?php echo htmlspecialchars($_SESSION['flash_contact_err']); unset($_SESSION['flash_contact_err']); ?>
        </div>
      <?php endif; ?>

      <form action="contact_save.php" method="post" class="auth-form">

        <!-- Prénom + Nom -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
          <div class="form-group">
            <label>Prénom *</label>
            <input type="text" name="prenom" required placeholder="Ex : James"
                   value="<?php echo htmlspecialchars($_SESSION['old_contact']['prenom'] ?? ''); ?>">
          </div>
          <div class="form-group">
            <label>Nom *</label>
            <input type="text" name="nom" required placeholder="Ex : Bond"
                   value="<?php echo htmlspecialchars($_SESSION['old_contact']['nom'] ?? ''); ?>">
          </div>
        </div>

        <!-- Pseudo + Code carte (tous les deux optionnels) -->
        <div style="background:rgba(132,106,83,0.06); border-radius:10px; padding:16px; margin-bottom:4px;">
          <p style="color:var(--accent); font-size:0.82rem; font-weight:600; margin-bottom:12px;">
            🔎 Pour retrouver votre compte <span style="font-weight:400; color:var(--muted);">(remplissez l'un, l'autre, ou les deux)</span>
          </p>
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
            <div class="form-group" style="margin-bottom:0;">
              <label>Pseudo</label>
              <input type="text" name="pseudo" placeholder="Ex : Agent007"
                     value="<?php echo htmlspecialchars($_SESSION['old_contact']['pseudo'] ?? $pseudo_connecte); ?>">
            </div>
            <div class="form-group" style="margin-bottom:0;">
              <label>Code de carte</label>
              <input type="text" name="carte_code" placeholder="Ex : BSP-AGE-4f9a2b1c"
                     style="font-family:'Courier New',monospace; letter-spacing:1px;"
                     value="<?php echo htmlspecialchars($_SESSION['old_contact']['carte_code'] ?? ''); ?>">
            </div>
          </div>
          <small style="color:var(--muted); font-size:0.78rem; display:block; margin-top:8px;">
            Ces champs sont facultatifs — ils aident l'admin à retrouver votre compte plus rapidement.
          </small>
        </div>

        <!-- Sujet -->
        <div class="form-group">
          <label>Sujet *</label>
          <select name="sujet" required
                  style="width:100%; padding:0.8rem; border-radius:8px; border:1px solid #000000; background:var(--accent-5); color:var(--accent); outline: none;">
            <option value="">— Choisissez un sujet —</option>
            <?php 
            // Vérifier si l'utilisateur est admin ou a "admin" dans son nom
            $isAdminOrAdminName = !empty($_SESSION['is_admin']);
            if (!$isAdminOrAdminName && isset($_SESSION['user_id'])) {
                $nomComplet = strtolower(($_SESSION['prenom'] ?? '') . ' ' . ($_SESSION['nom'] ?? '') . ' ' . ($_SESSION['username'] ?? ''));
                $isAdminOrAdminName = strpos($nomComplet, 'admin') !== false;
            }
            if (!$isAdminOrAdminName): 
            ?>
            <option value="Accès perdu (pseudo/code oublié)"    <?php echo (($_SESSION['old_contact']['sujet'] ?? '') === 'Accès perdu (pseudo/code oublié)')    ? 'selected' : ''; ?>>🔐 Accès perdu (pseudo/code oublié)</option>
            <?php endif; ?>
            <option value="Problème avec mon compte"            <?php echo (($_SESSION['old_contact']['sujet'] ?? '') === 'Problème avec mon compte')            ? 'selected' : ''; ?>>👤 Problème avec mon compte</option>
            <option value="Signaler un contenu"                 <?php echo (($_SESSION['old_contact']['sujet'] ?? '') === 'Signaler un contenu')                 ? 'selected' : ''; ?>>🚩 Signaler un contenu</option>
            <option value="Question générale"                   <?php echo (($_SESSION['old_contact']['sujet'] ?? '') === 'Question générale')                   ? 'selected' : ''; ?>>💬 Question générale</option>
            <option value="Autre"                               <?php echo (($_SESSION['old_contact']['sujet'] ?? '') === 'Autre')                               ? 'selected' : ''; ?>>📌 Autre</option>
          </select>
        </div>

        <!-- Message -->
        <div class="form-group">
          <label>Message *</label>
          <textarea name="message" rows="5" required
                    placeholder="Décrivez votre demande..."
                    style="width:100%; padding:0.8rem; border-radius:8px; border:1px solid #000000; background:var(--accent-5); color:var(--accent); outline:none; resize:vertical; "><?php echo htmlspecialchars($_SESSION['old_contact']['message'] ?? ''); ?></textarea>
        </div>

        <?php unset($_SESSION['old_contact']); ?>

        <div style="text-align: center;">
  <button type="submit" style="padding:11px 24px;background: var(--accent-3); font-size:0.9rem;color: white;cursor: pointer;border-radius:8px;font-weight: 600; text-align:center;width:100%;">
    📨 Envoyer le message
  </button>
</div>

      </form>

    <?php endif; ?>

  </section>
</main>

<?php require 'footer.php'; ?>
