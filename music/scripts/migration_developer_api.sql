-- HiTune Music — Developer API schema (see DEVELOPER_API.md)
--
-- Applied on the `musicpro` database. Safe to re-run (idempotent via
-- IF NOT EXISTS / INSERT IGNORE on seed rows).

-- 1) Developer applications (one row = one registered third-party app)
CREATE TABLE IF NOT EXISTS `_dev_apps` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `hash` varchar(32) NOT NULL,
  `user_id` int(7) NOT NULL,
  `name` varchar(120) NOT NULL,
  `website` varchar(255) DEFAULT NULL,
  `platform` varchar(20) NOT NULL DEFAULT 'web',
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `plan_id` int(11) DEFAULT NULL,
  `plan_expire` timestamp NULL DEFAULT NULL,
  `client_id` varchar(48) NOT NULL,
  `client_secret_hash` char(64) NOT NULL,
  `publishable_key` varchar(48) NOT NULL,
  `allowed_origins` text DEFAULT NULL,
  `allowed_bundles` text DEFAULT NULL,
  `pending_plan_id` int(11) DEFAULT NULL,
  `pending_txn` varchar(80) DEFAULT NULL,
  `data` text DEFAULT NULL,
  `time_add` timestamp NOT NULL DEFAULT current_timestamp(),
  `time_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`ID`),
  UNIQUE KEY `hash` (`hash`),
  UNIQUE KEY `client_id` (`client_id`),
  UNIQUE KEY `publishable_key` (`publishable_key`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  KEY `plan_id` (`plan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2) API plan tiers (admin-editable via be/developer_* endpoints)
CREATE TABLE IF NOT EXISTS `_dev_plans` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `hash` varchar(32) NOT NULL,
  `plan_key` varchar(40) NOT NULL,
  `name` varchar(80) NOT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `currency` varchar(8) NOT NULL DEFAULT 'INR',
  `monthly_requests` int(11) DEFAULT NULL,
  `rate_limit` int(11) NOT NULL DEFAULT 10,
  `max_apps` int(11) DEFAULT NULL,
  `allow_stream` tinyint(1) NOT NULL DEFAULT 0,
  `stream_quality` varchar(20) DEFAULT NULL,
  `monthly_streams` int(11) DEFAULT NULL,
  `commercial_use` tinyint(1) NOT NULL DEFAULT 0,
  `embed_whitelabel` tinyint(1) NOT NULL DEFAULT 0,
  `contact_sales` tinyint(1) NOT NULL DEFAULT 0,
  `features` text DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `sort` int(3) NOT NULL DEFAULT 0,
  `time_add` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`ID`),
  UNIQUE KEY `hash` (`hash`),
  UNIQUE KEY `plan_key` (`plan_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `_dev_plans`
  (`hash`, `plan_key`, `name`, `price`, `monthly_requests`, `rate_limit`, `max_apps`, `allow_stream`, `stream_quality`, `monthly_streams`, `commercial_use`, `embed_whitelabel`, `contact_sales`, `features`, `sort`)
VALUES
  (MD5('devplan_sandbox'),    'sandbox',    'Sandbox',            0.00,    10000,   10,   1,    0, NULL,       NULL,   0, 0, 0, '{"previews":1,"embed":1,"metadata":1}', 0),
  (MD5('devplan_starter'),    'starter',    'Starter',            999.00,  100000,  60,   3,    0, NULL,       NULL,   1, 0, 0, '{"previews":1,"embed":1,"metadata":1}', 1),
  (MD5('devplan_pro'),        'pro',        'Pro',                4999.00, 1000000, 300,  10,   1, '192kbps',  50000, 1, 0, 0, '{"previews":1,"embed":1,"metadata":1,"stream":1}', 2),
  (MD5('devplan_enterprise'), 'enterprise', 'Unlimited / Enterprise', NULL, NULL,  1000, NULL, 1, 'lossless', NULL,  1, 1, 1, '{"previews":1,"embed":1,"metadata":1,"stream":1,"whitelabel":1,"sla":1}', 3);

-- 3) OAuth client_credentials access tokens (hashed at rest)
CREATE TABLE IF NOT EXISTS `_dev_tokens` (
  `ID` bigint(20) NOT NULL AUTO_INCREMENT,
  `app_id` int(11) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `time_expire` timestamp NOT NULL,
  `time_add` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`ID`),
  UNIQUE KEY `token_hash` (`token_hash`),
  KEY `app_id` (`app_id`),
  KEY `time_expire` (`time_expire`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4) Daily usage aggregates per app (portal graphs + monthly quota)
CREATE TABLE IF NOT EXISTS `_dev_usage` (
  `ID` bigint(20) NOT NULL AUTO_INCREMENT,
  `app_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `requests` int(11) NOT NULL DEFAULT 0,
  `streams` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `app_date` (`app_id`,`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 5) Per-minute rate-limit buckets (sliding window, self-cleaning)
CREATE TABLE IF NOT EXISTS `_dev_rate` (
  `app_id` int(11) NOT NULL,
  `bucket` int(11) NOT NULL,
  `requests` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`app_id`,`bucket`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
