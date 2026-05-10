<?php
session_start();
require_once "../bl/usermanager.php";
require_once "../helper/sendEmail.php";

$manager = new usermanager();
$choice  = $_POST['choice'] ?? '';

// ── EMAIL TEMPLATES ───────────────────────────────────────────────────────────

function getLoginEmailBody(string $name, string $date): string {
    return "
    <div style='font-family:Georgia,serif;max-width:600px;margin:auto;border-radius:16px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.4);'>
        <div style='background:linear-gradient(135deg,#3B1F0E 0%,#6B3A2A 50%,#B5622A 100%);padding:40px 30px;text-align:center;'>
            <div style='font-size:48px;margin-bottom:12px;'>&#9820;</div>
            <h1 style='color:#FAF0DC;margin:0;font-size:24px;letter-spacing:4px;text-transform:uppercase;'>Miffy Chess Cup</h1>
            <p style='color:#E8A96A;margin:8px 0 0;font-size:11px;letter-spacing:3px;text-transform:uppercase;'>Tournament Portal</p>
        </div>
        <div style='background:#1C0A04;padding:36px 30px;'>
            <div style='border-left:3px solid #D4824A;padding-left:16px;margin-bottom:28px;'>
                <h2 style='color:#FAF0DC;margin:0 0 6px;font-size:20px;letter-spacing:1px;'>Login Notification</h2>
                <p style='color:#E8A96A;margin:0;font-size:12px;letter-spacing:2px;text-transform:uppercase;'>Player Activity Alert</p>
            </div>
            <p style='color:rgba(255,255,255,0.65);font-size:14px;line-height:1.7;margin-bottom:24px;'>A player has just logged in to the Miffy Chess Cup tournament portal.</p>
            <table style='width:100%;border-collapse:collapse;'>
                <tr>
                    <td style='padding:13px 16px;background:rgba(107,58,42,0.35);border-bottom:1px solid rgba(212,130,74,0.15);color:#E8A96A;font-size:11px;letter-spacing:2px;text-transform:uppercase;width:36%;'>Player Name</td>
                    <td style='padding:13px 16px;background:rgba(107,58,42,0.15);border-bottom:1px solid rgba(212,130,74,0.15);color:#FAF0DC;font-weight:bold;font-size:15px;'>{$name}</td>
                </tr>
                <tr>
                    <td style='padding:13px 16px;background:rgba(107,58,42,0.35);color:#E8A96A;font-size:11px;letter-spacing:2px;text-transform:uppercase;'>Date &amp; Time</td>
                    <td style='padding:13px 16px;background:rgba(107,58,42,0.15);color:rgba(255,255,255,0.7);font-size:14px;'>{$date}</td>
                </tr>
            </table>
        </div>
        <div style='background:#0F0502;padding:20px 30px;text-align:center;border-top:1px solid rgba(212,130,74,0.2);'>
            <p style='color:#E8A96A;font-size:13px;margin:0 0 4px;letter-spacing:1px;'>&#9820; Miffy Chess Cup</p>
            <p style='color:rgba(255,255,255,0.25);font-size:11px;margin:0;'>This is an automated notification. Please do not reply to this email.</p>
        </div>
    </div>";
}

