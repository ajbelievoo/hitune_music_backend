<?php
/**
 * web.hitune.in Signup Page
 * Separate signup system
 */

// Don't include header yet, will include after processing
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/email_helper.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/sso_sync.php';
require_once __DIR__ . '/../includes/referral.php';

// Already logged in? Send them to the dashboard instead of showing signup
if (isLoggedIn()) {
    header('Location: /index.php?q=dashboard');
    exit;
}

// Referral capture: ?ref=CODE lands here from shared links
if (!empty($_GET['ref'])) {
    $_SESSION['ref_code'] = substr(preg_replace('/[^A-Za-z0-9]/', '', (string) $_GET['ref']), 0, 20);
}

$error = '';
$success = '';

// Handle signup form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    validateCsrfToken($_POST['csrf_token'] ?? '');

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    
    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Please fill all fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters';
    } elseif ($password !== $password_confirm) {
        $error = 'Passwords do not match';
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'An account with this email already exists.';
        } else {
            // Generate verification token
            $token = generateVerificationToken();
            $tokenExpires = date('Y-m-d H:i:s', strtotime('+24 hours'));

            // Hash password with bcrypt
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            
            // Create user with unverified email
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, auth_type, email_verified, email_verification_token, verification_token_expires, created_at) VALUES (?, ?, ?, 'email', 0, ?, ?, NOW())");
            $stmt->bind_param("sssss", $name, $email, $hashedPassword, $token, $tokenExpires);
            
            if ($stmt->execute()) {
                $new_user_id = $conn->insert_id;

                // Referral: link to the referrer + assign the new user's own code
                if (!empty($_SESSION['ref_code'])) {
                    $referrer = ref_user_by_code($conn, $_SESSION['ref_code']);
                    if ($referrer) ref_record_signup($conn, $referrer, $new_user_id);
                }
                ref_code_for($conn, $new_user_id);

                // Mirror the account to Hitune Music (verified there once
                // the web email verification completes)
                sso_music_create($conn, $name, $email, $hashedPassword, false);

                // Send verification email
                if (sendVerificationEmail($email, $name, $token)) {
                    $success = 'Account created! Please check your email to verify your account before logging in.';
                } else {
                    $error = 'Account created but failed to send verification email. Please contact support.';
                }
            } else {
                $error = 'Failed to create account';
            }
        }
    }
}

$pageTitle = 'Sign Up - HiTune Music Distribution';
include __DIR__ . '/../includes/header_premium.php';
?>

