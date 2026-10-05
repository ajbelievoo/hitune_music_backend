<?php
/**
 * web.hitune.in Login Page
 * Separate login system
 */

// Don't include header yet, will include after processing
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/sso_sync.php';

// Already logged in? Redirect to dashboard or requested page
if (isLoggedIn()) {
    $redirect = $_GET['redirect'] ?? '/index.php?q=dashboard';
    header('Location: ' . $redirect);
    exit;
}

$error = '';
$success = '';

// Display flash success from email verification redirect
if (!empty($_SESSION['flash_success'])) {
    $success = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}

// Handle GET error params (e.g. from Google OAuth redirect)
if (!empty($_GET['error'])) {
    $errorMessages = [
        'google_token_failed'    => 'Google sign-in failed. Please try again.',
        'google_auth_failed'     => 'Google authentication was cancelled.',
        'google_userinfo_failed' => 'Could not retrieve Google account info. Try again.',
        'google_invalid_data'    => 'Invalid data received from Google.',
        'google_create_failed'   => 'Failed to create account via Google. Please try email signup.',
        'google_not_configured'  => 'Google sign-in is not configured. Please use email login.',
    ];
    $errorKey = $_GET['error'];
    $error = $errorMessages[$errorKey] ?? 'An error occurred. Please try again.';
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    validateCsrfToken($_POST['csrf_token'] ?? '');

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter email and password';
    } else {
        // Check user in database
        $stmt = $conn->prepare("SELECT id, email, name, password, email_verified, auth_type FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $redirect = $_GET['redirect'] ?? '/index.php?q=dashboard';

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Check if email is verified (only for email auth, not Google)
            if ($user['auth_type'] === 'email' && $user['email_verified'] == 0) {
                $error = 'Please verify your email before logging in. <a href="/index.php?q=resend_verification" style="color: #00b7ff; text-decoration: underline;">Resend verification email</a>';
            } else {
                // Verify password using password_verify
                if (password_verify($password, (string) $user['password'])) {
                    loginUser($user['id'], $user['email'], $user['name']);
                    header('Location: ' . $redirect);
                    exit;
                }

                // Local password failed — fall back to the Hitune Music account
                // (covers password changes made on music.hitune.in)
                $muser = sso_music_find($conn, $email);
                if ($muser && password_verify($password, (string) $muser['password'])) {
                    $upd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $upd->bind_param("si", $muser['password'], $user['id']);
                    $upd->execute();
                    $upd->close();
                    loginUser($user['id'], $user['email'], $user['name']);
                    header('Location: ' . $redirect);
                    exit;
                }
                $error = 'Invalid email or password.';
            }
        } else {
            // No local account — check Hitune Music account and auto-provision
            $muser = sso_music_find($conn, $email);
            if ($muser && password_verify($password, (string) $muser['password'])) {
                $newUser = sso_provision_local($conn, $muser);
                if ($newUser) {
                    loginUser($newUser['id'], $newUser['email'], $newUser['name']);
                    header('Location: ' . $redirect);
                    exit;
                }
            }
            $error = 'Invalid email or password.';
        }
    }
}

