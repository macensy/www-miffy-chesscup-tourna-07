<?php session_start(); ?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Access | Miffy Chess Cup</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --mocha-light:  #B5622A;
            --mocha-glow:   #D4824A;
            --caramel:      #E8A96A;
            --cream:        #FAF0DC;
            --glass-border: rgba(212, 130, 74, 0.28);
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'DM Sans', sans-serif;
            margin: 0;
            background: linear-gradient(rgba(18, 8, 4, 0.88), rgba(18, 8, 4, 0.88)),
                        url('https://i.pinimg.com/1200x/3e/61/10/3e611090fe833da3f7479bbe90e04df5.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .admin-card {
            background: rgba(30, 14, 8, 0.62);
            backdrop-filter: blur(18px);
            border: 1px solid var(--glass-border);
            border-radius: 22px;
            padding: 48px 40px;
            width: 100%;
            max-width: 420px;
            text-align: center;
            box-shadow: 0 24px 60px rgba(0,0,0,0.5);
            color: white;
        }
        .logo {
            width: 100px; height: 100px;
            border-radius: 50%;
            background: white;
            object-fit: contain;
            padding: 8px;
            border: 2.5px solid var(--caramel);
            margin-bottom: 18px;
        }
        .admin-title {
            font-family: 'Cinzel', serif;
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--cream);
            letter-spacing: 2px;
            margin-bottom: 6px;
        }
        .admin-sub {
            font-size: 11px;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: var(--caramel);
            margin-bottom: 32px;
            font-weight: 500;
        }
        .field-label {
            display: block;
            text-align: left;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--caramel);
            margin-bottom: 7px;
            margin-top: 16px;
        }
        .field-input {
            width: 100%;
            height: 46px;
            background: rgba(0,0,0,0.3);
            border: 1px solid rgba(212,130,74,0.32);
            border-radius: 10px;
            color: white;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            padding: 0 14px;
            outline: none;
            transition: 0.2s;
        }
        .field-input:focus {
            border-color: var(--caramel);
            box-shadow: 0 0 0 3px rgba(212,130,74,0.12);
        }
        .field-input::placeholder { color: rgba(255,255,255,0.25); }
        .btn-admin {
            width: 100%;
            height: 52px;
            margin-top: 28px;
            background: linear-gradient(135deg, var(--mocha-light), var(--mocha-glow));
            color: white;
            border: none;
            border-radius: 30px;
            font-family: 'Cinzel', serif;
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 2px;
            text-transform: uppercase;
            cursor: pointer;
            box-shadow: 0 6px 22px rgba(212,130,74,0.35);
            transition: 0.2s;
        }
        .btn-admin:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 28px rgba(212,130,74,0.5);
        }
        .back-link {
            display: block;
            margin-top: 20px;
            color: rgba(255,255,255,0.35);
            text-decoration: none;
            font-size: 13px;
            transition: 0.2s;
        }
        .back-link:hover { color: var(--caramel); }
    </style>
</head>
<body>
    <div class="admin-card">
        <img src="../assets/miffy.jpg" class="logo" alt="Miffy">
        <div class="admin-title">Admin Portal</div>
        <div class="admin-sub">Authorized Personnel Only</div>

        <label class="field-label">First Name</label>
        <input id="AdminFName" type="text" class="field-input" minlength="2" maxlength="50"
               oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '');" placeholder="Enter first name">

        <label class="field-label">Last Name</label>
        <input id="AdminLName" type="text" class="field-input" minlength="2" maxlength="50"
               oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '');" placeholder="Enter last name">

        <label class="field-label">Secret Arbiter Key</label>
        <input id="AdminSecretKey" type="password" class="field-input" placeholder="••••••••••••">

        <button class="btn-admin" onclick="registerAdmin()">Login Admin Account</button>
        <a href="loginpage.php" class="back-link">← Return to Login</a>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    function registerAdmin() {
        let fn = $('#AdminFName').val().trim();
        let ln = $('#AdminLName').val().trim();
        let key = $('#AdminSecretKey').val();

        if (!fn || !ln || !key) {
            Swal.fire("Wait", "Complete the details, boi!", "warning"); return;
        }
        if (fn.length < 2 || ln.length < 2) {
            Swal.fire("Wait", "Names are too short!", "warning"); return;
        }
        if (key !== "miffyandboris") {
            Swal.fire("Unauthorized", "Invalid Secret Key!", "error"); return;
        }
        $.post('../controllers/controller.php', { choice: 'registerAdmin', fn, ln }, function(res) {
            if (res.trim() === "true") {
                Swal.fire({ title: "ADMIN VERIFIED!", text: "Welcome, Arbiter. Redirecting...", icon: "success", timer: 1500, showConfirmButton: false })
                    .then(() => window.location.href = "dashboardpage.php");
            } else {
                Swal.fire("Error", "Check your database connection.", "error");
            }
        }).fail(() => Swal.fire("System Error", "Cannot connect to server.", "error"));
    }
    </script>
</body>
</html>
