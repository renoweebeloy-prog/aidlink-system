<?php
require_once __DIR__ . '/helpers.php';
$config = require __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Connection Error</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="auth-page">

    <main class="auth-shell">
        <section class="notice-card reveal">

            <h1>Database Connection Failed</h1>

            <p>
                The system cannot connect to the MySQL database.
                Please check your database configuration.
            </p>

            <?php if (isset($error)): ?>
                <div class="error-box">
                    <?= e($error->getMessage()) ?>
                </div>
            <?php endif; ?>

            <h3>Current Database Settings</h3>

            <pre>
Host: <?= e($config['db_host'] ?? '') ?>

Port: <?= e($config['db_port'] ?? '') ?>

Database: <?= e($config['db_name'] ?? '') ?>

Username: <?= e($config['db_user'] ?? '') ?>
            </pre>

            <a href="login.php" class="button">
                Back to Login
            </a>

        </section>
    </main>

</body>
</html>
