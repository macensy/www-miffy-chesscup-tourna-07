<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user'])) { header("Location: loginpage.php"); exit(); }
require_once "../bl/usermanager.php";
$manager = new usermanager();
$logs = $manager->getLogs();
$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Activity Logs | Miffy Chess Cup</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
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
            font-family: 'DM Sans', sans-serif;
            margin: 0; padding-bottom: 60px;
            background: linear-gradient(rgba(18, 8, 4, 0.88), rgba(18, 8, 4, 0.88)),
                        url('https://i.pinimg.com/736x/64/de/51/64de5126d1398692e1c52a44f1e8ced0.jpg');
            background-size: cover; background-position: center; background-attachment: fixed;
            min-height: 100vh; color: white;
        }

        /* TOP BAR */
        .top-bar {
            display: flex; align-items: center; justify-content: space-between;
            padding: 22px 32px;
            border-bottom: 1px solid var(--glass-border);
            background: rgba(18, 8, 4, 0.72);
            backdrop-filter: blur(14px);
            position: sticky; top: 0; z-index: 100;
        }
        .brand-row { display: flex; align-items: center; gap: 14px; }
        .brand-logo {
            width: 58px; height: 58px; border-radius: 50%;
            background: white; border: 2.5px solid var(--caramel);
            object-fit: contain; padding: 4px;
        }
        .brand-name { font-family: 'Cinzel', serif; font-size: 1.55rem; font-weight: 700; color: var(--cream); letter-spacing: 2px; }
        .brand-user { font-size: 12px; color: var(--caramel); letter-spacing: 2.5px; text-transform: uppercase; font-weight: 500; }
        .nav-actions { display: flex; align-items: center; gap: 10px; }
        .nav-link { font-size: 12px; letter-spacing: 1.5px; text-transform: uppercase; color: rgba(255,255,255,0.45); text-decoration: none; transition: 0.2s; }
        .nav-link:hover { color: var(--caramel); }
        .btn-logout {
            padding: 7px 18px; border: 1px solid var(--caramel); color: var(--caramel);
            background: transparent; border-radius: 20px; font-size: 12px;
            font-weight: 500; letter-spacing: 1px; text-decoration: none; transition: 0.2s;
        }
        .btn-logout:hover { background: rgba(212,130,74,0.12); color: var(--caramel); }

        /* PAGE */
        .page-wrap { padding: 28px 24px 0; max-width: 1000px; margin: 0 auto; }
        .page-header { display: flex; align-items: flex-end; justify-content: space-between; margin-bottom: 22px; }
        .back-btn {
            display: flex; align-items: center; gap: 6px;
            color: rgba(255,255,255,0.45); text-decoration: none;
            font-size: 13px; transition: 0.2s; margin-bottom: 6px;
        }
        .back-btn:hover { color: var(--caramel); }
        .back-btn .material-icons { font-size: 18px; }
        .page-title { font-family: 'Cinzel', serif; font-size: 1.6rem; font-weight: 700; color: var(--cream); letter-spacing: 1.5px; }
        .page-sub { font-size: 11px; letter-spacing: 2.5px; text-transform: uppercase; color: rgba(255,255,255,0.35); margin-top: 3px; }

        /* CARD */
        .glass-card {
            background: var(--glass-bg); border: 1px solid var(--glass-border);
            border-radius: 16px; padding: 24px 20px; backdrop-filter: blur(12px);
        }

        /* TABLE */
        .logs-table { width: 100%; border-collapse: collapse; }
        .logs-table th {
            font-size: 10px; font-weight: 700; letter-spacing: 2px;
            text-transform: uppercase; color: var(--caramel);
            padding: 0 12px 14px; text-align: left;
            border-bottom: 1px solid rgba(212,130,74,0.2);
        }
        .logs-table td {
            padding: 13px 12px; font-size: 13px;
            color: rgba(255,255,255,0.72);
            border-bottom: 1px solid rgba(255,255,255,0.04);
            vertical-align: middle;
        }
        .logs-table tr:last-child td { border-bottom: none; }
        .logs-table tr:hover td { background: var(--glass-hover); }
        .timestamp { font-family: 'DM Sans', sans-serif; font-size: 12px; color: rgba(255,255,255,0.4); white-space: nowrap; }
        .actor-name { font-weight: 600; color: rgba(255,255,255,0.88); }
        .action-chip {
            display: inline-flex; align-items: center; gap: 5px;
            background: rgba(212,130,74,0.12); border: 1px solid rgba(212,130,74,0.22);
            color: var(--caramel); padding: 4px 12px;
            border-radius: 20px; font-size: 12px; font-weight: 500;
        }
        .action-chip .material-icons { font-size: 13px; }
        .empty-state { text-align: center; padding: 50px; color: rgba(255,255,255,0.18); font-size: 13px; letter-spacing: 1px; }

        @media (max-width: 768px) {
            .top-bar { padding: 14px 16px; }
            .page-wrap { padding: 16px 12px 0; }
        }
    </style>
</head>
<body>

<!-- TOP BAR -->
<div class="top-bar">
    <div class="brand-row">
        <img src="../assets/miffy.jpg" class="brand-logo" alt="Miffy">
        <div>
            <div class="brand-name">Miffy Chess Cup</div>
            <div class="brand-user"><?= strtoupper($user['firstName'] ?? 'User') ?> &nbsp;·&nbsp; Admin</div>
        </div>
    </div>
    <div class="nav-actions">
        <a href="dashboardpage.php" class="nav-link">Dashboard</a>
        <a href="playerspage.php" class="nav-link">Players</a>
        <a href="logout.php" class="btn-logout">Logout</a>
    </div>
</div>

<div class="page-wrap">
    <div class="page-header">
        <div>
            <a href="dashboardpage.php" class="back-btn"><i class="material-icons">arrow_back</i> Back</a>
            <div class="page-title">Activity Logs</div>
            <div class="page-sub">System Audit Trail</div>
        </div>
    </div>

    <div class="glass-card">
        <table class="logs-table">
            <thead>
                <tr><th>Timestamp</th><th>Admin / User</th><th>Activity</th></tr>
            </thead>
            <tbody>
                <?php if(empty($logs)): ?>
                <tr><td colspan="3" class="empty-state">No activity logs yet.</td></tr>
                <?php else: ?>
                <?php foreach($logs as $l): ?>
                <tr>
                    <td><span class="timestamp"><?= date('Y-m-d H:i A', strtotime($l['created_at'])) ?></span></td>
                    <td><span class="actor-name"><?= $l['firstName'] . ' ' . $l['lastName'] ?></span></td>
                    <td>
                        <span class="action-chip">
                            <i class="material-icons">info</i>
                            <?= $l['action'] ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
</body>
</html>
