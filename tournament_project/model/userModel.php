<?php
class userModel {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function createUser($fName, $lName, $gender, $age, $rating): mixed {
        $sql = "INSERT INTO tbl_players 
                (firstName, lastName, gender, age, rating, score, isAdmin) 
                VALUES (?, ?, ?, ?, ?, 0, 0)";
        return $this->conn->prepare($sql)->execute([$fName, $lName, $gender, $age, $rating]);
    }

    public function readUser(): mixed {
        return $this->conn->query("SELECT * FROM tbl_players ORDER BY userID ASC");
    }

    public function searchUser($fName, $lName): mixed {
        $stmt = $this->conn->prepare("SELECT * FROM tbl_players WHERE firstName=? AND lastName=?");
        $stmt->execute([$fName, $lName]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getLeaderboard() {
        return $this->conn->query("SELECT * FROM tbl_players ORDER BY score DESC, rating DESC");
    }
}
?>