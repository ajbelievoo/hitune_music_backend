<?php
echo "Starting YouTube settings check...\n";
require_once __DIR__ . '/api/app/client/loader.php';
echo "Loader included successfully\n";

// Check youtube_piped setting
$youtube_piped = bof()->object->db_setting->get("youtube_piped");
echo "youtube_piped: " . ($youtube_piped ? "Enabled" : "Disabled") . "\n";

// Check youtube_automation setting  
$youtube_automation = bof()->object->db_setting->get("youtube_automation");
echo "youtube_automation: " . ($youtube_automation ? "Enabled" : "Disabled") . "\n";

// Check ut setting
$ut = bof()->object->db_setting->get("ut");
echo "ut: " . ($ut ? "Enabled" : "Disabled") . "\n";

echo "Check completed.\n";
?>