-- IyolMe integration schema (HiTune side)
-- Also auto-created lazily by iyolme->ensure_tables(); this file is the
-- canonical DDL for review/manual runs.

CREATE TABLE IF NOT EXISTS `_oauth_clients` (
  `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `client_id` VARCHAR(64) NOT NULL UNIQUE,
  `secret_hash` CHAR(64) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `redirect_uris` TEXT NULL,
  `scopes` VARCHAR(255) DEFAULT 'profile email attribution',
  `status` ENUM('active','disabled') DEFAULT 'active',
  `time_add` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `_oauth_codes` (
  `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `code_hash` CHAR(64) NOT NULL UNIQUE,
  `client_id` VARCHAR(64) NOT NULL,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `redirect_uri` VARCHAR(500) NULL,
  `scope` VARCHAR(255) NULL,
  `expires` INT(11) UNSIGNED NOT NULL,
  `used` TINYINT(1) DEFAULT 0,
  `time_add` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_lookup` (`code_hash`, `used`, `expires`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `_oauth_tokens` (
  `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `token_hash` CHAR(64) NOT NULL UNIQUE,
  `client_id` VARCHAR(64) NOT NULL,
  `user_id` INT(11) UNSIGNED NULL,
  `type` ENUM('access','refresh') NOT NULL,
  `scope` VARCHAR(255) NULL,
  `expires` INT(11) UNSIGNED NOT NULL,
  `revoked` TINYINT(1) DEFAULT 0,
  `time_add` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_lookup` (`token_hash`, `revoked`, `expires`),
  INDEX `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `_iyol_outbox` (
  `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `kind` VARCHAR(40) NOT NULL,
  `track_id` INT(11) UNSIGNED NULL,
  `payload` TEXT NULL,
  `status` ENUM('pending','sent','failed') DEFAULT 'pending',
  `attempts` INT(3) DEFAULT 0,
  `last_error` VARCHAR(255) NULL,
  `time_add` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `time_sent` TIMESTAMP NULL,
  INDEX `idx_status` (`status`),
  INDEX `idx_track` (`track_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `_iyol_events` (
  `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `event` VARCHAR(60) NOT NULL,
  `payload` TEXT NULL,
  `signature_ok` TINYINT(1) DEFAULT 0,
  `time_add` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_event` (`event`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
