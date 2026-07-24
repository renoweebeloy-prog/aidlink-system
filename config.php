<?php

return [
    'app_name' => 'AidLink',
    'db' => [
        'host' => getenv('DB_HOST') ?: 'aws-0-ap-southeast-1.pooler.supabase.com',
        'name' => getenv('DB_NAME') ?: 'postgres',
        'user' => getenv('DB_USER') ?: 'postgres.jfaqeporbfqacwklvhov',
        'pass' => getenv('DB_PASS') ?: 'D3GNEtgqZezUV9b9',
        'port' => getenv('DB_PORT') ?: 6543,
    ]
];
