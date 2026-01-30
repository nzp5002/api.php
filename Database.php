<?php
/**
class Database {
    private $host = "127.0.0.1";
    private $db_name = "academia";
    private $username = "root";
    private $password = "root";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            error_log("Erro de Conexão: " . $e->getMessage());
        }
        return $this->conn;
    }
}

**/
class Database {
    private $host = "127.0.0.1";
    private $db_name = "academia"; 
    private $username = "root";
    private $password = "17061967"; // Geralmente vazio no Termux

    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name, 
                $this->username, 
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
        } catch(PDOException $e) {
            error_log("Erro de Conexão: " . $e->getMessage());
        }
        return $this->conn;
    }
}

