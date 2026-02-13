<?php
date_default_timezone_set('Europe/Paris');
require 'backend/config.php';
$stmt = $pdo->query("SELECT * FROM events ORDER BY id DESC LIMIT 30");
$events = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));

$cpuTemps = [];
$cpuFreqs = [];
$ramUsed = [];
$labels = [];
$risks = ["LOW" => 0, "MEDIUM" => 0, "HIGH" => 0];
$totalSSH = 0;
$avgTemp = 0;
$currentTemp = 0;
$currentFreq = 0;
$currentRamUsed = 0;
$ramTotal = 0;
$osInfo = 'N/A';
$ipAddress = 'N/A';
$netmask = 'N/A';
$hostname = 'N/A';
$openPorts = 'N/A';

foreach ($events as $e) {
    $p = json_decode($e['payload'], true);

    // Température
    $cpuTemps[] = $p['cpu_temp_c'];
    $currentTemp = $p['cpu_temp_c'];

    // Labels temps
    $labels[] = date("H:i:s", $e['ts']);

    // Risques
    $risks[$p['risk']]++;

    // SSH
    $totalSSH += $p['ssh_failed_count'];

    // Fréquence CPU
    if (isset($p['cpu_freq_mhz'])) {
        $cpuFreqs[] = $p['cpu_freq_mhz'];
        $currentFreq = $p['cpu_freq_mhz'];
    }

    // RAM
    if (isset($p['ram_used_mb'], $p['ram_total_mb'])) {
        $ramUsed[] = $p['ram_used_mb'];
        $currentRamUsed = $p['ram_used_mb'];
        $ramTotal = $p['ram_total_mb'];
    }
}

// Récupération des informations système depuis le dernier événement
if (!empty($events)) {
    $lastEvent = end($events);
    $lastPayload = json_decode($lastEvent['payload'], true);
    
    if (isset($lastPayload['os'])) {
        $osInfo = $lastPayload['os'];
    }
    if (isset($lastPayload['ip'])) {
        $ipAddress = $lastPayload['ip'];
    }
    if (isset($lastPayload['netmask'])) {
        $netmask = $lastPayload['netmask'];
    }
    if (isset($lastPayload['hostname'])) {
        $hostname = $lastPayload['hostname'];
    }
    if (isset($lastPayload['open_ports'])) {
        $openPorts = $lastPayload['open_ports'];
    }
}

// Stats globales
$avgTemp = !empty($cpuTemps) ? round(array_sum($cpuTemps) / count($cpuTemps), 1) : 0;
$totalEvents = count($events);
$criticalEvents = $risks['HIGH'];
$maxTemp = !empty($cpuTemps) ? max($cpuTemps) : 0;
$minTemp = !empty($cpuTemps) ? min($cpuTemps) : 0;

// Profils AI (exemple)
$stmtProfiles = $pdo->prepare("SELECT id, name FROM ai_profiles WHERE user_id = ? LIMIT 5");
$stmtProfiles->execute([1]);
$profiles = $stmtProfiles->fetchAll(PDO::FETCH_ASSOC);

