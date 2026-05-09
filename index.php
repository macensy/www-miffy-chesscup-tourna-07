<?php 
session_start();

if(isset($_POST["FName"], $_POST["LName"]) && !isset($_POST["uID"]) && !isset($_POST["dID"]))
{
    if(!isset($_SESSION["userArray"]))
    {
    $_SESSION["userArray"] = []; }
            
    addUserFunc($_POST["FName"], $_POST["LName"]  );
    exit;

    } else if (isset($_POST["FName"], $_POST["LName"], $_POST["uID"])){
        if(!isset($_SESSION["userArray"]))
        {
        $_SESSION["userArray"] = [];
            }            
         updateFunc($_POST["FName"], $_POST["LName"], $_POST["uID"]);
            exit;
        }       else if (isset($_POST["dID"]))
        {
        if(!isset($_SESSION["userArray"]))
        {
            $_SESSION["userArray"] = [];}            
        deleteFunc($_POST["dID"]);
        exit;
        }
        
        function addUserFunc($firstName, $lastName): void
        {
            try{
            $_SESSION["userArray"] [] = [
                "FirstName" => $firstName,
                "LastName" => $lastName 
            ];
                echo count($_SESSION["userArray"]);           
                }catch(InvalidArgumentException $ex)
            {
                 http_response_code(501);
                    echo $ex->getMessage();
                    exit;
            }
        }

        function updateFunc($firstName, $lastName, $userID): void
        {
            if(isset($_SESSION["userArray"][$userID]))
            {
                try{
                    $_SESSION["userArray"] [$userID] = [
                        "FirstName" => $firstName,
                        "LastName" => $lastName 
                    ];
                        echo "Update Function";           
                        }catch(InvalidArgumentException $ex)
                    {
                         http_response_code(501);
                            echo $ex->getMessage();
                            exit;
                    }
            } else {
                http_response_code(404);
                echo "User Not Found";
                exit;
            }
        }

        function deleteFunc($userID): void
{
            if (isset($_SESSION["userArray"][$userID])){
                try {
            unset($_SESSION["userArray"][$userID]);

            echo "User Deleted";
             } catch (InvalidArgumentException $ex) 
        {
            http_response_code(501);
            echo $ex->getMessage();
            exit;
        }
    } 
    else {
        http_response_code(404);
        echo "User Not Found";
        exit;
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script
    <title>Document</title>
</head>
<body>
    <label>First Name </label>
    <input type="text" name = "firstName" placeholder="First Name" id="fName">
    <label>Last Name </label>
    <input type="text" name = "lastName" placeholder="Last Name" id="lName">
    <button onclick = "addFunc()"> Add </button>
<table>
    <tr>
        <th>User ID</th>
        <th>First Name</th>
        <th>Last Name</th>
        <th>Action</th>
    </tr>
    <?php if(!empty($_SESSION["userArray"])) : ?>
        <?php foreach($_SESSION["userArray"] as $index => $user) : ?>
            <tr>
                <td><?= $index + 1; ?></td>
                <td><?= ($user["FirstName"]); ?></td>
                <td><?= ($user["LastName"]); ?></td>
                <td><button onclick="updateFunc(<?php echo $index; ?>)">Update</button></td>
                <td><button onclick="deleteFunc(<?php echo $index; ?>)">Delete</button></td>
            </tr>
        <?php endforeach; ?>
    
    <?php else : ?>
        <tr>
            <td>No Data Found</td> 
        </tr>
    <?php endif; ?>
</table>

</body>
</html>

<script> 
    function addFunc() {
        var firstName = document.getElementById("fName").value;
        var lastName = document.getElementById("lName").value;
        $.ajax({
            url: "",
            type: "post",
            data: {
                FName : firstName,
                LName : lastName,
            },
            success: function(returnedData){
            Swal.fire({
            title: "Good job!",
            text: "You clicked the button!",
            icon: "success"
        });
        },
        error: function(xhr) {
            alert(xhr.status + " : " + xhr.responseText);
    }})
};
</script>

<script>
function updateFunc(userID) { 
  var firstName = document.getElementById("fName").value; 
  var lastName = document.getElementById("lName").value; 
  $.ajax({ 
    url: "", 
    type: "post", 
    data: { 
      FName : firstName, 
      LName : lastName, 
      uID : userID, 
    }, 
    success: function(response) { 
      location.reload(true);
    }, 
    error: function(xhr) { 
    }
  }) 
};
</script>

<script>
function deleteFunc(userID) {
        $.ajax({
            url: "",
            type: "post",
            data: {
                dID : userID,
            },
            success: function(response) {
            location.reload(true);
            alert(response);
        },
        error: function(xhr) {
            alert(xhr.status + " : " + xhr.responseText);
    }})
};
</script>

</body>
</html>