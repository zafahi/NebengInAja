<?php
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    public function __construct() {
        // Otomatis ambil dari ENV (Railway) jika ada, fallback ke lokal
        $this->host = getenv('MYSQLHOST') ?: 'localhost';
        $this->db_name = getenv('MYSQLDATABASE') ?: 'nebeng_db';
        $this->username = getenv('MYSQLUSER') ?: 'root';
        $this->password = getenv('MYSQLPASSWORD') ? getenv('MYSQLPASSWORD') : '';
    }

    public function getConnection() {
        $this->conn = null;
        try {
            $port = getenv('MYSQLPORT') ?: '3306';
            $dsn = "mysql:host={$this->host};port={$port};dbname={$this->db_name};charset=utf8mb4";
            
            // Set timeout options
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5, // 5 seconds timeout
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ];
            
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            $this->conn->exec("set names utf8mb4");
        } catch(PDOException $exception) {
            error_log("Database Connection Error: " . $exception->getMessage());
            error_log("Host: {$this->host}, Port: " . (getenv('MYSQLPORT') ?: '3306') . ", DB: {$this->db_name}, User: {$this->username}");
            http_response_code(500);
            die(json_encode([
                'success' => false,
                'message' => 'Database connection failed: ' . $exception->getMessage()
            ]));
        }
        return $this->conn;
    }
}
