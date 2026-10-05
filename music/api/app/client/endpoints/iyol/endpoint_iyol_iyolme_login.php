<?php

if ( !defined( "bof_root" ) ) die;

/**
 * GET /api/iyol/iyolme_login   (public, html)
 *
 * "Continue with IyolMe" — reverse SSO. IyolMe's web page
 * (iyolme.com/sso-hitune.html) mints a one-time signed grant for the
 * logged-in IyolMe user and bounces the browser here. We redeem it
 * server-to-server via the signed hitune/v1/grant_exchange endpoint,
 * find-or-create the matching HiTune account by email, then open a
 * normal web session.
 */
function endpoint_iyol_iyolme_login( $loader, $excuter, $args ){

  $fail = function( $title, $msg ){
    header( "Content-Type: text/html; charset=utf-8" );
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
      . '<title>' . htmlspecialchars( $title ) . '</title></head>'
      . '<body style="background:#0a0a1a;color:#fff;font-family:sans-serif;text-align:center;padding:60px 20px">'
      . '<h2>' . htmlspecialchars( $title ) . '</h2>'
      . '<p style="color:#93a0cf">' . htmlspecialchars( $msg ) . '</p>'
      . '<p><a style="color:#00b7ff" href="' . htmlspecialchars( web_address ) . '">Back to HiTune Music</a></p>'
      . '</body></html>';
    exit;
  };

  $iyol = bof()->iyolme;
  if ( !$iyol->configured() )
    $fail( "IyolMe sign-in unavailable", "The IyolMe connection is not configured yet." );

  $grant = trim( (string) $loader->nest->user_input( "get", "grant", "string" ) );
  if ( $grant === "" )
    $fail( "Missing grant", "Start the sign-in again from HiTune Music." );

  $res = $iyol->api_request( "POST", "/hitune/v1/grant_exchange", array( "grant" => $grant ) );

  if ( !is_array( $res ) || empty( $res["ok"] ) || empty( $res["user"]["email"] ) ){
    $err = is_array( $res ) && !empty( $res["error"] ) ? $res["error"] : "grant_exchange_failed";
    $fail( "Sign-in failed", "IyolMe rejected the session grant ({$err}). Try again." );
  }

  $iyol_user  = $res["user"];
  $email      = strtolower( trim( (string) $iyol_user["email"] ) );
  if ( !filter_var( $email, FILTER_VALIDATE_EMAIL ) )
    $fail( "Sign-in failed", "Your IyolMe account has no usable email address." );

  $db = $loader->db;

  // find existing HiTune account by email
  $u = $db->_select( array(
    "table" => "_u_list", "columns" => "ID",
    "where" => array( array( "email", "=", $email ) ),
    "limit" => 1, "single" => true
  ) );

  $uid = $u ? (int) $u["ID"] : 0;

  if ( !$uid ){

    // unique username from email local part
    $base = strtolower( preg_replace( "/[^a-zA-Z0-9_]/", "", strstr( $email, "@", true ) ?: "user" ) );
    if ( $base === "" ) $base = "user";
    $base = substr( $base, 0, 40 );
    $username = $base;
    for ( $i = 0; $i < 20; $i++ ){
      $taken = $db->_select( array(
        "table" => "_u_list", "columns" => "ID",
        "where" => array( array( "username", "=", $username ) ),
        "limit" => 1, "single" => true
      ) );
      if ( !$taken ) break;
      $username = $base . random_int( 100, 9999 );
    }

    $name = trim( (string) ( $iyol_user["fullname"] ?: $iyol_user["username"] ?: $username ) );
    $hash = md5( uniqid( (string) mt_rand(), true ) );
    $passwordHash = password_hash( bin2hex( random_bytes( 16 ) ), PASSWORD_DEFAULT );

    $db->_insert( array(
      "table" => "_u_list",
      "set" => array(
        array( "hash", $hash ),
        array( "username", $username ),
        array( "name", $name ),
        array( "password", $passwordHash ),
        array( "email", $email ),
        array( "role_ids", "2" ),
        array( "external_addresses", "[]" ),
        array( "time_verify", date( "Y-m-d H:i:s" ) ),
        array( "time_add", date( "Y-m-d H:i:s" ) ),
      )
    ) );

    $uid = (int) $db->insert_id();

  }

  if ( !$uid )
    $fail( "Sign-in failed", "Could not create your HiTune account. Try again." );

  // BOF web auth = sess_id + sess_key held in localStorage (dm_*) and
  // mirrored to hitune_sess_* cookies; session rows live in
  // _bof_cache_sessions with a JSON `data` column. create() handles all
  // of that — we just hand the pair to the browser.
  $sess = bof()->session->create( $uid, array(
    "platform_type" => "web",
    "extra_data"    => bof()->user->get_extraData( true, $uid )
  ) );

  if ( empty( $sess["id"] ) || empty( $sess["key"] ) )
    $fail( "Sign-in failed", "Could not open a HiTune session. Try again." );

  $sid  = json_encode( $sess["id"] );
  $skey = json_encode( $sess["key"] );
  $home = json_encode( web_address );

  header( "Content-Type: text/html; charset=utf-8" );
  echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
    . '<title>Signing you in — HiTune</title></head>'
    . '<body style="background:#0a0a1a;color:#fff;font-family:sans-serif;text-align:center;padding:60px 20px">'
    . '<h2>Login successful</h2><p>Opening HiTune Music…</p>'
    . '<script>'
    . 'var sid=' . $sid . ',skey=' . $skey . ';'
    . 'localStorage.setItem("dm_sess_id",sid);localStorage.setItem("dm_sess_key",skey);'
    . 'document.cookie="hitune_sess_id="+encodeURIComponent(sid)+"; domain=.hitune.in; path=/; SameSite=Lax; Secure; max-age=2592000";'
    . 'document.cookie="hitune_sess_key="+encodeURIComponent(skey)+"; domain=.hitune.in; path=/; SameSite=Lax; Secure; max-age=2592000";'
    . 'location.replace(' . $home . ');'
    . '</script>'
    . '<p><a style="color:#00b7ff" href="' . htmlspecialchars( web_address ) . '">Continue to HiTune Music</a></p>'
    . '</body></html>';
  exit;

}

?>
