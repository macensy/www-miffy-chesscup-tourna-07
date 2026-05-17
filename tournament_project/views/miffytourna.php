<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Miffy Chess Cup | Tournament 2026</title>
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
            position: relative;
            background: #0A0301;
        }

        #bgCanvas {
            position: fixed; top: 0; left: 0;
            width: 100%; height: 100%;
            z-index: 0;
        }

        .grid-overlay {
            position: fixed; inset: 0;
            z-index: 1;
            pointer-events: none;
            background-image:
                linear-gradient(rgba(212,130,74,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(212,130,74,0.03) 1px, transparent 1px);
            background-size: 60px 60px;
            mask-image: radial-gradient(ellipse 80% 80% at 50% 50%, black 40%, transparent 100%);
        }

        .scanlines {
            position: fixed; inset: 0; z-index: 2;
            pointer-events: none;
            background: repeating-linear-gradient(
                0deg,
                transparent,
                transparent 2px,
                rgba(0,0,0,0.03) 2px,
                rgba(0,0,0,0.03) 4px
            );
        }

        nav, .hero, .cards-section, footer, .watermark { position: relative; z-index: 10; }

        nav {
            display: flex; align-items: center; justify-content: space-between;
            padding: 24px 40px;
            background: rgba(10, 3, 1, 0.7);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(212,130,74,0.15);
            position: sticky; top: 0; z-index: 100;
        }
        .brand { display: flex; align-items: center; gap: 14px; text-decoration: none; }
        .brand-logo {
            width: 52px; height: 52px; border-radius: 50%;
            border: 2px solid var(--caramel);
            background: white; padding: 3px;
            object-fit: contain;
            animation: logoPulse 3s ease-in-out infinite;
        }
        @keyframes logoPulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(232,169,106,0); }
            50% { box-shadow: 0 0 0 8px rgba(232,169,106,0.12); }
        }
        .brand-name {
            font-family: 'Cinzel', serif;
            font-size: 1.3rem; font-weight: 700;
            color: var(--cream); letter-spacing: 2px;
        }
        .nav-links { display: flex; align-items: center; gap: 10px; }
        .btn-nav-outline {
            padding: 8px 22px;
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 50px;
            color: rgba(255,255,255,0.65);
            font-family: 'DM Sans', sans-serif;
            font-size: 12px; font-weight: 500;
            letter-spacing: 1.5px; text-transform: uppercase;
            text-decoration: none; transition: 0.2s;
        }
        .btn-nav-outline:hover { border-color: var(--caramel); color: var(--caramel); }
        .btn-nav-caramel {
            padding: 8px 22px;
            border: 1px solid var(--caramel);
            background: rgba(212, 130, 74, 0.08);
            border-radius: 50px;
            color: var(--caramel);
            font-family: 'DM Sans', sans-serif;
            font-size: 12px; font-weight: 700;
            letter-spacing: 1.5px; text-transform: uppercase;
            text-decoration: none; transition: 0.2s;
        }
        .btn-nav-caramel:hover { background: rgba(212,130,74,0.2); }

        .hero {
            min-height: 88vh;
            display: flex; flex-direction: column;
            justify-content: center; align-items: center;
            text-align: center;
            padding: 80px 24px 60px;
            position: relative;
        }

        .hero-ring {
            position: absolute;
            width: 700px; height: 700px;
            border-radius: 50%;
            border: 1px solid rgba(212,130,74,0.08);
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            animation: ringRotate 40s linear infinite;
            pointer-events: none;
        }
        .hero-ring::before {
            content: '';
            position: absolute; inset: 30px;
            border-radius: 50%;
            border: 1px dashed rgba(212,130,74,0.06);
            animation: ringRotate 25s linear infinite reverse;
        }
        .hero-ring::after {
            content: '';
            position: absolute; inset: 80px;
            border-radius: 50%;
            border: 1px solid rgba(212,130,74,0.04);
        }
        .hero-ring-2 {
            position: absolute;
            width: 900px; height: 900px;
            border-radius: 50%;
            border: 1px solid rgba(212,130,74,0.04);
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            animation: ringRotate 60s linear infinite reverse;
            pointer-events: none;
        }
        @keyframes ringRotate {
            from { transform: translate(-50%, -50%) rotate(0deg); }
            to   { transform: translate(-50%, -50%) rotate(360deg); }
        }

        .orbital {
            position: absolute;
            width: 700px; height: 700px;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            animation: ringRotate 30s linear infinite;
            pointer-events: none;
        }
        .orbital-piece {
            position: absolute;
            font-size: 1.4rem;
            color: var(--caramel);
            opacity: 0.25;
        }
        .orbital-piece:nth-child(1) { top: -16px; left: 50%; transform: translateX(-50%); }
        .orbital-piece:nth-child(2) { top: 50%; right: -16px; transform: translateY(-50%); }
        .orbital-piece:nth-child(3) { bottom: -16px; left: 50%; transform: translateX(-50%); }
        .orbital-piece:nth-child(4) { top: 50%; left: -16px; transform: translateY(-50%); }
        .orbital-piece:nth-child(5) { top: 10%; right: 10%; }
        .orbital-piece:nth-child(6) { bottom: 10%; left: 10%; }

        .hero-badge {
            display: inline-flex; align-items: center; gap: 8px;
            border: 1px solid rgba(212,130,74,0.35);
            background: rgba(212,130,74,0.07);
            padding: 7px 20px; border-radius: 50px;
            font-size: 11px; font-weight: 600;
            letter-spacing: 3px; text-transform: uppercase;
            color: var(--caramel); margin-bottom: 36px;
            animation: fadeSlideDown 0.8s ease both;
            backdrop-filter: blur(10px);
        }
        .badge-dot {
            width: 6px; height: 6px; border-radius: 50%;
            background: var(--caramel);
            animation: dotPulse 1.5s ease-in-out infinite;
        }
        @keyframes dotPulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(0.7); }
        }

        .hero-title {
            font-family: 'Cinzel', serif;
            font-size: clamp(3rem, 9vw, 7rem);
            font-weight: 900;
            line-height: 0.9;
            letter-spacing: -1px;
            color: var(--cream);
            margin-bottom: 12px;
            animation: fadeSlideUp 0.9s 0.15s ease both;
        }
        .hero-title .accent {
            display: block;
            background: linear-gradient(135deg, var(--caramel), var(--gold), var(--caramel-lt));
            background-size: 200% 200%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: shimmer 4s ease-in-out infinite;
        }
        @keyframes shimmer {
            0%, 100% { background-position: 0% 50%; }
            50%       { background-position: 100% 50%; }
        }

        .hero-title:hover .accent {
            animation: glitch 0.3s steps(2) both, shimmer 4s ease-in-out infinite;
        }
        @keyframes glitch {
            0%  { text-shadow: 2px 0 #ff4444, -2px 0 #4444ff; }
            33% { text-shadow: -2px 0 #ff4444, 2px 0 #4444ff; }
            66% { text-shadow: 2px 2px #ff4444, -2px -2px #4444ff; }
            100%{ text-shadow: none; }
        }

        .hero-sub {
            font-size: 1.05rem; font-weight: 300;
            color: rgba(255,255,255,0.45);
            max-width: 520px; line-height: 1.75;
            margin: 28px auto 44px;
            letter-spacing: 0.3px;
            animation: fadeSlideUp 0.9s 0.3s ease both;
        }
        .hero-cta {
            display: flex; gap: 14px; justify-content: center; flex-wrap: wrap;
            animation: fadeSlideUp 0.9s 0.45s ease both;
        }

        .floating-stats {
            display: flex; gap: 40px;
            margin-top: 64px;
            animation: fadeSlideUp 0.9s 0.6s ease both;
        }
        .stat-item { text-align: center; }
        .stat-num {
            font-family: 'Cinzel', serif;
            font-size: 2rem; font-weight: 700;
            color: var(--caramel);
            display: block;
            animation: countUp 1.5s 1s ease both;
        }
        .stat-label {
            font-size: 10px; letter-spacing: 2px;
            text-transform: uppercase;
            color: rgba(255,255,255,0.3);
            margin-top: 4px; display: block;
        }
        .stat-divider { width: 1px; background: rgba(212,130,74,0.2); }

        @keyframes fadeSlideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeSlideUp {
            from { opacity: 0; transform: translateY(30px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .btn-primary {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 16px 40px;
            background: linear-gradient(135deg, var(--mocha-light), var(--mocha-glow));
            color: white; border: none; border-radius: 12px;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px; font-weight: 700;
            letter-spacing: 1.5px; text-transform: uppercase;
            text-decoration: none;
            box-shadow: 0 8px 28px rgba(181, 98, 42, 0.35);
            transition: 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative; overflow: hidden;
        }
        .btn-primary::after {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(135deg, transparent 30%, rgba(255,255,255,0.15) 50%, transparent 70%);
            transform: translateX(-100%);
            transition: transform 0.5s ease;
        }
        .btn-primary:hover::after { transform: translateX(100%); }
        .btn-primary:hover { transform: translateY(-3px); box-shadow: 0 14px 36px rgba(181,98,42,0.5); color: white; }

        .btn-secondary {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 16px 40px;
            border: 1px solid rgba(212,130,74,0.3);
            background: rgba(30,14,8,0.5);
            backdrop-filter: blur(10px);
            color: rgba(255,255,255,0.6);
            border-radius: 12px;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px; font-weight: 500;
            letter-spacing: 1.5px; text-transform: uppercase;
            text-decoration: none; transition: 0.25s;
        }
        .btn-secondary:hover { border-color: var(--caramel); color: var(--caramel); background: rgba(212,130,74,0.05); }

        .marquee-wrap {
            overflow: hidden;
            border-top: 1px solid rgba(212,130,74,0.1);
            border-bottom: 1px solid rgba(212,130,74,0.1);
            background: rgba(10,3,1,0.6);
            padding: 14px 0;
            margin-bottom: 80px;
        }
        .marquee-track {
            display: flex; gap: 0;
            animation: marqueeScroll 25s linear infinite;
            white-space: nowrap;
        }
        .marquee-item {
            display: inline-flex; align-items: center; gap: 16px;
            padding: 0 32px;
            font-size: 11px; letter-spacing: 3px; text-transform: uppercase;
            color: rgba(212,130,74,0.5); font-weight: 600;
            flex-shrink: 0;
        }
        .marquee-dot { width: 4px; height: 4px; border-radius: 50%; background: rgba(212,130,74,0.35); }
        @keyframes marqueeScroll {
            from { transform: translateX(0); }
            to   { transform: translateX(-50%); }
        }

        .section-divider {
            display: flex; align-items: center; gap: 20px;
            max-width: 300px; margin: 0 auto 60px;
        }
        .section-divider::before, .section-divider::after {
            content: ''; flex: 1; height: 1px;
            background: linear-gradient(90deg, transparent, rgba(212,130,74,0.4));
        }
        .section-divider::after {
            background: linear-gradient(90deg, rgba(212,130,74,0.4), transparent);
        }
        .section-divider span {
            font-size: 18px; opacity: 0.5; color: var(--caramel);
        }

        .cards-section {
            max-width: 1060px; margin: 0 auto;
            padding: 0 24px 100px;
        }
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }
        .info-card {
            background: rgba(20, 9, 4, 0.7);
            border: 1px solid rgba(212,130,74,0.15);
            border-radius: 18px;
            padding: 32px 28px;
            backdrop-filter: blur(16px);
            transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative; overflow: hidden;
            opacity: 0; transform: translateY(40px);
        }
        .info-card.visible {
            opacity: 1; transform: translateY(0);
            transition: opacity 0.6s ease, transform 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275), border-color 0.3s, box-shadow 0.3s;
        }
        .info-card:nth-child(2) { transition-delay: 0.1s; }
        .info-card:nth-child(3) { transition-delay: 0.2s; }

        .info-card::before {
            content: '';
            position: absolute; top: 0; left: -100%; right: 100%;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--caramel), transparent);
            transition: left 0.4s ease, right 0.4s ease;
        }
        .info-card:hover::before { left: 0; right: 0; }

        .info-card::after {
            content: '';
            position: absolute; bottom: -60px; right: -60px;
            width: 140px; height: 140px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(212,130,74,0.07), transparent 70%);
            transition: 0.4s;
        }
        .info-card:hover { border-color: rgba(212,130,74,0.4); transform: translateY(-8px); box-shadow: 0 24px 60px rgba(0,0,0,0.4), 0 0 40px rgba(212,130,74,0.06); }
        .info-card:hover::after { bottom: -20px; right: -20px; transform: scale(1.5); }

        .card-icon {
            width: 52px; height: 52px; border-radius: 14px;
            background: rgba(181,98,42,0.12);
            border: 1px solid rgba(212,130,74,0.18);
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 22px;
            transition: 0.3s;
        }
        .info-card:hover .card-icon {
            background: rgba(181,98,42,0.22);
            transform: rotate(5deg) scale(1.05);
        }
        .card-icon .material-icons { color: var(--caramel); font-size: 24px; }
        .card-label {
            font-size: 10px; font-weight: 700; letter-spacing: 3px;
            text-transform: uppercase; color: var(--caramel);
            margin-bottom: 8px;
        }
        .card-title {
            font-family: 'Cinzel', serif; font-size: 1.1rem; font-weight: 700;
            color: var(--cream); margin-bottom: 12px; letter-spacing: 0.5px;
        }
        .card-desc {
            font-size: 13px; color: rgba(255,255,255,0.4);
            line-height: 1.7; font-weight: 300;
        }

        footer {
            border-top: 1px solid rgba(255,255,255,0.04);
            padding: 40px 24px;
            text-align: center;
            background: rgba(8,2,1,0.5);
        }
        .footer-chess { font-size: 1.4rem; opacity: 0.3; margin-bottom: 12px; letter-spacing: 8px; }
        footer p {
            font-size: 11px; letter-spacing: 3px;
            text-transform: uppercase; color: rgba(255,255,255,0.18);
        }

        .cursor-dot {
            width: 6px; height: 6px; border-radius: 50%;
            background: var(--caramel);
            position: fixed; pointer-events: none;
            z-index: 9999; opacity: 0;
            transform: translate(-50%, -50%);
            transition: opacity 0.3s;
        }

        @media (max-width: 768px) {
            nav { padding: 16px 20px; }
            .hero { min-height: 75vh; }
            .cards-grid { grid-template-columns: 1fr; }
            .hero-ring, .hero-ring-2, .orbital { display: none; }
            .floating-stats { gap: 24px; }
            .stat-num { font-size: 1.5rem; }
        }
        @media (max-width: 500px) {
            .btn-primary, .btn-secondary { width: 100%; justify-content: center; }
            .floating-stats { flex-wrap: wrap; gap: 20px; }
        }
    </style>
</head>
<body>

<canvas id="bgCanvas"></canvas>
<div class="grid-overlay"></div>
<div class="scanlines"></div>

<div class="cursor-dot" id="dot1"></div>
<div class="cursor-dot" id="dot2"></div>
<div class="cursor-dot" id="dot3"></div>

<nav>
    <a class="brand" href="#">
        <img src="../assets/miffy.jpg" class="brand-logo" alt="Miffy">
        <span class="brand-name">MIFFY CHESS</span>
    </a>
    <div class="nav-links">
        <a href="loginpage.php" class="btn-nav-outline">Player Login</a>
        <a href="adminreg.php" class="btn-nav-caramel">Arbiter Login</a>
    </div>
</nav>

<section class="hero">

    <div class="hero-ring"></div>
    <div class="hero-ring-2"></div>
    <div class="orbital">
        <span class="orbital-piece">♛</span>
        <span class="orbital-piece">♞</span>
        <span class="orbital-piece">♜</span>
        <span class="orbital-piece">♝</span>
        <span class="orbital-piece">♟</span>
        <span class="orbital-piece">♚</span>
    </div>

    <div class="hero-badge">
        <span class="badge-dot"></span>
        ♟ Tournament Season 2026
    </div>
    <h1 class="hero-title">
        MASTER THE
        <span class="accent">GRAND ARENA</span>
    </h1>
    <p class="hero-sub">
        Experience the ultimate chess competition. Compete with the best, sharpen your strategy, and claim your place at the Miffy Chess Cup.
    </p>
    <div class="hero-cta">
        <a href="registrationpage.php" class="btn-primary">
            <i class="material-icons" style="font-size:18px;">how_to_reg</i>
            Register Player
        </a>
        <a href="standings.php" class="btn-secondary">
            <i class="material-icons" style="font-size:18px;">login</i>
            View Standings
        </a>
    </div>
    <div class="floating-stats">
        <div class="stat-item">
            <span class="stat-num" data-target="7">7</span>
            <span class="stat-label">Rounds</span>
        </div>
        <div class="stat-divider"></div>
        <div class="stat-item">
            <span class="stat-num" data-target="2026">2026</span>
            <span class="stat-label">Season</span>
        </div>
        <div class="stat-divider"></div>
        <div class="stat-item">
            <span class="stat-num">FIDE</span>
            <span class="stat-label">Rated</span>
        </div>
    </div>
</section>

<div class="marquee-wrap">
    <div class="marquee-track" id="marqueeTrack">
        <span class="marquee-item">♟ Swiss System <span class="marquee-dot"></span></span>
        <span class="marquee-item">7 Rounds <span class="marquee-dot"></span></span>
        <span class="marquee-item">FIDE Rated <span class="marquee-dot"></span></span>
        <span class="marquee-item">Open Enrollment <span class="marquee-dot"></span></span>
        <span class="marquee-item">Elite Rewards <span class="marquee-dot"></span></span>
        <span class="marquee-item">Miffy Chess Cup 2026 <span class="marquee-dot"></span></span>
        <span class="marquee-item">♟ Swiss System <span class="marquee-dot"></span></span>
        <span class="marquee-item">7 Rounds <span class="marquee-dot"></span></span>
        <span class="marquee-item">FIDE Rated <span class="marquee-dot"></span></span>
        <span class="marquee-item">Open Enrollment <span class="marquee-dot"></span></span>
        <span class="marquee-item">Elite Rewards <span class="marquee-dot"></span></span>
        <span class="marquee-item">Miffy Chess Cup 2026 <span class="marquee-dot"></span></span>
    </div>
</div>

<section class="cards-section">
    <div class="section-divider"><span>♞</span></div>
    <div class="cards-grid">
        <div class="info-card">
            <div class="card-icon"><i class="material-icons">emoji_events</i></div>
            <div class="card-label">Format</div>
            <div class="card-title">Swiss System</div>
            <p class="card-desc">Fair and competitive 7-round system following international FIDE regulations. Every player faces an opponent of equal standing.</p>
        </div>
        <div class="info-card">
            <div class="card-icon"><i class="material-icons">military_tech</i></div>
            <div class="card-label">Prizes</div>
            <div class="card-title">Elite Rewards</div>
            <p class="card-desc">Exclusive Miffy Cup medals and rating points awarded to the top 10 grandmasters who demonstrate outstanding skill.</p>
        </div>
        <div class="info-card">
            <div class="card-icon"><i class="material-icons">groups</i></div>
            <div class="card-label">Eligibility</div>
            <div class="card-title">Open Enrollment</div>
            <p class="card-desc">Welcoming all skill levels — from casual players to seasoned professionals. Register today and write your chapter.</p>
        </div>
    </div>
</section>

<footer>
    <p>&copy; 2026 Miffy Chess Cup &bull; Arbiter Portal V3.0</p>
</footer>

<script>

(function() {
    const canvas = document.getElementById('bgCanvas');
    const ctx    = canvas.getContext('2d');
    const pieces = ['♟','♜','♞','♝','♛','♚','♙','♖'];

    let particles = [];
    let W, H, time = 0;

    function resize() {
        W = canvas.width  = window.innerWidth;
        H = canvas.height = window.innerHeight;
    }
    resize();
    window.addEventListener('resize', resize);

    for (let i = 0; i < 35; i++) {
        particles.push({
            x: Math.random() * 1600,
            y: Math.random() * 1000,
            symbol: pieces[Math.floor(Math.random()*pieces.length)],
            size: 10 + Math.random() * 32,
            speed: 0.08 + Math.random() * 0.22,
            drift: (Math.random() - 0.5) * 0.25,
            alpha: 0.018 + Math.random() * 0.055,
            rot: Math.random() * Math.PI * 2,
            rotSpeed: (Math.random() - 0.5) * 0.005,

            waveAmp: Math.random() * 0.8,
            waveFreq: 0.3 + Math.random() * 0.7,
            waveOffset: Math.random() * Math.PI * 2,
            baseX: 0,
        });
        particles[particles.length-1].baseX = particles[particles.length-1].x;
    }

    function draw() {
        time += 0.003;

        const grad = ctx.createRadialGradient(W*0.5, H*0.45, 0, W*0.5, H*0.45, W*0.95);
        grad.addColorStop(0,   'rgba(36, 16, 6, 0.97)');
        grad.addColorStop(0.5, 'rgba(18, 7, 3, 0.98)');
        grad.addColorStop(1,   'rgba(8, 2, 1, 1)');
        ctx.fillStyle = grad;
        ctx.fillRect(0, 0, W, H);

        const orbs = [
            { x: W*(0.18 + 0.07*Math.sin(time*0.6)), y: H*(0.22 + 0.06*Math.cos(time*0.45)), r: W*0.32, c: `rgba(107,58,42,${0.06 + 0.025*Math.sin(time*0.8)})` },
            { x: W*(0.84 + 0.05*Math.cos(time*0.55)), y: H*(0.68 + 0.07*Math.sin(time*0.38)), r: W*0.26, c: `rgba(181,98,42,${0.055 + 0.018*Math.cos(time*1.1)})` },
            { x: W*(0.5  + 0.04*Math.sin(time*0.85)), y: H*(0.88 + 0.035*Math.cos(time*0.75)), r: W*0.22, c: `rgba(212,130,74,${0.04 + 0.012*Math.sin(time*1.4)})` },
            { x: W*(0.67 + 0.09*Math.sin(time*0.38)), y: H*(0.12 + 0.055*Math.cos(time*0.65)), r: W*0.24, c: `rgba(90,40,20,${0.065 + 0.022*Math.sin(time*0.85)})` },

            { x: W*0.5, y: H*0.5, r: W*0.15, c: `rgba(140,70,30,${0.025 + 0.015*Math.sin(time*2)})` },
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
            const wx = p.waveAmp * 30 * Math.sin(time * p.waveFreq + p.waveOffset);
            const displayX = (p.x + wx) % W;

            ctx.save();
            ctx.translate(displayX < 0 ? displayX + W : displayX, p.y % H);
            ctx.rotate(p.rot);
            ctx.globalAlpha = p.alpha * (0.7 + 0.3 * Math.sin(time * 0.5 + p.waveOffset));
            ctx.fillStyle = '#E8A96A';
            ctx.font = `${p.size}px serif`;
            ctx.fillText(p.symbol, 0, 0);
            ctx.restore();

            p.y -= p.speed;
            p.x += p.drift;
            p.rot += p.rotSpeed;
            if (p.y < -70) { p.y = H + 70; p.x = Math.random() * W; p.baseX = p.x; }
            if (p.x < -70) p.x = W + 70;
            if (p.x > W + 70) p.x = -70;
        });

        requestAnimationFrame(draw);
    }
    draw();
})();

