<?php
class Database {
    private static $instance = null;

    private $host     = "localhost";
    private $dbName   = "db_tournament";
    private $username = "root";
    private $password = "";

    public function connectDB() {
        if (self::$instance !== null) {
            return self::$instance;
        }
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbName};charset=utf8mb4";
            $conn = new PDO($dsn, $this->username, $this->password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            self::$instance = $conn;
            return $conn;
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }
}
