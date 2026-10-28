<?php

if ( !defined( "bof_root" ) ) die;

/**
 * POST /api/v1/iyol/webhook
 * Inbound signed events from the IyolMe server.
 *
 * Signature contract (IyolMe -> HiTune):
 *   X-IYOL-Timestamp: unix seconds
 *   X-IYOL-Signature: hex hmac_sha256( "{ts}.{raw_body}", iyol_webhook_secret )
 *
 * Events: reel.published, reel.removed, sound.used, sound.takedown_ack
 */
function endpoint_iyol_webhook( $loader, $excuter, $args ){

  $send = function( $data, $http=200 ){
    if ( !headers_sent() ){
      http_response_code( $http );
      header( "Content-Type: application/json" );
    }
    echo json_encode( $data, JSON_UNESCAPED_SLASHES );
    exit;
  };

  if ( $_SERVER["REQUEST_METHOD"] !== "POST" )
    $send( array( "error" => "post_required" ), 405 );

  $iyol = bof()->iyolme;
  if ( !$iyol->enabled() )
    $send( array( "error" => "disabled" ), 503 );

  $raw = file_get_contents( "php://input" );
  $body = json_decode( (string)$raw, true );
  if ( !is_array( $body ) )
    $send( array( "error" => "invalid_json" ), 400 );

  $ok = $iyol->verify_inbound_signature( $raw );
  if ( !$ok )
    $send( array( "error" => "invalid_signature" ), 401 );

  $event = !empty( $body["event"] ) ? $body["event"] : "unknown";
  $data  = !empty( $body["data"] ) && is_array( $body["data"] ) ? $body["data"] : array();

  $iyol->log_event( $event, $body, true );

  switch ( $event ){

    case "reel.published":
      // IyolMe confirms a pushed reel is live -> notify the HiTune user
      $uid = 0;
      if ( !empty( $data["hitune_user_sub"] ) && preg_match( "/^u:([a-f0-9]{32})$/", $data["hitune_user_sub"], $m ) ){
        $u = $loader->db->_select( array(
          "table" => "_u_list", "columns" => "ID",
          "where" => array( array( "hash", "=", $m[1] ) ),
          "limit" => 1, "single" => true
        ) );
        if ( $u ) $uid = (int)$u["ID"];
      } elseif ( !empty( $data["hitune_uid"] ) ) {
        $uid = (int)$data["hitune_uid"];
      }

      if ( $uid && function_exists( "dist_notify_user" ) ){
        $url = !empty( $data["reel_url"] ) ? $data["reel_url"] : null;
        dist_notify_user( $uid,
          "Your reel is live on IyolMe",
          "Your reel was published on IyolMe." .
          ( $url ? " <a href='" . htmlspecialchars( $url ) . "'>Watch it here</a>." : "" ) .
          "<br><br>It carries your HiTune audio attribution — every listen counts toward your streams." );
      }
      break;

    case "reel.removed":
      // reel deleted on IyolMe — nothing to unwind on HiTune, event is logged
      break;

    case "sound.used":
      // a HiTune-registered sound was used in a new reel — count it as an
      // engagement view so IyolMe usage feeds the charts/popularity loop
      if ( !empty( $data["hitune_track_hash"] ) && preg_match( "/^[a-f0-9]{32}$/", $data["hitune_track_hash"] ) ){
        $h = $loader->db->real_escape_string( $data["hitune_track_hash"] );
        $loader->db->query( "UPDATE `_c_m_tracks` SET s_views = s_views + 1 WHERE hash = '{$h}' LIMIT 1" );
      }
      break;

    case "sound.takedown_ack":
      // IyolMe confirmed a takedown — mark matching outbox rows sent
      if ( !empty( $data["hitune_track_hash"] ) ){
        $hash = $loader->db->real_escape_string( $data["hitune_track_hash"] );
        $loader->db->query( "UPDATE `_iyol_outbox` o
          JOIN `_c_m_tracks` t ON o.track_id = t.ID
          SET o.status = 'sent', o.time_sent = NOW()
          WHERE o.kind = 'sound_takedown' AND t.hash = '{$hash}'" );
      }
      break;

  }

  $send( array( "received" => true, "event" => $event ) );

}

?>
