<?php
session_start();
require_once "../bl/usermanager.php";

$usermanager = new usermanager();

    if(isset($_POST["fName"], $_POST["lName"]) && !isset($_POST["uID"])) {
        
        $usermanager ->addUserFunc ($_POST["fName"], $_POST["lName"]);
        exit;
    } else if (isset($_POST["fName"], $_POST["lName"], $_POST["uID"])) {
         $usermanager -> updateUserFunc($_POST["fName"], $_POST["lName"], $_POST["uID"]);
        exit;
    } else if (isset($_POST["dID"])) {
         $usermanager -> deleteUserFunc($_POST["dID"]);
    }else if (isset($_POST["LFName"], $_POST["lLName"])) {
        $usermanager -> updateUserFunc ($_POST["lFName"], $_POST["lLname"], $_POST["uID"]);
        exit;

    } 


?>