// Historique chat (exemple)
$stmtHistory = $pdo->query("SELECT id, created_at FROM chat_history ORDER BY created_at DESC LIMIT 5");
$histories = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Security Operations Center</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.boxicons.com/3.0.8/fonts/basic/boxicons.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/code.css">
</head>
<body style="font-family: 'Poppins', sans-serif;">
<div id="app-container">
    <aside id="sidebar">
        <div class="sidebar-header">
            <h2>Navigation</h2>
            <button class="new-chat-btn" onclick="location.href='../ai-chatbot-ollama/index.php'"><i class="bx bx-dashboard"></i>Dashboard</button><br>
            <button class="new-chat-btn" onclick="location.href='../ai-chatbot-ollama/chatbot.php'"><i class="bx bx-message-circle-dots-2"></i>Chatbot</button>
        </div>
        
        <div class="sidebar-content">
            <br>
        </div>
        
        <div class="sidebar-footer">
            <button class="new-chat-btn" onclick="location.href='../ai-chatbot-ollama/settings.php'"><i class="bx bx-gear"></i>Settings</button><br>
            <button class="new-chat-btn" onclick="location.href='../ai-chatbot-ollama/backend/logout.php'"><i class="bx bx-arrow-out-right-square-half"></i>Déconnexion</button>
        </div>
    </aside>
    <div class="sidebar-overlay" onclick="toggleSidebar()"></div>
    <button class="menu-btn" onclick="toggleSidebar()">
        <span></span>
        <span></span>
        <span></span>
    </button>
    <div id="main-content">
        <header>
            <div class="header-content">
                <div>
                    <h1 class="header-title">Rasberry Cyber Sensors</h1>
                    <div class="header-subtitle">Dashboard</div>
                </div>
                <div class="header-meta">
                    <div class="last-update">Mis à jour: <?= date("d/m/Y H:i") ?></div>
                    <div class="status-badge" style="height:50px;">
                        <span class="status-dot"></span>
                        <button class="analyze-btn" onclick="analyzeData()" style="background:none;border:none;font-weight:bold;color:white;cursor:pointer;" >
                            Analyser mes données
                        </button>
                    </div>
                </div>
            </div>
        </header>
        <div class="content-scrollable">
            <div class="metrics-grid">
                    <a href="dashboards/cpu.php" class="metric-card" style="text-decoration:none;">
                        <div class="metric-header">
                            <div>
                                <div class="metric-label">Température CPU</div>
                                <div class="metric-value"><?= $currentTemp ?>°C</div>
                                <div class="metric-change">Moyenne: <?= $avgTemp ?>°C</div>
                            </div>
                            <div class="metric-icon blue">🌡️</div>
                        </div>
                    </a>
                    <div class="metric-card-bigger">
            <div class="metric-header">
                <div style="width: 100%;">
                    <div class="metric-label">Information Raspberry</div>
                    <?php 
                    $lastEvent = !empty($events) ? end($events) : null;
                    $sysInfo = $lastEvent ? json_decode($lastEvent['payload'], true) : [];
                    ?>
                    <div style="margin-top: 15px; display: flex; flex-direction: column; gap: 12px;">
                        <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid var(--border);">
                            <span style="color: var(--text-secondary); font-size: 14px;">📟 OS :</span>
                            <span style="color: var(--text-primary); font-weight: 500; font-size: 14px;">
                                <?= isset($sysInfo['os']) ? $sysInfo['os'] : 'N/A' ?>
                            </span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid var(--border);">
                            <span style="color: var(--text-secondary); font-size: 14px;">🌐 IP :</span>
                            <span style="color: var(--accent); font-weight: 600; font-size: 14px; font-family: 'IBM Plex Mono', monospace;">
                                <?= isset($sysInfo['ip']) ? $sysInfo['ip'] : 'N/A' ?>
                            </span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid var(--border);">
                            <span style="color: var(--text-secondary); font-size: 14px;">🔒 Masque :</span>
                            <span style="color: var(--text-primary); font-weight: 500; font-size: 14px; font-family: 'IBM Plex Mono', monospace;">
                                <?= isset($sysInfo['netmask']) ? $sysInfo['netmask'] : 'N/A' ?>
                            </span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid var(--border);">
                            <span style="color: var(--text-secondary); font-size: 14px;">💻 Machine :</span>
                            <span style="color: var(--text-primary); font-weight: 500; font-size: 14px;">
                                <?= isset($sysInfo['hostname']) ? $sysInfo['hostname'] : 'N/A' ?>
                            </span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid var(--border);">
                            <span style="color: var(--text-secondary); font-size: 14px;">💾 RAM TOTALE :</span>
                            <span style="color: var(--text-primary); font-weight: 500; font-size: 14px;">
                                <?= isset($sysInfo['ram_total_mb']) 
                                    ? $sysInfo['ram_total_mb'] . ' MB' 
                                    : 'N/A' ?>
                            </span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 8px 0;">
                            <span style="color: var(--text-secondary); font-size: 14px;">🔌 Ports ouverts :</span>
                            <span style="color: var(--color-warning); font-weight: 500; font-size: 12px; font-family: 'IBM Plex Mono', monospace; max-width: 60%; text-align: right; word-wrap: break-word;">
                                <?= isset($sysInfo['open_ports']) ? $sysInfo['open_ports'] : 'N/A' ?>
                            </span>
                        </div>
                    </div>
        </div>
        <div class="metric-icon blue" style="align-self: flex-start;">🖥️</div>
    </div>
