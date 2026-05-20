<?php
require_once __DIR__ . '/Database.php';

class Auth
{
    public static function login(string $email, string $password): bool
    {
        $pdo = Database::connect();
        $statement = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $statement->execute([$email]);
        $user = $statement->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user'] = [
                'id' => $user['id'],
                'fullname' => $user['fullname'],
                'email' => $user['email'],
                'phone' => $user['phone'],
                'role' => $user['role'],
                'avatar' => $user['avatar'] ?? null,
                'theme' => $user['theme'] ?? 'dark',
            ];

            self::log('Signed in: ' . $user['email']);
            return true;
        }

        return false;
    }

    public static function register(string $fullname, string $email, string $password, string $phone): bool
    {
        self::validateProfile($fullname, $email, $phone);

        if (strlen($password) < 6) {
            throw new InvalidArgumentException('Password must contain at least 6 characters.');
        }

        $pdo = Database::connect();
        $statement = $pdo->prepare(
            'INSERT INTO users (fullname, email, password, phone, role) VALUES (?, ?, ?, ?, ?)'
        );

        return $statement->execute([
            $fullname,
            $email,
            password_hash($password, PASSWORD_DEFAULT),
            $phone,
            'citizen',
        ]);
    }

    public static function requestPasswordReset(string $email, string $phone): string
    {
        $pdo = Database::connect();
        $statement = $pdo->prepare('SELECT id FROM users WHERE email = ? AND phone = ? LIMIT 1');
        $statement->execute([$email, $phone]);
        $user = $statement->fetch();

        if (!$user) {
            throw new InvalidArgumentException('No account matched the email and mobile number provided.');
        }

        $code = (string) random_int(100000, 999999);
        $expiresAt = date('Y-m-d H:i:s', time() + 900);

        $pdo->prepare('DELETE FROM password_resets WHERE user_id = ?')->execute([$user['id']]);
        $insert = $pdo->prepare(
            'INSERT INTO password_resets (user_id, code, expires_at) VALUES (?, ?, ?)'
        );
        $insert->execute([$user['id'], password_hash($code, PASSWORD_DEFAULT), $expiresAt]);

        self::log('Password verification code prepared for ' . $email);

        return $code;
    }

    public static function resetPassword(string $email, string $code, string $newPassword): bool
    {
        if (strlen($newPassword) < 6) {
            throw new InvalidArgumentException('Password must contain at least 6 characters.');
        }

        $pdo = Database::connect();
        $statement = $pdo->prepare(
            'SELECT pr.*, u.id AS account_id FROM password_resets pr JOIN users u ON u.id = pr.user_id WHERE u.email = ? ORDER BY pr.created_at DESC LIMIT 1'
        );
        $statement->execute([$email]);
        $reset = $statement->fetch();

        if (!$reset || strtotime($reset['expires_at']) < time() || !password_verify($code, $reset['code'])) {
            throw new InvalidArgumentException('Invalid or expired verification code.');
        }

        $update = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
        $update->execute([password_hash($newPassword, PASSWORD_DEFAULT), $reset['account_id']]);

        $pdo->prepare('DELETE FROM password_resets WHERE user_id = ?')->execute([$reset['account_id']]);
        self::log('Password changed through verification for ' . $email);

        return true;
    }


    public static function resetWithPreviousPassword(string $email, string $previousPassword, string $newPassword): bool
    {
        if (strlen($newPassword) < 6) {
            throw new InvalidArgumentException('Password must contain at least 6 characters.');
        }

        $pdo = Database::connect();
        $statement = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $statement->execute([$email]);
        $user = $statement->fetch();

        if (!$user || !password_verify($previousPassword, $user['password'])) {
            throw new InvalidArgumentException('Previous password did not match this account.');
        }

        $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')
            ->execute([password_hash($newPassword, PASSWORD_DEFAULT), $user['id']]);

        self::log('Password changed using previous password for ' . $email);
        return true;
    }

    public static function getSecurityQuestion(string $email): ?array
    {
        $statement = Database::connect()->prepare(
            'SELECT email, security_question FROM users WHERE email = ? AND security_question IS NOT NULL LIMIT 1'
        );
        $statement->execute([$email]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    public static function resetWithSecurityAnswer(string $email, string $answer, string $newPassword): bool
    {
        if (strlen($newPassword) < 6) {
            throw new InvalidArgumentException('Password must contain at least 6 characters.');
        }

        $pdo = Database::connect();
        $statement = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $statement->execute([$email]);
        $user = $statement->fetch();

        if (!$user || empty($user['security_answer']) || !password_verify(strtolower(trim($answer)), $user['security_answer'])) {
            throw new InvalidArgumentException('Security answer did not match this account.');
        }

        $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')
            ->execute([password_hash($newPassword, PASSWORD_DEFAULT), $user['id']]);

        self::log('Password changed using security question for ' . $email);
        return true;
    }

    public static function validateProfile(string $fullname, string $email, string $phone): void
    {
        if (strlen(trim($fullname)) < 3) {
            throw new InvalidArgumentException('Please enter a complete name.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Please enter a valid email address.');
        }

        if (!preg_match('/^09\d{9}$/', $phone)) {
            throw new InvalidArgumentException('Please enter a valid Philippine mobile number.');
        }
    }

    public static function logout(): void
    {
        session_destroy();
    }

    private static function log(string $activity): void
    {
        Database::connect()
            ->prepare('INSERT INTO system_logs (activity) VALUES (?)')
            ->execute([$activity]);
    }
}
