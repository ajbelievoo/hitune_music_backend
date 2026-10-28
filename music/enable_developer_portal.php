<?php
require_once __DIR__ . '/api/app/config.php';
require_once bof_root . '/loader.php';
require_once root . '/app/client/_setup/db.php';

// Check if setting exists
$result = bof()->db->query("SELECT * FROM _bof_setting WHERE setting_key = 'developer_portal_enabled'");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "Current value: " . $row['setting_value'] . "\n";
    // Update
    bof()->db->query("UPDATE _bof_setting SET setting_value = '1' WHERE setting_key = 'developer_portal_enabled'");
    echo "Updated to 1\n";
} else {
    echo "Not found, inserting...\n";
    bof()->db->query("INSERT INTO _bof_setting (setting_key, setting_value) VALUES ('developer_portal_enabled', '1')");
    echo "Inserted\n";
}

// Verify
$result = bof()->db->query("SELECT * FROM _bof_setting WHERE setting_key = 'developer_portal_enabled'");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "Final value: " . $row['setting_value'] . "\n";
}
