<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/RabbitMQ.php';

class Queue
{
    private static function ensureDeleteColumns(): void
    {
        $pdo = Database::connect();
        foreach (['recipient_deleted_at', 'admin_deleted_at', 'staff_deleted_at'] as $column) {
            try {
                $check = $pdo->prepare('SHOW COLUMNS FROM message_queue LIKE ?');
                $check->execute([$column]);
                if (!$check->fetch()) {
                    $pdo->exec("ALTER TABLE message_queue ADD COLUMN {$column} DATETIME NULL");
                }
            } catch (Throwable $error) {
                // Keep the system usable even if the database account has limited ALTER permission.
            }
        }
    }

    public static function publish(int $requestId, string $message): void
    {
        $pdo = Database::connect();
        $statement = $pdo->prepare(
            'INSERT INTO message_queue (request_id, message, status) VALUES (?, ?, ?)'
        );
        $statement->execute([$requestId, $message, 'queued']);

        $queueId = (int) $pdo->lastInsertId();

        RabbitMQ::publishCoordination([
            'queue_id' => $queueId,
            'request_id' => $requestId,
            'message' => $message,
            'event_type' => str_contains(strtolower($message), 'status') ? 'status_update' : 'new_request',
            'created_at' => date('Y-m-d H:i:s'),
            'source' => 'AidLink',
        ]);
    }

    public static function consume(int $queueId): void
    {
        $pdo = Database::connect();

        $select = $pdo->prepare(
            'SELECT mq.id, mq.request_id, mq.message, mq.status, sr.user_id, sr.category
             FROM message_queue mq
             JOIN service_requests sr ON sr.id = mq.request_id
             WHERE mq.id = ?
             LIMIT 1'
        );
        $select->execute([$queueId]);
        $queueItem = $select->fetch();

        if (!$queueItem || $queueItem['status'] !== 'queued') {
            return;
        }

        $statement = $pdo->prepare(
            'UPDATE message_queue SET status = ?, acknowledged_at = NOW() WHERE id = ?'
        );
        $statement->execute(['acknowledged', $queueId]);

        // RabbitMQ active queue countdown:
        // Once the staff/admin acknowledges this MySQL queue item, consume the matching RabbitMQ message.
        RabbitMQ::consumeByRequest((int) $queueItem['request_id'], RabbitMQ::COORDINATION_QUEUE);

        if ($queueItem['message'] === 'New community aid request received.') {
            require_once __DIR__ . '/Notification.php';

            Notification::create(
                (int) $queueItem['user_id'],
                'Aid request acknowledged',
                'Your aid request for ' . $queueItem['category'] . ' has been reviewed by the coordination team.',
                'queue.php'
            );
        }
    }

    public static function delete(int $queueId, array $actor): void
    {
        self::ensureDeleteColumns();
        $pdo = Database::connect();
        $role = $actor['role'] ?? '';
        $userId = (int) ($actor['id'] ?? 0);

        if ($role === 'admin' || $role === 'staff') {
            $column = $role === 'staff' ? 'staff_deleted_at' : 'admin_deleted_at';
            $statement = $pdo->prepare("UPDATE message_queue SET {$column} = NOW() WHERE id = ?");
            $statement->execute([$queueId]);
            return;
        }

        $statement = $pdo->prepare(
            'UPDATE message_queue mq
             JOIN service_requests sr ON sr.id = mq.request_id
             SET mq.recipient_deleted_at = NOW()
             WHERE mq.id = ? AND sr.user_id = ? AND mq.status = ?'
        );
        $statement->execute([$queueId, $userId, 'acknowledged']);
    }

    public static function activeCount(?int $userId = null, ?string $viewerRole = null): int
    {
        self::ensureDeleteColumns();
        $sql = 'SELECT COUNT(*)
                FROM message_queue mq
                JOIN service_requests sr ON sr.id = mq.request_id
                WHERE mq.status = ?';
        $params = ['queued'];

        if ($userId) {
            $sql .= ' AND sr.user_id = ? AND mq.recipient_deleted_at IS NULL';
            $params[] = $userId;
        } else {
            $deleteColumn = $viewerRole === 'staff' ? 'staff_deleted_at' : 'admin_deleted_at';
            $sql .= " AND mq.{$deleteColumn} IS NULL";
        }

        $statement = Database::connect()->prepare($sql);
        $statement->execute($params);
        return (int) $statement->fetchColumn();
    }

    public static function all(?int $userId = null, ?string $viewerRole = null): array
    {
        self::ensureDeleteColumns();
        $sql = 'SELECT mq.*, sr.category, sr.user_id, u.fullname
                FROM message_queue mq
                JOIN service_requests sr ON sr.id = mq.request_id
                JOIN users u ON u.id = sr.user_id';

        if ($userId) {
            $statement = Database::connect()->prepare(
                $sql . ' WHERE sr.user_id = ? AND mq.recipient_deleted_at IS NULL ORDER BY mq.created_at DESC'
            );
            $statement->execute([$userId]);
            return $statement->fetchAll();
        }

        $deleteColumn = $viewerRole === 'staff' ? 'staff_deleted_at' : 'admin_deleted_at';
        return Database::connect()
            ->query($sql . " WHERE mq.{$deleteColumn} IS NULL ORDER BY mq.created_at DESC")
            ->fetchAll();
    }
}
