<?php
/**
 * HiTune Premium Header — Single Canonical Header
 * Accepts: $pageTitle, $metaDescription, $metaKeywords, $ogImage,
 *          $canonicalUrl, $jsonLd (array), $path (string)
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Defaults
$pageTitle       = isset($pageTitle)       ? trim($pageTitle)       : 'HiTune Music Distribution';
$metaDescription = isset($metaDescription) ? trim($metaDescription) : 'Distribute your music to 150+ streaming platforms worldwide. Keep 100% of your royalties with HiTune Music Distribution.';
$metaKeywords    = isset($metaKeywords)    ? trim($metaKeywords)    : 'music distribution, distribute music, streaming platforms, royalties, independent artist';
$ogImage         = isset($ogImage)         ? trim($ogImage)         : '/assets/og-image.jpg';
$canonicalUrl    = isset($canonicalUrl)    ? trim($canonicalUrl)    : 'https://web.hitune.in/';
$path            = isset($path)            ? trim($path)            : '';

// Load branding from settings (with fallback defaults)
$siteTitle        = 'HiTune Music Distribution';
$siteFavicon      = '/assets/logo.png';
$siteLogoUrl      = '/assets/logo.png';
$googleAnalyticsId = '';
if (isset($conn)) {
    $brandStmt = $conn->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('site_title', 'favicon_url', 'logo_url', 'google_analytics_id')");
    if ($brandStmt) {
        $brandStmt->execute();
        $brandResult = $brandStmt->get_result();
        $brandSettings = [];
        while ($row = $brandResult->fetch_assoc()) {
            $brandSettings[$row['setting_key']] = $row['setting_value'];
        }
        if (!empty($brandSettings['site_title']))        $siteTitle         = $brandSettings['site_title'];
        if (!empty($brandSettings['favicon_url']))       $siteFavicon       = $brandSettings['favicon_url'];
        if (!empty($brandSettings['logo_url']))          $siteLogoUrl       = $brandSettings['logo_url'];
        $googleAnalyticsId = $brandSettings['google_analytics_id'] ?? '';
    }
}
// Use DB site title as fallback when $pageTitle was not set by the page
if (!isset($pageTitle) || $pageTitle === 'HiTune Music Distribution') {
    $pageTitle = $siteTitle;
}

// Determine auth state
$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$userName   = $isLoggedIn ? (htmlspecialchars($_SESSION['name'] ?? $_SESSION['email'] ?? 'User')) : '';

// Nav links definition: [label, route, icon]
$navLinks = [
    ['Home',    'home',    'mdi-home-outline'],
    ['Pricing', 'pricing', 'mdi-tag-multiple-outline'],
    ['Stores',  'stores',  'mdi-store-outline'],
    ['Help',    'help',    'mdi-help-circle-outline'],
    ['About',   'about',   'mdi-information-outline'],
];
// Logged-in artists get the Artist Panel (verification + analytics) in the main nav
if ($isLoggedIn) {
    array_splice($navLinks, 1, 0, [['Artist Panel', 'artist-panel', 'mdi-account-music-outline']]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>
    (function() {
        var t = 'light';
        try { t = localStorage.getItem('hitune-theme') || 'light'; } catch (e) {}
        document.documentElement.setAttribute('data-theme', t);
        document.addEventListener('click', function(e) {
            var btn = e.target.closest('.theme-toggle');
            if (!btn) return;
            var cur = document.documentElement.getAttribute('data-theme') === 'light' ? 'light' : 'dark';
            var next = cur === 'light' ? 'dark' : 'light';
            document.documentElement.classList.add('theme-transition');
            document.documentElement.setAttribute('data-theme', next);
            try { localStorage.setItem('hitune-theme', next); } catch (err) {}
            setTimeout(function() { document.documentElement.classList.remove('theme-transition'); }, 400);
        });
    })();
    </script>

    <!-- Primary SEO -->
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($metaDescription); ?>">
    <meta name="keywords"    content="<?php echo htmlspecialchars($metaKeywords); ?>">
    <link rel="canonical"    href="<?php echo htmlspecialchars($canonicalUrl); ?>">
    <link rel="icon"         href="<?php echo htmlspecialchars($siteFavicon); ?>">

    <?php if (!empty($googleAnalyticsId)): ?>
    <!-- Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo htmlspecialchars($googleAnalyticsId); ?>"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', '<?php echo htmlspecialchars($googleAnalyticsId); ?>');
    </script>
    <?php endif; ?>

    <!-- Open Graph (5 tags) -->
    <meta property="og:title"       content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($metaDescription); ?>">
    <meta property="og:image"       content="<?php echo htmlspecialchars($ogImage); ?>">
    <meta property="og:url"         content="<?php echo htmlspecialchars($canonicalUrl); ?>">
    <meta property="og:type"        content="website">

    <!-- Twitter Card (4 tags) -->
    <meta name="twitter:card"        content="summary_large_image">
    <meta name="twitter:title"       content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($metaDescription); ?>">
    <meta name="twitter:image"       content="<?php echo htmlspecialchars($ogImage); ?>">

    <?php if (!empty($jsonLd)): ?>
    <!-- JSON-LD Structured Data -->
    <script type="application/ld+json">
    <?php echo json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT); ?>
    </script>
    <?php endif; ?>

    <!-- Google Fonts: Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Material Design Icons 6.5.95 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@6.5.95/css/materialdesignicons.min.css">

    <!-- Premium Theme CSS -->
    <link rel="stylesheet" href="/assets/premium-theme.css?v=20260929f">

    <style>
        /* ==================== NAVBAR ==================== */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: rgba(10, 10, 15, 0.70);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .navbar.scrolled {
            background: rgba(10, 10, 15, 0.95);
            border-bottom-color: rgba(0, 183, 255, 0.2);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 80px;
        }

        /* Logo */
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: white;
            font-weight: 800;
            font-size: 26px;
            letter-spacing: -0.5px;
            transition: transform 0.3s;
        }

        .logo:hover { transform: scale(1.02); }

        .logo-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #00b7ff, #8b5cf6);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            position: relative;
            overflow: hidden;
            animation: logoGlow 3s ease-in-out infinite;
        }

        .logo-icon::before {
            content: '';
            position: absolute;
            top: -50%; left: -50%;
            width: 200%; height: 200%;
            background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.3) 50%, transparent 70%);
            animation: logoShine 3s infinite;
                pointer-events: none;
    }

        @keyframes logoGlow {
            0%, 100% { box-shadow: 0 0 20px rgba(0,183,255,0.4); }
            50%       { box-shadow: 0 0 40px rgba(0,183,255,0.8); }
        }

        @keyframes logoShine {
            0%   { transform: translateX(-100%) rotate(45deg); }
            100% { transform: translateX(100%)  rotate(45deg); }
        }

        /* Nav Links */
        .nav-links {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .nav-link {
            position: relative;
            padding: 10px 16px;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            border-radius: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .nav-link::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(0,183,255,0.1), rgba(139,92,246,0.1));
            border-radius: 12px;
            opacity: 0;
            transition: opacity 0.3s;
                pointer-events: none;
    }

        .nav-link:hover,
        .nav-link.active {
            color: #00b7ff;
        }

        .nav-link:hover::before,
        .nav-link.active::before {
            opacity: 1;
        }

        /* Auth Buttons */
        .nav-cta {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .nav-btn {
            padding: 10px 22px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            border: none;
        }

        .nav-btn-login {
            color: rgba(255,255,255,0.9);
            border: 2px solid rgba(255,255,255,0.2);
            background: transparent;
        }

        .nav-btn-login:hover {
            border-color: rgba(0,183,255,0.5);
            color: #00b7ff;
            transform: translateY(-2px);
        }

        .nav-btn-primary {
            background: linear-gradient(135deg, #00b7ff, #8b5cf6);
            color: white;
            box-shadow: 0 4px 20px rgba(0,183,255,0.4);
            position: relative;
            overflow: hidden;
        }

        .nav-btn-primary::before {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.6s;
                pointer-events: none;
    }

        .nav-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0,183,255,0.6);
        }

        .nav-btn-primary:hover::before { left: 100%; }

        /* Dashboard button (logged-in state) */
        .nav-btn-dashboard {
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(255,255,255,0.12);
            color: rgba(255,255,255,0.9);
        }

        .nav-btn-dashboard:hover {
            background: rgba(255,255,255,0.12);
            border-color: rgba(0,183,255,0.3);
            color: #00b7ff;
            transform: translateY(-2px);
        }

        /* Mobile Toggle */
        .mobile-toggle {
            display: none;
            flex-direction: column;
            gap: 5px;
            padding: 10px;
            background: none;
            border: none;
            cursor: pointer;
        }

        .mobile-toggle span {
            width: 24px;
            height: 2px;
            background: white;
            border-radius: 2px;
            transition: all 0.3s;
            display: block;
        }

        .mobile-toggle.active span:nth-child(1) { transform: rotate(45deg) translate(5px, 5px); }
        .mobile-toggle.active span:nth-child(2) { opacity: 0; }
        .mobile-toggle.active span:nth-child(3) { transform: rotate(-45deg) translate(5px, -5px); }

        /* Mobile Drawer */
        .mobile-nav {
            display: none;
            position: fixed;
            top: 80px;
            left: 0; right: 0; bottom: 0;
            background: rgba(10, 10, 15, 0.98);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-top: 1px solid rgba(255,255,255,0.08);
            padding: 24px 20px;
            transform: translateY(-100%);
            opacity: 0;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            overflow-y: auto;
            z-index: 999;
        }

        .mobile-nav.active {
            transform: translateY(0);
            opacity: 1;
        }

        .mobile-nav-links {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .mobile-nav-link {
            padding: 16px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            font-size: 16px;
            font-weight: 500;
            border-radius: 16px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .mobile-nav-link:hover,
        .mobile-nav-link.active {
            background: rgba(0,183,255,0.1);
            color: #00b7ff;
        }

        .mobile-nav-cta {
            margin-top: 24px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            padding-top: 24px;
            border-top: 1px solid rgba(255,255,255,0.08);
        }

        /* Main Content */
        .main-content {
            padding-top: 80px;
            min-height: 100vh;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .nav-links { display: none; }
            .mobile-toggle { display: flex; }
            .mobile-nav { display: block; }
            .nav-cta .nav-btn { display: none; }
            .nav-container { padding: 0 20px; }
        }

        @media (max-width: 480px) {
            .logo { font-size: 22px; }
            .logo-icon { width: 40px; height: 40px; font-size: 22px; }
            .nav-container { height: 70px; }
            .main-content { padding-top: 70px; }
            .mobile-nav { top: 70px; }
        }
    </style>
</head>
<body>
    <!-- Floating Particles -->
    <div class="particles" id="particles"></div>

    <!-- Navigation -->
    <nav class="navbar" id="navbar" role="navigation" aria-label="Main navigation">
        <div class="nav-container">
            <a href="/" class="logo" aria-label="HiTune Home">
                <div class="logo-icon" aria-hidden="true">
                    <img src="<?php echo htmlspecialchars($siteLogoUrl); ?>" alt="HiTune Logo" style="width:100%;height:100%;object-fit:contain;border-radius:14px;" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                    <span class="mdi mdi-music-circle" style="display:none;"></span>
                </div>
                HiTune
            </a>

            <div class="nav-links" role="menubar">
                <?php foreach ($navLinks as [$label, $route, $icon]): ?>
                <a href="/index.php?q=<?php echo $route; ?>"
                   class="nav-link <?php echo $path === $route ? 'active' : ''; ?>"
                   role="menuitem"
                   <?php echo $path === $route ? 'aria-current="page"' : ''; ?>>
                    <span class="mdi <?php echo $icon; ?>" aria-hidden="true"></span>
                    <span><?php echo $label; ?></span>
                </a>
                <?php endforeach; ?>
            </div>

            <div class="nav-cta">
                <button class="theme-toggle" type="button" aria-label="Toggle light/dark mode" title="Toggle theme">
                    <span class="mdi mdi-weather-night" aria-hidden="true"></span>
                    <span class="mdi mdi-white-balance-sunny" aria-hidden="true"></span>
                </button>
                <?php if ($isLoggedIn): ?>
                    <a href="/index.php?q=dashboard" class="nav-btn nav-btn-dashboard">
                        <span class="mdi mdi-view-dashboard-outline" aria-hidden="true"></span>
                        Dashboard
                    </a>
                    <a href="/index.php?q=logout" class="nav-btn nav-btn-login">
                        <span class="mdi mdi-logout" aria-hidden="true"></span>
                        Logout
                    </a>
                <?php else: ?>
                    <a href="/index.php?q=login" class="nav-btn nav-btn-login">
                        <span class="mdi mdi-login" aria-hidden="true"></span>
                        Login
                    </a>
                    <a href="/index.php?q=signup" class="nav-btn nav-btn-primary">
                        <span class="mdi mdi-account-plus" aria-hidden="true"></span>
                        Sign Up
                    </a>
                <?php endif; ?>
            </div>

            <button class="mobile-toggle" id="mobileToggle" aria-label="Toggle navigation menu" aria-expanded="false" aria-controls="mobileNav">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </nav>

    <!-- Mobile Navigation Drawer -->
    <div class="mobile-nav" id="mobileNav" role="dialog" aria-label="Mobile navigation" aria-hidden="true">
        <nav class="mobile-nav-links">
            <?php foreach ($navLinks as [$label, $route, $icon]): ?>
            <a href="/index.php?q=<?php echo $route; ?>"
               class="mobile-nav-link <?php echo $path === $route ? 'active' : ''; ?>"
               <?php echo $path === $route ? 'aria-current="page"' : ''; ?>>
                <span class="mdi <?php echo $icon; ?>" aria-hidden="true"></span>
                <?php echo $label; ?>
            </a>
            <?php endforeach; ?>
        </nav>
        <div class="mobile-nav-cta">
            <button class="theme-toggle" type="button" aria-label="Toggle light/dark mode" title="Toggle theme" style="width:100%;">
                <span class="mdi mdi-weather-night" aria-hidden="true"></span>
                <span class="mdi mdi-white-balance-sunny" aria-hidden="true"></span>
            </button>
            <?php if ($isLoggedIn): ?>
                <a href="/index.php?q=dashboard" class="nav-btn nav-btn-dashboard" style="justify-content:center;">
                    <span class="mdi mdi-view-dashboard-outline" aria-hidden="true"></span>
                    Dashboard
                </a>
                <a href="/index.php?q=logout" class="nav-btn nav-btn-login" style="justify-content:center;">
                    <span class="mdi mdi-logout" aria-hidden="true"></span>
                    Logout
                </a>
            <?php else: ?>
                <a href="/index.php?q=login" class="nav-btn nav-btn-login" style="justify-content:center;">
                    <span class="mdi mdi-login" aria-hidden="true"></span>
                    Login
                </a>
                <a href="/index.php?q=signup" class="nav-btn nav-btn-primary" style="justify-content:center;">
                    <span class="mdi mdi-account-plus" aria-hidden="true"></span>
                    Sign Up Free
                </a>
            <?php endif; ?>
        </div>
    </div>

    <main class="main-content" id="main-content">

<script>
(function() {
    // Navbar scroll effect
    var navbar = document.getElementById('navbar');
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    }, { passive: true });

    // Generate floating particles
    var container = document.getElementById('particles');
    for (var i = 0; i < 30; i++) {
        var p = document.createElement('div');
        p.className = 'particle';
        p.style.left = (Math.random() * 100) + '%';
        p.style.animationDelay = (Math.random() * 15) + 's';
        p.style.animationDuration = (15 + Math.random() * 10) + 's';
        p.style.opacity = (Math.random() * 0.4 + 0.1).toString();
        var size = (Math.random() * 3 + 2) + 'px';
        p.style.width = size;
        p.style.height = size;
        container.appendChild(p);
    }
})();
</script>