</div>
                <a class="metric-card" href="dashboards/ssh.php" style="text-decoration:none;">
                    <div class="metric-header">
                        <div>
                            <div class="metric-label">Tentatives SSH</div>
                            <div class="metric-value"><?= $totalSSH ?></div>
                            <div class="metric-change negative">Échecs de connexion</div>
                        </div>
                        <div class="metric-icon red">🔐</div>
                    </div>
                </a>
                <a href="dashboards/alerts.php" style="text-decoration:none;" class="metric-card">
                    <div class="metric-header">
                        <div>
                            <div class="metric-label">Alertes Faibles</div>
                            <div class="metric-value"><?= $risks['LOW'] ?></div>
                            <div class="metric-change positive"><?= round($risks['LOW']/$totalEvents*100) ?>% du total</div>
                        </div>
                        <div class="metric-icon green">✓</div>
                    </div>
                </a>
                <a href="dashboards/ram.php" style="text-decoration:none;" class="metric-card">
                    <div class="metric-label">Mémoire RAM</div>
                    <div class="metric-value"><?= $currentRamUsed ?> / <?= $ramTotal ?> MB</div>
                    <div class="metric-change">
                        <?= $ramTotal > 0 ? round(($currentRamUsed/$ramTotal)*100) : 0 ?>% utilisée
                    </div>
                </a>
                
            </div>
            <div class="content-grid">
                <div class="chart-card">
                    <div class="card-header">
                        <div>
                            <div class="card-title">Évolution de la température CPU</div>
                            <div class="card-subtitle">30 derniers événements</div>
                        </div>
                    </div>
                    <canvas id="cpuChart"></canvas>
                </div>

                <div class="summary-card">
                    <div class="card-header">
                        <div>
                            <div class="card-title">Vue d'ensemble</div>
                            <div class="card-subtitle">Statistiques globales</div>
                        </div>
                    </div>
                    <div class="summary-items">
                        <div class="summary-item">
                            <span class="summary-item-label">Total événements</span>
                            <span class="summary-item-value"><?= $totalEvents ?></span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-item-label">Alertes moyennes</span>
                            <span class="summary-item-value warning"><?= $risks['MEDIUM'] ?></span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-item-label">Température max</span>
                            <span class="summary-item-value danger"><?= max($cpuTemps) ?>°C</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-item-label">Température min</span>
                            <span class="summary-item-value success"><?= min($cpuTemps) ?>°C</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="events-card">
    <div class="card-header">
        <div>
            <div class="card-title">Journal des événements</div>
            <div class="card-subtitle">Historique des 30 derniers événements</div>
        </div>
    </div>

    <div class="table-container">
        <table class="events-table">
            <thead>
                <tr>
                    <th>Heure</th>
                    <th>Niveau de risque</th>
                    <th>Température</th>
                    <th>Fréq CPU</th>
                    <th>RAM</th>
                    <th>Échecs SSH</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_reverse($events) as $e):
                    $p = json_decode($e['payload'], true);
                    $riskClass = strtolower($p['risk']);

                    $ramPercent = isset($p['ram_used_mb'], $p['ram_total_mb']) && $p['ram_total_mb'] > 0
                        ? round(($p['ram_used_mb'] / $p['ram_total_mb']) * 100)
                        : null;
                ?>
                <tr>
                    <td class="event-time">
                        <?= date("d/m/Y H:i:s", $e['ts']) ?>
                    </td>

                    <td>
                        <span class="risk-badge risk-<?= $riskClass ?>">
                            <?= $p['risk'] ?>
                        </span>
                    </td>

                    <td class="event-temp">
                        <?= $p['cpu_temp_c'] ?>°C
                    </td>

                    <td>
                        <?= isset($p['cpu_freq_mhz']) ? round($p['cpu_freq_mhz']) . ' MHz' : '—' ?>
                    </td>

                    <td>
                        <?php if ($ramPercent !== null): ?>
                            <?= $p['ram_used_mb'] ?>/<?= $p['ram_total_mb'] ?> MB
                            <span style="font-size:12px;color:<?= $ramPercent > 90 ? '#ef5350' : ($ramPercent > 75 ? '#ffa726' : '#3fb980') ?>">
                                (<?= $ramPercent ?>%)
                            </span>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>

                    <td class="event-ssh">
                        <?= $p['ssh_failed_count'] ?>
                    </td>

                    <td>
                        <?= $p['risk'] === 'HIGH' ? '⚠️ Attention requise' : '✓ Normal' ?>
                        <br>

                        <button onclick="analyzeData()" style="display:inline-block;margin-top:5px;padding:3px 8px;background:var(--accent);color:white;border-radius:4px;text-decoration:none;font-size:12px;border:none;padding: 10px;cursor:pointer;">
                            <i class='bx bx-brain'></i> Analyser avec l'IA
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

            <br>
            <a href="history.php" style="color:var(--accent);text-decoration:none;font-weight:500;">
                <i class='bx bx-right-arrow-alt'></i> Voir tout l'historique
            </a>
        </div>
    </div>
        </div>
    </div>
