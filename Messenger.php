<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/RabbitMQ.php';

class Messenger
{
    private static function ensureDeliveryColumn(): void
    {
        $pdo = Database::connect();

        try {
            $check = $pdo->prepare('SHOW COLUMNS FROM chat_messages LIKE ?');
            $check->execute(['delivered_at']);

            if (!$check->fetch()) {
                $pdo->exec('ALTER TABLE chat_messages ADD COLUMN delivered_at DATETIME NULL');
                $pdo->exec('UPDATE chat_messages SET delivered_at = created_at WHERE delivered_at IS NULL');
            }
        } catch (Throwable $error) {
            // Keep messenger usable even if ALTER permission is limited.
        }
    }

    public static function markDelivered(int $messageId): void
    {
        self::ensureDeliveryColumn();

        $statement = Database::connect()->prepare(
            'UPDATE chat_messages SET delivered_at = NOW() WHERE id = ? AND delivered_at IS NULL'
        );

        $statement->execute([$messageId]);
    }

    public static function conversations(int $userId): array
    {
        self::ensureDeliveryColumn();

        $sql = 'SELECT c.*, 
                       (SELECT body 
                        FROM chat_messages 
                        WHERE conversation_id = c.id
                          AND (sender_id = ? OR delivered_at IS NOT NULL)
                        ORDER BY created_at DESC 
                        LIMIT 1) AS last_message,
                       (SELECT created_at 
                        FROM chat_messages 
                        WHERE conversation_id = c.id
                          AND (sender_id = ? OR delivered_at IS NOT NULL)
                        ORDER BY created_at DESC 
                        LIMIT 1) AS last_time,
                       (SELECT COUNT(*) 
                        FROM chat_messages cm 
                        WHERE cm.conversation_id = c.id 
                          AND cm.sender_id <> ? 
                          AND cm.delivered_at IS NOT NULL
                          AND cm.id > COALESCE(m.last_read_message_id, 0)) AS unread
                FROM conversations c
                JOIN conversation_members m ON m.conversation_id = c.id
                WHERE m.user_id = ?
                ORDER BY COALESCE(last_time, c.created_at) DESC';
        $statement = Database::connect()->prepare($sql);
        $statement->execute([$userId, $userId, $userId, $userId]);
        return $statement->fetchAll();
    }

    public static function unreadCount(int $userId): int
    {
        self::ensureDeliveryColumn();

        $statement = Database::connect()->prepare(
            'SELECT COUNT(*)
             FROM chat_messages cm
             JOIN conversation_members m ON m.conversation_id = cm.conversation_id AND m.user_id = ?
             WHERE cm.sender_id <> ? 
               AND cm.delivered_at IS NOT NULL
               AND cm.id > COALESCE(m.last_read_message_id, 0)'
        );
        $statement->execute([$userId, $userId]);
        return (int) $statement->fetchColumn();
    }

    public static function findConversation(int $conversationId, int $userId): ?array
    {
        $statement = Database::connect()->prepare(
            'SELECT c.* FROM conversations c JOIN conversation_members m ON m.conversation_id = c.id WHERE c.id = ? AND m.user_id = ? LIMIT 1'
        );
        $statement->execute([$conversationId, $userId]);
        $conversation = $statement->fetch();
        return $conversation ?: null;
    }

    public static function messages(int $conversationId, int $userId): array
    {
        self::ensureDeliveryColumn();
        if (!self::findConversation($conversationId, $userId)) {
            return [];
        }

        $statement = Database::connect()->prepare(
            'SELECT cm.*, u.fullname, u.avatar FROM chat_messages cm JOIN users u ON u.id = cm.sender_id WHERE cm.conversation_id = ? AND (cm.sender_id = ? OR cm.delivered_at IS NOT NULL) ORDER BY cm.created_at ASC'
        );
        $statement->execute([$conversationId, $userId]);
        $messages = $statement->fetchAll();

        $lastId = 0;
        foreach ($messages as $message) {
            $lastId = max($lastId, (int) $message['id']);
        }

        Database::connect()->prepare(
            'UPDATE conversation_members SET last_read_message_id = ? WHERE conversation_id = ? AND user_id = ?'
        )->execute([$lastId, $conversationId, $userId]);

        return $messages;
    }

    public static function send(int $conversationId, int $senderId, string $body): void
    {
        self::ensureDeliveryColumn();
        $body = trim($body);
        if ($body === '') {
            throw new InvalidArgumentException('Message cannot be empty.');
        }

        if (!self::findConversation($conversationId, $senderId)) {
            throw new InvalidArgumentException('Conversation is not available.');
        }

        $pdo = Database::connect();
        $statement = $pdo->prepare(
            'INSERT INTO chat_messages (conversation_id, sender_id, body) VALUES (?, ?, ?)'
        );
        $statement->execute([$conversationId, $senderId, $body]);

        $messageId = (int) $pdo->lastInsertId();

        $receiverStatement = $pdo->prepare(
            'SELECT user_id FROM conversation_members WHERE conversation_id = ? AND user_id != ?'
        );
        $receiverStatement->execute([$conversationId, $senderId]);
        $receiverIds = array_map('intval', $receiverStatement->fetchAll(PDO::FETCH_COLUMN));
        $receiverStatement = $pdo->prepare(
            'SELECT user_id FROM conversation_members WHERE conversation_id = ? AND user_id != ?'
        );
        $receiverStatement->execute([$conversationId, $senderId]);
        $receiverIds = array_map('intval', $receiverStatement->fetchAll(PDO::FETCH_COLUMN));

        RabbitMQ::publishMessengerToUsers([
            'message_id' => $messageId,
            'conversation_id' => $conversationId,
            'sender_id' => $senderId,
            'message' => $body,
            'created_at' => date('Y-m-d H:i:s'),
            'source' => 'AidLink Messenger',
        ], $receiverIds);

}

