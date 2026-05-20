<?php

require_once __DIR__ . '/../app/RabbitMQ.php';
require_once __DIR__ . '/../app/Database.php';

$config = require __DIR__ . '/../app/config.php';
$autoload = __DIR__ . '/../vendor/autoload.php';

if (!file_exists($autoload)) {
    echo "php-amqplib is not installed. Run: composer install
";
    exit(1);
}

require_once $autoload;

use PhpAmqpLib\Connection\AMQPStreamConnection;

$connection = new AMQPStreamConnection(
    $config['rabbitmq_host'],
    (int) $config['rabbitmq_port'],
    $config['rabbitmq_user'],
    $config['rabbitmq_pass'],
    $config['rabbitmq_vhost']
);

$channel = $connection->channel();
$queueName = $config['rabbitmq_queue'];
$channel->queue_declare($queueName, false, true, false, false);

RabbitMQ::writeLocalLog('RabbitMQ consumer started.');
echo "AidLink RabbitMQ consumer is running. Press CTRL+C to stop.
";

$callback = function ($message) {
    $body = $message->body;
    $payload = json_decode($body, true) ?: ['raw' => $body];

    RabbitMQ::writeLocalLog('Consumed from RabbitMQ.', $payload);

    try {
        $pdo = Database::connect();
        $statement = $pdo->prepare('INSERT INTO system_logs (activity) VALUES (?)');
        $statement->execute(['RabbitMQ consumed event: ' . ($payload['message'] ?? 'AidLink queue event')]);
    } catch (Throwable $error) {
        RabbitMQ::writeLocalLog('Unable to write consumed event to system logs: ' . $error->getMessage(), $payload);
    }

    $message->ack();
};

$channel->basic_qos(null, 1, null);
$channel->basic_consume($queueName, '', false, false, false, false, $callback);

while ($channel->is_consuming()) {
    $channel->wait();
}

$channel->close();
$connection->close();
