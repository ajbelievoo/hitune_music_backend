<?php
// Simple YouTube settings diagnostic
echo "=== HiTune YouTube Settings Diagnostic ===\n\n";

// Check if loader exists
$loader_path = __DIR__ . '/api/app/client/loader.php';
echo "Loader path: $loader_path\n";
echo "Loader exists: " . (file_exists($loader_path) ? "Yes" : "No") . "\n";

if (file_exists($loader_path)) {
    try {
        require_once $loader_path;
        echo "Loader included successfully\n";
        
        // Try to access BOF
        if (function_exists('bof')) {
            echo "BOF function exists\n";
            
            // Check settings
            try {
                $youtube_piped = bof()->object->db_setting->get("youtube_piped");
                echo "youtube_piped: " . ($youtube_piped ? "Enabled ({$youtube_piped})" : "Disabled or not set") . "\n";
            } catch (Exception $e) {
                echo "Error checking youtube_piped: " . $e->getMessage() . "\n";
            }
            
            try {
                $youtube_automation = bof()->object->db_setting->get("youtube_automation");
                echo "youtube_automation: " . ($youtube_automation ? "Enabled ({$youtube_automation})" : "Disabled or not set") . "\n";
            } catch (Exception $e) {
                echo "Error checking youtube_automation: " . $e->getMessage() . "\n";
            }
            
            try {
                $ut = bof()->object->db_setting->get("ut");
                echo "ut: " . ($ut ? "Enabled ({$ut})" : "Disabled or not set") . "\n";
            } catch (Exception $e) {
                echo "Error checking ut: " . $e->getMessage() . "\n";
            }
            
        } else {
            echo "BOF function not available\n";
        }
        
    } catch (Exception $e) {
        echo "Error including loader: " . $e->getMessage() . "\n";
    }
} else {
    echo "Loader file not found\n";
}

echo "\n=== Diagnostic completed ===\n";
?>