<?php

// SMART PATH FINDER para sa mga dependencies
$appDir = file_exists(__DIR__ . '/app/Database.php') ? __DIR__ . '/app' : (file_exists(__DIR__ . '/../app/Database.php') ? __DIR__ . '/../app' : __DIR__);

require_once $appDir . '/Database.php';
require_once $appDir . '/Queue.php';
require_once $appDir . '/RabbitMQ.php';
require_once $appDir . '/Notification.php';

class ServiceRequest
{
    private static function ensureDeleteColumns(): void
    {
        $pdo = Database::connect();
        foreach (['recipient_deleted_at', 'admin_deleted_at', 'staff_deleted_at'] as $column) {
            try {
                // FIX: PostgreSQL syntax para pag-check sa column imbes nga MySQL 'SHOW COLUMNS'
                $check = $pdo->prepare("SELECT column_name FROM information_schema.columns WHERE table_name = 'service_requests' AND column_name = ?");
                $check->execute([$column]);
                
                if (!$check->fetch()) {
                    // FIX: Gigamit ang TIMESTAMP imbes nga DATETIME para sa PostgreSQL
                    $pdo->exec("ALTER TABLE service_requests ADD COLUMN {$column} TIMESTAMP NULL");
                }
            } catch (Throwable $error) {
                // Keep the system usable even if the database account has limited ALTER permission.
            }
        }
    }

    public static function create(int $userId, string $category, string $quantity, string $urgency, string $location, string $description): int
    {
        $pdo = Database::connect();
        $statement = $pdo->prepare(
            'INSERT INTO service_requests (user_id, category, quantity, urgency, location, description) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $statement->execute([$userId, $category, $quantity, $urgency, $location, $description]);

        $requestId = (int) $pdo->lastInsertId();
        Queue::publish($requestId, 'New community aid request received.');

        // Separate RabbitMQ queue for active aid requests.
        // One request = one active message until Completed or Rejected.
        RabbitMQ::publishRequestStatus([
            'request_id' => $requestId,
            'message' => 'New active aid request awaiting action.',
            'event_type' => 'request_status_state',
            'status' => 'Pending',
            'created_at' => date('Y-m-d H:i:s'),
            'source' => 'AidLink',
        ]);

        Notification::createForRoles(['admin', 'staff'], 'New aid request', 'A recipient submitted a request for review.', 'requests.php');
        self::log('Created request #' . $requestId);

        return $requestId;
    }

    public static function all(?int $userId = null, ?string $viewerRole = null): array
    {
        self::ensureDeleteColumns();
        $pdo = Database::connect();

        if ($userId) {
            $statement = $pdo->prepare(
                'SELECT sr.*, u.fullname
                 FROM service_requests sr
                 JOIN users u ON u.id = sr.user_id
                 WHERE sr.user_id = ? AND sr.recipient_deleted_at IS NULL
                 ORDER BY sr.created_at DESC'
            );
            $statement->execute([$userId]);
            return $statement->fetchAll(PDO::FETCH_ASSOC);
        }

        $deleteColumn = $viewerRole === 'staff' ? 'staff_deleted_at' : 'admin_deleted_at';
        $statement = $pdo->query(
            "SELECT sr.*, u.fullname
             FROM service_requests sr
             JOIN users u ON u.id = sr.user_id
             WHERE sr.{$deleteColumn} IS NULL
             ORDER BY sr.created_at DESC"
        );

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function updateStatus(int $requestId, string $status, string $remarks): void
    {
        $pdo = Database::connect();
        $statement = $pdo->prepare(
            'UPDATE service_requests SET status = ?, remarks = ?, updated_at = NOW() WHERE id = ?'
        );
        $statement->execute([$status, $remarks, $requestId]);

        $owner = $pdo->prepare('SELECT user_id FROM service_requests WHERE id = ? LIMIT 1');
        $owner->execute([$requestId]);
        $ownerId = (int) $owner->fetchColumn();

        Queue::publish($requestId, 'Aid request status updated to ' . $status . '.');

        // Request status RabbitMQ queue is separated from coordination queue.
        // Status changes replace the previous active status message.
        // Completed/Rejected consumes/removes the active request from RabbitMQ.
        RabbitMQ::replaceRequestStatus($requestId, [
            'request_id' => $requestId,
            'message' => 'Aid request current status: ' . $status . ($remarks ? ' - ' . $remarks : ''),
            'event_type' => 'request_status_state',
            'status' => $status,
            'remarks' => $remarks,
            'created_at' => date('Y-m-d H:i:s'),
            'source' => 'AidLink',
        ]);
        
        if ($ownerId > 0) {
            Notification::create(
                $ownerId,
                'Aid request status updated',
                'Your aid request was marked as ' . $status . ($remarks ? '. Message: ' . $remarks : '.'),
                'requests.php'
            );
        }
        self::log('Updated request #' . $requestId . ' to ' . $status);
    }

    public static function delete(int $requestId, array $actor): void
    {
        self::ensureDeleteColumns();
        $pdo = Database::connect();
        $role = $actor['role'] ?? '';
        $userId = (int) ($actor['id'] ?? 0);

        if ($role === 'admin' || $role === 'staff') {
            $column = $role === 'staff' ? 'staff_deleted_at' : 'admin_deleted_at';
            $statement = $pdo->prepare("UPDATE service_requests SET {$column} = NOW() WHERE id = ? AND (status = ? OR status = ?)");
            $statement->execute([$requestId, 'Completed', 'Rejected']);
            self::log(ucfirst($role) . ' hid completed request #' . $requestId . ' from their view');
            return;
        }

        // Recipient deletion is treated as cancelling/removing their own submitted request.
        // This intentionally removes it from admin and staff views too, because the requester owns the request.
        $statement = $pdo->prepare('DELETE FROM service_requests WHERE id = ? AND user_id = ?');
        $statement->execute([$requestId, $userId]);
        self::log('Recipient deleted own request #' . $requestId);
    }

    public static function log(string $activity): void
    {
        $pdo = Database::connect();
        $statement = $pdo->prepare('INSERT INTO system_logs (activity) VALUES (?)');
        $statement->execute([$activity]);
    }
}
