<?php
session_start();
require_once 'backend/config.php';
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = "";
$success = "";

// LOGIN 
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header("Location: index.php");
        exit();
    } else {
        $error = "Nom d'utilisateur ou mot de passe incorrect";
    }
}

// SIGNUP 
if (isset($_POST['signup'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);

    if ($check->rowCount() > 0) {
        $error = "Cette adresse email est déjà utilisée";
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $password]);

        $_SESSION['user_id'] = $pdo->lastInsertId();
        $_SESSION['username'] = $username;
        header("Location: index.php");
        exit();
    }
}
?>
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat IA - Connexion</title>
    <style>
:root {
    --bg-primary: #0f0f0f;
    --bg-secondary: #1a1a1a;
    --bg-card: #232323;
    --text-primary: #e8e8e8;
    --text-secondary: #b4b4b4;
    --text-muted: #737373;
    --accent: #dc2626;
    --accent-hover: #b91c1c;
    --accent-light: rgba(220, 38, 38, 0.1);
    --border: #2f2f2f;
    --error: #ef4444;
    --error-bg: rgba(239, 68, 68, 0.1);
    --success: #10b981;
    --success-bg: rgba(16, 185, 129, 0.1);
}

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica', 'Arial', sans-serif;
    background: linear-gradient(135deg, #0f0f0f 0%, #1a1a1a 100%);
    color: var(--text-primary);
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
    position: relative;
    overflow: hidden;
}
@keyframes float {
    0%, 100% { transform: translate(0, 0); }
    50% { transform: translate(30px, 30px); }
}

.container {
    position: relative;
    z-index: 1;
    width: 100%;
    max-width: 420px;
}
.header {
    text-align: center;
    margin-bottom: 32px;
}
@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

.header h1 {
    font-size: 28px;
    font-weight: 700;
    background: linear-gradient(135deg, #dc2626, #ef4444);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 8px;
}

.header p {
    color: var(--text-secondary);
    font-size: 14px;
}
.card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 40px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
    backdrop-filter: blur(10px);
}
.message {
    padding: 12px 16px;
    border-radius: 10px;
    margin-bottom: 20px;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.message.error {
    background: var(--error-bg);
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: var(--error);
}

.message.success {
    background: var(--success-bg);
    border: 1px solid rgba(16, 185, 129, 0.3);
    color: var(--success);
}
.tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 24px;
    background: var(--bg-secondary);
    padding: 4px;
    border-radius: 10px;
}

.tab {
    flex: 1;
    padding: 10px 16px;
    background: transparent;
    border: none;
    border-radius: 8px;
    color: var(--text-secondary);
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s;
}

.tab.active {
    background: var(--accent);
    color: white;
    box-shadow: 0 2px 8px rgba(220, 38, 38, 0.3);
}

.tab:hover:not(.active) {
    background: var(--bg-primary);
    color: var(--text-primary);
}

.form-group {
    margin-bottom: 20px;
}

label {
    display: block;
    font-size: 13px;
    font-weight: 500;
    color: var(--text-secondary);
    margin-bottom: 8px;
}

.input-wrapper {
    position: relative;
}

.input-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    width: 18px;
    height: 18px;
    color: var(--text-muted);
    pointer-events: none;
}

input {
    width: 100%;
    padding: 12px 14px 12px 42px;
    background: var(--bg-secondary);
    border: 1px solid var(--border);
    border-radius: 10px;
    color: var(--text-primary);
    font-size: 14px;
    font-family: inherit;
    transition: all 0.2s;
}

input:focus {
    outline: none;
    border-color: var(--accent);
    background: var(--bg-primary);
    box-shadow: 0 0 0 3px var(--accent-light);
}

