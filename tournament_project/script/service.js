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

/* ── Themed SweetAlert helper ── */
function miffySwal(opts) {
    return Swal.fire(Object.assign({
        background: '#1C0A04',
        color: '#FAF0DC',
        confirmButtonColor: '#D4824A',
        cancelButtonColor: '#3B1F0E',
        iconColor: '#E8A96A',
        customClass: {
            popup:          'miffy-swal-popup',
            title:          'miffy-swal-title',
            htmlContainer:  'miffy-swal-html',
            confirmButton:  'miffy-swal-confirm',
            cancelButton:   'miffy-swal-cancel',
            timerProgressBar: 'miffy-swal-timer'
        }
    }, opts));
}

/* inject shared CSS once */
(function() {
    if (document.getElementById('miffy-swal-style')) return;
    const s = document.createElement('style');
    s.id = 'miffy-swal-style';
    s.textContent = `
        .miffy-swal-popup  { border: 1px solid rgba(212,130,74,0.35) !important; border-radius: 14px !important; box-shadow: 0 8px 40px rgba(0,0,0,0.7) !important; }
        .miffy-swal-title  { color: #FAF0DC !important; font-family: Georgia, serif !important; letter-spacing: 1px !important; }
        .miffy-swal-html   { color: rgba(250,240,220,0.75) !important; }
        .miffy-swal-confirm{ font-weight: bold !important; letter-spacing: 1px !important; }
        .miffy-swal-cancel { font-weight: bold !important; letter-spacing: 1px !important; }
        .miffy-swal-timer  { background: #D4824A !important; }
        .swal2-icon.swal2-warning { border-color: #E8A96A !important; color: #E8A96A !important; }
        .swal2-icon.swal2-error   { border-color: #c0392b !important; color: #c0392b !important; }
        .swal2-icon.swal2-success .swal2-success-ring { border-color: rgba(212,130,74,0.3) !important; }
        .swal2-icon.swal2-success [class^=swal2-success-line] { background-color: #D4824A !important; }
    `;
    document.head.appendChild(s);
})();


