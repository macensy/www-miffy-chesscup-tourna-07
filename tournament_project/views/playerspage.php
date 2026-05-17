<?php
session_start();
if (!isset($_SESSION['user'])) { header("Location: loginpage.php"); exit(); }
require_once "../bl/usermanager.php";
$manager    = new usermanager();
$user       = $_SESSION['user'];
$isAdmin    = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
$allPlayers = $manager->getUser();
$allAdmins  = $isAdmin ? ($manager->getAdmins() ?? []) : [];
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
            min-height: 100vh; color: white;
            position: relative; overflow-x: hidden;
        }
        #bgCanvas {
            position: fixed; top: 0; left: 0;
            width: 100%; height: 100%;
            z-index: -1;
        }
        .top-bar {
            display: flex; align-items: center; justify-content: space-between;
            padding: 22px 32px; border-bottom: 1px solid var(--glass-border);
            background: rgba(18, 8, 4, 0.72); backdrop-filter: blur(14px);
            position: sticky; top: 0; z-index: 100;
        }
        .brand-row { display: flex; align-items: center; gap: 14px; }
        .brand-logo { width: 58px; height: 58px; border-radius: 50%; background: white; border: 2.5px solid var(--caramel); object-fit: contain; padding: 4px; }
        .brand-name { font-family: 'Cinzel', serif; font-size: 1.55rem; font-weight: 700; color: var(--cream); letter-spacing: 2px; }
        .brand-user { font-size: 12px; color: var(--caramel); letter-spacing: 2.5px; text-transform: uppercase; font-weight: 500; }
        .nav-actions { display: flex; align-items: center; gap: 10px; }
        .nav-link { font-size: 12px; letter-spacing: 1.5px; text-transform: uppercase; color: rgba(255,255,255,0.45); text-decoration: none; transition: 0.2s; }
        .nav-link:hover { color: var(--caramel); }
        .btn-logout { padding: 7px 18px; border: 1px solid var(--caramel); color: var(--caramel); background: transparent; border-radius: 20px; font-size: 12px; font-weight: 500; letter-spacing: 1px; text-decoration: none; transition: 0.2s; }
        .btn-logout:hover { background: rgba(212,130,74,0.12); color: var(--caramel); }

        .page-wrap { padding: 28px 24px 0; max-width: 1100px; margin: 0 auto; }
        .page-header { display: flex; align-items: flex-end; justify-content: space-between; margin-bottom: 22px; }
        .back-btn { display: flex; align-items: center; gap: 6px; color: rgba(255,255,255,0.45); text-decoration: none; font-size: 13px; letter-spacing: 0.5px; transition: 0.2s; margin-bottom: 6px; }
        .back-btn:hover { color: var(--caramel); }
        .back-btn .material-icons { font-size: 18px; }
        .page-title { font-family: 'Cinzel', serif; font-size: 1.6rem; font-weight: 700; color: var(--cream); letter-spacing: 1.5px; }
        .page-sub { font-size: 11px; letter-spacing: 2.5px; text-transform: uppercase; color: rgba(255,255,255,0.35); margin-top: 3px; }
        .btn-add { display: flex; align-items: center; gap: 5px; padding: 9px 20px; background: var(--mocha-light); color: white; border: none; border-radius: 20px; font-size: 13px; font-weight: 500; text-decoration: none; transition: 0.2s; cursor: pointer; }
        .btn-add:hover { background: var(--mocha-glow); color: white; }
        .btn-add .material-icons { font-size: 16px; }

        .filter-bar {
            display: flex; gap: 10px; align-items: center;
            margin-bottom: 16px; flex-wrap: wrap;
        }
        .search-wrap {
            position: relative; flex: 1; min-width: 200px;
        }
        .search-wrap .material-icons {
            position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
            color: rgba(212,130,74,0.6); font-size: 18px; pointer-events: none;
        }
        .search-input {
            width: 100%; height: 40px;
            background: rgba(0,0,0,0.35);
            border: 1px solid rgba(212,130,74,0.3);
            border-radius: 20px;
            color: white; font-family: 'DM Sans', sans-serif;
            font-size: 13px; padding: 0 14px 0 38px;
            outline: none; transition: 0.2s;
        }
        .search-input::placeholder { color: rgba(255,255,255,0.25); }
        .search-input:focus { border-color: var(--caramel); box-shadow: 0 0 0 3px rgba(212,130,74,0.1); }
        .filter-select {
            height: 40px; padding: 0 14px;
            background: rgba(0,0,0,0.35);
            border: 1px solid rgba(212,130,74,0.3);
            border-radius: 20px; color: white;
            font-family: 'DM Sans', sans-serif; font-size: 13px;
            outline: none; cursor: pointer; transition: 0.2s;
            -webkit-appearance: none; appearance: none;
            padding-right: 30px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24'%3E%3Cpath fill='%23E8A96A' d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
            background-repeat: no-repeat; background-position: right 10px center;
        }
        .filter-select:focus { border-color: var(--caramel); }
        .filter-select option { background: #1C0A04; color: white; }
        .result-count {
            font-size: 12px; color: rgba(255,255,255,0.3);
            letter-spacing: 1px; white-space: nowrap; align-self: center;
        }

        .glass-card { background: var(--glass-bg); border: 1px solid var(--glass-border); border-radius: 16px; padding: 24px 20px; backdrop-filter: blur(12px); }
        .players-table { width: 100%; border-collapse: collapse; }
        .players-table th { font-size: 10px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; color: var(--caramel); padding: 0 12px 14px; text-align: left; border-bottom: 1px solid rgba(212,130,74,0.2); cursor: pointer; user-select: none; white-space: nowrap; }
        .players-table th:hover { color: var(--caramel-lt); }
        .players-table th .sort-icon { font-size: 12px; vertical-align: middle; opacity: 0.4; }
        .players-table th.sorted .sort-icon { opacity: 1; color: var(--caramel); }
        .players-table td { padding: 12px; font-size: 13px; color: rgba(255,255,255,0.75); border-bottom: 1px solid rgba(255,255,255,0.04); vertical-align: middle; }
        .players-table tr:last-child td { border-bottom: none; }
        .players-table tr:hover td { background: var(--glass-hover); }
        .id-badge { color: var(--caramel); font-family: 'Cinzel', serif; font-size: 12px; }
        .name-cell { font-weight: 500; color: rgba(255,255,255,0.9); }
        .rating-val { color: var(--gold-lt); font-weight: 600; }
        .action-btns { display: flex; gap: 6px; }
        .btn-icon { width: 32px; height: 32px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.12); background: transparent; color: rgba(255,255,255,0.45); cursor: pointer; display: flex; align-items: center; justify-content: center; transition: 0.15s; }
        .btn-icon .material-icons { font-size: 15px; }
        .btn-icon.edit:hover { border-color: var(--caramel); color: var(--caramel); background: rgba(212,130,74,0.1); }
        .btn-icon.del:hover { border-color: #e05050; color: #e05050; background: rgba(224,80,80,0.1); }
        .empty-state { text-align: center; padding: 50px; color: rgba(255,255,255,0.18); font-size: 13px; letter-spacing: 1px; }

        .modal { background: rgba(28, 12, 5, 0.98) !important; border: 1px solid var(--glass-border) !important; border-radius: 18px !important; max-width: 500px !important; color: white; }
        .modal-title { font-family: 'Cinzel', serif; font-size: 1.1rem; font-weight: 700; color: var(--cream); letter-spacing: 1.5px; margin-bottom: 20px; }
        .modal-field-label { display: block; font-size: 11px; font-weight: 600; letter-spacing: 2px; text-transform: uppercase; color: var(--caramel); margin-bottom: 6px; margin-top: 14px; }
        .modal-field { width: 100%; height: 44px; background: rgba(0,0,0,0.35); border: 1px solid rgba(212,130,74,0.3); border-radius: 10px; color: white; font-family: 'DM Sans', sans-serif; font-size: 14px; padding: 0 13px; outline: none; transition: 0.2s; }
        .modal-field:focus { border-color: var(--caramel); box-shadow: 0 0 0 3px rgba(212,130,74,0.1); }
        .modal-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .modal-footer-btns { display: flex; gap: 10px; justify-content: flex-end; margin-top: 24px; }
        .btn-save-modal { padding: 9px 22px; background: var(--mocha-light); color: white; border: none; border-radius: 20px; font-size: 13px; font-weight: 500; cursor: pointer; transition: 0.2s; }
        .btn-save-modal:hover { background: var(--mocha-glow); }
        .btn-cancel-modal { padding: 9px 22px; background: transparent; color: rgba(255,255,255,0.45); border: 1px solid rgba(255,255,255,0.15); border-radius: 20px; font-size: 13px; cursor: pointer; transition: 0.2s; }
        .btn-cancel-modal:hover { color: white; border-color: rgba(255,255,255,0.3); }

        tr.hidden-row { display: none; }
        @keyframes rowChessIn { from { opacity:0; transform:translateX(-4px); } to { opacity:1; transform:translateX(0); } }

        .tab-bar {
            display: flex; gap: 4px; margin-bottom: 18px;
            border-bottom: 1px solid rgba(212,130,74,0.15); padding-bottom: 0;
        }
        .tab-btn {
            padding: 9px 22px; border: none; background: transparent;
            font-family: 'DM Sans', sans-serif; font-size: 12px;
            font-weight: 600; letter-spacing: 2px; text-transform: uppercase;
            color: rgba(255,255,255,0.3); cursor: pointer; transition: 0.2s;
            border-bottom: 2px solid transparent; margin-bottom: -1px;
        }
        .tab-btn:hover { color: rgba(255,255,255,0.65); }
        .tab-btn.active { color: var(--caramel); border-bottom-color: var(--caramel); }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .admin-badge {
            display: inline-block; padding: 2px 10px; border-radius: 10px;
            font-size: 10px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase;
            background: rgba(212,130,74,0.15); color: var(--caramel); border: 1px solid rgba(212,130,74,0.3);
        }

        @media (max-width: 768px) {
            .top-bar { padding: 14px 16px; }
            .page-wrap { padding: 16px 12px 0; }
            .modal-row { grid-template-columns: 1fr; }
            .filter-bar { gap: 8px; }
        }
    </style>
</head>
<body>
<canvas id="bgCanvas"></canvas>
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
        <?php if($isAdmin): ?>
        <a href="analyticspage.php" class="nav-link">Analytics</a>
        <a href="logs.php" class="nav-link">Logs</a>
        <?php endif; ?>
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

    <?php if($isAdmin): ?>
    <div class="tab-bar">
        <button class="tab-btn active" onclick="switchTab('players', this)">
            ♟ Players <span id="playerCountBadge" style="font-size:10px;opacity:0.6;margin-left:4px;">(<?= count($allPlayers) ?>)</span>
        </button>
        <button class="tab-btn" onclick="switchTab('admins', this)">
            ⚔ Admins <span style="font-size:10px;opacity:0.6;margin-left:4px;">(<?= count($allAdmins) ?>)</span>
        </button>
    </div>
    <?php endif; ?>

    <div id="tab-players" class="tab-content active">

    <div class="filter-bar">
        <div class="search-wrap">
            <i class="material-icons">search</i>
            <input type="text" id="searchInput" class="search-input" placeholder="Search by name or ID…" oninput="applyFilters()">
        </div>
        <select id="genderFilter" class="filter-select" onchange="applyFilters()">
            <option value="">All Genders</option>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
        </select>
        <select id="ratingFilter" class="filter-select" onchange="applyFilters()">
            <option value="">All Ratings</option>
            <option value="2000">2000+</option>
            <option value="1800">1800–1999</option>
            <option value="1600">1600–1799</option>
            <option value="0">Under 1600</option>
        </select>
        <span class="result-count" id="resultCount"></span>
    </div>

    <div class="glass-card">
        <table class="players-table" id="playersTable">
            <thead>
                <tr>
                    <th onclick="sortTable(0)" data-col="0">#<i class="material-icons sort-icon">unfold_more</i></th>
                    <th onclick="sortTable(1)" data-col="1">Full Name<i class="material-icons sort-icon">unfold_more</i></th>
                    <th onclick="sortTable(2)" data-col="2">Gender<i class="material-icons sort-icon">unfold_more</i></th>
                    <th onclick="sortTable(3)" data-col="3">Age<i class="material-icons sort-icon">unfold_more</i></th>
                    <th onclick="sortTable(4)" data-col="4">Rating<i class="material-icons sort-icon">unfold_more</i></th>
                    <?php if($isAdmin): ?><th>Actions</th><?php endif; ?>
                </tr>
            </thead>
            <tbody id="playersBody">
                <?php if(empty($allPlayers)): ?>
                <tr><td colspan="6" class="empty-state">No players registered yet.</td></tr>
                <?php else: ?>
                <?php foreach($allPlayers as $p): ?>
                <tr data-name="<?= strtolower($p['firstName'].' '.$p['lastName']) ?>"
                    data-id="<?= $p['userID'] ?>"
                    data-gender="<?= $p['gender'] ?>"
                    data-rating="<?= $p['rating'] ?>">
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

    <?php if($isAdmin): ?>
    <div id="tab-admins" class="tab-content">
    <div class="glass-card">
        <table class="players-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Role</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($allAdmins)): ?>
                <tr><td colspan="4" class="empty-state">No admins found.</td></tr>
                <?php else: ?>
                <?php foreach($allAdmins as $a): ?>
                <tr>
                    <td><span class="id-badge">#<?= $a['adminID'] ?></span></td>
                    <td class="name-cell"><?= strtoupper($a['firstName'] . ' ' . $a['lastName']) ?></td>
                    <td style="color:rgba(255,255,255,0.5); font-size:12px;"><?= htmlspecialchars($a['email'] ?? '—') ?></td>
                    <td><span class="admin-badge"><?= 'Admin' ?></span></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    </div>
    <?php endif; ?>
</div>

<div id="modalEdit" class="modal">
    <div class="modal-content">
        <div class="modal-title">Update Player</div>
        <input type="hidden" id="editID">
        <div class="modal-row">
            <div>
                <label class="modal-field-label">First Name</label>
                <input id="editFName" type="text" class="modal-field" minlength="2" maxlength="50" oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '').slice(0,50);">
            </div>
            <div>
                <label class="modal-field-label">Last Name</label>
                <input id="editLName" type="text" class="modal-field" minlength="2" maxlength="50" oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '').slice(0,50);">
            </div>
            <div>
                <label class="modal-field-label">Age</label>
                <input id="editAge" type="number" class="modal-field" oninput="if(this.value.length > 2) this.value = this.value.slice(0,2);">
            </div>
            <div>
                <label class="modal-field-label">FIDE Rating</label>
                <input id="editRating" type="number" class="modal-field" oninput="if(this.value.length > 4) this.value = this.value.slice(0,4);">
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

