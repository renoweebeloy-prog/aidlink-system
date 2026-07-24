<?php
session_start();

// Gi-update ang path para automatic niya pangitaon ang 'app' folder
// bisan og naa ni sa sulod sa 'public' folder o naa sa gawas sa root folder.
$appDir = file_exists(__DIR__ . '/../app/Notification.php') ? __DIR__ . '/../app' : __DIR__ . '/app';
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
require __DIR__ . '/layout.php';
