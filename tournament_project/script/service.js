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
    let email = $('#LoginEmail').val().trim();
    let pass  = $('#LoginPassword').val();

    if (!email || !pass) {
        Swal.fire('Wait', 'Please enter your email and password.', 'warning');
        return;
    }

    $.post('../controllers/controller.php', { choice: 'login', email: email, password: pass }, function(res) {
        if (res.trim() == 'true') {
            window.location.href = 'dashboardpage.php';
        } else if (res.trim() == 'wrong_password') {
            Swal.fire('Error', 'Incorrect password.', 'error');
        } else if (res.trim() == 'not_found') {
            Swal.fire('Error', 'No account found with that email.', 'error');
        } else {
            Swal.fire('Error', 'Login failed. Please try again.', 'error');
        }
    });
}

function addFunc() {
    let fn   = $('#FName').val().trim();
    let ln   = $('#LName').val().trim();
    let gd   = $('#Gender').val();
    let ag   = $('#Age').val();
    let rt   = $('#Rating').val();
    let em   = $('#RegEmail').val().trim();
    let pass = $('#RegPassword').val();
    let conf = $('#RegConfirm').val();

    if (!fn || !ln || !gd || !ag || !rt || !em || !pass || !conf) {
        Swal.fire('Wait', 'Please complete all fields!', 'warning'); return;
    }
    if (fn.length < 2 || ln.length < 2) {
        Swal.fire('Invalid Name', 'First and Last Name must be at least 2 characters.', 'error'); return;
    }
    if (parseInt(ag) > 99) {
        Swal.fire('Invalid Age', 'Age must not exceed 99.', 'error'); return;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em)) {
        Swal.fire('Invalid Email', 'Please enter a valid email address.', 'error'); return;
    }
    if (pass.length < 8) {
        Swal.fire('Too Short', 'Password must be at least 8 characters.', 'warning'); return;
    }
    if (pass.length > 32) {
        Swal.fire('Too Long', 'Password must not exceed 32 characters.', 'warning'); return;
    }
    if (!/[A-Z]/.test(pass)) {
        Swal.fire('Weak Password', 'Password must include at least 1 uppercase letter (A-Z).', 'warning'); return;
    }
    if (!/[a-z]/.test(pass)) {
        Swal.fire('Weak Password', 'Password must include at least 1 lowercase letter (a-z).', 'warning'); return;
    }
    if (!/[0-9]/.test(pass)) {
        Swal.fire('Weak Password', 'Password must include at least 1 number (0-9).', 'warning'); return;
    }
    if (!/[^A-Za-z0-9]/.test(pass)) {
        Swal.fire('Weak Password', 'Password must include at least 1 special character (e.g. @, #, !).', 'warning'); return;
    }
    if (pass !== conf) {
        Swal.fire('Password Mismatch', 'Passwords do not match!', 'error'); return;
    }

    $.post('../controllers/controller.php', {
        choice: 'registerPlayer', fn, ln, gd, ag, rt, email: em, password: pass
    }, function(res) {
        if (res.trim() == 'true') {
            Swal.fire('Success!', 'Player Registered! You can now login.', 'success').then(() => location.reload());
        } else if (res.trim() == 'email_taken') {
            Swal.fire('Email Taken', 'That email is already registered.', 'error');
        } else {
            Swal.fire('Error', 'Registration failed: ' + res, 'error');
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