function switchTab(name, btn) {

    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    btn.classList.add('active');
}

function openEditModal(id, fn, ln, ag, rt) {
    $('#editID').val(id); $('#editFName').val(fn); $('#editLName').val(ln);
    $('#editAge').val(ag); $('#editRating').val(rt);
    $('#modalEdit').modal('open');
}

function applyFilters() {
    const q      = document.getElementById('searchInput').value.toLowerCase().trim();
    const gender = document.getElementById('genderFilter').value;
    const rating = document.getElementById('ratingFilter').value;
    const rows   = document.querySelectorAll('#playersBody tr[data-name]');
    let visible  = 0;

    rows.forEach(row => {
        const name   = row.dataset.name;
        const id     = row.dataset.id;
        const g      = row.dataset.gender;
        const r      = parseInt(row.dataset.rating);

        const matchQ = !q || name.includes(q) || id.includes(q);
        const matchG = !gender || g === gender;
        let   matchR = true;
        if (rating === '2000')      matchR = r >= 2000;
        else if (rating === '1800') matchR = r >= 1800 && r < 2000;
        else if (rating === '1600') matchR = r >= 1600 && r < 1800;
        else if (rating === '0')    matchR = r < 1600;

        if (matchQ && matchG && matchR) {
            row.classList.remove('hidden-row'); visible++;
        } else {
            row.classList.add('hidden-row');
        }
    });

    const total = rows.length;
    const countEl = document.getElementById('resultCount');
    countEl.textContent = q || gender || rating
        ? `${visible} of ${total} player${total !== 1 ? 's' : ''}`
        : `${total} player${total !== 1 ? 's' : ''}`;
}

