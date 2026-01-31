<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Non connecté']);
    exit();
}

$user_id = $_SESSION['user_id'];
$profile_id = $_POST['profile_id'] ?? null;
$messages = $_POST['messages'] ?? '';

if (!$profile_id) {
    echo json_encode(['error' => 'Aucun profil sélectionné']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT id FROM chat_history 
        WHERE user_id = ? AND profile_id = ? 
        AND DATE(created_at) = CURDATE()
        ORDER BY created_at DESC LIMIT 1
    ");
    $stmt->execute([$user_id, $profile_id]);
    $existing = $stmt->fetch();
    if ($existing) {
        $updateStmt = $pdo->prepare("
            UPDATE chat_history 
            SET messages = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        $updateStmt->execute([$messages, $existing['id']]);
        echo json_encode(['success' => true, 'action' => 'updated', 'id' => $existing['id']]);
    } else {
        $insertStmt = $pdo->prepare("
            INSERT INTO chat_history (user_id, profile_id, messages, created_at, updated_at) 
            VALUES (?, ?, ?, NOW(), NOW())
        ");
        $insertStmt->execute([$user_id, $profile_id, $messages]);
        echo json_encode(['success' => true, 'action' => 'created', 'id' => $pdo->lastInsertId()]);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>