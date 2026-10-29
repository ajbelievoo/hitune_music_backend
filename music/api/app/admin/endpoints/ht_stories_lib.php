<?php

if ( !defined( "root" ) && !defined( "bof_root" ) ) die;

/**
 * Shared helpers for HiTune admin stories.
 * Used by: be/ht_stories (admin API) and admin/pages/page_ht_stories.php.
 *
 * Storage: `_ht_admin_stories` in the music DB.
 * IyolMe mirror: each admin story also lands in IyolMe's `stories` table
 * under the official `blitzomnii` account so it shows inside IyolMe too.
 * `repeat_forever` maps to IyolMe `is_highlighted` — those rows survive the
 * 24h purge and keep flowing through /hitune/v1/stories.
 */

function ht_admin_stories_table( $db ){

  $db->query( "CREATE TABLE IF NOT EXISTS `_ht_admin_stories` (
    `ID` int(11) NOT NULL AUTO_INCREMENT,
    `iyol_story_id` int(11) DEFAULT NULL,
    `type` tinyint(4) NOT NULL DEFAULT 0,
    `content` varchar(999) NOT NULL,
    `thumbnail` varchar(999) DEFAULT NULL,
    `duration` varchar(10) DEFAULT '5',
    `caption` varchar(500) DEFAULT NULL,
    `link_url` varchar(999) DEFAULT NULL,
    `display_name` varchar(100) DEFAULT 'HiTune',
    `avatar` varchar(999) DEFAULT NULL,
    `repeat_forever` tinyint(1) NOT NULL DEFAULT 0,
    `active` tinyint(1) NOT NULL DEFAULT 1,
    `s_views` int(11) NOT NULL DEFAULT 0,
    `time_add` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`ID`),
    KEY `active` (`active`,`time_add`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4" );

}

/** IyolMe DB connection (reads /www/wwwroot/iyolme/.env). null on failure. */
function ht_iyol_db(){

  static $con = null, $tried = false;
  if ( $tried ) return $con;
  $tried = true;

  $envFile = "/www/wwwroot/iyolme/.env";
  if ( !is_readable( $envFile ) ) return null;

  $env = array();
  foreach ( file( $envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES ) as $line ){
    if ( $line[0] === "#" || strpos( $line, "=" ) === false ) continue;
    list( $k, $v ) = explode( "=", $line, 2 );
    $env[ trim( $k ) ] = trim( $v, " \t\"'" );
  }
  if ( empty( $env["DB_DATABASE"] ) || empty( $env["DB_USERNAME"] ) ) return null;

  $con = @new mysqli(
    !empty( $env["DB_HOST"] ) ? $env["DB_HOST"] : "localhost",
    $env["DB_USERNAME"],
    isset( $env["DB_PASSWORD"] ) ? $env["DB_PASSWORD"] : "",
    $env["DB_DATABASE"],
    !empty( $env["DB_PORT"] ) ? (int)$env["DB_PORT"] : 3306
  );
  if ( $con->connect_error ){ $con = null; return null; }
  $con->set_charset( "utf8mb4" );
  return $con;

}

/** Resolve the IyolMe user_id that authors admin stories (official account). */
function ht_iyol_official_user( $con ){

  static $uid = null, $tried = false;
  if ( $tried ) return $uid;
  $tried = true;

  // Preferred: a verified user whose hitune link is the site admin account.
  $r = $con->query( "SELECT id FROM `tbl_users` WHERE hitune_username = 'admin' OR username = 'blitzomnii' ORDER BY is_verify DESC, id ASC LIMIT 1" );
  if ( $r && ( $row = $r->fetch_assoc() ) ){ $uid = (int)$row["id"]; return $uid; }
  $r = $con->query( "SELECT id FROM `tbl_users` WHERE is_verify = 1 ORDER BY id ASC LIMIT 1" );
  if ( $r && ( $row = $r->fetch_assoc() ) ){ $uid = (int)$row["id"]; return $uid; }
  return null;

}

/** Mirror a saved admin story into IyolMe's `stories` table. Returns iyol id|null. */
function ht_admin_story_iyol_sync( $story ){

  $con = ht_iyol_db();
  if ( !$con ) return null;
  $uid = ht_iyol_official_user( $con );
  if ( !$uid ) return null;

  $type      = (int) $story["type"];
  $content   = $con->real_escape_string( (string)$story["content"] );
  $thumb     = !empty( $story["thumbnail"] ) ? "'" . $con->real_escape_string( $story["thumbnail"] ) . "'" : "NULL";
  $duration  = $con->real_escape_string( (string)($story["duration"] ?? "5") );
  $highlight = !empty( $story["repeat_forever"] ) ? 1 : 0;
  $meta      = $con->real_escape_string( json_encode( array(
      "source"   => "hitune_admin",
      "caption"  => $story["caption"] ?? null,
      "link_url" => $story["link_url"] ?? null,
  ) ) );

  if ( !empty( $story["iyol_story_id"] ) ){
    $id = (int)$story["iyol_story_id"];
    $con->query( "UPDATE `stories` SET `type`={$type}, `content`='{$content}', `thumbnail`={$thumb}, `duration`='{$duration}', `is_highlighted`={$highlight}, `metadata`='{$meta}', `deleted_at`=NULL, `updated_at`=NOW() WHERE `id`={$id}" );
    if ( $con->affected_rows >= 0 ) return $id;
  }

  $con->query( "INSERT INTO `stories`
    (`user_id`,`type`,`content`,`thumbnail`,`duration`,`audience_type`,`allow_replies`,`allow_reshare`,`metadata`,`is_highlighted`,`created_at`,`updated_at`)
    VALUES ({$uid},{$type},'{$content}',{$thumb},'{$duration}',0,0,1,'{$meta}',{$highlight},NOW(),NOW())" );

  return $con->insert_id ? (int)$con->insert_id : null;

}

function ht_admin_story_iyol_delete( $iyol_story_id ){

  $con = ht_iyol_db();
  if ( !$con || !$iyol_story_id ) return;
  $id = (int)$iyol_story_id;
  $con->query( "DELETE FROM `stories` WHERE `id`={$id}" );

}

/** Shape an _ht_admin_stories row like an iyol/v1 story item for the app rail. */
function ht_admin_story_public( $row ){

  $created = strtotime( $row["time_add"] );
  return array(
    "id"        => "admin_" . (int)$row["ID"],
    "ref"       => "a:" . (int)$row["ID"],
    "type"      => (int)$row["type"],
    "content"   => $row["content"],
    "thumbnail" => !empty( $row["thumbnail"] ) ? $row["thumbnail"] : $row["content"],
    "hls_url"   => null,
    "duration"  => (int)$row["duration"],
    "created_at"=> $created,
    "caption"   => $row["caption"],
    "link_url"  => $row["link_url"],
    "is_admin"  => true,
    "repeat"    => (bool)$row["repeat_forever"],
    "user"      => array(
      "id"              => 0,
      "username"        => "hitune",
      "fullname"        => !empty( $row["display_name"] ) ? $row["display_name"] : "HiTune",
      "avatar"          => !empty( $row["avatar"] ) ? $row["avatar"] : ( !empty( $row["thumbnail"] ) ? $row["thumbnail"] : $row["content"] ),
      "hitune_username" => "",
      "is_verified"     => true,
      "is_artist_verified" => true,
      "is_admin"        => true,
    ),
    "music" => null,
  );

}


/* ---------------- engagement tracking (shared) ---------------- */

function ht_story_events_schema( $db ){
  static $done = false;
  if ( $done ) return;
  $done = true;
  $db->query( "CREATE TABLE IF NOT EXISTS `_ht_story_views` (
    ID int(11) NOT NULL AUTO_INCREMENT,
    story_ref varchar(40) NOT NULL,
    who varchar(80) NOT NULL,
    user_id int(11) NOT NULL DEFAULT 0,
    time_add timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (ID), UNIQUE KEY ref_who (story_ref, who), KEY user_id (user_id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4" );
  $db->query( "CREATE TABLE IF NOT EXISTS `_ht_story_likes` (
    ID int(11) NOT NULL AUTO_INCREMENT,
    story_ref varchar(40) NOT NULL,
    who varchar(80) NOT NULL,
    user_id int(11) NOT NULL DEFAULT 0,
    time_add timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (ID), UNIQUE KEY ref_who (story_ref, who), KEY user_id (user_id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4" );
  $db->query( "CREATE TABLE IF NOT EXISTS `_ht_story_replies` (
    ID int(11) NOT NULL AUTO_INCREMENT,
    story_ref varchar(40) NOT NULL,
    who varchar(80) NOT NULL,
    user_id int(11) NOT NULL DEFAULT 0,
    message varchar(500) NOT NULL,
    time_add timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (ID), KEY story_ref (story_ref), KEY user_id (user_id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4" );
}

/** Bulk stats for a set of story refs: views/likes/replies + liked by $who. */
function ht_story_stats_bulk( $db, $refs, $who ){
  $out = array();
  if ( !$refs ) return $out;
  ht_story_events_schema( $db );
  $in = "'" . implode( "','", array_map( function( $x ) use ( $db ){ return $db->real_escape_string( $x ); }, $refs ) ) . "'";
  foreach ( array( "views" => "_ht_story_views", "likes" => "_ht_story_likes", "replies" => "_ht_story_replies" ) as $k => $t ){
    $r = $db->query( "SELECT story_ref, COUNT(*) c FROM `{$t}` WHERE story_ref IN ({$in}) GROUP BY story_ref" );
    while ( $r && ( $row = $r->fetch_assoc() ) ) $out[ $row["story_ref"] ][ $k ] = (int) $row["c"];
  }
  if ( $who ){
    $w = $db->real_escape_string( $who );
    $r = $db->query( "SELECT story_ref FROM `_ht_story_likes` WHERE story_ref IN ({$in}) AND who='{$w}'" );
    while ( $r && ( $row = $r->fetch_assoc() ) ) $out[ $row["story_ref"] ]["liked"] = true;
  }
  return $out;
}

?>
