<?php

if ( !defined( "bof_root" ) ) die;

/**
 * be/ht_stories — manage admin-posted stories (admin group).
 *
 * POST action:
 *   list    -> { stories: [...] }
 *   save    -> create/update: id?, type(0|1), content, thumbnail?, duration?,
 *              caption?, link_url?, display_name?, avatar?, repeat(0|1), active?
 *   delete  -> id
 *   toggle  -> id, field = repeat|active  (flips the flag)
 */

require_once( dirname(__FILE__) . "/ht_stories_lib.php" );

function endpoint_be_ht_stories( $loader, $excuter, $args ){

  $db = $loader->db;
  ht_admin_stories_table( $db );

  $action = $loader->nest->user_input( "post", "action", "in_array",
    [ "values" => [ "list", "save", "delete", "toggle" ] ], "list" );

  if ( $action === "list" ){

    $rows = array();
    $r = $db->query( "SELECT * FROM `_ht_admin_stories` ORDER BY ID DESC LIMIT 200" );
    while ( $r && ( $row = $r->fetch_assoc() ) ){
      $row["ID"] = (int)$row["ID"];
      $row["iyol_story_id"] = $row["iyol_story_id"] !== null ? (int)$row["iyol_story_id"] : null;
      $row["type"] = (int)$row["type"];
      $row["repeat_forever"] = (int)$row["repeat_forever"];
      $row["active"] = (int)$row["active"];
      $row["s_views"] = (int)$row["s_views"];
      $rows[] = $row;
    }
    return $loader->api->set_message( "ok", array( "stories" => $rows ) );

  }

  if ( $action === "delete" ){

    $id = (int)$loader->nest->user_input( "post", "id", "int" );
    if ( $id < 1 ) return $loader->api->set_error( "invalid_id" );

    $r = $db->query( "SELECT iyol_story_id FROM `_ht_admin_stories` WHERE ID={$id} LIMIT 1" );
    $row = $r ? $r->fetch_assoc() : null;
    if ( !$row ) return $loader->api->set_error( "not_found" );

    ht_admin_story_iyol_delete( $row["iyol_story_id"] );
    $db->query( "DELETE FROM `_ht_admin_stories` WHERE ID={$id} LIMIT 1" );
    return $loader->api->set_message( "story_deleted" );

  }

  if ( $action === "toggle" ){

    $id    = (int)$loader->nest->user_input( "post", "id", "int" );
    $field = $loader->nest->user_input( "post", "field", "in_array",
      [ "values" => [ "repeat_forever", "active" ] ] );
    if ( $id < 1 || !$field ) return $loader->api->set_error( "invalid_request" );

    $r = $db->query( "SELECT * FROM `_ht_admin_stories` WHERE ID={$id} LIMIT 1" );
    $row = $r ? $r->fetch_assoc() : null;
    if ( !$row ) return $loader->api->set_error( "not_found" );

    $new = $row[$field] ? 0 : 1;
    $db->query( "UPDATE `_ht_admin_stories` SET `{$field}`={$new} WHERE ID={$id} LIMIT 1" );
    $row[$field] = $new;

    if ( $field === "repeat_forever" )
    $row["iyol_story_id"] = ht_admin_story_iyol_sync( $row );

    return $loader->api->set_message( "ok", array(
      "id" => $id, "field" => $field, "value" => $new
    ) );

  }

  // save
  $id         = (int)$loader->nest->user_input( "post", "id", "int" );
  $type       = (int)$loader->nest->user_input( "post", "type", "int", [ "min" => 0, "max" => 1 ] );
  $content    = trim( (string)$loader->nest->user_input( "post", "content", "string" ) );

  // Optional media upload — `media` file field wins over the content URL.
  if ( !empty( $_FILES["media"] ) && $_FILES["media"]["error"] === UPLOAD_ERR_OK ){

    $tmp  = $_FILES["media"]["tmp_name"];
    $size = (int)$_FILES["media"]["size"];
    $mime = function_exists( "mime_content_type" ) ? mime_content_type( $tmp ) : ( $_FILES["media"]["type"] ?? "" );

    $img = strpos( $mime, "image/" ) === 0;
    $vid = strpos( $mime, "video/" ) === 0;

    if ( !$img && !$vid )
    return $loader->api->set_error( "media_must_be_image_or_video" );
    if ( $size < 1 || $size > 40 * 1024 * 1024 )
    return $loader->api->set_error( "media_too_large" );

    $ext = strtolower( pathinfo( $_FILES["media"]["name"], PATHINFO_EXTENSION ) );
    $ext = preg_replace( "/[^a-z0-9]/", "", $ext );
    if ( $ext === "" ) $ext = $img ? "jpg" : "mp4";
    $allowed = $img ? [ "jpg","jpeg","png","webp","gif" ] : [ "mp4","webm","mov","m4v" ];
    if ( !in_array( $ext, $allowed, true ) ) $ext = $img ? "jpg" : "mp4";

    $dir = root . "/files/ht_stories";
    if ( !is_dir( $dir ) ) @mkdir( $dir, 0755, true );
    $fname = "story_" . time() . "_" . substr( md5( mt_rand() . $tmp ), 0, 8 ) . "." . $ext;
    $dest  = $dir . "/" . $fname;

    if ( !@move_uploaded_file( $tmp, $dest ) )
    return $loader->api->set_error( "media_upload_failed" );

    @chmod( $dest, 0644 );
    $content = web_address . "files/ht_stories/" . $fname;
    $type    = $img ? 0 : 1;

  }
  $thumbnail  = trim( (string)$loader->nest->user_input( "post", "thumbnail", "string" ) );
  $duration   = trim( (string)$loader->nest->user_input( "post", "duration", "string" ) );
  $caption    = trim( (string)$loader->nest->user_input( "post", "caption", "string", [ "strip_emoji" => false ] ) );
  $link_url   = trim( (string)$loader->nest->user_input( "post", "link_url", "string" ) );
  $display    = trim( (string)$loader->nest->user_input( "post", "display_name", "string" ) );
  $avatar     = trim( (string)$loader->nest->user_input( "post", "avatar", "string" ) );
  $repeat     = (int)$loader->nest->user_input( "post", "repeat", "int", [ "min" => 0, "max" => 1 ] );
  $active     = $loader->nest->user_input( "post", "active", "int", [ "min" => 0, "max" => 1 ] );

  if ( $content === "" || !preg_match( "/^https?:\\/\\//i", $content ) )
  return $loader->api->set_error( "content_url_required" );

  $e = function( $v ) use ( $db ){ return $db->real_escape_string( $v ); };
  $dur = $duration !== "" ? $duration : "5";
  if ( !is_numeric( $dur ) ) $dur = "5";

  if ( $id > 0 ){
    $db->query( "UPDATE `_ht_admin_stories` SET
      `type`={$type}, `content`='{$e($content)}', `thumbnail`='{$e($thumbnail)}',
      `duration`='{$e($dur)}', `caption`='{$e($caption)}', `link_url`='{$e($link_url)}',
      `display_name`='{$e($display)}', `avatar`='{$e($avatar)}',
      `repeat_forever`={$repeat}
      WHERE ID={$id} LIMIT 1" );
    $r = $db->query( "SELECT * FROM `_ht_admin_stories` WHERE ID={$id} LIMIT 1" );
    $story = $r ? $r->fetch_assoc() : null;
  } else {
    $act = $active === null ? 1 : (int)$active;
    $db->query( "INSERT INTO `_ht_admin_stories`
      (`type`,`content`,`thumbnail`,`duration`,`caption`,`link_url`,`display_name`,`avatar`,`repeat_forever`,`active`)
      VALUES ({$type},'{$e($content)}','{$e($thumbnail)}','{$e($dur)}','{$e($caption)}','{$e($link_url)}','{$e($display)}','{$e($avatar)}',{$repeat},{$act})" );
    $id = (int)$db->insert_id;
    $story = array(
      "ID" => $id, "type" => $type, "content" => $content, "thumbnail" => $thumbnail,
      "duration" => $dur, "caption" => $caption, "link_url" => $link_url,
      "display_name" => $display, "avatar" => $avatar,
      "repeat_forever" => $repeat, "iyol_story_id" => null,
    );
  }

  if ( !$story ) return $loader->api->set_error( "save_failed" );

  $iyol_id = ht_admin_story_iyol_sync( $story );
  if ( $iyol_id && (int)($story["iyol_story_id"] ?? 0) !== (int)$iyol_id )
  $db->query( "UPDATE `_ht_admin_stories` SET `iyol_story_id`=" . (int)$iyol_id . " WHERE ID={$id} LIMIT 1" );

  return $loader->api->set_message( "story_saved", array(
    "id" => (int)$id, "iyol_story_id" => $iyol_id
  ) );

}

?>
