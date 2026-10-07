<?php

if ( !defined( "bof_root" ) ) die;

class social_login {

  protected function getHybridauth(){
    require_once( social_login_plugin_root . "/third/hybridauth/vendor/autoload.php" );
  }
  public function ini( $target, $id, $sercret, $args=[] ){

    $scopes = [];
    extract( $args );

    // Flutter app (Chrome Custom Tab) support: capture redirect_uri/return_url on
    // the first OAuth leg and persist it in PHP session for the callback leg.
    if ( session_status() !== PHP_SESSION_ACTIVE && !headers_sent() )
      @session_start();

    $app_redirect = !empty( $_GET["redirect_uri"] ) ? $_GET["redirect_uri"] : ( !empty( $_GET["return_url"] ) ? $_GET["return_url"] : null );
    if ( $app_redirect !== null ){
      $parsed = parse_url( $app_redirect );
      if ( !empty( $parsed["scheme"] ) && strtolower( $parsed["scheme"] ) === "hitune" )
        $_SESSION["sl_app_redirect"] = $app_redirect;
    }
    $supported_social_login = bof()->object->core_setting->get( "supported_social_logins" );
    $target_hybird_name = $supported_social_login[ $target ][ "hybirdName" ];

    $config = array(
      "callback" => endpoint_address . "login_social_ini?target={$target}",
      "providers" => array(
        $target_hybird_name => array(
          "enabled" => true,
          "keys" => array(
            "key" => $id,
            "secret" => $sercret
          ),
        )
      )
    );

    if ( $target == "google" ? bof()->object->db_setting->get("sl_gg_extra") : false ){
      $config["providers"][ $target_hybird_name ]["scope"] = "email profile https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email openid https://www.googleapis.com/auth/youtube.force-ssl";
      $config["providers"][ $target_hybird_name ]["access_type"] = "offline";
      $config["providers"][ $target_hybird_name ]["approval_prompt"] = "force";
    }
    if ( $target == "facebook" ){
      $config["providers"][ $target_hybird_name ]["scope"] = "email,public_profile";
    }
    if ( $target == "twitter" ){
      $config["providers"][ $target_hybird_name ]["includeEmail"] = true;
      $config["providers"][ $target_hybird_name ]["include_email"] = true;
    }
    if ( $target == "instagram" ){
      $config["providers"][ $target_hybird_name ]["scope"] = "user_profile";
    }
    if ( $target == "linkedin" ){
      //$config["providers"][ $target_hybird_name ]["scope"] = "email profile openid";
    }

    try {
      $this->getHybridauth();
      $hybridauth = new Hybridauth\Hybridauth( $config );
      $authProvider = $hybridauth->authenticate( $target_hybird_name );
      $tokens = $authProvider->getAccessToken();
      $user_profile = $authProvider->getUserProfile();
    }
    catch( Exception $e ){

      $is_app = !empty( $_SESSION["sl_app_redirect"] ) || !empty( $_SESSION["sl_app_flow"] ) || !empty( $_SESSION["sl_app_last_url"] );

      // Idempotent callback: this callback already succeeded once (state consumed)
      // -> re-issue the last app redirect instead of failing.
      if ( !empty( $_SESSION["sl_app_last_url"] ) ){
        $url = $_SESSION["sl_app_last_url"];
        if ( !headers_sent() ){ header( "Location: " . $url ); die( "<script>location.href=" . json_encode( $url ) . ";</script>" ); }
        die( "<script>location.href=" . json_encode( $url ) . ";</script><a href='" . htmlspecialchars( $url ) . "'>Open Hitune Music app</a>" );
      }

      // Invalid/consumed state -> restart the OAuth leg once with a fresh state.
      // Works for web popups too (restart just runs the flow again).
      if ( strpos( $e->getMessage(), "authorization state" ) !== false && empty( $_SESSION["sl_app_retry"] ) ){
        $_SESSION["sl_app_retry"] = 1;
        $restart = endpoint_address . "login_social_ini?target=" . urlencode( $target ) . "&slr=1";
        if ( !empty( $_SESSION["sl_app_redirect"] ) )
          $restart .= "&redirect_uri=" . urlencode( $_SESSION["sl_app_redirect"] );
        if ( !headers_sent() ){ header( "Location: " . $restart ); die( "<script>location.href=" . json_encode( $restart ) . ";</script>" ); }
        die( "<script>location.href=" . json_encode( $restart ) . ";</script>" );
      }

      // App flow: never show a raw error page -> bounce the error back to the app.
      if ( $is_app ){
        $red = !empty( $_SESSION["sl_app_redirect"] ) ? $_SESSION["sl_app_redirect"] : "hitune://sociallogin";
        $sep = strpos( $red, "?" ) === false ? "?" : "&";
        $url = $red . $sep . "success=false&error=" . urlencode( substr( $e->getMessage(), 0, 300 ) );
        if ( !headers_sent() ){ header( "Location: " . $url ); die( "<script>location.href=" . json_encode( $url ) . ";</script>" ); }
        die( "<script>location.href=" . json_encode( $url ) . ";</script><a href='" . htmlspecialchars( $url ) . "'>Back to Hitune Music app</a>" );
      }

      bof()->chapar->notify_admin("slogin_failed", array(
        "error" => $e->getMessage()
      ));
      die($e->getMessage());
    }

    $lock_tokens = bof()->crypto->lock( json_encode( $tokens ) );
    $extraData = json_encode( array(
      "{$target}_token" => array(
        "sign" => $lock_tokens["sign"],
        "nonce" => $lock_tokens["nonce"]
      ),
      "image" => !empty( $user_profile->photoURL ) ? $user_profile->photoURL : null
    ) );

    $email = !empty( $user_profile->email ) ? $user_profile->email : $user_profile->identifier . "@{$target}.com";
    if ( ( $requested_user = bof()->object->user->select(["email"=>$email],array(
      "_eq" => array(
        "roles" => array(
          "website" => null
        )
      ),
      "no_bof_time" => true
    ) ) ) ){

      if ( !empty( $tokens["refresh_token"] ) ){
        bof()->object->user->update(
          array(
            "ID" => $requested_user["ID"]
          ),
          array(
            "extraData" => $extraData
          )
        );
      }

      if ( $target === "google" )
        bof()->db->_update( array(
          "table" => "_u_list",
          "where" => array(
            array( "ID", "=", (int) $requested_user["ID"] ),
            array( "google_sub", null, null )
          ),
          "set" => array( array( "google_sub", (string) $user_profile->identifier ) )
        ) );

      bof()->chapar->notify_admin("slogin_ok", array(
        "type" => "relog"
      ));

      $social_login_enabled = bof()->object->user_setting->get( $requested_user["ID"], "social_login", 1 );
      if ( !$social_login_enabled )
      die( bof()->object->language->turn( "social_login_disabled", [], [ "lang" => "users", "uc_first" => true ]  ) );

    }
    else {

      $name = $user_profile->displayName;
      $username = bof()->object->user->make_username( $name );
      $userID = bof()->object->user->create(
        array(
          "email" => $email
        ),
        array(
          "email" => $email,
          "username" => $username,
          "password" => uniqid() . uniqid(),
          "name" => $name,
          "time_verify" => bof()->general->mysql_timestamp(),
          "extraData" => $extraData,
          "initial" => true
        )
      );

      bof()->db->_update( array(
        "table" => "_u_list",
        "where" => array( array( "ID", "=", (int) $userID ) ),
        "set" => array(
          array( "password_set", 0 ),
          array( "google_sub", $target === "google" ? (string) $user_profile->identifier : null )
        )
      ) );

      $requested_user = bof()->object->user->select(["ID"=>$userID],array(
        "_eq" => array(
          "roles" => array(
            "website" => null
          )
        ),
        "no_bof_time" => true
      ));

      bof()->chapar->notify_admin("slogin_ok", array(
        "type" => "create"
      ));

    }

    $sess_data = bof()->user_auth->create( $requested_user["ID"], false );

    $sess_data_simplified = array(
      "sess_id" => $sess_data["id"],
      "sess_key" => $sess_data["key"],
      "{$target}_key" => $lock_tokens["key"]
    );

    // Flutter app flow: redirect back into the app via custom scheme.
    // redirect_uri was stored in session on the first OAuth leg; default hitune://sociallogin.
    $app_redirect = !empty( $_SESSION["sl_app_redirect"] ) ? $_SESSION["sl_app_redirect"] : null;
    $app_flow_flag = !empty( $_SESSION["sl_app_flow"] );
    unset( $_SESSION["sl_app_redirect"] );
    unset( $_SESSION["sl_app_flow"] );
    unset( $_SESSION["sl_app_retry"] );
    $is_app_flow = $app_redirect !== null || $app_flow_flag;
    if ( !$app_redirect )
      $app_redirect = "hitune://sociallogin";

    $sep = strpos( $app_redirect, "?" ) === false ? "?" : "&";
    $app_url = $app_redirect . $sep
      . "success=true"
      . "&token=" . urlencode( json_encode( $sess_data_simplified ) )
      . "&sess_id=" . urlencode( $sess_data["id"] )
      . "&sess_key=" . urlencode( $sess_data["key"] );

    // Remember the issued app URL so a duplicate callback hit (state already
    // consumed) can be answered idempotently instead of showing an error.
    if ( $is_app_flow )
      $_SESSION["sl_app_last_url"] = $app_url;

    // App explicitly passed redirect_uri -> hard 302, no HTML needed
    if ( $is_app_flow && !headers_sent() ){
      header( "Location: " . $app_url );
      die( "<script>window.location.href=" . json_encode( $app_url ) . ";</script><a href='" . htmlspecialchars( $app_url ) . "'>Open Hitune Music app</a>" );
    }

    ?>

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
    </style>

    <?php

    echo '<!DOCTYPE html><html><head>
    <meta charset="utf-8">
    <meta name="format-detection" content="telephone=no">
    <meta name="msapplication-tap-highlight" content="no">
    <meta name="viewport" content="initial-scale=1, width=device-width, viewport-fit=cover">
    </head>
    <body>';

    echo "<script>
    var data = " . json_encode( json_encode( $sess_data_simplified ) ) . ";
    var appUrl = " . json_encode( $app_url ) . ";
    if ( window.opener ){
      try {
        window.opener.app.pages.user_auth.social_loginner_promise.resolve(data);
        window.close();
      } catch ( e ) {
        window.opener.postMessage( { type: 'social_login', data: data }, '*' );
        setTimeout( function(){ window.close(); }, 1000 );
      }
    }
    else {
      // Chrome Custom Tab: try several ways to launch the app scheme
      window.location.replace( appUrl );
      var m = document.createElement('meta');
      m.setAttribute('http-equiv','refresh');
      m.setAttribute('content','0;url=' + appUrl);
      document.head.appendChild(m);
      setTimeout( function(){ window.location.href = appUrl; }, 400 );
    }
    </script>";
    echo '<div style="font-family:sans-serif;text-align:center;margin-top:30vh"><a href="' . htmlspecialchars( $app_url ) . '" style="display:inline-block;padding:14px 28px;background:#00b7ff;color:#fff;border-radius:8px;text-decoration:none;font-weight:600">Open Hitune Music app</a></div>';

    echo "</body></html>";

  }

}

?>