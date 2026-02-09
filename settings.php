<?php
session_start();
require_once 'backend/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";
$messageType = "success"; // success ou error

// Récupérer infos utilisateur
$stmt = $pdo->prepare("SELECT username, email, avatar, theme FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

//  UPLOAD AVATAR 
if (isset($_POST['update_avatar'])) {
    if (isset($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] === 0) {
        $file = $_FILES['avatar_file'];
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $allowed = ['jpg','jpeg','png','webp','gif'];

        if (in_array(strtolower($ext), $allowed)) {
            $filename = "avatars/".uniqid().".".$ext;
            move_uploaded_file($file['tmp_name'], $filename);

            $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
            $stmt->execute([$filename, $user_id]);
            $message = "✓ Avatar mis à jour avec succès !";
            $user['avatar'] = $filename;
        } else {
            $message = "⚠ Format non autorisé (jpg, png, webp, gif)";
            $messageType = "error";
        }
    } elseif (!empty($_POST['avatar_url'])) {
        $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
        $stmt->execute([$_POST['avatar_url'], $user_id]);
        $message = "✓ Avatar mis à jour avec succès !";
        $user['avatar'] = $_POST['avatar_url'];
    } else {
        $message = "⚠ Veuillez choisir un fichier ou mettre une URL";
        $messageType = "error";
    }
}

//  CHANGER MOT DE PASSE 
if (isset($_POST['update_password'])) {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];

    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $data = $stmt->fetch();

    if (password_verify($current, $data['password'])) {
        $newHash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$newHash, $user_id]);
        $message = "✓ Mot de passe modifié avec succès !";
    } else {
        $message = "⚠ Mot de passe actuel incorrect";
        $messageType = "error";
    }
}

//  CHANGER PHRASE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_system'])) {
    $newSystem = $_POST['system_sentences'] ?? '';
    $stmt = $pdo->prepare("UPDATE users SET system_sentences = ? WHERE id = ?");
    $stmt->execute([$newSystem, $_SESSION['user_id']]);
    $message = "✓ Phrase système mise à jour !";
    $user['system_sentences'] = $newSystem;
}
if (isset($_POST['update_theme'])) {
    $newTheme = $_POST['theme'];
    
    if (in_array($newTheme, ['dark', 'light'])) {
        $stmt = $pdo->prepare("UPDATE users SET theme = ? WHERE id = ?");
        $stmt->execute([$newTheme, $user_id]);
        $message = "✓ Thème modifié avec succès !";
        $user['theme'] = $newTheme;
        
        // Recharger la page pour appliquer le nouveau thème
        header("Location: settings.php");
        exit();
    } else {
        $message = "⚠ Thème invalide";
        $messageType = "error";
    }
}
//  SUPPRIMER COMPTE
if (isset($_POST['delete_account'])) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    session_destroy();
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Paramètres - Chat Ollama</title>
<style>
:root {
    --bg-primary: #0f0f0f;
    --bg-secondary: #1a1a1a;
    --bg-tertiary: #232323;
    --text-primary: #e8e8e8;
    --text-secondary: #b4b4b4;
    --text-muted: #737373;
    --accent: #10a37f;
    --accent-hover: #0d8c6d;
    --border: #2f2f2f;
    --error: #ef4444;
    --error-bg: #7f1d1d;
    --success: #10b981;
    --warning: #f59e0b;
}

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica', 'Arial', sans-serif;
    background: var(--bg-primary);
    color: var(--text-primary);
    min-height: 100vh;
    padding: 20px;
}

.container {
    max-width: 800px;
    margin: 0 auto;
}

.header {
    text-align: center;
    margin-bottom: 40px;
    padding-top: 20px;
}

.header h1 {
    font-size: 32px;
    font-weight: 600;
    margin-bottom: 8px;
    background: linear-gradient(135deg, var(--accent), #06b6d4);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.header p {
    color: var(--text-secondary);
    font-size: 14px;
}

/* Message notifications */
.message {
    padding: 14px 18px;
    border-radius: 10px;
    margin-bottom: 24px;
    font-size: 14px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 10px;
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.message.success {
    background: rgba(16, 185, 129, 0.1);
    border: 1px solid rgba(16, 185, 129, 0.3);
    color: var(--success);
}

.message.error {
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: var(--error);
}
.user-info {
    background: var(--bg-secondary);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 20px;
}

.avatar-display {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    border: 3px solid var(--border);
    object-fit: cover;
    flex-shrink: 0;
}

.user-details h2 {
    font-size: 20px;
    font-weight: 600;
    margin-bottom: 4px;
}

.user-details p {
    color: var(--text-secondary);
    font-size: 14px;
    margin: 2px 0;
}

.user-details .email {
    color: var(--text-muted);
}

/* Settings Sections */
.settings-section {
    background: var(--bg-secondary);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 20px;
}

.section-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--border);
}

