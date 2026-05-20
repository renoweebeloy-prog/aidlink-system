<?php
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/RabbitMQ.php';

$user = Auth::requireLogin();

$message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    RabbitMQ::publishCoordination([
        'queue_id' => 0,
        'request_id' => 0,
        'message' => 'Manual RabbitMQ test from AidLink.',
        'event_type' => 'manual_test',
        'created_at' => date('Y-m-d H:i:s'),
        'source' => 'AidLink RabbitMQ Test Page',
        'sent_by' => $user['fullname'] ?? 'Unknown user',
    ]);

    $message = 'Test message sent. Open RabbitMQ Management and check queue: aidlink_coordination_queue.';
}

ob_start();
?>

<section class="hero">
    <span class="eyebrow">RabbitMQ Test</span>
    <h1>RabbitMQ Connection Check</h1>
    <p>Use this page to confirm if AidLink can publish messages to RabbitMQ.</p>
</section>

<div class="card">
    <?php if ($message): ?>
        <div class="notice success"><?= e($message) ?></div>
    <?php endif; ?>

    <p>
        This test sends one message to RabbitMQ queue <strong>aidlink_coordination_queue</strong>.
        If it does not appear in RabbitMQ, check <code>storage/rabbitmq.log</code>.
    </p>

    <form method="post" onsubmit="return confirm('Send a RabbitMQ test message?');">
        <button class="button" type="submit">Send RabbitMQ Test Message</button>
    </form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
