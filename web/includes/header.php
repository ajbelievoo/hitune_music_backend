<?php
/**
 * Distribution Website - Common Header
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="HiTune Music Distribution - Get your music on Spotify, Apple Music, YouTube and 150+ streaming platforms. Keep 100% of your royalties.">
    <meta name="keywords" content="music distribution, spotify, apple music, youtube music, upload music, independent artists, royalties">
    <meta name="author" content="HiTune Music">
    <meta property="og:title" content="HiTune Music Distribution - Sell Your Music Online">
    <meta property="og:description" content="Get your music on 150+ streaming platforms. Keep 100% of your royalties.">
    <meta property="og:image" content="https://web.hitune.in/assets/images/og-image.jpg">
    <meta property="og:url" content="https://web.hitune.in">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'HiTune Music Distribution'; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@6.5.95/css/materialdesignicons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: #0a0a0a;
            color: #fff;
            min-height: 100vh;
            line-height: 1.6;
        }
        /* Navigation */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: rgba(10, 10, 10, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 70px;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: #fff;
        }
        .logo-img {
            height: 40px;
            width: auto;
        }
        .nav-links {
            display: flex;
            align-items: center;
            gap: 30px;
        }
        .nav-links a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.3s;
        }
        .nav-links a:hover {
            color: #fff;
        }
        .nav-cta {
            display: flex;
            gap: 15px;
        }
        .btn {
            padding: 10px 24px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-outline {
            background: transparent;
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: #fff;
        }
        .btn-primary {
            background: linear-gradient(135deg, #00b7ff, #8b5cf6);
            color: #fff;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 183, 255, 0.3);
        }
        .btn-green {
            background: linear-gradient(135deg, #00c853, #00e676);
            color: #fff;
        }
        .btn-green:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 200, 83, 0.3);
        }
        /* Main Content */
        .main-content {
            padding-top: 70px;
        }
        /* Gradient Text */
        .gradient-text {
            background: linear-gradient(135deg, #00b7ff, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .gradient-text-green {
            background: linear-gradient(135deg, #00c853, #00e676);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        /* Footer */
        .footer {
            background: #0a0a0a;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding: 60px 30px 30px;
            margin-top: 100px;
        }
        .footer-content {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 40px;
        }
        .footer-section h4 {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #fff;
        }
        .footer-section a {
            display: block;
            color: rgba(255, 255, 255, 0.6);
            text-decoration: none;
            font-size: 14px;
            margin-bottom: 10px;
            transition: color 0.3s;
        }
        .footer-section a:hover {
            color: #fff;
        }
        .footer-bottom {
            max-width: 1400px;
            margin: 40px auto 0;
            padding-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            color: rgba(255, 255, 255, 0.5);
            font-size: 14px;
        }
        /* Responsive */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }
            .nav-container {
                padding: 0 20px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="/" class="logo">
                <img src="/assets/logo.png" alt="HiTune Music" class="logo-img">
            </a>
            <div class="nav-links">
                <a href="/index.php?q=home" class="nav-link <?php echo $path === '' || $path === 'home' ? 'active' : ''; ?>">Home</a>
                <a href="/index.php?q=pricing" class="nav-link <?php echo $path === 'pricing' ? 'active' : ''; ?>">Pricing</a>
                <a href="/index.php?q=services" class="nav-link <?php echo $path === 'services' ? 'active' : ''; ?>">Services</a>
                <a href="/index.php?q=sell" class="nav-link <?php echo $path === 'sell' ? 'active' : ''; ?>">Sell Your Music</a>
                <a href="/index.php?q=publishing" class="nav-link <?php echo $path === 'publishing' ? 'active' : ''; ?>">Publishing</a>
                <a href="/index.php?q=splits" class="nav-link <?php echo $path === 'splits' ? 'active' : ''; ?>">Splits</a>
                <a href="/index.php?q=accelerator" class="nav-link <?php echo $path === 'accelerator' ? 'active' : ''; ?>">Accelerator</a>
            </div>
            <div class="nav-cta">
                <?php
                $isLoggedIn = false;
                try {
                    if (file_exists(__DIR__ . '/../config.php') && file_exists(__DIR__ . '/includes/auth.php')) {
                        require_once __DIR__ . '/../config.php';
                        require_once __DIR__ . '/includes/auth.php';
                        $isLoggedIn = isLoggedIn();
                    }
                } catch (Exception $e) {
                    // Ignore auth errors, show login button
                }
                
                if ($isLoggedIn): ?>
                    <a href="/index.php?q=dashboard" class="btn btn-outline">Dashboard</a>
                    <a href="/index.php?q=logout" class="btn btn-green">Logout</a>
                <?php else: ?>
                    <a href="/index.php?q=login" class="btn btn-outline">Log In</a>
                    <a href="/index.php?q=signup" class="btn btn-green">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <main class="main-content">