function getRegisterEmailBody(string $name, string $gender, $age, $rating, string $date): string {
    return "
    <div style='font-family:Georgia,serif;max-width:600px;margin:auto;border-radius:16px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.4);'>
        <div style='background:linear-gradient(135deg,#3B1F0E 0%,#6B3A2A 50%,#B5622A 100%);padding:40px 30px;text-align:center;'>
            <div style='font-size:48px;margin-bottom:12px;'>&#9820;</div>
            <h1 style='color:#FAF0DC;margin:0;font-size:24px;letter-spacing:4px;text-transform:uppercase;'>Miffy Chess Cup</h1>
            <p style='color:#E8A96A;margin:8px 0 0;font-size:11px;letter-spacing:3px;text-transform:uppercase;'>Tournament Portal</p>
        </div>
        <div style='background:#1C0A04;padding:36px 30px;'>
            <div style='border-left:3px solid #D4824A;padding-left:16px;margin-bottom:28px;'>
                <h2 style='color:#FAF0DC;margin:0 0 6px;font-size:20px;letter-spacing:1px;'>New Player Registered</h2>
                <p style='color:#E8A96A;margin:0;font-size:12px;letter-spacing:2px;text-transform:uppercase;'>Tournament Enrollment</p>
            </div>
            <p style='color:rgba(255,255,255,0.65);font-size:14px;line-height:1.7;margin-bottom:24px;'>A new player has successfully registered for the Miffy Chess Cup tournament.</p>
            <table style='width:100%;border-collapse:collapse;'>
                <tr>
                    <td style='padding:13px 16px;background:rgba(107,58,42,0.35);border-bottom:1px solid rgba(212,130,74,0.15);color:#E8A96A;font-size:11px;letter-spacing:2px;text-transform:uppercase;width:36%;'>Full Name</td>
                    <td style='padding:13px 16px;background:rgba(107,58,42,0.15);border-bottom:1px solid rgba(212,130,74,0.15);color:#FAF0DC;font-weight:bold;font-size:15px;'>{$name}</td>
                </tr>
                <tr>
                    <td style='padding:13px 16px;background:rgba(107,58,42,0.35);border-bottom:1px solid rgba(212,130,74,0.15);color:#E8A96A;font-size:11px;letter-spacing:2px;text-transform:uppercase;'>Gender</td>
                    <td style='padding:13px 16px;background:rgba(107,58,42,0.15);border-bottom:1px solid rgba(212,130,74,0.15);color:rgba(255,255,255,0.75);font-size:14px;'>{$gender}</td>
                </tr>
                <tr>
                    <td style='padding:13px 16px;background:rgba(107,58,42,0.35);border-bottom:1px solid rgba(212,130,74,0.15);color:#E8A96A;font-size:11px;letter-spacing:2px;text-transform:uppercase;'>Age</td>
                    <td style='padding:13px 16px;background:rgba(107,58,42,0.15);border-bottom:1px solid rgba(212,130,74,0.15);color:rgba(255,255,255,0.75);font-size:14px;'>{$age}</td>
                </tr>
                <tr>
                    <td style='padding:13px 16px;background:rgba(107,58,42,0.35);border-bottom:1px solid rgba(212,130,74,0.15);color:#E8A96A;font-size:11px;letter-spacing:2px;text-transform:uppercase;'>FIDE Rating</td>
                    <td style='padding:13px 16px;background:rgba(107,58,42,0.15);border-bottom:1px solid rgba(212,130,74,0.15);color:#F0C86A;font-weight:bold;font-size:15px;'>{$rating}</td>
                </tr>
                <tr>
                    <td style='padding:13px 16px;background:rgba(107,58,42,0.35);color:#E8A96A;font-size:11px;letter-spacing:2px;text-transform:uppercase;'>Registered On</td>
                    <td style='padding:13px 16px;background:rgba(107,58,42,0.15);color:rgba(255,255,255,0.7);font-size:14px;'>{$date}</td>
                </tr>
            </table>
        </div>
        <div style='background:linear-gradient(90deg,#3B1F0E,#B5622A,#3B1F0E);padding:14px 30px;text-align:center;'>
            <p style='color:#FAF0DC;margin:0;font-size:12px;letter-spacing:3px;text-transform:uppercase;'>&#9820; &nbsp; Welcome to the Tournament &nbsp; &#9820;</p>
        </div>
        <div style='background:#0F0502;padding:20px 30px;text-align:center;border-top:1px solid rgba(212,130,74,0.2);'>
            <p style='color:#E8A96A;font-size:13px;margin:0 0 4px;letter-spacing:1px;'>&#9820; Miffy Chess Cup</p>
            <p style='color:rgba(255,255,255,0.25);font-size:11px;margin:0;'>This is an automated notification. Please do not reply to this email.</p>
        </div>
    </div>";
}

// ── 1. LOGIN ──────────────────────────────────────────────────────────────────
if ($choice == "login") {
    $email    = $_POST['email']    ?? '';
    $password = $_POST['password'] ?? '';
    $res      = $manager->login($email, $password);

    if ($res === true) {
        $user = $_SESSION['user'];
        $name = htmlspecialchars($user['firstName'] . ' ' . $user['lastName']);
        $date = date('F j, Y \a\t g:i A');
        sendEmail("peyttabungar@gmail.com", "Admin", "Player Login Alert: $name", getLoginEmailBody($name, $date));
        echo "true";
    } else {
        echo $res; // 'not_found' or 'wrong_password'
    }
    exit();
}

