<?php
session_start();
if (isset($_SESSION['user'])) { header("Location: dashboardpage.php"); exit(); }
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login | Miffy Chess Cup</title>
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
            font-family: 'DM Sans', sans-serif; margin: 0; padding: 0;
            background: linear-gradient(rgba(18,8,4,0.88), rgba(18,8,4,0.88)),
                        url('https://i.pinimg.com/1200x/3e/61/10/3e611090fe833da3f7479bbe90e04df5.jpg');
            background-size: cover; background-position: center; background-attachment: fixed;
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
        }
        .login-card {
            background: rgba(30,14,8,0.62); backdrop-filter: blur(18px);
            border: 1px solid var(--glass-border); border-radius: 22px;
            padding: 48px 40px; width: 100%; max-width: 420px;
            text-align: center; box-shadow: 0 24px 60px rgba(0,0,0,0.5);
        }
        .logo {
            width: 110px; height: 110px; border-radius: 50%;
            background: white; object-fit: contain; padding: 10px;
            border: 2.5px solid var(--caramel); margin-bottom: 20px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.3);
        }
        .login-title { font-family: 'Cinzel', serif; font-size: 1.9rem; font-weight: 700; color: var(--cream); letter-spacing: 2px; margin-bottom: 6px; }
        .login-sub { font-size: 11px; letter-spacing: 3.5px; text-transform: uppercase; color: var(--caramel); margin-bottom: 36px; font-weight: 500; }
        .field-label { display: block; text-align: left; font-size: 11px; font-weight: 600; letter-spacing: 2px; text-transform: uppercase; color: var(--caramel); margin-bottom: 7px; margin-top: 18px; }
        .field-input {
            width: 100%; height: 46px; background: rgba(0,0,0,0.3);
            border: 1px solid rgba(212,130,74,0.32); border-radius: 10px;
            color: white; font-family: 'DM Sans', sans-serif; font-size: 14px;
            padding: 0 14px; outline: none; transition: 0.2s;
        }
        .field-input:focus { border-color: var(--caramel); box-shadow: 0 0 0 3px rgba(212,130,74,0.12); }
        .field-input::placeholder { color: rgba(255,255,255,0.25); }
        .btn-login {
            width: 100%; height: 52px; margin-top: 28px;
            background: linear-gradient(135deg, var(--mocha-light), var(--mocha-glow));
            color: white; border: none; border-radius: 30px;
            font-family: 'Cinzel', serif; font-size: 14px; font-weight: 600;
            letter-spacing: 2.5px; text-transform: uppercase; cursor: pointer;
            box-shadow: 0 6px 22px rgba(212,130,74,0.35); transition: 0.2s;
        }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(212,130,74,0.5); }
        .footer-links { margin-top: 24px; font-size: 13px; color: rgba(255,255,255,0.4); }
        .footer-links a { color: rgba(255,255,255,0.5); text-decoration: none; transition: 0.2s; }
        .footer-links a:hover { color: var(--caramel); }
        .footer-links a.admin-link { color: var(--caramel); font-weight: 500; }
        .divider { margin: 0 14px; opacity: 0.25; }
    </style>
</head>
<body>
    <div class="login-card">
        <img src="../assets/miffy.jpg" alt="Miffy" class="logo">
        <div class="login-title">Player Login</div>
        <div class="login-sub">Tournament Portal</div>

        <label class="field-label">Email</label>
        <input id="LoginEmail" type="email" class="field-input" placeholder="Enter your email">

        <label class="field-label">Password</label>
        <input id="LoginPassword" type="password" class="field-input" placeholder="Enter your password">

        <button class="btn-login" onclick="loginFunc()">Login Now</button>

        <div class="footer-links">
            <a href="registrationpage.php">No Account?</a>
            <span class="divider">|</span>
            <a href="adminreg.php" class="admin-link">Admin Access</a>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../script/service.js"></script>
</body>
</html>
