<?php
require_once "../model/database.php";
$db = (new Database())->connectDB();

$totalPlayers  = (int)$db->query("SELECT COUNT(*) FROM tbl_players")->fetchColumn();
$completedRnds = (int)$db->query("SELECT COUNT(DISTINCT round_num) FROM tbl_pairing WHERE round_num NOT IN (SELECT DISTINCT round_num FROM tbl_pairing WHERE status != 'FINISHED')")->fetchColumn();
$totalMatches  = (int)$db->query("SELECT COUNT(*) FROM tbl_pairing WHERE status = 'FINISHED'")->fetchColumn();
$totalRounds   = 7;

$standings = $db->query("
    SELECT p.userID, p.firstName, p.lastName, p.gender, p.rating,
        IFNULL((SELECT SUM(p1_score) FROM tbl_pairing WHERE player1_id = p.userID), 0) +
        IFNULL((SELECT SUM(p2_score) FROM tbl_pairing WHERE player2_id = p.userID), 0) AS total_pts,
        (SELECT COUNT(*) FROM tbl_pairing WHERE (player1_id = p.userID OR player2_id = p.userID) AND status = 'FINISHED') AS games_played,
        (SELECT COUNT(*) FROM tbl_pairing WHERE winner_id = p.userID) AS wins,
        (SELECT COUNT(*) FROM tbl_pairing WHERE (player1_id = p.userID OR player2_id = p.userID) AND status = 'FINISHED' AND p1_score = p2_score) AS draws
    FROM tbl_players p
    ORDER BY total_pts DESC, p.rating DESC
")->fetchAll(PDO::FETCH_ASSOC);

if ($totalMatches === 0 && $totalPlayers === 0) {
    $statusLabel = 'Not Yet Started';
    $statusColor = 'rgba(255,255,255,0.3)';
    $statusDot   = '#888';
} elseif ($completedRnds >= $totalRounds) {
    $statusLabel = 'Tournament Completed';
    $statusColor = '#F0C86A';
    $statusDot   = '#F0C86A';
} elseif ($completedRnds > 0 || $totalMatches > 0) {
    $statusLabel = "Round {$completedRnds} of {$totalRounds} — In Progress";
    $statusColor = '#7FD18A';
    $statusDot   = '#7FD18A';
} else {
    $statusLabel = 'Registered — Awaiting First Round';
    $statusColor = 'var(--caramel)';
    $statusDot   = '#E8A96A';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Standings | Miffy Chess Cup 2026</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700;900&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
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
            --glass-bg:     rgba(30, 14, 8, 0.55);
            --glass-border: rgba(212, 130, 74, 0.25);
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'DM Sans', sans-serif;
            min-height: 100vh;
            color: white;
            overflow-x: hidden;
            background: #0A0301;
        }
        #bgCanvas { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 0; }
        .grid-overlay {
            position: fixed; inset: 0; z-index: 1; pointer-events: none;
            background-image:
                linear-gradient(rgba(212,130,74,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(212,130,74,0.03) 1px, transparent 1px);
            background-size: 60px 60px;
            mask-image: radial-gradient(ellipse 80% 80% at 50% 50%, black 40%, transparent 100%);
        }
        .scanlines {
            position: fixed; inset: 0; z-index: 2; pointer-events: none;
            background: repeating-linear-gradient(0deg, transparent, transparent 2px, rgba(0,0,0,0.03) 2px, rgba(0,0,0,0.03) 4px);
        }
        nav, main, footer { position: relative; z-index: 10; }

        nav {
            display: flex; align-items: center; justify-content: space-between;
            padding: 20px 40px;
            background: rgba(10, 3, 1, 0.75);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(212,130,74,0.15);
            position: sticky; top: 0; z-index: 100;
        }
        .brand { display: flex; align-items: center; gap: 14px; text-decoration: none; }
        .brand-logo {
            width: 46px; height: 46px; border-radius: 50%;
            border: 2px solid var(--caramel); background: white; padding: 3px;
            object-fit: contain;
        }
        .brand-name { font-family: 'Cinzel', serif; font-size: 1.2rem; font-weight: 700; color: var(--cream); letter-spacing: 2px; }
        .nav-right { display: flex; align-items: center; gap: 10px; }
        .btn-back {
            padding: 8px 20px; border: 1px solid rgba(255,255,255,0.2); border-radius: 50px;
            color: rgba(255,255,255,0.65); font-size: 12px; font-family: 'DM Sans', sans-serif;
            letter-spacing: 1px; text-decoration: none; text-transform: uppercase;
            transition: 0.2s; display: flex; align-items: center; gap: 6px;
        }
        .btn-back:hover { border-color: var(--caramel); color: var(--caramel); }
        .btn-login-nav {
            padding: 8px 20px; background: var(--mocha-light); border: none; border-radius: 50px;
            color: white; font-size: 12px; font-family: 'DM Sans', sans-serif;
            letter-spacing: 1px; text-decoration: none; text-transform: uppercase;
            transition: 0.2s; display: flex; align-items: center; gap: 6px;
        }
        .btn-login-nav:hover { background: var(--mocha-glow); }

        main { max-width: 900px; margin: 0 auto; padding: 50px 24px 80px; }

        .page-header { text-align: center; margin-bottom: 40px; }
        .page-eyebrow {
            font-size: 10px; letter-spacing: 4px; text-transform: uppercase;
            color: var(--caramel); margin-bottom: 12px;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .page-title {
            font-family: 'Cinzel', serif; font-size: clamp(1.6rem, 4vw, 2.4rem);
            font-weight: 700; color: var(--cream); letter-spacing: 3px; margin-bottom: 16px;
        }
        .page-title span { color: var(--caramel); }

        .status-badge {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 8px 20px; border-radius: 50px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            font-size: 12px; letter-spacing: 1.5px; text-transform: uppercase;
        }
        .status-dot {
            width: 7px; height: 7px; border-radius: 50%;
            background: <?= $statusDot ?>;
            box-shadow: 0 0 6px <?= $statusDot ?>;
            animation: pulse 2s ease-in-out infinite;
        }
        @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.4} }

        .stats-row {
            display: flex; gap: 16px; margin-bottom: 32px; flex-wrap: wrap;
        }
        .stat-card {
            flex: 1; min-width: 120px;
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 16px; padding: 20px 18px; text-align: center;
            backdrop-filter: blur(12px);
        }
        .stat-num { font-family: 'Cinzel', serif; font-size: 1.8rem; color: var(--caramel); font-weight: 700; }
        .stat-lbl { font-size: 10px; letter-spacing: 2px; text-transform: uppercase; color: rgba(255,255,255,0.35); margin-top: 4px; }

        .table-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 20px; overflow: hidden;
            backdrop-filter: blur(12px);
        }
        .table-card-header {
            padding: 20px 24px 16px;
            border-bottom: 1px solid rgba(212,130,74,0.12);
            display: flex; align-items: center; gap: 10px;
        }
        .table-card-title { font-family: 'Cinzel', serif; font-size: 13px; letter-spacing: 2px; color: var(--caramel); text-transform: uppercase; }
        .table-card-sub { font-size: 11px; color: rgba(255,255,255,0.3); letter-spacing: 1px; margin-left: auto; }

        table { width: 100%; border-collapse: collapse; }
        thead th {
            font-size: 10px; font-weight: 700; letter-spacing: 2px;
            text-transform: uppercase; color: var(--caramel);
            padding: 0 16px 14px; text-align: left;
            border-bottom: 1px solid rgba(212,130,74,0.2);
        }
        tbody tr { border-bottom: 1px solid rgba(255,255,255,0.04); transition: background 0.15s; }
        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover td { background: rgba(212,130,74,0.04); }
        td { padding: 13px 16px; font-size: 13px; vertical-align: middle; }

        .rank-cell { font-family: 'Cinzel', serif; font-size: 13px; font-weight: 700; width: 44px; }
        .medal-gold   { color: #F0C86A; }
        .medal-silver { color: #C0C0C0; }
        .medal-bronze { color: #CD7F32; }

        .player-name { font-weight: 600; letter-spacing: 0.5px; color: rgba(255,255,255,0.92); }
        .player-sub  { font-size: 10px; color: rgba(255,255,255,0.3); letter-spacing: 1px; margin-top: 2px; }

        .pts-badge {
            display: inline-block; padding: 4px 10px;
            background: rgba(212,130,74,0.15); border: 1px solid rgba(212,130,74,0.3);
            border-radius: 20px; font-size: 13px; font-weight: 600; color: var(--caramel);
        }
        .pts-zero { background: rgba(255,255,255,0.04); border-color: rgba(255,255,255,0.08); color: rgba(255,255,255,0.25); }

        .bar-wrap { width: 90px; }
        .bar-bg { height: 4px; background: rgba(255,255,255,0.07); border-radius: 4px; overflow: hidden; }
        .bar-fill { height: 100%; background: linear-gradient(90deg, var(--mocha-light), var(--caramel)); border-radius: 4px; transition: width 1s ease; }

        .muted { color: rgba(255,255,255,0.35); font-size: 12px; }

        .empty-state { text-align: center; padding: 60px 20px; }
        .empty-icon { font-size: 3rem; opacity: 0.15; margin-bottom: 16px; }
        .empty-title { font-family: 'Cinzel', serif; font-size: 14px; letter-spacing: 2px; color: rgba(255,255,255,0.25); margin-bottom: 8px; }
        .empty-sub { font-size: 12px; color: rgba(255,255,255,0.15); letter-spacing: 1px; }

        footer {
            text-align: center; padding: 32px;
            border-top: 1px solid rgba(212,130,74,0.1);
            color: rgba(255,255,255,0.18); font-size: 11px; letter-spacing: 2px;
            text-transform: uppercase;
        }

        .notice-card {
            background: rgba(212,130,74,0.06); border: 1px solid rgba(212,130,74,0.2);
            border-radius: 16px; padding: 24px 28px; margin-bottom: 28px;
            display: flex; align-items: flex-start; gap: 14px;
        }
        .notice-icon { color: var(--caramel); font-size: 20px; margin-top: 2px; flex-shrink: 0; }
        .notice-text { font-size: 13px; color: rgba(255,255,255,0.55); line-height: 1.7; }
        .notice-text strong { color: var(--caramel); }

        @media (max-width: 600px) {
            nav { padding: 16px 18px; }
            main { padding: 32px 14px 60px; }
            .brand-name { font-size: 1rem; }
            .stats-row { gap: 10px; }
            .bar-wrap { display: none; }
            td { padding: 11px 10px; }
            thead th { padding: 0 10px 12px; }
        }
    </style>
</head>
<body>

<canvas id="bgCanvas"></canvas>
<div class="grid-overlay"></div>
<div class="scanlines"></div>

<div class="fx-cursor-dot" id="dot1"></div>
<div class="fx-cursor-dot" id="dot2"></div>
<div class="fx-cursor-dot" id="dot3"></div>

<nav>
    <a class="brand" href="miffytourna.php">
        <img src="../assets/miffy.jpg" class="brand-logo" alt="Miffy">
        <span class="brand-name">MIFFY CHESS</span>
    </a>
    <div class="nav-right">
        <a href="miffytourna.php" class="btn-back">
            <i class="material-icons" style="font-size:15px;">arrow_back</i>
            Back
        </a>
        <a href="loginpage.php" class="btn-login-nav">
            <i class="material-icons" style="font-size:15px;">login</i>
            Player Login
        </a>
    </div>
</nav>

<main>

    <div class="page-header">
        <div class="page-eyebrow">
            <i class="material-icons" style="font-size:14px;">emoji_events</i>
            Miffy Chess Cup · Tournament 2026
        </div>
        <h1 class="page-title">LIVE <span>STANDINGS</span></h1>
        <div class="status-badge">
            <span class="status-dot"></span>
            <span style="color:<?= $statusColor ?>;"><?= $statusLabel ?></span>
        </div>
    </div>

    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-num"><?= $totalPlayers ?></div>
            <div class="stat-lbl">Players</div>
        </div>
        <div class="stat-card">
            <div class="stat-num"><?= $completedRnds ?> / <?= $totalRounds ?></div>
            <div class="stat-lbl">Rounds Done</div>
        </div>
        <div class="stat-card">
            <div class="stat-num"><?= $totalMatches ?></div>
            <div class="stat-lbl">Matches Played</div>
        </div>
    </div>

    <?php if ($totalPlayers === 0): ?>

    <div class="notice-card">
        <i class="material-icons notice-icon">info</i>
        <div class="notice-text">
            <strong>Tournament registration is open.</strong> No players have been registered yet.
            Check back soon or register to participate in the Miffy Chess Cup 2026.
        </div>
    </div>
    <?php elseif ($totalMatches === 0): ?>

    <div class="notice-card">
        <i class="material-icons notice-icon">schedule</i>
        <div class="notice-text">
            <strong><?= $totalPlayers ?> player<?= $totalPlayers > 1 ? 's' : '' ?> registered.</strong>
            The tournament hasn't started yet — pairings for Round 1 haven't been generated.
            The standings below show pre-tournament rankings by FIDE rating.
        </div>
    </div>
    <?php endif; ?>

    <div class="table-card">
        <div class="table-card-header">
            <i class="material-icons" style="color:var(--caramel);font-size:18px;">leaderboard</i>
            <span class="table-card-title">Full Standings</span>
            <?php if (!empty($standings)): ?>
            <span class="table-card-sub">Updated live</span>
            <?php endif; ?>
        </div>

        <?php if (empty($standings)): ?>
        <div class="empty-state">
            <div class="empty-icon">♟</div>
            <div class="empty-title">No Players Yet</div>
            <div class="empty-sub">Standings will appear once players are registered</div>
        </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Player</th>
                    <th>Pts</th>
                    <th>W</th>
                    <th>D</th>
                    <th>GP</th>
                    <th>Rating</th>
                    <th class="bar-wrap"></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $maxPts = max(array_column($standings, 'total_pts'));
                if ($maxPts == 0) $maxPts = 1;
                $rank = 1;
                foreach ($standings as $s):
                    $pct = round($s['total_pts'] / $maxPts * 100);
                    $medalClass = $rank == 1 ? 'medal-gold' : ($rank == 2 ? 'medal-silver' : ($rank == 3 ? 'medal-bronze' : ''));
                    $isZero = $s['total_pts'] == 0;
                ?>
                <tr>
                    <td class="rank-cell <?= $medalClass ?>"><?= $rank++ ?></td>
                    <td>
                        <div class="player-name"><?= strtoupper(htmlspecialchars($s['firstName'])) ?> <?= strtoupper(htmlspecialchars($s['lastName'])) ?></div>
                        <div class="player-sub"><?= htmlspecialchars($s['gender']) ?></div>
                    </td>
                    <td><span class="pts-badge <?= $isZero ? 'pts-zero' : '' ?>"><?= $s['total_pts'] ?></span></td>
                    <td class="muted"><?= $s['wins'] ?></td>
                    <td class="muted"><?= $s['draws'] ?></td>
                    <td class="muted"><?= $s['games_played'] ?></td>
                    <td class="muted"><?= $s['rating'] ?></td>
                    <td class="bar-wrap">
                        <div class="bar-bg">
                            <div class="bar-fill" style="width:<?= $pct ?>%;"></div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

</main>

<footer>
    ♟ Miffy Chess Cup 2026 &nbsp;·&nbsp; Public Standings
</footer>

<script src="../script/fx.js"></script>
<script>
(function () {

    const canvas = document.getElementById('bgCanvas');
    const ctx    = canvas.getContext('2d');
    const pieces = ['♟','♜','♞','♝','♛','♚'];
    let particles = [], W, H, time = 0;

    function resize() { W = canvas.width = window.innerWidth; H = canvas.height = window.innerHeight; }
    resize(); window.addEventListener('resize', resize);

    for (let i = 0; i < 18; i++) particles.push({
        x: Math.random() * 1400, y: Math.random() * 900,
        symbol: pieces[Math.floor(Math.random() * 6)],
        size: 12 + Math.random() * 20, speed: 0.1 + Math.random() * 0.22,
        drift: (Math.random() - 0.5) * 0.15, alpha: 0.02 + Math.random() * 0.05,
        rot: Math.random() * Math.PI * 2, rotSpeed: (Math.random() - 0.5) * 0.004,
        wave: Math.random() * Math.PI * 2, waveAmp: 0.3 + Math.random() * 0.5
    });

    function draw() {
        time += 0.004;
        const g = ctx.createRadialGradient(W*.5, H*.4, 0, W*.5, H*.4, W*.85);
        g.addColorStop(0,'rgba(38,17,7,.97)'); g.addColorStop(.55,'rgba(20,8,3,.98)'); g.addColorStop(1,'rgba(10,3,1,1)');
        ctx.fillStyle = g; ctx.fillRect(0, 0, W, H);

        [{x:W*(.2+.08*Math.sin(time*.7)),y:H*(.25+.07*Math.cos(time*.5)),r:W*.3,c:`rgba(107,58,42,${.05+.02*Math.sin(time)})`},
         {x:W*(.82+.06*Math.cos(time*.6)),y:H*(.65+.08*Math.sin(time*.4)),r:W*.25,c:`rgba(181,98,42,${.04+.015*Math.cos(time*1.2)})`}
        ].forEach(o => {
            const og = ctx.createRadialGradient(o.x,o.y,0,o.x,o.y,o.r);
            og.addColorStop(0,o.c); og.addColorStop(1,'transparent');
            ctx.fillStyle=og; ctx.fillRect(0,0,W,H);
        });

        ctx.textBaseline='middle';
        particles.forEach(p => {
            p.wave += 0.018;
            ctx.save();
            ctx.translate(p.x%W, p.y%H); ctx.rotate(p.rot);
            ctx.globalAlpha = p.alpha * (0.7 + 0.3*Math.sin(p.wave));
            ctx.fillStyle='#E8A96A'; ctx.font=`${p.size}px serif`;
            ctx.fillText(p.symbol,0,0); ctx.restore();
            p.y -= p.speed; p.x += p.drift + Math.sin(p.wave)*p.waveAmp; p.rot += p.rotSpeed;
            if (p.y < -60) { p.y = H+60; p.x = Math.random()*W; }
            if (p.x < -60) p.x = W+60; if (p.x > W+60) p.x = -60;
        });
        requestAnimationFrame(draw);
    }
    draw();
})();
</script>
</body>
</html>
