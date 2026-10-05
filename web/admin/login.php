<?php
/**
 * HiTune Premium Admin Panel - Login
 * Glassmorphism Design with Premium Animations
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/config.php';

// session handled inside isAdmin() / adminLogin()

$error = '';

// Check if already logged in
if (isAdmin()) {
    header('Location: index.php');
    exit;
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter username and password';
    } else {
        if (adminLogin($username, $password)) {
            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid credentials';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | HiTune Music Distribution</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@7.4.47/css/materialdesignicons.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary-gradient: linear-gradient(135deg, #00b7ff 0%, #8b5cf6 50%, #ff6b9d 100%);
            --secondary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --bg-dark: #0a0a0f;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg-dark);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow: hidden;
            position: relative;
        }
        
        /* Animated Background */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(ellipse at 20% 20%, rgba(0, 183, 255, 0.2) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 80%, rgba(102, 126, 234, 0.2) 0%, transparent 50%),
                radial-gradient(ellipse at 50% 50%, rgba(139, 92, 246, 0.1) 0%, transparent 70%);
            animation: bgPulse 8s ease-in-out infinite;
            z-index: -1;
        }
        
        @keyframes bgPulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        /* Floating Particles */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: -1;
            pointer-events: none;
        }
        
        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(0, 183, 255, 0.3);
            border-radius: 50%;
            animation: float 15s infinite;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(100vh) rotate(0deg);
                opacity: 0;
            }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% {
                transform: translateY(-100vh) rotate(720deg);
                opacity: 0;
            }
        }
        
        /* Login Container */
        .login-wrapper {
            width: 100%;
            max-width: 450px;
            animation: fadeInUp 0.8s ease-out;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 30px;
            padding: 50px;
            box-shadow: 
                0 25px 80px rgba(0, 0, 0, 0.5),
                inset 0 1px 0 rgba(255, 255, 255, 0.05);
            position: relative;
            overflow: hidden;
        }
        
        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        }
        
        /* Login Header */
        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .admin-badge {
            width: 90px;
            height: 90px;
            background: var(--primary-gradient);
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            font-size: 44px;
            color: white;
            position: relative;
            animation: badgeGlow 3s ease-in-out infinite, badgeFloat 3s ease-in-out infinite;
        }
        
        @keyframes badgeGlow {
            0%, 100% { box-shadow: 0 0 30px rgba(0, 183, 255, 0.5); }
            50% { box-shadow: 0 0 60px rgba(0, 183, 255, 0.8); }
        }
        
        @keyframes badgeFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }
        
        .admin-badge::before {
            content: '';
            position: absolute;
            inset: -2px;
            background: var(--primary-gradient);
            border-radius: 26px;
            z-index: -1;
            opacity: 0.5;
            filter: blur(10px);
            animation: badgeGlow 3s ease-in-out infinite;
        }
        
        .login-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: white;
            margin-bottom: 8px;
        }
        
        .login-header p {
            color: rgba(255, 255, 255, 0.5);
            font-size: 15px;
        }
        
        /* Form Styles */
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 10px;
            padding-left: 5px;
        }
        
        .input-group {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.4);
            font-size: 20px;
            transition: color 0.3s;
        }
        
        .form-control {
            width: 100%;
            padding: 16px 18px 16px 52px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            font-size: 15px;
            color: white;
            transition: all 0.3s;
            font-family: inherit;
        }
        
        .form-control:focus {
            outline: none;
            border-color: rgba(0, 183, 255, 0.5);
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 25px rgba(0, 183, 255, 0.15);
        }
        
        .form-control:focus + .input-icon,
        .input-group:focus-within .input-icon {
            color: #00b7ff;
        }
        
        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.3);
        }
        
        /* Password Toggle */
        .password-toggle {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.4);
            cursor: pointer;
            font-size: 20px;
            transition: color 0.3s;
            padding: 0;
        }
        
        .password-toggle:hover {
            color: rgba(255, 255, 255, 0.8);
        }
        
        .form-control.with-toggle {
            padding-right: 52px;
        }
        
        /* Remember & Forgot */
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }
        
        .remember-me input {
            width: 18px;
            height: 18px;
            accent-color: #00b7ff;
            cursor: pointer;
        }
        
        .remember-me span {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.6);
        }
        
        .forgot-link {
            font-size: 14px;
            color: #00b7ff;
            text-decoration: none;
            transition: opacity 0.3s;
        }
        
        .forgot-link:hover {
            opacity: 0.8;
        }
        
        /* Login Button */
        .btn-login {
            width: 100%;
            padding: 18px;
            background: var(--primary-gradient);
            border: none;
            border-radius: 16px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 183, 255, 0.3);
        }
        
        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.6s;
        }
        
        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(0, 183, 255, 0.5);
        }
        
        .btn-login:hover::before {
            left: 100%;
        }
        
        .btn-login:active {
            transform: translateY(-1px);
        }
        
        .btn-login .mdi {
            font-size: 20px;
        }
        
        /* Error Message */
        .alert {
            padding: 16px;
            border-radius: 14px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            animation: shake 0.5s;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        .alert-error {
            background: rgba(255, 82, 82, 0.15);
            border: 1px solid rgba(255, 82, 82, 0.3);
            color: #ff5252;
        }
        
        .alert .mdi {
            font-size: 20px;
        }
        
        /* Security Badge */
        .security-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            color: rgba(255, 255, 255, 0.4);
            font-size: 13px;
        }
        
        .security-badge .mdi {
            color: #00c853;
            font-size: 16px;
        }
        
        /* Back Link */
        .back-link {
            text-align: center;
            margin-top: 25px;
        }
        
        .back-link a {
            color: rgba(255, 255, 255, 0.5);
            text-decoration: none;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: color 0.3s;
        }
        
        .back-link a:hover {
            color: #00b7ff;
        }
        
        /* Responsive */
        @media (max-width: 480px) {
            .login-card {
                padding: 35px 25px;
                border-radius: 24px;
            }
            
            .login-header h1 {
                font-size: 24px;
            }
            
            .admin-badge {
                width: 75px;
                height: 75px;
                font-size: 36px;
            }
            
            .form-options {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <!-- Floating Particles -->
    <div class="particles" id="particles"></div>
    
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-header">
                <div class="admin-badge">
                    <span class="mdi mdi-shield-account"></span>
                </div>
                <h1>Admin Access</h1>
                <p>HiTune Music Distribution</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <span class="mdi mdi-alert-circle"></span>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" id="loginForm">
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <div class="input-group">
                        <span class="mdi mdi-account input-icon"></span>
                        <input type="text" name="username" class="form-control" placeholder="Enter your username" required autofocus>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="mdi mdi-lock input-icon"></span>
                        <input type="password" name="password" class="form-control with-toggle" id="password" placeholder="Enter your password" required>
                        <button type="button" class="password-toggle" id="togglePassword" aria-label="Toggle password visibility">
                            <span class="mdi mdi-eye-outline" id="toggleIcon"></span>
                        </button>
                    </div>
                </div>
                
                <div class="form-options">
                    <label class="remember-me">
                        <input type="checkbox" name="remember" id="remember">
                        <span>Remember me</span>
                    </label>
                    <a href="#" class="forgot-link">Forgot password?</a>
                </div>
                
                <button type="submit" class="btn-login">
                    <span>Sign In to Dashboard</span>
                    <span class="mdi mdi-arrow-right"></span>
                </button>
            </form>
            
            <div class="security-badge">
                <span class="mdi mdi-shield-check"></span>
                <span>Secure 256-bit SSL encrypted connection</span>
            </div>
        </div>
        
        <div class="back-link">
            <a href="/index.php?q=home">
                <span class="mdi mdi-arrow-left"></span>
                Back to Website
            </a>
        </div>
    </div>
    
    <script>
        // Generate floating particles
        const particlesContainer = document.getElementById('particles');
        const particleCount = 25;
        
        for (let i = 0; i < particleCount; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.left = Math.random() * 100 + '%';
            particle.style.animationDelay = Math.random() * 15 + 's';
            particle.style.animationDuration = (15 + Math.random() * 10) + 's';
            particle.style.opacity = Math.random() * 0.5 + 0.2;
            particle.style.width = (Math.random() * 4 + 2) + 'px';
            particle.style.height = particle.style.width;
            particlesContainer.appendChild(particle);
        }
        
        // Password toggle
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.getElementById('toggleIcon');
        
        togglePassword.addEventListener('click', () => {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            toggleIcon.className = type === 'password' ? 'mdi mdi-eye-outline' : 'mdi mdi-eye-off-outline';
        });
        
        // Form animation on submit
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = this.querySelector('.btn-login');
            btn.innerHTML = '<span class="mdi mdi-loading mdi-spin"></span> Signing in...';
            btn.style.opacity = '0.8';
        });
    </script>
</body>
</html>
