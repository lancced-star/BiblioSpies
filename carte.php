<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['nouvelle_carte'])) { header('Location: register.php'); exit; }

$carte = $_SESSION['nouvelle_carte'];
unset($_SESSION['nouvelle_carte']); // affichée une seule fois

require 'header.php';
?>

<main class="container" style="padding:40px 20px; text-align:center;">

  <h2 style="color:var(--accent); font-size:1.6rem; margin-bottom:8px;">
    🎉 Bienvenue dans l'Agence, <?php echo htmlspecialchars($carte['prenom']); ?> !
  </h2>
  <p style="color:var(--muted); margin-bottom:36px;">
    Votre carte d'agent est prête. <strong>Notez votre code</strong> — il est votre seul moyen de vous connecter.
  </p>

  <!-- ══ CARTE D'AGENT ══ -->
  <div style="
    display:inline-block;
    background:linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
    border-radius:18px; padding:36px 48px;
    box-shadow:0 20px 60px rgba(0,0,0,0.4);
    min-width:360px; max-width:460px;
    position:relative; overflow:hidden; margin-bottom:28px;
  ">
    <!-- Cercles déco -->
    <div style="position:absolute;top:-40px;right:-40px;width:180px;height:180px;border-radius:50%;background:rgba(185,33,33,0.12);pointer-events:none;"></div>
    <div style="position:absolute;bottom:-60px;left:-30px;width:200px;height:200px;border-radius:50%;background:rgba(58,31,5,0.18);pointer-events:none;"></div>

    <!-- En-tête carte -->
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:24px;">
      <img src="logoimage.png" alt="logo" style="height:34px;filter:brightness(0) invert(1);opacity:0.85;">
      <span style="color:rgba(255,255,255,0.45);font-size:0.72rem;letter-spacing:3px;text-transform:uppercase;">Carte d'Agent</span>
    </div>

    <!-- Identité -->
    <div style="text-align:left;margin-bottom:28px;">
      <p style="color:rgba(255,255,255,0.45);font-size:0.68rem;letter-spacing:2px;text-transform:uppercase;margin-bottom:4px;">Identité</p>
      <p style="color:white;font-size:1.5rem;font-weight:700;">
        <?php echo htmlspecialchars($carte['prenom'] . ' ' . strtoupper($carte['nom'])); ?>
      </p>
      <p style="color:rgba(255,255,255,0.55);font-size:0.9rem;margin-top:4px;">
        @<?php echo htmlspecialchars($carte['username']); ?>
      </p>
    </div>

    <!-- Code carte -->
    <div style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.12);border-radius:10px;padding:16px 20px;text-align:left;">
      <p style="color:rgba(255,255,255,0.4);font-size:0.65rem;letter-spacing:2.5px;text-transform:uppercase;margin-bottom:8px;">Code d'identification</p>
      <p id="code-carte" style="color:#e8c97a;font-family:'Courier New',monospace;font-size:1.3rem;font-weight:700;letter-spacing:3px;">
        <?php echo htmlspecialchars($carte['carteCode']); ?>
      </p>
    </div>

    <p style="color:rgba(255,255,255,0.18);font-size:0.62rem;margin-top:20px;letter-spacing:1px;text-transform:uppercase;">
      BIBLIOSPIES — CLASSIFICATION : CONFIDENTIEL
    </p>
  </div>

  <!-- Actions -->
  <div style="display:flex;flex-direction:column;align-items:center;gap:12px;">

    <button onclick="copierCode()" style="
      background:rgba(232,201,122,0.12);border:1px solid rgba(232,201,122,0.4);
      color:#e8c97a;padding:10px 28px;border-radius:8px;
      cursor:pointer;font-size:0.9rem;font-weight:600;">
      📋 Copier mon code
    </button>

    <a href="login.php" style="
      background:var(--accent-2);color:white;
      padding:12px 36px;border-radius:10px;
      font-weight:700;font-size:1rem;text-decoration:none;">
      🔐 Se connecter avec ma carte →
    </a>

    <p style="color:var(--muted);font-size:0.8rem;">
      ⚠️ Cette page ne s'affiche qu'une seule fois — notez votre code maintenant.
    </p>
  </div>
</main>

<script>
function copierCode() {
  const code = document.getElementById('code-carte').innerText;
  navigator.clipboard.writeText(code).then(() => {
    alert('✅ Code copié dans le presse-papiers !');
  }).catch(() => {
    const el = document.createElement('textarea');
    el.value = code; document.body.appendChild(el);
    el.select(); document.execCommand('copy');
    document.body.removeChild(el);
    alert('✅ Code copié !');
  });
}
</script>

<?php require 'footer.php'; ?>
