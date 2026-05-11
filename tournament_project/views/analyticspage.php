<?php
session_start();
if (!isset($_SESSION['user'])) { header("Location: loginpage.php"); exit(); }
require_once "../bl/usermanager.php";
$manager  = new usermanager();
$user     = $_SESSION['user'];
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
if (!$isAdmin) { header("Location: dashboardpage.php"); exit(); }
$db = (new Database())->connectDB();

$totalPlayers  = (int)$db->query("SELECT COUNT(*) FROM tbl_players")->fetchColumn();
$maleCount     = (int)$db->query("SELECT COUNT(*) FROM tbl_players WHERE gender='Male'")->fetchColumn();
$femaleCount   = (int)$db->query("SELECT COUNT(*) FROM tbl_players WHERE gender='Female'")->fetchColumn();
$completedRnds = (int)$db->query("SELECT COUNT(DISTINCT round_num) FROM tbl_pairing WHERE round_num NOT IN (SELECT DISTINCT round_num FROM tbl_pairing WHERE status != 'FINISHED')")->fetchColumn();
$totalMatches  = (int)$db->query("SELECT COUNT(*) FROM tbl_pairing WHERE status = 'FINISHED'")->fetchColumn();

$standings = $db->query("
    SELECT p.userID, p.firstName, p.lastName, p.gender, p.age, p.rating,
        IFNULL((SELECT SUM(p1_score) FROM tbl_pairing WHERE player1_id = p.userID), 0) +
        IFNULL((SELECT SUM(p2_score) FROM tbl_pairing WHERE player2_id = p.userID), 0) AS total_pts,
        (SELECT COUNT(*) FROM tbl_pairing WHERE (player1_id = p.userID OR player2_id = p.userID) AND status = 'FINISHED') AS games_played,
        (SELECT COUNT(*) FROM tbl_pairing WHERE winner_id = p.userID) AS wins
    FROM tbl_players p
    ORDER BY total_pts DESC, p.rating DESC
")->fetchAll(PDO::FETCH_ASSOC);

$top5ids = array_slice(array_column($standings, 'userID'), 0, 5);
$top5names = array_slice(array_map(fn($s) => strtoupper($s['firstName']), $standings), 0, 5);
$roundPts = []; // [round][playerIdx] = pts
for ($r = 1; $r <= 7; $r++) {
    $roundPts[$r] = [];
    foreach ($top5ids as $pid) {
        $stmt = $db->prepare("
            SELECT IFNULL(SUM(CASE WHEN player1_id = ? THEN p1_score WHEN player2_id = ? THEN p2_score ELSE 0 END), 0)
            FROM tbl_pairing WHERE round_num = ? AND status = 'FINISHED'
        ");
        $stmt->execute([$pid, $pid, $r]);
        $roundPts[$r][] = (float)$stmt->fetchColumn();
    }
}
// ── Cumulative pts per round for line chart ───────────────
$cumData = array_fill(0, count($top5ids), []); // [playerIdx][round] = cumulative
for ($i = 0; $i < count($top5ids); $i++) {
    $cum = 0;
    for ($r = 1; $r <= 7; $r++) {
        $cum += $roundPts[$r][$i] ?? 0;
        $cumData[$i][] = round($cum, 1);
    }
}
// ── Win rate per player ───────────────────────────────────
$winRateLabels = array_map(fn($s) => strtoupper($s['firstName']), $standings);
$winRateData   = array_map(function($s) {
    if ($s['games_played'] == 0) return 0;
    return round($s['wins'] / $s['games_played'] * 100, 1);
}, $standings);
// ── Draws & decisive games ────────────────────────────────
$decisive = (int)$db->query("SELECT COUNT(*) FROM tbl_pairing WHERE status='FINISHED' AND p1_score != p2_score")->fetchColumn();
$draws    = $totalMatches - $decisive;
// ── Age distribution ──────────────────────────────────────
$ageRows = $db->query("SELECT age FROM tbl_players")->fetchAll(PDO::FETCH_COLUMN);
$ageBuckets = ['U18' => 0, '18-25' => 0, '26-35' => 0, '36-50' => 0, '50+' => 0];
foreach ($ageRows as $age) {
    $age = (int)$age;
    if ($age < 18)       $ageBuckets['U18']++;
    elseif ($age <= 25)  $ageBuckets['18-25']++;
    elseif ($age <= 35)  $ageBuckets['26-35']++;
    elseif ($age <= 50)  $ageBuckets['36-50']++;
    else                 $ageBuckets['50+']++;
}
// ── Rating distribution ───────────────────────────────────
$ratingRows = $db->query("SELECT rating FROM tbl_players ORDER BY rating ASC")->fetchAll(PDO::FETCH_COLUMN);
$ratingLabels = array_map(fn($s) => strtoupper($s['firstName']), $standings);
$ratingData   = array_map(fn($s) => (int)$s['rating'], $standings);
// Chart palette
$palette = ['#D4824A','#F0C86A','#7B9E87','#A06B9A','#5B9BD5','#E07B7B','#8BC4C4'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics — Tournament</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
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
        /* ── HEADER ─────────────────────────── */
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
        .nav-link.active { color: var(--caramel); }
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
        }
        .btn-logout:hover { background: rgba(212,130,74,0.12); }
        /* ── LAYOUT ──────────────────────────── */
        .page-wrap { padding: 28px 20px 0; max-width: 1100px; margin: 0 auto; }
        .page-heading {
            font-family: 'Cinzel', serif;
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--cream);
            letter-spacing: 3px;
            text-transform: uppercase;
            margin-bottom: 6px;
        }
        .page-sub {
            font-size: 12px;
            color: rgba(255,255,255,0.35);
            letter-spacing: 1.5px;
            margin-bottom: 28px;
        }
        /* ── STAT CARDS ──────────────────────── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 12px;
            margin-bottom: 28px;
        }
        .stat-card {
            background: rgba(107, 58, 42, 0.28);
            border: 1px solid var(--glass-border);
            border-radius: 14px;
            padding: 18px 12px;
            text-align: center;
            backdrop-filter: blur(10px);
            transition: border-color 0.2s;
        }
        .stat-card:hover { border-color: rgba(212,130,74,0.5); }
        .stat-num {
            font-family: 'Cinzel', serif;
            font-size: 2rem;
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
            font-size: 10px;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: rgba(255,255,255,0.4);
        }
        /* ── GLASS CARDS ─────────────────────── */
        .glass-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 22px 20px;
            backdrop-filter: blur(12px);
            margin-bottom: 20px;
        }
        .card-title {
            font-family: 'Cinzel', serif;
            font-size: 11px;
            letter-spacing: 2.5px;
            text-transform: uppercase;
            color: var(--caramel);
            margin-bottom: 18px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(212,130,74,0.15);
        }
        /* ── CHART GRIDS ─────────────────────── */
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        /* ── LEADERBOARD TABLE ───────────────── */
        .lb-table { width: 100%; border-collapse: collapse; }
        .lb-table th {
            font-size: 10px;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: rgba(255,255,255,0.3);
            padding: 6px 8px;
            text-align: left;
            border-bottom: 1px solid rgba(255,255,255,0.07);
        }
        .lb-table td { padding: 9px 8px; font-size: 13px; border-bottom: 1px solid rgba(255,255,255,0.04); }
        .lb-table tr:last-child td { border-bottom: none; }
        .lb-table tr:hover td { background: var(--glass-hover); }
        .lb-rank {
            font-family: 'Cinzel', serif;
            font-weight: 600;
            color: var(--caramel);
            width: 28px;
        }
        .lb-name { color: rgba(255,255,255,0.85); font-weight: 500; }
        .lb-pts {
            font-size: 12px;
            color: var(--gold-lt);
            background: rgba(212,168,67,0.12);
            padding: 2px 8px;
            border-radius: 10px;
            white-space: nowrap;
        }
        .lb-bar-wrap { width: 120px; }
        .lb-bar-bg { background: rgba(255,255,255,0.06); border-radius: 4px; height: 6px; }
        .lb-bar-fill { height: 6px; border-radius: 4px; background: var(--mocha-glow); }
        .medal-gold   { color: #D4A843; }
        .medal-silver { color: #b0b8cc; }
        .medal-bronze { color: #c87941; }
        @media (max-width: 768px) {
            .grid-2, .grid-3, .stats-grid { grid-template-columns: 1fr; }
            .top-bar { padding: 16px; }
            .lb-bar-wrap { display: none; }
        }
    </style>
</head>
<body>
<!-- NAV -->
<div class="top-bar">
    <div class="brand-row">
        <img src="../assets/miffy.jpg" class="brand-logo" alt="logo">
        <div>
            <div class="brand-name">MIFFY CHESS</div>
            <div class="brand-user">
                <?= strtoupper($user['firstName'] ?? 'User') ?> &nbsp;·&nbsp; Admin
            </div>
        </div>
    </div>
    <div class="nav-actions">
        <a href="dashboardpage.php" class="nav-link">Dashboard</a>
        <a href="playerspage.php" class="nav-link">Players</a>
        <a href="analyticspage.php" class="nav-link active">Analytics</a>
        <a href="logs.php" class="nav-link">Logs</a>
        <a href="logout.php" class="btn-logout">Logout</a>
    </div>
</div>
<div class="page-wrap">
    <div class="page-heading">Analytics</div>
    <div class="page-sub">Tournament performance overview</div>
    <!-- STAT CARDS -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-num"><?= $totalPlayers ?></div>
            <div class="stat-divider"></div>
            <div class="stat-lbl">Players</div>
        </div>
        <div class="stat-card">
            <div class="stat-num"><?= $completedRnds ?><span style="font-size:1rem;opacity:.5;">/7</span></div>
            <div class="stat-divider"></div>
            <div class="stat-lbl">Rounds Done</div>
        </div>
        <div class="stat-card">
            <div class="stat-num"><?= $totalMatches ?></div>
            <div class="stat-divider"></div>
            <div class="stat-lbl">Matches Played</div>
        </div>
        <div class="stat-card">
            <div class="stat-num"><?= $decisive ?></div>
            <div class="stat-divider"></div>
            <div class="stat-lbl">Decisive Games</div>
        </div>
        <div class="stat-card">
            <div class="stat-num"><?= $draws ?></div>
            <div class="stat-divider"></div>
            <div class="stat-lbl">Draws</div>
        </div>
    </div>
    <!-- ROW 1: Full leaderboard + Gender donut -->
    <div class="grid-2">
        <!-- Full leaderboard table -->
        <div class="glass-card">
            <div class="card-title">Full Standings</div>
            <table class="lb-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Player</th>
                        <th>Pts</th>
                        <th>W</th>
                        <th>GP</th>
                        <th>Rating</th>
                        <th class="lb-bar-wrap"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $maxPts = !empty($standings) ? max(array_column($standings, 'total_pts')) : 1;
                    $rank = 1;
                    foreach ($standings as $s):
                        $pct = $maxPts > 0 ? ($s['total_pts'] / $maxPts * 100) : 0;
                        $medalClass = $rank == 1 ? 'medal-gold' : ($rank == 2 ? 'medal-silver' : ($rank == 3 ? 'medal-bronze' : ''));
                    ?>
                    <tr>
                        <td class="lb-rank <?= $medalClass ?>"><?= $rank++ ?></td>
                        <td class="lb-name"><?= strtoupper($s['firstName']) ?></td>
                        <td><span class="lb-pts"><?= $s['total_pts'] ?></span></td>
                        <td style="color:rgba(255,255,255,0.5);font-size:12px;"><?= $s['wins'] ?></td>
                        <td style="color:rgba(255,255,255,0.5);font-size:12px;"><?= $s['games_played'] ?></td>
                        <td style="color:rgba(255,255,255,0.4);font-size:12px;"><?= $s['rating'] ?></td>
                        <td class="lb-bar-wrap">
                            <div class="lb-bar-bg">
                                <div class="lb-bar-fill" style="width:<?= round($pct) ?>%;"></div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($standings)): ?>
                    <tr><td colspan="7" style="text-align:center;padding:30px;color:rgba(255,255,255,0.2);font-size:12px;">No data yet</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <!-- Right column: donut + decisive/draws donut -->
        <div>
            <div class="glass-card">
                <div class="card-title">Gender Ratio</div>
                <div style="position:relative; height:200px;">
                    <canvas id="genderChart"></canvas>
                </div>
            </div>
            <div class="glass-card">
                <div class="card-title">Game Results</div>
                <div style="position:relative; height:200px;">
                    <canvas id="resultChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <!-- ROW 2: Cumulative points line chart -->
    <div class="glass-card">
        <div class="card-title">Top 5 — Cumulative Points by Round</div>
        <div style="position:relative; height:280px;">
            <canvas id="lineChart"></canvas>
        </div>
    </div>
    <!-- ROW 3: Bar chart (all players) + Age distribution -->
    <div class="grid-2">
        <div class="glass-card">
            <div class="card-title">Points — All Players</div>
            <div style="position:relative; height:260px;">
                <canvas id="barChart"></canvas>
            </div>
        </div>
        <div class="glass-card">
            <div class="card-title">Age Distribution</div>
            <div style="position:relative; height:260px;">
                <canvas id="ageChart"></canvas>
            </div>
        </div>
    </div>
    <!-- ROW 4: Win rate + Rating distribution -->
    <div class="grid-2">
        <div class="glass-card">
            <div class="card-title">Win Rate by Player</div>
            <div style="position:relative; height:300px;">
                <canvas id="winRateChart"></canvas>
            </div>
        </div>
        <div class="glass-card">
            <div class="card-title">Rating Distribution</div>
            <div style="position:relative; height:300px;">
                <canvas id="ratingChart"></canvas>
            </div>
        </div>
    </div>
</div><!-- /page-wrap -->
<script>
Chart.register(ChartDataLabels);
const palette = <?= json_encode($palette) ?>;
const tooltipDefaults = {
    backgroundColor: 'rgba(28,10,4,0.95)',
    titleColor: '#E8A96A',
    bodyColor: '#FAF0DC',
    borderColor: 'rgba(212,130,74,0.3)',
    borderWidth: 1,
    padding: 12,
};
document.addEventListener('DOMContentLoaded', function() {
    // ── Gender donut ──────────────────────────────────────
    new Chart(document.getElementById('genderChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['Male', 'Female'],
            datasets: [{
                data: [<?= $maleCount ?>, <?= $femaleCount ?>],
                backgroundColor: ['#D4824A','#F0C86A'],
                borderColor: ['#1C0A04','#1C0A04'],
                borderWidth: 3,
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false, cutout: '60%',
            plugins: {
                legend: { position: 'bottom', labels: { color: '#fff', padding: 16, font: { size: 12 }, usePointStyle: true, pointStyleWidth: 10, boxHeight: 10,
                    generateLabels: chart => {
                        const d = chart.data;
                        const total = d.datasets[0].data.reduce((a,b)=>a+b,0);
                        return d.labels.map((label,i) => {
                            const val = d.datasets[0].data[i];
                            const pct = total===0?'0':(val*100/total).toFixed(0);
                            return { text: label+': '+val+' ('+pct+'%)', fillStyle: d.datasets[0].backgroundColor[i], strokeStyle:'transparent', pointStyle:'circle', hidden:false, index:i };
                        });
                    }
                }},
                datalabels: { display: false },
                tooltip: { ...tooltipDefaults }
            }
        }
    });
    // ── Decisive vs Draws donut ───────────────────────────
    new Chart(document.getElementById('resultChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['Decisive', 'Draws'],
            datasets: [{
                data: [<?= $decisive ?>, <?= $draws ?>],
                backgroundColor: ['#7B9E87','#A06B9A'],
                borderColor: ['#1C0A04','#1C0A04'],
                borderWidth: 3,
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false, cutout: '60%',
            plugins: {
                legend: { position: 'bottom', labels: { color: '#fff', padding: 16, font: { size: 12 }, usePointStyle: true, pointStyleWidth: 10, boxHeight: 10,
                    generateLabels: chart => {
                        const d = chart.data;
                        const total = d.datasets[0].data.reduce((a,b)=>a+b,0);
                        return d.labels.map((label,i) => {
                            const val = d.datasets[0].data[i];
                            const pct = total===0?'0':(val*100/total).toFixed(0);
                            return { text: label+': '+val+' ('+pct+'%)', fillStyle: d.datasets[0].backgroundColor[i], strokeStyle:'transparent', pointStyle:'circle', hidden:false, index:i };
                        });
                    }
                }},
                datalabels: { display: false },
                tooltip: { ...tooltipDefaults }
            }
        }
    });
    // ── Cumulative line chart ─────────────────────────────
    const top5Names = <?= json_encode($top5names) ?>;
    const cumData   = <?= json_encode($cumData) ?>;
    const lineDatasets = top5Names.map((name, i) => ({
        label: name,
        data: cumData[i],
        borderColor: palette[i],
        backgroundColor: palette[i] + '22',
        borderWidth: 2.5,
        pointRadius: 5,
        pointHoverRadius: 7,
        tension: 0.3,
        fill: false,
    }));
    new Chart(document.getElementById('lineChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: ['R1','R2','R3','R4','R5','R6','R7'],
            datasets: lineDatasets
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: 'rgba(255,255,255,0.4)', font: { size: 11 } } },
                x: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: 'rgba(255,255,255,0.6)', font: { size: 11 } } }
            },
            plugins: {
                legend: { position: 'bottom', labels: { color: '#fff', padding: 14, font: { size: 11 }, usePointStyle: true, pointStyleWidth: 10, boxHeight: 10 } },
                datalabels: { display: false },
                tooltip: { ...tooltipDefaults, mode: 'index', intersect: false }
            }
        }
    });
    // ── All-player bar chart ──────────────────────────────
    const allNames = <?= json_encode(array_map(fn($s) => strtoupper($s['firstName']), $standings)) ?>;
    const allPts   = <?= json_encode(array_column($standings, 'total_pts')) ?>;
    new Chart(document.getElementById('barChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: allNames,
            datasets: [{
                label: 'Points',
                data: allPts,
                backgroundColor: allNames.map((_,i) => palette[i % palette.length] + 'CC'),
                borderColor:     allNames.map((_,i) => palette[i % palette.length]),
                borderWidth: 1,
                borderRadius: 6,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: 'rgba(255,255,255,0.4)', font: { size: 11 } } },
                x: { grid: { display: false }, ticks: { color: 'rgba(255,255,255,0.7)', font: { size: 10 } } }
            },
            plugins: {
                legend: { display: false },
                datalabels: { anchor:'end', align:'top', color:'#E8A96A', font:{ weight:'600', size:11 } },
                tooltip: { ...tooltipDefaults }
            }
        }
    });
    // ── Age distribution bar ──────────────────────────────
    const ageBuckets = <?= json_encode(array_values($ageBuckets)) ?>;
    const ageLabels  = <?= json_encode(array_keys($ageBuckets)) ?>;
    new Chart(document.getElementById('ageChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: ageLabels,
            datasets: [{
                label: 'Players',
                data: ageBuckets,
                backgroundColor: ['#D4824ACC','#F0C86ACC','#7B9E87CC','#A06B9ACC','#5B9BD5CC'],
                borderColor:     ['#D4824A','#F0C86A','#7B9E87','#A06B9A','#5B9BD5'],
                borderWidth: 1,
                borderRadius: 6,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: 'rgba(255,255,255,0.4)', font: { size: 11 }, stepSize: 1 } },
                x: { grid: { display: false }, ticks: { color: 'rgba(255,255,255,0.7)', font: { size: 12 } } }
            },
            plugins: {
                legend: { display: false },
                datalabels: { anchor:'end', align:'top', color:'#E8A96A', font:{ weight:'600', size:12 }, formatter: v => v === 0 ? '' : v },
                tooltip: { ...tooltipDefaults }
            }
        }
    });

    // ── Win rate horizontal bar ───────────────────────────
    const wrNames = <?= json_encode(array_values(array_map(fn($s) => strtoupper($s['firstName']), array_filter($standings, fn($s) => $s['games_played'] > 0)))) ?>;
    const wrData  = <?= json_encode(array_values(array_map(fn($s) => (float)($s['games_played'] > 0 ? round($s['wins'] / $s['games_played'] * 100, 1) : 0), array_filter($standings, fn($s) => $s['games_played'] > 0)))) ?>;
    new Chart(document.getElementById('winRateChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: wrNames,
            datasets: [{
                label: 'Win Rate %',
                data: wrData,
                backgroundColor: wrNames.map((_, i) => palette[i % palette.length] + 'CC'),
                borderColor:     wrNames.map((_, i) => palette[i % palette.length]),
                borderWidth: 1,
                borderRadius: 6,
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true, maintainAspectRatio: false,
            scales: {
                x: { beginAtZero: true, max: 100, grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: 'rgba(255,255,255,0.4)', font: { size: 11 }, callback: v => v + '%' } },
                y: { grid: { display: false }, ticks: { color: 'rgba(255,255,255,0.7)', font: { size: 11 } } }
            },
            plugins: {
                legend: { display: false },
                datalabels: { anchor: 'end', align: 'right', color: '#E8A96A', font: { weight: '600', size: 11 }, formatter: v => v + '%' },
                tooltip: { ...tooltipDefaults, callbacks: { label: ctx => ' ' + ctx.parsed.x + '%' } }
            }
        }
    });

    // ── Rating distribution bar ───────────────────────────
    const ratingLabels = <?= json_encode($ratingLabels) ?>;
    const ratingData   = <?= json_encode($ratingData) ?>;
    new Chart(document.getElementById('ratingChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: ratingLabels,
            datasets: [{
                label: 'Rating',
                data: ratingData,
                backgroundColor: ratingLabels.map((_, i) => palette[i % palette.length] + 'CC'),
                borderColor:     ratingLabels.map((_, i) => palette[i % palette.length]),
                borderWidth: 1,
                borderRadius: 6,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: false, grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: 'rgba(255,255,255,0.4)', font: { size: 11 } } },
                x: { grid: { display: false }, ticks: { color: 'rgba(255,255,255,0.7)', font: { size: 10 } } }
            },
            plugins: {
                legend: { display: false },
                datalabels: { anchor: 'end', align: 'top', color: '#E8A96A', font: { weight: '600', size: 10 }, formatter: v => v === 0 ? '' : v },
                tooltip: { ...tooltipDefaults }
            }
        }
    });
});
</script>
<script src="../script/fx.js"></script>
</body>
</html>