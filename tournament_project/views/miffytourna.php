<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Miffy Chess Cup | Tournament</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;500;700;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        :root {
            --primary: #F2B705;
            --secondary: #A6290D;
            --dark: #140F0A;
            --glass: rgba(255, 255, 255, 0.05);
        }
        body {
            font-family: 'Space Grotesk', sans-serif;
            background: radial-gradient(circle at top right, rgba(166, 41, 13, 0.2), transparent),
                        linear-gradient(rgba(20, 15, 10, 0.9), rgba(20, 15, 10, 0.95)), 
                        url('https://i.pinimg.com/736x/6e/91/f5/6e91f50873c6530bf7d58b5fa27bb501.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: #fff;
            overflow-x: hidden;
        }
        nav {
            background: transparent !important;
            box-shadow: none !important;
            height: 100px;
            padding-top: 20px;
        }
        .nav-wrapper {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .brand-logo-custom {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .nav-miffy-img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: 2px solid var(--primary);
            background: white;
            padding: 3px;
        }
        .hero-section {
            min-height: 85vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 60px 0;
        }
        .hero-text {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 900px;
        }
        .badge-premium {
            display: inline-block;
            background: var(--secondary);
            padding: 5px 15px;
            border-radius: 5px;
            font-weight: 700;
            font-size: 0.8rem;
            letter-spacing: 3px;
            margin-bottom: 20px;
            text-transform: uppercase;
        }
        .main-headline {
            font-size: clamp(3.5rem, 10vw, 6.5rem);
            font-weight: 900;
            line-height: 0.85;
            margin: 0;
            text-transform: uppercase;
        }
        .highlight-text {
            color: var(--primary);
            display: block;
        }
        .hero-description {
            font-size: 1.4rem;
            color: rgba(255,255,255,0.7);
            max-width: 700px;
            margin: 35px auto;
            line-height: 1.6;
        }
        .cta-group {
            display: flex;
            gap: 25px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 40px;
        }
        .btn-elite {
            height: 80px;
            line-height: 80px;
            padding: 0 55px;
            border-radius: 15px;
            font-weight: 900;
            font-size: 1.3rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            min-width: 300px;
        }
        .btn-gold {
            background-color: var(--primary) !important;
            color: var(--dark) !important;
            box-shadow: 0 10px 30px rgba(242, 183, 5, 0.2);
        }
        .btn-elite:hover {
            transform: scale(1.08) translateY(-5px);
            box-shadow: 0 20px 40px rgba(242, 183, 5, 0.3);
        }
        .details-grid {
            margin-top: 80px;
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 60px;
        }
        .info-card {
            background: var(--glass);
            backdrop-filter: blur(10px);
            padding: 35px;
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.1);
            height: 100%;
            transition: 0.3s;
            text-align: center;
        }
        .info-card:hover {
            border-color: var(--primary);
            background: rgba(255,255,255,0.08);
            transform: translateY(-10px);
        }
        .info-card i {
            color: var(--primary);
            font-size: 3rem;
            margin-bottom: 20px;
            display: block;
        }
        .info-card h5 {
            font-weight: 900;
            font-size: 1.2rem;
            margin: 0 0 15px 0;
            text-transform: uppercase;
        }
        .info-card p {
            margin: 0;
            color: rgba(255,255,255,0.5);
            line-height: 1.5;
        }
        .floating-piece {
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            font-size: 40rem;
            color: rgba(255,255,255,0.02);
            pointer-events: none;
            user-select: none;
            z-index: 1;
        }
        @media (max-width: 600px) {
            .btn-elite { width: 100%; min-width: unset; }
            .main-headline { font-size: 3.5rem; }
        }
    </style>
</head>
<body>
    <nav>
        <div class="container">
            <div class="nav-wrapper">
                <div class="brand-logo-custom">
                    <img src="../assets/miffy.jpg" class="nav-miffy-img">
                    <span class="hide-on-small-only" style="font-weight: 900; font-size: 1.5rem; letter-spacing: 1px;">MIFFY CHESS</span>
                </div>
                <div class="nav-actions" style="display: flex; align-items: center; gap: 12px;">
                    <a href="loginpage.php" style="border: 1px solid rgba(255,255,255,0.3); padding: 8px 20px; border-radius: 50px; color: #fff; font-weight: 500; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 1px;">
                        Player Login
                    </a>
                    <a href="adminreg.php" style="border: 1px solid var(--primary); background: rgba(242, 183, 5, 0.05); padding: 8px 20px; border-radius: 50px; color: var(--primary); font-weight: 700; text-transform: uppercase; letter-spacing: 1px; font-size: 0.75rem;">
                        Arbiter Login
                    </a>
                </div>
            </div>
        </div>
    </nav>
    <div class="floating-piece">♔</div>
    <main class="container hero-section">
        <div class="hero-text">
            <span class="badge-premium pulse">Tournament Season 2026</span>
            <h1 class="main-headline">
                MASTER THE <br>
                <span class="highlight-text">GRAND ARENA</span>
            </h1>
            <p class="hero-description">
                Experience the ultimate chess competition. Compete with the best, sharpen your strategy, and claim your place of the Miffy Chess Cup.
            </p>
            <div class="cta-group">
                <a href="registrationpage.php" class="btn btn-elite btn-gold waves-effect waves-light">
                    Register Player <i class="material-icons right" style="font-size: 2rem;">arrow_forward</i>
                </a>
            </div>
        </div>
    </main>
    <section class="container details-grid">
        <div class="row">
            <div class="col s12 m4" style="margin-bottom: 30px;">
                <div class="info-card">
                    <i class="material-icons">emoji_events</i>
                    <h5>Swiss Format</h5>
                    <p>Fair and competitive 7-round system following international FIDE regulations.</p>
                </div>
            </div>
            <div class="col s12 m4" style="margin-bottom: 30px;">
                <div class="info-card">
                    <i class="material-icons">military_tech</i>
                    <h5>Elite Prizes</h5>
                    <p>Exclusive Miffy Cup medals and rating points for the top 10 grandmasters.</p>
                </div>
            </div>
            <div class="col s12 m4" style="margin-bottom: 30px;">
                <div class="info-card">
                    <i class="material-icons">history_edu</i>
                    <h5>Open Enrollment</h5>
                    <p>Welcoming all skill levels. From casual players to professional arbiters.</p>
                </div>
            </div>
        </div>
    </section>
    <footer style="margin-top: 100px; padding: 50px 0; border-top: 1px solid rgba(255,255,255,0.05); text-align: center;">
        <p style="color: rgba(255,255,255,0.3); font-size: 0.8rem; letter-spacing: 3px; text-transform: uppercase;">
            &copy; 2026 MIFFY CHESS CUP &bull; ARBITER PORTAL V3.0
        </p>
    </footer>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
</body>
</html>