$pageTitle = 'Login - HiTune Music Distribution';
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
        background: radial-gradient(circle, rgba(0, 183, 255, 0.15) 0%, transparent 60%);
        top: -300px;
        right: -300px;
        animation: bgGlow 8s ease-in-out infinite;
            pointer-events: none;
    }
    
    @keyframes bgGlow {
        0%, 100% { transform: scale(1); opacity: 0.5; }
        50% { transform: scale(1.2); opacity: 0.8; }
    }
    
    .login-container {
        width: 100%;
        max-width: 450px;
        background: linear-gradient(135deg, rgba(255,255,255,0.03) 0%, rgba(255,255,255,0.01) 100%);
        backdrop-filter: blur(30px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 30px;
        padding: 50px;
        position: relative;
        overflow: hidden;
        animation: fadeInUp 0.8s ease-out;
    }
    
    .login-container::before {
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
    
    .login-header {
        text-align: center;
        margin-bottom: 40px;
    }
    
    .login-icon {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #00b7ff, #8b5cf6);
        border-radius: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 25px;
        font-size: 40px;
        color: white;
        box-shadow: 0 15px 40px rgba(0, 183, 255, 0.4);
        animation: iconFloat 3s ease-in-out infinite;
    }
    
    @keyframes iconFloat {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-8px); }
    }
    
    .login-container h1 {
        font-size: 32px;
        font-weight: 700;
        margin-bottom: 10px;
    }
    
    .login-container p {
        color: rgba(255, 255, 255, 0.5);
        font-size: 15px;
    }
    
    .form-group {
        margin-bottom: 25px;
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
        padding: 16px 50px 16px 52px;
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
        border-color: rgba(0, 183, 255, 0.5);
        background: rgba(255, 255, 255, 0.08);
        box-shadow: 0 0 25px rgba(0, 183, 255, 0.1);
    }
    
    .form-group input:focus + .input-icon {
        color: #00b7ff;
    }
    
    .btn-login {
        width: 100%;
        padding: 18px;
        background: linear-gradient(135deg, #00b7ff, #8b5cf6);
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
        box-shadow: 0 10px 30px rgba(0, 183, 255, 0.3);
        position: relative;
        overflow: hidden;
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
            pointer-events: none;
    }
    
    .btn-login:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 40px rgba(0, 183, 255, 0.5);
    }
    
    .btn-login:hover::before {
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
    
    .signup-link {
        text-align: center;
        margin-top: 30px;
        padding-top: 30px;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
        font-size: 15px;
        color: rgba(255, 255, 255, 0.5);
    }
    
    .signup-link a {
        color: #00b7ff;
        text-decoration: none;
        font-weight: 600;
        transition: opacity 0.3s;
    }
    
    .signup-link a:hover {
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
    
    @media (max-width: 480px) {
        .login-container {
            padding: 35px 25px;
            border-radius: 24px;
        }
        
        .login-icon {
            width: 70px;
            height: 70px;
            font-size: 34px;
        }
    }
</style>

<section class="auth-section">
    <div class="login-container">
        <div class="login-header">
            <div class="login-icon" style="overflow:hidden;">
                <img src="/assets/hitune-icon.png" alt="HiTune" style="width:100%;height:100%;object-fit:cover;border-radius:24px;" onerror="this.outerHTML='<span class=\'mdi mdi-account-circle\'></span>';">
            </div>
            <h1>Welcome Back</h1>
            <p>Sign in to your HiTune account</p>
        </div>
    
    <form method="POST">
        <?php echo csrfField(); ?>
        <?php if ($error): ?>
            <div class="error">
                <span class="mdi mdi-alert-circle"></span>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success">
                <span class="mdi mdi-check-circle"></span>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
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
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
                <span class="mdi mdi-eye toggle-password" onclick="togglePassword()" title="Show/Hide password" style="position:absolute;right:18px;top:50%;transform:translateY(-50%);color:rgba(255,255,255,0.4);font-size:20px;cursor:pointer;transition:color 0.3s;" onmouseover="this.style.color='#00b7ff'" onmouseout="this.style.color='rgba(255,255,255,0.4)'"></span>
            </div>
        </div>
        
        <div style="text-align: right; margin-top: -15px; margin-bottom: 20px;">
            <a href="/index.php?q=forgot_password" style="color: rgba(255,255,255,0.5); font-size: 13px; text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='#00b7ff'" onmouseout="this.style.color='rgba(255,255,255,0.5)'">Forgot password?</a>
        </div>
        
        <button type="submit" class="btn-login">
            <span class="mdi mdi-login"></span>
            Sign In
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
        
        <?php
        if ($googleConfigured):
            $googleRedirectUri = 'https://' . $_SERVER['HTTP_HOST'] . '/index.php?q=google_callback';
            $googleAuthUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
                'client_id'     => $googleClientId,
                'redirect_uri'  => $googleRedirectUri,
                'response_type' => 'code',
                'scope'         => 'email profile',
                'access_type'   => 'online',
                'prompt'        => 'select_account'
            ]);
        ?>
            <a href="<?php echo htmlspecialchars($googleAuthUrl); ?>" class="google-btn">
                <img src="https://www.google.com/favicon.ico" alt="Google">
                Continue with Google
            </a>
        <?php else: ?>
            <button type="button" class="google-btn disabled" onclick="alert('Google Sign-in is not configured yet.')">
                <img src="https://www.google.com/favicon.ico" alt="Google">
                Continue with Google
            </button>
        <?php endif; ?>
    </form>
    
    <div class="signup-link">
        Don't have an account? <a href="/index.php?q=signup">Create one</a>
    </div>

</div>
</section>

<?php include __DIR__ . '/../includes/footer_premium.php'; ?>
<script>
function togglePassword() {
    var input = document.getElementById('password');
    var icon = document.querySelector('.toggle-password');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('mdi-eye', 'mdi-eye-off');
    } else {
        input.type = 'password';
        icon.classList.replace('mdi-eye-off', 'mdi-eye');
    }
}
</script>
