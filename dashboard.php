<?php
session_start();

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/ServiceRequest.php';
require_once __DIR__ . '/Queue.php';
require_once __DIR__ . '/helpers.php';

require_login();
refresh_current_user();
$user = current_user();
$isRecipient = $user['role'] === 'citizen';
$requests = $isRecipient ? ServiceRequest::all((int) $user['id']) : ServiceRequest::all(null, $user['role']);
$queue = $isRecipient ? Queue::all((int) $user['id']) : Queue::all(null, $user['role']);
$activeQueueCount = $isRecipient ? Queue::activeCount((int) $user['id']) : Queue::activeCount(null, $user['role']);
$total = count($requests);
$pending = count(array_filter($requests, fn ($request) => $request['status'] === 'Pending'));
$resolved = count(array_filter($requests, fn ($request) => in_array($request['status'], ['Completed', 'Resolved'], true)));
$emergency = count(array_filter($requests, fn ($request) => ($request['urgency'] ?? '') === 'Emergency'));
$latest = array_slice($requests, 0, 6);

ob_start();
?>
<section class="page-head reveal">
    <div>
        <span class="eyebrow">Operations Center</span>
        <h1>Welcome, <?= e($user['fullname']) ?></h1>
        <p class="lead">
            <?= $isRecipient
                ? 'Track submitted assistance requests, updates, and coordinator responses in one secure workspace.'
                : 'Oversee donation activity, volunteer follow-ups, and clear aid records for the community.' ?>
        </p>
    </div>
    <a class="button" href="requests.php"><?= $isRecipient ? 'New Aid Request' : 'Open Aid Requests' ?></a>
</section>

<section class="stats-grid">
    <article class="stat-card reveal"><p>Total Requests</p><strong><?= $total ?></strong></article>
    <article class="stat-card reveal"><p>Pending</p><strong><?= $pending ?></strong></article>
    <article class="stat-card reveal"><p>Completed</p><strong><?= $resolved ?></strong></article>
    <article class="stat-card reveal"><p>Urgent Cases</p><strong><?= $emergency ?></strong></article>
    <article class="stat-card reveal"><p>Queue Events</p><strong><?= $activeQueueCount ?></strong></article>
</section>

<section class="panel reveal">
    <h2><?= $isRecipient ? 'My Recent Aid Requests' : 'Recent Activity' ?></h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <?php if (!$isRecipient): ?><th>Recipient</th><?php endif; ?>
                    <th>Category</th>
                    <th>Need</th>
                    <th>Urgency</th>
                    <th>Location</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($latest as $request): ?>
                    <tr>
                        <?php if (!$isRecipient): ?><td><?= e($request['fullname']) ?></td><?php endif; ?>
                        <td><?= e($request['category']) ?></td>
                        <td><?= e($request['quantity'] ?? 'Not specified') ?></td>
                        <td><span class="status urgency"><?= e($request['urgency'] ?? 'Medium') ?></span></td>
                        <td><?= e($request['location']) ?></td>
                        <td><span class="status"><?= e($request['status']) ?></span></td>
                        <td><?= e($request['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$latest): ?>
                    <tr><td colspan="7">No records available yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
$content = ob_get_clean();
$title = 'Dashboard - AidLink';
require __DIR__ . '/layout.php';
