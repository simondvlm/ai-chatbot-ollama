<?php
date_default_timezone_set('Europe/Paris');
require '../backend/config.php';

$stmt = $pdo->query("SELECT * FROM events ORDER BY id DESC LIMIT 100");
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

$risks = ["LOW" => 0, "MEDIUM" => 0, "HIGH" => 0];
$riskHistory = ["LOW" => [], "MEDIUM" => [], "HIGH" => []];
$labels = [];
$criticalEvents = [];

foreach($events as $e) {
    $p = json_decode($e['payload'], true);
    $risk = $p['risk'];
    $risks[$risk]++;
    $riskHistory[$risk][] = $e;
    $labels[] = date("H:i", $e['ts']);
    
    if ($risk === 'HIGH') {
        $criticalEvents[] = $e;
    }
}

$totalEvents = count($events);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Alertes - SOC</title>
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
                <button class="profile-item" onclick="location.href='ssh.php'">
                    <i class='bx bx-shield'></i> Sécurité SSH
                </button><br><br>
                <button class="profile-item active" onclick="location.href='alerts.php'">
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
                        <i class='bx bx-error'></i> Dashboard Alertes
                    </h1>
                    <div class="header-subtitle">Gestion et suivi des alertes système</div>
                </div>
                <div class="header-meta">
                    <div class="last-update">Mis à jour: <?= date("d/m/Y H:i") ?></div>
                    <div class="status-badge" style="<?= $risks['HIGH'] > 0 ? 'background: rgba(239, 83, 80, 0.15); border-color: rgba(239, 83, 80, 0.3); color: #ef5350;' : '' ?>">
                        <span class="status-dot" style="<?= $risks['HIGH'] > 0 ? 'background: #ef5350;' : '' ?>"></span>
                        <?= $risks['HIGH'] > 0 ? $risks['HIGH'].' Alerte(s) Critique(s)' : 'Aucune alerte' ?>
                    </div>
                </div>
            </div>
        </header>

        <div class="content-scrollable">
            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-header">
                        <div>
                            <div class="metric-label">Alertes Faibles</div>
                            <div class="metric-value"><?= $risks['LOW'] ?></div>
                            <div class="metric-change positive"><?= round($risks['LOW']/$totalEvents*100) ?>% du total</div>
                        </div>
                        <div class="metric-icon green">✓</div>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-header">
                        <div>
                            <div class="metric-label">Alertes Moyennes</div>
                            <div class="metric-value"><?= $risks['MEDIUM'] ?></div>
                            <div class="metric-change"><?= round($risks['MEDIUM']/$totalEvents*100) ?>% du total</div>
                        </div>
                        <div class="metric-icon orange">⚡</div>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-header">
                        <div>
                            <div class="metric-label">Alertes Critiques</div>
                            <div class="metric-value"><?= $risks['HIGH'] ?></div>
                            <div class="metric-change negative"><?= round($risks['HIGH']/$totalEvents*100) ?>% du total</div>
                        </div>
                        <div class="metric-icon red">⚠️</div>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-header">
                        <div>
                            <div class="metric-label">Total Événements</div>
                            <div class="metric-value"><?= $totalEvents ?></div>
                            <div class="metric-change">Analysés</div>
                        </div>
                        <div class="metric-icon blue">📊</div>
                    </div>
                </div>
            </div>

            <div class="content-grid">
                <div class="chart-card">
                    <div class="card-header">
                        <div>
                            <div class="card-title">Répartition des alertes</div>
                            <div class="card-subtitle">Par niveau de risque</div>
                        </div>
                    </div>
                    <canvas id="riskPieChart" style="max-height: 300px;"></canvas>
                </div>

                <div class="summary-card">
                    <div class="card-header">
                        <div>
                            <div class="card-title">Analyse rapide</div>
                            <div class="card-subtitle">Résumé des risques</div>
                        </div>
                    </div>
                    <div class="summary-items">
                        <div class="summary-item">
                            <span class="summary-item-label">Niveau global</span>
                            <span class="summary-item-value <?= $risks['HIGH'] > 5 ? 'danger' : ($risks['MEDIUM'] > 10 ? 'warning' : 'success') ?>">
                                <?= $risks['HIGH'] > 5 ? 'CRITIQUE' : ($risks['MEDIUM'] > 10 ? 'ÉLEVÉ' : 'NORMAL') ?>
                            </span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-item-label">Taux critique</span>
                            <span class="summary-item-value danger"><?= round($risks['HIGH']/$totalEvents*100, 1) ?>%</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-item-label">Taux normal</span>
                            <span class="summary-item-value success"><?= round($risks['LOW']/$totalEvents*100, 1) ?>%</span>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($criticalEvents)): ?>
            <div class="events-card" style="border: 2px solid #ef5350;">
                <div class="card-header">
                    <div>
                        <div class="card-title" style="color: #ef5350;">
                            <i class='bx bx-error-circle'></i> Alertes critiques
                        </div>
                        <div class="card-subtitle">Nécessitent une attention immédiate</div>
                    </div>
                </div>
                <div class="table-container">
                    <table class="events-table">
                        <thead>
                            <tr>
                                <th>Horodatage</th>
                                <th>Température</th>
                                <th>SSH</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach(array_reverse($criticalEvents) as $e):
                                $p = json_decode($e['payload'], true);
                            ?>
                            <tr style="background: rgba(239, 83, 80, 0.1);">
                                <td class="event-time"><?= date("d/m/Y H:i:s", $e['ts']) ?></td>
                                <td class="event-temp" style="color: #ef5350; font-weight: 600;"><?= $p['cpu_temp_c'] ?>°C</td>
                                <td class="event-ssh" style="color: #ef5350; font-weight: 600;"><?= $p['ssh_failed_count'] ?></td>
                                <td>
                                    <a href="../../chatbot/index.php?q=<?= urlencode('ALERTE CRITIQUE : Température '.$p['cpu_temp_c'].'°C, SSH '.$p['ssh_failed_count'].' échecs. Analyse urgente requise.') ?>" 
                                    style="display:inline-block;padding:4px 10px;background:#ef5350;color:white;border-radius:4px;text-decoration:none;font-size:12px;">
                                        <i class='bx bx-error'></i> Urgence
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('active');
    document.querySelector('.sidebar-overlay').classList.toggle('active');
    document.querySelector('.menu-btn').classList.toggle('active');
}

const ctx = document.getElementById('riskPieChart').getContext('2d');
new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: ['Faible', 'Moyen', 'Critique'],
        datasets: [{
            data: [<?= $risks['LOW'] ?>, <?= $risks['MEDIUM'] ?>, <?= $risks['HIGH'] ?>],
            backgroundColor: ['#3fb980', '#ffa726', '#ef5350'],
            borderColor: ['#2d8a5e', '#d17d1a', '#c73632'],
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { position: 'bottom', labels: { color: '#8792a2', padding: 20 } }
        }
    }
});

setTimeout(() => location.reload(), 60000);
</script>
</body>
</html>