(function() {
    const dots = [
        document.getElementById('dot1'),
        document.getElementById('dot2'),
        document.getElementById('dot3'),
    ];
    const positions = dots.map(() => ({ x: 0, y: 0 }));
    let mouse = { x: 0, y: 0 };
    let active = false;

    document.addEventListener('mousemove', e => {
        mouse.x = e.clientX;
        mouse.y = e.clientY;
        if (!active) {
            active = true;
            dots.forEach(d => d.style.opacity = '0.7');
            animate();
        }
    });

    document.addEventListener('mouseleave', () => {
        dots.forEach(d => d.style.opacity = '0');
        active = false;
    });

    let frame;
    function animate() {
        if (!active) return;
        positions[0].x += (mouse.x - positions[0].x) * 0.35;
        positions[0].y += (mouse.y - positions[0].y) * 0.35;
        positions[1].x += (positions[0].x - positions[1].x) * 0.28;
        positions[1].y += (positions[0].y - positions[1].y) * 0.28;
        positions[2].x += (positions[1].x - positions[2].x) * 0.22;
        positions[2].y += (positions[1].y - positions[2].y) * 0.22;

        const sizes = [6, 4, 3];
        const opacities = [0.7, 0.45, 0.25];
        dots.forEach((dot, i) => {
            dot.style.left = positions[i].x + 'px';
            dot.style.top  = positions[i].y + 'px';
            dot.style.width  = sizes[i] + 'px';
            dot.style.height = sizes[i] + 'px';
            dot.style.opacity = opacities[i];
        });
        frame = requestAnimationFrame(animate);
    }
})();

