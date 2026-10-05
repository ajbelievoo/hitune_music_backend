-- HiTune Music — schema changes for the Flutter feature contract
-- (see BACKEND_REQUIREMENTS.md)
--
-- Applied on the `musicpro` database. Safe to re-run (idempotent guards
-- via IF NOT EXISTS / information_schema checks).

-- 1) In-app comments on tracks/albums/etc.
CREATE TABLE IF NOT EXISTS `_u_comments` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `hash` varchar(32) DEFAULT NULL,
  `user_id` int(7) NOT NULL,
  `object_name` varchar(50) NOT NULL,
  `object_id` int(11) NOT NULL,
  `text` text NOT NULL,
  `s_likes` int(6) DEFAULT 0,
  `time_add` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`ID`),
  KEY `user_id` (`user_id`),
  KEY `obj` (`object_name`,`object_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2) IAP receipts (verify_purchase endpoint) — token_hash makes the
--    endpoint idempotent across retries.
CREATE TABLE IF NOT EXISTS `_u_iap_receipts` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(7) NOT NULL,
  `platform` varchar(10) NOT NULL,
  `product_id` varchar(100) NOT NULL,
  `purchase_token` text NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `subs_plan_id` int(3) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'verified',
  `time_add` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`ID`),
  UNIQUE KEY `token_hash` (`token_hash`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3) Loudness metadata on tracks (volume normalization, -14 LUFS target).
--    Populated by scripts/measure_loudness.php (ffmpeg ebur128).
SET @col_lufs := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '_c_m_tracks' AND COLUMN_NAME = 'lufs'
);
SET @sql := IF(@col_lufs = 0,
  'ALTER TABLE `_c_m_tracks` ADD COLUMN `lufs` float DEFAULT NULL AFTER `lyrics`',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_peak := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '_c_m_tracks' AND COLUMN_NAME = 'peak_db'
);
SET @sql := IF(@col_peak = 0,
  'ALTER TABLE `_c_m_tracks` ADD COLUMN `peak_db` float DEFAULT NULL AFTER `lufs`',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
