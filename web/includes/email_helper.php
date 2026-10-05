<?php
/**
 * Email Helper - Sends emails using DB SMTP settings or PHP mail()
 */

// Bundled PHPMailer (no composer on PHP 7.4 runtime)
if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
    $pmDir = __DIR__ . '/phpmailer';
    require_once $pmDir . '/Exception.php';
    require_once $pmDir . '/PHPMailer.php';
    require_once $pmDir . '/SMTP.php';
    require_once $pmDir . '/OAuthTokenProvider.php';
    require_once $pmDir . '/OAuth.php';
    require_once $pmDir . '/DSNConfigurator.php';
    require_once $pmDir . '/POP3.php';
}

/**
 * Get email settings from DB
 */
function getEmailSettings() {
    global $conn;
    $keys = ['smtp_host','smtp_port','smtp_username','smtp_password','from_email','from_name','smtp_ssl',
             'site_title','logo_url','site_url','favicon_url','footer_copyright'];
    $settings = [
        'from_email'       => 'noreply@hitune.in',
        'from_name'        => 'HiTune Music',
        'site_title'       => 'HiTune Music Distribution',
        'logo_url'         => '',
        'site_url'         => 'https://web.hitune.in',
        'footer_copyright' => '© ' . date('Y') . ' HiTune Music Distribution',
    ];
    if (!isset($conn)) return $settings;
    $in = implode(',', array_fill(0, count($keys), '?'));
    $stmt = $conn->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ($in)");
    if (!$stmt) return $settings;
    $stmt->bind_param(str_repeat('s', count($keys)), ...$keys);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    $stmt->close();
    return $settings;
}

/**
 * Send email using SMTP (via PHPMailer if available) or PHP mail()
 */
function sendEmail($to, $toName, $subject, $htmlBody) {
    $cfg = getEmailSettings();
    $fromEmail = $cfg['from_email'] ?: 'noreply@hitune.in';
    $fromName  = $cfg['from_name']  ?: 'HiTune Music';

    // Try PHPMailer if available
    if (class_exists('PHPMailer\PHPMailer\PHPMailer') && !empty($cfg['smtp_host'])) {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = $cfg['smtp_host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $cfg['smtp_username'];
            $mail->Password   = $cfg['smtp_password'];
            $mail->SMTPSecure = ($cfg['smtp_ssl'] === '1') ? 'ssl' : 'tls';
            $mail->Port       = (int)($cfg['smtp_port'] ?: 587);
            $mail->CharSet    = 'UTF-8';
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($to, $toName);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            return $mail->send();
        } catch (Exception $e) {
            error_log("PHPMailer error: " . $e->getMessage());
        }
    }

    // Fallback: PHP mail() — MIME-encode subject so UTF-8 doesn't require SMTPUTF8
    $encodedSubject = mb_encode_mimeheader($subject, 'UTF-8');
    $headers  = "From: {$fromName} <{$fromEmail}>\r\n";
    $headers .= "Reply-To: {$fromEmail}\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    return mail($to, $encodedSubject, $htmlBody, $headers);
}

/**
 * Build a branded email HTML template
 */
