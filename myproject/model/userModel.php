<?php


    class userModel{
        private $conn;

        public function __construct($db) {
            $this->conn = $db;
        }

        public function createUser($fName, $lName): mixed {
            $insertQuery = "INSERT INTO tbl_users (firstName, lastName, createdAt, updatedAt)VALUES(:firstName, :lastName)";
            $dateNow = date(format: 'Y-m-d H:i:s');

            $response = $this->conn->prepare($insertQuery);

            $response->bindParam(":firstName", $fName);
            $response->bindParam(":lastName", $lName);
            $response->bindParam(":createdAt", $dateNow);
            $response->bindParam(":updatedAt", $lName);
            
            return $response->execute();
        }

        public function readUser(): mixed {
            $selectQuery = "SELECT * FROM tbl_users";
            $response = $this->conn->prepare($selectQuery);
            $response -> execute();
            return $response;
        }

        public function readAdvancedUser(): mixed {
            $selectQuery = "SELECT * FROM tbl_users INNER JOIN tbl_departments ON tbl_users, deptID";
            $response = $this->conn->prepare($selectQuery);
            $response -> execute();
            return $response;

        }

        public function updateUser($uID, $fName, $lName):mixed {
            $updateQuery = "UPDATE tbl_users SET firstName = :firstName, lastName = :lastName WHERE userID = :userID";
            $response = $this->conn->prepare($updateQuery);

            
            $response->bindParam(":firstName", $fName);
            $response->bindParam(":lastName", $lName);
            $response->bindParam(":userID", $uID);

            $response -> execute();

            return $response;
            
        }

        public function deleteUser($uID):mixed {
            $deleteQuery = "DELETE FROM tbl_users WHERE userID = :userID";
            $response = $this->conn->prepare($deleteQuery);
            $response->bindParam(":userID", $uID);

            $response -> execute();

            return $response;



        }





    }


?>