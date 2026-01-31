<?php
session_start();
require_once 'backend/config.php';
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];
$stmtUser = $pdo->prepare("SELECT system_sentences FROM users WHERE id = ?");
$stmtUser->execute([$user_id]);
$user = $stmtUser->fetch();

// Profils IA
$stmtProfiles = $pdo->prepare("SELECT id, name, system_prompt FROM ai_profiles WHERE user_id = ?");
$stmtProfiles->execute([$user_id]);
$profiles = $stmtProfiles->fetchAll(PDO::FETCH_ASSOC);

// Historique des conversations
$stmt = $pdo->prepare("
    SELECT ch.id, ch.messages, ch.created_at, p.name AS profile_name
    FROM chat_history ch
    LEFT JOIN ai_profiles p ON ch.profile_id = p.id
    WHERE ch.user_id = ?
    ORDER BY ch.created_at DESC
    LIMIT 5
");
$stmt->execute([$user_id]);
$histories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$systemSentences = $user['system_sentences'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>IA CHATBOT</title>
<link rel="stylesheet" href="assets/style.css">
<!--  Import from external website -->
<link href="https://cdn.boxicons.com/3.0.8/fonts/basic/boxicons.min.css" rel="stylesheet">
<link href="https://cdn.boxicons.com/3.0.8/fonts/filled/boxicons-filled.min.css" rel="stylesheet">
<link href="https://cdn.boxicons.com/3.0.8/fonts/brands/boxicons-brands.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<style>
.prompt-item {
    width: 80%;
    text-align: left;
    padding: 10px 15px;
    margin: 5px 0;
    background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
    border: 1px solid #404040;
    border-radius: 8px;
    color: #e0e0e0;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
    position: relative;
    overflow: hidden;
    margin-left:10px;
}

.prompt-item::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    height: 100%;
    width: 3px;
    background: linear-gradient(180deg, #dc2626 0%, #991b1b 100%);
    transform: scaleY(0);
    transition: transform 0.3s ease;
}

.prompt-item:hover {
    background: linear-gradient(135deg, #2d2d2d 0%, #1a1a1a 100%);
    border-color: #dc2626;
    transform: translateX(5px);
    box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
}

.prompt-item:hover::before {
    transform: scaleY(1);
}

.prompt-item:active {
    transform: translateX(3px) scale(0.98);
    background: linear-gradient(135deg, #1a1a1a 0%, #0d0d0d 100%);
}

.prompt-item i {
    font-size: 16px;
    color: #dc2626;
    transition: color 0.3s ease;
}

.prompt-item:hover i {
    color: #ef4444;
}

#prompt-list {
    max-height: 300px;
    overflow-y: auto;
    padding-right: 5px;
}
</style>
</head>
<body>
<button class="menu-btn" id="menu-toggle" style="margin-top:-10px;">
    <span></span>
    <span></span>
    <span></span>
</button>
<div class="sidebar-overlay" id="sidebar-overlay"></div>
<div id="app-container">
    <div id="sidebar">
        <div class="sidebar-header">
            <h2>IA CHATBOT</h2>
            <button class="new-chat-btn" id="reset-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Nouvelle conversation
            </button><br>
            <form action="backend/logout.php"><button class="new-chat-btn"><i class='bx  bx-arrow-in-right-square-half'></i> Se déconnecter</button></form><br>
            <form action="settings.php"><button class="new-chat-btn"><i class='bx  bx-cog'></i>  Paramètres</button></form>
        </div><br>
        
        <div class="settings-group">
            <label style="margin-left:10px;">Profils IA</label>
            <div id="profile-list">
                <?php if (count($profiles) === 0): ?>
                    <label style="margin-left:10px;margin-top:30px;">Aucun profil disponible. Ajoutez-en un<br>dans les paramètres !</label>
                <?php else: ?>
                    <?php foreach ($profiles as $profile): ?>
                        <button type="button" class="profile-item" 
                            data-id="<?= $profile['id'] ?>" 
                            data-prompt="<?= htmlspecialchars($profile['system_prompt']) ?>">
                            <?= htmlspecialchars($profile['name']) ?>
                        </button>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="settings-group">
            <br>
            <label style="margin-left:10px;">Mes Prompts</label>
            <div id="prompt-list">
                <?php 
                $stmt = $pdo->prepare("SELECT * FROM prompt_user WHERE user_id = ? ORDER BY date_creation DESC");
                $stmt->execute([$_SESSION['user_id']]);
                $user_prompts = $stmt->fetchAll();
                
                if (count($user_prompts) === 0): ?>
                    <p style="color:#9a9a9a; margin-left:10px;">Aucun prompt enregistré.</p>
                <?php else: ?>
                    <?php foreach ($user_prompts as $prompt): ?>
                        <button type="button" class="prompt-item" 
                            data-prompt="<?= htmlspecialchars($prompt['contenu_prompt']) ?>"
                            title="<?= htmlspecialchars($prompt['contenu_prompt']) ?>">
                            <i class="bx bx-discussion"></i> <?= htmlspecialchars($prompt['nom_prompt']) ?>
                        </button>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="settings-group">
            <br>
            <label style="margin-left:10px;">Historique des conversations</label>
            <div id="history-list">
                <?php if (count($histories) === 0): ?>
                    <p style="color:#9a9a9a; margin-left:10px;">Aucune conversation enregistrée.</p>
                <?php else: ?>
                    <?php foreach ($histories as $history): 
                        $msgs = json_decode($history['messages'], true);
                        $lastMessage = end($msgs)['content'] ?? '';?>
                        <div class="history-item-wrapper">
                            <button type="button" class="history-item" data-id="<?= $history['id'] ?>">
                                <strong><?= htmlspecialchars($history['profile_name'] ?? 'Profil inconnu') ?></strong><br>
                                <span style="color:#9a9a9a; font-size:12px;"><?= htmlspecialchars(substr($lastMessage, 0, 50)) ?>...</span>
                                <br>
                                <span style="color:#9a9a9a; font-size:10px;"><?= date('d/m/Y', strtotime($history['created_at'])) ?></span>
                            </button>
                            <button type="button" class="delete-history-btn" data-id="<?= $history['id'] ?>" title="Supprimer">
                                <i class='bx bx-trash'></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div id="chat-area">
        <div id="header">
            <h1>IA CHATBOT</h1>
        </div>

        <div id="messages"></div>
        <div id="input-area">
            <div class="input-container">
                <textarea id="user-input" rows="1" placeholder="Envoyez un message..."></textarea>
                <button class="send-btn" onclick="sendMessage()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>
<script>
let chatHistory = JSON.parse(localStorage.getItem('chatHistory') || '[]');
let selectedProfilePrompt = "";
let selectedProfileId = null;

document.querySelectorAll('.history-item').forEach(item => {
    item.addEventListener('click', async () => {
        const historyId = item.dataset.id;
        
        try {
            const res = await fetch(`backend/get.php?id=${historyId}`);
            const data = await res.json();
            
            if (data.success) {
                chatHistory = JSON.parse(data.messages);
                localStorage.setItem('chatHistory', JSON.stringify(chatHistory));
                if (data.profile_id) {
                    selectedProfileId = data.profile_id;
                    document.querySelectorAll('.profile-item').forEach(p => {
                        p.classList.remove('active');
                        if (p.dataset.id == data.profile_id) {
                            p.classList.add('active');
                            selectedProfilePrompt = p.dataset.prompt;
                            localStorage.setItem('selectedProfilePrompt', selectedProfilePrompt);
                        }
                    });
                }
                document.getElementById('messages').innerHTML = '';
                initializeChat();
                if (window.innerWidth <= 768) toggleMenu();
            }
        } catch (err) {
            console.error('Erreur chargement historique:', err);
        }
    });
});
    

// delete history
document.querySelectorAll('.delete-history-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
        e.stopPropagation();
        
        if (!confirm('Voulez-vous vraiment supprimer cette conversation ?')) {
            return;
        }
        
        const historyId = btn.dataset.id;
        const formData = new FormData();
        formData.append('id', historyId);
        
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'backend/delete.php', true);
        
        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    const data = JSON.parse(xhr.responseText);
                    if (data.success) {
                        btn.closest('.history-item-wrapper').remove();
                        const historyList = document.getElementById('history-list');
                        if (historyList.querySelectorAll('.history-item-wrapper').length === 0) {
                            historyList.innerHTML = '<p style="color:#9a9a9a; margin-left:10px;">Aucune conversation enregistrée.</p>';
                        }
                        console.log('Conversation supprimée');
                    } else {
                        alert('Erreur: ' + data.error);
                    }
                } catch (err) {
                    console.error('Erreur parsing:', err);
                }
            }
        };
        
        xhr.send(formData);
    });
});
    

