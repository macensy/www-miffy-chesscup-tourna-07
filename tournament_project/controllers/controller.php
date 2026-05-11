<?php
session_start();
require_once "../bl/usermanager.php";
require_once "../helper/sendEmail.php";
require_once "../helper/emailLogin.php";
require_once "../helper/emailReg.php";

$manager = new usermanager();
$choice  = $_POST['choice'] ?? '';

if ($choice == "login") {
    $email    = $_POST['email']    ?? '';
    $password = $_POST['password'] ?? '';
    $res      = $manager->login($email, $password);

    if ($res === true) {
        $user = $_SESSION['user'];
        $name = htmlspecialchars($user['firstName'] . ' ' . $user['lastName']);
        $date = date('F j, Y \a\t g:i A');
        $manager->addLog($user['userID'], "User Logged In: $name");
        sendEmail("peyttabungar@gmail.com", "Admin", "Player Login Alert: $name", getLoginEmailBody($name, $date));
        echo "true";
    } else {
        echo $res;
    }
    exit();
}


if ($choice == "registerPlayer") {
    $fn       = $_POST['fn']       ?? '';
    $ln       = $_POST['ln']       ?? '';
    $gd       = $_POST['gd']       ?? 'N/A';
    $ag       = $_POST['ag']       ?? 0;
    $rt       = $_POST['rt']       ?? 0;
    $email    = $_POST['email']    ?? '';
    $password = $_POST['password'] ?? '';

    $res = $manager->registerPlayer($fn, $ln, $gd, $ag, $rt, $email, $password);

    if ($res == true) {
        $name   = htmlspecialchars("$fn $ln");
        $gender = htmlspecialchars($gd);
        $date   = date('F j, Y \a\t g:i A');
        $manager->addLog(0, "Registered Player: $name");
        sendEmail("peyttabungar@gmail.com", "Admin", "New Player Registered: $name", getRegisterEmailBody($name, $gender, $ag, $rt, $date));
        ob_clean();
        echo "true";
    } else {
        echo $res;
    }
    exit();
}


if ($choice == "adminLogin") {
    $email    = $_POST['email']    ?? '';
    $password = $_POST['password'] ?? '';
    $res      = $manager->adminLogin($email, $password);

    if ($res === true) {
        $user = $_SESSION['user'];
        $name = htmlspecialchars($user['firstName'] . ' ' . $user['lastName']);
        $manager->addLog($user['userID'], "Admin Logged In: $name");
    }
    echo ($res === true) ? "true" : $res;
    exit();
}


if ($choice == "registerAdmin") {
    $fn       = $_POST['fn']       ?? '';
    $ln       = $_POST['ln']       ?? '';
    $email    = $_POST['email']    ?? '';
    $password = $_POST['password'] ?? '';

    $res = $manager->registerAdminFunc($fn, $ln, $email, $password);

    if ($res === true) {
        $loginRes = $manager->adminLogin($email, $password);
        $name = htmlspecialchars("$fn $ln");
        $manager->addLog($_SESSION['user']['userID'] ?? 0, "Registered Admin: $name");
        echo "true";
    } else {
        echo $res;
    }
    exit();
}

if ($choice == "updateUser") {
    $id = $_POST['id'] ?? 0;
    $fn = $_POST['fn'] ?? '';
    $ln = $_POST['ln'] ?? '';
    $ag = $_POST['ag'] ?? 0;
    $rt = $_POST['rt'] ?? 0;

    $res = $manager->updateUserFunc($id, $fn, $ln, $ag, $rt);
    if ($res) {
        $adminID = $_SESSION['user']['userID'] ?? 0;
        $name    = htmlspecialchars("$fn $ln");
        $manager->addLog($adminID, "Updated Player: $name");
    }
    echo $res ? "true" : "false";
    exit();
}


if ($choice == "deletePlayer") {
    $id  = $_POST['userID'] ?? 0;
    $res = $manager->deleteUser($id);
    if ($res) {
        $adminID = $_SESSION['user']['userID'] ?? 0;
        $manager->addLog($adminID, "Deleted Player ID: $id");
    }
    echo $res ? "true" : "false";
    exit();
}



if ($choice == 'generatePairs') {
    $round   = $_POST['round'] ?? 1;
    $isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

    if (!$isAdmin) { die("Unauthorized: Admin access only."); }

    $res = $manager->generatePairingFunc($round);
    ob_clean();
    echo ($res === true) ? "true" : $res;
    exit();
}

if ($choice == 'updateScore') {
    $mid = $_POST['mID'] ?? 0;
    $s1  = $_POST['s1']  ?? 0;
    $s2  = $_POST['s2']  ?? 0;

    $res = $manager->updateScoreFunc($mid, $s1, $s2);
    echo ($res === "true" || $res === true) ? "true" : $res;
    exit();
}

if ($choice == 'resetTournament') {
    $res = $manager->resetTournamentFunc();
    echo ($res === true) ? "true" : $res;
    exit();
}
?>