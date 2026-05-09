<?php
namespace TokStock;

use PDO;
use PDOException;

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $host = getenv('DB_HOST') ?: 'mariadb';
            $db   = getenv('MYSQL_DATABASE') ?: 'tok_stock_db';
            $user = getenv('MYSQL_USER') ?: 'tok_admin';
            $pass = getenv('MYSQL_PASSWORD') ?: 'tok_password';
            $charset = 'utf8mb4';

            $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$instance = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                // In production, log the error instead of displaying it.
                die("Veritabanı Bağlantı Hatası: " . $e->getMessage());
            }
        }

        return self::$instance;
    }
}
