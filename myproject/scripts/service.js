function addFunc() {
    var firstName = document.getElementById("FName").value;
    var lastName  = document.getElementById("LName").value;

    $.ajax({
        url: "../controllers/controller.php",
        type: "POST",
        data: {
            choice: 'registerPlayer',
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
        url: "../controllers/controller.php",
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
        url: "../controllers/controller.php",
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

function redirectFunc(redirectID) {
    if (redirectID == 1) {
        window.location.href = "../views/loginpage.php";
    }else if(redirectID == 2) {
        window.location.href = "../views/dashboardpage.php";
    }else if (redirectID == 2) {
        window.location.href = "../views/registrationpage.php";
    
    }
}

function loginFunc() {
    let data = {
        choice: 'loginUser',
        fn: $('#icon_LFName').val(),
        ln: $('#icon_LLName').val()
    };

    $.post('../controllers/controller.php', data, function(res) {
        if (res.trim() == "true") {
            Swal.fire({
                title: "Welcome Back!",
                text: "Redirecting to Tournament Dashboard...",
                icon: "success",
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
            
                window.location.href = "dashboardpage.php";
            });
        } else {
            Swal.fire("Access Denied", "Invalid Name or Password", "error");
        }
    });
}

function updateScore(mid) {
    // Kunin ang value ng scores base sa match_id
    let score1 = $('#score1_' + mid).val();
    let score2 = $('#score2_' + mid).val();

    console.log("Saving Match ID: " + mid + " | Score: " + score1 + " - " + score2);

    $.ajax({
        url: "../controllers/controller.php",
        type: "POST",
        data: {
            choice: 'updateScore',
            mID: mid,
            s1: score1,
            s2: score2
        },
        success: function (res) {
            if (res.trim() == "true") {
                Swal.fire({
                    title: "Score Saved!",
                    text: "Match results updated and standings refreshed.",
                    icon: "success",
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire("Error", "Message: " + res, "error");
            }
        },
        error: function (xhr) {
            alert("Error: " + xhr.responseText);
        }
    });
}
    

