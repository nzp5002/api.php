<?php

class Database {
    public function getConnection() {
        try {
            $host = getenv('MYSQLHOST');
            $port = getenv('MYSQLPORT');
            $db   = getenv('MYSQLDATABASE');
            $user = getenv('MYSQLUSER');
            $pass = getenv('MYSQLPASSWORD');

            if (!$host || !$user || !$db) {
                throw new Exception('Variáveis MYSQL não encontradas');
            }

            $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";

            return new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
            ]);

        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                "status" => "error",
                "db_error" => $e->getMessage()
            ]);
            exit;
        }
    }
}