// ── 2. REGISTER PLAYER ───────────────────────────────────────────────────────
if ($choice == "registerPlayer") {
    $fn       = $_POST['fn']       ?? '';
    $ln       = $_POST['ln']       ?? '';
    $gd       = $_POST['gd']       ?? 'N/A';
    $ag       = $_POST['ag']       ?? 0;
    $rt       = $_POST['rt']       ?? 0;
    $email    = $_POST['email']    ?? '';
    $password = $_POST['password'] ?? '';
    $role     = 'Player';

    $res = $manager->registerPlayer($fn, $ln, $gd, $ag, $rt, $role, $email, $password);

    if ($res == true) {
        $name   = htmlspecialchars("$fn $ln");
        $gender = htmlspecialchars($gd);
        $date   = date('F j, Y \a\t g:i A');
        sendEmail("peyttabungar@gmail.com", "Admin", "New Player Registered: $name", getRegisterEmailBody($name, $gender, $ag, $rt, $date));
        ob_clean();
        echo "true";
    } else {
        echo $res; // 'email_taken' or error message
    }
    exit();
}

// ── 3. ADMIN LOGIN ───────────────────────────────────────────────────────────
if ($choice == "adminLogin") {
    $email    = $_POST['email']    ?? '';
    $password = $_POST['password'] ?? '';
    $res      = $manager->adminLogin($email, $password);
    echo ($res === true) ? "true" : $res;
    exit();
}

// ── 4a. REGISTER ADMIN (first time setup) ────────────────────────────────────
if ($choice == "registerAdmin") {
    $fn       = $_POST['fn']       ?? '';
    $ln       = $_POST['ln']       ?? '';
    $email    = $_POST['email']    ?? '';
    $password = $_POST['password'] ?? '';
    $id       = rand(100000, 999999);
    $role     = 'Admin';

    $res = $manager->registerAdminFunc($id, $fn, $ln, $role, $email, $password);

    if ($res === true) {
        $_SESSION['user'] = [
            'userID'    => $id,
            'firstName' => $fn,
            'lastName'  => $ln,
            'role'      => $role
        ];
        echo "true";
    } else {
        echo $res; // 'email_taken' or error
    }
    exit();
}

// ── 5. UPDATE USER ───────────────────────────────────────────────────────────
if ($choice == "updateUser") {
    $id = $_POST['id'] ?? 0;
    $fn = $_POST['fn'] ?? '';
    $ln = $_POST['ln'] ?? '';
    $ag = $_POST['ag'] ?? 0;
    $rt = $_POST['rt'] ?? 0;

    echo $manager->updateUserFunc($id, $fn, $ln, $ag, $rt) ? "true" : "false";
    exit();
}

// ── 6. DELETE PLAYER ─────────────────────────────────────────────────────────
if ($choice == "deletePlayer") {
    $id = $_POST['userID'] ?? 0;
    echo $manager->deleteUser($id) ? "true" : "false";
    exit();
}

// ── 7. GENERATE PAIRS ────────────────────────────────────────────────────────
if ($choice == 'generatePairs') {
    $round   = $_POST['round'] ?? 1;
    $user    = $_SESSION['user'] ?? null;
    $isAdmin = isset($user['role']) && strcasecmp($user['role'], 'Admin') == 0;

    if (!$isAdmin) { die("Unauthorized: Admin access only."); }

    $res = $manager->generatePairingFunc($round);
    ob_clean();
    echo ($res === true) ? "true" : $res;
    exit();
}

// ── 8. UPDATE SCORE ──────────────────────────────────────────────────────────
if ($choice == 'updateScore') {
    $mid = $_POST['mID'] ?? 0;
    $s1  = $_POST['s1']  ?? 0;
    $s2  = $_POST['s2']  ?? 0;

    $res = $manager->updateScoreFunc($mid, $s1, $s2);
    echo ($res === "true" || $res === true) ? "true" : $res;
    exit();
}

// ── 9. RESET TOURNAMENT ──────────────────────────────────────────────────────
if ($choice == 'resetTournament') {
    $res = $manager->resetTournamentFunc();
    echo ($res === true) ? "true" : $res;
    exit();
}
?>
