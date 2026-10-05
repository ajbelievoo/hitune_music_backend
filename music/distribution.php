<?php
/**
 * Music Distribution - Pricing Page (Standalone)
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Canonical host is the distribution subdomain; bounce legacy music.hitune.in URLs.
if (($_SERVER['HTTP_HOST'] ?? '') === 'music.hitune.in') {
    $q = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
    header('Location: https://distribution.hitune.in/' . $q, true, 301);
    exit;
}

// Check if user is logged in (simple check)
$is_logged = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Music Distribution Plans - Distribute Your Music Worldwide | Hitune Music</title>
    <meta name="description" content="Distribute your music to Spotify, Apple Music, YouTube and 100+ platforms. Choose from Professional, Breakout Artist, or Rising Artist plans.">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@6.5.95/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="/dist-style.css">
    <script src="/dist-auth.js"></script>
</head>
<body>
    <nav class="topnav">
        <div class="topnav-inner">
            <a class="brand" href="/"><span class="brand-dot"><span class="mdi mdi-music-note"></span></span>Hitune <small>Distribution</small></a>
            <div class="nav-links">
                <a href="/dashboard">Dashboard</a>
                <a href="/submit">Submit Music</a>
                <a href="https://music.hitune.in/">Hitune Music</a>
                <a href="/submit" class="nav-cta">Get Started</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <section class="hero">
            <span class="eyebrow">Independent Distribution</span>
            <h1>Distribute your music<br><span class="accent">worldwide</span></h1>
            <p>Get your music on Spotify, Apple Music, YouTube Music and 100+ streaming platforms. Keep 100% of your royalties.</p>
            <div class="hero-actions">
                <a class="btn btn-primary" href="#plans">See Plans</a>
                <a class="btn btn-secondary" href="/submit">Submit a Release</a>
            </div>
        </section>

        <div class="plans-container" id="plans">
            <!-- Rising Artist -->
            <div class="pricing-card">
                <div class="plan-name">Rising Artist</div>
                <div class="plan-desc">Perfect for new artists starting their journey</div>
                <div class="plan-price">₹1599<span>/year</span></div>
                <ul class="features-list">
                    <li>Upload to 50+ platforms</li>
                    <li>Standard support (48h response)</li>
                    <li>Basic analytics</li>
                    <li>1 artist profile</li>
                    <li>Unlimited releases</li>
                    <li class="disabled">YouTube Content ID</li>
                    <li class="disabled">Custom label name</li>
                    <li class="disabled">Scheduled releases</li>
                </ul>
                <button class="btn btn-secondary btn-block" onclick="selectPlan('rising')">Choose Plan</button>
            </div>

            <!-- Breakout Artist -->
            <div class="pricing-card featured">
                <div class="plan-name">Breakout Artist</div>
                <div class="plan-desc">For growing artists ready to expand</div>
                <div class="plan-price">₹2799<span>/year</span></div>
                <ul class="features-list">
                    <li>Upload to 100+ platforms</li>
                    <li>Priority support (24h response)</li>
                    <li>Advanced analytics</li>
                    <li>3 artist profiles</li>
                    <li>Unlimited releases</li>
                    <li>YouTube Content ID</li>
                    <li class="disabled">Custom label name</li>
                    <li class="disabled">Scheduled releases</li>
                </ul>
                <button class="btn btn-primary btn-block" onclick="selectPlan('breakout')">Choose Plan</button>
            </div>

            <!-- Professional -->
            <div class="pricing-card">
                <div class="plan-name">Professional</div>
                <div class="plan-desc">For serious artists and labels</div>
                <div class="plan-price">₹4499<span>/year</span></div>
                <ul class="features-list">
                    <li>Upload to 150+ platforms</li>
                    <li>VIP support (12h response)</li>
                    <li>Professional analytics</li>
                    <li>Unlimited artist profiles</li>
                    <li>Unlimited releases</li>
                    <li>YouTube Content ID</li>
                    <li>Custom label name</li>
                    <li>Scheduled releases</li>
                </ul>
                <button class="btn btn-secondary btn-block" onclick="selectPlan('professional')">Choose Plan</button>
            </div>
        </div>

        <div class="platforms-section">
            <h2>Supported Platforms</h2>
            <p>Your release delivered to every major streaming service and store.</p>
            <div class="platforms-grid">
                <div class="platform-item">Spotify</div>
                <div class="platform-item">Apple Music</div>
                <div class="platform-item">YouTube Music</div>
                <div class="platform-item">Amazon Music</div>
                <div class="platform-item">Deezer</div>
                <div class="platform-item">Tidal</div>
                <div class="platform-item">Pandora</div>
                <div class="platform-item">Instagram</div>
                <div class="platform-item">TikTok</div>
                <div class="platform-item">Snapchat</div>
            </div>
        </div>
    </div>

    <footer class="dist-footer">
        Powered by <a href="https://music.hitune.in/">Hitune Music</a> · Independent music distribution
    </footer>

    <script>
    function selectPlan(planSlug) {
        // The subscribe page checks login via API and preserves the selected plan
        window.location.href = '/subscribe/' + planSlug;
    }
    </script>
</body>
</html>