function loginFunc() {
    let email = $('#LoginEmail').val().trim();
    let pass  = $('#LoginPassword').val();

    if (!email || !pass) {
        miffySwal({ title: 'Wait', text: 'Please enter your email and password.', icon: 'warning' });
        return;
    }

    $.post('../controllers/controller.php', { choice: 'login', email: email, password: pass }, function(res) {
        if (res.trim() == 'true') {
            window.location.href = 'dashboardpage.php';
        } else if (res.trim() == 'wrong_password') {
            miffySwal({ title: 'Error', text: 'Incorrect password.', icon: 'error' });
        } else if (res.trim() == 'not_found') {
            miffySwal({ title: 'Error', text: 'No account found with that email.', icon: 'error' });
        } else {
            miffySwal({ title: 'Error', text: 'Login failed. Please try again.', icon: 'error' });
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
        miffySwal({ title: 'Wait', text: 'Please complete all fields!', icon: 'warning' }); return;
    }
    if (fn.length < 2 || ln.length < 2) {
        miffySwal({ title: 'Invalid Name', text: 'First and Last Name must be at least 2 characters.', icon: 'error' }); return;
    }
    if (parseInt(ag) > 99) {
        miffySwal({ title: 'Invalid Age', text: 'Age must not exceed 99.', icon: 'error' }); return;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em)) {
        miffySwal({ title: 'Invalid Email', text: 'Please enter a valid email address.', icon: 'error' }); return;
    }
    if (pass.length < 8) {
        miffySwal({ title: 'Too Short', text: 'Password must be at least 8 characters.', icon: 'warning' }); return;
    }
    if (pass.length > 32) {
        miffySwal({ title: 'Too Long', text: 'Password must not exceed 32 characters.', icon: 'warning' }); return;
    }
    if (!/[A-Z]/.test(pass)) {
        miffySwal({ title: 'Weak Password', text: 'Password must include at least 1 uppercase letter (A-Z).', icon: 'warning' }); return;
    }
    if (!/[a-z]/.test(pass)) {
        miffySwal({ title: 'Weak Password', text: 'Password must include at least 1 lowercase letter (a-z).', icon: 'warning' }); return;
    }
    if (!/[0-9]/.test(pass)) {
        miffySwal({ title: 'Weak Password', text: 'Password must include at least 1 number (0-9).', icon: 'warning' }); return;
    }
    if (!/[^A-Za-z0-9]/.test(pass)) {
        miffySwal({ title: 'Weak Password', text: 'Password must include at least 1 special character (e.g. @, #, !).', icon: 'warning' }); return;
    }
    if (pass !== conf) {
        miffySwal({ title: 'Password Mismatch', text: 'Passwords do not match!', icon: 'error' }); return;
    }

    $.post('../controllers/controller.php', {
        choice: 'registerPlayer', fn, ln, gd, ag, rt, email: em, password: pass
    }, function(res) {
        if (res.trim() == 'true') {
            miffySwal({ title: 'Profile Created!', text: 'Player registered! You can now login.', icon: 'success' })
                .then(() => location.reload());
        } else if (res.trim() == 'email_taken') {
            miffySwal({ title: 'Email Taken', text: 'That email is already registered.', icon: 'error' });
        } else {
            miffySwal({ title: 'Error', text: 'Registration failed: ' + res, icon: 'error' });
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
            miffySwal({
                icon: 'success',
                title: 'Player Updated!',
                timer: 1000,
                showConfirmButton: false,
                timerProgressBar: true
            }).then(() => {
                location.reload();
            });
        } else {
            miffySwal({ title: 'Error', text: 'Update failed: ' + res, icon: 'error' });
        }
    });
}

function generatePairs(roundNum) { 
    miffySwal({
        title: 'Generate Round ' + roundNum + '?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Generate!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('../controllers/controller.php', { choice: 'generatePairs', round: roundNum }, function(response) {
                if (response.trim() == "true") {
                    miffySwal({
                        icon: 'success',
                        title: 'Matches Generated!',
                        timer: 1200,
                        showConfirmButton: false,
                        timerProgressBar: true
                    }).then(() => {
                        window.location.href = 'dashboardpage.php?round=' + roundNum;
                    });
                } else {
                    miffySwal({ title: 'Error', text: response, icon: 'error' });
                }
            });
        }
    });
}

function updateScore(mid) {
    let s1_val = document.getElementById("score1_" + mid).value;
    let s2_val = document.getElementById("score2_" + mid).value;

    miffySwal({
        title: 'Save Score?',
        text: 'Are you sure you want to save this score?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Save it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post("../controllers/controller.php", {
                choice: 'updateScore',
                mID: mid,
                s1: s1_val,
                s2: s2_val
            }, function(res) {
                if (res.trim().includes("true")) {
                    miffySwal({
                        icon: 'success',
                        title: 'Score Saved!',
                        timer: 900,
                        showConfirmButton: false,
                        timerProgressBar: true
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    miffySwal({ title: 'Failed!', text: res, icon: 'error' });
                }
            });
        }
    });
}

function deleteFunc(id) {
    miffySwal({
        title: 'Are you sure?',
        text: 'This player will be permanently deleted!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
        confirmButtonColor: '#8B1A1A'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('../controllers/controller.php', { choice: 'deletePlayer', userID: id }, function(res) {
                if (res.trim() == "true") {
                    miffySwal({
                        icon: 'success',
                        title: 'Deleted!',
                        text: 'Player has been removed.',
                        timer: 1000,
                        showConfirmButton: false,
                        timerProgressBar: true
                    }).then(() => location.reload());
                } else {
                    miffySwal({ title: 'Error', text: 'Failed to delete.', icon: 'error' });
                }
            });
        }
    });
}

function resetTournament() {
    miffySwal({
        title: 'Reset Tournament?',
        text: 'This will clear all scores and pairings!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#8B1A1A',
        confirmButtonText: 'Yes, Reset Now'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('../controllers/controller.php', { choice: 'resetTournament' }, function(res) {
                if (res.trim() == "true") {
                    location.href = "dashboardpage.php?round=1";
                } else {
                    miffySwal({ title: 'Error', text: res, icon: 'error' });
                }
            });
        }
    });
}
