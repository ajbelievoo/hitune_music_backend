<?php

if ( !defined( "bof_root" ) ) die;

/**
 * POST /api/ai_studio   (group: user — session call, skip_key_check)
 *
 * Strategy doc §4/§9 — AI Creator Studio job API.
 *
 *   action=tools                      -> per-tool enabled state + engines + plan gating
 *   action=submit  type=<tool>        -> enqueue a job (quota enforced)
 *        &track_id=123                -> catalog track input
 *        &file_id=456                 -> or an uploaded file input
 *        &prompt=...                  -> cover art / song_gen prompt
 *        &title=&tags=&instrumental=&seconds=   -> song_gen fields
 *        &start=12.5&duration=30      -> clip_video window (5-90s)
 *   action=status  &job_id=99         -> job detail
 *   action=list    [&type=&limit=]    -> caller's recent jobs
 *   action=quota                      -> remaining daily generations
 *
 * clip_video renders cover+extract into a vertical mp4; the app then
 * passes result file_id to /api/iyol_publish ("Publish as Reel").
 */
function endpoint_ai_studio( $loader, $excuter, $args ){

  $ai = bof()->hitune_ai;
  $ai->ensure_tables();

  $user_id = 0;
  try {
    $u = $loader->user->check();
    $user_id = ( $u && !empty( $u->ID ) ) ? (int)$u->ID : (int) bof()->user->get( "ID" );
  } catch ( \Throwable $e ) {
    $user_id = (int) bof()->user->get( "ID" );
  }
  if ( !$user_id )
    return $loader->api->set_error( "access_denied" );

  $action = $loader->nest->user_input( "post", "action", "string" ) ?: "tools";
  $plan = $ai->plan_features( $user_id );

  switch ( $action ){

    case "tools":
      return $loader->api->set_message( "ok", array(
        "tools" => $ai->tools_status( $user_id ),
        "plan"  => array(
          "key"        => $plan["plan"],
          "is_pro"     => $plan["is_pro"],
          "quota"      => $ai->quota_limit( $user_id ),
          "quota_left" => $ai->quota_left_for( $user_id ),
        ),
      ) );

    case "quota":
      return $loader->api->set_message( "ok", array(
        "quota" => array(
          "left"  => $ai->quota_left_for( $user_id ),
          "limit" => $ai->quota_limit( $user_id ),
          "plan"  => $plan["plan"],
          "pro"   => $plan["is_pro"],
        ),
      ) );

    case "submit":
      $type = $loader->nest->user_input( "post", "type", "string" );
      $track_id = $loader->nest->user_input( "post", "track_id", "int" );
      if ( !$track_id ){
        $hash = $loader->nest->user_input( "post", "track_hash", "md5" );
        if ( $hash ){
          $t = $loader->object->m_track->select( array( "hash" => $hash ), array( "clean" => false ) );
          if ( $t ) $track_id = (int)$t["ID"];
        }
      }
      $res = $ai->enqueue( $user_id, (string)$type, array(
        "track_id"   => $track_id,
        "file_id"    => $loader->nest->user_input( "post", "file_id", "int" ),
        "prompt"     => $loader->nest->user_input( "post", "prompt", "string" ),
        "start"      => $loader->nest->user_input( "post", "start", "float" ),
        "duration"   => $loader->nest->user_input( "post", "duration", "float" ),
        "caption"    => $loader->nest->user_input( "post", "caption", "string" ),
        "track_hash" => $loader->nest->user_input( "post", "track_hash", "md5" ),
        "reference_track_id" => $loader->nest->user_input( "post", "reference_track_id", "int" ),
        "reference_file_id"  => $loader->nest->user_input( "post", "reference_file_id", "int" ),
        "title"       => $loader->nest->user_input( "post", "title", "string" ),
        "tags"        => $loader->nest->user_input( "post", "tags", "string" ),
        "instrumental"=> $loader->nest->user_input( "post", "instrumental", "int" ),
        "seconds"     => $loader->nest->user_input( "post", "seconds", "int" ),
      ) );
      if ( !empty( $res["error"] ) )
        return $loader->api->set_error( "failed", array( "code" => $res["error"] ) );
      return $loader->api->set_message( "ok", array( "job" => $res ) );

    case "status":
      $job_id = (int) $loader->nest->user_input( "post", "job_id", "int" );
      $job = $ai->job( $job_id, $user_id );
      if ( !$job ) return $loader->api->set_error( "invalid_input", array( "code" => "job_not_found" ) );
      return $loader->api->set_message( "ok", array( "job" => $job ) );

    case "stats":
      return $loader->api->set_message( "ok", array(
        "stats" => $ai->stats_for( $user_id ),
        "plan"  => $plan["plan"],
      ) );

    case "list":
      $limit = $loader->nest->user_input( "post", "limit", "int" );
      // self-driving queue — user polls nudge pending jobs along (cron also runs it)
      try { $ai->process_queue( 1 ); } catch ( \Throwable $e ) {}
      return $loader->api->set_message( "ok", array( "jobs" => $ai->jobs_for( $user_id, $limit ?: 20 ) ) );

    default:
      return $loader->api->set_error( "invalid_input", array( "code" => "unknown_action" ) );
  }

}

?>
