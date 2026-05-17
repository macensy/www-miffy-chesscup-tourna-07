<?php
session_start();
if (!isset($_SESSION['user'])) { header("Location: loginpage.php"); exit(); }
require_once "../bl/usermanager.php";

$manager = new usermanager();
$currentRound = isset($_GET['round']) ? (int)$_GET['round'] : 1;

$user = $_SESSION['user'];
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

$adminData = $manager->getAdminDashboardStats($currentRound);
$standings  = $manager->getLeaderboards() ?? [];
$pairings   = $manager->getPairings($currentRound) ?? [];
$wdlData    = $manager->getPlayerWDL() ?? [];

$nextRoundToGenerate = 1;
$maxRoundsReached    = false;
$prevRoundPending    = false;
$existingRounds      = [];
try {
    $db   = (new Database())->connectDB();
    $rRows = $db->query("SELECT DISTINCT round_num FROM tbl_pairing ORDER BY round_num ASC")->fetchAll(PDO::FETCH_COLUMN);
    $existingRounds = $rRows;

    $stmt = $db->query("SELECT MAX(round_num) as max_r FROM tbl_pairing");
    $row  = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && $row['max_r']) {
        $nextRoundToGenerate = (int)$row['max_r'] + 1;
        if ($nextRoundToGenerate > 7) $maxRoundsReached = true;
        $pCheck = $db->prepare("SELECT COUNT(*) FROM tbl_pairing WHERE round_num = ? AND status != 'FINISHED'");
        $pCheck->execute([(int)$row['max_r']]);
        if ($pCheck->fetchColumn() > 0) $prevRoundPending = true;
    }
} catch (Exception $e) {}