// Profils IA
document.querySelectorAll('.profile-item').forEach(item => {
    item.addEventListener('click', () => {
        document.querySelectorAll('.profile-item').forEach(i => i.classList.remove('active'));
        item.classList.add('active');

        selectedProfilePrompt = item.dataset.prompt;
        selectedProfileId = item.dataset.id; // Pour la BDD
        localStorage.setItem('selectedProfilePrompt', selectedProfilePrompt);
    });
});

const savedPrompt = localStorage.getItem('selectedProfilePrompt');
if (savedPrompt) {
    selectedProfilePrompt = savedPrompt;
    document.querySelectorAll('.profile-item').forEach(item => {
        if (item.dataset.prompt === savedPrompt) {
            item.classList.add('active');
            selectedProfileId = item.dataset.id;
        }
    });
}
// initialize chat
function initializeChat() {
    const messagesDiv = document.getElementById('messages');
    if (!messagesDiv) return;

    if (chatHistory.length === 0) {
        showEmptyState();
    } else {
        chatHistory.forEach(m => {
            const avatarHTML = m.role === 'user'
                ? "<i class='bx bx-user-circle'></i>"
                : "<i class='bx bx-robot'></i>";
            addMessage(m.role, m.content, avatarHTML, false, true);
        });
    }
}
function showEmptyState() {
    const messagesDiv = document.getElementById('messages');
    if (!messagesDiv) return;
    messagesDiv.innerHTML = `
        <div class="empty-state">
            <h2>Comment puis-je vous aider ?</h2>
            <p>Commencez une conversation en tapant un message ci-dessous.</p>
        </div>
    `;
}