let sortDir = {};
function sortTable(colIdx) {
    const tbody = document.getElementById('playersBody');
    const rows  = Array.from(tbody.querySelectorAll('tr[data-name]'));
    sortDir[colIdx] = !sortDir[colIdx];
    const asc = sortDir[colIdx];

    document.querySelectorAll('.players-table th').forEach((th, i) => {
        th.classList.toggle('sorted', i === colIdx);
        const icon = th.querySelector('.sort-icon');
        if (icon) icon.textContent = i === colIdx ? (asc ? 'arrow_upward' : 'arrow_downward') : 'unfold_more';
    });

    rows.sort((a, b) => {
        const aVal = a.cells[colIdx]?.textContent.trim() || '';
        const bVal = b.cells[colIdx]?.textContent.trim() || '';
        const aNum = parseFloat(aVal.replace(/[^0-9.]/g,''));
        const bNum = parseFloat(bVal.replace(/[^0-9.]/g,''));
        if (!isNaN(aNum) && !isNaN(bNum)) return asc ? aNum - bNum : bNum - aNum;
        return asc ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
    });

    rows.forEach(r => tbody.appendChild(r));
    applyFilters();
}

applyFilters();

document.querySelectorAll('.players-table tbody tr[data-name]').forEach(row => {
    row.addEventListener('mouseenter', () => {
        row.style.transition = 'background 0.18s';
        const nameCell = row.querySelector('.name-cell');
        if (nameCell && !nameCell.querySelector('.row-chess')) {
            const icon = document.createElement('span');
            icon.className = 'row-chess';
            icon.textContent = ' ♟';
            icon.style.cssText = 'color:rgba(212,130,74,0.45);font-size:11px;animation:rowChessIn 0.3s ease;';
            nameCell.appendChild(icon);
        }
    });
    row.addEventListener('mouseleave', () => {
        const icon = row.querySelector('.row-chess');
        if (icon) icon.remove();
    });
});

