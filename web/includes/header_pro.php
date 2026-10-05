<?php
/**
 * web.hitune.in - Professional Premium Header
 * High-level design with animations and blur effects
 */

session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="HiTune Music Distribution - Professional Music Distribution Platform">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'HiTune Music Distribution'; ?></title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@6.9.96/css/materialdesignicons.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary: #00b7ff;
            --primary-dark: #ee5a5a;
            --secondary: #8b5cf6;
            --accent: #00d4ff;
            --success: #00e676;
            --warning: #ffc107;
            --danger: #ff5252;
            --dark: #0a0a0f;
            --darker: #050508;
            --card: rgba(255, 255, 255, 0.03);
            --card-hover: rgba(255, 255, 255, 0.06);
            --border: rgba(255, 255, 255, 0.08);
            --text: #ffffff;
            --text-muted: rgba(255, 255, 255, 0.6);
            --gradient-1: linear-gradient(135deg, #00b7ff 0%, #8b5cf6 100%);
            --gradient-2: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-3: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            --glow: 0 0 40px rgba(0, 183, 255, 0.3);
            --shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--darker);
            color: var(--text);
            min-height: 100vh;
            overflow-x: hidden;
            line-height: 1.6;
        }
        
        /* Animated Background */
        .bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: -1;
            background: 
                radial-gradient(ellipse at 20% 20%, rgba(0, 183, 255, 0.15) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 80%, rgba(102, 126, 234, 0.1) 0%, transparent 50%),
                radial-gradient(ellipse at 50% 50%, rgba(139, 92, 246, 0.05) 0%, transparent 70%),
                var(--darker);
        }
        
        .bg-animation::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            right: -50%;
            bottom: -50%;
            background: 
                radial-gradient(circle at 30% 30%, rgba(0, 183, 255, 0.1) 0%, transparent 40%),
                radial-gradient(circle at 70% 70%, rgba(102, 126, 234, 0.1) 0%, transparent 40%);
            animation: bgPulse 20s ease-in-out infinite;
                pointer-events: none;
    }
        
        @keyframes bgPulse {
            0%, 100% { transform: scale(1) rotate(0deg); opacity: 0.5; }
            50% { transform: scale(1.2) rotate(5deg); opacity: 0.8; }
        }
        
        /* Premium Navigation */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: rgba(10, 10, 15, 0.7);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border-bottom: 1px solid var(--border);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .navbar.scrolled {
            background: rgba(10, 10, 15, 0.9);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.3);
        }
        
        .nav-container {
            max-width: 1440px;
            margin: 0 auto;
            padding: 0 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 80px;
        }
        
        /* Animated Logo */
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--text);
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 700;
            font-size: 26px;
            letter-spacing: -0.5px;
            transition: all 0.3s ease;
        }
        
        .logo:hover {
            transform: scale(1.05);
        }
        
        .logo-icon {
            width: 48px;
            height: 48px;
            background: var(--gradient-1);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 0 30px rgba(0, 183, 255, 0.4);
            animation: logoGlow 3s ease-in-out infinite;
        }
        
        @keyframes logoGlow {
            0%, 100% { box-shadow: 0 0 30px rgba(0, 183, 255, 0.4); }
            50% { box-shadow: 0 0 50px rgba(0, 183, 255, 0.6); }
        }
        
        .logo-icon::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.3), transparent);
            animation: shimmer 3s infinite;
                pointer-events: none;
    }
        
        @keyframes shimmer {
            0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
            100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
        }
        
        /* Navigation Links */
        .nav-links {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .nav-links a {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            padding: 10px 18px;
            border-radius: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .nav-links a::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: var(--gradient-1);
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: -1;
            border-radius: 12px;
        }
        
        .nav-links a:hover {
            color: var(--text);
            transform: translateY(-2px);
        }
        
        .nav-links a:hover::before {
            opacity: 0.1;
        }
        
        .nav-links a.active {
            color: var(--text);
            background: rgba(0, 183, 255, 0.15);
            border: 1px solid rgba(0, 183, 255, 0.3);
        }
        
        /* CTA Buttons */
        .nav-cta {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            position: relative;
            overflow: hidden;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
                pointer-events: none;
    }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .btn-outline {
            background: transparent;
            color: var(--text);
            border: 1.5px solid var(--border);
        }
        
        .btn-outline:hover {
            border-color: var(--primary);
            background: rgba(0, 183, 255, 0.1);
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 183, 255, 0.2);
        }
        
        .btn-primary {
            background: var(--gradient-1);
            color: white;
            box-shadow: 0 4px 20px rgba(0, 183, 255, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 40px rgba(0, 183, 255, 0.4);
        }
        
        .btn-success {
            background: var(--gradient-3);
            color: white;
            box-shadow: 0 4px 20px rgba(0, 230, 118, 0.3);
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 40px rgba(0, 230, 118, 0.4);
        }
        
        /* Main Content */
        .main-content {
            padding-top: 80px;
            min-height: 100vh;
        }
        
        /* Floating Particles */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            pointer-events: none;
            z-index: -1;
            overflow: hidden;
        }
        
        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(0, 183, 255, 0.3);
            border-radius: 50%;
            animation: float 20s infinite linear;
        }
        
        @keyframes float {
            0% { transform: translateY(100vh) rotate(0deg); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { transform: translateY(-100vh) rotate(720deg); opacity: 0; }
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .nav-links { display: none; }
            .nav-container { padding: 0 20px; }
        }
    </style>
</head>
<body>
    <div class="bg-animation"></div>
    
    <!-- Floating Particles -->
    <div class="particles">
        <div class="particle" style="left: 10%; animation-delay: 0s;"></div>
        <div class="particle" style="left: 20%; animation-delay: 2s;"></div>
        <div class="particle" style="left: 30%; animation-delay: 4s;"></div>
        <div class="particle" style="left: 40%; animation-delay: 6s;"></div>
        <div class="particle" style="left: 50%; animation-delay: 8s;"></div>
        <div class="particle" style="left: 60%; animation-delay: 10s;"></div>
        <div class="particle" style="left: 70%; animation-delay: 12s;"></div>
        <div class="particle" style="left: 80%; animation-delay: 14s;"></div>
        <div class="particle" style="left: 90%; animation-delay: 16s;"></div>
    </div>

    <nav class="navbar" id="navbar">
        <div class="nav-container">
            <a href="/" class="logo">
                <div class="logo-icon">
                    <span class="mdi mdi-music-note-eighth"></span>
                </div>
                HiTune
            </a>
            
            <div class="nav-links">
                <a href="/index.php?q=home" class="<?php echo $path === '' || $path === 'home' ? 'active' : ''; ?>">Home</a>
                <a href="/index.php?q=pricing" class="<?php echo $path === 'pricing' ? 'active' : ''; ?>">Pricing</a>
                <a href="/index.php?q=releases" class="<?php echo $path === 'releases' ? 'active' : ''; ?>">Releases</a>
                <a href="/index.php?q=analytics" class="<?php echo $path === 'analytics' ? 'active' : ''; ?>">Analytics</a>
                <a href="/index.php?q=revenue" class="<?php echo $path === 'revenue' ? 'active' : ''; ?>">Revenue</a>
                <a href="/index.php?q=stores" class="<?php echo $path === 'stores' ? 'active' : ''; ?>">Stores</a>
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
                } catch (Exception $e) {}
                
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
                        <span class="mdi mdi-rocket-launch"></span>
                        Get Started
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    </script>

    <main class="main-content">