    public static function createConversation(string $title, array $memberIds, int $creatorId, bool $isGroup): int
    {
        $memberIds[] = $creatorId;
        $memberIds = array_values(array_unique(array_map('intval', $memberIds)));

        if (count($memberIds) < 2) {
            throw new InvalidArgumentException('Choose at least one recipient.');
        }

        $pdo = Database::connect();

        if (!$isGroup && count($memberIds) === 2) {
            $otherId = $memberIds[0] === $creatorId ? $memberIds[1] : $memberIds[0];
            $statement = $pdo->prepare(
                'SELECT c.id
                 FROM conversations c
                 JOIN conversation_members a ON a.conversation_id = c.id AND a.user_id = ?
                 JOIN conversation_members b ON b.conversation_id = c.id AND b.user_id = ?
                 WHERE c.is_group = 0
                 LIMIT 1'
            );
            $statement->execute([$creatorId, $otherId]);
            $existing = $statement->fetchColumn();

            if ($existing) {
                return (int) $existing;
            }
        }

        if (trim($title) === '') {
            if ($isGroup) {
                $title = 'Group Conversation';
            } else {
                $otherId = $memberIds[0] === $creatorId ? $memberIds[1] : $memberIds[0];
                $person = $pdo->prepare('SELECT fullname FROM users WHERE id = ?');
                $person->execute([$otherId]);
                $title = $person->fetchColumn() ?: 'Direct Message';
            }
        }

        $statement = $pdo->prepare(
            'INSERT INTO conversations (title, is_group, created_by) VALUES (?, ?, ?)'
        );
        $statement->execute([$title, $isGroup ? 1 : 0, $creatorId]);
        $conversationId = (int) $pdo->lastInsertId();

        $memberInsert = $pdo->prepare(
            'INSERT INTO conversation_members (conversation_id, user_id) VALUES (?, ?)'
        );
        foreach ($memberIds as $memberId) {
            $memberInsert->execute([$conversationId, $memberId]);
        }

        return $conversationId;
    }


    public static function findOrCreateDirect(int $creatorId, string $lookup): int
    {
        $target = self::findUserByLookup($lookup, $creatorId);

        if (!$target) {
            throw new InvalidArgumentException('No matching account was found. Search by full name, email, or mobile number.');
        }

        $pdo = Database::connect();
        $sql = 'SELECT c.id
                FROM conversations c
                JOIN conversation_members a ON a.conversation_id = c.id AND a.user_id = ?
                JOIN conversation_members b ON b.conversation_id = c.id AND b.user_id = ?
                WHERE c.is_group = 0
                LIMIT 1';
        $statement = $pdo->prepare($sql);
        $statement->execute([$creatorId, (int) $target['id']]);
        $existing = $statement->fetchColumn();

        if ($existing) {
            return (int) $existing;
        }

        return self::createConversation($target['fullname'], [(int) $target['id']], $creatorId, false);
    }

    public static function findUserByLookup(string $lookup, int $currentUserId): ?array
    {
        $lookup = trim($lookup);

        if ($lookup === '') {
            return null;
        }

        $like = '%' . $lookup . '%';
        $statement = Database::connect()->prepare(
            'SELECT id, fullname, email, phone, role
             FROM users
             WHERE id <> ? AND (fullname LIKE ? OR email LIKE ? OR phone LIKE ?)
             ORDER BY fullname ASC
             LIMIT 1'
        );
        $statement->execute([$currentUserId, $like, $like, $like]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    public static function addMembers(int $conversationId, int $requesterId, array $memberIds): void
    {
        $conversation = self::findConversation($conversationId, $requesterId);

        if (!$conversation || !(int) $conversation['is_group']) {
            throw new InvalidArgumentException('Members can only be added to group conversations.');
        }

        $memberIds = array_values(array_unique(array_map('intval', $memberIds)));
        $pdo = Database::connect();
        $insert = $pdo->prepare('INSERT IGNORE INTO conversation_members (conversation_id, user_id) VALUES (?, ?)');

        foreach ($memberIds as $memberId) {
            if ($memberId > 0 && $memberId !== $requesterId) {
                $insert->execute([$conversationId, $memberId]);
            }
        }
    }


    public static function deleteMessage(int $messageId, int $userId): void
    {
        $pdo = Database::connect();

        $statement = $pdo->prepare(
            'UPDATE chat_messages SET body = ? WHERE id = ? AND sender_id = ?'
        );
        $statement->execute(['Message deleted', $messageId, $userId]);
    }

    public static function members(int $conversationId): array
    {
        $statement = Database::connect()->prepare(
            'SELECT u.id, u.fullname, u.email, u.phone, u.role
             FROM conversation_members cm
             JOIN users u ON u.id = cm.user_id
             WHERE cm.conversation_id = ?
             ORDER BY u.fullname ASC'
        );
        $statement->execute([$conversationId]);
        return $statement->fetchAll();
    }

    public static function availableUsers(int $currentUserId): array
    {
        $statement = Database::connect()->prepare(
            'SELECT id, fullname, email, phone, role FROM users WHERE id <> ? ORDER BY fullname ASC'
        );
        $statement->execute([$currentUserId]);
        return $statement->fetchAll();
    }
}
