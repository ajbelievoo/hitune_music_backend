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
  *{box-sizing:border-box}
  body{margin:0;font-family:system-ui,-apple-system,Roboto,'Segoe UI',Arial,sans-serif;color:#e8e8f2;display:flex;align-items:center;justify-content:center;min-height:100vh;overflow:hidden;
    background:radial-gradient(1200px 800px at 15% 10%,#16163a 0%,transparent 55%),radial-gradient(1000px 700px at 85% 90%,#241244 0%,transparent 55%),#07070f}
  .glow{position:fixed;border-radius:50%;filter:blur(90px);opacity:.5;pointer-events:none;animation:drift 14s ease-in-out infinite alternate}
  .g1{width:420px;height:420px;background:#00b7ff;top:-120px;left:-100px}
  .g2{width:380px;height:380px;background:#8b5cf6;bottom:-140px;right:-80px;animation-delay:-7s}
  @keyframes drift{to{transform:translate(50px,36px) scale(1.12)}}
  .card{position:relative;background:rgba(20,20,42,.72);backdrop-filter:blur(18px);-webkit-backdrop-filter:blur(18px);border:1px solid rgba(255,255,255,.09);border-radius:22px;max-width:400px;width:92%;padding:38px 34px 30px;text-align:center;box-shadow:0 24px 80px rgba(0,0,0,.6),inset 0 1px 0 rgba(255,255,255,.06);animation:rise .45s cubic-bezier(.2,.8,.25,1)}
  @keyframes rise{from{opacity:0;transform:translateY(18px)}}
  .brand{display:flex;align-items:center;justify-content:center;gap:10px;margin-bottom:4px}
  .note{width:44px;height:44px;border-radius:13px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#00b7ff,#8b5cf6);box-shadow:0 8px 24px rgba(0,183,255,.4);font-size:22px}
  .logo{font-size:30px;font-weight:800;letter-spacing:-.5px;background:linear-gradient(90deg,#00b7ff,#8b5cf6,#ff4ecd);-webkit-background-clip:text;background-clip:text;color:transparent}
  .tag{font-size:11.5px;color:#7c7ca3;letter-spacing:2.5px;text-transform:uppercase;margin-bottom:8px}
  h1{font-size:21px;font-weight:700;margin:16px 0 4px;letter-spacing:-.3px}
  p{color:#9a9ab8;font-size:13.5px;line-height:1.6;margin:6px 0}
  .app{display:inline-block;background:rgba(0,183,255,.12);border:1px solid rgba(0,183,255,.3);border-radius:8px;padding:3px 12px;color:#4fcfff;font-weight:700;margin:2px 0}
  .scopes{list-style:none;text-align:left;background:rgba(10,10,26,.6);border:1px solid rgba(255,255,255,.06);border-radius:12px;padding:12px 18px;margin:18px 0 6px;font-size:13px;color:#c2c2e0}
  .scopes li{margin:8px 0;color:#b8b8d8}
  .scopes li::marker{color:#2ee6a8}
  .btn{display:block;width:100%;padding:14px;border:none;border-radius:12px;font-size:15px;font-weight:700;cursor:pointer;margin-top:10px;text-decoration:none;transition:transform .12s,box-shadow .2s,opacity .15s;letter-spacing:.2px}
  .approve{background:linear-gradient(90deg,#00b7ff,#8b5cf6);color:#fff;box-shadow:0 8px 26px rgba(94,92,246,.45)}
  .approve:hover{transform:translateY(-1px);box-shadow:0 12px 32px rgba(94,92,246,.6);opacity:.95}
  .approve:active{transform:translateY(0)}
  .deny{background:transparent;color:#8d8db0;border:1px solid rgba(255,255,255,.14)}
  .deny:hover{background:rgba(255,255,255,.05);color:#c8c8e4}
  a{color:#4fcfff}
  .foot{margin-top:20px;font-size:11px;color:#5d5d82;display:flex;align-items:center;justify-content:center;gap:6px}
</style></head><body>
<div class='glow g1'></div><div class='glow g2'></div>
<div class='card'>
  <div class='brand'><div class='note'>&#9835;</div><div class='logo'>HiTune</div></div>
  <div class='tag'>music without limits</div>
  {$body_html}
  <div class='foot'>&#128737; Secured by HiTune OAuth</div>
</div></body></html>";
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
