<?php

if ( !defined( "bof_root" ) ) die;

/**
 * POST /api/iyol/story_event
 *
 * Records engagement for stories shown in the HiTune rail:
 *   action=view|like|unlike|reply   ref=a:<admin_id> | i:<iyol_story_id>
 *   message=<text>                (reply only)
 *
 * Always counts in the musicpro `_ht_story_*` tables. When the story is
 * an admin mirror AND the viewer maps to an IyolMe account
 * (tbl_users.hitune_username), the event is mirrored into IyolMe
 * (stories.view_by_user_ids / tbl_story_interactions) so IyolMe-side
 * analytics stay in sync.
 *
 * Guests can only `view` (deduped per ip+ua fingerprint).
 */

function endpoint_iyol_story_event( $loader, $excuter, $args ){

  require_once root . "/app/admin/endpoints/ht_stories_lib.php";
  $db = $loader->db;
  ht_story_events_schema( $db );

  $action = $loader->nest->user_input( "post", "action", "string" ) ?: $loader->nest->user_input( "get", "action", "string" );
  $ref    = $loader->nest->user_input( "post", "ref", "string" ) ?: $loader->nest->user_input( "get", "ref", "string" );
  if ( !$ref && isset( $_REQUEST["story_id"] ) ){
    // tolerate plain iyol story id
    $sid = (int) $_REQUEST["story_id"];
    if ( $sid > 0 ) $ref = "i:" . $sid;
  }
  $message = trim( (string) ( $loader->nest->user_input( "post", "message", "string" ) ?: $loader->nest->user_input( "get", "message", "string" ) ) );

  if ( !in_array( $action, array( "view", "like", "unlike", "reply" ), true ) )
    return $loader->api->set_error( "failed", array( "code" => "bad_action" ) );
  if ( !preg_match( '/^(a|i):(\d+)$/', (string) $ref, $m ) )
    return $loader->api->set_error( "failed", array( "code" => "bad_ref" ) );

  $ref_esc = $db->real_escape_string( $ref );

  /* ---------------- viewer identity ---------------- */
  $user_id = 0; $username = "";
  try {
    $u = $loader->user->check();
    if ( $u && !empty( $u->ID ) ){
      $user_id = (int) $u->ID;
      $username = (string) ( $u->username ?? "" );
    }
  } catch ( \Throwable $e ) {}
  // No fallback to bof()->user->get("ID") — it returns a server-side
  // default account for guests, which would misattribute their views.
  if ( !$username && $user_id ){
    $r = $db->query( "SELECT username FROM `_u_list` WHERE ID={$user_id} LIMIT 1" );
    if ( $r && ( $row = $r->fetch_assoc() ) ) $username = (string) $row["username"];
  }

  $who = $user_id
    ? "u:" . $user_id
    : "g:" . md5( ( $_SERVER["REMOTE_ADDR"] ?? "" ) . "|" . ( $_SERVER["HTTP_USER_AGENT"] ?? "" ) );

  if ( !$user_id && $action !== "view" )
    return $loader->api->set_error( "access_denied", array( "code" => "login_required" ) );
  if ( $action === "reply" && ( $message === "" || strlen( $message ) > 500 ) )
    return $loader->api->set_error( "failed", array( "code" => "bad_message" ) );

  /* ---------------- write ---------------- */
  if ( $action === "view" ){
    $db->query( "INSERT IGNORE INTO `_ht_story_views` (story_ref, who, user_id) VALUES ('{$ref_esc}', '" . $db->real_escape_string( $who ) . "', {$user_id})" );
    if ( $m[1] === "a" )
      $db->query( "UPDATE `_ht_admin_stories` SET s_views = s_views + 1 WHERE ID=" . (int) $m[2] );
  } elseif ( $action === "like" ){
    $db->query( "INSERT IGNORE INTO `_ht_story_likes` (story_ref, who, user_id) VALUES ('{$ref_esc}', '" . $db->real_escape_string( $who ) . "', {$user_id})" );
  } elseif ( $action === "unlike" ){
    $db->query( "DELETE FROM `_ht_story_likes` WHERE story_ref='{$ref_esc}' AND who='" . $db->real_escape_string( $who ) . "'" );
  } elseif ( $action === "reply" ){
    $db->query( "INSERT INTO `_ht_story_replies` (story_ref, who, user_id, message) VALUES ('{$ref_esc}', '" . $db->real_escape_string( $who ) . "', {$user_id}, '" . $db->real_escape_string( $message ) . "')" );
  }

  /* ---------------- IyolMe mirror (admin stories) ---------------- */
  if ( $m[1] === "a" ){
    try { ht_story_event_iyol_mirror( $db, (int) $m[2], $action, $user_id, $username, $message ); }
    catch ( \Throwable $e ) {}
  }

  $stats = ht_story_stats_bulk( $db, array( $ref ), $who );
  return $loader->api->set_message( "ok", array(
    "ref"     => $ref,
    "views"   => $stats[ $ref ]["views"] ?? 0,
    "likes"   => $stats[ $ref ]["likes"] ?? 0,
    "replies" => $stats[ $ref ]["replies"] ?? 0,
    "liked"   => !empty( $stats[ $ref ]["liked"] ),
  ) );
}

