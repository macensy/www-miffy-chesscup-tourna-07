<?php
require_once "../model/database.php";
require_once "../model/departmentModel.php";

class DepartmentManager {

    private $departmentModel;

    public function __construct() {
        $database = new Database();
        $db = $database->connectDB();
        $this->departmentModel = new DepartmentModel($db); 
    }


    public function readDepartment(): mixed {
       $response = $this->departmentModel->readDepartment();
       return $response->fetchAll(PDO::FETCH_ASSOC);
    }

        
    
}
?>