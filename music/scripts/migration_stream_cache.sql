-- HiTune Music — instant-play stream cache + track youtube_id
-- Apply: mysql -u musicpro -p musicpro < scripts/migration_stream_cache.sql

-- Resolved YouTube/googlevideo stream URLs, shared across all clients.
-- `expire_at` is the unix timestamp parsed from the URL's `expire` param
-- (googlevideo URLs stay valid ~6h); fallback TTL applied when absent.
CREATE TABLE IF NOT EXISTS `_bof_cache_streams` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `stream_key` varchar(96) NOT NULL,
  `url` text NOT NULL,
  `mime` varchar(96) DEFAULT NULL,
  `type` varchar(16) DEFAULT NULL,
  `duration` int(11) DEFAULT NULL,
  `expire_at` int(11) DEFAULT NULL,
  `used` int(11) NOT NULL DEFAULT 0,
  `time_add` timestamp NOT NULL DEFAULT current_timestamp(),
  `time_used` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`ID`),
  UNIQUE KEY `stream_key` (`stream_key`),
  KEY `expire_at` (`expire_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Resolved YouTube video id on the track row itself so listing/detail
-- payloads carry `youtube_id` without loading the sources relation.
ALTER TABLE `_c_m_tracks`
  ADD COLUMN IF NOT EXISTS `youtube_id` varchar(20) DEFAULT NULL AFTER `spotify_id`,
  ADD INDEX IF NOT EXISTS `youtube_id` (`youtube_id`);
