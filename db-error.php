<?php
$config = require __DIR__ . '/../app/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Connection</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <main class="auth-shell">
        <section class="notice-card reveal">
            <h1>Database connection needs attention</h1>
            <p>The application cannot connect to MySQL using the credentials in <code>app/config.php</code>.</p>
            <div class="error-box"><?= e($error->getMessage()) ?></div>
            <p>Current settings:</p>
            <pre>Host: <?= e($config['db_host']) ?>
Port: <?= e($config['db_port']) ?>
Database: <?= e($config['db_name']) ?>
Username: <?= e($config['db_user']) ?></pre>
        </section>
    </main>
</body>
</html>