// Reset conv
const resetBtn = document.getElementById('reset-btn');
if (resetBtn) {
    resetBtn.addEventListener('click', () => {
            chatHistory = [];
            localStorage.setItem('chatHistory', JSON.stringify(chatHistory));
            showEmptyState();
            if (window.innerWidth <= 768) toggleMenu();
        }
    );
}


async function sendMessage() {
    const input = document.getElementById('user-input');
    if (!input) return;

    const msg = input.value.trim();
    if (!msg) return;

    if (window.innerWidth <= 768 && sidebar.classList.contains('active')) toggleMenu();

    const emptyState = document.querySelector('.empty-state');
    if (emptyState) emptyState.remove();

    const userAvatarHTML = "<i class='bx bx-user-circle'></i>";
    const botAvatarHTML = "<i class='bx bx-robot'></i>";

    addMessage('user', msg, userAvatarHTML, false, true);
    chatHistory.push({ role: 'user', content: msg });
    localStorage.setItem('chatHistory', JSON.stringify(chatHistory));

    input.value = '';
    input.style.height = 'auto';

    const tempContent = addMessage('bot', '', botAvatarHTML, true, true);

    try {
        const payload = [];
        if (selectedProfilePrompt) {
            payload.push({ role: 'system', content: selectedProfilePrompt });
        }
        chatHistory.forEach(m => payload.push({ role: m.role, content: m.content }));
        const res = await fetch('http://localhost:11434/v1/chat/completions', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ model: 'gemma3', messages: payload })
        });
        const data = await res.json();
        const botReply = data.choices[0].message.content;
        await typeMessage(tempContent, botReply);
        chatHistory.push({ role: 'bot', content: botReply });
        localStorage.setItem('chatHistory', JSON.stringify(chatHistory));
        saveChatToDB(chatHistory, selectedProfileId);
    } catch (err) {
        console.error('Erreur envoi message:', err);
        tempContent.innerHTML = '⚠️ Erreur serveur IA';
    }
}

