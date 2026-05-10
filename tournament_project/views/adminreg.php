<?php session_start(); ?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Portal | Miffy Chess Cup</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --mocha-light:  #B5622A;
            --mocha-glow:   #D4824A;
            --caramel:      #E8A96A;
            --cream:        #FAF0DC;
            --glass-border: rgba(212,130,74,0.28);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'DM Sans', sans-serif;
            background: linear-gradient(rgba(18,8,4,0.88), rgba(18,8,4,0.88)),
                        url('https://i.pinimg.com/1200x/3e/61/10/3e611090fe833da3f7479bbe90e04df5.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        /* ── Card ── */
        .admin-card {
            background: rgba(30,14,8,0.65);
            backdrop-filter: blur(18px);
            border: 1px solid var(--glass-border);
            border-radius: 22px;
            padding: 44px 38px 36px;
            width: 100%;
            max-width: 440px;
            text-align: center;
            box-shadow: 0 24px 60px rgba(0,0,0,0.55);
            color: white;
        }
        .logo {
            width: 86px; height: 86px;
            border-radius: 50%;
            background: white;
            object-fit: contain;
            padding: 8px;
            border: 2.5px solid var(--caramel);
            margin-bottom: 14px;
        }
        .admin-title {
            font-family: 'Cinzel', serif;
            font-size: 1.65rem;
            font-weight: 700;
            color: var(--cream);
            letter-spacing: 2px;
            margin-bottom: 4px;
        }
        .admin-sub {
            font-size: 10px;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: var(--caramel);
            font-weight: 500;
            margin-bottom: 30px;
        }

        /* ── Mode chooser ── */
        .mode-chooser {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 28px;
        }
        .mode-btn {
            padding: 13px 10px;
            border-radius: 12px;
            border: 1.5px solid rgba(212,130,74,0.25);
            background: rgba(0,0,0,0.25);
            color: rgba(255,255,255,0.45);
            font-family: 'DM Sans', sans-serif;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            cursor: pointer;
            transition: 0.2s;
        }
        .mode-btn:hover {
            border-color: rgba(212,130,74,0.6);
            color: var(--caramel);
            background: rgba(212,130,74,0.07);
        }
        .mode-btn.active {
            border-color: var(--caramel);
            background: rgba(212,130,74,0.13);
            color: var(--caramel);
            box-shadow: 0 0 0 2px rgba(212,130,74,0.15);
        }
        .mode-btn .mode-icon { font-size: 20px; display: block; margin-bottom: 6px; }

        /* ── Forms ── */
        .form-panel { display: none; }
        .form-panel.active { display: block; }

        .field-label {
            display: block;
            text-align: left;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--caramel);
            margin-bottom: 6px;
            margin-top: 15px;
        }
        .field-input {
            width: 100%;
            height: 46px;
            background: rgba(0,0,0,0.3);
            border: 1px solid rgba(212,130,74,0.3);
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
            box-shadow: 0 0 0 3px rgba(212,130,74,0.1);
        }
        .field-input::placeholder { color: rgba(255,255,255,0.22); }

        .section-divider {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 20px 0 4px;
        }
        .section-divider::before,
        .section-divider::after { content:''; flex:1; height:1px; background:rgba(212,130,74,0.18); }
        .section-divider span {
            font-size: 9px;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: rgba(212,130,74,0.45);
            white-space: nowrap;
        }

        /* strength meter */
        .strength-bar  { height:3px; border-radius:3px; background:rgba(255,255,255,0.07); margin-top:5px; overflow:hidden; }
        .strength-fill { height:100%; width:0%; border-radius:3px; transition:width 0.3s,background 0.3s; }
        .strength-text { font-size:10px; margin-top:3px; letter-spacing:1px; color:rgba(255,255,255,0.3); text-align:left; }

        .key-note {
            font-size: 11px;
            color: rgba(255,255,255,0.28);
            text-align: left;
            margin-top: 5px;
        }

        .btn-admin {
            width: 100%;
            height: 50px;
            margin-top: 26px;
            background: linear-gradient(135deg, var(--mocha-light), var(--mocha-glow));
            color: white;
            border: none;
            border-radius: 30px;
            font-family: 'Cinzel', serif;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 2px;
            text-transform: uppercase;
            cursor: pointer;
            box-shadow: 0 6px 20px rgba(212,130,74,0.3);
            transition: 0.2s;
        }
        .btn-admin:hover { transform:translateY(-2px); box-shadow:0 10px 26px rgba(212,130,74,0.45); }

        .back-link {
            display: block;
            margin-top: 18px;
            color: rgba(255,255,255,0.3);
            text-decoration: none;
            font-size: 12px;
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

    <!-- ── Mode Chooser ── -->
    <div class="mode-chooser">
        <button class="mode-btn active" id="btnLogin" onclick="switchMode('login')">
            <span class="mode-icon">&#9820;</span>
            Login
        </button>
        <button class="mode-btn" id="btnRegister" onclick="switchMode('register')">
            <span class="mode-icon">&#9998;</span>
            New Account
        </button>
    </div>

    <!-- ── LOGIN FORM ── -->
    <div class="form-panel active" id="panelLogin">
        <label class="field-label">Email Address</label>
        <input id="LoginEmail" type="email" class="field-input" placeholder="admin@email.com">

        <label class="field-label">Password</label>
        <input id="LoginPassword" type="password" class="field-input" placeholder="••••••••">

        <div class="section-divider"><span>Authorization</span></div>

        <label class="field-label">Secret Arbiter Key</label>
        <input id="LoginKey" type="password" class="field-input" placeholder="••••••••••••">
        <p class="key-note">&#9888; Required in addition to your password.</p>

        <button class="btn-admin" onclick="loginAdmin()">Enter Portal</button>
    </div>

    <!-- ── REGISTER FORM ── -->
    <div class="form-panel" id="panelRegister">
        <label class="field-label">First Name</label>
        <input id="RegFName" type="text" class="field-input" placeholder="Enter first name"
               oninput="this.value=this.value.replace(/[^a-zA-Z\s]/g,'')">

        <label class="field-label">Last Name</label>
        <input id="RegLName" type="text" class="field-input" placeholder="Enter last name"
               oninput="this.value=this.value.replace(/[^a-zA-Z\s]/g,'')">

        <div class="section-divider"><span>Account Credentials</span></div>

        <label class="field-label">Email Address</label>
        <input id="RegEmail" type="email" class="field-input" placeholder="admin@email.com">

        <label class="field-label">Password</label>
        <input id="RegPassword" type="password" class="field-input"
               placeholder="Min. 8 chars, uppercase, number, symbol"
               oninput="checkStrength(this.value)">
        <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
        <div class="strength-text" id="strengthText"></div>

        <label class="field-label">Confirm Password</label>
        <input id="RegConfirm" type="password" class="field-input" placeholder="Re-enter password">

        <div class="section-divider"><span>Authorization</span></div>

        <label class="field-label">Secret Arbiter Key</label>
        <input id="RegKey" type="password" class="field-input" placeholder="••••••••••••">
        <p class="key-note">&#9888; Only authorized arbiters have this key.</p>

        <button class="btn-admin" onclick="registerAdmin()">Create Admin Account</button>
    </div>

    <a href="loginpage.php" class="back-link">← Return to Player Login</a>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// ── Mode switcher ──────────────────────────────────────
function switchMode(mode) {
    document.getElementById('panelLogin').classList.toggle('active', mode === 'login');
    document.getElementById('panelRegister').classList.toggle('active', mode === 'register');
    document.getElementById('btnLogin').classList.toggle('active', mode === 'login');
    document.getElementById('btnRegister').classList.toggle('active', mode === 'register');
}

// ── Password strength meter ────────────────────────────
function checkStrength(val) {
    let fill = document.getElementById('strengthFill');
    let text = document.getElementById('strengthText');
    if (!val) { fill.style.width='0%'; text.textContent=''; return; }
    let score = 0;
    if (val.length >= 8)  score++;
    if (val.length >= 12) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;
    const levels = [
        {w:'20%',bg:'#e05050',t:'Weak'},
        {w:'40%',bg:'#e08050',t:'Fair'},
        {w:'60%',bg:'#E8A96A',t:'Good'},
        {w:'80%',bg:'#8fbc45',t:'Strong'},
        {w:'100%',bg:'#4E8C24',t:'Very Strong'}
    ];
    let lvl = levels[Math.min(score-1,4)] || levels[0];
    fill.style.width=lvl.w; fill.style.background=lvl.bg;
    text.textContent=lvl.t; text.style.color=lvl.bg;
}

// ── Password rules validator ───────────────────────────
function validatePassword(pass) {
    if (pass.length < 8)            { Swal.fire('Too Short',     'Password must be at least 8 characters.',              'warning'); return false; }
    if (pass.length > 32)           { Swal.fire('Too Long',      'Password must not exceed 32 characters.',              'warning'); return false; }
    if (!/[A-Z]/.test(pass))        { Swal.fire('Weak Password', 'Must include at least 1 uppercase letter (A-Z).',      'warning'); return false; }
    if (!/[a-z]/.test(pass))        { Swal.fire('Weak Password', 'Must include at least 1 lowercase letter (a-z).',      'warning'); return false; }
    if (!/[0-9]/.test(pass))        { Swal.fire('Weak Password', 'Must include at least 1 number (0-9).',                'warning'); return false; }
    if (!/[^A-Za-z0-9]/.test(pass)) { Swal.fire('Weak Password', 'Must include at least 1 special character (@, #, !).','warning'); return false; }
    return true;
}

// ── Login ──────────────────────────────────────────────
function loginAdmin() {
    let em   = $('#LoginEmail').val().trim();
    let pass = $('#LoginPassword').val();
    let key  = $('#LoginKey').val();

    if (!em || !pass || !key) {
        Swal.fire('Wait', 'Please fill in all fields.', 'warning'); return;
    }
    if (key !== 'miffyandboris') {
        Swal.fire('Unauthorized', 'Invalid Secret Arbiter Key!', 'error'); return;
    }

    $.post('../controllers/controller.php', {
        choice: 'adminLogin', email: em, password: pass
    }, function(res) {
        res = res.trim();
        if (res === 'true') {
            Swal.fire({ title: 'Welcome, Arbiter!', icon: 'success', timer: 1400, showConfirmButton: false })
                .then(() => window.location.href = 'dashboardpage.php');
        } else if (res === 'wrong_password') {
            Swal.fire('Incorrect Password', 'The password you entered is wrong.', 'error');
        } else if (res === 'not_found') {
            Swal.fire('Not Found', 'No admin account found with that email.', 'error');
        } else {
            Swal.fire('Error', res, 'error');
        }
    }).fail(() => Swal.fire('System Error', 'Cannot connect to server.', 'error'));
}

// ── Register new admin ─────────────────────────────────
function registerAdmin() {
    let fn   = $('#RegFName').val().trim();
    let ln   = $('#RegLName').val().trim();
    let em   = $('#RegEmail').val().trim();
    let pass = $('#RegPassword').val();
    let conf = $('#RegConfirm').val();
    let key  = $('#RegKey').val();

    if (!fn || !ln || !em || !pass || !conf || !key) {
        Swal.fire('Wait', 'Please fill in all fields.', 'warning'); return;
    }
    if (fn.length < 2 || ln.length < 2) {
        Swal.fire('Invalid Name', 'Names must be at least 2 characters.', 'warning'); return;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em)) {
        Swal.fire('Invalid Email', 'Please enter a valid email address.', 'error'); return;
    }
    if (!validatePassword(pass)) return;
    if (pass !== conf) {
        Swal.fire('Mismatch', 'Passwords do not match!', 'error'); return;
    }
    if (key !== 'miffyandboris') {
        Swal.fire('Unauthorized', 'Invalid Secret Arbiter Key!', 'error'); return;
    }

    $.post('../controllers/controller.php', {
        choice: 'registerAdmin', fn, ln, email: em, password: pass
    }, function(res) {
        res = res.trim();
        if (res === 'true') {
            Swal.fire({
                title: 'Admin Created!',
                text: 'Welcome, Arbiter. Redirecting to portal...',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            }).then(() => window.location.href = 'dashboardpage.php');
        } else if (res === 'email_taken') {
            Swal.fire('Email Taken', 'An admin with that email already exists.', 'error');
        } else {
            Swal.fire('Error', res, 'error');
        }
    }).fail(() => Swal.fire('System Error', 'Cannot connect to server.', 'error'));
}
</script>
</body>
</html>
