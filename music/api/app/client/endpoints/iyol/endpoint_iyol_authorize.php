<?php

if ( !defined( "bof_root" ) ) die;

/**
 * GET|POST /api/oauth/authorize
 * OAuth 2.0 authorization endpoint (browser consent page).
 * Logged-in HiTune web user approves -> 302 redirect back to IyolMe with
 * a one-time authorization code.
 */
function endpoint_iyol_authorize( $loader, $excuter, $args ){

  // Top-level browser navigations can't send x-bof-sess-* headers; the
  // frontend mirrors them into hitune_sess_* cookies on .hitune.in, so
  // lift them into POST where session->open() looks for sess_id/key.
  if ( empty( $_POST["sess_id"] ) && !empty( $_COOKIE["hitune_sess_id"] ) )
    $_POST["sess_id"] = $_COOKIE["hitune_sess_id"];
  if ( empty( $_POST["sess_key"] ) && !empty( $_COOKIE["hitune_sess_key"] ) )
    $_POST["sess_key"] = $_COOKIE["hitune_sess_key"];

  $iyol = bof()->iyolme;

  $page = function( $title, $body_html ){
    if ( !headers_sent() ) header( "Content-Type: text/html; charset=utf-8" );
    $t = htmlspecialchars( $title );
    echo "<!DOCTYPE html><html lang='en'><head><meta charset='utf-8'>
<meta name='viewport' content='width=device-width, initial-scale=1'>
<title>{$t} — HiTune</title>
<style>
  body{margin:0;font-family:system-ui,-apple-system,Roboto,Arial,sans-serif;background:#0a0a1a;color:#e8e8f0;display:flex;align-items:center;justify-content:center;min-height:100vh}
  .card{background:#12122a;border:1px solid #23234a;border-radius:16px;max-width:420px;width:92%;padding:32px;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.5)}
  .logo{font-size:28px;font-weight:800;background:linear-gradient(90deg,#00b7ff,#8b5cf6);-webkit-background-clip:text;background-clip:text;color:transparent;margin-bottom:6px}
  h1{font-size:20px;margin:14px 0 6px}
  p{color:#9a9ab8;font-size:14px;line-height:1.55}
  .app{display:inline-block;background:#1b1b3a;border-radius:8px;padding:4px 12px;color:#00b7ff;font-weight:600;margin:4px 0}
  .scopes{text-align:left;background:#0d0d22;border-radius:10px;padding:12px 16px;margin:16px 0;font-size:13px;color:#b8b8d8}
  .scopes li{margin:6px 0}
  .btn{display:block;width:100%;padding:13px;border:none;border-radius:10px;font-size:15px;font-weight:700;cursor:pointer;margin-top:10px;text-decoration:none}
  .approve{background:linear-gradient(90deg,#00b7ff,#8b5cf6);color:#fff}
  .deny{background:transparent;color:#9a9ab8;border:1px solid #2d2d55}
  a{color:#00b7ff}
</style></head><body><div class='card'><div class='logo'>HiTune</div>{$body_html}</div></body></html>";
    exit;
  };

  $oauth_err = function( $redirect_uri, $state, $code, $desc=null ){
    $qs = array( "error" => $code, "state" => $state );
    if ( $desc ) $qs["error_description"] = $desc;
    header( "Location: " . $redirect_uri . ( strpos( $redirect_uri, "?" ) === false ? "?" : "&" ) . http_build_query( $qs ), true, 302 );
    exit;
  };

  if ( !$iyol->enabled() )
    $page( "Unavailable", "<h1>Integration disabled</h1><p>HiTune &harr; IyolMe linking is currently disabled.</p>" );

  $in = function( $k ) use ( $loader ){
    $v = $loader->nest->user_input( "post", $k, "string" );
    return $v ?: $loader->nest->user_input( "get", $k, "string" );
  };

  $client_id     = $in( "client_id" );
  $redirect_uri  = $in( "redirect_uri" );
  $response_type = $in( "response_type" ) ?: "code";
  $scope         = $in( "scope" ) ?: "profile";
  $state         = $in( "state" );

  if ( $response_type !== "code" )
    $page( "Invalid request", "<h1>Unsupported response_type</h1><p>Only <code>code</code> is supported.</p>" );

  $client = $iyol->ensure_client();
  if ( !$client || $client["client_id"] !== $client_id ) $client = $iyol->oauth_client( $client_id );

  if ( !$client || $client["status"] !== "active" )
    $page( "Invalid client", "<h1>Unknown application</h1><p>This client_id is not registered with HiTune.</p>" );

  if ( !$redirect_uri || !$iyol->oauth_client_ok( $client, null, $redirect_uri ) )
    $page( "Invalid redirect", "<h1>Bad redirect URI</h1><p>The redirect URI is not registered for this application.</p>" );

  // logged-in check (web session)
  $user = null;
  try { $user = $loader->user->check(); } catch ( \Throwable $e ) {}
  $user_id = ( $user && !empty( $user->ID ) ) ? (int)$user->ID : 0;

  if ( !$user_id ){
    $retry = web_address . "api/oauth/authorize?" . http_build_query( array(
      "client_id" => $client_id, "redirect_uri" => $redirect_uri,
      "response_type" => "code", "scope" => $scope, "state" => $state
    ) );
    $page( "Login required",
      "<h1>Connect your account</h1>
       <p>Log in to your HiTune account first, then come back to authorize <span class='app'>" . htmlspecialchars( $client["name"] ) . "</span>.</p>
       <a class='btn approve' href='" . htmlspecialchars( web_address ) . "'>Open HiTune to log in</a>
       <a class='btn deny' href='" . htmlspecialchars( $retry ) . "'>I'm logged in — retry</a>" );
  }

  // POST approve/deny
  if ( $_SERVER["REQUEST_METHOD"] === "POST" ){

    if ( $in( "approve" ) !== "1" )
      $oauth_err( $redirect_uri, $state, "access_denied", "User declined the request" );

    $code = $iyol->create_code( $client_id, $user_id, $redirect_uri, $scope );
    $qs = array( "code" => $code, "state" => $state );
    header( "Location: " . $redirect_uri . ( strpos( $redirect_uri, "?" ) === false ? "?" : "&" ) . http_build_query( $qs ), true, 302 );
    exit;
  }

  // consent screen
  $scope_list = "";
  foreach( preg_split( "/\s+/", $scope ) as $s ){
    $labels = array(
      "profile" => "See your username, name and avatar",
      "email" => "See your email address",
      "attribution" => "Read audio attribution &amp; track metadata",
      "reels" => "Publish reels on your behalf"
    );
    $scope_list .= "<li>&#10003; " . ( isset( $labels[$s] ) ? $labels[$s] : htmlspecialchars( $s ) ) . "</li>";
  }

  $hidden = "";
  foreach( array( "client_id","redirect_uri","response_type","scope","state" ) as $k )
    $hidden .= "<input type='hidden' name='{$k}' value='" . htmlspecialchars( ${$k} ) . "'>";

  $page( "Authorize",
    "<h1>Authorize <span class='app'>" . htmlspecialchars( $client["name"] ) . "</span></h1>
     <p>Signed in as <b>" . htmlspecialchars( $user->name ? $user->name : $user->username ) . "</b><br>
     This app wants to:</p>
     <ul class='scopes'>{$scope_list}</ul>
     <form method='post'>
       {$hidden}
       <button class='btn approve' name='approve' value='1'>Authorize</button>
       <button class='btn deny' name='approve' value='0' formnovalidate>Cancel</button>
     </form>" );

}

?>
