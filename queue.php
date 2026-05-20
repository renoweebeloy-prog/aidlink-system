<?php
session_start();

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Queue.php';
require_once __DIR__ . '/helpers.php';

require_login();

$user = current_user();

$isRecipient = $user['role'] === 'citizen';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['consume'])) {

    require_role(['admin', 'staff']);

    Queue::consume((int) $_POST['queue_id']);

    redirect('queue.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_queue'])) {

    Queue::delete((int) $_POST['queue_id'], $user);

    redirect('queue.php');
}

$items = $isRecipient
    ? Queue::all((int) $user['id'])
    : Queue::all(null, $user['role']);

ob_start();
?>

<section class="page-head reveal">
    <div>
        <span class="eyebrow">
            <?= $isRecipient ? 'Updates' : 'Queue Desk' ?>
        </span>

        <h1>
            <?= $isRecipient ? 'Aid Updates' : 'Coordination Queue' ?>
        </h1>

        <p class="lead">
            <?= $isRecipient
                ? 'View the latest notices connected to your submitted aid requests.'
                : 'Review queued aid records and mark entries once coordinator review is complete.' ?>
        </p>
    </div>
</section>

<section class="panel reveal">

    <h2>
        <?= $isRecipient ? 'My Updates' : 'Queue Records' ?>
    </h2>

    <div class="table-wrap">

        <table>

            <thead>
                <tr>

                    <?php if (!$isRecipient): ?>
                        <th>Recipient</th>
                    <?php endif; ?>

                    <th>Request</th>
                    <th>Notice</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Action</th>

                </tr>
            </thead>

            <tbody>

                <?php foreach ($items as $item): ?>

                    <tr>

                        <?php if (!$isRecipient): ?>
                            <td><?= e($item['fullname']) ?></td>
                        <?php endif; ?>

                        <td><?= e($item['category']) ?></td>

                        <td><?= e($item['message']) ?></td>

                        <td>
                            <span class="status">
                                <?= e($item['status']) ?>
                            </span>
                        </td>

                        <td><?= e($item['created_at']) ?></td>

                        <td>

                            <?php if (!$isRecipient): ?>

                                <?php if ($item['status'] === 'queued'): ?>

                                    <form method="POST">
                                        <input type="hidden" name="queue_id" value="<?= (int) $item['id'] ?>">

                                        <button class="small-button" name="consume">
                                            Acknowledge
                                        </button>
                                    </form>

                                <?php else: ?>

                                    <span class="muted">Completed</span>

                                <?php endif; ?>

                                <form class="compact-action" method="POST" data-confirm="Delete this queue record?">

                                    <input type="hidden" name="delete_queue" value="1">

                                    <input type="hidden" name="queue_id" value="<?= (int) $item['id'] ?>">

                                    <button class="icon-action danger-icon" type="submit" title="Delete">
                                        🗑
                                    </button>

                                </form>

                            <?php else: ?>

                                <?php if ($item['status'] === 'acknowledged'): ?>

                                    <form method="POST" data-confirm="Delete this acknowledged update?">

                                        <input type="hidden" name="delete_queue" value="1">

                                        <input type="hidden" name="queue_id" value="<?= (int) $item['id'] ?>">

                                        <button class="icon-action danger-icon" type="submit" title="Delete">
                                            🗑
                                        </button>

                                    </form>

                                <?php else: ?>

                                    <span class="muted">Awaiting review</span>

                                <?php endif; ?>

                            <?php endif; ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

                <?php if (!$items): ?>

                    <tr>
                        <td colspan="<?= $isRecipient ? 5 : 6 ?>">
                            No queue entries available.
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</section>

<?php
$content = ob_get_clean();

$title = 'Queue - AidLink';

require __DIR__ . '/layout.php';
?>
