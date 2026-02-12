<?php
session_start();
require_once 'backend/config.php';
if (!isset($_SESSION['user_id'])) header("Location: login.php");

$user_id = $_SESSION['user_id'];
$message = "";
$stmtPrompts = $pdo->prepare("SELECT * FROM prompt_user WHERE user_id = ? ORDER BY date_creation DESC");
$stmtPrompts->execute([$user_id]);
$prompts = $stmtPrompts->fetchAll(PDO::FETCH_ASSOC);
if (isset($_POST['add_prompt'])) {
    $name = $_POST['prompt_name'] ?? '';
    $content = $_POST['prompt_content'] ?? '';
    if ($name && $content) {
        $stmt = $pdo->prepare("INSERT INTO prompt_user (user_id, nom_prompt, contenu_prompt, date_creation) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$user_id, $name, $content]);
        header("Location: " . $_SERVER['PHP_SELF'] . "?success=added");
        exit;
    }
}

// Supprimer prompt
if (isset($_POST['delete_prompt'])) {
    $id = $_POST['prompt_id'];
    $stmt = $pdo->prepare("DELETE FROM prompt_user WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    header("Location: " . $_SERVER['PHP_SELF'] . "?success=deleted");
    exit;
}

// Modifier prompt
if (isset($_POST['edit_prompt'])) {
    $id = $_POST['prompt_id'];
    $name = $_POST['prompt_name'];
    $content = $_POST['prompt_content'];
    $stmt = $pdo->prepare("UPDATE prompt_user SET nom_prompt=?, contenu_prompt=? WHERE id=? AND user_id=?");
    $stmt->execute([$name, $content, $id, $user_id]);
    header("Location: " . $_SERVER['PHP_SELF'] . "?success=updated");
    exit;
}
if (isset($_GET['success'])) {
    switch($_GET['success']) {
        case 'added': $message = "✓ Prompt ajouté avec succès !"; break;
        case 'updated': $message = "✓ Prompt mis à jour avec succès !"; break;
        case 'deleted': $message = "✓ Prompt supprimé avec succès !"; break;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mes Prompts</title>
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body { 
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica', 'Arial', sans-serif;
    background: #0f0f0f;
    color: #e8e8e8; 
    padding: 40px 20px;
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: flex-start;
}

.container {
    width: 100%;
    max-width: 800px;
}

.box { 
    background: #1a1a1a;
    padding: 30px;
    border-radius: 12px;
    border: 1px solid #2a2a2a;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
}

h2 { 
    text-align: center;
    margin-bottom: 25px;
    font-size: 28px;
    color: #e8e8e8;
    font-weight: 600;
}

.success-message {
    background: rgba(220, 38, 38, 0.1);
    border: 1px solid #dc2626;
    color: #dc2626;
    padding: 12px 16px;
    border-radius: 8px;
    text-align: center;
    margin-bottom: 20px;
    font-weight: 500;
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

.header-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    gap: 15px;
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #9a9a9a;
    text-decoration: none;
    font-size: 14px;
    padding: 8px 12px;
    border-radius: 6px;
    transition: all 0.2s;
}

.back-link:hover {
    color: #dc2626;
    background: #2a2a2a;
}

button { 
    padding: 10px 18px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.2s;
    font-size: 14px;
    font-family: inherit;
}

.btn-add { 
    background: #dc2626;
    color: white;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-add:hover { 
    background: #b91c1c;
    box-shadow: 0 0 12px rgba(220, 38, 38, 0.4);
    transform: translateY(-1px);
}

.btn-edit { 
    background: #2a2a2a;
    color: #e8e8e8;
    border: 1px solid #3a3a3a;
    padding: 6px 12px;
    font-size: 13px;
}

.btn-edit:hover { 
    background: #3a3a3a;
    border-color: #dc2626;
}

.btn-delete { 
    background: #7f1d1d;
    color: #e8e8e8;
    padding: 6px 12px;
    font-size: 13px;
}

.btn-delete:hover { 
    background: #991b1b;
    box-shadow: 0 0 8px rgba(127, 29, 29, 0.4);
}

.prompts-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.prompt-item { 
    background: #2a2a2a;
    padding: 16px;
    border-radius: 8px;
    border: 1px solid #3a3a3a;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.2s;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateX(-10px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.prompt-item:hover {
    border-color: #dc2626;
    background: #333333;
}

.prompt-info {
    flex: 1;
}

.prompt-name {
    font-weight: 600;
    font-size: 16px;
    color: #e8e8e8;
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.prompt-preview {
    font-size: 13px;
    color: #9a9a9a;
    max-width: 500px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.prompt-date {
    font-size: 11px;
    color: #6a6a6a;
    margin-top: 4px;
}

.prompt-actions {
    display: flex;
    gap: 8px;
    align-items: center;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #9a9a9a;
}

.empty-state-icon {
    font-size: 48px;
    margin-bottom: 16px;
    opacity: 0.5;
}

.empty-state p {
    margin-bottom: 20px;
}
.modal { 
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    justify-content: center;
    align-items: center;
    z-index: 1000;
    animation: fadeInModal 0.2s ease;
}

@keyframes fadeInModal {
    from { opacity: 0; }
    to { opacity: 1; }
}

.modal-content { 
    background: #1a1a1a;
    padding: 30px;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    border: 1px solid #2a2a2a;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.5);
    animation: slideUp 0.3s ease;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.modal-header h3 {
    font-size: 20px;
    color: #e8e8e8;
}

.close { 
    cursor: pointer;
    color: #9a9a9a;
    font-size: 24px;
    font-weight: bold;
    transition: color 0.2s;
    background: none;
    border: none;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.close:hover {
    color: #dc2626;
}

.form-group {
    margin-bottom: 16px;
}

.form-group label {
    display: block;
    margin-bottom: 6px;
    font-size: 14px;
    color: #9a9a9a;
    font-weight: 500;
}

input[type="text"], textarea { 
    width: 100%;
    padding: 10px 12px;
    border-radius: 6px;
    border: 1px solid #3a3a3a;
    background: #2a2a2a;
    color: #e8e8e8;
    font-family: inherit;
    font-size: 14px;
    transition: border-color 0.2s;
}

input[type="text"]:focus, textarea:focus {
    outline: none;
    border-color: #dc2626;
}

textarea {
    resize: vertical;
    min-height: 150px;
    line-height: 1.5;
}

.modal-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.btn-submit {
    flex: 1;
    background: #dc2626;
    color: white;
    padding: 12px;
}

.btn-submit:hover {
    background: #b91c1c;
    box-shadow: 0 0 12px rgba(220, 38, 38, 0.4);
}

.btn-cancel {
    background: #2a2a2a;
    color: #e8e8e8;
    border: 1px solid #3a3a3a;
    padding: 12px 20px;
}

.btn-cancel:hover {
    background: #3a3a3a;
}

@media (max-width: 768px) {
    body {
        padding: 20px 10px;
    }
    
    .box {
        padding: 20px;
    }
    
    .header-actions {
        flex-direction: column;
        align-items: stretch;
    }
    
    .prompt-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
    
    .prompt-actions {
        width: 100%;
        justify-content: flex-end;
    }
    
    .modal-content {
        padding: 20px;
    }
}
</style>
</head>
<body>
<div class="container">
    <div class="box">
        <h2>Mes Prompts</h2>
        
        <?php if($message): ?>
        <div class="success-message"><?=$message?></div>
        <?php endif; ?>

        <div class="header-actions">
            <a href="/ai-chatbot-ollama" class="back-link">← Retour au chat</a>
            <button class="btn-add" onclick="openModal('add')">
                <span>➕</span> Nouveau prompt
            </button>
        </div>
        <?php if(empty($prompts)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">📋</div>
            <p>Aucun prompt enregistré pour le moment</p>
            <button class="btn-add" onclick="openModal('add')">Créer votre premier prompt</button>
        </div>
        <?php else: ?>
        <div class="prompts-list">
            <?php foreach($prompts as $p): ?>
            <div class="prompt-item">
                <div class="prompt-info">
                    <div class="prompt-name"><?=htmlspecialchars($p['nom_prompt'])?></div>
                    <div class="prompt-preview"><?=htmlspecialchars($p['contenu_prompt'])?></div>
                    <div class="prompt-date">Créé le <?=date('d/m/Y à H:i', strtotime($p['date_creation']))?></div>
                </div>
                <div class="prompt-actions">
                    <button class="btn-edit" onclick="openModal('edit',<?=$p['id']?>,'<?=htmlspecialchars(addslashes($p['nom_prompt']))?>','<?=htmlspecialchars(addslashes($p['contenu_prompt']))?>')">✏️ Modifier</button>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="prompt_id" value="<?=$p['id']?>">
                        <button class="btn-delete" name="delete_prompt">🗑️ Supprimer</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>


<div class="modal" id="modal" onclick="if(event.target===this) closeModal()">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Nouveau prompt</h3>
            <button class="close" onclick="closeModal()">✖</button>
        </div>
        <form method="POST" id="modalForm">
            <input type="hidden" name="prompt_id" id="prompt_id">
            
            <div class="form-group">
                <label for="prompt_name">Nom du prompt</label>
                <input type="text" name="prompt_name" id="prompt_name" placeholder="Ex: Correction de texte" required>
            </div>
            
            <div class="form-group">
                <label for="prompt_content">Contenu du prompt</label>
                <textarea name="prompt_content" id="prompt_content" placeholder="Écrivez le contenu de votre prompt ici..." required></textarea>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeModal()">Annuler</button>
                <button type="submit" class="btn-submit" id="modalSubmit">Ajouter</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(type, id='', name='', content='') {
    const modal = document.getElementById('modal');
    const modalTitle = document.getElementById('modalTitle');
    const submitBtn = document.getElementById('modalSubmit');
    
    modal.style.display = 'flex';
    document.getElementById('prompt_id').value = id;
    document.getElementById('prompt_name').value = name;
    document.getElementById('prompt_content').value = content;
    
    if (type === 'add') {
        modalTitle.textContent = 'Nouveau prompt';
        submitBtn.name = 'add_prompt';
        submitBtn.textContent = 'Ajouter';
    } else {
        modalTitle.textContent = 'Modifier le prompt';
        submitBtn.name = 'edit_prompt';
        submitBtn.textContent = 'Enregistrer';
    }
    setTimeout(() => document.getElementById('prompt_name').focus(), 100);
}

function closeModal() {
    document.getElementById('modal').style.display = 'none';
    document.getElementById('modalForm').reset();
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && document.getElementById('modal').style.display === 'flex') {
        closeModal();
    }
});

<?php if($message): ?>
setTimeout(() => {
    const msg = document.querySelector('.success-message');
    if (msg) {
        msg.style.transition = 'opacity 0.3s';
        msg.style.opacity = '0';
        setTimeout(() => msg.remove(), 300);
    }
}, 3000);
<?php endif; ?>
</script>
</body>
</html>
