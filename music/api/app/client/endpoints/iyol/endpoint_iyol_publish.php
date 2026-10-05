<?php

if ( !defined( "bof_root" ) ) die;

/**
 * POST /api/iyol_publish   (group: user — BOF signed app call)
 *
 * "Publish as Reel on IyolMe" action. The app uploads the rendered clip
 * via /api/upload first, then calls this with file_id or media_url.
 * HiTune forwards the reel + mandatory audio attribution + a one-time
 * SSO code to IyolMe's POST /hitune/v1/reels/create-from-hitune.
 */
function endpoint_iyol_publish( $loader, $excuter, $args ){

  $iyol = bof()->iyolme;
  if ( !$iyol->configured() || !$iyol->api_base() )
    return $loader->api->set_error( "failed", array( "code" => "iyol_not_configured" ) );

  $user_id = 0;
  try {
    $u = $loader->user->check();
    $user_id = ( $u && !empty( $u->ID ) ) ? (int)$u->ID : (int) bof()->user->get( "ID" );
  } catch ( \Throwable $e ) {
    $user_id = (int) bof()->user->get( "ID" );
  }
  if ( !$user_id )
    return $loader->api->set_error( "access_denied" );

  $media_url   = $loader->nest->user_input( "post", "media_url", "url" );
  $file_id     = $loader->nest->user_input( "post", "file_id", "id" );
  $media_type  = $loader->nest->user_input( "post", "media_type", "string" ) ?: "video";
  $caption     = $loader->nest->user_input( "post", "caption", "string" );
  $duration_ms = $loader->nest->user_input( "post", "duration_ms", "int" );
  $thumb_url   = $loader->nest->user_input( "post", "thumb_url", "url" );
  $track_hash  = $loader->nest->user_input( "post", "track_hash", "md5" );
  $ai_gen      = (bool) $loader->nest->user_input( "post", "ai_generated", "boolean" );
  $ai_pct      = $loader->nest->user_input( "post", "ai_pct", "int" );

  // resolve an uploaded file id to its public URL
  if ( !$media_url && $file_id ){
    try {
      $file = $loader->object->file->select( array( "ID" => (int)$file_id ) );
      if ( $file ){
        $file = $loader->object->file->clean( $file, array() );
        $media_url = !empty( $file["web_address"] ) ? $file["web_address"] : null;
      }
    } catch ( \Throwable $e ) {}
  }

  if ( !$media_url )
    return $loader->api->set_error( "invalid_input", array( "code" => "media_required", "message" => "Send media_url or file_id" ) );

  if ( $track_hash ){
    $track = $loader->object->m_track->select( array( "hash" => $track_hash ), array( "clean" => false ) );
    if ( !$track )
      return $loader->api->set_error( "invalid_input", array( "code" => "track_not_found" ) );
  }

  $res = $iyol->push_reel( $user_id, array(
    "media_url"    => $media_url,
    "media_type"   => $media_type,
    "thumb_url"    => $thumb_url,
    "caption"      => $caption,
    "duration_ms"  => $duration_ms,
    "track_hash"   => $track_hash,
    "ai_generated" => $ai_gen,
    "ai_pct"       => $ai_pct
  ) );

  if ( !empty( $res["error"] ) )
    return $loader->api->set_error( "failed", array( "code" => $res["error"], "detail" => !empty( $res["detail"] ) ? $res["detail"] : null ) );

  unset( $res["_http_code"] );
  $loader->api->set_message( "ok", array( "iyol" => $res ) );

}

?>