function saveChatToDB(chatHistory, profileId) {
    $.ajax({
        url: 'backend/process.php',
        type: 'POST',
        data: {
            profile_id: profileId || '',
            messages: JSON.stringify(chatHistory)
        },
        success: function(data) {
            console.log('SAVE_CHAT response:', data);
        },
        error: function(xhr, status, error) {
            console.error('SAVE_CHAT error:', error);
        }
    });
}
function addMessage(role, text, avatar = null, isTyping = false, isHTML = false) {
    const messagesDiv = document.getElementById('messages');
    if (!messagesDiv) return;

    const wrapper = document.createElement('div');
    wrapper.classList.add('message-wrapper', role === 'user' ? 'user-wrapper' : 'bot-wrapper');

    const msgDiv = document.createElement('div');
    msgDiv.classList.add('message');

    const avatarDiv = document.createElement('div');
    avatarDiv.classList.add('avatar', role === 'user' ? 'user-avatar' : 'bot-avatar');
    if (avatar) {
        isHTML ? avatarDiv.innerHTML = avatar : avatarDiv.textContent = avatar;
    }

    msgDiv.appendChild(avatarDiv);

    const contentDiv = document.createElement('div');
    contentDiv.classList.add('message-content');

    if (isTyping) {
        contentDiv.innerHTML = '<div class="typing-indicator"><span></span><span></span><span></span></div>';
    } else {
        contentDiv.textContent = text;
    }

    msgDiv.appendChild(contentDiv);
    wrapper.appendChild(msgDiv);
    messagesDiv.appendChild(wrapper);
    messagesDiv.scrollTop = messagesDiv.scrollHeight;

    return contentDiv;
}
async function typeMessage(contentDiv, text) {
    contentDiv.textContent = '';
    for (let c of text) {
        contentDiv.textContent += c;
        await new Promise(r => setTimeout(r, 15));
        const messagesDiv = document.getElementById('messages');
        if (messagesDiv) messagesDiv.scrollTop = messagesDiv.scrollHeight;
    }
}
const userInput = document.getElementById('user-input');
if (userInput) {
    userInput.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 200) + 'px';
    });

    userInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            if (e.shiftKey) return;
            e.preventDefault();
            sendMessage();
        }
    });
}
const menuToggle = document.getElementById('menu-toggle');
const sidebar = document.getElementById('sidebar');
const sidebarOverlay = document.getElementById('sidebar-overlay');

if (menuToggle && sidebar && sidebarOverlay) {
    menuToggle.addEventListener('click', toggleMenu);
    sidebarOverlay.addEventListener('click', toggleMenu);
}

function toggleMenu() {
    menuToggle.classList.toggle('active');
    sidebar.classList.toggle('active');
    sidebarOverlay.classList.toggle('active');
}
initializeChat();
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const promptItems = document.querySelectorAll('.prompt-item');
    const userInput = document.getElementById('user-input');
    
    promptItems.forEach(item => {
        item.addEventListener('click', function() {
            const promptContent = this.getAttribute('data-prompt');
            userInput.value = promptContent;
            userInput.focus();
            userInput.style.height = 'auto';
            userInput.style.height = userInput.scrollHeight + 'px';
        });
    });
});
</script>
</body>
</html>