.section-icon {
    width: 40px;
    height: 40px;
    background: var(--bg-tertiary);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}

.section-header h3 {
    font-size: 18px;
    font-weight: 600;
    color: var(--text-primary);
}

.section-header p {
    font-size: 13px;
    color: var(--text-secondary);
    margin-top: 2px;
}

.section-title {
    flex: 1;
}

/* Form Elements */
.form-group {
    margin-bottom: 20px;
}

.form-group:last-child {
    margin-bottom: 0;
}

label {
    display: block;
    font-size: 14px;
    font-weight: 500;
    color: var(--text-secondary);
    margin-bottom: 8px;
}

input[type="text"],
input[type="password"],
input[type="email"],
input[type="file"],
textarea,
select {
    width: 100%;
    padding: 12px 14px;
    background: var(--bg-tertiary);
    border: 1px solid var(--border);
    border-radius: 8px;
    color: var(--text-primary);
    font-size: 14px;
    font-family: inherit;
    transition: all 0.2s;
}

input:focus,
textarea:focus,
select:focus {
    outline: none;
    border-color: var(--accent);
    background: var(--bg-primary);
}

input::placeholder,
textarea::placeholder {
    color: var(--text-muted);
}

textarea {
    resize: vertical;
    min-height: 100px;
    line-height: 1.6;
}

input[type="file"] {
    padding: 10px;
    cursor: pointer;
}

input[type="file"]::file-selector-button {
    padding: 8px 16px;
    background: var(--bg-primary);
    border: 1px solid var(--border);
    border-radius: 6px;
    color: var(--text-primary);
    cursor: pointer;
    margin-right: 12px;
    transition: all 0.2s;
}

input[type="file"]::file-selector-button:hover {
    background: var(--bg-tertiary);
    border-color: var(--accent);
}

/* Buttons */
button,
.button {
    width: 100%;
    padding: 12px 20px;
    background: var(--accent);
    border: none;
    border-radius: 8px;
    color: white;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    text-decoration: none;
}

button:hover,
.button:hover {
    background: var(--accent-hover);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(16, 163, 127, 0.3);
}

button:active,
.button:active {
    transform: translateY(0);
}

.button-secondary {
    background: var(--bg-tertiary);
    border: 1px solid var(--border);
    color: var(--text-primary);
}

.button-secondary:hover {
    background: var(--bg-primary);
    border-color: var(--text-muted);
    box-shadow: none;
}

.button-danger {
    background: var(--error-bg);
    color: white;
}

.button-danger:hover {
    background: #991b1b;
    box-shadow: 0 4px 12px rgba(127, 29, 29, 0.4);
}
.danger-zone {
    background: rgba(127, 29, 29, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.danger-zone .section-icon {
    background: rgba(127, 29, 29, 0.2);
}

/* Back Link */
.back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--text-secondary);
    text-decoration: none;
    font-size: 14px;
    margin-top: 24px;
    padding: 8px 0;
    transition: color 0.2s;
}

.back-link:hover {
    color: var(--accent);
}