/**
 * Push the engagement into the IyolMe mirror row when the HiTune user
 * maps to an IyolMe account (tbl_users.hitune_username).
 */
function ht_story_event_iyol_mirror( $db, $admin_id, $action, $user_id, $username, $message ){

  $r = $db->query( "SELECT iyol_story_id FROM `_ht_admin_stories` WHERE ID=" . (int) $admin_id . " LIMIT 1" );
  if ( !$r || !( $row = $r->fetch_assoc() ) || empty( $row["iyol_story_id"] ) ) return;
  $iyol_story_id = (int) $row["iyol_story_id"];

  $env = @parse_ini_file( "/www/wwwroot/iyolme/.env", false, INI_SCANNER_RAW );
  if ( empty( $env["DB_DATABASE"] ) || empty( $env["DB_USERNAME"] ) ) return;
  $pdo = new PDO(
    "mysql:host=" . ( $env["DB_HOST"] ?? "localhost" ) . ";dbname=" . $env["DB_DATABASE"] . ";charset=utf8mb4",
    $env["DB_USERNAME"], $env["DB_PASSWORD"] ?? "",
    array( PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC )
  );

  $iyol_uid = 0;
  if ( $username !== "" ){
    $st = $pdo->prepare( "SELECT id FROM tbl_users WHERE hitune_username=? LIMIT 1" );
    $st->execute( array( $username ) );
    $iyol_uid = (int) $st->fetchColumn();
  }
  if ( !$iyol_uid ) return; // no linked IyolMe account — keep HiTune-side count only

  if ( $action === "view" ){
    $st = $pdo->prepare( "SELECT view_by_user_ids FROM stories WHERE id=?" );
    $st->execute( array( $iyol_story_id ) );
    $csv = (string) $st->fetchColumn();
    $ids = array_filter( explode( ",", $csv ) );
    if ( !in_array( (string) $iyol_uid, $ids, true ) ){
      $ids[] = $iyol_uid;
      $pdo->prepare( "UPDATE stories SET view_by_user_ids=? WHERE id=?" )
          ->execute( array( implode( ",", $ids ), $iyol_story_id ) );
    }
  } elseif ( $action === "like" ){
    $pdo->prepare( "INSERT INTO tbl_story_interactions (story_id, sticker, user_id, note, created_at, updated_at)
                   VALUES (?, 'reaction', ?, '❤️', NOW(), NOW())
                   ON DUPLICATE KEY UPDATE note='❤️', updated_at=NOW()" )
        ->execute( array( $iyol_story_id, $iyol_uid ) );
  } elseif ( $action === "unlike" ){
    $pdo->prepare( "DELETE FROM tbl_story_interactions WHERE story_id=? AND sticker='reaction' AND user_id=?" )
        ->execute( array( $iyol_story_id, $iyol_uid ) );
  } elseif ( $action === "reply" ){
    $pdo->prepare( "INSERT INTO tbl_story_interactions (story_id, sticker, user_id, note, created_at, updated_at)
                   VALUES (?, 'reply', ?, ?, NOW(), NOW())" )
        ->execute( array( $iyol_story_id, $iyol_uid, $message ) );
  }
}

?>
