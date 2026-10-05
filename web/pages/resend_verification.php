<?php
/**
 * Resend Email Verification Page
 * Allows users to request a new verification email
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/email_helper.php';

session_start();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        // Check if user exists and is not verified
        $stmt = $conn->prepare("SELECT id, name, email, email_verified FROM users WHERE email = ? AND auth_type = 'email'");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if ($user['email_verified'] == 1) {
                $success = 'Your email is already verified. You can log in now.';
            } else {
                // Generate new token
                $token = generateVerificationToken();
                $tokenExpires = date('Y-m-d H:i:s', strtotime('+24 hours'));
                
                // Update user with new token
                $updateStmt = $conn->prepare("UPDATE users SET email_verification_token = ?, verification_token_expires = ? WHERE id = ?");
                $updateStmt->bind_param("ssi", $token, $tokenExpires, $user['id']);
                
                if ($updateStmt->execute()) {
                    // Send verification email
                    if (sendVerificationEmail($user['email'], $user['name'], $token)) {
                        $success = 'Verification email has been resent! Please check your inbox (and spam folder). The link expires in 24 hours.';
                    } else {
                        $error = 'Failed to send verification email. Please try again later.';
                    }
                } else {
                    $error = 'Failed to generate new verification token. Please try again.';
                }
                
                $updateStmt->close();
            }
        } else {
            // Don't reveal if email exists or not for security
            $success = 'If an account exists with this email, a verification link has been sent.';
        }
        
        $stmt->close();
    }
}

$conn->close();

$pageTitle = 'Resend Verification Email - HiTune Music Distribution';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@7.4.47/css/materialdesignicons.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: #0a0a0f;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .resend-container {
            width: 100%;
            max-width: 450px;
            background: linear-gradient(135deg, rgba(255,255,255,0.03) 0%, rgba(255,255,255,0.01) 100%);
            backdrop-filter: blur(30px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 30px;
            padding: 50px;
            animation: fadeInUp 0.8s ease-out;
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .resend-icon {
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
        }
        
        h1 {
            font-size: 28px;
            font-weight: 700;
            color: white;
            text-align: center;
            margin-bottom: 10px;
        }
        
        .subtitle {
            color: rgba(255, 255, 255, 0.5);
            font-size: 15px;
            text-align: center;
            margin-bottom: 30px;
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
        
        .btn-resend {
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
        }
        
        .btn-resend:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(0, 183, 255, 0.5);
        }
        
        .alert {
            padding: 16px;
            border-radius: 14px;
            margin-bottom: 25px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-error {
            background: rgba(255, 82, 82, 0.1);
            border: 1px solid rgba(255, 82, 82, 0.3);
            color: #ff5252;
        }
        
        .alert-success {
            background: rgba(0, 200, 83, 0.1);
            border: 1px solid rgba(0, 200, 83, 0.3);
            color: #00c853;
        }
        
        .back-link {
            text-align: center;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
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
        
        @media (max-width: 480px) {
            .resend-container {
                padding: 35px 25px;
                border-radius: 24px;
            }
            
            .resend-icon {
                width: 70px;
                height: 70px;
                font-size: 34px;
            }
            
            h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="resend-container">
        <div class="resend-icon">
            <span class="mdi mdi-email-sync"></span>
        </div>
        
        <h1>Resend Verification</h1>
        <p class="subtitle">Enter your email to receive a new verification link</p>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <span class="mdi mdi-alert-circle"></span>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <span class="mdi mdi-check-circle"></span>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!$success || strpos($success, 'already verified') !== false): ?>
        <form method="POST">
            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-group">
                    <span class="mdi mdi-email input-icon"></span>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required>
                </div>
            </div>
            
            <button type="submit" class="btn-resend">
                <span class="mdi mdi-send"></span>
                Send Verification Email
            </button>
        </form>
        <?php endif; ?>
        
        <div class="back-link">
            <a href="/index.php?q=login">
                <span class="mdi mdi-arrow-left"></span>
                Back to Login
            </a>
        </div>
    </div>
</body>
</html>
