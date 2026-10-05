<?php
/**
 * Monthly contests auto-rollover. A contest is "monthly" — when its
 * window expires, the next window is the whole next calendar month.
 * Run every minute (idempotent, exits fast when nothing is due).
 */
if ( php_sapi_name() !== "cli" ) { http_response_code(404); exit; }

require_once dirname(__DIR__) . "/api/app/config.php";
require_once bof_root . "/loader.php";
require_once root . "/app/client/loader.php";

$db = bof()->db;
$r = $db->query("SELECT id FROM `_htx_contests` WHERE status='active' AND ends_at IS NOT NULL AND ends_at < NOW() LIMIT 50");
$n = 0;
while ( $r && $c = $r->fetch_assoc() ) {
  $id = (int)$c["id"];
  // next window = the calendar month after the expired ends_at
  $db->query("UPDATE `_htx_contests` SET
    starts_at = DATE_FORMAT(DATE_ADD(ends_at, INTERVAL 1 DAY), '%Y-%m-01 00:00:00'),
    ends_at   = LAST_DAY(DATE_ADD(ends_at, INTERVAL 1 DAY)) + INTERVAL 86399 SECOND
    WHERE id = {$id} AND ends_at < NOW()");
  $n++;
}
echo "rolled {$n} contest(s)\n";