function buildEmailTemplate($title, $preheader, $bodyHtml) {
    $cfg = getEmailSettings();
    $siteTitle = htmlspecialchars($cfg['site_title'] ?: 'HiTune Music Distribution');
    $siteUrl   = htmlspecialchars($cfg['site_url']   ?: 'https://web.hitune.in');
    $copyright = htmlspecialchars($cfg['footer_copyright'] ?: '© ' . date('Y') . ' HiTune Music Distribution');
    $logoUrl   = $cfg['logo_url'] ?: '';

    // If logo_url is relative, make it absolute (PHP 7 compatible)
    if ($logoUrl && strpos($logoUrl, 'http') !== 0) {
        $logoUrl = rtrim($cfg['site_url'], '/') . '/' . ltrim($logoUrl, '/');
    }

    $logoHtml = $logoUrl
        ? "<img src=\"" . htmlspecialchars($logoUrl) . "\" alt=\"{$siteTitle}\" style=\"height:45px;max-width:200px;object-fit:contain;\">"
        : "<span style=\"font-size:22px;font-weight:800;color:#ffffff;letter-spacing:-0.5px;\">{$siteTitle}</span>";

    return "<!DOCTYPE html>
<html lang=\"en\">
<head>
<meta charset=\"UTF-8\">
<meta name=\"viewport\" content=\"width=device-width,initial-scale=1\">
<meta name=\"color-scheme\" content=\"light dark\">
<title>{$title}</title>
<style>
  body,table,td,a{-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%}
  body{margin:0;padding:0;background:#f0f2f5;font-family:'Segoe UI',Arial,sans-serif}
  img{border:0;outline:none;text-decoration:none}
  table{border-collapse:collapse}
</style>
</head>
<body style=\"margin:0;padding:0;background:#f0f2f5;\">
<!-- Preheader (hidden) -->
<div style=\"display:none;max-height:0;overflow:hidden;mso-hide:all;\">{$preheader}&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;</div>

<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"background:#f0f2f5;padding:40px 20px;\">
  <tr><td align=\"center\">
    <table width=\"600\" cellpadding=\"0\" cellspacing=\"0\" style=\"max-width:600px;width:100%;\">

      <!-- Header -->
      <tr>
        <td style=\"background:linear-gradient(135deg,#00b7ff 0%,#8b5cf6 100%);border-radius:16px 16px 0 0;padding:32px 40px;text-align:center;\">
          <a href=\"{$siteUrl}\" style=\"text-decoration:none;display:inline-block;\">
            {$logoHtml}
          </a>
        </td>
      </tr>

      <!-- Body -->
      <tr>
        <td style=\"background:#ffffff;padding:40px;border-left:1px solid #e8e8e8;border-right:1px solid #e8e8e8;\">
          {$bodyHtml}
        </td>
      </tr>

      <!-- Footer -->
      <tr>
        <td style=\"background:#1a1a2e;border-radius:0 0 16px 16px;padding:24px 40px;text-align:center;\">
          <p style=\"margin:0 0 8px;color:rgba(255,255,255,0.5);font-size:12px;\">{$copyright}</p>
          <p style=\"margin:0;font-size:12px;\">
            <a href=\"{$siteUrl}\" style=\"color:#00b7ff;text-decoration:none;\">{$siteTitle}</a>
            &nbsp;·&nbsp;
            <a href=\"{$siteUrl}/index.php?q=privacy\" style=\"color:rgba(255,255,255,0.4);text-decoration:none;\">Privacy Policy</a>
          </p>
        </td>
      </tr>

    </table>
  </td></tr>
</table>
</body>
</html>";
}

/**
 * Send email verification link
 */
function sendVerificationEmail($email, $name, $token) {
    $cfg = getEmailSettings();
    $siteUrl    = rtrim($cfg['site_url'] ?: 'https://web.hitune.in', '/');
    $siteTitle  = $cfg['site_title'] ?: 'HiTune Music Distribution';
    $verifyUrl  = $siteUrl . '/index.php?q=verify_email&token=' . urlencode($token);
    $firstName  = htmlspecialchars(explode(' ', trim($name))[0]);

    $body = "
      <h2 style=\"margin:0 0 8px;font-size:26px;font-weight:800;color:#1a1a2e;\">Verify Your Email</h2>
      <p style=\"margin:0 0 24px;color:#666;font-size:15px;\">One last step to get started</p>

      <p style=\"margin:0 0 20px;color:#333;font-size:15px;line-height:1.7;\">
        Hi <strong>{$firstName}</strong>, welcome to {$siteTitle}! 🎵<br>
        Please verify your email address to activate your account and start distributing your music worldwide.
      </p>

      <div style=\"text-align:center;margin:32px 0;\">
        <a href=\"{$verifyUrl}\"
           style=\"display:inline-block;background:linear-gradient(135deg,#00b7ff,#8b5cf6);color:#ffffff;text-decoration:none;padding:16px 40px;border-radius:50px;font-size:16px;font-weight:700;letter-spacing:0.3px;box-shadow:0 8px 24px rgba(0,183,255,0.35);\">
          ✓ &nbsp; Verify My Email
        </a>
      </div>

      <p style=\"margin:0 0 12px;color:#555;font-size:14px;line-height:1.6;\">
        Or copy and paste this link into your browser:
      </p>
      <div style=\"background:#f8f8f8;border:1px solid #e0e0e0;border-radius:8px;padding:12px 16px;word-break:break-all;font-size:13px;color:#555;font-family:monospace;\">
        {$verifyUrl}
      </div>

      <p style=\"margin:24px 0 0;color:#999;font-size:13px;\">
        This link expires in <strong>24 hours</strong>. If you didn't create an account, you can safely ignore this email.
      </p>
    ";

    $html = buildEmailTemplate(
        "Verify Your Email - {$siteTitle}",
        "Please verify your email to activate your {$siteTitle} account.",
        $body
    );

    return sendEmail($email, $name, "Verify Your Email - {$siteTitle}", $html);
}

/**
 * Send welcome email after verification
 */
function sendWelcomeEmail($email, $name) {
    $cfg = getEmailSettings();
    $siteUrl   = rtrim($cfg['site_url'] ?: 'https://web.hitune.in', '/');
    $siteTitle = $cfg['site_title'] ?: 'HiTune Music Distribution';
    $firstName = htmlspecialchars(explode(' ', trim($name))[0]);
    $loginUrl  = $siteUrl . '/index.php?q=login';

    $body = "
      <h2 style=\"margin:0 0 8px;font-size:26px;font-weight:800;color:#1a1a2e;\">You're all set! 🎉</h2>
      <p style=\"margin:0 0 24px;color:#666;font-size:15px;\">Welcome to {$siteTitle}</p>

      <p style=\"margin:0 0 20px;color:#333;font-size:15px;line-height:1.7;\">
        Hi <strong>{$firstName}</strong>, your email has been verified successfully.<br>
        You're now ready to distribute your music to 150+ platforms worldwide.
      </p>

      <div style=\"background:#fff8f8;border:1px solid #ffe0e0;border-radius:12px;padding:20px 24px;margin:24px 0;\">
        <p style=\"margin:0 0 12px;font-weight:700;color:#1a1a2e;font-size:14px;\">What you can do with {$siteTitle}:</p>
        <table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">
          <tr><td style=\"padding:6px 0;color:#555;font-size:14px;\">🎵 &nbsp; Distribute to Spotify, Apple Music, YouTube & 150+ more</td></tr>
          <tr><td style=\"padding:6px 0;color:#555;font-size:14px;\">💰 &nbsp; Keep 100% of your royalties</td></tr>
          <tr><td style=\"padding:6px 0;color:#555;font-size:14px;\">📊 &nbsp; Get detailed analytics and insights</td></tr>
          <tr><td style=\"padding:6px 0;color:#555;font-size:14px;\">🎤 &nbsp; Claim your artist profiles on major platforms</td></tr>
        </table>
      </div>

      <div style=\"text-align:center;margin:32px 0;\">
        <a href=\"{$loginUrl}\"
           style=\"display:inline-block;background:linear-gradient(135deg,#00b7ff,#8b5cf6);color:#ffffff;text-decoration:none;padding:16px 40px;border-radius:50px;font-size:16px;font-weight:700;letter-spacing:0.3px;box-shadow:0 8px 24px rgba(0,183,255,0.35);\">
          🚀 &nbsp; Go to Dashboard
        </a>
      </div>

      <p style=\"margin:0;color:#999;font-size:13px;text-align:center;\">
        Need help? Reply to this email or visit our <a href=\"{$siteUrl}/index.php?q=help\" style=\"color:#00b7ff;\">Help Center</a>.
      </p>
    ";

    $html = buildEmailTemplate(
        "Welcome to {$siteTitle}!",
        "Your account is verified. Start distributing your music today!",
        $body
    );

    return sendEmail($email, $name, "Welcome to {$siteTitle}! 🎵", $html);
}

/**
 * Send password reset email
 */
function sendPasswordResetEmail($email, $name, $token) {
    $cfg = getEmailSettings();
    $siteUrl   = rtrim($cfg['site_url'] ?: 'https://web.hitune.in', '/');
    $siteTitle = $cfg['site_title'] ?: 'HiTune Music Distribution';
    $resetUrl  = $siteUrl . '/index.php?q=reset_password&token=' . urlencode($token);
    $firstName = htmlspecialchars(explode(' ', trim($name))[0]);

    $body = "
      <h2 style=\"margin:0 0 8px;font-size:26px;font-weight:800;color:#1a1a2e;\">Reset Your Password</h2>
      <p style=\"margin:0 0 24px;color:#666;font-size:15px;\">We received a password reset request</p>

      <p style=\"margin:0 0 20px;color:#333;font-size:15px;line-height:1.7;\">
        Hi <strong>{$firstName}</strong>,<br>
        We received a request to reset the password for your {$siteTitle} account. Click the button below to create a new password.
      </p>

      <div style=\"text-align:center;margin:32px 0;\">
        <a href=\"{$resetUrl}\"
           style=\"display:inline-block;background:linear-gradient(135deg,#00b7ff,#8b5cf6);color:#ffffff;text-decoration:none;padding:16px 40px;border-radius:50px;font-size:16px;font-weight:700;letter-spacing:0.3px;box-shadow:0 8px 24px rgba(0,183,255,0.35);\">
          🔑 &nbsp; Reset My Password
        </a>
      </div>

      <p style=\"margin:0 0 12px;color:#555;font-size:14px;line-height:1.6;\">
        Or copy and paste this link into your browser:
      </p>
      <div style=\"background:#f8f8f8;border:1px solid #e0e0e0;border-radius:8px;padding:12px 16px;word-break:break-all;font-size:13px;color:#555;font-family:monospace;\">
        {$resetUrl}
      </div>

      <div style=\"background:#fff8e1;border:1px solid #ffe082;border-radius:8px;padding:14px 16px;margin:24px 0;\">
        <p style=\"margin:0;color:#795548;font-size:13px;\">
          ⏰ &nbsp; This link expires in <strong>1 hour</strong>.<br>
          🔒 &nbsp; If you didn't request this, please ignore this email. Your password will remain unchanged.
        </p>
      </div>
    ";

    $html = buildEmailTemplate(
        "Reset Your Password - {$siteTitle}",
        "Click the link to reset your {$siteTitle} account password.",
        $body
    );

    return sendEmail($email, $name, "Reset Your Password - {$siteTitle}", $html);
}

/**
 * Generate a random token
 */
function generateVerificationToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Notify a user that their release status changed.
 */
function sendReleaseStatusEmail($email, $name, $releaseTitle, $newStatus, $adminNotes = '') {
    $cfg = getEmailSettings();
    $siteTitle = $cfg['site_title'] ?: 'HiTune Music Distribution';
    $siteUrl   = rtrim($cfg['site_url'] ?: 'https://web.hitune.in', '/');

    $map = [
        'submitted'          => ['Submitted', '#ffc107', 'We have received your release and it is queued for review.'],
        'in_progress'        => ['In Review', '#4facfe', 'Our team is reviewing your release right now — metadata, audio quality and artwork.'],
        'ready'              => ['Ready for Delivery', '#00c853', 'Your release has been approved and is being delivered to the selected stores.'],
        'live'               => ['Live on Stores', '#00d4aa', 'Congratulations! Your release is now live on streaming platforms. Check your dashboard for per-store links.'],
        'rejected'           => ['Needs Changes', '#ff5252', 'Your release needs a few changes before it can be delivered. Please check the notes below, fix the issues and resubmit.'],
        'takedown_requested' => ['Takedown Requested', '#ffc107', 'We have received your takedown request and it is being processed.'],
        'taken_down'         => ['Taken Down', '#9e9e9e', 'Your release has been taken down from stores.'],
    ];
    $info = $map[$newStatus] ?? [ucfirst(str_replace('_', ' ', $newStatus)), '#888', 'Your release status has been updated.'];
    list($label, $color, $desc) = $info;

    $title    = htmlspecialchars($releaseTitle);
    $safeName = htmlspecialchars($name);
    $statusLabel = htmlspecialchars($label);

    $body = "
        <h2 style='margin:0 0 16px;color:#1a1a2e;font-size:22px;'>Hi {$safeName},</h2>
        <p style='color:#444;line-height:1.6;margin:0 0 20px;'>Your release <b>\"{$title}\"</b> has a status update:</p>
        <div style='text-align:center;margin:24px 0;'>
            <span style='display:inline-block;background:{$color};color:#fff;font-weight:700;font-size:15px;padding:10px 26px;border-radius:30px;'>{$statusLabel}</span>
        </div>
        <p style='color:#444;line-height:1.6;margin:0 0 16px;'>{$desc}</p>"
        . ($adminNotes !== '' ? "
        <div style='background:#fff8e1;border-left:4px solid #ffc107;padding:14px 18px;border-radius:8px;margin:0 0 16px;'>
            <b style='color:#7a5d00;'>Note from our team:</b>
            <div style='color:#5d4a00;line-height:1.5;'>" . nl2br(htmlspecialchars($adminNotes)) . "</div>
        </div>" : "") . "
        <p style='margin:24px 0 0;text-align:center;'>
            <a href='{$siteUrl}/index.php?q=dashboard' style='display:inline-block;background:linear-gradient(135deg,#00b7ff,#8b5cf6);color:#fff;text-decoration:none;font-weight:700;padding:14px 32px;border-radius:12px;'>View Dashboard</a>
        </p>";

    $subject = "Release Update: \"{$releaseTitle}\" — {$label}";
    return sendEmail($email, $name, $subject, buildEmailTemplate($subject, $desc, $body));
}
