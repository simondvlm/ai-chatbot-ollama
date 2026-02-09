<?php
date_default_timezone_set('Europe/Paris');
require '../backend/config.php';

$stmt = $pdo->query("SELECT * FROM events ORDER BY id DESC LIMIT 100");
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
$sshCounts = [];
$labels = [];
$totalSSH = 0;
$maxSSH = 0;
$avgSSH = 0;
$dangerousEvents = 0;
foreach($events as $e) {
    $p = json_decode($e['payload'], true);
    $ssh = $p['ssh_failed_count'];
    $sshCounts[] = $ssh;
    $labels[] = date("H:i", $e['ts']);
    $totalSSH += $ssh;
    
    if ($ssh > $maxSSH) $maxSSH = $ssh;
    if ($ssh > 10) $dangerousEvents++;
}

$avgSSH = !empty($sshCounts) ? round(array_sum($sshCounts) / count($sshCounts), 1) : 0;
$currentSSH = end(array_reverse($sshCounts)) ?: 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard SSH - SOC</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.boxicons.com/3.0.8/fonts/basic/boxicons.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/code.css">
</head>
<body style="font-family: 'Poppins', sans-serif;">

<div id="app-container">
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
                <button class="profile-item" onclick="location.href='cpu.php'">
                    <i class='bx bx-chip'></i> CPU
                </button><br><br>
                <button class="profile-item active" onclick="location.href='ssh.php'">
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

    <div id="main-content">
        <header>
            <div class="header-content">
                <div>
                    <h1 class="header-title">
                        <i class='bx bx-shield'></i> Dashboard Sécurité SSH
                    </h1>
                    <div class="header-subtitle">Surveillance des tentatives de connexion</div>
                </div>
                <div class="header-meta">
                    <div class="last-update">Mis à jour: <?= date("d/m/Y H:i") ?></div>
                    <div class="status-badge">
                        <span class="status-dot"></span>
                        <?= $currentSSH < 5 ? 'Sécurisé' : ($currentSSH < 15 ? 'Surveillance' : 'Alerte') ?>
                    </div>
                </div>
            </div>
        </header>

        <div class="content-scrollable">
            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-header">
                        <div>
                            <div class="metric-label">Tentatives Actuelles</div>
                            <div class="metric-value"><?= $currentSSH ?></div>
                            <div class="metric-change negative">Échecs SSH récents</div>
                        </div>
                        <div class="metric-icon red">🔐</div>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-header">
                        <div>
                            <div class="metric-label">Total Échecs</div>
                            <div class="metric-value"><?= $totalSSH ?></div>
                            <div class="metric-change">Sur 100 derniers événements</div>
                        </div>
                        <div class="metric-icon orange">📊</div>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-header">
                        <div>
                            <div class="metric-label">Moyenne</div>
                            <div class="metric-value"><?= $avgSSH ?></div>
                            <div class="metric-change">Tentatives / événement</div>
                        </div>
                        <div class="metric-icon blue">📈</div>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-header">
                        <div>
                            <div class="metric-label">Événements Dangereux</div>
                            <div class="metric-value"><?= $dangerousEvents ?></div>
                            <div class="metric-change negative">> 10 tentatives</div>
                        </div>
                        <div class="metric-icon red">⚠️</div>
                    </div>
                </div>
            </div>

            <div class="content-grid">
                <div class="chart-card" style="grid-column: 1 / -1;">
                    <div class="card-header">
                        <div>
                            <div class="card-title">Historique des tentatives SSH</div>
                            <div class="card-subtitle">100 derniers événements</div>
                        </div>
                    </div>
                    <canvas id="sshChart" style="max-height: 400px;"></canvas>
                </div>
            </div>

            <div class="events-card">
                <div class="card-header">
                    <div>
                        <div class="card-title">Événements SSH récents</div>
                        <div class="card-subtitle">20 dernières tentatives</div>
                    </div>
                </div>
                <div class="table-container">
                    <table class="events-table">
                        <thead>
                            <tr>
                                <th>Horodatage</th>
                                <th>Échecs SSH</th>
                                <th>Niveau de menace</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach(array_slice(array_reverse($events), 0, 20) as $e):
                                $p = json_decode($e['payload'], true);
                                $ssh = $p['ssh_failed_count'];
                            ?>
                            <tr>
                                <td class="event-time"><?= date("d/m/Y H:i:s", $e['ts']) ?></td>
                                <td class="event-ssh" style="font-weight: 600; font-size: 16px;"><?= $ssh ?></td>
                                <td>
                                    <?php if ($ssh > 15): ?>
                                        <span class="risk-badge risk-high">CRITIQUE</span>
                                    <?php elseif ($ssh > 5): ?>
                                        <span class="risk-badge risk-medium">ÉLEVÉ</span>
                                    <?php else: ?>
                                        <span class="risk-badge risk-low">FAIBLE</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="../../chatbot/index.php?q=<?= urlencode('Analyse cette tentative SSH : '.$ssh.' échecs de connexion le '.date("d/m/Y à H:i:s", $e['ts'])) ?>" 
                                    style="display:inline-block;padding:4px 10px;background:#4a9eff;color:white;border-radius:4px;text-decoration:none;font-size:12px;">
                                        <i class='bx bx-brain'></i> Analyser
                                    </a>
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

const sshData = <?= json_encode(array_reverse($sshCounts)) ?>;
const labels = <?= json_encode(array_reverse($labels)) ?>;

const ctx = document.getElementById('sshChart').getContext('2d');
const gradient = ctx.createLinearGradient(0, 0, 0, 400);
gradient.addColorStop(0, 'rgba(239, 83, 80, 0.3)');
gradient.addColorStop(1, 'rgba(239, 83, 80, 0.05)');

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [{
            label: 'Tentatives SSH échouées',
            data: sshData,
            backgroundColor: sshData.map(s => s > 15 ? 'rgba(239, 83, 80, 0.8)' : (s > 5 ? 'rgba(255, 167, 38, 0.8)' : 'rgba(74, 158, 255, 0.8)')),
            borderColor: sshData.map(s => s > 15 ? '#ef5350' : (s > 5 ? '#ffa726' : '#4a9eff')),
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: { legend: { display: true, position: 'top' } },
        scales: {
            x: { 
                grid: { display: false },
                ticks: { color: '#8792a2', maxRotation: 45 }
            },
            y: {
                beginAtZero: true,
                ticks: { color: '#8792a2' },
                grid: { color: 'rgba(255,255,255,0.05)' }
            }
        }
    }
});

setTimeout(() => location.reload(), 60000);
</script>
</body>
</html>