.back-link svg {
    width: 16px;
    height: 16px;
}
.avatar-options {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

@media (max-width: 640px) {
    .avatar-options {
        grid-template-columns: 1fr;
    }
}

.form-hint {
    font-size: 12px;
    color: var(--text-muted);
    margin-top: 6px;
    display: block;
}
.divider {
    height: 1px;
    background: var(--border);
    margin: 16px 0;
}
@media (max-width: 768px) {
    .user-info {
        flex-direction: column;
        text-align: center;
    }
    
    .header h1 {
        font-size: 24px;
    }
}
</style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>Paramètres</h1>
        <p>Gérez votre profil et vos préférences</p>
    </div>

    <?php if ($message): ?>
        <div class="message <?= $messageType ?>">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <!-- USER INFO -->
    <div class="user-info">
        <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="avatar" class="avatar-display" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($user['username']) ?>&background=10a37f&color=fff'">
        <div class="user-details">
            <h2><?= htmlspecialchars($user['username']) ?></h2>
            <p class="email"><?= htmlspecialchars($user['email']) ?></p>
        </div>
    </div>

    <!-- AVATAR SECTION -->
    <div class="settings-section">
        <div class="section-header">
            <div class="section-icon">🎨</div>
            <div class="section-title">
                <h3>Avatar</h3>
                <p>Personnalisez votre photo de profil</p>
            </div>
        </div>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="avatar-options">
                <div class="form-group">
                    <label>Importer un fichier</label>
                    <input type="file" name="avatar_file" accept="image/*">
                    <span class="form-hint">JPG, PNG, WEBP ou GIF</span>
                </div>
                
                <div class="form-group">
                    <label>Ou utiliser une URL</label>
                    <input type="text" name="avatar_url" placeholder="avatars/default.webp" value="<?= htmlspecialchars($user['avatar']) ?>">
                    <span class="form-hint">Lien vers une image en ligne</span>
                </div>
            </div>
            
            <div class="divider"></div>
            
            <button name="update_avatar" type="submit">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/>
                    <polyline points="17 21 17 13 7 13 7 21"/>
                    <polyline points="7 3 7 8 15 8"/>
                </svg>
                Enregistrer l'avatar
            </button>
        </form>
    </div>
    <div class="settings-section">
        <div class="section-header">
            <div class="section-icon">🔒</div>
            <div class="section-title">
                <h3>Sécurité</h3>
                <p>Modifiez votre mot de passe</p>
            </div>
        </div>
        
        <form method="POST">
            <div class="form-group">
                <label>Mot de passe actuel</label>
                <input type="password" name="current_password" placeholder="Entrez votre mot de passe actuel" required>
            </div>
            
            <div class="form-group">
                <label>Nouveau mot de passe</label>
                <input type="password" name="new_password" placeholder="Entrez votre nouveau mot de passe" required>
                <span class="form-hint">Minimum 8 caractères recommandés</span>
            </div>
            
            <div class="divider"></div>
            
            <button name="update_password" type="submit">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                    <path d="M7 11V7a5 5 0 0110 0v4"/>
                </svg>
                Mettre à jour le mot de passe
            </button>
        </form>
    </div>
    <div class="settings-section">
        <div class="section-header">
            <div class="section-icon">🌓</div>
            <div class="section-title">
                <h3>Thème</h3>
                <p>Choisissez votre mode d'affichage préféré</p>
            </div>
        </div>
        
        <form method="POST">
            <div class="form-group">
                <label>Mode d'affichage</label>
                <select name="theme" required>
                    <option value="dark" <?= ($user['theme'] ?? 'dark') === 'dark' ? 'selected' : '' ?>>🌙 Mode Sombre</option>
                    <option value="light" <?= ($user['theme'] ?? 'dark') === 'light' ? 'selected' : '' ?>>☀️ Mode Clair</option>
                </select>
                <span class="form-hint">Le thème sera appliqué immédiatement après enregistrement</span>
            </div>
            
            <div class="divider"></div>
            
            <button name="update_theme" type="submit">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="5"/>
                    <line x1="12" y1="1" x2="12" y2="3"/>
                    <line x1="12" y1="21" x2="12" y2="23"/>
                    <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
                    <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                    <line x1="1" y1="12" x2="3" y2="12"/>
                    <line x1="21" y1="12" x2="23" y2="12"/>
                    <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
                    <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                </svg>
                Enregistrer le thème
            </button>
        </form>
    </div>
    <div class="settings-section">
        <div class="section-header">
            <div class="section-icon">👤</div>
            <div class="section-title">
                <h3>Profils</h3>
                <p>Gérez vos profils de conversation</p>
            </div>
        </div>
        
        <form method="POST" action="profil.php">
            <button class="button-secondary" type="submit">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
                Gérer mes profils
            </button>
        </form>
    </div>

    <div class="settings-section">
        <div class="section-header">
            <div class="section-icon">📝</div>
            <div class="section-title">
                <h3>Prompts</h3>
                <p>Créer vos prompts pré-enrégistré</p>
            </div>
        </div>
        
        <form method="POST" action="prompts.php">
            <button class="button-secondary" type="submit">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
                Gérer mes prompts
            </button>
        </form>
    </div>
    <div class="settings-section danger-zone">
        <div class="section-header">
            <div class="section-icon">⚠️</div>
            <div class="section-title">
                <h3>Supprimer Votre Compte</h3>
                <p>Actions irréversibles sur votre compte</p>
            </div>
        </div>
        
        <form method="POST" onsubmit="return confirm('ATTENTION !\n\nÊtes-vous absolument sûr(e) de vouloir supprimer votre compte ?\n\nCette action est IRRÉVERSIBLE et supprimera :\n• Toutes vos conversations\n• Tous vos profils\n• Toutes vos données\n\nTapez OK pour confirmer.');">
            <button class="button-danger" name="delete_account" type="submit">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="3 6 5 6 21 6"/>
                    <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
                    <line x1="10" y1="11" x2="10" y2="17"/>
                    <line x1="14" y1="11" x2="14" y2="17"/>
                </svg>
                Supprimer définitivement mon compte
            </button>
            <span class="form-hint" style="text-align: center; color: var(--error); margin-top: 8px;">Cette action est irréversible et supprimera toutes vos données</span>
        </form>
    </div>

    <a href="/chatbot/chatbot.php" class="back-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="19" y1="12" x2="5" y2="12"/>
            <polyline points="12 19 5 12 12 5"/>
        </svg>
        Retour au chat
    </a>
</div>
</body>
</html>
