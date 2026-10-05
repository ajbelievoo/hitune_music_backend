<?php
/**
 * music.hitune.in — AI Studio job worker
 *
 * Processes pending `_htx_ai_jobs` rows (karaoke via demucs/spleeter,
 * mastering via matchering, lyrics via whisper, cover art, clip renders).
 * ffmpeg-fallback jobs run inline inside the endpoint; heavy ML engines
 * are picked up here.
 *
 * Cron (every minute is fine — exits fast when the queue is empty):
 *   * * * * * /usr/bin/php82 /www/wwwroot/music/scripts/ai_worker.php >> /tmp/htx_ai_worker.log 2>&1
 *
 * CLI:
 *   /usr/bin/php82 scripts/ai_worker.php --limit=5
 */

if ( php_sapi_name() !== "cli" ) { http_response_code(404); exit; }

require_once dirname(__DIR__) . "/api/app/config.php";
require_once bof_root . "/loader.php";
require_once root . "/app/client/loader.php";

$options = getopt( "", [ "limit::", "help" ] );
if ( isset($options["help"]) ){
  echo "Usage: php82 scripts/ai_worker.php [--limit=N]\n";
  exit(0);
}
$limit = isset($options["limit"]) ? max( 1, (int)$options["limit"] ) : 5;

$ai = bof()->hitune_ai;
$ai->ensure_tables();

$done = $ai->process_queue( $limit );
echo date("Y-m-d H:i:s") . " processed {$done} job(s)\n";
exit(0);

?>
