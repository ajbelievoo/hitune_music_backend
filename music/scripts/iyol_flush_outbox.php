<?php
/**
 * music.hitune.in — IyolMe outbox flusher
 *
 * Delivers pending/failed `_iyol_outbox` rows (sound_register /
 * sound_takedown pushes to IyolMe's /hitune/v1/sounds endpoints).
 * Exits fast when the queue is empty.
 *
 * Cron (every minute):
 *   * * * * * /usr/bin/php82 /www/wwwroot/music/scripts/iyol_flush_outbox.php >> /tmp/iyol_outbox.log 2>&1
 *
 * CLI:
 *   /usr/bin/php82 scripts/iyol_flush_outbox.php [--limit=N]
 */

if ( php_sapi_name() !== "cli" ) { http_response_code(404); exit; }

require_once dirname(__DIR__) . "/api/app/config.php";
require_once bof_root . "/loader.php";
require_once root . "/app/client/loader.php";

$options = getopt( "", [ "limit::" ] );
$limit = isset($options["limit"]) ? max( 1, (int)$options["limit"] ) : 20;

$iyol = bof()->iyolme;
if ( !$iyol->configured() ){
  echo "[" . date("c") . "] iyolme not configured — skip\n";
  exit(0);
}

$res = $iyol->process_outbox( $limit );
if ( !empty($res["processed"]) )
  echo "[" . date("c") . "] processed={$res["processed"]} sent={$res["sent"]} failed={$res["failed"]}\n";
