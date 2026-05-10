<?php
session_start();
require_once "../bl/usermanager.php";
$manager = new usermanager();
$players = $manager->getUser();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Join Tournament | Miffy Chess Cup</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --mocha-light:  #B5622A;
            --mocha-glow:   #D4824A;
            --caramel:      #E8A96A;
            --cream:        #FAF0DC;
            --gold-lt:      #F0C86A;
            --glass-bg:     rgba(30, 14, 8, 0.58);
            --glass-border: rgba(212, 130, 74, 0.26);
            --glass-hover:  rgba(212, 130, 74, 0.08);
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'DM Sans', sans-serif; margin: 0;
            background: linear-gradient(rgba(18,8,4,0.88), rgba(18,8,4,0.88)),
                        url('https://i.pinimg.com/1200x/3e/61/10/3e611090fe833da3f7479bbe90e04df5.jpg');
            background-size: cover; background-position: center; background-attachment: fixed;
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
            padding: 28px 16px; color: white;
        }
        .reg-wrap {
            width: 100%; max-width: 960px;
            background: var(--glass-bg); backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border); border-radius: 22px;
            padding: 40px; box-shadow: 0 24px 60px rgba(0,0,0,0.5);
        }
        .reg-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0; }
        .left-panel { padding-right: 36px; border-right: 1px solid rgba(212,130,74,0.15); }
        .right-panel { padding-left: 36px; }
        .panel-logo {
            width: 90px; height: 90px; border-radius: 50%;
            background: white; object-fit: contain; padding: 8px;
            border: 2.5px solid var(--caramel); margin-bottom: 14px;
        }
        .panel-title { font-family: 'Cinzel', serif; font-size: 1.8rem; font-weight: 700; color: var(--cream); letter-spacing: 2px; margin-bottom: 4px; }
        .panel-sub { font-size: 11px; letter-spacing: 3px; text-transform: uppercase; color: var(--caramel); margin-bottom: 24px; font-weight: 500; }
        .section-label { font-size: 10px; font-weight: 700; letter-spacing: 2.5px; text-transform: uppercase; color: rgba(212,130,74,0.6); margin-bottom: 12px; }
        .section-divider { border: none; border-top: 1px solid rgba(212,130,74,0.15); margin: 14px 0 16px; }
        .field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .field-group { margin-bottom: 13px; }
        .field-label { display: block; font-size: 11px; font-weight: 600; letter-spacing: 2px; text-transform: uppercase; color: var(--caramel); margin-bottom: 7px; }
        .field-input {
            width: 100%; height: 44px; background: rgba(0,0,0,0.28);
            border: 1px solid rgba(212,130,74,0.3); border-radius: 10px;
            color: white; font-family: 'DM Sans', sans-serif; font-size: 14px;
            padding: 0 13px; outline: none; transition: 0.2s;
        }
        .field-input:focus { border-color: var(--caramel); box-shadow: 0 0 0 3px rgba(212,130,74,0.1); }
        .field-input::placeholder { color: rgba(255,255,255,0.22); }
        select.field-select {
            display: block !important; width: 100% !important; height: 44px !important;
            background: rgba(0,0,0,0.45) !important; border: 1px solid rgba(212,130,74,0.35) !important;
            border-radius: 10px !important; color: white !important;
            font-family: 'DM Sans', sans-serif !important; font-size: 14px !important;
            padding: 0 36px 0 13px !important; outline: none !important; cursor: pointer !important;
            -webkit-appearance: none !important; appearance: none !important;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' fill='none' viewBox='0 0 12 8'%3E%3Cpath stroke='%23E8A96A' stroke-width='1.5' stroke-linecap='round' d='M1 1l5 5 5-5'/%3E%3C/svg%3E") !important;
            background-repeat: no-repeat !important; background-position: right 14px center !important;
            margin: 0 !important; box-shadow: none !important;
        }
        select.field-select option { background: #2a1008; color: white; }
        .select-wrapper { display: none !important; }
        .strength-bar { height: 3px; border-radius: 3px; background: rgba(255,255,255,0.08); margin-top: 6px; overflow: hidden; }
        .strength-fill { height: 100%; width: 0%; border-radius: 3px; transition: width 0.3s, background 0.3s; }
        .strength-text { font-size: 10px; margin-top: 4px; letter-spacing: 1px; color: rgba(255,255,255,0.35); }
        .btn-register {
            width: 100%; height: 52px; margin-top: 18px;
            background: linear-gradient(135deg, var(--mocha-light), var(--mocha-glow));
            color: white; border: none; border-radius: 30px;
            font-family: 'Cinzel', serif; font-size: 14px; font-weight: 600;
            letter-spacing: 2px; text-transform: uppercase; cursor: pointer;
            box-shadow: 0 6px 22px rgba(212,130,74,0.35); transition: 0.2s;
        }
        .btn-register:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(212,130,74,0.5); }
        .back-link { display: block; text-align: center; margin-top: 14px; color: var(--caramel); text-decoration: none; font-size: 13px; font-weight: 500; }
        .back-link:hover { opacity: 0.75; }
        .list-title { font-family: 'Cinzel', serif; font-size: 12px; font-weight: 700; letter-spacing: 3px; text-transform: uppercase; color: var(--caramel); margin-bottom: 16px; padding-bottom: 10px; border-bottom: 1px solid rgba(212,130,74,0.18); }
        .player-scroll { max-height: 540px; overflow-y: auto; padding-right: 4px; }
        .player-scroll::-webkit-scrollbar { width: 4px; }
        .player-scroll::-webkit-scrollbar-thumb { background: rgba(212,130,74,0.3); border-radius: 4px; }
        .player-table { width: 100%; border-collapse: collapse; }
        .player-table th { font-size: 10px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; color: var(--caramel); padding: 0 8px 12px; text-align: left; border-bottom: 1px solid rgba(212,130,74,0.18); }
        .player-table td { padding: 9px 8px; font-size: 13px; color: rgba(255,255,255,0.78); border-bottom: 1px solid rgba(255,255,255,0.04); }
        .player-table tr:last-child td { border-bottom: none; }
        .player-table tr:hover td { background: var(--glass-hover); }
        .rating-val { color: var(--gold-lt); font-weight: 500; }
        .empty-state { text-align: center; padding: 40px 10px; color: rgba(255,255,255,0.2); font-size: 13px; }
        @media (max-width: 700px) {
            .reg-grid { grid-template-columns: 1fr; }
            .left-panel { padding-right: 0; border-right: none; border-bottom: 1px solid rgba(212,130,74,0.15); padding-bottom: 28px; margin-bottom: 28px; }
            .right-panel { padding-left: 0; }
            .field-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="reg-wrap">
    <div class="reg-grid">

        <!-- LEFT: Form -->
        <div class="left-panel">
            <center>
                <img src="../assets/miffy.jpg" class="panel-logo" alt="Miffy">
                <div class="panel-title">Register</div>
                <div class="panel-sub">Join the Tournament!</div>
            </center>

            <div class="section-label">Player Info</div>
            <div class="field-row">
                <div class="field-group">
                    <label class="field-label">First Name</label>
                    <input id="FName" type="text" class="field-input" minlength="2" maxlength="50"
                           oninput="this.value=this.value.replace(/[^a-zA-Z\s]/g,'');" placeholder="First name">
                </div>
                <div class="field-group">
                    <label class="field-label">Last Name</label>
                    <input id="LName" type="text" class="field-input" minlength="2" maxlength="50"
                           oninput="this.value=this.value.replace(/[^a-zA-Z\s]/g,'');" placeholder="Last name">
                </div>
            </div>

            <div class="field-group">
                <label class="field-label">Gender</label>
                <select id="Gender" class="field-select">
                    <option value="" disabled selected>Choose gender</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>
            </div>

            <div class="field-row">
                <div class="field-group">
                    <label class="field-label">Age</label>
                    <input id="Age" type="number" class="field-input" placeholder="e.g. 21"
                           oninput="if(this.value.length>2)this.value=this.value.slice(0,2);">
                </div>
                <div class="field-group">
                    <label class="field-label">FIDE Rating</label>
                    <input id="Rating" type="number" class="field-input" placeholder="e.g. 1800"
                           oninput="if(this.value.length>4)this.value=this.value.slice(0,4);">
                </div>
            </div>

            <hr class="section-divider">
            <div class="section-label">Account Credentials</div>

            <div class="field-group">
                <label class="field-label">Email</label>
                <input id="RegEmail" type="email" class="field-input" maxlength="100" placeholder="e.g. juan@email.com">
            </div>

            <div class="field-group">
                <label class="field-label">Password</label>
                <input id="RegPassword" type="password" class="field-input" placeholder="Min. 8 chars, uppercase, number, symbol" oninput="checkStrength(this.value)">
                <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
                <div class="strength-text" id="strengthText"></div>
            </div>

            <div class="field-group">
                <label class="field-label">Confirm Password</label>
                <input id="RegConfirm" type="password" class="field-input" placeholder="Re-enter password">
            </div>

            <button class="btn-register" onclick="addFunc()">Submit Player</button>
            <a href="loginpage.php" class="back-link">← Already have an account?</a>
        </div>

        <!-- RIGHT: Player list -->
        <div class="right-panel">
            <div class="list-title">Registered Players</div>
            <div class="player-scroll">
                <?php if(empty($players)): ?>
                <div class="empty-state">No players registered yet.</div>
                <?php else: ?>
                <table class="player-table">
                    <thead><tr><th>Name</th><th>Age</th><th>Rating</th></tr></thead>
                    <tbody>
                        <?php foreach($players as $p): ?>
                        <tr>
                            <td style="font-weight:500;color:rgba(255,255,255,0.88);"><?= strtoupper($p['firstName'].' '.$p['lastName']) ?></td>
                            <td><?= $p['age'] ?></td>
                            <td><span class="rating-val"><?= $p['rating'] ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../script/service.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var sel = document.getElementById('Gender');
    if (sel) {
        var instance = M.FormSelect.getInstance(sel);
        if (instance) instance.destroy();
        var wrapper = sel.closest('.select-wrapper');
        if (wrapper) { wrapper.parentNode.insertBefore(sel, wrapper); wrapper.parentNode.removeChild(wrapper); }
    }
});
function checkStrength(val) {
    let fill = document.getElementById('strengthFill');
    let text = document.getElementById('strengthText');
    if (!val) { fill.style.width='0%'; text.textContent=''; return; }
    let score = 0;
    if (val.length >= 8) score++;
    if (val.length >= 12) score++;
    if (/[a-z]/.test(val)) {} // lowercase required but not scored separately
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
    fill.style.width = lvl.w; fill.style.background = lvl.bg;
    text.textContent = lvl.t; text.style.color = lvl.bg;
}
</script>
</body>
</html>