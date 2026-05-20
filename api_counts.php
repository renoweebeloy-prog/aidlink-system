<?php
session_start();
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/Notification.php';
require_once __DIR__ . '/../app/Messenger.php';
header('Content-Type: application/json');
$user = current_user();
if (!$user) {
    echo json_encode(['ok' => false, 'message_count' => 0, 'notification_count' => 0]);
    exit;
}
echo json_encode([
    'ok' => true,
    'message_count' => Messenger::unreadCount((int) $user['id']),
    'notification_count' => Notification::unreadCount((int) $user['id']),
]);
