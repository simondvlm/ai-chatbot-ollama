<?php
date_default_timezone_set('Europe/Paris');
require 'backend/config.php'; // connexion à la base avec PDO

// Récupérer tous les événements
$stmt = $pdo->query("SELECT * FROM events ORDER BY ts DESC");
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Nombre total de requêtes
$totalRequests = count($events);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Historique des événements</title>
<style>
:root {
    --bleu-clair: #5bc0eb;
    --bleu-fonce: #0b3d91;
    --vert: #28a745;
    --rouge: #dc2626;
    --blanc: #ffffff;
    --gris-fonce: #e2e8f0;
    --gris-sombre: #121212;
    --gris-tableau: #1f1f1f;
}
body {
    margin: 0;
    padding: 0;
    font-family: 'Poppins', sans-serif;
    background-color: var(--gris-sombre);
    color: var(--gris-fonce);
}

a {
    text-decoration: none;
    color: inherit;
}
header {
    background: var(--bleu-fonce);
    color: var(--blanc);
    padding: 20px 30px;
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
}

.header-title {
    margin: 0;
    font-size: 28px;
}

.header-subtitle {
    font-size: 14px;
    color: #cbd5e0;
    margin-top: 4px;
}

.header-meta {
    text-align: right;
    font-size: 14px;
}

.last-update {
    margin-bottom: 6px;
    color: #cbd5e0;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-weight: bold;
}

.status-dot {
    width: 10px;
    height: 10px;
    background: var(--vert);
    border-radius: 50%;
}
.container {
    padding: 20px 30px;
}
.events-card {
    background: var(--gris-tableau);
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.5);
}

.card-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 20px;
}

.card-title {
    font-size: 20px;
    font-weight: 600;
}

.card-subtitle {
    font-size: 14px;
    color: #a0aec0;
}
.table-container {
    overflow-x: auto;
}

.events-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 800px;
}

.events-table th, 
.events-table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #2d3748;
    color: var(--gris-fonce);
}

.events-table th {
    background: var(--bleu-clair);
    color: var(--blanc);
    font-weight: 600;
    text-transform: uppercase;
}

.events-table tr:nth-child(even) {
    background: #2a2a2a;
}

.events-table tr:hover {
    background: #333333;
}
.risk-badge {
    padding: 4px 8px;
    border-radius: 4px;
    color: var(--blanc);
    font-weight: bold;
    font-size: 12px;
    text-transform: uppercase;
}

.risk-low {
    background: var(--vert);
}

.risk-medium {
    background: #ffa726; /* orange clair */
}

.risk-high {
    background: var(--rouge);
}
.event-temp {
    font-weight: 500;
}

.event-ssh {
    font-weight: 500;
}
.events-table a {
    display: inline-block;
    margin-top: 5px;
    padding: 3px 8px;
    background: var(--rouge);
    color: var(--blanc);
    border-radius: 4px;
    font-size: 12px;
    transition: background 0.3s, transform 0.2s;
}

.events-table a:hover {
    background: var(--bleu-clair);
    transform: translateY(-2px);
}
@media (max-width: 768px) {
    .header-content {
        flex-direction: column;
        gap: 10px;
    }
    .card-header {
        flex-direction: column;
        gap: 10px;
    }
    .events-table {
        font-size: 12px;
    }
}

</style>
<link href="https://cdn.boxicons.com/3.0.8/fonts/basic/boxicons.min.css" rel="stylesheet">
</head>
<body style="font-family: 'Poppins', sans-serif;">

<header>
    <div class="header-content">
        <div>
            <h1 class="header-title">Historique des événements</h1>
            <div class="header-subtitle">Tous les événements enregistrés</div>
        </div>
        <div class="header-meta">
            <div class="last-update"><i class="bx bx-reply"></i><a href="index.php" style="text-decoration:none;color:#718096;">  Dashboard</a></div>
            <div class="last-update">Mis à jour: <?= date("d/m/Y H:i") ?></div>
            <div class="status-badge">
                <span class="status-dot"></span>
                Total requêtes: <span id="totalRequests"><?= $totalRequests ?></span>
            </div>
        </div>
    </div>
