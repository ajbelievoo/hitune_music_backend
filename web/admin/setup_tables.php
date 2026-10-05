<?php
/**
 * Database Table Setup Script
 * Run this to create missing payments and settings tables
 */

require_once __DIR__ . '/../config.php';

$success = [];
$errors = [];

// Create Settings Table
$sql_settings = "CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_group` varchar(50) DEFAULT 'general',
  `description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($sql_settings)) {
    $success[] = "Settings table created successfully";
} else {
    $errors[] = "Error creating settings table: " . $conn->error;
}

// Insert default settings
$default_settings = [
    ['razorpay_enabled', '1', 'payment', 'Enable Razorpay payment gateway'],
    ['razorpay_test_mode', '1', 'payment', 'Use Razorpay test mode'],
    ['razorpay_key_id', 'rzp_test_YOUR_KEY', 'payment', 'Razorpay Key ID'],
    ['razorpay_key_secret', '', 'payment', 'Razorpay Key Secret'],
    ['cashfree_enabled', '1', 'payment', 'Enable Cashfree payment gateway'],
    ['cashfree_test_mode', '1', 'payment', 'Use Cashfree test mode'],
    ['cashfree_app_id', 'YOUR_APP_ID', 'payment', 'Cashfree App ID'],
    ['cashfree_secret_key', '', 'payment', 'Cashfree Secret Key']
];

$stmt = $conn->prepare("INSERT IGNORE INTO settings (setting_key, setting_value, setting_group, description) VALUES (?, ?, ?, ?)");
foreach ($default_settings as $setting) {
    $stmt->bind_param("ssss", $setting[0], $setting[1], $setting[2], $setting[3]);
    $stmt->execute();
}
$success[] = "Default settings inserted";

// Create Payments Table
$sql_payments = "CREATE TABLE IF NOT EXISTS `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `subscription_id` int(11) DEFAULT NULL,
  `plan_name` varchar(100) NOT NULL,
  `plan_amount` decimal(10,2) NOT NULL,
  `payment_gateway` enum('razorpay','cashfree') NOT NULL,
  `payment_order_id` varchar(255) DEFAULT NULL,
  `payment_id` varchar(255) DEFAULT NULL,
  `payment_status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `payment_signature` varchar(500) DEFAULT NULL,
  `payment_response` text DEFAULT NULL,
  `amount_paid` decimal(10,2) DEFAULT 0.00,
  `currency` varchar(10) DEFAULT 'INR',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `subscription_id` (`subscription_id`),
  KEY `payment_order_id` (`payment_order_id`),
  KEY `payment_status` (`payment_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($sql_payments)) {
    $success[] = "Payments table created successfully";
} else {
    $errors[] = "Error creating payments table: " . $conn->error;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Setup</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #0a0a0a;
            color: #fff;
            padding: 50px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: rgba(255,255,255,0.05);
            padding: 30px;
            border-radius: 15px;
        }
        h1 { color: #00c853; }
        .success {
            background: rgba(0, 200, 83, 0.2);
            color: #00c853;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .error {
            background: rgba(0, 183, 255, 0.2);
            color: #00b7ff;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .btn {
            display: inline-block;
            padding: 15px 30px;
            background: linear-gradient(135deg, #00b7ff, #8b5cf6);
            color: #fff;
            text-decoration: none;
            border-radius: 10px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>✅ Database Setup Complete!</h1>
        
        <?php foreach ($success as $msg): ?>
            <div class="success">✓ <?php echo $msg; ?></div>
        <?php endforeach; ?>
        
        <?php foreach ($errors as $msg): ?>
            <div class="error">✗ <?php echo $msg; ?></div>
        <?php endforeach; ?>
        
        <a href="payments.php" class="btn">Go to Payments Page</a>
        <a href="settings.php" class="btn">Go to Settings Page</a>
    </div>
</body>
</html>
