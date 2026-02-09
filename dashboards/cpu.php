<?php
date_default_timezone_set('Europe/Paris');
require '../backend/config.php';

// Récupération de tous les événements CPU
$stmt = $pdo->query("SELECT * FROM events ORDER BY id DESC LIMIT 100");
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

$cpuTemps = [];
$labels = [];
$maxTemp = 0;
$minTemp = 100;
$avgTemp = 0;
$warningCount = 0;
$criticalCount = 0;

foreach($events as $e) {
    $p = json_decode($e['payload'], true);
    $temp = $p['cpu_temp_c'];
    $cpuTemps[] = $temp;
    $labels[] = date("H:i", $e['ts']);
    
    if ($temp > $maxTemp) $maxTemp = $temp;
    if ($temp < $minTemp) $minTemp = $temp;
    if ($temp > 75) $warningCount++;
    if ($temp > 85) $criticalCount++;
}

$avgTemp = !empty($cpuTemps) ? round(array_sum($cpuTemps) / count($cpuTemps), 1) : 0;
$currentTemp = end(array_reverse($cpuTemps)) ?: 0;
$tempTrend = count($cpuTemps) > 1 ? ($cpuTemps[0] - $cpuTemps[count($cpuTemps)-1]) : 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard CPU - SOC</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.boxicons.com/3.0.8/fonts/basic/boxicons.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/code.css">
</head>
<body style="font-family: 'Poppins', sans-serif;">

