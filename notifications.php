<?php
session_start();

// SMART PATH FINDER: Pangitaon nato ang 'app' folder bisan asa pa nimo nabutang
$appDir = null;
$possible_paths = [
    __DIR__ . '/../app',
    __DIR__ . '/app',
    __DIR__ . '/../aidlink/app',
    __DIR__ . '/aidlink/app'
];

foreach ($possible_paths as $path) {
    if (file_exists($path . '/Notification.php')) {
        $appDir = $path;
        break;
    }
}

// Kung wala gyud makit-i, mag-print ta og debug error aron makita nato unsa ray naa sa GitHub nimo
if (!$appDir) {
    die("<div style='padding:20px; font-family:sans-serif; color:#721c24; background:#f8d7da; border:1px solid #f5c6cb;'>
        <h2>Missing Folder Error</h2>
        <p>Wala makit-i sa Render ang imong <b>app/Notification.php</b> nga file.</p>
        <p>Palihug i-check sa imong <b>GitHub Repository</b> kung:</p>
        <ol>
            <li>Na-upload ba gyud nimo ang tibuok <b>app</b> folder?</li>
            <li>Naka-capital ba ang 'N' sa <b>Notification.php</b>?</li>
        </ol>
        <hr>
        <p>Kini ang mga files nga nabasa sa Render server karon:</p>
        <pre>" . print_r(scandir(__DIR__), true) . "</pre>
    </div>");
}

require_once $appDir . '/Notification.php';
require_once $appDir . '/helpers.php';
require_login();

$user = current_user();

if (isset($_GET['open'])) {
    $link = Notification::markOneRead((int) $_GET['open'], (int) $user['id']);
    redirect($link ?: 'notifications.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    Notification::markAllRead((int) $user['id']);
    redirect('notifications.php');
}

$notifications = Notification::all((int) $user['id']);

ob_start();
?>
<section class="page-head reveal">
    <div>
        <span class="eyebrow">Notifications</span>
        <h1>Updates</h1>
        <p class="lead">Review request updates, account notices, and office activity connected to your account.</p>
    </div>
    <form method="POST">
        <button class="button secondary" name="mark_read" type="submit">Mark all as read</button>
    </form>
</section>

<section class="notification-list">
    <?php foreach ($notifications as $notice): ?>
        <a class="notification-item <?= (int) $notice['is_read'] === 0 ? 'unread' : '' ?>" href="notifications.php?open=<?= (int) $notice['id'] ?>">
            <strong><?= e($notice['title']) ?></strong>
            <p><?= e($notice['body']) ?></p>
            <span class="muted"><?= e($notice['created_at']) ?></span>
        </a>
    <?php endforeach; ?>
    <?php if (!$notifications): ?>
        <article class="panel"><p class="muted">No notifications yet.</p></article>
    <?php endif; ?>
</section>
<?php
$content = ob_get_clean();
$title = 'Notifications - AidLink';

// Auto-detect layout.php
$layoutPath = file_exists(__DIR__ . '/layout.php') ? __DIR__ . '/layout.php' : __DIR__ . '/../public/layout.php';
require $layoutPath;
