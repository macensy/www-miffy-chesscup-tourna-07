<?php


    class DepartmentModel{

        private $conn;
        public function __construct($db)
        {
            $this->conn = $db;
        }

        public function readDepartmentModel(): mixed {
            $selectQuery = "SELECT* FROM tbl_users";
            $response = $this->conn->prepare($selectQuery);
            $response->execute();
            return $response;



        }





    }


?>