</header>

<div class="container">
    <div class="events-card">
        <div class="card-header">
            <div>
                <div class="card-title">Journal complet</div>
                <div class="card-subtitle">Tous les événements</div>
            </div>
        </div>
        <div class="table-container">
            <table style="" class="events-table">
                <thead>
                    <tr>
                        <th>Horodatage</th>
                        <th>Niveau de risque</th>
                        <th>Température</th>
                        <th>Fréq CPU</th>
                        <th>RAM</th>
                        <th>Échecs SSH</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($events as $e):
                        $p = json_decode($e['payload'], true);
                        $riskClass = strtolower($p['risk']);
                        $ramPercent = isset($p['ram_used_mb'], $p['ram_total_mb']) && $p['ram_total_mb'] > 0
                            ? round(($p['ram_used_mb'] / $p['ram_total_mb']) * 100)
                            : null;
                    ?>
                    <tr>
                        <td class="event-time"><?= date("d/m/Y H:i:s", $e['ts']) ?></td>
                        <td><span class="risk-badge risk-<?= $riskClass ?>"><?= $p['risk'] ?></span></td>
                        <td class="event-temp"><?= $p['cpu_temp_c'] ?>°C</td>
                        <td><?= isset($p['cpu_freq_mhz']) ? round($p['cpu_freq_mhz']).' MHz' : '—' ?></td>
                        <td>
                            <?php if($ramPercent !== null): ?>
                                <?= $p['ram_used_mb'].'/'.$p['ram_total_mb'] ?> MB
                                <span style="font-size:12px;color:<?= $ramPercent>90 ? '#ef5350' : ($ramPercent>75 ? '#ffa726' : '#3fb980') ?>;">
                                    (<?= $ramPercent ?>%)
                                </span>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td class="event-ssh"><?= $p['ssh_failed_count'] ?></td>
                        <td>
                            <?= $p['risk']==='HIGH' ? '⚠️ Attention requise' : '✓ Normal' ?>
                            <br>
                            <a href="chatbot.php?q=<?= urlencode(
                                'Analyse ces données de sécurité : '.
                                'CPU '.$p['cpu_temp_c'].'°C, '.
                                (isset($p['cpu_freq_mhz']) ? 'Fréq '.round($p['cpu_freq_mhz']).' MHz, ' : '').
                                ($ramPercent!==null ? 'RAM '.$p['ram_used_mb'].'/'.$p['ram_total_mb'].' MB ('.$ramPercent.'%), ' : '').
                                'SSH échoués '.$p['ssh_failed_count'].', '.
                                'Risque '.$p['risk']
                            ) ?>" 
                            style="display:inline-block;margin-top:5px;padding:3px 8px;background:#dc2626;color:white;border-radius:4px;text-decoration:none;font-size:12px;">
                                Demander à l'IA
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// 🔄 Refresh des éléments toutes les 22 secondes
setInterval(async () => {
    try {
        const res = await fetch(window.location.href, { cache: 'no-store' });
        const html = await res.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');

        const newContainer = doc.querySelector('.container');
        const oldContainer = document.querySelector('.container');
        if(newContainer && oldContainer) oldContainer.innerHTML = newContainer.innerHTML;

        const newTotal = doc.querySelector('#totalRequests');
        const oldTotal = document.querySelector('#totalRequests');
        if(newTotal && oldTotal) oldTotal.innerText = newTotal.innerText;

    } catch(e) { console.error('Erreur refresh éléments', e); }
}, 22000);

// 🔁 Reload complet toutes les 30 secondes
setInterval(() => location.reload(), 30000);
</script>

</body>
</html>
