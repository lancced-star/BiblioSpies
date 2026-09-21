<?php
require_once 'connexion-bdd.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Vérif admin uniquement
if (empty($_SESSION['user_id'])) {
    header('Location: login.php'); exit;
}

if (empty($_SESSION['is_admin'])) {
    http_response_code(403);
    die('<h1 style="font-family:monospace;color:red;padding:40px">403 — Accès refusé.</h1>');
}

// ─── STATS ────────────────────────────────────────────────────────────────────

// Total users
$totalUsers = (int) $bdd->query('SELECT COUNT(*) FROM users')->fetchColumn();

// Nouveaux inscrits 7 derniers jours
$newUsers7d = (int) $bdd->query("SELECT COUNT(*) FROM users WHERE created_at >= NOW() - INTERVAL 7 DAY")->fetchColumn();

// Total livres
$totalLivres = (int) $bdd->query('SELECT COUNT(*) FROM Livre')->fetchColumn();

// Total avis
$totalAvis = 0;
try { $totalAvis = (int) $bdd->query('SELECT COUNT(*) FROM avis')->fetchColumn(); } catch(Exception $e){}

// Total favoris
$totalFavoris = 0;
try { $totalFavoris = (int) $bdd->query('SELECT COUNT(*) FROM favoris')->fetchColumn(); } catch(Exception $e){}

// Total abonnements (suivis)
$totalSuivis = 0;
try { $totalSuivis = (int) $bdd->query('SELECT COUNT(*) FROM suivis')->fetchColumn(); } catch(Exception $e){}

// Inscrits par jour (30 derniers jours)
$inscritsParJour = [];
try {
    $rows = $bdd->query("
        SELECT DATE(created_at) as jour, COUNT(*) as nb
        FROM users
        WHERE created_at >= NOW() - INTERVAL 30 DAY
        GROUP BY DATE(created_at)
        ORDER BY jour ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) $inscritsParJour[$r['jour']] = (int)$r['nb'];
} catch(Exception $e){}

// Avis par jour (30 derniers jours)
$avisParJour = [];
try {
    $rows = $bdd->query("
        SELECT DATE(created_at) as jour, COUNT(*) as nb
        FROM avis
        WHERE created_at >= NOW() - INTERVAL 30 DAY
        GROUP BY DATE(created_at)
        ORDER BY jour ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) $avisParJour[$r['jour']] = (int)$r['nb'];
} catch(Exception $e){}