<div id="app-container">
    <!-- Sidebar -->
    <aside id="sidebar">
        <div class="sidebar-header">
            <h2>Navigation</h2>
            <button class="new-chat-btn" onclick="location.href='../index.php'">
                <i class="bx bx-dashboard"></i>Dashboard Principal
            </button>
        </div>
        <div class="sidebar-content">
            <div class="settings-group">
                <label>Dashboards Spécialisés</label>
                <button class="profile-item active" onclick="location.href='cpu.php'">
                    <i class='bx bx-chip'></i> CPU
                </button><br><br>
                <button class="profile-item" onclick="location.href='ssh.php'">
                    <i class='bx bx-shield'></i> Sécurité SSH
                </button><br><br>
                <button class="profile-item" onclick="location.href='alerts.php'">
                    <i class='bx bx-error'></i> Alertes
                </button><br><br>
                <button class="profile-item" onclick="location.href='ram.php'">
                    <i class='bx bx-error'></i> RAM
                </button>
            </div>
        </div>
        <div class="sidebar-footer">
            <button class="new-chat-btn" onclick="location.href='../settings.php'">
                <i class="bx bx-gear"></i>Settings
            </button>
        </div>
    </aside>

    <div class="sidebar-overlay" onclick="toggleSidebar()"></div>
    <button class="menu-btn" onclick="toggleSidebar()">
        <span></span><span></span><span></span>
    </button>

    <!-- Main Content -->
    <div id="main-content">
        <header>
            <div class="header-content">
                <div>
                    <h1 class="header-title">
                        <i class='bx bx-chip'></i> Dashboard CPU
                    </h1>
                    <div class="header-subtitle">Analyse détaillée de la température du processeur</div>
                </div>
                <div class="header-meta">
                    <div class="last-update">Mis à jour: <?= date("d/m/Y H:i") ?></div>
                    <div class="status-badge">
                        <span class="status-dot"></span>
                        <?= $currentTemp < 75 ? 'Normal' : ($currentTemp < 85 ? 'Attention' : 'Critique') ?>
                    </div>
                </div>
            </div>
        </header>

        <div class="content-scrollable">
            <!-- Metrics Grid -->
            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-header">
                        <div>
                            <div class="metric-label">Température Actuelle</div>
                            <div class="metric-value"><?= $currentTemp ?>°C</div>
                            <div class="metric-change <?= $tempTrend > 0 ? 'positive' : 'negative' ?>">
                                <?= $tempTrend > 0 ? '↓' : '↑' ?> <?= abs(round($tempTrend, 1)) ?>°C
                            </div>
                        </div>
                        <div class="metric-icon blue">🌡️</div>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-header">
                        <div>
                            <div class="metric-label">Température Moyenne</div>
                            <div class="metric-value"><?= $avgTemp ?>°C</div>
                            <div class="metric-change">Sur 100 derniers événements</div>
                        </div>
                        <div class="metric-icon green">📊</div>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-header">
                        <div>
                            <div class="metric-label">Température Max</div>
                            <div class="metric-value"><?= $maxTemp ?>°C</div>
                            <div class="metric-change negative">Pic enregistré</div>
                        </div>
                        <div class="metric-icon red">🔥</div>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-header">
                        <div>
                            <div class="metric-label">Alertes Température</div>
                            <div class="metric-value"><?= $warningCount ?></div>
                            <div class="metric-change negative">Dont <?= $criticalCount ?> critiques</div>
                        </div>
                        <div class="metric-icon orange">⚠️</div>
                    </div>
                </div>
            </div>

            <!-- Graphiques -->
            <div class="content-grid">
                <div class="chart-card" style="grid-column: 1 / -1;">
                    <div class="card-header">
                        <div>
                            <div class="card-title">Historique de température CPU</div>
                            <div class="card-subtitle">100 derniers événements</div>
                        </div>
                    </div>
                    <canvas id="cpuChart" style="max-height: 400px;"></canvas>
                </div>
            </div>

            <!-- Statistiques détaillées -->
            <div class="events-card">
                <div class="card-header">
                    <div>
                        <div class="card-title">Statistiques détaillées</div>
                        <div class="card-subtitle">Analyse de performance</div>
                    </div>
                </div>
                <div class="summary-items" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">
                    <div class="summary-item">
                        <span class="summary-item-label">Température Min</span>
                        <span class="summary-item-value success"><?= $minTemp ?>°C</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-item-label">Écart type</span>
                        <span class="summary-item-value"><?= round(sqrt(array_sum(array_map(function($x) use ($avgTemp) { return pow($x - $avgTemp, 2); }, $cpuTemps)) / count($cpuTemps)), 2) ?>°C</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-item-label">Stabilité</span>
                        <span class="summary-item-value <?= ($maxTemp - $minTemp) < 10 ? 'success' : 'warning' ?>">
                            <?= ($maxTemp - $minTemp) < 10 ? 'Excellente' : 'Moyenne' ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Historique récent -->
            <div class="events-card">
                <div class="card-header">
                    <div>
                        <div class="card-title">Événements récents</div>
                        <div class="card-subtitle">20 dernières mesures</div>
                    </div>
                </div>
                <div class="table-container">
                    <table class="events-table">
                        <thead>
                            <tr>
                                <th>Horodatage</th>
                                <th>Température</th>
                                <th>État</th>
                                <th>Variation</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $recentEvents = array_slice(array_reverse($events), 0, 20);
                            $prevTemp = null;
                            foreach($recentEvents as $e):
                                $p = json_decode($e['payload'], true);
                                $temp = $p['cpu_temp_c'];
                                $variation = $prevTemp ? $temp - $prevTemp : 0;
                                $prevTemp = $temp;
                            ?>
                            <tr>
                                <td class="event-time"><?= date("d/m/Y H:i:s", $e['ts']) ?></td>
                                <td class="event-temp" style="font-weight: 600; color: <?= $temp > 85 ? '#ef5350' : ($temp > 75 ? '#ffa726' : '#3fb980') ?>">
                                    <?= $temp ?>°C
                                </td>
                                <td>
                                    <?php if ($temp > 85): ?>
                                        <span class="risk-badge risk-high">CRITIQUE</span>
                                    <?php elseif ($temp > 75): ?>
                                        <span class="risk-badge risk-medium">ATTENTION</span>
                                    <?php else: ?>
                                        <span class="risk-badge risk-low">NORMAL</span>
                                    <?php endif; ?>
                                </td>
                                <td style="color: <?= $variation > 0 ? '#ef5350' : ($variation < 0 ? '#3fb980' : '#8792a2') ?>">
                                    <?= $variation > 0 ? '↑' : ($variation < 0 ? '↓' : '→') ?> <?= abs(round($variation, 1)) ?>°C
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
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

const temps = <?= json_encode(array_reverse($cpuTemps)) ?>;
const labels = <?= json_encode(array_reverse($labels)) ?>;

const ctx = document.getElementById('cpuChart').getContext('2d');
const gradient = ctx.createLinearGradient(0, 0, 0, 400);
gradient.addColorStop(0, 'rgba(74, 158, 255, 0.3)');
gradient.addColorStop(1, 'rgba(74, 158, 255, 0.05)');

new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Température CPU (°C)',
            data: temps,
            borderColor: '#4a9eff',
            backgroundColor: gradient,
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: temps.map(t => t > 85 ? '#ef5350' : (t > 75 ? '#ffa726' : '#4a9eff')),
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 5,
            pointHoverRadius: 7
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { display: true, position: 'top' },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.parsed.y + '°C';
                    }
                }
            }
        },
        scales: {
            x: { 
                grid: { display: false },
                ticks: { color: '#8792a2', maxRotation: 45 }
            },
            y: {
                min: Math.min(...temps) - 10,
                max: Math.max(...temps) + 10,
                ticks: { 
                    color: '#8792a2',
                    callback: function(value) { return value + '°C'; }
                },
                grid: { color: 'rgba(255,255,255,0.05)' }
            }
        }
    }
});

setTimeout(() => location.reload(), 60000);
</script>
</body>
</html>