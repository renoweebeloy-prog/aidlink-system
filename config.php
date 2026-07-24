<?php

return [
    'app_name' => 'AidLink',
    'db' => [
        // Mukuha ni sa Render Environment Variables, pero kung wala, 
        // gamiton niya kining imong default Supabase credentials.
        'host' => getenv('DB_HOST') ?: 'db.jfaqeporbfqacwklvhov.supabase.co',
        'name' => getenv('DB_NAME') ?: 'postgres',
        'user' => getenv('DB_USER') ?: 'postgres',
        'pass' => getenv('DB_PASS') ?: 'D3GNEtgqZezUV9b9',
        'port' => getenv('DB_PORT') ?: 5432,
    ]
];
