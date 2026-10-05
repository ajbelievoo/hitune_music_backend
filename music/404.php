<?php

header( "HTTP/1.0 404 Not Found" );
if ( !headers_sent() ){
  header( "Cross-Origin-Opener-Policy: same-origin" );
  header( "Content-Security-Policy: default-src 'self' https: data: blob; script-src 'self' 'unsafe-inline' 'unsafe-eval' https:; style-src 'self' 'unsafe-inline' https:; img-src 'self' https: data: blob; font-src 'self' https: data:; connect-src 'self' https:; frame-ancestors 'self'; upgrade-insecure-requests" );
}

$home = defined('web_address') ? web_address : 'https://music.hitune.in/';
$brand = 'Hitune Music';
$logo = $home . 'api/assets/images/icon_128.png';

if ( function_exists('bof') && is_object(bof()) ) {
    try {
        $sitename = bof()->object->db_setting->get("sitename");
        if ( !empty($sitename) ) $brand = $sitename;
    } catch ( Throwable $e ) {}
    if ( !empty($client_config) && !empty($client_config["brand"]["logo"]) ) {
        $logo = $client_config["brand"]["logo"];
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, follow">
    <title>404 - Page Not Found | <?=htmlspecialchars($brand)?></title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,800,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="icon" type="image/png" href="<?=htmlspecialchars($home . 'favicon.ico')?>">
    <style>
        :root {
            --primary: #d946ef;
            --bg: #101210;
            --text: #ffffff;
            --muted: #a78fa8;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Figtree', sans-serif;
            background: var(--bg);
            color: var(--text);
            overflow: hidden;
            min-height: 100vh;
        }
        #drone-canvas {
            position: fixed;
            inset: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            pointer-events: none;
        }
        .wrap {
            position: relative;
            z-index: 10;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px;
            text-align: center;
        }
        .home-icon {
            position: fixed;
            top: 24px;
            left: 24px;
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: rgba(217, 70, 239, 0.1);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 1.25rem;
            border: 1px solid rgba(217, 70, 239, 0.2);
            backdrop-filter: blur(8px);
            transition: all .3s ease;
            z-index: 20;
        }
        .home-icon:hover { background: var(--primary); color: #fff; }
        .brand {
            position: fixed;
            top: 24px;
            right: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 800;
            font-size: 1.1rem;
            color: var(--text);
            text-decoration: none;
            z-index: 20;
        }
        .brand img { height: 36px; width: auto; object-fit: contain; border-radius: 8px; }
        .hero {
            max-width: 760px;
            animation: floatIn 1s ease-out both;
        }
        .hero h1 {
            font-size: clamp(8rem, 22vw, 16rem);
            font-weight: 900;
            line-height: 1;
            color: var(--primary);
            opacity: .95;
            text-shadow: 0 20px 60px rgba(217,70,239,.25);
        }
        .hero h2 {
            font-size: clamp(1.5rem, 4vw, 2.5rem);
            font-weight: 800;
            margin: 10px 0 14px;
        }
        .hero p {
            font-size: clamp(1rem, 2.2vw, 1.25rem);
            color: var(--muted);
            max-width: 560px;
            margin: 0 auto 32px;
            line-height: 1.6;
        }
        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            justify-content: center;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 14px 30px;
            border-radius: 999px;
            font-weight: 700;
            font-size: .95rem;
            text-decoration: none;
            transition: all .25s ease;
            border: none;
            cursor: pointer;
        }
        .btn-primary {
            background: var(--primary);
            color: #fff;
            box-shadow: 0 12px 30px rgba(217,70,239,.35);
        }
        .btn-primary:hover { transform: translateY(-3px); box-shadow: 0 18px 40px rgba(217,70,239,.45); }
        .btn-ghost {
            background: rgba(217,70,239,.08);
            color: var(--primary);
            border: 1px solid rgba(217,70,239,.25);
        }
        .btn-ghost:hover { background: rgba(217,70,239,.16); }
        .search {
            margin-top: 28px;
            position: relative;
            width: min(100%, 420px);
        }
        .search input {
            width: 100%;
            padding: 14px 22px 14px 48px;
            border-radius: 999px;
            border: 1px solid rgba(217,70,239,.25);
            background: rgba(217,70,239,.06);
            color: var(--text);
            font-size: 1rem;
            outline: none;
            font-family: inherit;
        }
        .search i {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary);
        }
        .footer {
            position: fixed;
            bottom: 20px;
            left: 0; right: 0;
            text-align: center;
            font-size: .8rem;
            color: var(--muted);
            z-index: 10;
        }
        @keyframes floatIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @media (max-width: 640px) {
            .home-icon, .brand { top: 16px; }
            .home-icon { left: 16px; width: 42px; height: 42px; }
            .brand { right: 16px; font-size: .95rem; }
            .brand img { height: 28px; }
        }
    </style>
