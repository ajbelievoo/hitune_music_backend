<?php
/**
 * web.hitune.in - New Modern Header Design
 * Unique and different from TuneCore
 */
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
            document.documentElement.setAttribute('data-theme', next);
            try { localStorage.setItem('hitune-theme', next); } catch (err) {}
        });
    })();
    </script>
    <meta name="description" content="HiTune Music Distribution - Global Music Distribution Platform">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'HiTune Music Distribution'; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@6.5.95/css/materialdesignicons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);
            color: #fff;
            min-height: 100vh;
        }
        
        /* Modern Navigation */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: rgba(15, 12, 41, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 2px solid rgba(0, 183, 255, 0.2);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.3);
        }
        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 80px;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: #fff;
            font-weight: 800;
            font-size: 24px;
            letter-spacing: -0.5px;
        }
        .logo-icon {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #00b7ff, #8b5cf6);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        .nav-links {
            display: flex;
            align-items: center;
            gap: 40px;
        }
        .nav-links a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            font-size: 15px;
            font-weight: 500;
            transition: all 0.3s;
            position: relative;
        }
        .nav-links a:hover {
            color: #00b7ff;
        }
        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, #00b7ff, #8b5cf6);
            transition: width 0.3s;
        }
        .nav-links a:hover::after {
            width: 100%;
        }
        .nav-links a.active {
            color: #00b7ff;
        }
        .nav-links a.active::after {
            width: 100%;
        }
        .nav-cta {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        .btn {
            padding: 12px 28px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            letter-spacing: 0.5px;
        }
        .btn-outline {
            background: transparent;
            color: #fff;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: #00b7ff;
            color: #00b7ff;
            transform: translateY(-2px);
        }
        .btn-primary {
            background: linear-gradient(135deg, #00b7ff, #8b5cf6);
            color: #fff;
            box-shadow: 0 4px 20px rgba(0, 183, 255, 0.4);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 30px rgba(0, 183, 255, 0.6);
        }
        .btn-success {
            background: linear-gradient(135deg, #00c853, #00e676);
            color: #fff;
            box-shadow: 0 4px 20px rgba(0, 200, 83, 0.4);
        }
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 30px rgba(0, 200, 83, 0.6);
        }
        /* Main Content */
        .main-content {
            padding-top: 80px;
            min-height: 100vh;
        }
        /* Responsive */
        @media (max-width: 968px) {
            .nav-links {
                display: none;
            }
            .nav-container {
                padding: 0 20px;
            }
        }

        /* Light theme */
        html[data-theme="light"] body {
            background: #f4f7fb !important;
            color: #0f172a !important;
        }
        html[data-theme="light"] .navbar {
            background: rgba(255, 255, 255, 0.92) !important;
            border-bottom-color: rgba(15, 23, 42, 0.1) !important;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.07) !important;
        }
        html[data-theme="light"] .logo { color: #0f172a !important; }
        html[data-theme="light"] .nav-links a { color: rgba(15, 23, 42, 0.68) !important; }
        html[data-theme="light"] .nav-links a:hover,
        html[data-theme="light"] .nav-links a.active { color: #0f172a !important; }
        html[data-theme="light"] .btn-outline {
            color: #0f172a !important;
            border-color: rgba(15, 23, 42, 0.2) !important;
            background: rgba(15, 23, 42, 0.05) !important;
        }
        .theme-toggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
            cursor: pointer;
            font-size: 20px;
            flex-shrink: 0;
        }
        html[data-theme="light"] .theme-toggle {
            background: rgba(15, 23, 42, 0.05);
            border-color: rgba(15, 23, 42, 0.15);
            color: #0f172a;
        }
        html[data-theme="light"] .theme-toggle .mdi-weather-night { display: none; }
        html:not([data-theme="light"]) .theme-toggle .mdi-white-balance-sunny { display: none; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="/" class="logo">
                <div class="logo-icon">
                    <span class="mdi mdi-music"></span>
                </div>
                HiTune
            </a>
            <div class="nav-links">
                <a href="/index.php?q=home" class="<?php echo $path === '' || $path === 'home' ? 'active' : ''; ?>">Home</a>
                <a href="/index.php?q=pricing" class="<?php echo $path === 'pricing' ? 'active' : ''; ?>">Pricing</a>
                <a href="/index.php?q=releases" class="<?php echo $path === 'releases' ? 'active' : ''; ?>">Releases</a>
                <a href="/index.php?q=analytics" class="<?php echo $path === 'analytics' ? 'active' : ''; ?>">Analytics</a>
                <a href="/index.php?q=revenue" class="<?php echo $path === 'revenue' ? 'active' : ''; ?>">Revenue</a>
                <a href="/index.php?q=royalties" class="<?php echo $path === 'royalties' ? 'active' : ''; ?>">Royalties</a>
                <a href="/index.php?q=payouts" class="<?php echo $path === 'payouts' ? 'active' : ''; ?>">Payouts</a>
                <a href="/index.php?q=stores" class="<?php echo $path === 'stores' ? 'active' : ''; ?>">Stores</a>
            </div>
            <div class="nav-cta">
                <button class="theme-toggle" type="button" aria-label="Toggle light/dark mode" title="Toggle theme">
                    <span class="mdi mdi-weather-night" aria-hidden="true"></span>
                    <span class="mdi mdi-white-balance-sunny" aria-hidden="true"></span>
                </button>
                <?php
                $isLoggedIn = false;
                try {
                    if (file_exists(__DIR__ . '/../config.php') && file_exists(__DIR__ . '/includes/auth.php')) {
                        require_once __DIR__ . '/../config.php';
                        require_once __DIR__ . '/includes/auth.php';
                        $isLoggedIn = isLoggedIn();
                    }
                } catch (Exception $e) {
                    // Ignore auth errors
                }
                
                if ($isLoggedIn): ?>
                    <a href="/index.php?q=releases" class="btn btn-outline">
                        <span class="mdi mdi-view-dashboard"></span>
                        Dashboard
                    </a>
                    <a href="/index.php?q=logout" class="btn btn-success">
                        <span class="mdi mdi-logout"></span>
                        Logout
                    </a>
                <?php else: ?>
                    <a href="/index.php?q=login" class="btn btn-outline">
                        <span class="mdi mdi-login"></span>
                        Log In
                    </a>
                    <a href="/index.php?q=signup" class="btn btn-primary">
                        <span class="mdi mdi-account-plus"></span>
                        Sign Up
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <main class="main-content">
