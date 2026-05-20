<?php
return [
    'app_name' => 'AidLink',
    'db_host' => '127.0.0.1',
    'db_port' => '3307',
    'db_name' => 'aidlink',
    'db_user' => 'root',
    'db_pass' => 'access',

    // RabbitMQ backup integration. Keep enabled true for RabbitMQ demo;
    // the system still works if RabbitMQ is not running because it safely falls back to the database queue.
    'rabbitmq_enabled' => true,
    'rabbitmq_host' => '127.0.0.1',
    'rabbitmq_port' => 5672,
    'rabbitmq_user' => 'guest',
    'rabbitmq_pass' => 'guest',
    'rabbitmq_vhost' => '/',
    'rabbitmq_queue' => 'aidlink_coordination_queue',
    'rabbitmq_coordination_queue' => 'aidlink_coordination_queue',
    'rabbitmq_request_status_queue' => 'aidlink_request_status_queue',
    'rabbitmq_messenger_queue' => 'aidlink_messenger_queue',
    'rabbitmq_management_port' => 15672,
    'rabbitmq_publish_method' => 'http_api',
];