// Top 5 livres les plus mis en favoris
$topFavoris = [];
try {
    $topFavoris = $bdd->query("
        SELECT l.titre, l.isbn, COUNT(f.isbn) as nb
        FROM favoris f
        LEFT JOIN Livre l ON f.isbn = l.isbn
        GROUP BY f.isbn, l.titre
        ORDER BY nb DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e){}

// Top 5 livres les plus notés
$topNotes = [];
try {
    $topNotes = $bdd->query("
        SELECT l.titre, l.isbn, COUNT(a.id) as nb, ROUND(AVG(a.rating),1) as moy
        FROM avis a
        LEFT JOIN Livre l ON a.isbn = l.isbn
        GROUP BY a.isbn, l.titre
        ORDER BY nb DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e){}

// Derniers inscrits
$dernierUsers = $bdd->query("
    SELECT id, prenom, nom, username, created_at, is_admin
    FROM users ORDER BY created_at DESC LIMIT 8
")->fetchAll(PDO::FETCH_ASSOC);

// Derniers avis
$dernierAvis = [];
try {
    $dernierAvis = $bdd->query("
        SELECT a.id, a.contenu, a.rating, a.created_at, a.isbn,
               u.username, l.titre
        FROM avis a
        LEFT JOIN users u ON a.user_id = u.id
        LEFT JOIN Livre l ON a.isbn = l.isbn
        ORDER BY a.created_at DESC LIMIT 6
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e){}

// Ratio hommes/femmes (si champ genre existe)
$genreStats = ['H' => 0, 'F' => 0, 'autre' => 0];
try {
    $rows = $bdd->query("SELECT genre, COUNT(*) as nb FROM users GROUP BY genre")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $g = strtoupper($r['genre'] ?? '');
        if ($g === 'H' || $g === 'M') $genreStats['H'] += $r['nb'];
        elseif ($g === 'F') $genreStats['F'] += $r['nb'];
        else $genreStats['autre'] += $r['nb'];
    }
} catch(Exception $e){}

// Générer les 30 derniers jours pour le graphe
$labels = [];
$dataUsers = [];
$dataAvis  = [];
for ($i = 29; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i days"));
    $labels[]    = date('d/m', strtotime($day));
    $dataUsers[] = $inscritsParJour[$day] ?? 0;
    $dataAvis[]  = $avisParJour[$day]     ?? 0;
}

require_once 'header.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Analytics — BBSpies Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
:root {
    --bg:       #0a0a0f;
    --surface:  #111118;
    --surface2: #16161f;
    --border:   rgba(255,255,255,0.06);
    --accent:   #c9a96e;
    --accent2:  #7c6aff;
    --green:    #3ddc84;
    --red:      #ff4f6a;
    --muted:    rgba(255,255,255,0.35);
    --text:     rgba(255,255,255,0.88);
    --mono:     'Inter', monospace;
    --sans:     'Inter', sans-serif;
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
    background: var(--bg);
    color: var(--text);
    font-family: var(--sans);
    min-height: 100vh;
}

/* Grain overlay */
body::before {
    content: '';
    position: fixed;
    inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.03'/%3E%3C/svg%3E");
    pointer-events: none;
    z-index: 9999;
    opacity: .4;
}

.ana-wrap {
    max-width: 1280px;
    margin: 0 auto;
    padding: 40px 24px 80px;
}

/* Header */
.ana-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 40px;
    padding-bottom: 24px;
    border-bottom: 1px solid var(--border);
    flex-wrap: wrap;
    gap: 16px;
}

.ana-header-left h1 {
    font-family: var(--mono);
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--accent);
    letter-spacing: .12em;
    text-transform: uppercase;
}

.ana-header-left p {
    font-size: 0.75rem;
    color: var(--muted);
    margin-top: 4px;
    font-family: var(--mono);
}

.ana-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border-radius: 20px;
    border: 1px solid rgba(201,169,110,0.3);
    font-size: 0.72rem;
    font-family: var(--mono);
    color: var(--accent);
    background: rgba(201,169,110,0.07);
}

.ana-badge::before {
    content: '';
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: var(--green);
    animation: blink 2s ease infinite;
}

@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.2; }
}

/* KPI Grid */
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 14px;
    margin-bottom: 28px;
}

.kpi-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 20px 22px;
    position: relative;
    overflow: hidden;
    transition: border-color .2s, transform .2s;
}

.kpi-card:hover {
    border-color: rgba(201,169,110,0.25);
    transform: translateY(-2px);
}

.kpi-card::after {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 2px;
    background: var(--kpi-color, var(--accent));
    opacity: .7;
}

.kpi-icon {
    font-size: 1.4rem;
    margin-bottom: 12px;
    display: block;
}

.kpi-val {
    font-family: var(--mono);
    font-size: 2rem;
    font-weight: 700;
    color: var(--text);
    line-height: 1;
    margin-bottom: 6px;
}

.kpi-label {
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: .1em;
    color: var(--muted);
}

.kpi-delta {
    position: absolute;
    top: 18px; right: 18px;
    font-size: 0.7rem;
    font-family: var(--mono);
    color: var(--green);
    background: rgba(61,220,132,0.1);
    padding: 2px 8px;
    border-radius: 10px;
}

/* Section title */
.sec-title {
    font-family: var(--mono);
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: .15em;
    color: var(--muted);
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.sec-title::after {
    content: '';
    flex: 1;
    height: 1px;
    background: var(--border);
}

/* Charts row */
.charts-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 28px;
}

.chart-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 22px;
}

.chart-card h3 {
    font-size: 0.82rem;
    font-family: var(--mono);
    color: var(--accent);
    margin-bottom: 18px;
    text-transform: uppercase;
    letter-spacing: .1em;
}

.chart-wrap {
    position: relative;
    height: 200px;
}

