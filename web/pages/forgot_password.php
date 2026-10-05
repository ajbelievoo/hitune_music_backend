<?php
/**
 * Forgot Password Page
 * Send password reset link to user's email
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/email_helper.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken($_POST['csrf_token'] ?? '');
    
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Please enter your email address';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        // Auto-create password_resets table if not exists
        $conn->query("CREATE TABLE IF NOT EXISTS `password_resets` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `token` varchar(64) NOT NULL,
            `expires_at` datetime NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `user_id` (`user_id`),
            KEY `token` (`token`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Check if email exists
        $stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ?");
        if (!$stmt) {
            $error = 'Database error. Please try again.';
        } else {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store token in database
            $stmt2 = $conn->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE token = ?, expires_at = ?");
            if ($stmt2) {
                $stmt2->bind_param("issss", $user['id'], $token, $expiry, $token, $expiry);
                $stmt2->execute();
                $stmt2->close();
            }
            
            // Send reset email using new branded template
            if (sendPasswordResetEmail($email, $user['name'], $token)) {
                $success = 'Password reset link has been sent to your email. Please check your inbox (and spam folder).';
            } else {
                $error = 'Failed to send email. Please try again later.';
            }
        } else {
            // Don't reveal if email exists or not (security)
            $success = 'If an account exists with this email, you will receive a password reset link shortly.';
        }
        $stmt->close();
        } // end if (!$stmt) else
    }
}

$pageTitle = 'Forgot Password - HiTune';
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
    
    .forgot-container {
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
    
    .forgot-container::before {
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
    
    .forgot-header {
        text-align: center;
        margin-bottom: 40px;
    }
    
    .forgot-icon {
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
    
    .forgot-container h1 {
        font-size: 32px;
        font-weight: 700;
        margin-bottom: 10px;
    }
    
    .forgot-container p {
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
        border-color: rgba(0, 183, 255, 0.5);
        background: rgba(255, 255, 255, 0.08);
        box-shadow: 0 0 25px rgba(0, 183, 255, 0.1);
    }
    
    .form-group input:focus + .input-icon {
        color: #00b7ff;
    }
    
    .btn-submit {
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
    
    .btn-submit::before {
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
    
    .btn-submit:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 40px rgba(0, 183, 255, 0.5);
    }
    
    .btn-submit:hover::before {
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
    
    .back-link {
        text-align: center;
        margin-top: 30px;
        padding-top: 30px;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
        font-size: 15px;
        color: rgba(255, 255, 255, 0.5);
    }
    
    .back-link a {
        color: #00b7ff;
        text-decoration: none;
        font-weight: 600;
        transition: opacity 0.3s;
    }
    
    .back-link a:hover {
        opacity: 0.8;
    }
    
    @media (max-width: 480px) {
        .forgot-container {
            padding: 35px 25px;
            border-radius: 24px;
        }
        
        .forgot-icon {
            width: 70px;
            height: 70px;
            font-size: 34px;
        }
    }
</style>

<section class="auth-section">
    <div class="forgot-container">
        <div class="forgot-header">
            <div class="forgot-icon">
                <span class="mdi mdi-lock-reset"></span>
            </div>
            <h1>Forgot Password?</h1>
            <p>Enter your email and we'll send you a reset link</p>
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
            
            <button type="submit" class="btn-submit">
                <span class="mdi mdi-send"></span>
                Send Reset Link
            </button>
        </form>
        
        <div class="back-link">
            Remember your password? <a href="/index.php?q=login">Back to Login</a>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer_premium.php'; ?>
