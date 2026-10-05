<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_login_social_ini( $loader, $excuter, $args ){

  if ( !$loader->object->db_setting->get( "sl" ) )
  return;

  // Flutter app (Chrome Custom Tab): persist redirect_uri/return_url in session so
  // it survives every leg of the OAuth round-trip, including the alert/confirm page.
  $app_redirect = !empty( $_GET["redirect_uri"] ) ? $_GET["redirect_uri"] : ( !empty( $_GET["return_url"] ) ? $_GET["return_url"] : null );
  if ( $app_redirect !== null ){
    $parsed = parse_url( $app_redirect );
    if ( !empty( $parsed["scheme"] ) && strtolower( $parsed["scheme"] ) === "hitune" ){
      if ( session_status() !== PHP_SESSION_ACTIVE && !headers_sent() )
        @session_start();
      $_SESSION["sl_app_redirect"] = $app_redirect;
    }
  }

  // Detect app flow on the INITIATING leg only (callback legs carry code/state/
  // oauth_verifier params and an external referer, so they must not touch the flag).
  $is_callback_leg = !empty( $_GET["code"] ) || !empty( $_GET["state"] ) || !empty( $_GET["oauth_verifier"] ) || !empty( $_GET["oauth_token"] ) || !empty( $_GET["error"] );
  if ( !$is_callback_leg ){
    if ( session_status() !== PHP_SESSION_ACTIVE && !headers_sent() )
      @session_start();
    // internal state-restart redirects carry slr=1 -> keep session flags as-is
    if ( empty( $_GET["slr"] ) ){
      // fresh login attempt -> drop stale issued URL and retry marker
      unset( $_SESSION["sl_app_last_url"] );
      unset( $_SESSION["sl_app_retry"] );
      $ref = !empty( $_SERVER["HTTP_REFERER"] ) ? $_SERVER["HTTP_REFERER"] : "";
      if ( $ref === "" || stripos( $ref, "hitune.in" ) === false )
        $_SESSION["sl_app_flow"] = 1;
      else
        unset( $_SESSION["sl_app_flow"] );
    }
  }

  $alert = $loader->nest->user_input( "get", "alert", "equal", [ "value" => "true" ] );
  $supported_social_login = $loader->object->core_setting->get( "supported_social_logins" );
  $target_name = $loader->nest->user_input( "get", "target", "in_array", [ "values" => array_keys( $supported_social_login ) ] );

  if ( !$target_name )
  return;

  $target_slang = $supported_social_login[ $target_name ][ "slang" ];
  $target_enabled = $loader->object->db_setting->get( "sl_{$target_slang}" );

  if ( !$target_enabled )
  return;

  $target_id = $loader->object->db_setting->get( "sl_{$target_slang}_id" );
  $target_secret = $loader->object->db_setting->get( "sl_{$target_slang}_secret" );

  if ( !$target_id || !$target_secret )
  return;

  if ( $alert ){

    echo '<!DOCTYPE html><html><head>
      <meta charset="utf-8">
      <meta name="format-detection" content="telephone=no">
      <meta name="msapplication-tap-highlight" content="no">
      <meta name="viewport" content="initial-scale=1, width=device-width, viewport-fit=cover">
    </head>
    <body>';

    echo '
    <style>
    .btn {
      display: inline-block;
      /* background: #000; */
      border: 1px solid rgb(0 0 0 / 23%);
      color: #4a4a4a;
      padding: 10px 15px;
      border-radius: 10px;
      font-size: 90%;
      cursor: pointer;
      width: auto;
      line-height: 1;
      position: relative;
      text-transform: capitalize;
      margin: 0 30px;
      display: block;
      font-weight: 600;
      transition: 200ms ease all;
      text-decoration: none
    }

    .btn:hover {
      border-color: rgb(0 0 0 / 53%);
      color: #000;
    }
    </style>
    <div style="
    font-family: sans-serif;
    max-width: 400px;
    margin: 0 auto;
    ">

    <div style="
    position: fixed;
    top: 0;
    right: 0;
    left: 0;
    text-align: center;
    border-bottom: 1px solid rgba(0,0,0,0.2);
    color: rgba(0,0,0,0.4);
    padding: 5px 0;
    ">'.bof()->object->language->turn( "social_login", [], [ "uc_first" => true, "lang" => "users" ] ).'</div>
    <div style="
    margin-top: 20vh;
    text-align: center;
    font-size: 180%;
    margin-bottom: 10vh;
    ">'.bof()->object->language->turn( "social_login_direct", [ "target_name" => ucfirst($target_name) ], [ "uc_first" => true, "lang" => "users" ] ).'</div>
    <div style="
    font-size: 90%;
    font-weight: 600;
    ">'.bof()->object->language->turn( "social_login_need", [], [ "uc_first" => true, "lang" => "users" ] ).':</div>
    <ul style="
    margin: 30px 0;
    ">
    <li style="
    /* margin-bottom: 15px; */
    ">'.bof()->object->language->turn( "social_login_mail", [], [ "uc_first" => true, "lang" => "users" ] ).'</i></li>';

    if ( $target_slang == "gg" ? $loader->object->db_setting->get("sl_gg_extra") : false)
    echo '<li style="
    margin-top: 20px;
    ">'.bof()->object->language->turn( "social_login_ytlike", [ "sitename" => $loader->object->db_setting->get("sitename") ], [ "uc_first" => true, "lang" => "users" ] ).'</li>';

    echo '</ul>
    <div style="
    font-size: 80%;
    line-height: 1;
    /* opacity: 0.6; */
    ">'.bof()->object->language->turn( "social_login_revoke", [ "target_name" => ucfirst($target_name) ], [ "uc_first" => true, "lang" => "users" ] ).'</div>
    <div style="
    text-align: center;
    margin-top: 30px;
    "><a class="btn" href="login_social_ini?target='.$target_name.'&confirm=true">'.bof()->object->language->turn( "continue", [], [ "uc_first" => true, "lang" => "users" ] ).'</a></div>

    </div>
    </body>
    </html>
    ';
    die;

  }

  $loader->social_login->ini( $target_name, $target_id, $target_secret );

}

?>
