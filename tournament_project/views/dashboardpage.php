<?php
session_start();
if (!isset($_SESSION['user'])) { header("Location: loginpage.php"); exit(); }
require_once "../bl/usermanager.php";

$manager = new usermanager();
$currentRound = isset($_GET['round']) ? (int)$_GET['round'] : 1; 

$user = $_SESSION['user'];
$isAdmin = (isset($user['role']) && $user['role'] == 'Admin') || (strtolower($user['firstName'] ?? '') == 'faith');

// Data retrieval
$adminData = $manager->getAdminDashboardStats($currentRound);
$standings = $manager->getLeaderboards() ?? []; 
$pairings = $manager->getPairings($currentRound) ?? []; 

$nextRoundToGenerate = 1;
if (!empty($standings)) {
    $db = (new Database())->connectDB();
    $stmt = $db->query("SELECT MAX(round_num) as max_r FROM tbl_pairing");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && $row['max_r']) {
        $nextRoundToGenerate = $row['max_r'] + 1;
    }
}

// Chart Data (Top 5)
$chartLabels = [];
$chartData = [];
foreach(array_slice($standings, 0, 5) as $s) {
    $chartLabels[] = strtoupper($s['firstName']);
    $chartData[] = (float)$s['total_pts'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard | Miffy Chess Cup</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <style>
        :root {
            --mocha:        #6B3A2A;
            --mocha-mid:    #8B4A2F;
            --mocha-light:  #B5622A;
            --mocha-glow:   #D4824A;
            --caramel:      #E8A96A;
            --caramel-lt:   #F5C98A;
            --cream:        #FAF0DC;
            --gold:         #D4A843;
            --gold-lt:      #F0C86A;
            --glass-bg:     rgba(30, 14, 8, 0.58);
            --glass-border: rgba(212, 130, 74, 0.26);
            --glass-hover:  rgba(212, 130, 74, 0.08);
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'DM Sans', sans-serif;
            margin: 0;
            padding-bottom: 60px;
            background:
                linear-gradient(rgba(18, 8, 4, 0.88), rgba(18, 8, 4, 0.88)),
                url('https://i.pinimg.com/736x/64/de/51/64de5126d1398692e1c52a44f1e8ced0.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh;
            color: white;
        }

        /* ── HEADER ─────────────────────────────── */
        .top-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 22px 32px;
            border-bottom: 1px solid var(--glass-border);
            background: rgba(18, 8, 4, 0.72);
            backdrop-filter: blur(14px);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .brand-row { display: flex; align-items: center; gap: 14px; }
        .brand-logo {
            width: 58px; height: 58px; border-radius: 50%;
            background: white;
            border: 2.5px solid var(--caramel);
            object-fit: contain;
            padding: 4px;
        }
        .brand-name {
            font-family: 'Cinzel', serif;
            font-size: 1.55rem;
            font-weight: 700;
            color: var(--cream);
            letter-spacing: 2px;
            line-height: 1.15;
        }
        .brand-user {
            font-size: 12px;
            color: var(--caramel);
            letter-spacing: 2.5px;
            text-transform: uppercase;
            font-weight: 500;
        }
        .nav-actions { display: flex; align-items: center; gap: 10px; }
        .nav-link {
            font-size: 12px;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: rgba(255,255,255,0.45);
            text-decoration: none;
            transition: color 0.2s;
        }
        .nav-link:hover { color: var(--caramel); }
        .btn-logout {
            padding: 7px 18px;
            border: 1px solid var(--caramel);
            color: var(--caramel);
            background: transparent;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            letter-spacing: 1px;
            text-decoration: none;
            transition: 0.2s;
            cursor: pointer;
        }
        .btn-logout:hover { background: rgba(212,130,74,0.12); color: var(--caramel); }

        /* ── LAYOUT ──────────────────────────────── */
        .page-wrap { padding: 28px 20px 0; max-width: 1100px; margin: 0 auto; }

        /* ── STAT CARDS ──────────────────────────── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: rgba(107, 58, 42, 0.28);
            border: 1px solid var(--glass-border);
            border-radius: 14px;
            padding: 20px 16px;
            text-align: center;
            backdrop-filter: blur(10px);
            transition: border-color 0.2s;
        }
        .stat-card:hover { border-color: rgba(212,130,74,0.5); }
        .stat-num {
            font-family: 'Cinzel', serif;
            font-size: 2.4rem;
            font-weight: 600;
            color: var(--caramel);
            line-height: 1;
            margin-bottom: 8px;
        }
        .stat-divider {
            width: 24px; height: 1px;
            background: rgba(212,130,74,0.38);
            margin: 0 auto 8px;
        }
        .stat-lbl {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 2.5px;
            text-transform: uppercase;
            color: rgba(255,255,255,0.45);
        }

        /* ── MAIN GRID ───────────────────────────── */
        .main-grid {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 16px;
            align-items: start;
        }

        /* ── GLASS CARD ──────────────────────────── */
        .glass-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 22px 20px;
            backdrop-filter: blur(12px);
            margin-bottom: 16px;
        }
        .card-title {
            font-family: 'Cinzel', serif;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: var(--caramel);
            margin-bottom: 16px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(212,130,74,0.18);
        }

        /* ── STANDINGS TABLE ─────────────────────── */
        .stand-table { width: 100%; border-collapse: collapse; }
        .stand-table tr { border-bottom: 1px solid rgba(255,255,255,0.05); }
        .stand-table tr:last-child { border-bottom: none; }
        .stand-table tr:hover td { background: var(--glass-hover); }
        .stand-table td { padding: 8px 4px; font-size: 13px; }
        .rank-num {
            font-family: 'Cinzel', serif;
            font-weight: 600;
            color: var(--caramel);
            width: 24px;
        }
        .rank-name { color: rgba(255,255,255,0.85); padding: 0 8px; }
        .rank-pts {
            text-align: right;
            font-size: 12px;
            color: var(--gold-lt);
            background: rgba(212,168,67,0.12);
            padding: 2px 8px;
            border-radius: 10px;
            font-weight: 500;
            white-space: nowrap;
        }

        /* ── ROUND PILLS ─────────────────────────── */
        .round-row {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
            margin-bottom: 18px;
        }
        .round-pill {
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 500;
            letter-spacing: 0.5px;
            border: 1px solid rgba(212,130,74,0.22);
            color: rgba(255,255,255,0.38);
            background: transparent;
            text-decoration: none;
            transition: 0.15s;
        }
        .round-pill:hover { color: rgba(255,255,255,0.75); border-color: rgba(212,130,74,0.45); }
        .round-pill.active {
            background: var(--mocha-light);
            color: white;
            border-color: var(--mocha-light);
        }

        /* ── PAIRING TABLE ───────────────────────── */
        .pairing-head th {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--caramel);
            padding: 0 8px 12px;
            border-bottom: 1px solid rgba(212,130,74,0.18);
        }
        .pairing-row td { padding: 13px 8px; border-bottom: 1px solid rgba(255,255,255,0.04); }
        .pairing-row:last-child td { border-bottom: none; }
        .pairing-row:hover td { background: var(--glass-hover); }
        .player-cell {
            font-weight: 500;
            font-size: 13px;
            color: rgba(255,255,255,0.85);
        }
        .result-cell { text-align: center; }

        /* ── SCORE INPUTS ────────────────────────── */
        .score-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .score-input {
            width: 42px !important;
            height: 32px !important;
            background: rgba(0,0,0,0.35) !important;
            border: 1px solid rgba(212,130,74,0.38) !important;
            color: white !important;
            text-align: center;
            border-radius: 7px !important;
            font-size: 14px;
            font-weight: 500;
            margin: 0 !important;
            box-shadow: none !important;
        }
        .score-input:focus {
            border-color: var(--caramel) !important;
            box-shadow: 0 0 0 2px rgba(212,130,74,0.15) !important;
            outline: none;
        }
        .score-sep { color: rgba(212,130,74,0.55); font-size: 12px; }
        .score-display {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--caramel);
            letter-spacing: 2px;
        }

        /* ── SAVE BUTTON ─────────────────────────── */
        .btn-save {
            width: 30px; height: 30px;
            border-radius: 50%;
            background: var(--mocha-light);
            border: none;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: 0.2s;
            flex-shrink: 0;
        }
        .btn-save:hover { background: var(--caramel); transform: scale(1.08); }
        .btn-save .material-icons { font-size: 15px; }

        /* ── ACTION BUTTONS ──────────────────────── */
        .pairing-actions { display: flex; gap: 8px; align-items: center; }
        .btn-generate {
            display: flex; align-items: center; gap: 5px;
            padding: 7px 16px;
            background: var(--mocha-light);
            color: white; border: none; border-radius: 20px;
            font-size: 12px; font-weight: 500; letter-spacing: 0.5px;
            cursor: pointer; transition: 0.2s;
        }
        .btn-generate:hover { background: var(--mocha-glow); }
        .btn-reset {
            display: flex; align-items: center; gap: 5px;
            padding: 7px 16px;
            background: transparent;
            color: rgba(220,80,80,0.8);
            border: 1px solid rgba(220,80,80,0.32);
            border-radius: 20px;
            font-size: 12px; font-weight: 500;
            cursor: pointer; transition: 0.2s;
        }
        .btn-reset:hover { background: rgba(220,80,80,0.1); }
        .btn-generate .material-icons,
        .btn-reset .material-icons { font-size: 14px; }

        /* ── NO MATCHES ──────────────────────────── */
        .no-matches {
            text-align: center;
            padding: 50px 20px;
            color: rgba(255,255,255,0.18);
            font-size: 13px;
            letter-spacing: 1px;
        }

        /* ── CHART ───────────────────────────────── */
        .chart-title {
            font-family: 'Cinzel', serif;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: var(--caramel);
            text-align: center;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .main-grid { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: 1fr; }
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
            <div class="brand-user">
                <?= strtoupper($user['firstName'] ?? 'User') ?> &nbsp;·&nbsp; <?= $isAdmin ? 'Admin' : 'Player' ?>
            </div>
        </div>
    </div>
    <div class="nav-actions">
        <a href="playerspage.php" class="nav-link">Players</a>
        <?php if($isAdmin): ?>
        <a href="logs.php" class="nav-link">Logs</a>
        <?php endif; ?>
        <a href="logout.php" class="btn-logout">Logout</a>
    </div>
</div>

<div class="page-wrap">

    <!-- STAT CARDS -->
    <?php if($isAdmin): ?>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-num"><?= $adminData['registered_players'] ?></div>
            <div class="stat-divider"></div>
            <div class="stat-lbl">Registered Players</div>
        </div>
        <div class="stat-card">
            <div class="stat-num"><?= $adminData['unfinished_matches'] ?></div>
            <div class="stat-divider"></div>
            <div class="stat-lbl">Unfinished Matches</div>
        </div>
        <div class="stat-card">
            <div class="stat-num"><?= $adminData['completed_rounds'] ?></div>
            <div class="stat-divider"></div>
            <div class="stat-lbl">Rounds Completed</div>
        </div>
    </div>
    <?php endif; ?>

    <!-- MAIN GRID -->
    <div class="main-grid">

        <!-- LEFT: Standings + Pie -->
        <div>
            <div class="glass-card">
                <div class="card-title">Standings</div>
                <table class="stand-table">
                    <tbody>
                        <?php $rank = 1; foreach($standings as $s): ?>
                        <tr>
                            <td class="rank-num"><?= $rank++ ?></td>
                            <td class="rank-name"><?= strtoupper($s['firstName']) ?></td>
                            <td style="text-align:right;"><span class="rank-pts"><?= $s['total_pts'] ?> pts</span></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($standings)): ?>
                        <tr><td colspan="3" style="text-align:center;padding:30px;color:rgba(255,255,255,0.2);font-size:12px;letter-spacing:1px;">No standings yet</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if($isAdmin): ?>
            <div class="glass-card">
                <div class="card-title">Player Ratio</div>
                <div style="position:relative; height:220px;">
                    <canvas id="ratioChart"></canvas>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- RIGHT: Pairings + Bar -->
        <div>
            <div class="glass-card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                    <div class="card-title" style="margin-bottom:0; border:none; padding-bottom:0;">Pairing — Round <?= $currentRound ?></div>
                    <?php if($isAdmin): ?>
                    <div class="pairing-actions">
                        <button onclick="generatePairs(<?= $nextRoundToGenerate ?>)" class="btn-generate">
                            <i class="material-icons">bolt</i> Generate
                        </button>
                        <button onclick="resetTournament()" class="btn-reset">
                            <i class="material-icons">history</i> Reset
                        </button>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="round-row">
                    <?php for($r = 1; $r <= 7; $r++): ?>
                    <a href="?round=<?= $r ?>" class="round-pill <?= $currentRound == $r ? 'active' : '' ?>">R<?= $r ?></a>
                    <?php endfor; ?>
                </div>

                <table style="width:100%; border-collapse:collapse;">
                    <thead class="pairing-head">
                        <tr>
                            <th style="text-align:left;">White</th>
                            <th style="text-align:center;">Result</th>
                            <th style="text-align:right;">Black</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($pairings)): ?>
                        <tr><td colspan="3" class="no-matches">No matches for Round <?= $currentRound ?>.</td></tr>
                        <?php else: ?>
                        <?php foreach($pairings as $m): ?>
                        <tr class="pairing-row">
                            <td class="player-cell"><?= strtoupper($m['p1Name']) ?></td>
                            <td class="result-cell">
                                <?php if($isAdmin): ?>
                                <div class="score-wrap">
                                    <input type="number" id="score1_<?= (int)$m['match_id'] ?>" value="<?= $m['p1_score'] ?>" class="score-input" min="0" max="1" step="0.5">
                                    <span class="score-sep">—</span>
                                    <input type="number" id="score2_<?= (int)$m['match_id'] ?>" value="<?= $m['p2_score'] ?>" class="score-input" min="0" max="1" step="0.5">
                                    <button onclick="updateScore(<?= (int)$m['match_id'] ?>)" class="btn-save"><i class="material-icons">save</i></button>
                                </div>
                                <?php else: ?>
                                <span class="score-display"><?= $m['p1_score'] ?> – <?= $m['p2_score'] ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="player-cell" style="text-align:right;"><?= strtoupper($m['p2Name']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if($isAdmin): ?>
            <div class="glass-card">
                <div class="chart-title">Top 5 Progress</div>
                <div style="position:relative; height:300px;">
                    <canvas id="performanceChart"></canvas>
                </div>
            </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../script/service.js"></script>

<script>
Chart.register(ChartDataLabels);

document.addEventListener('DOMContentLoaded', function() {
    <?php if($isAdmin): ?>

    // Donut: Player Ratio
    const ratioCtx = document.getElementById('ratioChart').getContext('2d');
    const maleCount  = <?= (int)($adminData['male_count']   ?? 0) ?>;
    const femaleCount = <?= (int)($adminData['female_count'] ?? 0) ?>;
    new Chart(ratioCtx, {
        type: 'doughnut',
        data: {
            labels: ['Male', 'Female'],
            datasets: [{
                data: [maleCount, femaleCount],
                backgroundColor: ['#D4824A', '#F0C86A'],
                borderColor: ['#1C0A04', '#1C0A04'],
                borderWidth: 3,
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '58%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: '#FFFFFF',
                        padding: 20,
                        font: { size: 14, weight: '600' },
                        usePointStyle: true,
                        pointStyleWidth: 12,
                        boxHeight: 12,
                        generateLabels: (chart) => {
                            const data = chart.data;
                            const total = data.datasets[0].data.reduce((a,b) => a+b, 0);
                            return data.labels.map((label, i) => {
                                const val = data.datasets[0].data[i];
                                const pct = total === 0 ? '0' : (val * 100 / total).toFixed(0);
                                return {
                                    text: label + ':  ' + val + '  (' + pct + '%)',
                                    fillStyle: data.datasets[0].backgroundColor[i],
                                    strokeStyle: 'transparent',
                                    pointStyle: 'circle',
                                    fontColor: '#FFFFFF',
                                    hidden: false,
                                    index: i
                                };
                            });
                        }
                    }
                },
                datalabels: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(28,10,4,0.95)',
                    titleColor: '#E8A96A',
                    bodyColor: '#FAF0DC',
                    borderColor: 'rgba(212,130,74,0.3)',
                    borderWidth: 1,
                    padding: 12,
                    callbacks: {
                        label: (ctx) => {
                            let sum = ctx.chart.data.datasets[0].data.reduce((a,b)=>a+b,0);
                            let pct = sum === 0 ? 0 : (ctx.parsed * 100 / sum).toFixed(1);
                            return '  ' + ctx.label + ': ' + ctx.parsed + ' (' + pct + '%)';
                        }
                    }
                }
            }
        }
    });

    // Bar: Top 5 Progress
    const perfCtx = document.getElementById('performanceChart').getContext('2d');
    new Chart(perfCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($chartLabels) ?>,
            datasets: [{
                label: 'Total Points',
                data: <?= json_encode($chartData) ?>,
                backgroundColor: 'rgba(181, 98, 42, 0.75)',
                borderColor: '#D4824A',
                borderWidth: 1,
                borderRadius: 8,
                barThickness: 48
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(255,255,255,0.04)' },
                    ticks: { color: 'rgba(255,255,255,0.4)', font: { size: 12 } }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: 'rgba(255,255,255,0.75)', font: { size: 12, weight: '500' } }
                }
            },
            plugins: {
                legend: { display: false },
                datalabels: {
                    anchor: 'end', align: 'top',
                    color: '#E8A96A',
                    font: { weight: '600', size: 12 }
                }
            }
        }
    });

    <?php endif; ?>
});
</script>
</body>
</html>