<?php
/**
 * music.hitune.in — IyolMe sound backfill
 *
 * Queues catalog tracks into `_iyol_outbox` for registration as
 * IyolMe sounds (HITUNE:{hash} convention). Default: top N most-played
 * tracks + all tracks from catalog-published distribution submissions.
 *
 * CLI:
 *   /usr/bin/php82 scripts/iyol_push_sounds.php [--limit=200] [--all]
 */

if ( php_sapi_name() !== "cli" ) { http_response_code(404); exit; }

require_once dirname(__DIR__) . "/api/app/config.php";
require_once bof_root . "/loader.php";
require_once root . "/app/client/loader.php";

$options = getopt( "", [ "limit::", "all" ] );
$limit = isset($options["limit"]) ? max( 1, (int)$options["limit"] ) : 200;

$iyol = bof()->iyolme;
if ( !$iyol->configured() ){
  echo "iyolme not configured\n"; exit(1);
}
$iyol->ensure_tables();

$db = bof()->db;
$track_ids = array();

// 1. tracks from catalog-published distribution submissions (always include)
$r = $db->query( "SELECT catalog_track_ids FROM `_dist_submissions` WHERE catalog_published = 1" );
while ( $r && $row = $r->fetch_assoc() ){
  foreach ( (array) json_decode( (string)$row["catalog_track_ids"], true ) as $tid )
    $track_ids[] = (int)$tid;
}

// 2. top catalog tracks by plays
$top = isset($options["all"]) ? 100000 : $limit;
$r = $db->query( "SELECT ID FROM `_c_m_tracks` ORDER BY s_plays DESC, s_views DESC LIMIT {$top}" );
while ( $r && $row = $r->fetch_assoc() )
  $track_ids[] = (int)$row["ID"];

$track_ids = array_values( array_unique( array_filter( $track_ids ) ) );

// skip tracks already queued/registered
$done = array();
$r = $db->query( "SELECT DISTINCT track_id FROM `_iyol_outbox` WHERE kind = 'sound_register' AND status IN ('pending','sent')" );
while ( $r && $row = $r->fetch_assoc() )
  $done[ (int)$row["track_id"] ] = true;

$queued = 0; $skipped = 0;
foreach ( $track_ids as $tid ){
  if ( isset($done[$tid]) ) { $skipped++; continue; }
  $res = $iyol->queue_sound( $tid );
  if ( empty($res["error"]) ) $queued++; else $skipped++;
}

echo "candidates=" . count($track_ids) . " queued={$queued} skipped={$skipped}\n";
