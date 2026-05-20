<?php
require_once __DIR__ . '/app/Messenger.php';

header('Content-Type: application/json');

$secret = $_POST['secret'] ?? '';
$messageId = (int) ($_POST['message_id'] ?? 0);

if ($secret !== 'aidlink_realtime_secret') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Invalid secret.']);
    exit;
}

if ($messageId <= 0) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Invalid message ID.']);
    exit;
}

Messenger::markDelivered($messageId);

echo json_encode(['ok' => true]);
