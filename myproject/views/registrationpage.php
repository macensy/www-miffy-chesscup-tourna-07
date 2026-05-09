<?php
session_start();
require_once "../bl/usermanager.php";
require_once "../bl/DepartmentManager.php";

$usermanager = new usermanager();
$users = $usermanager->getUser();
$advancedUsers = $usermanager->getAdvancedUsers();

$deptManager = new DepartmentManager();
$department = $deptManager->getDepartment();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <title>Document</title>
</head>
<body>

<style>
    body {
        background-color: #fff9c4;
        min-height: 100vh;
    }
</style>

<div class="container" style="margin-top: 50px;"></div>
    <div class="row">
        <div class="col s12 m6 offset-m3">
            <div class="card-panel z-depth-2">

            <div class="row">
        <?php if(!empty($users)): ?>
            <a class="waves-effect waves-light btn-large #81c784 green lighten-2" onclick="redirectFunc(1)">
                <i class="material-icons right">star</i>login
            </a>
        <?php endif?>
    </div>



    <h4 class="center-align">
                    Registration <i class="material-icons yellow-text text-lighten-2">star</i>
                </h4>
                <br>

        <div class="row">

            <div class="input-field col s12">
                <i class="material-icons prefix">account_circle</i>
                <input id="FName" type="text" class="validate">
                <label for="FName">First Name</label>
            </div>

            <div class="input-field col s12">
                <i class="material-icons prefix">account_circle</i>
                <input id="LName" type="tel" class="validate">
                <label for="LName">Last Name</label>
            </div>

            <div class="col s12 center-align">
                <a class="waves-effect waves-light btn-large #90a4ae blue-grey lighten-2" style="width: 100%;" onclick="addFunc()">
                    <i class="material-icons right">add_circle</i>
                    Add User
                </a>
            </div>

            <br>

            <div class="col s12 m12 l12">
                <table class = "highlight centered" id = cleo>

                <thead>


                </thead>
                    <tr>
                        <th>User ID</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Action</th>
                    </tr>

                    <?php if (!empty($users)) : ?>
                        <?php foreach ($_SESSION["userArray"] as $index => $user) : ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= $user['FirstName'] ?></td>
                                <td><?= $user['LastName'] ?></td>
                                <td>
                                    <a class="waves-effect waves-light btn #81c784 green lighten-2" onclick="updateFunc(<?= $index; ?>)" style="width: 100%; margin 5px"><i class="material-icons right">refresh</i>Update</a>
                                    <a class="waves-effect waves-light btn #a1887f brown lighten-2" onclick="deleteFunc(<?= $index; ?>)" style="width: 100%; margin 5px"><i class="material-icons right">remove_circle_outline</i>Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td>No data found</td>
                        </tr>
                    <?php endif ?>
                </table>
            </div>

        </div>
    </div>

    <div class="col s4 m4 l4"></div>
</div>

    <br>

    <script src="../scripts/service.js"></script>

</body>
</html>

