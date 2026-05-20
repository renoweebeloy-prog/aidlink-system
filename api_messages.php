<?php
session_start();
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/Messenger.php';
header('Content-Type: application/json');
$user = current_user();
if (!$user) {
    echo json_encode(['ok' => false, 'messages' => []]);
    exit;
}
$conversationId = (int) ($_GET['conversation_id'] ?? 0);
$messages = $conversationId ? Messenger::messages($conversationId, (int) $user['id']) : [];
echo json_encode([
    'ok' => true,
    'current_user_id' => (int) $user['id'],
    'messages' => array_map(function ($message) {
        return [
            'id' => (int) $message['id'],
            'sender_id' => (int) $message['sender_id'],
            'fullname' => $message['fullname'],
            'body' => $message['body'],
            'created_at' => $message['created_at'],
        ];
    }, $messages),
]);
