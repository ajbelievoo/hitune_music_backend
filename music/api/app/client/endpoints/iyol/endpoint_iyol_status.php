<?php

/**
 * POST /api/iyol_status   (group: user — BOF signed app call)
 *
 * Reports whether the logged-in HiTune user has a linked IyolMe account
 * so the app can show "Connected as @user" vs "Not connected".
 */

if ( !defined( "root" ) || !defined( "bof_root" ) ) die;

function endpoint_iyol_status( $loader, $excuter, $args ){

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

  $user = $iyol->user_payload( $user_id );
  if ( !$user )
    return $loader->api->set_error( "failed", array( "code" => "user_not_found" ) );

  $res = $iyol->api_request( "POST", "/hitune/v1/link_status", array( "sub" => $user["sub"] ) );

  if ( !empty( $res["error"] ) )
    return $loader->api->set_message( "ok", array(
      "linked"     => false,
      "configured" => true,
      "reachable"  => false,
      "error"      => $res["error"]
    ) );

  $linked = !empty( $res["linked"] );
  $out = array(
    "linked"       => $linked,
    "configured"   => true,
    "reachable"    => true,
    "hitune_sub"   => $user["sub"],
    "hitune_username" => $user["username"]
  );
  if ( $linked && !empty( $res["user"] ) && is_array( $res["user"] ) ){
    $out["iyol_username"]  = $res["user"]["username"] ?? null;
    $out["iyol_fullname"]  = $res["user"]["fullname"] ?? null;
    $out["iyol_user_id"]   = isset( $res["user"]["id"] ) ? (int)$res["user"]["id"] : null;
  }

  $loader->api->set_message( "ok", $out );

}

?>
