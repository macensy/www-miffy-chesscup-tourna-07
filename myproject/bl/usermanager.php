<?php
require_once "../model/database.php";
require_once "../model/userModel.php";

class usermanager {

    private $userModel;

    public function __construct() {
        $database = new Database();
        $db = $database->connectDB();
        $this->userModel = new userModel($db); 
    }

   public function addUserFunc($firstName, $lastName, $role = 'Player') {
    try {
        
        if($this->userModel->createUser($firstName, $lastName, 'N/A', 0, 0, $role)) {
            echo "true"; 
        } else {
            echo "false";
        }
    } catch (Exception $ex) {
        echo $ex->getMessage();
    }
}

    public function updateUserFunc($firstName, $lastName, $userID) {
        try{
            if($this->userModel->updateUser(uID: $userID, fName: $firstName, lName: $lastName)) {
                echo "User has been updated";
            }else{
                echo"Error is encountered while updating value to the database.";
            }
        }catch(PDOException $ex) {
            http_response_code(response_code: 501);
            echo $ex-> getMessage();
            exit;
        }
    }

    public function deleteUserFunc($userID) {
        try{
            if($this->userModel->deleteUser(uID: $userID)) {
                echo "User has been updated";
            }else{
                echo"Error is encountered while updating value to the database.";
            }
        }catch(PDOException $ex) {
            http_response_code(response_code: 501);
            echo $ex-> getMessage(); 
            exit;
        }
    }

    public function getUser(): mixed {
       $response = $this->userModel->readUser();
       return $response->fetchAll(PDO::FETCH_ASSOC);
    }

    public function loginUserFunc($fName, $lName): void {
        $sql = "SELECT * FROM tbl_players WHERE firstName = ? AND lastName = ?";
            $stmt = $this->userModel->db->prepare($sql); 
            $stmt->execute([$fName, $lName]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if($user) {
                session_start();
                $_SESSION['user'] = $user;
                echo "true";
            } else {
                echo "false";
            }
        }
        ?>