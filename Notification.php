<?php
session_start();

require_once __DIR__ . '/app/Notification.php';
require_once __DIR__ . '/app/helpers.php';

require_login();

$user = current_user();

if (isset($_POST['mark_all'])) {
    Notification::markAllRead((int) $user['id']);
    redirect('notifications.php');
}

if (isset($_POST['delete_all'])) {
    Notification::deleteAll((int) $user['id']);
    redirect('notifications.php');
}

if (isset($_GET['read'])) {

    $link = Notification::markOneRead(
        (int) $_GET['read'],
        (int) $user['id']
    );

    if ($link) {
        redirect($link);
    }
}

$notifications = Notification::all((int) $user['id']);

ob_start();
?>

<section class="page-head reveal">
    <div>
        <span class="eyebrow">Alerts</span>
        <h1>Notifications</h1>
        <p class="lead">
            View your latest system notifications and updates.
        </p>
    </div>
</section>

<section class="panel reveal">

    <div style="display:flex; gap:10px; margin-bottom:20px; flex-wrap:wrap;">

        <form method="POST">
            <button class="small-button" name="mark_all">
                Mark all as read
            </button>
        </form>

        <form method="POST">
            <button class="small-button danger-button" name="delete_all">
                Delete all
            </button>
        </form>

    </div>

    <?php if (!$notifications): ?>

        <div class="notice-box">
            No notifications available.
        </div>

    <?php endif; ?>

    <div class="notification-list">

        <?php foreach ($notifications as $notification): ?>

            <article class="notification-card <?= !$notification['is_read'] ? 'unread' : '' ?>">

                <h3>
                    <?= e($notification['title']) ?>
                </h3>

                <p>
                    <?= e($notification['body']) ?>
                </p>

                <small class="muted">
                    <?= e($notification['created_at']) ?>
                </small>

                <div style="margin-top:15px;">

                    <a
                        class="small-button"
                        href="notifications.php?read=<?= (int) $notification['id'] ?>"
                    >
                        Open
                    </a>

                </div>

            </article>

        <?php endforeach; ?>

    </div>

</section>

<?php
$content = ob_get_clean();

$title = 'Notifications - AidLink';

require __DIR__ . '/layout.php';
?>
