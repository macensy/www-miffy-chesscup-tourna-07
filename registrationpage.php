<?php
session_start();
//isset
//$_POST["fName"];

if (isset($_POST["fName"], $_POST["lName"]) && !isset($_POST["uID"])) {
    if (!isset($_SESSION["userArray"])) {
        $_SESSION["userArray"] = [];
    }

    addUserFunc($_POST["fName"], $_POST["lName"]);
    exit;
} else if (isset($_POST["fName"], $_POST["lName"], $_POST["uID"])) {
    updateUserFunc($_POST["fName"], $_POST["lName"], $_POST["uID"]);
    exit;
} else if (isset($_POST["dID"])) {
    deleteUserFunc($_POST["dID"]);
    exit;
}

function addUserFunc($firstName, $lastName) {
    try {
        $_SESSION["userArray"][] = [
            "FirstName" => $firstName,
            "LastName"  => $lastName
        ];

        echo count($_SESSION["userArray"]);
    } catch (InvalidArgumentException $ex) {
        http_response_code(501);
        echo $ex->getMessage();
        exit;
    }
}

function updateUserFunc($firstName, $lastName, $userID) {
    if (isset($_SESSION["userArray"][$userID])) {
        $_SESSION["userArray"][$userID]["FirstName"] = $firstName;
        $_SESSION["userArray"][$userID]["LastName"]  = $lastName;
        echo "Update Function";
    }
}

function deleteUserFunc($userID) {
    if (isset($_SESSION["userArray"][$userID])) {
        unset($_SESSION["userArray"][$userID]);
    }
}
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

<div class="row">
    <div class="col s4 m4 l4"></div>

    <div class="col s4 m4 l4">
        <div class="row">

            <div class="input-field col s6 m6 l6">
                <i class="material-icons prefix">account_circle</i>
                <input id="FName" type="text" class="validate">
                <label for="FName">First Name</label>
            </div>

            <div class="input-field col s6 m6 l6">
                <i class="material-icons prefix">account_circle</i>
                <input id="LName" type="tel" class="validate">
                <label for="LName">Last Name</label>
            </div>

            <div class="col s12 m12 l12">
                <a class="waves-effect waves-light btn-large #071f34 blue darken-1" style="width: 100%;" onclick="addFunc()">
                    <i class="material-icons right">add_circle</i>
                    Add User
                </a>
            </div>

            <br>

            <div class="col s12 m12 l12">
                <table class = "highlight centered">
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
                                    <a class="waves-effect waves-light btn #59190a yellow darken-1" onclick="updateFunc(<?= $index; ?>)" style="width: 100%; margin 5px"><i class="material-icons right">refresh</i>Update</a>
                                    <a class="waves-effect waves-light btn #02406c red darken-1" onclick="deleteFunc(<?= $index; ?>)" style="width: 100%; margin 5px"><i class="material-icons right">remove_circle_outline</i>Delete</a>
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

</body>
</html>

<script>
function addFunc() {
    var firstName = document.getElementById("FName").value;
    var lastName  = document.getElementById("LName").value;

    $.ajax({
        url: "",
        type: "POST",
        data: {
            fName: firstName,
            lName: lastName
        },
        success: function (returnedData) {
            Swal.fire({
                title: "Good job!",
                text: "Succesfully added a user named " + firstName + " " + lastName,
                icon: "success",
                confirmButtonText: "OK",
            }).then((result) => {
                if (result.isConfirmed) {
                    location.reload(true);
                }
            });
        },
        error: function (xhr) {
            alert(xhr.status + " : " + xhr.responseText);
        }
    });
}

function updateFunc(userID) {
    var firstName = document.getElementById("FName").value;
    var lastName  = document.getElementById("LName").value;

    $.ajax({
        url: "",
        type: "POST",
        data: {
            fName: firstName,
            lName: lastName,
            uID: userID
        },
        success: function (returnedData) {
           Swal.fire({
                title: "Good job!",
                text: "Succesfully updated a user named " + firstName + " " + lastName,
                icon: "success",
                confirmButtonText: "OK",
            }).then((result) => {
                if (result.isConfirmed) {
                    location.reload(true);
                }
            });
        },
        error: function (xhr) {
            alert(xhr.status + " : " + xhr.responseText);
        }
    });
}

function deleteFunc(userID) {
    $.ajax({
        url: "",
        type: "POST",
        data: {
            dID: userID
        },
        success: function (returnedData) {
            Swal.fire({
                title: "Good job!",
                text: "Succesfully deleted a user",
                icon: "success",
                confirmButtonText: "OK",
            }).then((result) => {
                if (result.isConfirmed) {
                    location.reload(true);
                }
            });
        },
        error: function (xhr) {
            alert(xhr.status + " : " + xhr.responseText);
        }
    });
}
</script>