/* Tables row */
.tables-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 28px;
}

.table-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 22px;
    overflow: hidden;
}

.table-card h3 {
    font-size: 0.82rem;
    font-family: var(--mono);
    color: var(--accent);
    margin-bottom: 16px;
    text-transform: uppercase;
    letter-spacing: .1em;
}

.ana-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.82rem;
}

.ana-table th {
    text-align: left;
    font-size: 0.65rem;
    text-transform: uppercase;
    letter-spacing: .1em;
    color: var(--muted);
    padding: 0 10px 10px 0;
    font-family: var(--mono);
    font-weight: 400;
    border-bottom: 1px solid var(--border);
}

.ana-table td {
    padding: 9px 10px 9px 0;
    border-bottom: 1px solid var(--border);
    color: var(--text);
    vertical-align: middle;
}

.ana-table tr:last-child td { border-bottom: none; }

.ana-table tr:hover td { background: rgba(255,255,255,0.02); }

.rank-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 22px;
    height: 22px;
    border-radius: 6px;
    font-size: 0.7rem;
    font-family: var(--mono);
    font-weight: 700;
    background: rgba(201,169,110,0.1);
    color: var(--accent);
}

.rank-badge.top { background: rgba(201,169,110,0.25); color: var(--accent); }

.stars {
    color: #f6b01e;
    font-size: 0.75rem;
    letter-spacing: -1px;
}

/* Feed section */
.feed-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 28px;
}

.feed-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 22px;
}

.feed-card h3 {
    font-size: 0.82rem;
    font-family: var(--mono);
    color: var(--accent);
    margin-bottom: 16px;
    text-transform: uppercase;
    letter-spacing: .1em;
}

.feed-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid var(--border);
}

.feed-item:last-child { border-bottom: none; }

