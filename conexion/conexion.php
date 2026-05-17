<?php

require_once __DIR__ . '/../config/database.php';

class Database {
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
                DB_USER,
                DB_PASS
            );
            $this->conn->exec("set names " . DB_CHARSET);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $exception) {
            error_log("Error de conexión: " . $exception->getMessage());
            throw $exception;
        }
        return $this->conn;
    }
}

function conectarDB() {
    $database = new Database();
    return $database->getConnection();
}