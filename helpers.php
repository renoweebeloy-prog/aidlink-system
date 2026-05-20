<?php
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function refresh_current_user(): void
{
    if (!isset($_SESSION['user'])) {
        return;
    }

    require_once __DIR__ . '/User.php';
    $fresh = User::find((int) $_SESSION['user']['id']);

    if ($fresh) {
        $_SESSION['user'] = [
            'id' => $fresh['id'],
            'fullname' => $fresh['fullname'],
            'email' => $fresh['email'],
            'phone' => $fresh['phone'],
            'role' => $fresh['role'],
            'avatar' => $fresh['avatar'],
            'theme' => $fresh['theme'],
        ];
    }
}

function require_login(): void
{
    if (!current_user()) {
        redirect('login.php');
    }
}

function require_role(array $roles): void
{
    require_login();

    if (!in_array(current_user()['role'], $roles, true)) {
        redirect('dashboard.php');
    }
}

function role_label(string $role): string
{
    return match ($role) {
        'admin' => 'Administrator',
        'staff' => 'Volunteer Coordinator',
        default => 'Recipient',
    };
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $first = $parts[0][0] ?? 'B';
    $last = count($parts) > 1 ? ($parts[count($parts) - 1][0] ?? '') : '';
    return strtoupper($first . $last);
}
