function validateNumFields(element) {
    element.value = element.value.replace(/[^0-9]/g, ""); 
}

$(document).ready(function() {
    const ageInput = document.getElementById("Age");
    if (ageInput) {
        ageInput.addEventListener("input", function() { validateNumFields(this); });
    }

    const ratingInput = document.getElementById("Rating");
    if (ratingInput) {
        ratingInput.addEventListener("input", function() { validateNumFields(this); });
    }

    if (typeof $.fn.modal !== 'undefined') {
        $('.modal').modal(); 
    }
});


function loginFunc() {
    let data = {
        choice: 'login',
        fn: $('#icon_LFName').val(),
        ln: $('#icon_LLName').val()
    };

    $.post('../controllers/controller.php', data, function(res) {
        if (res.trim() == "true") {
            window.location.href = "dashboardpage.php"; 
        } else {
            Swal.fire("Error", "Login Failed", "error");
        }
    });
}

function addFunc() {
    let fn = $('#FName').val();
    let ln = $('#LName').val();
    let gd = $('#Gender').val();
    let ag = $('#Age').val();
    let rt = $('#Rating').val();

    // Validations
    if (!fn || !ln || !gd || !ag || !rt) {
        Swal.fire("Wait", "Please complete all player details!", "warning");
        return;
    }
    if (fn.length < 2 || ln.length < 2) {
        Swal.fire("Invalid Name", "First and Last Name must be at least 2 characters.", "error");
        return;
    }
    if (ag.length > 2 || parseInt(ag) > 99) {
        Swal.fire("Invalid Age", "Age must not exceed 99.", "error");
        return;
    }

    let data = {
        choice: 'registerPlayer',
        fn: fn,
        ln: ln,
        gd: gd,
        ag: ag,
        rt: rt
    };

    $.post('../controllers/controller.php', data, function(res) {
        if (res.trim() == "true") {
            Swal.fire("Success", "Player Registered!", "success").then(() => {
                location.reload();
            });
        } else {
            Swal.fire("Error", "Registration failed: " + res, "error");
        }
    });
}

function openEditModal(id, fn, ln, age, rt) {
    $('#editID').val(id);
    $('#editFName').val(fn);
    $('#editLName').val(ln);
    $('#editAge').val(age);
    $('#editRating').val(rt);
    
    let elem = document.getElementById('modalEdit');
    if (elem) {
        M.Modal.getInstance(elem).open();
    }
}

function updateFunc() {
    let data = {
        choice: 'updateUser',
        id: $('#editID').val(),
        fn: $('#editFName').val(),
        ln: $('#editLName').val(),
        ag: $('#editAge').val(),
        rt: $('#editRating').val()
    };

    $.post("../controllers/controller.php", data, function(res) {
        if (res.trim() === "true") {
            Swal.fire({ icon: 'success', title: 'Player Updated!', timer: 1000, showConfirmButton: false })
            .then(() => location.reload());
        } else {
            Swal.fire("Error", "Update failed: " + res, "error");
        }
    });
}

function generatePairs(roundNum) { 
    Swal.fire({
        title: 'Generate Round ' + roundNum + '?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#F2B705',
        confirmButtonText: 'Yes, Generate!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('../controllers/controller.php', { choice: 'generatePairs', round: roundNum }, function(response) {
                if (response.trim() == "true") {
                    Swal.fire('Success!', 'Matches generated.', 'success').then(() => location.reload());
                } else {
                    Swal.fire('Error', response, 'error');
                }
            });
        }
    });
}

function updateScore(mid) {
    let s1_val = document.getElementById("score1_" + mid).value;
    let s2_val = document.getElementById("score2_" + mid).value;

    $.post("../controllers/controller.php", {
        choice: 'updateScore',
        mID: mid,
        s1: s1_val,
        s2: s2_val
    }, function(res) {
        if (res.trim().includes("true")) {
            location.reload();
        } else {
            Swal.fire("Error", res, "error");
        }
    });
}

function deleteFunc(id) {
    Swal.fire({
        title: 'Are you sure?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#F2B705',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('../controllers/controller.php', { choice: 'deletePlayer', userID: id }, function(res) {
                if(res.trim() == "true") {
                    location.reload();
                } else {
                    Swal.fire('Error', 'Failed to delete', 'error');
                }
            });
        }
    });
}

function resetTournament() {
    Swal.fire({
        title: 'Reset Tournament?',
        text: "This will clear all scores and pairings!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, Reset Now'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('../controllers/controller.php', { choice: 'resetTournament' }, function(res) {
                if (res.trim() == "true") {
                    location.href = "dashboardpage.php?round=1";
                } else {
                    Swal.fire('Error', res, 'error');
                }
            });
        }
    });
}