$roundProgress = [];
try {
    $db2 = (new Database())->connectDB();
    $rows = $db2->query("SELECT round_num,
                          SUM(CASE WHEN status='FINISHED' THEN 1 ELSE 0 END) as finished,
                          SUM(CASE WHEN status!='FINISHED' THEN 1 ELSE 0 END) as pending
                          FROM tbl_pairing GROUP BY round_num ORDER BY round_num ASC")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) $roundProgress[] = $r;
} catch (Exception $e) { $roundProgress = []; }

$chartLabels = [];
$chartData   = [];
foreach (array_slice($standings, 0, 5) as $s) {
    $chartLabels[] = strtoupper($s['firstName']);
    $chartData[]   = (float)$s['total_pts'];
}

$wdlLabels = $wdlWins = $wdlDraws = $wdlLosses = [];
foreach (array_slice($wdlData, 0, 8) as $w) {
    $wdlLabels[] = strtoupper($w['firstName']);
    $wdlWins[]   = (int)$w['wins'];
    $wdlDraws[]  = (int)$w['draws'];
    $wdlLosses[] = (int)$w['losses'];
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
            --win-color:    #4CAF82;
            --draw-color:   #E8A96A;
            --loss-color:   #E05050;
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
            background-color: #0C0402;
        }
        #bgCanvas {
            position: fixed; top: 0; left: 0;
            width: 100%; height: 100%;
            z-index: -1;
        }

        #confettiCanvas {
            position: fixed; top: 0; left: 0;
            width: 100%; height: 100%;
            z-index: 9999; pointer-events: none;
        }
        #podiumOverlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(8, 3, 1, 0.85);
            z-index: 9998; backdrop-filter: blur(10px);
            align-items: center; justify-content: center;
            flex-direction: column;
        }
        #podiumOverlay.visible { display: flex; }
        .podium-title {
            font-family: 'Cinzel', serif;
            font-size: clamp(1.4rem, 4vw, 2.8rem);
            font-weight: 900; letter-spacing: 4px;
            color: var(--cream); text-align: center;
            margin-bottom: 8px; text-shadow: 0 0 40px rgba(212,168,67,0.5);
        }
        .podium-subtitle {
            font-size: 11px; letter-spacing: 4px; text-transform: uppercase;
            color: var(--caramel); margin-bottom: 48px; text-align: center;
        }
        .podium-stage {
            display: flex; align-items: flex-end; gap: 0;
            justify-content: center; margin-bottom: 48px;
        }
        .podium-place {
            display: flex; flex-direction: column; align-items: center;
        }
        .podium-avatar {
            width: 70px; height: 70px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-family: 'Cinzel', serif; font-size: 1.6rem; font-weight: 700;
            margin-bottom: 10px; border: 3px solid;
        }
        .podium-name {
            font-family: 'Cinzel', serif; font-size: 11px; font-weight: 700;
            letter-spacing: 2px; text-transform: uppercase;
            margin-bottom: 6px; text-align: center; max-width: 120px;
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        }
        .podium-pts {
            font-size: 11px; letter-spacing: 1px; margin-bottom: 8px;
            color: rgba(255,255,255,0.55);
        }
        .podium-block {
            width: 130px; display: flex; align-items: center; justify-content: center;
            border-radius: 8px 8px 0 0; font-family: 'Cinzel', serif;
            font-size: 2.5rem; font-weight: 900; color: rgba(0,0,0,0.3);
        }

        .podium-place.first .podium-avatar  { background: rgba(212,168,67,0.18); border-color: var(--gold); color: var(--gold-lt); }
        .podium-place.first .podium-name    { color: var(--gold-lt); }
        .podium-place.first .podium-block   { height: 160px; background: linear-gradient(180deg, #D4A843, #8B6B1A); }

        .podium-place.second .podium-avatar { background: rgba(192,192,192,0.15); border-color: #C0C0C0; color: #E0E0E0; }
        .podium-place.second .podium-name   { color: #E0E0E0; }
        .podium-place.second .podium-block  { height: 120px; background: linear-gradient(180deg, #B0B0B0, #606060); }

        .podium-place.third .podium-avatar  { background: rgba(205,127,50,0.15); border-color: #CD7F32; color: #E8A060; }
        .podium-place.third .podium-name    { color: #E8A060; }
        .podium-place.third .podium-block   { height: 90px; background: linear-gradient(180deg, #CD7F32, #7A4A18); }
        .btn-close-podium {
            padding: 12px 36px; background: var(--mocha-light); color: white;
            border: none; border-radius: 30px; font-family: 'DM Sans', sans-serif;
            font-size: 13px; font-weight: 600; letter-spacing: 1.5px; text-transform: uppercase;
            cursor: pointer; transition: 0.2s;
        }
        .btn-close-podium:hover { background: var(--mocha-glow); transform: translateY(-2px); }
        .top-bar {
            display: flex; align-items: center; justify-content: space-between;
            padding: 22px 32px; border-bottom: 1px solid var(--glass-border);
            background: rgba(18, 8, 4, 0.72); backdrop-filter: blur(14px);
            position: sticky; top: 0; z-index: 100;
        }
        .brand-row { display: flex; align-items: center; gap: 14px; }
        .brand-logo { width: 58px; height: 58px; border-radius: 50%; background: white; border: 2.5px solid var(--caramel); object-fit: contain; padding: 4px; }
        .brand-name { font-family: 'Cinzel', serif; font-size: 1.55rem; font-weight: 700; color: var(--cream); letter-spacing: 2px; line-height: 1.15; }
        .brand-user { font-size: 12px; color: var(--caramel); letter-spacing: 2.5px; text-transform: uppercase; font-weight: 500; }
        .nav-actions { display: flex; align-items: center; gap: 10px; }
        .nav-link { font-size: 12px; letter-spacing: 1.5px; text-transform: uppercase; color: rgba(255,255,255,0.45); text-decoration: none; transition: color 0.2s; }
        .nav-link:hover { color: var(--caramel); }
        .btn-logout { padding: 7px 18px; border: 1px solid var(--caramel); color: var(--caramel); background: transparent; border-radius: 20px; font-size: 12px; font-weight: 500; letter-spacing: 1px; text-decoration: none; transition: 0.2s; cursor: pointer; }
        .btn-logout:hover { background: rgba(212,130,74,0.12); color: var(--caramel); }
        .page-wrap { padding: 28px 20px 0; max-width: 1100px; margin: 0 auto; }
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 24px; }
        .stat-card { background: rgba(107, 58, 42, 0.28); border: 1px solid var(--glass-border); border-radius: 14px; padding: 20px 16px; text-align: center; backdrop-filter: blur(10px); transition: border-color 0.2s; }
        .stat-card:hover { border-color: rgba(212,130,74,0.5); }
        .stat-num { font-family: 'Cinzel', serif; font-size: 2.4rem; font-weight: 600; color: var(--caramel); line-height: 1; margin-bottom: 8px; }
        .stat-divider { width: 24px; height: 1px; background: rgba(212,130,74,0.38); margin: 0 auto 8px; }
        .stat-lbl { font-size: 11px; font-weight: 600; letter-spacing: 2.5px; text-transform: uppercase; color: rgba(255,255,255,0.45); }
        .main-grid { display: grid; grid-template-columns: 300px 1fr; gap: 16px; align-items: start; }
        .glass-card { background: var(--glass-bg); border: 1px solid var(--glass-border); border-radius: 16px; padding: 22px 20px; backdrop-filter: blur(12px); margin-bottom: 16px; }
        .card-title { font-family: 'Cinzel', serif; font-size: 12px; font-weight: 700; letter-spacing: 3px; text-transform: uppercase; color: var(--caramel); margin-bottom: 16px; padding-bottom: 10px; border-bottom: 1px solid rgba(212,130,74,0.18); }
        .stand-table { width: 100%; border-collapse: collapse; }
        .stand-table tr { border-bottom: 1px solid rgba(255,255,255,0.05); }
        .stand-table tr:last-child { border-bottom: none; }
        .stand-table tr:hover td { background: var(--glass-hover); }
        .stand-table td { padding: 8px 4px; font-size: 13px; }
        .rank-num { font-family: 'Cinzel', serif; font-weight: 600; color: var(--caramel); width: 24px; }
        .rank-name { color: rgba(255,255,255,0.85); padding: 0 8px; }
        .rank-pts { text-align: right; font-size: 12px; color: var(--gold-lt); background: rgba(212,168,67,0.12); padding: 2px 8px; border-radius: 10px; font-weight: 500; white-space: nowrap; }
        .round-row { display: flex; flex-wrap: wrap; gap: 7px; margin-bottom: 18px; }
        .round-pill { padding: 5px 14px; border-radius: 20px; font-size: 11px; font-weight: 500; letter-spacing: 0.5px; border: 1px solid rgba(212,130,74,0.22); color: rgba(255,255,255,0.38); background: transparent; text-decoration: none; transition: 0.15s; }
        .round-pill:hover { color: rgba(255,255,255,0.75); border-color: rgba(212,130,74,0.45); }
        .round-pill.active { background: var(--mocha-light); color: white; border-color: var(--mocha-light); }
        .pairing-head th { font-size: 11px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; color: var(--caramel); padding: 0 8px 12px; border-bottom: 1px solid rgba(212,130,74,0.18); }
        .pairing-row td { padding: 13px 8px; border-bottom: 1px solid rgba(255,255,255,0.04); }
        .pairing-row:last-child td { border-bottom: none; }
        .pairing-row:hover td { background: var(--glass-hover); }
        .player-cell { font-weight: 500; font-size: 13px; color: rgba(255,255,255,0.85); }
        .result-cell { text-align: center; }
        .score-wrap { display: flex; align-items: center; justify-content: center; gap: 8px; }
        .score-input { width: 42px !important; height: 32px !important; background: rgba(0,0,0,0.35) !important; border: 1px solid rgba(212,130,74,0.38) !important; color: white !important; text-align: center; border-radius: 7px !important; font-size: 14px; font-weight: 500; margin: 0 !important; box-shadow: none !important; }
        .score-input:focus { border-color: var(--caramel) !important; box-shadow: 0 0 0 2px rgba(212,130,74,0.15) !important; outline: none; }
        .score-sep { color: rgba(212,130,74,0.55); font-size: 12px; }
        .score-display { font-size: 1.1rem; font-weight: 600; color: var(--caramel); letter-spacing: 2px; }
        .btn-save { width: 30px; height: 30px; border-radius: 50%; background: var(--mocha-light); border: none; color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: 0.2s; flex-shrink: 0; }
        .btn-save:hover { background: var(--caramel); transform: scale(1.08); }
        .btn-save .material-icons { font-size: 15px; }
        .pairing-actions { display: flex; gap: 8px; align-items: center; }
        .btn-generate { display: flex; align-items: center; gap: 5px; padding: 7px 16px; background: var(--mocha-light); color: white; border: none; border-radius: 20px; font-size: 12px; font-weight: 500; letter-spacing: 0.5px; cursor: pointer; transition: 0.2s; }
        .btn-generate:hover { background: var(--mocha-glow); }
        .btn-reset { display: flex; align-items: center; gap: 5px; padding: 7px 16px; background: transparent; color: rgba(220,80,80,0.8); border: 1px solid rgba(220,80,80,0.32); border-radius: 20px; font-size: 12px; font-weight: 500; cursor: pointer; transition: 0.2s; }
        .btn-reset:hover { background: rgba(220,80,80,0.1); }
        .btn-generate .material-icons, .btn-reset .material-icons { font-size: 14px; }
        .no-matches { text-align: center; padding: 50px 20px; color: rgba(255,255,255,0.18); font-size: 13px; letter-spacing: 1px; }
        .chart-title { font-family: 'Cinzel', serif; font-size: 12px; font-weight: 700; letter-spacing: 3px; text-transform: uppercase; color: var(--caramel); text-align: center; margin-bottom: 20px; }

        .wdl-legend { display: flex; justify-content: center; gap: 18px; margin-bottom: 14px; }
        .wdl-dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-right: 5px; vertical-align: middle; }
        .wdl-legend span { font-size: 12px; color: rgba(255,255,255,0.55); letter-spacing: 1px; }

        @media (max-width: 768px) {
            .main-grid { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: 1fr; }
            .top-bar { padding: 14px 16px; }
            .page-wrap { padding: 16px 12px 0; }
            .player-row-2col { grid-template-columns: 1fr !important; }
        }
    </style>
</head>
<body>

<canvas id="bgCanvas"></canvas>
<canvas id="confettiCanvas"></canvas>

<div id="podiumOverlay">
    <div class="podium-title">🏆 Tournament Complete!</div>
    <div class="podium-subtitle">♟ Final Leaderboard — Miffy Chess Cup 2026</div>
    <div class="podium-stage" id="podiumStage"></div>
    <button class="btn-close-podium" onclick="closePodium()">View Full Standings</button>
</div>

<div class="top-bar">
    <div class="brand-row">
        <img src="../assets/miffy.jpg" class="brand-logo" alt="Miffy">
        <div>
            <div class="brand-name">Miffy Chess Cup</div>
            <div class="brand-user"><?= strtoupper($user['firstName'] ?? 'User') ?> &nbsp;·&nbsp; <?= $isAdmin ? 'Admin' : 'Player' ?></div>
        </div>
    </div>
    <div class="nav-actions">
        <a href="playerspage.php" class="nav-link">Players</a>
        <?php if($isAdmin): ?>
        <a href="analyticspage.php" class="nav-link">Analytics</a>
        <a href="logs.php" class="nav-link">Logs</a>
        <?php endif; ?>
        <a href="logout.php" class="btn-logout">Logout</a>
    </div>
</div>

<div class="page-wrap">

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

    <div class="main-grid">

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
                <div class="card-title">Round Progress</div>
                <?php if(empty($roundProgress)): ?>
                <div style="text-align:center; padding:30px; color:rgba(255,255,255,0.18); font-size:12px; letter-spacing:1px;">No rounds generated yet.</div>
                <?php else: ?>
                <div style="display:flex; flex-direction:column; gap:10px;">
                <?php foreach($roundProgress as $rp):
                    $total = $rp["finished"] + $rp["pending"];
                    $pct   = $total > 0 ? round(($rp["finished"] / $total) * 100) : 0;
                    $done  = $pct == 100;
                ?>
                <div>
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:5px;">
                        <span style="font-size:12px; font-weight:600; color:rgba(255,255,255,0.75);">Round <?= $rp["round_num"] ?></span>
                        <span style="font-size:11px; color:<?= $done ? "var(--win-color)" : "var(--caramel)" ?>; letter-spacing:1px;">
                            <?= $done ? "✓ Done" : $rp["finished"]."\/".$total." matches" ?>
                        </span>
                    </div>
                    <div style="height:6px; background:rgba(255,255,255,0.07); border-radius:6px; overflow:hidden;">
                        <div style="height:100%; width:<?= $pct ?>%; background:<?= $done ? "var(--win-color)" : "var(--mocha-glow)" ?>; border-radius:6px; transition:width 0.4s;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <div>
            <div class="glass-card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                    <div class="card-title" style="margin-bottom:0; border:none; padding-bottom:0;">Pairing — Round <?= $currentRound ?></div>
                    <?php if($isAdmin): ?>
                    <div class="pairing-actions">
                        <?php if($maxRoundsReached): ?>
                        <span style="font-size:11px;color:var(--caramel);letter-spacing:1px;opacity:0.6;">Max 7 rounds reached</span>
                        <?php elseif($prevRoundPending): ?>
                        <button class="btn-generate" disabled style="opacity:0.4;cursor:not-allowed;" title="Finish Round <?= $nextRoundToGenerate - 1 ?> first">
                            <i class="material-icons">lock</i> Round <?= $nextRoundToGenerate - 1 ?> Ongoing
                        </button>
                        <?php else: ?>
                        <button onclick="generatePairs(<?= $nextRoundToGenerate ?>)" class="btn-generate">
                            <i class="material-icons">bolt</i> Generate R<?= $nextRoundToGenerate ?>
                        </button>
                        <?php endif; ?>
                        <button onclick="resetTournament()" class="btn-reset">
                            <i class="material-icons">history</i> Reset
                        </button>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="round-row">
                    <?php foreach($existingRounds as $r): ?>
                    <a href="?round=<?= $r ?>" class="round-pill <?= $currentRound == $r ? 'active' : '' ?>">R<?= $r ?></a>
                    <?php endforeach; ?>
                    <?php if(empty($existingRounds)): ?>
                    <span style="font-size:12px;color:rgba(255,255,255,0.25);letter-spacing:1px;">No rounds yet</span>
                    <?php endif; ?>
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

            <div class="glass-card" style="text-align:center; padding: 32px 24px;">
                <div style="font-size: 38px; margin-bottom: 12px;">📊</div>
                <div style="font-family:'Cinzel',serif; font-size:13px; font-weight:700; letter-spacing:3px; text-transform:uppercase; color:var(--caramel); margin-bottom:10px;">Deep Analytics</div>
                <div style="font-size:13px; color:rgba(255,255,255,0.45); margin-bottom:22px; line-height:1.6;">
                    View Top 5 performance charts, Win / Draw / Loss breakdowns, and more.
                </div>
                <a href="analyticspage.php" style="display:inline-flex; align-items:center; gap:7px; padding:10px 24px; background:var(--mocha-light); color:white; border-radius:22px; font-size:13px; font-weight:500; letter-spacing:1px; text-decoration:none; transition:0.2s;" onmouseover="this.style.background='var(--mocha-glow)'" onmouseout="this.style.background='var(--mocha-light)'">
                    <i class="material-icons" style="font-size:16px;">bar_chart</i> View Analytics
                </a>
            </div>
            <?php endif; ?>
        </div>

    </div>
</div>
<?php if(!$isAdmin):

$myID     = (int)$user['userID'];
$myWDL    = ['wins'=>0,'draws'=>0,'losses'=>0,'total_pts'=>0,'rank'=>'—'];
$myRating = htmlspecialchars($user['rating'] ?? '—');
$rankPos  = 1;
foreach($standings as $s) {
    if((int)$s['userID'] === $myID) {
        $myWDL['total_pts'] = $s['total_pts'];
        $myWDL['rank']      = $rankPos;
        break;
    }
    $rankPos++;
}
foreach($wdlData as $w) {
    if((int)$w['userID'] === $myID) {
        $myWDL['wins']   = (int)$w['wins'];
        $myWDL['draws']  = (int)$w['draws'];
        $myWDL['losses'] = (int)$w['losses'];
        break;
    }
}

$nextOpponent = null;
$nextMatchRound = null;
$myColor = null;
try {
    $dbNext = (new Database())->connectDB();
    $stmtOpp = $dbNext->prepare("
        SELECT m.*, p1.firstName AS p1Name, p2.firstName AS p2Name
        FROM tbl_pairing m
        INNER JOIN tbl_players p1 ON m.player1_id = p1.userID
        INNER JOIN tbl_players p2 ON m.player2_id = p2.userID
        WHERE m.status = 'PENDING'
          AND (m.player1_id = :uid OR m.player2_id = :uid)
        ORDER BY m.round_num ASC, m.match_id ASC
        LIMIT 1
    ");
    $stmtOpp->execute([':uid' => $myID]);
    $nextMatch = $stmtOpp->fetch(PDO::FETCH_ASSOC);
    if($nextMatch) {
        $nextMatchRound = (int)$nextMatch['round_num'];
        if((int)$nextMatch['player1_id'] === $myID) {
            $nextOpponent = htmlspecialchars($nextMatch['p2Name']);
            $myColor = 'White';
        } else {
            $nextOpponent = htmlspecialchars($nextMatch['p1Name']);
            $myColor = 'Black';
        }
    }
} catch(Exception $e) {}

$countdownTarget = null;
$countdownLabel  = '';
try {
    $dbCd = (new Database())->connectDB();
    $maxR = (int)$dbCd->query("SELECT COALESCE(MAX(round_num),0) FROM tbl_pairing")->fetchColumn();
    if($maxR > 0) {
        $stmtPend = $dbCd->prepare("SELECT COUNT(*) FROM tbl_pairing WHERE round_num=? AND status!='FINISHED'");
        $stmtPend->execute([$maxR]);
        $pendingInMax = (int)$stmtPend->fetchColumn();
        if($pendingInMax > 0) {

            $midnight = mktime(23,59,59, date('n'), date('j'), date('Y'));
            $countdownTarget = $midnight;
            $countdownLabel  = 'Round ' . $maxR . ' results close';
        } else {

            $countdownTarget = strtotime('tomorrow midnight');
            $countdownLabel  = 'Next round expected by';
        }
    } else {
        $countdownTarget = strtotime('tomorrow midnight');
        $countdownLabel  = 'Tournament starts in';
    }
} catch(Exception $e) {
    $countdownTarget = strtotime('tomorrow midnight');
    $countdownLabel  = 'Next update in';
}

$puzzleOfDay = (int)date('z') % 10;

?>

<div class="page-wrap" style="max-width:1100px;margin:0 auto;padding:0 20px 48px;">

    <div class="player-row-2col" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">

        <div class="glass-card" style="padding:26px 24px;">
            <div class="card-title">♟ My Tournament Status</div>

            <div style="display:flex;align-items:center;gap:16px;margin-bottom:22px;">
                <div style="width:52px;height:52px;border-radius:50%;background:rgba(212,130,74,0.15);border:2px solid rgba(212,130,74,0.4);display:flex;align-items:center;justify-content:center;font-family:'Cinzel',serif;font-size:1.3rem;font-weight:700;color:var(--caramel);flex-shrink:0;">
                    <?= strtoupper(substr($user['firstName'] ?? 'P', 0, 1)) ?>
                </div>
                <div>
                    <div style="font-family:'Cinzel',serif;font-size:15px;font-weight:600;color:var(--cream);letter-spacing:1px;">
                        <?= strtoupper(htmlspecialchars(($user['firstName']??'') . ' ' . ($user['lastName']??''))) ?>
                    </div>
                    <div style="font-size:11px;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,0.35);margin-top:3px;">
                        FIDE Rating: <?= $myRating ?>
                    </div>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:22px;">
                <div style="background:rgba(76,175,130,0.12);border:1px solid rgba(76,175,130,0.25);border-radius:10px;padding:12px 6px;text-align:center;">
                    <div style="font-family:'Cinzel',serif;font-size:1.4rem;font-weight:600;color:var(--win-color);"><?= $myWDL['wins'] ?></div>
                    <div style="font-size:10px;letter-spacing:2px;color:rgba(255,255,255,0.35);margin-top:4px;">WINS</div>
                </div>
                <div style="background:rgba(232,169,106,0.12);border:1px solid rgba(232,169,106,0.25);border-radius:10px;padding:12px 6px;text-align:center;">
                    <div style="font-family:'Cinzel',serif;font-size:1.4rem;font-weight:600;color:var(--caramel);"><?= $myWDL['draws'] ?></div>
                    <div style="font-size:10px;letter-spacing:2px;color:rgba(255,255,255,0.35);margin-top:4px;">DRAWS</div>
                </div>
                <div style="background:rgba(224,80,80,0.12);border:1px solid rgba(224,80,80,0.25);border-radius:10px;padding:12px 6px;text-align:center;">
                    <div style="font-family:'Cinzel',serif;font-size:1.4rem;font-weight:600;color:var(--loss-color);"><?= $myWDL['losses'] ?></div>
                    <div style="font-size:10px;letter-spacing:2px;color:rgba(255,255,255,0.35);margin-top:4px;">LOSS</div>
                </div>
                <div style="background:rgba(212,168,67,0.12);border:1px solid rgba(212,168,67,0.25);border-radius:10px;padding:12px 6px;text-align:center;">
                    <div style="font-family:'Cinzel',serif;font-size:1.4rem;font-weight:600;color:var(--gold-lt);"><?= $myWDL['total_pts'] ?></div>
                    <div style="font-size:10px;letter-spacing:2px;color:rgba(255,255,255,0.35);margin-top:4px;">PTS</div>
                </div>
            </div>

            <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;background:rgba(107,58,42,0.22);border-radius:10px;margin-bottom:16px;">
                <span style="font-size:12px;letter-spacing:1.5px;text-transform:uppercase;color:rgba(255,255,255,0.45);">Current Rank</span>
                <span style="font-family:'Cinzel',serif;font-size:1.2rem;font-weight:700;color:var(--gold-lt);">
                    #<?= $myWDL['rank'] ?>
                </span>
            </div>

            <?php if($nextOpponent): ?>
            <div style="padding:12px 16px;background:rgba(76,175,130,0.08);border:1px solid rgba(76,175,130,0.22);border-radius:10px;">
                <div style="font-size:10px;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,0.35);margin-bottom:6px;">Next Match — Round <?= $nextMatchRound ?></div>
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <span style="font-family:'Cinzel',serif;font-size:13px;color:var(--cream);">vs <?= strtoupper($nextOpponent) ?></span>
                    <span style="font-size:11px;padding:3px 10px;border-radius:20px;background:rgba(212,130,74,0.18);color:var(--caramel);letter-spacing:1px;"><?= $myColor ?></span>
                </div>
            </div>
            <?php else: ?>
            <div style="padding:12px 16px;background:rgba(107,58,42,0.15);border-radius:10px;font-size:13px;color:rgba(255,255,255,0.3);letter-spacing:0.5px;text-align:center;">
                No pending matches
            </div>
            <?php endif; ?>
        </div>

        <div class="glass-card" style="padding:26px 24px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;">
            <div style="font-size:32px;margin-bottom:12px;">⏱</div>
            <div style="font-family:'Cinzel',serif;font-size:11px;font-weight:700;letter-spacing:3px;text-transform:uppercase;color:var(--caramel);margin-bottom:6px;">
                <?= htmlspecialchars($countdownLabel) ?>
            </div>
            <div style="font-size:11px;letter-spacing:2px;color:rgba(255,255,255,0.25);margin-bottom:28px;">
                <?= date('F j, Y') ?>
            </div>

            <div style="display:flex;gap:16px;align-items:flex-end;margin-bottom:28px;">
                <div style="text-align:center;">
                    <div id="cd-hours" style="font-family:'Cinzel',serif;font-size:2.6rem;font-weight:700;color:var(--cream);line-height:1;min-width:60px;">00</div>
                    <div style="font-size:9px;letter-spacing:2.5px;text-transform:uppercase;color:rgba(255,255,255,0.3);margin-top:6px;">hours</div>
                </div>
                <div style="font-family:'Cinzel',serif;font-size:2rem;color:rgba(212,130,74,0.4);padding-bottom:10px;">:</div>
                <div style="text-align:center;">
                    <div id="cd-mins" style="font-family:'Cinzel',serif;font-size:2.6rem;font-weight:700;color:var(--cream);line-height:1;min-width:60px;">00</div>
                    <div style="font-size:9px;letter-spacing:2.5px;text-transform:uppercase;color:rgba(255,255,255,0.3);margin-top:6px;">minutes</div>
                </div>
                <div style="font-family:'Cinzel',serif;font-size:2rem;color:rgba(212,130,74,0.4);padding-bottom:10px;">:</div>
                <div style="text-align:center;">
                    <div id="cd-secs" style="font-family:'Cinzel',serif;font-size:2.6rem;font-weight:700;color:var(--caramel);line-height:1;min-width:60px;">00</div>
                    <div style="font-size:9px;letter-spacing:2.5px;text-transform:uppercase;color:rgba(255,255,255,0.3);margin-top:6px;">seconds</div>
                </div>
            </div>

            <div style="width:100%;background:rgba(255,255,255,0.06);border-radius:6px;height:5px;overflow:hidden;margin-bottom:16px;">
                <div id="cd-bar" style="height:100%;background:linear-gradient(90deg,var(--mocha-light),var(--caramel));border-radius:6px;transition:width 1s linear;width:0%;"></div>
            </div>

            <div style="display:flex;gap:6px;flex-wrap:wrap;justify-content:center;">
                <?php
                $totalRounds = 7;
                $completedRounds = (int)($adminData['completed_rounds'] ?? 0);
                for($r=1;$r<=$totalRounds;$r++):
                    $done = $r <= $completedRounds;
                    $curr = $r === ($completedRounds + 1) && !$maxRoundsReached;
                ?>
                <div style="width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:'Cinzel',serif;font-size:10px;font-weight:600;
                    <?= $done ? 'background:var(--mocha-light);color:white;' : ($curr ? 'background:rgba(212,130,74,0.22);color:var(--caramel);border:1px solid rgba(212,130,74,0.5);' : 'background:rgba(255,255,255,0.05);color:rgba(255,255,255,0.2);border:1px solid rgba(255,255,255,0.08);') ?>
                ">
                    <?= $done ? '✓' : $r ?>
                </div>
                <?php endfor; ?>
            </div>
            <div style="font-size:11px;color:rgba(255,255,255,0.25);margin-top:10px;letter-spacing:1px;">
                Round <?= $completedRounds ?>/<?= $totalRounds ?> complete
            </div>
        </div>
    </div>

    <div class="glass-card" style="padding:28px 24px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
            <div class="card-title" style="margin-bottom:0;padding-bottom:0;border:none;">♟ Daily Chess Puzzle</div>
            <div style="display:flex;align-items:center;gap:10px;">
                <span id="pz-badge" style="font-size:11px;background:rgba(107,58,42,0.5);color:var(--caramel);padding:4px 12px;border-radius:20px;border:1px solid rgba(212,130,74,0.22);letter-spacing:1px;"></span>
                <span style="font-size:11px;color:rgba(255,255,255,0.25);letter-spacing:1px;">Puzzle of the day</span>
            </div>
        </div>

        <div style="display:flex;gap:28px;align-items:flex-start;flex-wrap:wrap;">

            <div style="flex-shrink:0;">
                <canvas id="pzBoard" width="360" height="360" style="border-radius:10px;display:block;cursor:pointer;box-shadow:0 8px 32px rgba(0,0,0,0.5);"></canvas>
                <div style="display:flex;justify-content:space-between;margin-top:8px;padding:0 4px;">
                    <?php foreach(['a','b','c','d','e','f','g','h'] as $lbl): ?>
                    <span style="font-size:10px;color:rgba(212,130,74,0.4);width:45px;text-align:center;"><?= $lbl ?></span>
                    <?php endforeach; ?>
                </div>
            </div>

            <div style="flex:1;min-width:200px;display:flex;flex-direction:column;gap:0;">

                <div id="pz-turn" style="display:inline-flex;align-items:center;gap:8px;padding:8px 18px;border-radius:20px;background:rgba(212,130,74,0.1);border:1px solid rgba(212,130,74,0.28);margin-bottom:18px;align-self:flex-start;">
                    <span id="pz-dot" style="width:10px;height:10px;border-radius:50%;background:#FAF0DC;display:inline-block;"></span>
                    <span id="pz-turn-text" style="font-family:'Cinzel',serif;font-size:11px;letter-spacing:2px;color:var(--caramel);">WHITE TO MOVE</span>
                </div>

                <div style="font-size:11px;letter-spacing:2px;text-transform:uppercase;color:rgba(212,130,74,0.5);margin-bottom:6px;">Objective</div>
                <div id="pz-hint" style="font-size:13px;color:rgba(255,255,255,0.5);line-height:1.7;margin-bottom:18px;min-height:38px;"></div>

                <div id="pz-status" style="font-size:13px;font-weight:500;min-height:20px;margin-bottom:18px;"></div>

                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px;">
                    <button onclick="pzReset()" style="padding:9px 18px;background:var(--mocha-light);color:white;border:none;border-radius:20px;font-family:'Cinzel',serif;font-size:11px;letter-spacing:1.5px;cursor:pointer;transition:0.2s;" onmouseover="this.style.background='var(--mocha-glow)'" onmouseout="this.style.background='var(--mocha-light)'">↺ Try Again</button>
                    <button onclick="pzNext()" style="padding:9px 18px;background:transparent;color:var(--caramel);border:1px solid rgba(212,130,74,0.38);border-radius:20px;font-family:'Cinzel',serif;font-size:11px;letter-spacing:1.5px;cursor:pointer;transition:0.2s;">Next ›</button>
                    <button onclick="pzReveal()" id="pz-reveal-btn" style="padding:9px 18px;background:transparent;color:rgba(255,255,255,0.3);border:1px solid rgba(255,255,255,0.1);border-radius:20px;font-family:'Cinzel',serif;font-size:11px;letter-spacing:1.5px;cursor:pointer;transition:0.2s;">Show Answer</button>
                </div>

                <div style="padding-top:16px;border-top:1px solid rgba(212,130,74,0.12);display:flex;gap:20px;">
                    <div>
                        <div style="font-size:10px;letter-spacing:2px;text-transform:uppercase;color:rgba(212,130,74,0.45);margin-bottom:4px;">Solved today</div>
                        <div id="pz-solved-count" style="font-family:'Cinzel',serif;font-size:1.4rem;color:var(--caramel);">0</div>
                    </div>
                    <div>
                        <div style="font-size:10px;letter-spacing:2px;text-transform:uppercase;color:rgba(212,130,74,0.45);margin-bottom:4px;">Streak</div>
                        <div id="pz-streak" style="font-family:'Cinzel',serif;font-size:1.4rem;color:var(--gold-lt);">🔥 0</div>
                    </div>
                    <div>
                        <div style="font-size:10px;letter-spacing:2px;text-transform:uppercase;color:rgba(212,130,74,0.45);margin-bottom:4px;">Accuracy</div>
                        <div id="pz-accuracy" style="font-family:'Cinzel',serif;font-size:1.4rem;color:var(--cream);">—</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
(function(){
    const target = <?= (int)$countdownTarget ?> * 1000;
    const dayStart = new Date();
    dayStart.setHours(0,0,0,0);
    const dayMs = 24*60*60*1000;

    function tick(){
        const now  = Date.now();
        const diff = Math.max(0, target - now);
        const h    = Math.floor(diff / 3600000);
        const m    = Math.floor((diff % 3600000) / 60000);
        const s    = Math.floor((diff % 60000) / 1000);
        document.getElementById('cd-hours').textContent = String(h).padStart(2,'0');
        document.getElementById('cd-mins').textContent  = String(m).padStart(2,'0');
        document.getElementById('cd-secs').textContent  = String(s).padStart(2,'0');

        const dayPct = Math.min(100, ((now - dayStart.getTime()) / dayMs) * 100);
        document.getElementById('cd-bar').style.width = dayPct.toFixed(1) + '%';
        if(diff <= 0) { clearInterval(timer); document.getElementById('cd-secs').textContent='00'; }
    }
    const timer = setInterval(tick, 1000);
    tick();
})();
</script>

<script>
(function(){
const LIGHT='#C8956A', DARK='#3E1A06';
const SEL='rgba(212,200,50,0.45)', MOVE='rgba(100,200,100,0.32)', LAST='rgba(212,168,67,0.22)';
const CORRECT_SQ='rgba(76,200,130,0.45)', WRONG_SQ='rgba(224,80,80,0.4)';
const canvas = document.getElementById('pzBoard');
const ctx    = canvas.getContext('2d');
const SZ     = 45;

const GLYPHS = {wK:'♔',wQ:'♕',wR:'♖',wB:'♗',wN:'♘',wP:'♙',bK:'♚',bQ:'♛',bR:'♜',bB:'♝',bN:'♞',bP:'♟'};

const PUZZLES = [
    {
        label:'Mate in 1', hint:'White delivers checkmate in one move.',
        sideToMove:'w',
        board:(()=>{let b=mk();b[7][4]='wK';b[0][4]='bK';b[0][3]='bP';b[0][5]='bP';b[1][4]='wQ';return b;})(),
        solution:[{fr:1,fc:4,tr:0,tc:4}]
    },
    {
        label:'Mate in 2', hint:'Sacrifice first, then finish with checkmate.',
        sideToMove:'w',
        board:(()=>{let b=mk();b[7][4]='wK';b[0][4]='bK';b[0][0]='bR';b[0][7]='bR';b[0][5]='bB';b[1][6]='bP';b[1][7]='bP';b[2][4]='wQ';b[5][3]='wR';return b;})(),
        solution:[{fr:2,fc:4,tr:0,tc:6},{fr:5,fc:3,tr:0,tc:3}]
    },
    {
        label:'Mate in 1', hint:'The queen strikes to the back rank.',
        sideToMove:'w',
        board:(()=>{let b=mk();b[7][6]='wK';b[0][5]='bK';b[0][3]='bP';b[0][7]='bP';b[1][5]='bP';b[3][5]='wQ';return b;})(),
        solution:[{fr:3,fc:5,tr:0,tc:5}]
    },
    {
        label:'Mate in 1', hint:'The rook delivers a back-rank mate.',
        sideToMove:'w',
        board:(()=>{let b=mk();b[7][6]='wK';b[0][4]='bK';b[0][3]='bP';b[0][5]='bP';b[1][4]='bP';b[2][3]='wR';return b;})(),
        solution:[{fr:2,fc:3,tr:0,tc:3}]
    },
    {
        label:'Mate in 1', hint:'Knight delivers a forking checkmate.',
        sideToMove:'w',
        board:(()=>{let b=mk();b[7][0]='wK';b[0][4]='bK';b[1][3]='bP';b[1][5]='bP';b[2][4]='bP';b[2][2]='wN';return b;})(),
        solution:[{fr:2,fc:2,tr:0,tc:3}]
    },
    {
        label:'Mate in 2', hint:'Drive the king into a corner.',
        sideToMove:'w',
        board:(()=>{let b=mk();b[7][4]='wK';b[1][4]='bK';b[3][3]='wQ';b[5][5]='wR';return b;})(),
        solution:[{fr:3,fc:3,tr:1,tc:5},{fr:5,fc:5,tr:1,tc:5}]
    },
    {
        label:'Mate in 1', hint:'The bishop seals the diagonal.',
        sideToMove:'w',
        board:(()=>{let b=mk();b[7][7]='wK';b[0][0]='bK';b[1][0]='bP';b[0][1]='bP';b[5][5]='wB';return b;})(),
        solution:[{fr:5,fc:5,tr:2,tc:2}]
    },
    {
        label:'Mate in 1', hint:'Queen checkmate on the open file.',
        sideToMove:'w',
        board:(()=>{let b=mk();b[7][0]='wK';b[0][7]='bK';b[0][6]='bP';b[1][7]='bP';b[4][7]='wQ';return b;})(),
        solution:[{fr:4,fc:7,tr:0,tc:7}]
    },
    {
        label:'Mate in 2', hint:'Rook lift then queen closes in.',
        sideToMove:'w',
        board:(()=>{let b=mk();b[7][4]='wK';b[0][3]='bK';b[0][2]='bP';b[0][4]='bP';b[1][3]='bP';b[6][0]='wR';b[3][5]='wQ';return b;})(),
        solution:[{fr:6,fc:0,tr:1,tc:0},{fr:3,fc:5,tr:0,tc:2}]
    },
    {
        label:'Mate in 1', hint:'Smothered mate — queen on h7.',
        sideToMove:'w',
        board:(()=>{let b=mk();b[7][4]='wK';b[0][6]='bK';b[0][7]='bR';b[1][6]='bP';b[1][7]='bP';b[3][7]='wQ';return b;})(),
        solution:[{fr:3,fc:7,tr:1,tc:7}]
    }
];

function mk(){return Array.from({length:8},()=>Array(8).fill(null));}

let puzzleIdx = <?= (int)$puzzleOfDay ?>;
let board, selected, step, moveCount, solveAttempts, solved, revealUsed;

const TODAY = new Date().toDateString();
let stats = JSON.parse(sessionStorage.getItem('pz_stats') || 'null') || {date:TODAY, solved:0, streak:0, attempts:0, correct:0};
if(stats.date !== TODAY) stats = {date:TODAY, solved:0, streak:0, attempts:0, correct:0};
function saveStats(){ sessionStorage.setItem('pz_stats', JSON.stringify(stats)); }
function renderStats(){
    document.getElementById('pz-solved-count').textContent = stats.solved;
    document.getElementById('pz-streak').textContent = '🔥 ' + stats.streak;
    const acc = stats.attempts > 0 ? Math.round((stats.correct / stats.attempts)*100) + '%' : '—';
    document.getElementById('pz-accuracy').textContent = acc;
}

function loadPuzzle(idx){
    const pz = PUZZLES[idx % PUZZLES.length];
    board   = pz.board.map(r=>[...r]);
    selected = null; step = 0; moveCount = 0; solveAttempts = 0; solved = false; revealUsed = false;
    document.getElementById('pz-badge').textContent = pz.label;
    document.getElementById('pz-hint').textContent  = pz.hint;
    document.getElementById('pz-status').textContent = '';
    document.getElementById('pz-status').style.color = '';
    document.getElementById('pz-turn-text').textContent = (pz.sideToMove==='w' ? 'WHITE' : 'BLACK') + ' TO MOVE';
    document.getElementById('pz-dot').style.background = pz.sideToMove==='w' ? '#FAF0DC' : '#1a0800';
    render();
}

function render(flashFrom, flashTo, flashType){
    ctx.clearRect(0,0,360,360);
    for(let r=0;r<8;r++) for(let c=0;c<8;c++){
        const x=c*SZ, y=(7-r)*SZ;
        ctx.fillStyle = (r+c)%2===0 ? LIGHT : DARK;
        ctx.fillRect(x,y,SZ,SZ);
        if(selected && selected.r===r && selected.c===c){ ctx.fillStyle=SEL; ctx.fillRect(x,y,SZ,SZ); }
        const isMoveTarget = (step < PUZZLES[puzzleIdx % PUZZLES.length].solution.length) &&
            selected && selected.r===PUZZLES[puzzleIdx % PUZZLES.length].solution[step].fr &&
            selected.c===PUZZLES[puzzleIdx % PUZZLES.length].solution[step].fc;
        if(isMoveTarget){ ctx.fillStyle=MOVE; ctx.beginPath(); ctx.arc(x+SZ/2,y+SZ/2,8,0,Math.PI*2); ctx.fill(); }
        if(flashFrom && flashFrom.r===r && flashFrom.c===c){ ctx.fillStyle=flashType==='ok'?CORRECT_SQ:WRONG_SQ; ctx.fillRect(x,y,SZ,SZ); }
        if(flashTo   && flashTo.r===r   && flashTo.c===c  ){ ctx.fillStyle=flashType==='ok'?CORRECT_SQ:WRONG_SQ; ctx.fillRect(x,y,SZ,SZ); }
        if(c===0){ ctx.fillStyle='rgba(212,130,74,0.55)'; ctx.font='bold 9px sans-serif'; ctx.textAlign='left'; ctx.textBaseline='top'; ctx.fillText(r+1,x+2,y+2); }
        const p=board[r][c];
        if(p){
            ctx.font=`${SZ*0.72}px serif`; ctx.textAlign='center'; ctx.textBaseline='middle';
            ctx.fillStyle='rgba(0,0,0,0.45)'; ctx.fillText(GLYPHS[p],x+SZ/2+1.5,y+SZ/2+2);
            ctx.fillStyle=p[0]==='w'?'#FAF0DC':'#0d0500'; ctx.fillText(GLYPHS[p],x+SZ/2,y+SZ/2);
        }
    }
}

function boardPos(ex,ey){
    const rect=canvas.getBoundingClientRect();
    const scale=360/rect.width;
    return { r: 7-Math.floor((ey-rect.top)*scale/SZ), c: Math.floor((ex-rect.left)*scale/SZ) };
}

canvas.addEventListener('click', e=>{
    if(solved || revealUsed) return;
    const {r,c} = boardPos(e.clientX, e.clientY);
    if(r<0||r>7||c<0||c>7) return;
    const pz = PUZZLES[puzzleIdx % PUZZLES.length];
    const move = pz.solution[step];
    const p = board[r][c];
    const side = pz.sideToMove;

    if(!selected){
        if(p && p[0]===side){ selected={r,c}; render(); }
        return;
    }
    if(selected.r===r && selected.c===c){ selected=null; render(); return; }

    if(selected.r===move.fr && selected.c===move.fc && r===move.tr && c===move.tc){
        board[r][c] = board[selected.r][selected.c];
        board[selected.r][selected.c] = null;
        const from = {...selected}, to = {r,c};
        selected = null; step++;
        render(from, to, 'ok');
        if(step >= pz.solution.length){
            solved = true;
            solveAttempts++;
            stats.attempts++;
            stats.correct++;
            stats.solved++;
            stats.streak++;
            saveStats(); renderStats();
            const st = document.getElementById('pz-status');
            st.textContent = '✓ Excellent! Puzzle solved.';
            st.style.color = '#4CAF82';
        } else {
            const st = document.getElementById('pz-status');
            st.textContent = '✓ Good move! Keep going…';
            st.style.color = '#4CAF82';
            setTimeout(()=>{ st.textContent=''; },1200);
        }
    } else if(p && p[0]===side){
        selected = {r,c}; render();
    } else {
        const from = {...selected}, to = {r,c};
        stats.attempts++;
        saveStats(); renderStats();
        selected = null;
        render(from, to, 'err');
        const st = document.getElementById('pz-status');
        st.textContent = '✗ Not quite — try again.';
        st.style.color = 'var(--loss-color)';
        setTimeout(()=>{ st.textContent=''; render(); },900);
    }
});

canvas.addEventListener('mousemove', e=>{
    if(solved || revealUsed){ canvas.style.cursor='default'; return; }
    const {r,c} = boardPos(e.clientX, e.clientY);
    const pz = PUZZLES[puzzleIdx % PUZZLES.length];
    const p  = board[r]?.[c];
    canvas.style.cursor = (p && p[0]===pz.sideToMove) ? 'grab' : 'default';
});

window.pzReset = function(){ loadPuzzle(puzzleIdx); };
window.pzNext  = function(){
    puzzleIdx = (puzzleIdx + 1) % PUZZLES.length;
    loadPuzzle(puzzleIdx);
};
window.pzReveal = function(){
    if(solved) return;
    revealUsed = true;
    const pz = PUZZLES[puzzleIdx % PUZZLES.length];
    const st = document.getElementById('pz-status');
    st.textContent = 'Answer revealed — try a new puzzle!';
    st.style.color = 'rgba(255,255,255,0.4)';

    let delay = 0;
    pz.solution.forEach(mv => {
        setTimeout(()=>{
            board[mv.tr][mv.tc] = board[mv.fr][mv.fc];
            board[mv.fr][mv.fc] = null;
            render({r:mv.fr,c:mv.fc},{r:mv.tr,c:mv.tc},'ok');
        }, delay);
        delay += 700;
    });
};

renderStats();
loadPuzzle(puzzleIdx);
})();
</script>
<?php endif; ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../script/service.js"></script>

<script>
Chart.register(ChartDataLabels);

document.addEventListener('DOMContentLoaded', function() {
    <?php if($isAdmin): ?>

        <?php endif; ?>
});
</script>

<script>
(function() {
    const canvas = document.getElementById('bgCanvas');
    const ctx    = canvas.getContext('2d');
    const pieces = ['♟','♜','♞','♝','♛','♚'];

    let particles = [];
    let W, H;

    function resize() {
        W = canvas.width  = window.innerWidth;
        H = canvas.height = window.innerHeight;
    }
    resize();
    window.addEventListener('resize', resize);

    for (let i = 0; i < 22; i++) {
        particles.push({
            x: Math.random() * 1400,
            y: Math.random() * 900,
            symbol: pieces[Math.floor(Math.random()*pieces.length)],
            size: 14 + Math.random() * 26,
            speed: 0.15 + Math.random() * 0.3,
            drift: (Math.random() - 0.5) * 0.18,
            alpha: 0.03 + Math.random() * 0.06,
            rot: Math.random() * Math.PI * 2,
            rotSpeed: (Math.random() - 0.5) * 0.005
        });
    }

    function draw() {

        const grad = ctx.createRadialGradient(W*0.5, H*0.4, 0, W*0.5, H*0.4, W*0.85);
        grad.addColorStop(0,   'rgba(40, 18, 8, 0.95)');
        grad.addColorStop(0.5, 'rgba(22, 9, 4, 0.97)');
        grad.addColorStop(1,   'rgba(12, 4, 2, 0.99)');
        ctx.fillStyle = grad;
        ctx.fillRect(0, 0, W, H);

        const orbs = [
            { x: W*0.2, y: H*0.3, r: W*0.28, c: 'rgba(107,58,42,0.07)' },
            { x: W*0.8, y: H*0.7, r: W*0.22, c: 'rgba(181,98,42,0.06)' },
            { x: W*0.5, y: H*0.85, r: W*0.2, c: 'rgba(212,130,74,0.04)' },
        ];
        orbs.forEach(o => {
            const g = ctx.createRadialGradient(o.x, o.y, 0, o.x, o.y, o.r);
            g.addColorStop(0, o.c);
            g.addColorStop(1, 'transparent');
            ctx.fillStyle = g;
            ctx.fillRect(0, 0, W, H);
        });

        ctx.textBaseline = 'middle';
        particles.forEach(p => {
            ctx.save();
            ctx.translate(p.x % W, p.y % H);
            ctx.rotate(p.rot);
            ctx.globalAlpha = p.alpha;
            ctx.fillStyle = '#E8A96A';
            ctx.font = `${p.size}px serif`;
            ctx.fillText(p.symbol, 0, 0);
            ctx.restore();

            p.y -= p.speed;
            p.x += p.drift;
            p.rot += p.rotSpeed;
            if (p.y < -50) { p.y = H + 50; p.x = Math.random() * W; }
            if (p.x < -50) p.x = W + 50;
            if (p.x > W + 50) p.x = -50;
        });

        requestAnimationFrame(draw);
    }
    draw();
})();
</script>

<script>
<?php
$tournamentComplete = false;
$podiumData = [];
try {
    $dbCheck = (new Database())->connectDB();
    $roundCount = (int)$dbCheck->query("SELECT COUNT(DISTINCT round_num) FROM tbl_pairing")->fetchColumn();
    $pendingCount = (int)$dbCheck->query("SELECT COUNT(*) FROM tbl_pairing WHERE status != 'FINISHED'")->fetchColumn();
    if ($roundCount >= 7 && $pendingCount === 0) {
        $tournamentComplete = true;
        $podiumRows = $dbCheck->query("
            SELECT p.firstName, p.lastName,
            (SELECT IFNULL(SUM(p1_score),0) FROM tbl_pairing WHERE player1_id = p.userID) +
            (SELECT IFNULL(SUM(p2_score),0) FROM tbl_pairing WHERE player2_id = p.userID) as total_pts
            FROM tbl_players p
            ORDER BY total_pts DESC LIMIT 3
        ")->fetchAll(PDO::FETCH_ASSOC);
        $podiumData = $podiumRows;
    }
} catch(Exception $e) {}
?>

const TOURNAMENT_COMPLETE = <?= $tournamentComplete ? 'true' : 'false' ?>;
const PODIUM_DATA = <?= json_encode($podiumData) ?>;

const confettiCanvas = document.getElementById('confettiCanvas');
const cctx = confettiCanvas.getContext('2d');
let confettiParticles = [];
let confettiRunning = false;

function resizeConfetti() {
    confettiCanvas.width  = window.innerWidth;
    confettiCanvas.height = window.innerHeight;
}
resizeConfetti();
window.addEventListener('resize', resizeConfetti);

function makeConfettiParticle() {
    const colors = ['#D4A843','#E8A96A','#F5C98A','#ffffff','#B5622A','#4CAF82','#E05050','#6B8FD4'];
    return {
        x: Math.random() * confettiCanvas.width,
        y: -10 - Math.random() * 100,
        w: 6 + Math.random() * 10,
        h: 4 + Math.random() * 6,
        color: colors[Math.floor(Math.random() * colors.length)],
        rot: Math.random() * Math.PI * 2,
        rotSpeed: (Math.random() - 0.5) * 0.18,
        vx: (Math.random() - 0.5) * 3,
        vy: 2 + Math.random() * 3.5,
        alpha: 1,
        life: 1
    };
}

function spawnBurst() {
    for (let i = 0; i < 18; i++) confettiParticles.push(makeConfettiParticle());
}

function animateConfetti() {
    if (!confettiRunning) return;
    cctx.clearRect(0, 0, confettiCanvas.width, confettiCanvas.height);
    confettiParticles = confettiParticles.filter(p => p.alpha > 0.05);
    confettiParticles.forEach(p => {
        p.x += p.vx; p.y += p.vy; p.rot += p.rotSpeed;
        if (p.y > confettiCanvas.height * 0.8) p.alpha -= 0.018;
        cctx.save();
        cctx.translate(p.x, p.y); cctx.rotate(p.rot);
        cctx.globalAlpha = p.alpha;
        cctx.fillStyle = p.color;
        cctx.fillRect(-p.w/2, -p.h/2, p.w, p.h);
        cctx.restore();
    });
    requestAnimationFrame(animateConfetti);
}

function startConfetti() {
    confettiRunning = true;
    animateConfetti();
    let bursts = 0;
    const interval = setInterval(() => {
        spawnBurst(); bursts++;
        if (bursts >= 30) clearInterval(interval);
    }, 180);

    setTimeout(() => { confettiRunning = false; cctx.clearRect(0,0,confettiCanvas.width,confettiCanvas.height); }, 8000);
}

function buildPodium() {
    const stage = document.getElementById('podiumStage');
    if (!PODIUM_DATA || PODIUM_DATA.length === 0) return;

    const order  = [1, 0, 2];
    const classes = ['second','first','third'];
    const medals  = ['🥈','🥇','🥉'];

    order.forEach((dataIdx, stagePos) => {
        const p = PODIUM_DATA[dataIdx];
        if (!p) return;
        const cls = classes[stagePos];
        const initial = (p.firstName || '?')[0].toUpperCase();
        const div = document.createElement('div');
        div.className = `podium-place ${cls}`;
        div.innerHTML = `
            <div class="podium-avatar">${initial}</div>
            <div class="podium-name">${p.firstName.toUpperCase()}</div>
            <div class="podium-pts">${parseFloat(p.total_pts).toFixed(1)} pts</div>
            <div class="podium-block">${medals[stagePos]}</div>
        `;
        stage.appendChild(div);
    });
}

function closePodium() {
    document.getElementById('podiumOverlay').classList.remove('visible');
    confettiRunning = false;
    cctx.clearRect(0,0,confettiCanvas.width,confettiCanvas.height);
}

if (TOURNAMENT_COMPLETE) {
    window.addEventListener('load', () => {
        setTimeout(() => {
            buildPodium();
            document.getElementById('podiumOverlay').classList.add('visible');
            startConfetti();
        }, 800);
    });
}

window.addEventListener('load', () => {
    document.querySelectorAll('.stat-num').forEach(el => {
        const raw = parseInt(el.textContent.replace(/\D/g,''));
        if (isNaN(raw) || raw === 0) return;
        el.textContent = '0';
        let cur = 0;
        const step = Math.max(1, Math.ceil(raw / 40));
        const tick = setInterval(() => {
            cur = Math.min(cur + step, raw);
            el.textContent = cur;
            if (cur >= raw) clearInterval(tick);
        }, 28);
    });
});

document.querySelectorAll('.pairing-row').forEach(row => {
    const cells = row.querySelectorAll('.player-cell');
    const scores = row.querySelectorAll('.score-display');
    if (scores.length === 2 && cells.length >= 2) {
        const s1 = parseFloat(scores[0]?.textContent) || 0;
        const s2 = parseFloat(scores[1]?.textContent) || 0;
        if (s1 > s2) cells[0].style.color = '#8fbc45';
        else if (s2 > s1) cells[1].style.color = '#8fbc45';
    }
});

document.querySelectorAll('.rank-num').forEach((el, i) => {
    el.style.opacity = '0';
    el.style.transform = 'translateX(-8px)';
    el.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
    setTimeout(() => {
        el.style.opacity = '1';
        el.style.transform = 'translateX(0)';
    }, 80 + i * 55);
});
</script>
<script src="../script/fx.js"></script>
</body>
</html>