</div>

<script>
let cpuChart = null;
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    const menuBtn = document.querySelector('.menu-btn');
    
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
    menuBtn.classList.toggle('active');
}
function usePrompt(prompt) {
    const encoded = encodeURIComponent(prompt);
    window.location.href = '../chatbot/index.php?q=' + encoded;
}
function loadEventData(index) {
    const allEvents = <?= json_encode($events) ?>;
    const eventData = allEvents[index];
    
    if (!eventData) return;
    
    const p = JSON.parse(eventData.payload);
    
    let message = `Analyse ces données de sécurité du ${new Date(eventData.ts * 1000).toLocaleString('fr-FR')} :\n\n`;
    message += `• CPU: ${p.cpu_temp_c}°C`;
    
    if (p.cpu_freq_mhz !== undefined) {
        message += ` | Fréq CPU: ${Math.round(p.cpu_freq_mhz)} MHz`;
    }
    
    if (p.ram_used_mb !== undefined && p.ram_total_mb !== undefined) {
        const percent = Math.round((p.ram_used_mb / p.ram_total_mb) * 100);
        message += ` | RAM: ${p.ram_used_mb}/${p.ram_total_mb} MB (${percent}%)`;
    }
    
    message += ` | SSH échoués: ${p.ssh_failed_count}`;
    message += ` | Risque: ${p.risk}`;
    
    const encoded = encodeURIComponent(message);
    window.location.href = `../chatbot/chatbot.php?auto=1&q=${encoded}`;
}
function initChart() {
    const canvas = document.getElementById('cpuChart');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');

    const temps = <?= json_encode($cpuTemps) ?>;
    const labels = <?= json_encode($labels) ?>;
    const allEvents = <?= json_encode($events) ?>; // Récupérer tous les événements

    const gradient = ctx.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, 'rgba(74, 158, 255, 0.15)');
    gradient.addColorStop(1, 'rgba(74, 158, 255, 0.02)');

    cpuChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Température CPU',
                data: temps,
                borderColor: '#4a9eff',
                backgroundColor: gradient,
                borderWidth: 2.5,
                fill: true,
                tension: 0.3,
                pointBackgroundColor: '#2d7dd2',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
                pointHoverBorderWidth: 3
            }]
        },
        options: {
            animation: false,
            responsive: true,
            maintainAspectRatio: true,
            plugins: { 
                legend: { display: false },
                tooltip: {
                    enabled: true,
                    backgroundColor: 'rgba(30, 39, 46, 0.95)',
                    titleColor: '#ffffff',
                    bodyColor: '#ffffff',
                    borderColor: '#4a9eff',
                    borderWidth: 1,
                    padding: 12,
                    displayColors: false,
                    titleFont: {
                        size: 14,
                        weight: 'bold'
                    },
                    bodyFont: {
                        size: 13
                    },
                    footerFont: {
                        size: 12,
                        weight: 'bold'
                    },
                    footerColor: '#4a9eff',
                    callbacks: {
                        title: function(context) {
                            return labels[context[0].dataIndex];
                        },
                        label: function(context) {
                            return `Température: ${context.parsed.y}°C`;
                        },
                        afterLabel: function(context) {
                            const eventData = allEvents[context.dataIndex];
                            if (eventData) {
                                const p = JSON.parse(eventData.payload);
                                let info = `\nRisque: ${p.risk}`;
                                info += `\nSSH échoués: ${p.ssh_failed_count}`;
                                if (p.cpu_freq_mhz) {
                                    info += `\nFréq CPU: ${Math.round(p.cpu_freq_mhz)} MHz`;
                                }
                                if (p.ram_used_mb && p.ram_total_mb) {
                                    const percent = Math.round((p.ram_used_mb / p.ram_total_mb) * 100);
                                    info += `\nRAM: ${p.ram_used_mb}/${p.ram_total_mb} MB (${percent}%)`;
                                }
                                return info;
                            }
                            return '';
                        },
                        footer: function(context) {
                            return '\n🔍 Cliquez pour charger ces données';
                        }
                    }
                }
            },
            scales: {
                x: { 
                    grid: { display: false, drawBorder: false }, 
                    ticks: { color: '#8792a2' } 
                },
                y: { 
                    min: Math.min(...temps) - 5, 
                    max: Math.max(...temps) + 5, 
                    ticks: { color: '#8792a2' }, 
                    grid: { drawBorder: false, color: 'rgba(255,255,255,0.05)' } 
                }
            },
            onClick: (event, activeElements) => {
                if (activeElements.length > 0) {
                    const dataIndex = activeElements[0].index;
                    loadEventData(dataIndex);
                }
            },
            onHover: (event, activeElements) => {
                event.native.target.style.cursor = activeElements.length > 0 ? 'pointer' : 'default';
            }
        }
    });
}
async function updateChart() {
    try {
        const res = await fetch('backend/get_latest_events.php');
        const data = await res.json();
        cpuChart.data.labels = data.labels;
        cpuChart.data.datasets[0].data = data.temps;
        cpuChart.update();
    } catch(e) {
        console.error('Erreur update chart:', e);
    }
}