</head>
<body>
    <canvas id="drone-canvas"></canvas>

    <a href="<?=htmlspecialchars($home)?>" class="home-icon" aria-label="Home"><i class="fas fa-home"></i></a>
    <a href="<?=htmlspecialchars($home)?>" class="brand">
        <img src="<?=htmlspecialchars($logo)?>" alt="<?=htmlspecialchars($brand)?>" onerror="this.style.display='none'">
        <span><?=htmlspecialchars($brand)?></span>
    </a>

    <main class="wrap">
        <div class="hero">
            <h1>404</h1>
            <h2>Sorry, The Page Not Found!</h2>
            <p>It looks like this page took off with the drones. The link may be broken or the page may have been moved.</p>
            <div class="actions">
                <a href="<?=htmlspecialchars($home)?>" class="btn btn-primary"><i class="fas fa-home"></i> Back to Home</a>
                <a href="<?=htmlspecialchars(rtrim($home,'/') . '/contact')?>" class="btn btn-ghost"><i class="fas fa-headset"></i> Contact Support</a>
                <button class="btn btn-ghost" onclick="history.back()"><i class="fas fa-arrow-left"></i> Go Back</button>
            </div>
            <form class="search" action="<?=htmlspecialchars(rtrim($home,'/') . '/search')?>" method="get">
                <i class="fas fa-search"></i>
                <input type="text" name="q" placeholder="Search songs, artists..." aria-label="Search">
            </form>
        </div>
    </main>

    <footer class="footer">&copy; <?=date('Y')?> <?=htmlspecialchars($brand)?>. All rights reserved.</footer>

    <script>
        const canvas = document.getElementById('drone-canvas');
        const ctx = canvas.getContext('2d');
        const primary = getComputedStyle(document.documentElement).getPropertyValue('--primary').trim() || '#d946ef';
        let width, height;
        let drones = [];
        let mouse = { x: null, y: null };
        const DRONE_COUNT = 55;
        const CONNECT_DIST = 160;
        const MOUSE_DIST = 220;

        function resize() {
            width = canvas.width = window.innerWidth;
            height = canvas.height = window.innerHeight;
        }
        window.addEventListener('resize', resize);

        class Drone {
            constructor() {
                this.x = Math.random() * width;
                this.y = Math.random() * height;
                this.vx = (Math.random() - 0.5) * 0.7;
                this.vy = (Math.random() - 0.5) * 0.7;
                this.size = 5 + Math.random() * 5;
                this.angle = Math.random() * Math.PI * 2;
                this.spin = (Math.random() - 0.5) * 0.04;
            }
            update() {
                this.x += this.vx;
                this.y += this.vy;
                this.angle += this.spin;

                if (this.x < -30) this.x = width + 30;
                if (this.x > width + 30) this.x = -30;
                if (this.y < -30) this.y = height + 30;
                if (this.y > height + 30) this.y = -30;

                if (mouse.x !== null) {
                    const dx = this.x - mouse.x;
                    const dy = this.y - mouse.y;
                    const d = Math.sqrt(dx * dx + dy * dy);
                    if (d < MOUSE_DIST && d > 0) {
                        this.x += (dx / d) * 2.2;
                        this.y += (dy / d) * 2.2;
                    }
                }
            }
            draw() {
                drawDrone(ctx, this.x, this.y, this.size, this.angle, primary);
            }
        }

        function drawDrone(c, x, y, size, angle, color) {
            c.save();
            c.translate(x, y);
            c.rotate(angle);
            c.strokeStyle = color;
            c.globalAlpha = 0.55;
            c.lineWidth = 1.2;
            c.beginPath();
            c.moveTo(-size, 0); c.lineTo(size, 0);
            c.moveTo(0, -size); c.lineTo(0, size);
            c.stroke();

            c.fillStyle = color;
            const rotors = [[-size, 0], [size, 0], [0, -size], [0, size]];
            rotors.forEach(([dx, dy]) => {
                c.beginPath();
                c.arc(dx, dy, size / 3.5, 0, Math.PI * 2);
                c.fill();
            });

            c.beginPath();
            c.arc(0, 0, size / 5, 0, Math.PI * 2);
            c.fill();
            c.restore();
        }

        function drawConnections() {
            for (let i = 0; i < drones.length; i++) {
                for (let j = i + 1; j < drones.length; j++) {
                    const dx = drones[i].x - drones[j].x;
                    const dy = drones[i].y - drones[j].y;
                    const d = Math.sqrt(dx * dx + dy * dy);
                    if (d < CONNECT_DIST) {
                        ctx.beginPath();
                        ctx.strokeStyle = primary;
                        ctx.globalAlpha = 0.12 * (1 - d / CONNECT_DIST);
                        ctx.lineWidth = 0.8;
                        ctx.moveTo(drones[i].x, drones[i].y);
                        ctx.lineTo(drones[j].x, drones[j].y);
                        ctx.stroke();
                    }
                }
            }
        }

        function init() {
            resize();
            for (let i = 0; i < DRONE_COUNT; i++) drones.push(new Drone());
            loop();
        }

        function loop() {
            ctx.clearRect(0, 0, width, height);
            drawConnections();
            drones.forEach(d => { d.update(); d.draw(); });
            requestAnimationFrame(loop);
        }

        window.addEventListener('mousemove', e => {
            const rect = canvas.getBoundingClientRect();
            mouse.x = e.clientX - rect.left;
            mouse.y = e.clientY - rect.top;
        });
        window.addEventListener('mouseout', () => { mouse.x = null; mouse.y = null; });

        init();
    </script>
</body>
</html>