function countUp(el, target, duration = 900) {
    let start = 0, step = target / (duration / 16);
    const tick = () => {
        start = Math.min(start + step, target);
        el.textContent = Math.floor(start);
        if (start < target) requestAnimationFrame(tick);
    };
    tick();
}
document.querySelectorAll('#playerCountBadge').forEach(el => {
    const n = parseInt(el.textContent.replace(/\D/g,''));
    if (!isNaN(n)) { el.textContent = '(0)'; setTimeout(() => countUp({ textContent: '' , set textContent(v){ el.textContent = '(' + v + ')'; } }, n), 300); }
});

document.querySelectorAll('.players-table tbody tr[data-name]').forEach(row => {
    const ratingVal = parseInt(row.dataset.rating) || 0;
    const ratingCell = row.querySelector('.rating-val');
    if (ratingCell) {
        const pct = Math.min(ratingVal / 3000 * 100, 100);
        const bar = document.createElement('div');
        bar.style.cssText = `height:2px;border-radius:2px;margin-top:3px;width:0%;
            background:linear-gradient(90deg,#B5622A,#F0C86A);transition:width 0.8s ease;`;
        ratingCell.parentNode.style.position = 'relative';
        ratingCell.after(bar);
        setTimeout(() => bar.style.width = pct + '%', 200 + Math.random()*300);
    }
});
</script>
<script>
(function(){
    const canvas=document.getElementById('bgCanvas'),ctx=canvas.getContext('2d');
    const pieces=['♟','♜','♞','♝','♛','♚'];
    let particles=[],W,H,time=0;
    function resize(){W=canvas.width=window.innerWidth;H=canvas.height=window.innerHeight;}
    resize();window.addEventListener('resize',resize);
    for(let i=0;i<22;i++)particles.push({x:Math.random()*1400,y:Math.random()*900,symbol:pieces[Math.floor(Math.random()*6)],size:14+Math.random()*24,speed:0.13+Math.random()*0.27,drift:(Math.random()-.5)*.18,alpha:.025+Math.random()*.06,rot:Math.random()*Math.PI*2,rotSpeed:(Math.random()-.5)*.005});
    function draw(){
        time+=.004;
        const g=ctx.createRadialGradient(W*.5,H*.4,0,W*.5,H*.4,W*.85);
        g.addColorStop(0,'rgba(38,17,7,.97)');g.addColorStop(.55,'rgba(20,8,3,.98)');g.addColorStop(1,'rgba(10,3,1,1)');
        ctx.fillStyle=g;ctx.fillRect(0,0,W,H);
        [{x:W*(.2+.08*Math.sin(time*.7)),y:H*(.25+.07*Math.cos(time*.5)),r:W*.3,c:`rgba(107,58,42,${.05+.02*Math.sin(time)})`},
         {x:W*(.82+.06*Math.cos(time*.6)),y:H*(.65+.08*Math.sin(time*.4)),r:W*.25,c:`rgba(181,98,42,${.045+.015*Math.cos(time*1.2)})`}]
        .forEach(o=>{const og=ctx.createRadialGradient(o.x,o.y,0,o.x,o.y,o.r);og.addColorStop(0,o.c);og.addColorStop(1,'transparent');ctx.fillStyle=og;ctx.fillRect(0,0,W,H);});
        ctx.textBaseline='middle';
        particles.forEach(p=>{
            ctx.save();ctx.translate(p.x%W,p.y%H);ctx.rotate(p.rot);ctx.globalAlpha=p.alpha;ctx.fillStyle='#E8A96A';ctx.font=`${p.size}px serif`;ctx.fillText(p.symbol,0,0);ctx.restore();
            p.y-=p.speed;p.x+=p.drift;p.rot+=p.rotSpeed;
            if(p.y<-60){p.y=H+60;p.x=Math.random()*W;}
            if(p.x<-60)p.x=W+60;if(p.x>W+60)p.x=-60;
        });
        requestAnimationFrame(draw);
    }
    draw();
})();
</script>
<script src="../script/fx.js"></script>
</body>
</html>
