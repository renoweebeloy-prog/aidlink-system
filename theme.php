<?php
session_start();
require_once __DIR__ . '/app/helpers.php';
require_once __DIR__ . '/app/User.php';

header('Content-Type: application/json');

$user = current_user();
$theme = $_POST['theme'] ?? '';

if (!in_array($theme, ['dark', 'light'], true)) {
    echo json_encode(['ok' => false]);
    exit;
}

setcookie('aidlink_theme', $theme, time() + 31536000, '/');

if ($user) {
    User::updateTheme((int) $user['id'], $theme);
    refresh_current_user();
}

echo json_encode(['ok' => true, 'theme' => $theme]);
