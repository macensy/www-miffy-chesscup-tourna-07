<?php
session_start();
if (!isset($_SESSION['user'])) { header("Location: loginpage.php"); exit(); }
require_once "../bl/usermanager.php";
$manager = new usermanager();
$user = $_SESSION['user'];
$allPlayers = $manager->getUser();
$isAdmin = (isset($user['role']) && $user['role'] == 'Admin') || (strtolower($user['firstName']) == 'faith');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Players | Miffy Chess Cup</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --mocha-light:  #B5622A;
            --mocha-glow:   #D4824A;
            --caramel:      #E8A96A;
            --caramel-lt:   #F5C98A;
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
        .brand-name {
            font-family: 'Cinzel', serif; font-size: 1.55rem;
            font-weight: 700; color: var(--cream); letter-spacing: 2px;
        }
        .brand-user { font-size: 12px; color: var(--caramel); letter-spacing: 2.5px; text-transform: uppercase; font-weight: 500; }
        .nav-actions { display: flex; align-items: center; gap: 10px; }
        .nav-link { font-size: 12px; letter-spacing: 1.5px; text-transform: uppercase; color: rgba(255,255,255,0.45); text-decoration: none; transition: 0.2s; }
        .nav-link:hover { color: var(--caramel); }
        .btn-logout {
            padding: 7px 18px; border: 1px solid var(--caramel); color: var(--caramel);
            background: transparent; border-radius: 20px; font-size: 12px; font-weight: 500;
            letter-spacing: 1px; text-decoration: none; transition: 0.2s;
        }
        .btn-logout:hover { background: rgba(212,130,74,0.12); color: var(--caramel); }

        /* PAGE */
        .page-wrap { padding: 28px 24px 0; max-width: 1100px; margin: 0 auto; }
        .page-header {
            display: flex; align-items: flex-end; justify-content: space-between; margin-bottom: 22px;
        }
        .back-btn {
            display: flex; align-items: center; gap: 6px;
            color: rgba(255,255,255,0.45); text-decoration: none;
            font-size: 13px; letter-spacing: 0.5px; transition: 0.2s;
            margin-bottom: 6px;
        }
        .back-btn:hover { color: var(--caramel); }
        .back-btn .material-icons { font-size: 18px; }
        .page-title { font-family: 'Cinzel', serif; font-size: 1.6rem; font-weight: 700; color: var(--cream); letter-spacing: 1.5px; }
        .page-sub { font-size: 11px; letter-spacing: 2.5px; text-transform: uppercase; color: rgba(255,255,255,0.35); margin-top: 3px; }
        .btn-add {
            display: flex; align-items: center; gap: 5px;
            padding: 9px 20px; background: var(--mocha-light);
            color: white; border: none; border-radius: 20px;
            font-size: 13px; font-weight: 500; text-decoration: none;
            transition: 0.2s; cursor: pointer;
        }
        .btn-add:hover { background: var(--mocha-glow); color: white; }
        .btn-add .material-icons { font-size: 16px; }

        /* CARD */
        .glass-card {
            background: var(--glass-bg); border: 1px solid var(--glass-border);
            border-radius: 16px; padding: 24px 20px;
            backdrop-filter: blur(12px);
        }

        /* TABLE */
        .players-table { width: 100%; border-collapse: collapse; }
        .players-table th {
            font-size: 10px; font-weight: 700; letter-spacing: 2px;
            text-transform: uppercase; color: var(--caramel);
            padding: 0 12px 14px; text-align: left;
            border-bottom: 1px solid rgba(212,130,74,0.2);
        }
        .players-table td {
            padding: 12px; font-size: 13px;
            color: rgba(255,255,255,0.75);
            border-bottom: 1px solid rgba(255,255,255,0.04);
            vertical-align: middle;
        }
        .players-table tr:last-child td { border-bottom: none; }
        .players-table tr:hover td { background: var(--glass-hover); }
        .id-badge { color: var(--caramel); font-family: 'Cinzel', serif; font-size: 12px; }
        .name-cell { font-weight: 500; color: rgba(255,255,255,0.9); }
        .rating-val { color: var(--gold-lt); font-weight: 600; }
        .action-btns { display: flex; gap: 6px; }
        .btn-icon {
            width: 32px; height: 32px; border-radius: 8px;
            border: 1px solid rgba(255,255,255,0.12);
            background: transparent; color: rgba(255,255,255,0.45);
            cursor: pointer; display: flex; align-items: center; justify-content: center;
            transition: 0.15s;
        }
        .btn-icon .material-icons { font-size: 15px; }
        .btn-icon.edit:hover { border-color: var(--caramel); color: var(--caramel); background: rgba(212,130,74,0.1); }
        .btn-icon.del:hover { border-color: #e05050; color: #e05050; background: rgba(224,80,80,0.1); }
        .empty-state { text-align: center; padding: 50px; color: rgba(255,255,255,0.18); font-size: 13px; letter-spacing: 1px; }

        /* MODAL */
        .modal {
            background: rgba(28, 12, 5, 0.98) !important;
            border: 1px solid var(--glass-border) !important;
            border-radius: 18px !important;
            max-width: 500px !important;
            color: white;
        }
        .modal-title {
            font-family: 'Cinzel', serif; font-size: 1.1rem; font-weight: 700;
            color: var(--cream); letter-spacing: 1.5px; margin-bottom: 20px;
        }
        .modal-field-label {
            display: block; font-size: 11px; font-weight: 600;
            letter-spacing: 2px; text-transform: uppercase;
            color: var(--caramel); margin-bottom: 6px; margin-top: 14px;
        }
        .modal-field {
            width: 100%; height: 44px;
            background: rgba(0,0,0,0.35); border: 1px solid rgba(212,130,74,0.3);
            border-radius: 10px; color: white;
            font-family: 'DM Sans', sans-serif; font-size: 14px;
            padding: 0 13px; outline: none; transition: 0.2s;
        }
        .modal-field:focus { border-color: var(--caramel); box-shadow: 0 0 0 3px rgba(212,130,74,0.1); }
        .modal-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .modal-footer-btns { display: flex; gap: 10px; justify-content: flex-end; margin-top: 24px; }
        .btn-save-modal {
            padding: 9px 22px; background: var(--mocha-light); color: white;
            border: none; border-radius: 20px; font-size: 13px; font-weight: 500;
            cursor: pointer; transition: 0.2s; letter-spacing: 0.5px;
        }
        .btn-save-modal:hover { background: var(--mocha-glow); }
        .btn-cancel-modal {
            padding: 9px 22px; background: transparent; color: rgba(255,255,255,0.45);
            border: 1px solid rgba(255,255,255,0.15); border-radius: 20px;
            font-size: 13px; cursor: pointer; transition: 0.2s;
        }
        .btn-cancel-modal:hover { color: white; border-color: rgba(255,255,255,0.3); }

        @media (max-width: 768px) {
            .top-bar { padding: 14px 16px; }
            .page-wrap { padding: 16px 12px 0; }
            .modal-row { grid-template-columns: 1fr; }
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
            <div class="brand-user"><?= strtoupper($user['firstName'] ?? 'User') ?> &nbsp;·&nbsp; <?= $isAdmin ? 'Admin' : 'Player' ?></div>
        </div>
    </div>
    <div class="nav-actions">
        <a href="dashboardpage.php" class="nav-link">Dashboard</a>
        <?php if($isAdmin): ?><a href="logs.php" class="nav-link">Logs</a><?php endif; ?>
        <a href="logout.php" class="btn-logout">Logout</a>
    </div>
</div>

<div class="page-wrap">
    <div class="page-header">
        <div>
            <a href="dashboardpage.php" class="back-btn"><i class="material-icons">arrow_back</i> Back</a>
            <div class="page-title">Player Registry</div>
            <div class="page-sub">Manage Tournament Participants</div>
        </div>
        <a href="registrationpage.php" class="btn-add"><i class="material-icons">add</i> New Player</a>
    </div>

    <div class="glass-card">
        <table class="players-table">
            <thead>
                <tr>
                    <th>ID</th><th>Full Name</th><th>Gender</th><th>Age</th><th>Rating</th>
                    <?php if($isAdmin): ?><th>Actions</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($allPlayers)): ?>
                <tr><td colspan="6" class="empty-state">No players registered yet.</td></tr>
                <?php else: ?>
                <?php foreach($allPlayers as $p): ?>
                <tr>
                    <td><span class="id-badge">#<?= $p['userID'] ?></span></td>
                    <td class="name-cell"><?= strtoupper($p['firstName'] . ' ' . $p['lastName']) ?></td>
                    <td><?= $p['gender'] ?></td>
                    <td><?= $p['age'] ?></td>
                    <td><span class="rating-val"><?= $p['rating'] ?></span></td>
                    <?php if($isAdmin): ?>
                    <td>
                        <div class="action-btns">
                            <button class="btn-icon edit" title="Edit"
                                onclick="openEditModal('<?=$p['userID']?>','<?=$p['firstName']?>','<?=$p['lastName']?>','<?=$p['age']?>','<?=$p['rating']?>')">
                                <i class="material-icons">edit</i>
                            </button>
                            <button class="btn-icon del" title="Delete" onclick="deleteFunc(<?=$p['userID']?>)">
                                <i class="material-icons">delete</i>
                            </button>
                        </div>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- EDIT MODAL -->
<div id="modalEdit" class="modal">
    <div class="modal-content">
        <div class="modal-title">Update Player</div>
        <input type="hidden" id="editID">
        <div class="modal-row">
            <div>
                <label class="modal-field-label">First Name</label>
                <input id="editFName" type="text" class="modal-field"
                       oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '').slice(0,50);">
            </div>
            <div>
                <label class="modal-field-label">Last Name</label>
                <input id="editLName" type="text" class="modal-field"
                       oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '').slice(0,50);">
            </div>
            <div>
                <label class="modal-field-label">Age</label>
                <input id="editAge" type="number" class="modal-field"
                       oninput="if(this.value.length > 2) this.value = this.value.slice(0,2);">
            </div>
            <div>
                <label class="modal-field-label">FIDE Rating</label>
                <input id="editRating" type="number" class="modal-field"
                       oninput="if(this.value.length > 4) this.value = this.value.slice(0,4);">
            </div>
        </div>
        <div class="modal-footer-btns">
            <button class="btn-cancel-modal modal-close">Cancel</button>
            <button class="btn-save-modal" onclick="updateFunc()">Save Changes</button>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../script/service.js"></script>
<script>
    $(document).ready(function(){ $('.modal').modal(); });
    function openEditModal(id, fn, ln, ag, rt) {
        $('#editID').val(id); $('#editFName').val(fn); $('#editLName').val(ln);
        $('#editAge').val(ag); $('#editRating').val(rt);
        $('#modalEdit').modal('open');
    }
</script>
</body>
</html>
