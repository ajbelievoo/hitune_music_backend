<?php
/**
 * HiTune Developer API Portal
 */

// Share session across subdomains before BOF loads
if (!headers_sent()) {
    ini_set('session.cookie_domain', '.hitune.in');
    session_set_cookie_params([
        'lifetime' => 86400,
        'path' => '/',
        'domain' => '.hitune.in',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load BOF config to get session keys
require_once( dirname(__FILE__) . "/api/app/config.php" );

// Check if user is logged in via BOF session
$is_logged = false;
$user_id = null;

// Check for BOF session
if (isset($_SESSION['sess_id']) && !empty($_SESSION['sess_id'])) {
    $is_logged = true;
    $user_id = $_SESSION['user_id'] ?? null;
}

// If not logged in via BOF, check for simple session
if (!$is_logged && isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    $is_logged = true;
    $user_id = $_SESSION['user_id'];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Developer API - Build with HiTune Music | Hitune</title>
    <meta name="description" content="Access HiTune's music catalog through our REST API. Search tracks, albums, artists, playlists, and embed music in your apps and websites.">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@6.5.95/css/materialdesignicons.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Figtree', sans-serif;
            background: #0a0a1a;
            color: #fff;
            line-height: 1.6;
        }
        .topnav {
            background: rgba(10, 10, 26, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .topnav-inner {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: #fff;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .brand-dot {
            background: linear-gradient(135deg, #00b7ff, #8b5cf6);
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .brand small {
            font-size: 0.8rem;
            color: #888;
            margin-left: 0.5rem;
        }
        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }
        .nav-links a {
            color: #aaa;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }
        .nav-links a:hover {
            color: #00b7ff;
        }
        .nav-cta {
            background: linear-gradient(135deg, #00b7ff, #8b5cf6);
            color: #fff !important;
            padding: 0.5rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 3rem 2rem;
        }
        .hero {
            text-align: center;
            padding: 4rem 0;
        }
        .eyebrow {
            color: #00b7ff;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .hero h1 {
            font-size: 3.5rem;
            font-weight: 800;
            margin: 1rem 0;
            line-height: 1.1;
        }
        .hero .accent {
            background: linear-gradient(135deg, #00b7ff, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .hero p {
            font-size: 1.2rem;
            color: #888;
            max-width: 600px;
            margin: 0 auto 2rem;
        }
        .hero-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }
        .btn {
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 183, 255, 0.3);
        }
        .btn-primary {
            background: linear-gradient(135deg, #00b7ff, #8b5cf6);
            color: #fff;
        }
        .btn-secondary {
            background: rgba(255,255,255,0.1);
            color: #fff;
            border: 1px solid rgba(255,255,255,0.2);
        }
        .plans-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin: 4rem 0;
        }
        .pricing-card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 2rem;
            transition: transform 0.2s, border-color 0.2s;
        }
        .pricing-card:hover {
            transform: translateY(-5px);
            border-color: #00b7ff;
        }
        .pricing-card.featured {
            background: rgba(0, 183, 255, 0.1);
            border-color: #00b7ff;
        }
        .plan-name {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .plan-desc {
            color: #888;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        .plan-price {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
        }
        .plan-price span {
            font-size: 1rem;
            font-weight: 500;
            color: #888;
        }
        .features-list {
            list-style: none;
            margin-bottom: 2rem;
        }
        .features-list li {
            padding: 0.5rem 0;
            color: #aaa;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .features-list li::before {
            content: '✓';
            color: #00b7ff;
            font-weight: bold;
        }
        .features-list li.disabled {
            color: #555;
            text-decoration: line-through;
        }
        .features-list li.disabled::before {
            content: '✗';
            color: #555;
        }
        .btn-block {
            width: 100%;
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }
        .section-title {
            font-size: 2rem;
            font-weight: 700;
            text-align: center;
            margin: 4rem 0 2rem;
        }
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }
        .feature-card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 2rem;
        }
        .feature-icon {
            font-size: 2.5rem;
            color: #00b7ff;
            margin-bottom: 1rem;
        }
        .feature-card h3 {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
        }
        .feature-card p {
            color: #888;
        }
        .stats-bar {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 1rem 2rem;
            margin: 2rem 0;
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 2rem;
        }
        .stat-item {
            text-align: center;
        }
        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: #00b7ff;
        }
        .stat-label {
            color: #888;
            font-size: 0.9rem;
        }
        @media (max-width: 768px) {
            .hero h1 { font-size: 2.5rem; }
            .nav-links { display: none; }
            .plans-container { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <nav class="topnav">
        <div class="topnav-inner">
            <a class="brand" href="/">
                <span class="brand-dot"><span class="mdi mdi-music-note"></span></span>
                Hitune <small>Developer</small>
            </a>
            <div class="nav-links">
                <a href="#features">Features</a>
                <a href="#plans">Plans</a>
                <a href="#docs">Documentation</a>
                <a href="https://music.hitune.in/">Music App</a>
                <?php if ($is_logged): ?>
                    <a href="/developer-dashboard.php" class="nav-cta">Dashboard</a>
                <?php else: ?>
                    <a href="https://music.hitune.in/" class="nav-cta">Login to Start</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container">
        <section class="hero">
            <span class="eyebrow">Developer API</span>
            <h1>Build with <span class="accent">HiTune Music</span></h1>
            <p>Access millions of tracks, albums, and artists through our REST API. Search music, get metadata, and embed players in your apps and websites.</p>
            <div class="hero-actions">
                <?php if ($is_logged): ?>
                    <a class="btn btn-primary" href="/developer-dashboard.php">Go to Dashboard</a>
                <?php else: ?>
                    <a class="btn btn-primary" href="https://music.hitune.in/">Login to Get Started</a>
                <?php endif; ?>
                <a class="btn btn-secondary" href="#docs">Read Docs</a>
            </div>
        </section>

        <div class="stats-bar">
            <div class="stat-item">
                <div class="stat-value">10M+</div>
                <div class="stat-label">Tracks</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">500K+</div>
                <div class="stat-label">Artists</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">1M+</div>
                <div class="stat-label">Albums</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">99.9%</div>
                <div class="stat-label">Uptime</div>
            </div>
        </div>

        <h2 class="section-title" id="features">What You Can Build</h2>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon"><span class="mdi mdi-magnify"></span></div>
                <h3>Search Music</h3>
                <p>Search across millions of tracks, albums, and artists with powerful filtering options.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><span class="mdi mdi-code-braces"></span></div>
                <h3>REST API</h3>
                <p>Simple JSON REST API with OAuth 2.0 authentication. Easy integration in any language.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><span class="mdi mdi-webhook"></span></div>
                <h3>Webhooks</h3>
                <p>Get real-time notifications for new releases, track updates, and more.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><span class="mdi mdi-play-circle"></span></div>
                <h3>Embed Player</h3>
                <p>Beautiful embed player for your website. Fully customizable and responsive.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><span class="mdi mdi-chart-line"></span></div>
                <h3>Analytics</h3>
                <p>Track your API usage with detailed analytics and real-time monitoring.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><span class="mdi mdi-shield-check"></span></div>
                <h3>Secure</h3>
                <p>OAuth 2.0 authentication, rate limiting, and HTTPS encryption for all requests.</p>
            </div>
        </div>

        <h2 class="section-title" id="plans">API Plans</h2>
        <div class="plans-container">
            <!-- Sandbox -->
            <div class="pricing-card">
                <div class="plan-name">Sandbox</div>
                <div class="plan-desc">For testing and development</div>
                <div class="plan-price">Free<span>/month</span></div>
                <ul class="features-list">
                    <li>10,000 requests/month</li>
                    <li>10 requests/minute</li>
                    <li>1 app</li>
                    <li>Metadata access</li>
                    <li>30s previews</li>
                    <li class="disabled">Full streaming</li>
                    <li class="disabled">Embed player</li>
                </ul>
                <button class="btn btn-secondary btn-block" onclick="selectPlan('sandbox')">Get Started Free</button>
            </div>

            <!-- Starter -->
            <div class="pricing-card featured">
                <div class="plan-name">Starter</div>
                <div class="plan-desc">For small projects and startups</div>
                <div class="plan-price">₹999<span>/month</span></div>
                <ul class="features-list">
                    <li>100,000 requests/month</li>
                    <li>60 requests/minute</li>
                    <li>3 apps</li>
                    <li>Metadata access</li>
                    <li>30s previews</li>
                    <li>Embed player</li>
                    <li class="disabled">Full streaming</li>
                </ul>
                <button class="btn btn-primary btn-block" onclick="selectPlan('starter')">Choose Starter</button>
            </div>

            <!-- Pro -->
            <div class="pricing-card">
                <div class="plan-name">Pro</div>
                <div class="plan-desc">For growing applications</div>
                <div class="plan-price">₹4,999<span>/month</span></div>
                <ul class="features-list">
                    <li>1,000,000 requests/month</li>
                    <li>300 requests/minute</li>
                    <li>10 apps</li>
                    <li>Metadata access</li>
                    <li>Full streaming (192kbps)</li>
                    <li>50,000 streams/month</li>
                    <li>Embed player</li>
                </ul>
                <button class="btn btn-secondary btn-block" onclick="selectPlan('pro')">Choose Pro</button>
            </div>

            <!-- Enterprise -->
            <div class="pricing-card">
                <div class="plan-name">Enterprise</div>
                <div class="plan-desc">For large-scale applications</div>
                <div class="plan-price">Custom</div>
                <ul class="features-list">
                    <li>Unlimited requests</li>
                    <li>1,000 requests/minute</li>
                    <li>Unlimited apps</li>
                    <li>Full streaming (lossless)</li>
                    <li>Unlimited streams</li>
                    <li>White-label embed</li>
                    <li>SLA guarantee</li>
                </ul>
                <button class="btn btn-secondary btn-block" onclick="contactSales()">Contact Sales</button>
            </div>
        </div>

        <h2 class="section-title" id="docs">Quick Start</h2>
        <div style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 2rem;">
            <h3 style="color: #00b7ff; margin-bottom: 1rem;">For Browser/Mobile Apps (Publishable Key)</h3>
            <pre style="color: #00b7ff; overflow-x: auto; margin-bottom: 2rem;"><code>// 1. Get your API key from the dashboard
const API_KEY = 'ht_pub_xxxxxxxxx';

// 2. Search for tracks
fetch('https://music.hitune.in/api/v1/search?query=love&type=track&limit=10', {
  headers: { 'x-api-key': API_KEY }
})
.then(r => r.json())
.then(data => console.log(data.tracks));

// 3. Get track details
fetch('https://music.hitune.in/api/v1/tracks/{hash}', {
  headers: { 'x-api-key': API_KEY }
})
.then(r => r.json())
.then(data => console.log(data));</code></pre>

            <h3 style="color: #00b7ff; margin-bottom: 1rem;">For Server-Side (Bearer Token)</h3>
            <pre style="color: #00b7ff; overflow-x: auto;"><code>// 1. Get access token
const tokenRes = await fetch('https://music.hitune.in/api/v1/token', {
  method: 'POST',
  headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
  body: 'grant_type=client_credentials&client_id=ht_live_xxx&client_secret=ht_sec_yyy'
});
const { access_token } = await tokenRes.json();

// 2. Use token for API calls
const trackRes = await fetch('https://music.hitune.in/api/v1/tracks/{hash}', {
  headers: { 'Authorization': `Bearer ${access_token}` }
});
const track = await trackRes.json();</code></pre>
        </div>

        <div style="background: rgba(255, 193, 7, 0.1); border: 1px solid rgba(255, 193, 7, 0.3); border-radius: 12px; padding: 1.5rem; margin-top: 2rem;">
            <h4 style="color: #ffc107; margin-bottom: 1rem;">Important Notes</h4>
            <ul style="color: #aaa; line-height: 1.6;">
                <li><strong>Search parameter:</strong> Use <code style="background: rgba(0,0,0,0.3); padding: 0.2rem 0.4rem; border-radius: 4px;">?query=</code> (not <code style="background: rgba(0,0,0,0.3); padding: 0.2rem 0.4rem; border-radius: 4px;">?q=</code>)</li>
                <li><strong>Publishable keys:</strong> Only for browser/mobile apps (requires Origin header)</li>
                <li><strong>Server-side:</strong> Use Bearer token from <code style="background: rgba(0,0,0,0.3); padding: 0.2rem 0.4rem; border-radius: 4px;">/api/v1/token</code></li>
                <li><strong>Streaming:</strong> Requires Pro+ plan and Bearer token</li>
                <li><strong>Preview URL:</strong> Check <code style="background: rgba(0,0,0,0.3); padding: 0.2rem 0.4rem; border-radius: 4px;">preview_url</code> field in track metadata</li>
            </ul>
        </div>
    </div>

    <script>
        function selectPlan(plan) {
            <?php if ($is_logged): ?>
                window.location.href = '/developer-dashboard.php?plan=' + plan;
            <?php else: ?>
                window.location.href = 'https://music.hitune.in/';
            <?php endif; ?>
        }

        function contactSales() {
            window.location.href = 'mailto:support@hitune.in?subject=Enterprise API Plan';
        }
    </script>
</body>
</html>
