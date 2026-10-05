<?php
/**
 * Reset Password Page
 * Allows user to set a new password using a valid reset token
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/sso_sync.php';

$error = '';
$success = '';
$token = trim($_GET['token'] ?? '');
$validToken = false;
$userId = null;

// Validate token
if (!empty($token)) {
    // Auto-create table if not exists
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

    $stmt = $conn->prepare("SELECT pr.user_id FROM password_resets pr WHERE pr.token = ? AND pr.expires_at > NOW()");
    if ($stmt) {
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            $userId = $row['user_id'];
            $validToken = true;
        } else {
            $error = 'This reset link is invalid or has expired. Please request a new one.';
        }
        $stmt->close();
    } else {
        $error = 'Database error. Please try again.';
    }
} else {
    $error = 'Invalid reset link.';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    validateCsrfToken($_POST['csrf_token'] ?? '');
    
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($password)) {
        $error = 'Please enter a new password';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Update password
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashedPassword, $userId);
        
        if ($stmt->execute()) {
            // Delete used token
            $stmt2 = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
            $stmt2->bind_param("i", $userId);
            $stmt2->execute();
            $stmt2->close();

            // Sync the new password to the linked Hitune Music account
            $em = $conn->prepare("SELECT email FROM users WHERE id = ?");
            if ($em) {
                $em->bind_param("i", $userId);
                $em->execute();
                $emRow = $em->get_result()->fetch_assoc();
                $em->close();
                if ($emRow) {
                    sso_music_update_password($conn, $emRow['email'], $hashedPassword);
                }
            }

            $success = 'Password reset successfully! You can now login with your new password.';
            $validToken = false; // Hide form after success
        } else {
            $error = 'Failed to reset password. Please try again.';
        }
        $stmt->close();
    }
}

$pageTitle = 'Reset Password - HiTune';
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
    
    .reset-container {
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
    
    .reset-container::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            pointer-events: none;
    }
    
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .reset-header {
        text-align: center;
        margin-bottom: 40px;
    }
    
    .reset-icon {
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
    
    .reset-container h1 { font-size: 32px; font-weight: 700; margin-bottom: 10px; }
    .reset-container > p { color: rgba(255, 255, 255, 0.5); font-size: 15px; }
    
    .form-group { margin-bottom: 25px; }
    
    .form-group label {
        display: block;
        margin-bottom: 10px;
        font-size: 14px;
        font-weight: 500;
        color: rgba(255, 255, 255, 0.7);
    }
    
    .input-group { position: relative; }
    
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
    
    .toggle-pw {
        position: absolute;
        right: 18px;
        top: 50%;
        transform: translateY(-50%);
        color: rgba(255,255,255,0.4);
        font-size: 20px;
        cursor: pointer;
        transition: color 0.3s;
    }
    
    .toggle-pw:hover { color: #00b7ff; }
    
    .password-hint {
        font-size: 12px;
        color: rgba(255,255,255,0.4);
        margin-top: 8px;
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
    
    .btn-submit:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 40px rgba(0, 183, 255, 0.5);
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
    }
    
    .back-link a:hover { opacity: 0.8; }
    
    @media (max-width: 480px) {
        .reset-container { padding: 35px 25px; border-radius: 24px; }
        .reset-icon { width: 70px; height: 70px; font-size: 34px; }
    }
</style>

<section class="auth-section">
    <div class="reset-container">
        <div class="reset-header">
            <div class="reset-icon">
                <span class="mdi mdi-lock-open-variant"></span>
            </div>
            <h1>Reset Password</h1>
            <p>Enter your new password below</p>
        </div>
        
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
        
        <?php if ($validToken): ?>
        <form method="POST">
            <?php echo csrfField(); ?>
            
            <div class="form-group">
                <label for="password">New Password</label>
                <div class="input-group">
                    <span class="mdi mdi-lock input-icon"></span>
                    <input type="password" id="password" name="password" placeholder="Enter new password" required minlength="8">
                    <span class="mdi mdi-eye toggle-pw" onclick="togglePw('password', this)"></span>
                </div>
                <div class="password-hint">Minimum 8 characters</div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <div class="input-group">
                    <span class="mdi mdi-lock-check input-icon"></span>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required>
                    <span class="mdi mdi-eye toggle-pw" onclick="togglePw('confirm_password', this)"></span>
                </div>
            </div>
            
            <button type="submit" class="btn-submit">
                <span class="mdi mdi-check-bold"></span>
                Reset Password
            </button>
        </form>
        <?php endif; ?>
        
        <div class="back-link">
            <?php if ($success): ?>
                <a href="/index.php?q=login">Go to Login</a>
            <?php else: ?>
                <a href="/index.php?q=forgot_password">Request new link</a> &nbsp;·&nbsp; <a href="/index.php?q=login">Back to Login</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
function togglePw(fieldId, icon) {
    var input = document.getElementById(fieldId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('mdi-eye', 'mdi-eye-off');
    } else {
        input.type = 'password';
        icon.classList.replace('mdi-eye-off', 'mdi-eye');
    }
}
</script>

<?php include __DIR__ . '/../includes/footer_premium.php'; ?>
