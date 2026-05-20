<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';

class User
{
    public static function find(int $id): ?array
    {
        $statement = Database::connect()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $statement->execute([$id]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    private static function ensureSecurityPlainColumn(): void
    {
        try {
            Database::connect()->exec('ALTER TABLE users ADD COLUMN security_answer_plain TEXT NULL');
        } catch (Throwable $exception) {
            // Column already exists or database user cannot alter it.
        }
    }

    public static function verifyCurrentPassword(int $id, string $currentPassword): bool
    {
        $user = self::find($id);

        return $user && password_verify($currentPassword, $user['password']);
    }

    public static function revealSecurityAnswer(int $id, string $currentPassword): string
    {
        self::ensureSecurityPlainColumn();

        $user = self::find($id);

        if (!$user || !password_verify($currentPassword, $user['password'])) {
            throw new InvalidArgumentException('Current password is incorrect.');
        }

        if (empty($user['security_answer_plain'])) {
            throw new InvalidArgumentException('Saved answers from older versions cannot be displayed. Re-save your recovery question to enable secure viewing.');
        }

        $decoded = base64_decode((string) $user['security_answer_plain'], true);

        if ($decoded === false || $decoded === '') {
            throw new InvalidArgumentException('Recovery answer is not available.');
        }

        return $decoded;
    }

    public static function all(): array
    {
        return Database::connect()
            ->query('SELECT id, fullname, email, phone, role, avatar, theme, security_question, created_at FROM users ORDER BY created_at DESC')
            ->fetchAll();
    }

    public static function createByAdmin(string $fullname, string $email, string $phone, string $password, string $role): void
    {
        Auth::validateProfile($fullname, $email, $phone);

        if (!in_array($role, ['admin', 'staff', 'citizen'], true)) {
            throw new InvalidArgumentException('Invalid account role selected.');
        }

        if (strlen($password) < 6) {
            throw new InvalidArgumentException('Password must contain at least 6 characters.');
        }

        $statement = Database::connect()->prepare(
            'INSERT INTO users (fullname, email, phone, password, role) VALUES (?, ?, ?, ?, ?)'
        );

        $statement->execute([
            $fullname,
            $email,
            $phone,
            password_hash($password, PASSWORD_DEFAULT),
            $role
        ]);
    }

    public static function updateTheme(int $id, string $theme): void
    {
        if (!in_array($theme, ['dark', 'light'], true)) {
            return;
        }

        Database::connect()
            ->prepare('UPDATE users SET theme = ? WHERE id = ?')
            ->execute([$theme, $id]);
    }

    public static function updateProfile(int $id, string $fullname, string $phone, string $theme): void
    {
        $current = self::find($id);

        Auth::validateProfile($fullname, $current['email'] ?? 'account@example.com', $phone);

        if (!in_array($theme, ['dark', 'light'], true)) {
            $theme = 'dark';
        }

        $statement = Database::connect()->prepare(
            'UPDATE users SET fullname = ?, phone = ?, theme = ? WHERE id = ?'
        );

        $statement->execute([$fullname, $phone, $theme, $id]);
    }

    public static function updateSecurityQuestion(int $id, string $question, string $answer, string $currentPassword): void
    {
        self::ensureSecurityPlainColumn();

        $user = self::find($id);

        if (!$user || !password_verify($currentPassword, $user['password'])) {
            throw new InvalidArgumentException('Enter your current password before changing the recovery question.');
        }

        $question = trim($question);
        $answer = trim($answer);

        if (strlen($question) < 8) {
            throw new InvalidArgumentException('Security question must be clear and specific.');
        }

        if (strlen($answer) < 3) {
            throw new InvalidArgumentException('Security answer must contain at least 3 characters.');
        }

        Database::connect()
            ->prepare('UPDATE users SET security_question = ?, security_answer = ?, security_answer_plain = ? WHERE id = ?')
            ->execute([
                $question,
                password_hash(strtolower($answer), PASSWORD_DEFAULT),
                base64_encode($answer),
                $id
            ]);
    }

    public static function changePassword(int $id, string $currentPassword, string $newPassword): void
    {
        $user = self::find($id);

        if (!$user || !password_verify($currentPassword, $user['password'])) {
            throw new InvalidArgumentException('Current password is incorrect.');
        }

        if (strlen($newPassword) < 6) {
            throw new InvalidArgumentException('New password must contain at least 6 characters.');
        }

        $statement = Database::connect()->prepare(
            'UPDATE users SET password = ? WHERE id = ?'
        );

        $statement->execute([
            password_hash($newPassword, PASSWORD_DEFAULT),
            $id
        ]);
    }

    public static function updateAvatar(int $id, array $file): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('Profile photo upload failed.');
        }

        if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
            throw new InvalidArgumentException('Profile photo must be 2MB or smaller.');
        }

        $extension = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($extension, $allowed, true)) {
            throw new InvalidArgumentException('Profile photo must be JPG, PNG, or WEBP.');
        }

        $uploadDir = __DIR__ . '/uploads/avatars/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $filename = 'avatar_' . $id . '_' . time() . '.' . $extension;
        $destination = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new InvalidArgumentException('Unable to save the uploaded photo.');
        }

        $path = 'uploads/avatars/' . $filename;

        Database::connect()
            ->prepare('UPDATE users SET avatar = ? WHERE id = ?')
            ->execute([$path, $id]);

        return $path;
    }

    public static function removeAvatar(int $id): void
    {
        $user = self::find($id);

        if (!$user || empty($user['avatar'])) {
            Database::connect()
                ->prepare('UPDATE users SET avatar = NULL WHERE id = ?')
                ->execute([$id]);

            return;
        }

        $relativePath = (string) $user['avatar'];

        $fullPath = realpath(__DIR__ . '/' . $relativePath);
        $avatarRoot = realpath(__DIR__ . '/uploads/avatars');

        if ($fullPath && $avatarRoot && str_starts_with($fullPath, $avatarRoot) && is_file($fullPath)) {
            @unlink($fullPath);
        }

        Database::connect()
            ->prepare('UPDATE users SET avatar = NULL WHERE id = ?')
            ->execute([$id]);
    }
}
