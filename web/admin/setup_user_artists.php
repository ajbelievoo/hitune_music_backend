<?php
/**
 * Setup script for user_artists table
 * User artist request system - user adds, admin approves
 */

require_once __DIR__ . '/../config.php';

$sql = "CREATE TABLE IF NOT EXISTS `user_artists` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `artist_name` varchar(255) NOT NULL,
  `spotify_url` varchar(500) DEFAULT NULL,
  `apple_music_url` varchar(500) DEFAULT NULL,
  `youtube_channel_url` varchar(500) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `requested_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `approved_at` timestamp NULL DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `is_claimed_youtube` tinyint(1) DEFAULT 0,
  `is_claimed_spotify` tinyint(1) DEFAULT 0,
  `is_claimed_apple` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  CONSTRAINT `user_artists_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

if ($conn->query($sql)) {
    echo "✓ user_artists table created successfully!<br>";
} else {
    echo "✗ Error: " . $conn->error . "<br>";
}

echo "<br><strong>Setup complete!</strong><br>";
echo "<a href='index.php'>Go to Admin Dashboard</a>";

$conn->close();
?>