<style>
    .auth-section {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 140px 40px 80px;
        position: relative;
        overflow: hidden;
    }
    
    .auth-section::before {
        content: '';
        position: absolute;
        width: 800px;
        height: 800px;
        background: radial-gradient(circle, rgba(0, 200, 83, 0.15) 0%, transparent 60%);
        top: -300px;
        left: 50%;
        transform: translateX(-50%);
        animation: bgGlow 8s ease-in-out infinite;
            pointer-events: none;
    }
    
    @keyframes bgGlow {
        0%, 100% { transform: translateX(-50%) scale(1); opacity: 0.5; }
        50% { transform: translateX(-50%) scale(1.2); opacity: 0.8; }
    }
    
    .signup-container {
        width: 100%;
        max-width: 480px;
        background: linear-gradient(135deg, rgba(255,255,255,0.03) 0%, rgba(255,255,255,0.01) 100%);
        backdrop-filter: blur(30px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 30px;
        padding: 50px;
        position: relative;
        overflow: hidden;
        animation: fadeInUp 0.8s ease-out;
    }
    
    .signup-container::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            pointer-events: none;
    }
    
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .signup-header {
        text-align: center;
        margin-bottom: 40px;
    }
    
    .signup-icon {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #00c853, #00e676);
        border-radius: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 25px;
        font-size: 40px;
        color: white;
        box-shadow: 0 15px 40px rgba(0, 200, 83, 0.4);
        animation: iconFloat 3s ease-in-out infinite;
    }
    
    @keyframes iconFloat {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-8px); }
    }
    
    .signup-container h1 {
        font-size: 32px;
        font-weight: 700;
        margin-bottom: 10px;
    }
    
    .signup-container p {
        color: rgba(255, 255, 255, 0.5);
        font-size: 15px;
    }
    
    .form-group {
        margin-bottom: 22px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 10px;
        font-size: 14px;
        font-weight: 500;
        color: rgba(255, 255, 255, 0.7);
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
    
    .form-group input {
        width: 100%;
        padding: 16px 18px 16px 52px;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 16px;
        color: #fff;
        font-size: 15px;
        font-family: inherit;
        transition: all 0.3s;
        box-sizing: border-box;
    }
    
    .form-group input:focus {
        outline: none;
        border-color: rgba(0, 200, 83, 0.5);
        background: rgba(255, 255, 255, 0.08);
        box-shadow: 0 0 25px rgba(0, 200, 83, 0.1);
    }
    
    .form-group input:focus + .input-icon {
        color: #00c853;
    }
    
    .btn-signup {
        width: 100%;
        padding: 18px;
        background: linear-gradient(135deg, #00c853, #00e676);
        color: #fff;
        border: none;
        border-radius: 16px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.4s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        box-shadow: 0 10px 30px rgba(0, 200, 83, 0.3);
        position: relative;
        overflow: hidden;
        margin-top: 10px;
    }
    
    .btn-signup::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        transition: left 0.6s;
            pointer-events: none;
    }
    
    .btn-signup:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 40px rgba(0, 200, 83, 0.5);
    }
    
    .btn-signup:hover::before {
        left: 100%;
    }
    
    .error {
        background: rgba(255, 82, 82, 0.1);
        border: 1px solid rgba(255, 82, 82, 0.3);
        color: #ff5252;
        padding: 16px;
        border-radius: 14px;
        margin-bottom: 25px;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 10px;
        animation: shake 0.5s;
    }
    
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        75% { transform: translateX(5px); }
    }
    
    .success {
        background: rgba(0, 200, 83, 0.1);
        border: 1px solid rgba(0, 200, 83, 0.3);
        color: #00c853;
        padding: 16px;
        border-radius: 14px;
        margin-bottom: 25px;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .login-link {
        text-align: center;
        margin-top: 30px;
        padding-top: 30px;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
        font-size: 15px;
        color: rgba(255, 255, 255, 0.5);
    }
    
    .login-link a {
        color: #00c853;
        text-decoration: none;
        font-weight: 600;
        transition: opacity 0.3s;
    }
    
    .login-link a:hover {
        opacity: 0.8;
    }
    
    .social-divider {
        display: flex;
        align-items: center;
        margin: 30px 0;
        color: rgba(255, 255, 255, 0.4);
        font-size: 14px;
    }
    
    .social-divider::before,
    .social-divider::after {
        content: '';
        flex: 1;
        height: 1px;
        background: rgba(255, 255, 255, 0.1);
    }
    
    .social-divider span {
        padding: 0 15px;
    }
    
    .google-btn {
        width: 100%;
        padding: 16px;
        background: #fff;
        color: #333;
        border: none;
        border-radius: 16px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.4s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        text-decoration: none;
    }
    
    .google-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }
    
    .google-btn img {
        width: 20px;
        height: 20px;
    }
    
    .google-btn.disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    .setup-notice {
        background: rgba(255, 193, 7, 0.1);
        border: 1px solid rgba(255, 193, 7, 0.3);
        color: #ffc107;
        padding: 12px;
        border-radius: 10px;
        font-size: 12px;
        margin-top: 10px;
    }
    
    .success-box {
        text-align: center;
        padding: 20px;
    }
    
    .success-icon {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #00c853, #00e676);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        font-size: 40px;
        color: white;
    }
    
    @media (max-width: 480px) {
        .signup-container {
            padding: 35px 25px;
            border-radius: 24px;
        }
        
        .signup-icon {
            width: 70px;
            height: 70px;
            font-size: 34px;
        }
    }
</style>

