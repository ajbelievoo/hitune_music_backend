<?php
/**
 * Email Verification Page
 * Verifies user's email using token from email
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/sso_sync.php';

$error = '';
$success = '';

// Read token from query string
$token = trim($_GET['token'] ?? '');

if (empty($token)) {
    $error = 'Invalid or missing verification token.';
} else {
    // Look up user by verification_token with prepared statement
    $stmt = $conn->prepare("SELECT id, name, email, email_verified FROM users WHERE email_verification_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $error = 'Invalid or expired verification token.';
    } else {
        $user = $result->fetch_assoc();

        if ($user['email_verified'] == 1) {
            // Token already used — redirect to login with success
            $_SESSION['flash_success'] = 'Your email is already verified. You can log in.';
            header('Location: /index.php?q=login');
            exit;
        } else {
            // Set email_verified = 1, clear verification_token
            $updateStmt = $conn->prepare("UPDATE users SET email_verified = 1, email_verification_token = NULL, verification_token_expires = NULL WHERE id = ?");
            $updateStmt->bind_param("i", $user['id']);

            if ($updateStmt->execute()) {
                // Also mark the mirrored Hitune Music account verified
                sso_music_mark_verified($conn, $user['email']);

                // Redirect to login with success flash
                $_SESSION['flash_success'] = 'Your email has been verified! You can now log in.';
                header('Location: /index.php?q=login');
                exit;
            } else {
                $error = 'Failed to verify email. Please try again.';
            }
            $updateStmt->close();
        }
    }
    $stmt->close();
}

$pageTitle = 'Email Verification - HiTune Music Distribution';
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
        
        .verify-container {
            width: 100%;
            max-width: 450px;
            background: linear-gradient(135deg, rgba(255,255,255,0.03) 0%, rgba(255,255,255,0.01) 100%);
            backdrop-filter: blur(30px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 30px;
            padding: 50px;
            text-align: center;
            animation: fadeInUp 0.8s ease-out;
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .verify-icon {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            font-size: 50px;
        }
        
        .verify-icon.error {
            background: linear-gradient(135deg, #ff5252, #00b7ff);
            color: white;
            box-shadow: 0 15px 40px rgba(255, 82, 82, 0.4);
        }
        
        h1 {
            font-size: 28px;
            font-weight: 700;
            color: white;
            margin-bottom: 20px;
        }
        
        p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 16px 32px;
            border-radius: 16px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #00b7ff, #8b5cf6);
            color: white;
            box-shadow: 0 10px 30px rgba(0, 183, 255, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(0, 183, 255, 0.5);
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            margin-left: 10px;
        }
        
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        @media (max-width: 480px) {
            .verify-container {
                padding: 35px 25px;
            }
            
            .verify-icon {
                width: 80px;
                height: 80px;
                font-size: 40px;
            }
            
            h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="verify-container">
        <div class="verify-icon error">
            <span class="mdi mdi-close"></span>
        </div>
        <h1>Verification Failed</h1>
        <p><?php echo htmlspecialchars($error); ?></p>
        <a href="/index.php?q=login" class="btn btn-primary">
            <span class="mdi mdi-arrow-left"></span>
            Back to Login
        </a>
        <a href="/index.php?q=signup" class="btn btn-secondary">
            Create Account
        </a>
    </div>
</body>
</html>
