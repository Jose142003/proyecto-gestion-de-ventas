<?php
class Database {
    private $host = 'localhost';
    private $dbname = 'carrito_db';
    private $username = 'root';
    private $password = '';
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->dbname, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            error_log("Error de conexión: " . $exception->getMessage());
            throw $exception;
        }
        return $this->conn;
    }
}

// Agregar esta función
function conectarDB() {
    $database = new Database();
    return $database->getConnection();
}
?>