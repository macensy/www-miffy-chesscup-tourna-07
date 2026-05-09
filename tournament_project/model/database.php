<?php
class Database {
    private $host = "localhost";
    private $dbName = "db_tournament";
    private $username = "root";
    private $password = "";

    public function connectDB() {
        try {
            $dsn = "mysql:host=$this->host;dbname=$this->dbName";
            $conn = new PDO($dsn, $this->username, $this->password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $conn;
        } catch(PDOException $e) {
            echo "Connection failed: " . $e->getMessage();
        }
    }
}
?>