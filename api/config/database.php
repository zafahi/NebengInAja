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
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->db_name}",
                $this->username, $this->password
            );
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