function analyzeData() {
    const events = <?= json_encode($events) ?>;
    if (!events.length) return;
    const lastEvent = events[events.length - 1];
    const p = JSON.parse(lastEvent.payload);

    let message = "Analyse ces données de sécurité et indique si une action est nécessaire :\n\n";

    message += `• CPU: ${p.cpu_temp_c}°C`;

    if (p.cpu_freq_mhz !== undefined) {
        message += ` | Fréq CPU: ${Math.round(p.cpu_freq_mhz)} MHz`;
    }

    if (p.ram_used_mb !== undefined && p.ram_total_mb !== undefined) {
        const percent = Math.round((p.ram_used_mb / p.ram_total_mb) * 100);
        message += ` | RAM: ${p.ram_used_mb}/${p.ram_total_mb} MB (${percent}%)`;
    }
    message += ` | SSH échoués: ${p.ssh_failed_count}`;
    message += ` | Risque: ${p.risk}`;

    const encoded = encodeURIComponent(message);
    window.location.href = `../ai-chatbot-ollama/chatbot.php?auto=1&q=${encoded}`;
}

document.addEventListener('DOMContentLoaded', function() {
    initChart();
    setInterval(updateChart, 10);
});

setInterval(() => location.reload(), 30000);
</script>
</body>
</html>
