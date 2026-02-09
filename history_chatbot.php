<?php
session_start();
require_once 'backend/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];
// Récupérer TOUT l'historique des conversations
$stmt = $pdo->prepare("
    SELECT ch.id, ch.messages, ch.created_at, p.name AS profile_name
    FROM chat_history ch
    LEFT JOIN ai_profiles p ON ch.profile_id = p.id
    WHERE ch.user_id = ?
    ORDER BY ch.created_at DESC
");
$stmt->execute([$user_id]);
$histories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Historique - IA CHATBOT</title>
<link rel="stylesheet" href="assets/style-<?= htmlspecialchars($theme) ?>.css">
<link href="https://cdn.boxicons.com/3.0.8/fonts/basic/boxicons.min.css" rel="stylesheet">
<link href="https://cdn.boxicons.com/3.0.8/fonts/filled/boxicons-filled.min.css" rel="stylesheet">
<style>
    body {
        margin: 0;
        padding: 0;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    }

    .history-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    .history-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }

    .history-header h1 {
        margin: 0;
        font-size: 28px;
    }

    .back-btn {
        background: rgba(255,255,255,0.1);
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        color: inherit;
        cursor: pointer;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
    }

    .back-btn:hover {
        background: rgba(255,255,255,0.2);
    }

    .history-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 20px;
    }

    .history-card {
        background: rgba(255,255,255,0.05);
        border-radius: 12px;
        padding: 20px;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        border: 1px solid rgba(255,255,255,0.1);
    }

    .history-card:hover {
        background: rgba(255,255,255,0.1);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    }

    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 12px;
    }

    .profile-badge {
        background: rgba(66, 133, 244, 0.2);
        color: #4285f4;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }

    .delete-btn {
        background: rgba(244, 67, 54, 0.2);
        border: none;
        padding: 8px;
        border-radius: 6px;
        color: #f44336;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }

    .delete-btn:hover {
        background: rgba(244, 67, 54, 0.3);
    }

    .card-preview {
        color: rgba(255,255,255,0.6);
        font-size: 14px;
        line-height: 1.5;
        margin-bottom: 12px;
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
    }

    .card-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 12px;
        color: rgba(255,255,255,0.4);
    }

    .message-count {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: rgba(255,255,255,0.5);
    }

    .empty-state i {
        font-size: 64px;
        margin-bottom: 20px;
        opacity: 0.3;
    }

    .empty-state h2 {
        margin: 0 0 10px 0;
        font-size: 24px;
    }

    .empty-state p {
        margin: 0;
        font-size: 16px;
    }

    @media (max-width: 768px) {
        .history-grid {
            grid-template-columns: 1fr;
        }
        
        .history-header h1 {
            font-size: 22px;
        }
    }
</style>
</head>
<body>

<div class="history-container">
    <div class="history-header">
        <h1><i class='bx bx-history'></i> Historique des conversations</h1>
        <button class="back-btn" onclick="location.href='chatbot.php'">
            <i class='bx bx-arrow-left'></i> Retour au chat
        </button>
    </div>

    <?php if (count($histories) === 0): ?>
        <div class="empty-state">
            <i class='bx bx-message-x'></i>
            <h2>Aucune conversation</h2>
            <p>Votre historique de conversations apparaîtra ici.</p>
        </div>
    <?php else: ?>
        <div class="history-grid">
            <?php foreach ($histories as $history): 
                $msgs = json_decode($history['messages'], true);
                $messageCount = count($msgs);
                
                // Trouver le premier message utilisateur pour l'aperçu
                $previewText = '';
                foreach ($msgs as $msg) {
                    if ($msg['role'] === 'user') {
                        $previewText = $msg['content'];
                        break;
                    }
                }
                if (empty($previewText) && !empty($msgs)) {
                    $previewText = $msgs[0]['content'] ?? '';
                }
            ?>
                <div class="history-card" data-id="<?= $history['id'] ?>">
                    <div class="card-header">
                        <span class="profile-badge">
                            <?= htmlspecialchars($history['profile_name'] ?? 'Sans profil') ?>
                        </span>
                        <button class="delete-btn" data-id="<?= $history['id'] ?>" onclick="event.stopPropagation(); deleteConversation(this)">
                            <i class='bx bx-trash'></i>
                        </button>
                    </div>
                    
                    <div class="card-preview">
                        <?= htmlspecialchars($previewText) ?>
                    </div>
                    
                    <div class="card-footer">
                        <span class="message-count">
                            <i class='bx bx-message-circle-dots-2'></i>
                            <?= $messageCount ?> message<?= $messageCount > 1 ? 's' : '' ?>
                        </span>
                        <span><?= date('d/m/Y H:i', strtotime($history['created_at'])) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
// Charger une conversation dans le chatbot
document.querySelectorAll('.history-card').forEach(card => {
    card.addEventListener('click', function() {
        const historyId = this.dataset.id;
        // Rediriger vers le chatbot avec l'ID de la conversation
        window.location.href = `chatbot.php?load=${historyId}`;
    });
});

// Supprimer une conversation
function deleteConversation(btn) {
    if (!confirm('Voulez-vous vraiment supprimer cette conversation ?')) {
        return;
    }
    
    const historyId = btn.dataset.id;
    const card = btn.closest('.history-card');
    
    const formData = new FormData();
    formData.append('id', historyId);
    
    fetch('backend/delete.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Animation de suppression
            card.style.opacity = '0';
            card.style.transform = 'scale(0.9)';
            
            setTimeout(() => {
                card.remove();
                
                // Vérifier s'il reste des conversations
                const remainingCards = document.querySelectorAll('.history-card');
                if (remainingCards.length === 0) {
                    location.reload(); // Recharger pour afficher l'état vide
                }
            }, 300);
        } else {
            alert('Erreur: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors de la suppression');
    });
}
</script>

</body>
</html>