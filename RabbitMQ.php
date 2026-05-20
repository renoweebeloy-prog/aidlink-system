<?php

class RabbitMQ
{
    public const COORDINATION_QUEUE = 'aidlink_coordination_queue';
    public const REQUEST_STATUS_QUEUE = 'aidlink_request_status_queue';
    public const MESSENGER_QUEUE = 'aidlink_messenger_queue';

    private static function config(): array
    {
        return require __DIR__ . '/config.php';
    }

    public static function publish(array $payload, ?string $queueName = null): void
    {
        $config = self::config();

        if (empty($config['rabbitmq_enabled'])) {
            self::writeLocalLog('RabbitMQ is disabled in config.php.', $payload);
            return;
        }

        $queueName = $queueName ?: ($config['rabbitmq_queue'] ?? self::COORDINATION_QUEUE);

        if (($config['rabbitmq_publish_method'] ?? 'http_api') === 'http_api') {
            if (self::publishViaHttpApi($payload, $config, $queueName)) {
                return;
            }
        }

        if (self::publishViaPhpAmqpLib($payload, $config, $queueName)) {
            return;
        }

        self::writeLocalLog(
            'RabbitMQ publish was not completed. Check RabbitMQ credentials/config.',
            $payload
        );
    }

    public static function publishCoordination(array $payload): void
    {
        $payload['queue_group'] = 'coordination';
        self::publish($payload, self::COORDINATION_QUEUE);
    }

    public static function publishRequestStatus(array $payload): void
    {
        $payload['queue_group'] = 'request_status';
        self::publish($payload, self::REQUEST_STATUS_QUEUE);
    }

    public static function publishMessenger(array $payload): void
    {
        $payload['queue_group'] = 'messenger';
        $payload['event_type'] = $payload['event_type'] ?? 'messenger_message';
        self::publish($payload, self::MESSENGER_QUEUE);
    }

    public static function messengerUserQueue(int $userId): string
    {
        return self::MESSENGER_QUEUE . '_user_' . $userId;
    }

    public static function publishMessengerToUsers(array $payload, array $receiverIds): void
    {
        $payload['queue_group'] = 'messenger';
        $payload['event_type'] = $payload['event_type'] ?? 'messenger_message';

        foreach ($receiverIds as $receiverId) {
            $receiverId = (int) $receiverId;

            if ($receiverId <= 0) {
                continue;
            }

            $payload['receiver_id'] = $receiverId;
            $payload['receiver_ids'] = [$receiverId];

            self::publish($payload, self::messengerUserQueue($receiverId));
        }
    }

    public static function replaceRequestStatus(int $requestId, array $payload): void
    {
        self::consumeByRequest($requestId, self::REQUEST_STATUS_QUEUE);

        $status = strtolower((string)($payload['status'] ?? ''));

        if (in_array($status, ['completed', 'rejected'], true)) {
            self::writeLocalLog('Request status queue consumed because request ended.', $payload);
            return;
        }

        self::publishRequestStatus($payload);
    }

    public static function consumeOne(?string $eventType = null, ?int $requestId = null, ?string $queueName = null): bool
    {
        $config = self::config();

        if (empty($config['rabbitmq_enabled'])) {
            return false;
        }

        $queueName = $queueName ?: ($config['rabbitmq_queue'] ?? self::COORDINATION_QUEUE);

        if (($config['rabbitmq_publish_method'] ?? 'http_api') === 'http_api') {
            return self::consumeOneViaHttpApi($config, $eventType, $requestId, $queueName);
        }

        return false;
    }

    public static function consumeByRequest(int $requestId, ?string $queueName = null): bool
    {
        return self::consumeOne(null, $requestId, $queueName);
    }

    private static function publishViaHttpApi(array $payload, array $config, string $queueName): bool
    {
        $host = $config['rabbitmq_host'] ?? '127.0.0.1';
        $managementPort = (int)($config['rabbitmq_management_port'] ?? 15672);
        $user = $config['rabbitmq_user'] ?? 'guest';
        $pass = $config['rabbitmq_pass'] ?? 'guest';
        $vhost = rawurlencode($config['rabbitmq_vhost'] ?? '/');

        $baseUrl = "http://{$host}:{$managementPort}/api";

        $queueUrl = "{$baseUrl}/queues/{$vhost}/" . rawurlencode($queueName);

        $queueBody = json_encode([
            'durable' => true,
            'auto_delete' => false,
            'arguments' => new stdClass(),
        ]);

        $queueResult = self::httpRequest('PUT', $queueUrl, $queueBody, $user, $pass);

        if (!$queueResult['ok']) {
            self::writeLocalLog('RabbitMQ HTTP queue declare failed for ' . $queueName . ': ' . $queueResult['error'], $payload);
            return false;
        }

        $publishUrl = "{$baseUrl}/exchanges/{$vhost}/amq.default/publish";

        $publishBody = json_encode([
            'properties' => [
                'delivery_mode' => 2,
                'content_type' => 'application/json',
            ],
            'routing_key' => $queueName,
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'payload_encoding' => 'string',
        ]);

        $publishResult = self::httpRequest('POST', $publishUrl, $publishBody, $user, $pass);

        if (!$publishResult['ok']) {
            self::writeLocalLog('RabbitMQ HTTP publish failed for ' . $queueName . ': ' . $publishResult['error'], $payload);
            return false;
        }

        $decoded = json_decode($publishResult['body'], true);

        if (isset($decoded['routed']) && $decoded['routed'] === true) {
            self::writeLocalLog('Published to RabbitMQ queue ' . $queueName . '.', $payload);
            return true;
        }

        self::writeLocalLog('RabbitMQ publish reached server but was not routed to ' . $queueName . '.', $payload);
        return false;
    }

