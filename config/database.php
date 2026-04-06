<?php

declare(strict_types=1);

class Database
{
    private static ?PDO $instance = null;

    public static function connect(): PDO
    {
        if (self::$instance === null) {
            $hostConfig = getenv('DB_HOST') ?: '127.0.0.1,localhost';
            $hosts = array_values(array_filter(array_map('trim', explode(',', $hostConfig))));
            if ($hosts === []) {
                $hosts = ['127.0.0.1', 'localhost'];
            }

            $port = getenv('DB_PORT') ?: '3306';
            $dbname = getenv('DB_NAME') ?: 'sisprot';
            $user = getenv('DB_USER') ?: 'root';
            $pass = getenv('DB_PASS') ?: '';

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 3,
            ];

            $lastException = null;

            foreach ($hosts as $host) {
                $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

                try {
                    self::$instance = new PDO($dsn, $user, $pass, $options);
                    break;
                } catch (PDOException $e) {
                    $lastException = $e;
                }
            }

            if (self::$instance === null) {
                throw new RuntimeException(
                    'Nao foi possivel conectar ao MySQL. Verifique se o servico do MySQL no XAMPP esta iniciado e se as variaveis DB_HOST/DB_PORT estao corretas.',
                    0,
                    $lastException
                );
            }
        }

        return self::$instance;
    }
}