(function() {
    const PIECES = ['♟','♞','♝','♜','♛','♚','♙','♘','♗','♖','♕','♔'];
    const COLORS = [
        'rgba(232,169,106,0.82)',
        'rgba(245,201,138,0.7)',
        'rgba(212,168,67,0.78)',
        'rgba(255,255,255,0.45)',
        'rgba(181,98,42,0.9)',
    ];
    const FX = ['✦','✧','⭑','✶','❋','✺'];

    let score = 0, combo = 0, best = 0, pieces = [], pid = 0;

    const hud = document.createElement('div');
    hud.innerHTML = `<div id="ic-hud" style="position:fixed;bottom:20px;right:22px;display:flex;gap:8px;z-index:9999;pointer-events:none;">
        <div class="ic-pill">Eaten <b id="ic-score">0</b></div>
        <div class="ic-pill">Best <b id="ic-best">0</b>×</div>
    </div>`;
    document.body.appendChild(hud);

    const banner = document.createElement('div');
    banner.id = 'ic-combo';
    banner.style.cssText = 'position:fixed;top:50%;left:50%;transform:translate(-50%,-50%) scale(0);background:rgba(10,3,1,0.92);border:1px solid rgba(232,169,106,0.55);border-radius:12px;padding:10px 28px;font-family:Cinzel,serif;font-size:22px;color:#E8A96A;letter-spacing:2px;z-index:9999;pointer-events:none;opacity:0;transition:transform 0.2s cubic-bezier(.34,1.56,.64,1),opacity 0.15s;';
    document.body.appendChild(banner);

    const style = document.createElement('style');
    style.textContent = `
        .ic-piece{position:fixed;font-size:38px;pointer-events:all;cursor:crosshair;user-select:none;z-index:500;line-height:1;transition:left var(--dur,7s) linear,top var(--dur,7s) linear,opacity 0.5s;filter:drop-shadow(0 0 8px rgba(232,169,106,0.5));}
        .ic-piece:hover{transform:scale(1.35)!important;filter:drop-shadow(0 0 18px rgba(232,169,106,1)) brightness(1.5);}
        .ic-piece.ic-eat{animation:icEat 0.38s forwards;pointer-events:none;}
        @keyframes icEat{0%{transform:scale(1.6) rotate(0);opacity:1}50%{transform:scale(0.7) rotate(25deg);opacity:0.5}100%{transform:scale(0) rotate(-45deg);opacity:0}}
        .ic-piece.ic-pop{animation:icPop 0.45s ease-out forwards;}
        @keyframes icPop{0%{transform:scale(0) rotate(-30deg);opacity:0}65%{transform:scale(1.25);opacity:1}100%{transform:scale(1);opacity:1}}
        .ic-fx{position:fixed;font-size:22px;pointer-events:none;z-index:600;color:#E8A96A;text-shadow:0 0 8px rgba(232,169,106,0.8);animation:icFx 0.75s forwards;}
        @keyframes icFx{0%{transform:translateY(0) scale(1.3);opacity:1}100%{transform:translateY(-55px) scale(0.3);opacity:0}}
        .ic-pill{background:rgba(10,3,1,0.75);border:0.5px solid rgba(232,169,106,0.3);border-radius:20px;padding:5px 14px;font-size:12px;color:rgba(255,255,255,0.4);letter-spacing:1px;display:flex;align-items:center;gap:6px;font-family:'DM Sans',sans-serif;}
        .ic-pill b{color:#E8A96A;font-size:13px;font-weight:700;}
    `;
    document.head.appendChild(style);

    const scoreEl = document.getElementById('ic-score');
    const bestEl  = document.getElementById('ic-best');

    function rnd(a,b){ return Math.random()*(b-a)+a; }

    function spawn() {
        const id = pid++;
        const el = document.createElement('div');
        el.className = 'ic-piece ic-pop';
        el.textContent = PIECES[Math.floor(Math.random()*PIECES.length)];
        el.style.color = COLORS[Math.floor(Math.random()*COLORS.length)];
        const vw = window.innerWidth, vh = window.innerHeight;
        el.style.left = rnd(30, vw-70) + 'px';
        el.style.top  = rnd(80, vh-80) + 'px';
        const dur = rnd(6, 12);
        el.style.setProperty('--dur', dur+'s');
        document.body.appendChild(el);

        const p = { id, el, alive: true };
        pieces.push(p);

        el.addEventListener('click', function(e) {
            if (!p.alive) return;
            e.stopPropagation();
            eat(p, e.clientX, e.clientY);
        });

        requestAnimationFrame(() => requestAnimationFrame(() => {
            el.style.left = rnd(30, vw-70) + 'px';
            el.style.top  = rnd(80, vh-80) + 'px';
        }));

        p.t = setTimeout(() => {
            if (!p.alive) return;
            p.alive = false; combo = 0;
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 600);
            pieces = pieces.filter(x => x.id !== id);
            refill();
        }, dur * 1000 + rnd(1000, 3000));
    }

    function eat(p, cx, cy) {
        p.alive = false;
        clearTimeout(p.t);
        p.el.classList.remove('ic-pop');
        p.el.classList.add('ic-eat');

        score++; combo++;
        if (combo > best) { best = combo; bestEl.textContent = best + '×'; }
        scoreEl.textContent = score;

        const fx = document.createElement('div');
        fx.className = 'ic-fx';
        fx.textContent = FX[Math.floor(Math.random()*FX.length)];
        fx.style.left = (cx - 10) + 'px';
        fx.style.top  = (cy - 24) + 'px';
        document.body.appendChild(fx);
        setTimeout(() => fx.remove(), 800);

        if (combo >= 3) {
            banner.textContent = combo + '× Combo!';
            banner.style.transform = 'translate(-50%,-50%) scale(1)';
            banner.style.opacity = '1';
            clearTimeout(banner._t);
            banner._t = setTimeout(() => {
                banner.style.transform = 'translate(-50%,-50%) scale(0)';
                banner.style.opacity = '0';
            }, 850);
        }

        setTimeout(() => {
            p.el.remove();
            pieces = pieces.filter(x => x.id !== p.id);
            refill();
        }, 400);
    }

    function refill() {
        const need = Math.max(1, Math.floor(rnd(6,10)) - pieces.length);
        for (let i = 0; i < need; i++) setTimeout(spawn, i * rnd(120, 400));
    }

    for (let i = 0; i < 8; i++) setTimeout(spawn, i * 180);
    setInterval(() => { if (pieces.length < 4) refill(); }, 2000);
})();

(function() {
    const cards = document.querySelectorAll('.info-card');
    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, { threshold: 0.15 });
    cards.forEach(c => observer.observe(c));
})();

(function() {
    const hero = document.querySelector('.hero');
    const ring = document.querySelector('.hero-ring');
    const ring2 = document.querySelector('.hero-ring-2');

    if (!hero || !ring) return;

    hero.addEventListener('mousemove', e => {
        const rect = hero.getBoundingClientRect();
        const cx = (e.clientX - rect.left) / rect.width - 0.5;
        const cy = (e.clientY - rect.top)  / rect.height - 0.5;

        ring.style.transform  = `translate(calc(-50% + ${cx * 18}px), calc(-50% + ${cy * 18}px)) rotate(0deg)`;
        ring2.style.transform = `translate(calc(-50% + ${cx * -10}px), calc(-50% + ${cy * -10}px)) rotate(0deg)`;
    });

    hero.addEventListener('mouseleave', () => {
        ring.style.transform  = '';
        ring2.style.transform = '';
    });
})();
</script>
</body>
</html>