    private static function consumeOneViaHttpApi(array $config, ?string $eventType, ?int $requestId, string $queueName): bool
    {
        $host = $config['rabbitmq_host'] ?? '127.0.0.1';
        $managementPort = (int)($config['rabbitmq_management_port'] ?? 15672);
        $user = $config['rabbitmq_user'] ?? 'guest';
        $pass = $config['rabbitmq_pass'] ?? 'guest';
        $vhost = rawurlencode($config['rabbitmq_vhost'] ?? '/');

        $url = "http://{$host}:{$managementPort}/api/queues/{$vhost}/" . rawurlencode($queueName) . "/get";

        $body = json_encode([
            'count' => 50,
            'ackmode' => 'ack_requeue_false',
            'encoding' => 'auto',
            'truncate' => 50000,
        ]);

        $result = self::httpRequest('POST', $url, $body, $user, $pass);

        if (!$result['ok']) {
            self::writeLocalLog('RabbitMQ consume failed for ' . $queueName . ': ' . $result['error']);
            return false;
        }

        $messages = json_decode($result['body'], true);

        if (!is_array($messages) || empty($messages)) {
            self::writeLocalLog('RabbitMQ consume found no messages in ' . $queueName . '.');
            return false;
        }

        $matched = false;
        $unmatched = [];

        foreach ($messages as $message) {
            $payloadRaw = $message['payload'] ?? '';
            $payload = json_decode($payloadRaw, true);

            if (!is_array($payload)) {
                continue;
            }

            $typeMatches = $eventType === null || ($payload['event_type'] ?? '') === $eventType;
            $requestMatches = $requestId === null || (int)($payload['request_id'] ?? 0) === $requestId;

            if (!$matched && $typeMatches && $requestMatches) {
                $matched = true;
                self::writeLocalLog('Consumed RabbitMQ message from ' . $queueName . '.', $payload);
                continue;
            }

            $unmatched[] = $payload;
        }

        foreach ($unmatched as $payload) {
            self::publish($payload, $queueName);
        }

        return $matched;
    }

    private static function publishViaPhpAmqpLib(array $payload, array $config, string $queueName): bool
    {
        $autoload = __DIR__ . '/vendor/autoload.php';

        if (!file_exists($autoload)) {
            self::writeLocalLog('php-amqplib is not installed. Skipping AMQP library method.', $payload);
            return false;
        }

        require_once $autoload;

        if (!class_exists('PhpAmqpLib\Connection\AMQPStreamConnection')) {
            self::writeLocalLog('php-amqplib classes not found after loading vendor/autoload.php.', $payload);
            return false;
        }

        try {
            $connectionClass = 'PhpAmqpLib\Connection\AMQPStreamConnection';
            $messageClass = 'PhpAmqpLib\Message\AMQPMessage';

            $connection = new $connectionClass(
                $config['rabbitmq_host'],
                (int)($config['rabbitmq_port']),
                $config['rabbitmq_user'],
                $config['rabbitmq_pass'],
                $config['rabbitmq_vhost']
            );

            $channel = $connection->channel();
            $channel->queue_declare($queueName, false, true, false, false);

            $message = new $messageClass(
                json_encode($payload, JSON_UNESCAPED_UNICODE),
                [
                    'delivery_mode' => 2,
                    'content_type' => 'application/json',
                    'timestamp' => time(),
                ]
            );

            $channel->basic_publish($message, '', $queueName);

            $channel->close();
            $connection->close();

            self::writeLocalLog('Published to RabbitMQ queue ' . $queueName . ' through php-amqplib.', $payload);
            return true;

        } catch (Throwable $error) {
            self::writeLocalLog('php-amqplib publish failed for ' . $queueName . ': ' . $error->getMessage(), $payload);
            return false;
        }
    }

    private static function httpRequest(string $method, string $url, string $body, string $user, string $pass): array
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);

            curl_setopt_array($ch, [
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_USERPWD => $user . ':' . $pass,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT => 3,
            ]);

            $response = curl_exec($ch);
            $error = curl_error($ch);
            $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

            curl_close($ch);

            return [
                'ok' => $response !== false && $status >= 200 && $status < 300,
                'status' => $status,
                'body' => (string)$response,
                'error' => $error ?: ('HTTP status ' . $status . ' body ' . (string)$response),
            ];
        }

        $context = stream_context_create([
            'http' => [
                'method' => $method,
                'header' =>
                    "Content-Type: application/json\r\n" .
                    "Authorization: Basic " . base64_encode($user . ':' . $pass) . "\r\n",
                'content' => $body,
                'timeout' => 3,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        $status = 0;

        if (!empty($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $matches)) {
            $status = (int)$matches[1];
        }

        return [
            'ok' => $response !== false && $status >= 200 && $status < 300,
            'status' => $status,
            'body' => (string)$response,
            'error' => 'HTTP status ' . $status . ' body ' . (string)$response,
        ];
    }

    public static function writeLocalLog(string $message, array $payload = []): void
    {
        $directory = __DIR__ . '/storage';

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $line = '[' . date('Y-m-d H:i:s') . '] ' . $message;

        if ($payload) {
            $line .= ' ' . json_encode($payload, JSON_UNESCAPED_UNICODE);
        }

        file_put_contents($directory . '/rabbitmq.log', $line . PHP_EOL, FILE_APPEND);
    }
}
