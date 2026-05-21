<?php
session_start();

require_once __DIR__ . '/app/Notification.php';
require_once __DIR__ . '/app/helpers.php';

require_login();

$user = current_user();

if (isset($_GET['read'])) {
    $link = Notification::markOneRead(
        (int) $_GET['read'],
        (int) $user['id']
    );

    if ($link) {
        header('Location: ' . $link);
        exit;
    }
}

if (isset($_POST['mark_all'])) {
    Notification::markAllRead((int) $user['id']);
    header('Location: notifications.php');
    exit;
}

$notifications = Notification::all((int) $user['id']);

ob_start();
?>

<section class="page-head reveal">
    <div>
        <span class="eyebrow">Notifications</span>
        <h1>Alerts & Updates</h1>
        <p class="lead">View recent aid request updates and announcements.</p>
    </div>
</section>

<section class="panel reveal">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
        <h2>My Notifications</h2>

        <form method="POST">
            <button class="small-button" name="mark_all">
                Mark all as read
            </button>
        </form>
    </div>

    <?php if ($notifications): ?>

        <div class="notification-list">

            <?php foreach ($notifications as $notification): ?>

                <a
                    href="notifications.php?read=<?= (int) $notification['id'] ?>"
                    class="notification-card <?= !$notification['is_read'] ? 'unread' : '' ?>"
                    style="
                        display:block;
                        padding:18px;
                        border-radius:18px;
                        margin-bottom:15px;
                        text-decoration:none;
                        background:rgba(255,255,255,.05);
                        border:1px solid rgba(255,255,255,.08);
                        color:white;
                    "
                >
                    <h3><?= e($notification['title']) ?></h3>

                    <p><?= e($notification['body']) ?></p>

                    <small style="opacity:.7;">
                        <?= e($notification['created_at']) ?>
                    </small>
                </a>

            <?php endforeach; ?>

        </div>

    <?php else: ?>

        <p class="muted">No notifications available.</p>

    <?php endif; ?>
</section>

<?php
$content = ob_get_clean();
$title = 'Notifications - AidLink';

require __DIR__ . '/layout.php';
?>
