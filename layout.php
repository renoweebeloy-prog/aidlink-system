<?php
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/Notification.php';
require_once __DIR__ . '/Messenger.php';

$config = require __DIR__ . '/config.php';

$user = current_user();
$theme = $user['theme'] ?? ($_COOKIE['aidlink_theme'] ?? 'dark');

$navItems = [
    ['dashboard.php', 'Dashboard', '⌂'],
    ['requests.php', 'Aid Requests', '◫'],
    ['queue.php', 'Coordination Queue', '↔'],
    ['messenger.php', 'Messenger', '✉'],
    ['reports.php', 'Reports', '▤'],
    ['tools.php', 'Tools', '⌘'],
    ['settings.php', 'Settings', '⚙'],
];

if ($user && $user['role'] === 'admin') {
    $navItems[] = ['users.php', 'Accounts', '◎'];
}

$current = basename($_SERVER['PHP_SELF']);

$notificationCount = 0;
$messageCount = 0;
$topNotifications = [];

if ($user) {
    if (class_exists('Notification')) {
        $notificationCount = Notification::unreadCount((int) $user['id']);
        $topNotifications = array_slice(Notification::all((int) $user['id']), 0, 5);
    }

    if (class_exists('Messenger')) {
        $messageCount = Messenger::unreadCount((int) $user['id']);
    }
}

if (!function_exists('icon_svg')) {
    function icon_svg(string $name): string
    {
        $icons = [
            'mail' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="m3 7 9 6 9-6"></path></svg>',
            'bell' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>',
            'theme' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a9 9 0 1 0 9 9 7 7 0 0 1-9-9Z"></path></svg>',
        ];

        return $icons[$name] ?? '';
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= e($theme) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? ($config['app_name'] ?? 'AidLink')) ?></title>

    <link rel="stylesheet" href="style.css">

    <?php if ($user): ?>
    <script>
        window.AIDLINK_CURRENT_USER_ID = <?= (int) $user['id'] ?>;
        window.AIDLINK_CURRENT_PAGE = "<?= e(basename($_SERVER['PHP_SELF'])) ?>";
        window.AIDLINK_ACTIVE_CONVERSATION_ID = <?= isset($activeId) ? (int) $activeId : 0 ?>;
    </script>
    <?php endif; ?>

    <script defer src="app.js"></script>
</head>

<body class="<?= $user ? 'app-page' : 'auth-page' ?>">
    <div class="aidlink-wallpaper" aria-hidden="true">
        <span class="bubble bubble-one"></span>
        <span class="bubble bubble-two"></span>
        <span class="bubble bubble-three"></span>
        <span class="bubble bubble-four"></span>
        <span class="bubble bubble-five"></span>
        <span class="bubble bubble-six"></span>
        <span class="aurora aurora-one"></span>
        <span class="aurora aurora-two"></span>
        <span class="aurora aurora-three"></span>
    </div>

    <?php if (!$user): ?>
        <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
            <?= icon_svg('theme') ?>
        </button>
    <?php endif; ?>

    <?php if ($user): ?>
        <button class="menu-toggle" id="menuToggle" aria-label="Open navigation">
            <span></span><span></span><span></span>
        </button>

        <div class="top-actions">
            <a class="icon-button" href="messenger.php" aria-label="Open messenger" data-count="<?= $messageCount ?>">
                <?= icon_svg('mail') ?>
                <?php if ($messageCount > 0): ?>
                    <span class="badge"><?= $messageCount ?></span>
                <?php endif; ?>
            </a>

            <a class="icon-button" href="notifications.php" aria-label="Open notifications" data-count="<?= $notificationCount ?>">
                <?= icon_svg('bell') ?>
                <?php if ($notificationCount > 0): ?>
                    <span class="badge"><?= $notificationCount ?></span>
                <?php endif; ?>
            </a>

            <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
                <?= icon_svg('theme') ?>
            </button>
        </div>

        <div class="overlay" id="overlay"></div>

        <aside class="sidebar" id="sidebar">
            <div class="profile-block">
                <?php if (!empty($user['avatar'])): ?>
                    <img src="<?= e($user['avatar']) ?>" class="avatar" alt="Profile photo">
                <?php else: ?>
                    <div class="brand-mark"><?= e(initials($user['fullname'])) ?></div>
                <?php endif; ?>

                <div>
                    <h2>AidLink</h2>
                    <p><?= e(role_label($user['role'])) ?></p>
                </div>
            </div>

            <nav class="nav-links">
                <?php foreach ($navItems as [$href, $label, $icon]): ?>
                    <a class="<?= $current === $href ? 'active' : '' ?>" href="<?= e($href) ?>">
                        <span><?= e($icon) ?></span><?= e($label) ?>
                    </a>
                <?php endforeach; ?>

                <a href="logout.php"><span>↳</span>Logout</a>
            </nav>
        </aside>
    <?php endif; ?>

    <main class="<?= $user ? 'main-shell' : 'auth-shell' ?>">
        <?= $content ?? '' ?>
    </main>
</body>
</html>
