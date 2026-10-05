<?php
/**
 * Setup script for admin_artists table
 * Run this once to create the table
 */

require_once __DIR__ . '/../config.php';

$sql = "CREATE TABLE IF NOT EXISTS `admin_artists` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `artist_name` varchar(255) NOT NULL,
  `source` varchar(100) DEFAULT 'manual',
  `source_id` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `spotify_url` varchar(500) DEFAULT NULL,
  `apple_music_url` varchar(500) DEFAULT NULL,
  `youtube_channel_url` varchar(500) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `artist_name` (`artist_name`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Also modify artist_accounts to track which admin_artist was claimed
ALTER TABLE `artist_accounts` 
ADD COLUMN IF NOT EXISTS `admin_artist_id` int(11) DEFAULT NULL,
ADD KEY IF NOT EXISTS `admin_artist_id` (`admin_artist_id`);
";

// Execute each statement separately
$statements = array_filter(array_map('trim', explode(';', $sql)));

foreach ($statements as $statement) {
    if (empty($statement)) continue;
    
    if ($conn->query($statement . ';')) {
        echo "✓ Executed: " . substr($statement, 0, 50) . "...<br>";
    } else {
        echo "✗ Error: " . $conn->error . "<br>";
    }
}

echo "<br><strong>Setup complete!</strong><br>";
echo "<a href='index.php'>Go to Admin Dashboard</a>";

$conn->close();
?>
