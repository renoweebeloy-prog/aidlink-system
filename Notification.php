<?php

// SMART PATH FINDER para sa Database.php
$dbPath = file_exists(__DIR__ . '/Database.php') ? __DIR__ . '/Database.php' : __DIR__ . '/../app/Database.php';
require_once $dbPath;

class Notification
{
    public static function create(int $userId, string $title, string $body, string $link = 'dashboard.php'): void
    {
        $statement = Database::connect()->prepare(
            'INSERT INTO notifications (user_id, title, body, link, is_read) VALUES (?, ?, ?, ?, 0)'
        );

        $statement->execute([$userId, $title, $body, $link]);
    }

    // GIBALIK KINI NGA FUNCTION: Gikinahanglan ni sa ServiceRequest aron ma-notify ang Admins/Staff
    public static function createForRoles(array $roles, string $title, string $body, string $link = 'dashboard.php'): void
    {
        $placeholders = implode(',', array_fill(0, count($roles), '?'));
        $statement = Database::connect()->prepare("SELECT id FROM users WHERE role IN ($placeholders)");
        $statement->execute($roles);

        foreach ($statement->fetchAll() as $user) {
            self::create((int) $user['id'], $title, $body, $link);
        }
    }

    public static function unreadCount(int $userId): int
    {
        $statement = Database::connect()->prepare(
            'SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0 AND link NOT LIKE ?'
        );

        $statement->execute([$userId, 'messenger.php%']);

        return (int) $statement->fetchColumn();
    }

    public static function all(int $userId): array
    {
        $statement = Database::connect()->prepare(
            'SELECT * FROM notifications WHERE user_id = ? AND link NOT LIKE ? ORDER BY created_at DESC LIMIT 30'
        );

        $statement->execute([$userId, 'messenger.php%']);

        return $statement->fetchAll();
    }

    public static function markAllRead(int $userId): void
    {
        $statement = Database::connect()->prepare(
            'UPDATE notifications SET is_read = 1 WHERE user_id = ? AND link NOT LIKE ?'
        );

        $statement->execute([$userId, 'messenger.php%']);
    }

    public static function markOneRead(int $notificationId, int $userId): ?string
    {
        $pdo = Database::connect();

        $select = $pdo->prepare(
            'SELECT link FROM notifications WHERE id = ? AND user_id = ? LIMIT 1'
        );

        $select->execute([$notificationId, $userId]);

        $link = $select->fetchColumn();

        if (!$link) {
            return null;
        }

        $update = $pdo->prepare(
            'UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?'
        );

        $update->execute([$notificationId, $userId]);

        return (string) $link;
    }

    // IMONG BAG-ONG FUNCTION
    public static function deleteAll(int $userId): void
    {
        $statement = Database::connect()->prepare(
            'DELETE FROM notifications WHERE user_id = ? AND link NOT LIKE ?'
        );

        $statement->execute([$userId, 'messenger.php%']);
    }
}