input::placeholder {
    color: var(--text-muted);
}
button[type="submit"] {
    width: 100%;
    padding: 14px;
    background: var(--accent);
    border: none;
    border-radius: 10px;
    color: white;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    margin-top: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

button[type="submit"]:hover {
    background: var(--accent-hover);
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(220, 38, 38, 0.3);
}

button[type="submit"]:active {
    transform: translateY(0);
}

button svg {
    width: 18px;
    height: 18px;
}
.divider {
    display: flex;
    align-items: center;
    text-align: center;
    margin: 24px 0;
}

.divider::before,
.divider::after {
    content: '';
    flex: 1;
    border-bottom: 1px solid var(--border);
}

.divider span {
    padding: 0 16px;
    color: var(--text-muted);
    font-size: 13px;
}
@media (max-width: 480px) {
    body {
        padding: 10px;
    }
    
    .card {
        padding: 28px 24px;
    }
    
    .header h1 {
        font-size: 24px;
    }
    
    .header .logo {
        font-size: 40px;
    }
    
    .features-grid {
        grid-template-columns: 1fr;
    }
    .swipe-indicator {
        text-align: center;
        font-size: 11px;
        color: var(--text-muted);
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    
    .swipe-indicator svg {
        width: 16px;
        height: 16px;
        animation: swipe 1.5s ease-in-out infinite;
    }
    
    @keyframes swipe {
        0%, 100% { transform: translateX(-4px); opacity: 0.5; }
        50% { transform: translateX(4px); opacity: 1; }
    }
}
.form-container {
    position: relative;
    overflow: hidden;
    touch-action: pan-y;
}

.forms-wrapper {
    display: flex;
    transition: transform 0.3s ease;
    width: 200%;
}

.form {
    width: 50%;
    flex-shrink: 0;
}

.forms-wrapper.show-signup {
    transform: translateX(-50%);
}
.password-toggle {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--text-muted);
    cursor: pointer;
    padding: 4px;
    width: auto;
    margin: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.password-toggle:hover {
    color: var(--text-primary);
    background: transparent;
    transform: translateY(-50%);
    box-shadow: none;
}

.password-toggle svg {
    width: 18px;
    height: 18px;
}
</style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>Chat IA</h1>
        <p>Votre assistant IA personnel et privé</p>
    </div>
    <div class="card">
        <?php if ($error): ?>
            <div class="message error">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        <div class="tabs">
            <button class="tab active" onclick="switchTab('login')">Connexion</button>
            <button class="tab" onclick="switchTab('signup')">Inscription</button>
        </div>
        <div class="form-container">
            <div class="swipe-indicator" style="display: none;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
                Swipez pour changer
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </div>
            
            <div class="forms-wrapper">
                <form method="POST" id="loginForm" class="form">
                    <div class="form-group">
                        <label>Nom d'utilisateur</label>
                        <div class="input-wrapper">
                            <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                            <input type="text" name="username" placeholder="Votre nom d'utilisateur" required autocomplete="username">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Mot de passe</label>
                        <div class="input-wrapper">
                            <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                <path d="M7 11V7a5 5 0 0110 0v4"/>
                            </svg>
                            <input type="password" name="password" id="loginPassword" placeholder="Votre mot de passe" required autocomplete="current-password">
                            <button type="button" class="password-toggle" onclick="togglePassword('loginPassword', this)">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" name="login">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4M10 17l5-5-5-5M13.8 12H3"/>
                        </svg>
                        Se connecter
                    </button>
                </form>

                <form method="POST" id="signupForm" class="form">
                    <div class="form-group">
                        <label>Nom d'utilisateur</label>
                        <div class="input-wrapper">
                            <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                            <input type="text" name="username" placeholder="Choisissez un nom d'utilisateur" required autocomplete="username">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Adresse email</label>
                        <div class="input-wrapper">
                            <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                <polyline points="22,6 12,13 2,6"/>
                            </svg>
                            <input type="email" name="email" placeholder="votre@email.com" required autocomplete="email">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Mot de passe</label>
                        <div class="input-wrapper">
                            <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                <path d="M7 11V7a5 5 0 0110 0v4"/>
                            </svg>
                            <input type="password" name="password" id="signupPassword" placeholder="Minimum 8 caractères" required autocomplete="new-password">
                            <button type="button" class="password-toggle" onclick="togglePassword('signupPassword', this)">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" name="signup">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                            <circle cx="8.5" cy="7" r="4"/>
                            <line x1="20" y1="8" x2="20" y2="14"/>
                            <line x1="23" y1="11" x2="17" y2="11"/>
                        </svg>
                        Créer mon compte
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
let currentForm = 'login';
    
function switchTab(tab) {
    currentForm = tab;
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    event.target.classList.add('active');
    const wrapper = document.querySelector('.forms-wrapper');
    if (tab === 'signup') {
        wrapper.classList.add('show-signup');
    } else {
        wrapper.classList.remove('show-signup');
    }
}

function togglePassword(inputId, button) {
    const input = document.getElementById(inputId);
    const svg = button.querySelector('svg');
    
    if (input.type === 'password') {
        input.type = 'text';
        svg.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>';
    } else {
        input.type = 'password';
        svg.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
    }
}

let touchStartX = 0;
let touchEndX = 0;
const formContainer = document.querySelector('.form-container');
const wrapper = document.querySelector('.forms-wrapper');

formContainer.addEventListener('touchstart', (e) => {
    touchStartX = e.changedTouches[0].screenX;
}, false);
formContainer.addEventListener('touchend', (e) => {
    touchEndX = e.changedTouches[0].screenX;
    handleSwipe();
}, false);

function handleSwipe() {
    const swipeThreshold = 50;
    const diff = touchStartX - touchEndX;
    if (Math.abs(diff) > swipeThreshold) {
        if (diff > 0 && currentForm === 'login') {
            document.querySelectorAll('.tab')[1].click();
        } else if (diff < 0 && currentForm === 'signup') {
            document.querySelectorAll('.tab')[0].click();
        }
    }
}
if (window.innerWidth <= 480) {
    const swipeIndicator = document.querySelector('.swipe-indicator');
    if (swipeIndicator) {
        swipeIndicator.style.display = 'flex';
        setTimeout(() => {
            swipeIndicator.style.transition = 'opacity 0.5s';
            swipeIndicator.style.opacity = '0';
            setTimeout(() => {
                swipeIndicator.style.display = 'none';
            }, 500);
        }, 3000);
    }
}
</script>
</body>
</html>
