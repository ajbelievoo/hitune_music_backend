<?php
/**
 * Load PayU + Razorpay credentials from /www/wwwroot/.payment.env
 * into the BusyOwlFramework _bof_setting table.
 */

require_once __DIR__ . '/../api/app/env_loader.php';

$paymentEnv = bof_env('PAYMENT_ENV_FILE', '/www/wwwroot/.payment.env');
if (!file_exists($paymentEnv) || !is_readable($paymentEnv)) {
    fwrite(STDERR, "Payment env file not found\n");
    exit(1);
}

$values = parse_ini_file($paymentEnv, false, INI_SCANNER_RAW);
if ($values === false) {
    fwrite(STDERR, "Failed to parse payment env file\n");
    exit(1);
}

$dbHost = bof_env('DB_HOST', 'localhost');
$dbPort = (int) bof_env('DB_PORT', 3306);
$dbName = bof_env('DB_NAME', '');
$dbUser = bof_env('DB_USER', '');
$dbPass = bof_env('DB_PASS', '');

if (!$dbName || !$dbUser) {
    fwrite(STDERR, "Database config not loaded\n");
    exit(1);
}

$mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName, $dbPort);
if ($mysqli->connect_error) {
    fwrite(STDERR, "DB connection failed\n");
    exit(1);
}

$map = [
    'gateway_payu' => '1',
    'gateway_payu_key' => $values['PAYU_KEY'] ?? '',
    'gateway_payu_salt' => $values['PAYU_SALT'] ?? '',
    'gateway_payu_mode' => $values['PAYU_MODE'] ?? 'live',
    'gateway_payu_test' => ( ($values['PAYU_MODE'] ?? '') === 'test' ? '1' : '0' ),
    'gateway_payu_client_id' => $values['PAYU_CLIENT_ID'] ?? '',
    'gateway_payu_client_secret' => $values['PAYU_CLIENT_SECRET'] ?? '',
    'gateway_payu_merchant_id' => $values['PAYU_MERCHANT_ID'] ?? '',
    'gateway_razorpay' => '1',
    'gateway_razorpay_id' => $values['RAZORPAY_KEY_ID'] ?? '',
    'gateway_razorpay_key' => $values['RAZORPAY_KEY_SECRET'] ?? '',
    'gateway_razorpay_test' => ( ($values['RAZORPAY_MODE'] ?? '') === 'test' ? '1' : '0' ),
];

foreach ($map as $var => $val) {
    $stmt = $mysqli->prepare("INSERT INTO _bof_setting (`var`, `val`, `type`) VALUES (?, ?, 'text') ON DUPLICATE KEY UPDATE `val` = VALUES(`val`)");
    $stmt->bind_param('ss', $var, $val);
    $stmt->execute();
    $stmt->close();
}

$mysqli->close();
echo "Payment env loaded into music settings.\n";
