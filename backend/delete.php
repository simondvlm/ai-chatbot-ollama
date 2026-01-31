<?php
session_start();
require_once 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Non connecté']);
    exit();
}

$user_id = $_SESSION['user_id'];
$chat_id = $_POST['id'] ?? null;

if (!$chat_id) {
    echo json_encode(['error' => 'ID manquant']);
    exit();
}

try {
    $stmt = $pdo->prepare("DELETE FROM chat_history WHERE id = ? AND user_id = ?");
    $stmt->execute([$chat_id, $user_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Conversation non trouvée']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>