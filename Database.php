<?php
class Database
{
    private static ?PDO $connection = null;

    public static function connect(): PDO
    {
        if (self::$connection !== null) {
            return self::$connection;
        }

        $config = require __DIR__ . '/config.php';

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $config['db_host'],
            $config['db_port'],
            $config['db_name']
        );

        try {
            self::$connection = new PDO($dsn, $config['db_user'], $config['db_pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            return self::$connection;
        } catch (PDOException $error) {
            http_response_code(500);
            require __DIR__ . '/../public/db-error.php';
            exit;
        }
    }
}
