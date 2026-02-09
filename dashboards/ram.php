<?php
date_default_timezone_set('Europe/Paris');
require '../backend/config.php';
$stmt = $pdo->query("SELECT * FROM events ORDER BY id DESC LIMIT 100");
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

$ramUsed = [];
$labels = [];
$maxRam = 0;
$minRam = PHP_INT_MAX;
$avgRam = 0;
$warningCount = 0;
$criticalCount = 0;
$currentRam = 0;
$ramTotal = 0;

foreach ($events as $e) {
    $p = json_decode($e['payload'], true);

    if (!isset($p['ram_used_mb'], $p['ram_total_mb'])) continue;

    $used = $p['ram_used_mb'];
    $total = $p['ram_total_mb'];
    $percent = ($used / $total) * 100;

    $ramUsed[] = $percent;
    $labels[] = date("H:i", $e['ts']);

    $currentRam = $percent;
    $ramTotal = $total;

    $maxRam = max($maxRam, $percent);
    $minRam = min($minRam, $percent);

    if ($percent > 75) $warningCount++;
    if ($percent > 90) $criticalCount++;
}

$avgRam = !empty($ramUsed) ? round(array_sum($ramUsed) / count($ramUsed), 1) : 0;
$ramTrend = count($ramUsed) > 1 ? $ramUsed[0] - $ramUsed[count($ramUsed) - 1] : 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard RAM - SOC</title>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.boxicons.com/3.0.8/fonts/basic/boxicons.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/code.css">
</head>

<body style="font-family:'Poppins',sans-serif;">
<div id="app-container">
<aside id="sidebar">
    <div class="sidebar-header">
        <h2>Navigation</h2>
        <button class="new-chat-btn" onclick="location.href='../index.php'">
            <i class="bx bx-dashboard"></i> Dashboard Principal
        </button>
    </div>
    <div class="sidebar-content">
            <div class="settings-group">
                <label>Dashboards Spécialisés</label>
                <button class="profile-item" onclick="location.href='cpu.php'">
                    <i class='bx bx-chip'></i> CPU
                </button><br><br>
                <button class="profile-item" onclick="location.href='ssh.php'">
                    <i class='bx bx-shield'></i> Sécurité SSH
                </button><br><br>
                <button class="profile-item" onclick="location.href='alerts.php'">
                    <i class='bx bx-error'></i> Alertes
                </button><br><br>
                <button class="profile-item active" onclick="location.href='ram.php'">
                    <i class='bx bx-error'></i> RAM
                </button>
            </div>
    </div>
</aside>

<div class="sidebar-overlay" onclick="toggleSidebar()"></div>
<button class="menu-btn" onclick="toggleSidebar()">
    <span></span><span></span><span></span>
</button>
<div id="main-content">
<header>
    <div class="header-content">
        <div>
            <h1 class="header-title">
                <i class='bx bx-memory-card'></i> Dashboard RAM
            </h1>
            <div class="header-subtitle">Analyse détaillée de l’utilisation mémoire</div>
        </div>
        <div class="header-meta">
            <div class="last-update">Mis à jour: <?= date("d/m/Y H:i") ?></div>
            <div class="status-badge">
                <span class="status-dot"></span>
                <?= $currentRam < 75 ? 'Normal' : ($currentRam < 90 ? 'Attention' : 'Critique') ?>
            </div>
        </div>
    </div>
</header>

<div class="content-scrollable">
<div class="metrics-grid">
    <div class="metric-card">
        <div class="metric-header">
            <div>
                <div class="metric-label">RAM Actuelle</div>
                <div class="metric-value"><?= round($currentRam) ?>%</div>
                <div class="metric-change <?= $ramTrend < 0 ? 'positive' : 'negative' ?>">
                    <?= $ramTrend < 0 ? '↓' : '↑' ?> <?= abs(round($ramTrend,1)) ?>%
                </div>
            </div>
            <div class="metric-icon blue">🧠</div>
        </div>
    </div>
    <div class="metric-card">
        <div class="metric-header">
            <div>
                <div class="metric-label">RAM Moyenne</div>
                <div class="metric-value"><?= $avgRam ?>%</div>
                <div class="metric-change">100 derniers événements</div>
            </div>
            <div class="metric-icon green">📊</div>
        </div>
    </div>
    <div class="metric-card">
        <div class="metric-header">
            <div>
                <div class="metric-label">RAM Max</div>
                <div class="metric-value"><?= round($maxRam) ?>%</div>
                <div class="metric-change negative">Pic mémoire</div>
            </div>
            <div class="metric-icon red">🔥</div>
        </div>
    </div>
    <div class="metric-card">
        <div class="metric-header">
            <div>
                <div class="metric-label">Alertes RAM</div>
                <div class="metric-value"><?= $warningCount ?></div>
                <div class="metric-change negative"><?= $criticalCount ?> critiques</div>
            </div>
            <div class="metric-icon orange">⚠️</div>
        </div>
    </div>
</div>
<div class="chart-card" style="grid-column:1/-1;">
    <div class="card-header">
        <div>
            <div class="card-title">Historique RAM</div>
            <div class="card-subtitle">Utilisation mémoire (%)</div>
        </div>
    </div>
    <canvas id="ramChart" style="max-height:400px;"></canvas>
</div>
</div>
</div>
</div>
<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('active');
    document.querySelector('.sidebar-overlay').classList.toggle('active');
    document.querySelector('.menu-btn').classList.toggle('active');
}

const values = <?= json_encode(array_reverse($ramUsed)) ?>;
const labels = <?= json_encode(array_reverse($labels)) ?>;

const ctx = document.getElementById('ramChart').getContext('2d');
const gradient = ctx.createLinearGradient(0,0,0,400);
gradient.addColorStop(0,'rgba(74,158,255,0.3)');
gradient.addColorStop(1,'rgba(74,158,255,0.05)');

new Chart(ctx,{
    type:'line',
    data:{
        labels:labels,
        datasets:[{
            label:'RAM (%)',
            data:values,
            borderColor:'#4a9eff',
            backgroundColor:gradient,
            fill:true,
            tension:0.4,
            pointBackgroundColor: values.map(v => v > 90 ? '#ef5350' : (v > 75 ? '#ffa726' : '#4a9eff')),
            pointBorderColor:'#fff',
            pointBorderWidth:2,
            pointRadius:5
        }]
    },
    options:{
        responsive:true,
        plugins:{ legend:{ display:true }},
        scales:{
            y:{
                min:0,
                max:100,
                ticks:{ callback:v=>v+'%' }
            }
        }
    }
});
setTimeout(()=>location.reload(),60000);
</script>
</body>
</html>
