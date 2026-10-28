-- ============================================================================
-- Ecosystem integration migration (strategy doc: AI tagging + unified statuses)
-- Apply with: /usr/bin/php82 or mysql client against `musicpro`
-- ============================================================================

-- AI metadata on distribution submissions (mandatory per strategy doc §3)
ALTER TABLE `_dist_submissions`
    ADD COLUMN IF NOT EXISTS `ai_pct` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'AI generation percentage 0-100',
    ADD COLUMN IF NOT EXISTS `ai_tools` VARCHAR(255) NULL COMMENT 'AI tools used (Suno, Udio, etc)',
    ADD COLUMN IF NOT EXISTS `ai_declared` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Creator affirmed AI policy',
    ADD COLUMN IF NOT EXISTS `source` VARCHAR(30) NOT NULL DEFAULT 'portal' COMMENT 'portal | web_portal bridge',
    ADD COLUMN IF NOT EXISTS `web_release_id` INT(11) NULL COMMENT 'web.releases.id when bridged',
    ADD UNIQUE KEY `uk_web_release` (`web_release_id`);

-- Per-track AI percentage (optional override of submission-level value)
ALTER TABLE `_dist_tracks`
    ADD COLUMN IF NOT EXISTS `ai_pct` TINYINT UNSIGNED NOT NULL DEFAULT 0;

-- "AI Original" badge flag on the streaming catalog
ALTER TABLE `_c_m_tracks`
    ADD COLUMN IF NOT EXISTS `ai_pct` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'AI generation percentage for badge';

-- §3 fingerprinting / review queue: per-track content hash + dup flagging
ALTER TABLE `_dist_tracks`
    ADD COLUMN IF NOT EXISTS `fingerprint` VARCHAR(64) NULL AFTER `ai_pct`,
    ADD INDEX IF NOT EXISTS `idx_fingerprint` (`fingerprint`);

-- §4 fan-to-artist tipping wallet + tip ledger; §8 referral handled web-side.
CREATE TABLE IF NOT EXISTS `_htx_wallet` (
  `user_id` INT(11) UNSIGNED NOT NULL PRIMARY KEY,
  `balance` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `time_update` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `_htx_tips` (
  `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `from_user_id` INT(11) UNSIGNED NOT NULL,
  `to_user_id` INT(11) UNSIGNED NOT NULL,
  `track_id` INT(11) UNSIGNED NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `artist_amount` DECIMAL(12,2) NOT NULL,
  `platform_amount` DECIMAL(12,2) NOT NULL,
  `note` VARCHAR(255) NULL,
  `time_add` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_to` (`to_user_id`), KEY `idx_from` (`from_user_id`), KEY `idx_track` (`track_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- §8 live listening parties
CREATE TABLE IF NOT EXISTS `_htx_party` (
  `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `hash` VARCHAR(32) NOT NULL UNIQUE,
  `host_user_id` INT(11) UNSIGNED NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `track_id` INT(11) UNSIGNED NULL,
  `position_sec` INT(11) NOT NULL DEFAULT 0,
  `state_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` ENUM('live','ended') NOT NULL DEFAULT 'live',
  `s_listeners` INT(11) NOT NULL DEFAULT 0,
  `time_add` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `_htx_party_listeners` (
  `party_id` INT(11) UNSIGNED NOT NULL,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `time_join` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq` (`party_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `_htx_topups` (
  `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `currency` VARCHAR(8) DEFAULT 'INR',
  `status` ENUM('pending','paid','failed') NOT NULL DEFAULT 'pending',
  `transaction_id` VARCHAR(128) NULL,
  `time_add` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_user` (`user_id`), KEY `idx_txn` (`transaction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- §8 collab competitions
CREATE TABLE IF NOT EXISTS `_htx_contests` (
  `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(160) NOT NULL,
  `slug` VARCHAR(60) NOT NULL UNIQUE,
  `chart` ENUM('top_ai','indie','viral','top_tracks') NOT NULL DEFAULT 'indie',
  `prize` VARCHAR(255) NULL,
  `starts_at` DATETIME NULL,
  `ends_at` DATETIME NULL,
  `status` ENUM('active','closed') NOT NULL DEFAULT 'active',
  `time_add` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- AI Creator Studio job queue (doc §4/§6/§9)
-- ============================================================
CREATE TABLE IF NOT EXISTS `_htx_ai_jobs` (
  `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `type` ENUM('karaoke','master','lyrics','cover_art','clip_video') NOT NULL,
  `track_id` INT(11) UNSIGNED NULL,
  `file_id` INT(11) UNSIGNED NULL,
  `params` TEXT NULL,
  `engine` VARCHAR(40) NULL,
  `status` ENUM('pending','processing','done','failed') DEFAULT 'pending',
  `result_file_id` INT(11) UNSIGNED NULL,
  `result_path` VARCHAR(500) NULL,
  `result_url` VARCHAR(600) NULL,
  `result_data` TEXT NULL,
  `error` VARCHAR(500) NULL,
  `attempts` INT(3) DEFAULT 0,
  `time_add` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `time_done` TIMESTAMP NULL,
  INDEX `idx_status` (`status`),
  INDEX `idx_user` (`user_id`, `type`),
  INDEX `idx_track` (`track_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Royalty splits (distribution portal `web` DB) — doc §1 split royalty
CREATE TABLE IF NOT EXISTS track_splits (
  id INT AUTO_INCREMENT PRIMARY KEY,
  track_id INT NOT NULL,
  release_id INT NOT NULL,
  email VARCHAR(190) NOT NULL,
  user_id INT NULL,
  pct DECIMAL(5,2) NOT NULL,
  status ENUM('active','removed') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_track_email (track_id, email),
  KEY idx_user (user_id),
  KEY idx_release (release_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