<section class="auth-section">
    <div class="signup-container">
        <div class="signup-header">
            <div class="signup-icon" style="overflow:hidden;background:linear-gradient(135deg,#00b7ff,#8b5cf6);">
                <img src="/assets/hitune-icon.png" alt="HiTune" style="width:100%;height:100%;object-fit:cover;border-radius:24px;" onerror="this.outerHTML='<span class=\'mdi mdi-account-plus\'></span>';">
            </div>
            <h1>Create Account</h1>
            <p>Join HiTune and start distributing your music</p>
        </div>
    
    <form method="POST">
        <?php echo csrfField(); ?>
        <?php if ($error): ?>
            <div class="error">
                <span class="mdi mdi-alert-circle"></span>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success">
                <span class="mdi mdi-check-circle"></span>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!$success): ?>
        <div class="form-group">
            <label for="name">Full Name</label>
            <div class="input-group">
                <span class="mdi mdi-account input-icon"></span>
                <input type="text" id="name" name="name" placeholder="Enter your full name" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
            </div>
        </div>
        
        <div class="form-group">
            <label for="email">Email Address</label>
            <div class="input-group">
                <span class="mdi mdi-email input-icon"></span>
                <input type="email" id="email" name="email" placeholder="Enter your email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
        </div>
        
        <div class="form-group">
            <label for="password">Password</label>
            <div class="input-group">
                <span class="mdi mdi-lock input-icon"></span>
                <input type="password" id="password" name="password" placeholder="Create a password" required minlength="8">
            </div>
        </div>
        
        <div class="form-group">
            <label for="password_confirm">Confirm Password</label>
            <div class="input-group">
                <span class="mdi mdi-lock-check input-icon"></span>
                <input type="password" id="password_confirm" name="password_confirm" placeholder="Confirm your password" required minlength="8">
            </div>
        </div>
        
        <button type="submit" class="btn-signup">
            <span class="mdi mdi-account-plus"></span>
            Create Account
        </button>
        
        <div class="social-divider">
            <span>OR</span>
        </div>
        
        <?php
        // Read Google OAuth settings from DB
        $googleClientId = '';
        $googleLoginEnabled = false;
        if (isset($conn)) {
            $stmt = $conn->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('google_client_id', 'google_login_enabled')");
            if ($stmt) {
                $stmt->execute();
                $result = $stmt->get_result();
                $oauthSettings = [];
                while ($row = $result->fetch_assoc()) {
                    $oauthSettings[$row['setting_key']] = $row['setting_value'];
                }
                $googleClientId = $oauthSettings['google_client_id'] ?? '';
                $googleLoginEnabled = ($oauthSettings['google_login_enabled'] ?? '0') === '1';
            }
        }
        $googleConfigured = !empty($googleClientId) && $googleLoginEnabled;
        ?>
        
        <?php if ($googleConfigured): ?>
            <a href="https://accounts.google.com/o/oauth2/v2/auth?client_id=<?php echo urlencode($googleClientId); ?>&redirect_uri=<?php echo urlencode('https://' . $_SERVER['HTTP_HOST'] . '/index.php?q=google_callback'); ?>&response_type=code&scope=email%20profile" class="google-btn">
                <img src="https://www.google.com/favicon.ico" alt="Google">
                Sign up with Google
            </a>
        <?php else: ?>
            <button type="button" class="google-btn disabled" onclick="alert('Google Sign-up is not configured yet. Please contact the administrator.')">
                <img src="https://www.google.com/favicon.ico" alt="Google">
                Sign up with Google
            </button>
            <div class="setup-notice">
                <span class="mdi mdi-information-outline"></span>
                Google Sign-up needs to be configured. Please set up Google OAuth in the admin settings.
            </div>
        <?php endif; ?>
        <?php endif; ?>
    </form>
    
    <?php if (!$success): ?>
    <div class="login-link">
        Already have an account? <a href="/index.php?q=login">Sign In</a>
    </div>
    <?php else: ?>
    <div class="success-box">
        <div class="success-icon">
            <span class="mdi mdi-email-check"></span>
        </div>
        <a href="/index.php?q=login" class="btn-signup" style="text-decoration: none; display: inline-flex; margin-top: 20px;">
            <span class="mdi mdi-login"></span>
            Go to Login
        </a>
    </div>
    <?php endif; ?>

</div>
</section>

<?php include __DIR__ . '/../includes/footer_premium.php'; ?>