.feed-avatar {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    background: linear-gradient(135deg, #c9a96e33, #7c6aff33);
    border: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    font-weight: 700;
    color: var(--accent);
    flex-shrink: 0;
    font-family: var(--mono);
}

.feed-info { flex: 1; min-width: 0; }

.feed-name {
    font-size: 0.82rem;
    font-weight: 700;
    color: var(--text);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.feed-sub {
    font-size: 0.7rem;
    color: var(--muted);
    margin-top: 2px;
    font-family: var(--mono);
}

.feed-time {
    font-size: 0.65rem;
    color: var(--muted);
    font-family: var(--mono);
    flex-shrink: 0;
}

.avis-feed-item {
    padding: 11px 0;
    border-bottom: 1px solid var(--border);
}

.avis-feed-item:last-child { border-bottom: none; }

.avis-feed-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 5px;
    gap: 10px;
}

.avis-feed-book {
    font-size: 0.8rem;
    font-weight: 700;
    color: var(--accent);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 180px;
}

.avis-feed-text {
    font-size: 0.78rem;
    color: var(--muted);
    line-height: 1.6;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Progress bar */
.progress-bar-wrap { margin-top: 6px; }
.progress-label { display: flex; justify-content: space-between; font-size: 0.72rem; color: var(--muted); font-family: var(--mono); margin-bottom: 5px; }
.progress-track { height: 5px; background: var(--surface2); border-radius: 3px; overflow: hidden; }
.progress-fill { height: 100%; border-radius: 3px; background: var(--accent); transition: width .6s ease; }

/* Responsive */
@media (max-width: 900px) {
    .charts-row, .tables-row, .feed-row { grid-template-columns: 1fr; }
}

@media (max-width: 600px) {
    .kpi-grid { grid-template-columns: 1fr 1fr; }
}
</style>

<div class="ana-wrap">

    <div class="ana-header">
        <div class="ana-header-left">
            <h1>📡 Analytics Dashboard</h1>
            <p>BBSpies — Données en temps réel · <?= date('d/m/Y H:i') ?></p>
        </div>
        <div class="ana-badge">LIVE</div>
    </div>

    <!-- KPI Cards -->
    <div class="sec-title">Vue d'ensemble</div>
    <div class="kpi-grid">
        <div class="kpi-card" style="--kpi-color:#c9a96e">
            <span class="kpi-icon">👤</span>
            <div class="kpi-val"><?= number_format($totalUsers) ?></div>
            <div class="kpi-label">Utilisateurs</div>
            <?php if ($newUsers7d > 0): ?>
            <div class="kpi-delta">+<?= $newUsers7d ?> / 7j</div>
            <?php endif; ?>
        </div>
        <div class="kpi-card" style="--kpi-color:#7c6aff">
            <span class="kpi-icon">📚</span>
            <div class="kpi-val"><?= number_format($totalLivres) ?></div>
            <div class="kpi-label">Livres</div>
        </div>
        <div class="kpi-card" style="--kpi-color:#3ddc84">
            <span class="kpi-icon">💬</span>
            <div class="kpi-val"><?= number_format($totalAvis) ?></div>
            <div class="kpi-label">Avis publiés</div>
        </div>
        <div class="kpi-card" style="--kpi-color:#ff4f6a">
            <span class="kpi-icon">❤️</span>
            <div class="kpi-val"><?= number_format($totalFavoris) ?></div>
            <div class="kpi-label">Favoris</div>
        </div>
        <div class="kpi-card" style="--kpi-color:#f6b01e">
            <span class="kpi-icon">🔗</span>
            <div class="kpi-val"><?= number_format($totalSuivis) ?></div>
            <div class="kpi-label">Abonnements</div>
        </div>
        <div class="kpi-card" style="--kpi-color:#00c9ff">
            <span class="kpi-icon">📈</span>
            <div class="kpi-val"><?= $totalUsers > 0 ? round($totalAvis / $totalUsers, 1) : 0 ?></div>
            <div class="kpi-label">Avis / user</div>
        </div>
    </div>

    <!-- Graphiques -->
    <div class="sec-title">Activité — 30 derniers jours</div>
    <div class="charts-row">
        <div class="chart-card">
            <h3>📅 Nouvelles inscriptions</h3>
            <div class="chart-wrap">
                <canvas id="chartUsers"></canvas>
            </div>
        </div>
        <div class="chart-card">
            <h3>💬 Avis publiés</h3>
            <div class="chart-wrap">
                <canvas id="chartAvis"></canvas>
            </div>
        </div>
    </div>

    <!-- Top livres -->
    <div class="sec-title">Livres populaires</div>
    <div class="tables-row">
        <div class="table-card">
            <h3>❤️ Top favoris</h3>
            <?php if (empty($topFavoris)): ?>
                <p style="color:var(--muted);font-size:0.82rem;">Aucune donnée.</p>
            <?php else: ?>
            <table class="ana-table">
                <thead>
                    <tr><th>#</th><th>Titre</th><th>Favoris</th></tr>
                </thead>
                <tbody>
                <?php foreach ($topFavoris as $i => $liv): ?>
                <tr>
                    <td><span class="rank-badge <?= $i===0?'top':'' ?>"><?= $i+1 ?></span></td>
                    <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        <a href="book-page.php?isbn=<?= urlencode($liv['isbn']) ?>"
                           style="color:var(--text);text-decoration:none;">
                            <?= htmlspecialchars($liv['titre'] ?? 'Inconnu') ?>
                        </a>
                    </td>
                    <td>
                        <div class="progress-bar-wrap">
                            <div class="progress-label"><span><?= $liv['nb'] ?></span></div>
                            <div class="progress-track">
                                <div class="progress-fill" style="width:<?= min(100, ($liv['nb'] / max(1, $topFavoris[0]['nb'])) * 100) ?>%"></div>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <div class="table-card">
            <h3>⭐ Top notés</h3>
            <?php if (empty($topNotes)): ?>
                <p style="color:var(--muted);font-size:0.82rem;">Aucune donnée.</p>
            <?php else: ?>
            <table class="ana-table">
                <thead>
                    <tr><th>#</th><th>Titre</th><th>Note</th><th>Avis</th></tr>
                </thead>
                <tbody>
                <?php foreach ($topNotes as $i => $liv): ?>
                <tr>
                    <td><span class="rank-badge <?= $i===0?'top':'' ?>"><?= $i+1 ?></span></td>
                    <td style="max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        <a href="book-page.php?isbn=<?= urlencode($liv['isbn']) ?>"
                           style="color:var(--text);text-decoration:none;">
                            <?= htmlspecialchars($liv['titre'] ?? 'Inconnu') ?>
                        </a>
                    </td>
                    <td>
                        <span style="font-family:var(--mono);font-size:0.82rem;color:var(--accent);">
                            <?= $liv['moy'] ?>
                        </span>
                        <span class="stars"> ★</span>
                    </td>
                    <td style="font-family:var(--mono);font-size:0.8rem;color:var(--muted);"><?= $liv['nb'] ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Feeds -->
    <div class="sec-title">Activité récente</div>
    <div class="feed-row">
        <div class="feed-card">
            <h3>👤 Derniers inscrits</h3>
            <?php foreach ($dernierUsers as $u): ?>
            <div class="feed-item">
                <div class="feed-avatar"><?= strtoupper(mb_substr($u['prenom'] ?? '?', 0, 1)) ?></div>
                <div class="feed-info">
                    <div class="feed-name"><?= htmlspecialchars(($u['prenom'] ?? '') . ' ' . ($u['nom'] ?? '')) ?></div>
                    <div class="feed-sub">@<?= htmlspecialchars($u['username'] ?? '') ?>
                        <?php if (!empty($u['is_admin'])): ?>
                        <span style="color:var(--accent);margin-left:5px;">● admin</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="feed-time"><?= date('d/m/Y', strtotime($u['created_at'])) ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="feed-card">
            <h3>💬 Derniers avis</h3>
            <?php if (empty($dernierAvis)): ?>
                <p style="color:var(--muted);font-size:0.82rem;">Aucun avis pour l'instant.</p>
            <?php else: ?>
            <?php foreach ($dernierAvis as $av): ?>
            <div class="avis-feed-item">
                <div class="avis-feed-top">
                    <span class="avis-feed-book"><?= htmlspecialchars($av['titre'] ?? 'Livre inconnu') ?></span>
                    <span class="stars"><?= str_repeat('★', max(1,min(5,(int)$av['rating']))) ?></span>
                    <span class="feed-time"><?= date('d/m', strtotime($av['created_at'])) ?></span>
                </div>
                <div class="avis-feed-text">
                    <span style="color:var(--accent);font-size:0.72rem;font-family:var(--mono);">@<?= htmlspecialchars($av['username'] ?? '?') ?></span>
                    — <?= htmlspecialchars($av['contenu']) ?>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php require_once 'footer.php'; ?>

<script>
const labels    = <?= json_encode($labels) ?>;
const dataUsers = <?= json_encode($dataUsers) ?>;
const dataAvis  = <?= json_encode($dataAvis) ?>;

const chartDefaults = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        x: {
            ticks: { color: 'rgba(255,255,255,0.3)', font: { family: 'Space Mono', size: 9 }, maxTicksLimit: 8 },
            grid:  { color: 'rgba(255,255,255,0.04)' }
        },
        y: {
            ticks: { color: 'rgba(255,255,255,0.3)', font: { family: 'Space Mono', size: 9 } },
            grid:  { color: 'rgba(255,255,255,0.04)' },
            beginAtZero: true,
            precision: 0
        }
    }
};

// Chart inscrits
new Chart(document.getElementById('chartUsers'), {
    type: 'bar',
    data: {
        labels,
        datasets: [{
            data: dataUsers,
            backgroundColor: 'rgba(201,169,110,0.25)',
            borderColor:     '#c9a96e',
            borderWidth: 1,
            borderRadius: 4,
        }]
    },
    options: chartDefaults
});

// Chart avis
new Chart(document.getElementById('chartAvis'), {
    type: 'line',
    data: {
        labels,
        datasets: [{
            data: dataAvis,
            borderColor:     '#7c6aff',
            backgroundColor: 'rgba(124,106,255,0.12)',
            borderWidth: 2,
            pointRadius: 3,
            pointBackgroundColor: '#7c6aff',
            fill: true,
            tension: 0.4
        }]
    },
    options: chartDefaults
});
</script>
