<?php

if ( !defined( "bof_root" ) ) die;

class user_auth extends bof_type_class {

  public function create( $userID, $setAPIMessage=false ){

    $platform = bof()->nest->user_input( "http_header", "x-bof-platform", "in_array", [
      "values" => bof()->object->core_setting->get( "supported_platforms", null, [ "invalid_death" => true ] )
    ], "web" );
    $device_type = $platform == !empty( bof()->request->get_userAgent()["data"]["device"]["type"] ) ? strtolower( bof()->request->get_userAgent()["data"]["device"]["type"] ) : null;

    $sess_data = bof()->session->create( $userID, array(
      "platform_type" => $platform,
      "device_type" => $device_type,
      "extra_data" => bof()->user->get_extraData( true, $userID )
    ) );

    if ( $setAPIMessage ){
      $redirect = isset( $sess_data["redirect"] ) ? $sess_data["redirect"] : null;
      bof()->api->set_message( "welcome", array(
        "sess_id" => $sess_data["id"],
        "sess_key" => $sess_data["key"],
        "redirect" => $redirect
      ) );
    }

    return $sess_data;

  }
  public function actions(){

    return array(
      "login" => array(
        "inputs" => array(
          "email" => array(
            "icon" => "email",
            "html" => '<input type="email" name="email" class="bof_input" placeholder="'.(bof()->object->language->turn("email",[],["uc_first"=>true,"lang"=>"users"])).'" required>'
          ),
          "password" => array(
            "icon" => "lock",
            "html" => '<input type="password" name="password" minlength="5" class="bof_input" placeholder="'.(bof()->object->language->turn("password",[],["uc_first"=>true,"lang"=>"users"])).'" required>'
          ),
        ),
        "content" => '<label class="form-text"><a href="userAuth?do=recover">'.(bof()->object->language->turn("login_recover_text",[],["uc_first"=>true,"lang"=>"users"])).'</a></label>',
        "btns" => array(
          '<div class="btn btn-primary submit"><span class="message">'.(bof()->object->language->turn("continue",[],["uc_first"=>true,"lang"=>"users"])).'</span><div class="loader"></div></div>',
          '<a class="btn btn-light" href="userAuth?do=signup">'.(bof()->object->language->turn("signup",[],["uc_first"=>true,"lang"=>"users"])).'</a>',
          '<a class="btn btn-light" href="https://iyolme.com/sso-hitune.html" style="display:flex;align-items:center;justify-content:center;gap:8px">Continue with IyolMe</a>'
        )
      ),
      "phone_auth" => array(
        "inputs" => array(),
        "content" => "",
        "btns" => array()
      ),
      "bind_phone" => array(
        "inputs" => array(),
        "content" => "",
        "btns" => array()
      ),
      "iyol_grant" => array(
        "inputs" => array(),
        "content" => "",
        "btns" => array()
      ),
      "set_password" => array(
        "inputs" => array(),
        "content" => "",
        "btns" => array()
      ),
      "security_status" => array(
        "inputs" => array(),
        "content" => "",
        "btns" => array()
      ),
      "signup" => array(
        "inputs" => array(
          "email" => array(
            "icon" => "email",
            "html" => '<input type="email" name="email" class="bof_input" placeholder="'.(bof()->object->language->turn("email",[],["uc_first"=>true,"lang"=>"users"])).'" required>'
          ),
          "username" => array(
            "icon" => "account",
            "html" => '<input type="username" name="username" id="username" minlength="4" check_username="yes" class="bof_input" placeholder="'.(bof()->object->language->turn("username",[],["uc_first"=>true,"lang"=>"users"])).'" required>'
          ),
          "password" => array(
            "icon" => "lock",
            "html" => ' <input type="password" name="password" id="password" minlength="5" class="bof_input" placeholder="'.(bof()->object->language->turn("password",[],["uc_first"=>true,"lang"=>"users"])).'" required>'
          ),
          "password_repeat" => array(
            "icon" => "lock",
            "html" => '<input type="password" name="password_repeat" minlength="5" class="bof_input" check_password="yes" placeholder="'.(bof()->object->language->turn("password",[],["uc_first"=>true,"lang"=>"users"])).'" required>'
          ),
        ),
        "content" => '<div class="form-text"><div class="_cw"><input type="checkbox" name="agree"><span class="_m"></span></div>'.(bof()->object->language->turn("signup_agree_terms",[],["uc_first"=>true,"lang"=>"users"])).'</div>',
        "btns" => array(
          '<div class="btn btn-primary submit"><span class="message">'.(bof()->object->language->turn("continue",[],["uc_first"=>true,"lang"=>"users"])).'</span><div class="loader"></div></div>',
          '<a class="btn btn-light" href="userAuth?do=login">'.(bof()->object->language->turn("login",[],["uc_first"=>true,"lang"=>"users"])).'</a>'
        )
      ),
      "recover" => array(
        "inputs" => array(
          "email" => array(
            "icon" => "email",
            "html" => '<input type="email" name="email" class="bof_input" placeholder="'.(bof()->object->language->turn("email",[],["uc_first"=>true,"lang"=>"users"])).'" required>'
          ),
        ),
        "content" => "",
        "btns" => array(
          '<div class="btn btn-primary submit"><span class="message">'.(bof()->object->language->turn("continue",[],["uc_first"=>true,"lang"=>"users"])).'</span><div class="loader"></div></div>',
          '<a class="btn btn-light" href="userAuth?do=login">'.(bof()->object->language->turn("login",[],["uc_first"=>true,"lang"=>"users"])).'</a>'
        )
      ),
      "recover_confirm" => array(
        "inputs" => array(
          "email" => array(
            "icon" => "email",
            "html" => '<input type="email" name="email" class="bof_input" placeholder="'.(bof()->object->language->turn("email",[],["uc_first"=>true,"lang"=>"users"])).'" value="'.(bof()->nest->user_input("get","email","email")?bof()->nest->user_input("get","email","email"):"").'" required>'
          ),
          "code" => array(
            "icon" => "shield",
            "html" => '<input type="text" name="code" class="bof_input" placeholder="'.(bof()->object->language->turn("verification_code",[],["uc_first"=>true,"lang"=>"users"])).'" value="'.(bof()->nest->user_input("get","code","md5")?bof()->nest->user_input("get","code","md5"):"").'" required>'
          ),
          "password" => array(
            "icon" => "lock",
            "html" => '<input type="password" ID="password" name="password" class="bof_input" placeholder="'.(bof()->object->language->turn("new_password",[],["uc_first"=>true,"lang"=>"users"])).'" required>'
          ),
          "password_repeat" => array(
            "icon" => "lock",
            "html" => '<input type="password" name="password_repeat" check_password="yes" class="bof_input" placeholder="'.(bof()->object->language->turn("new_password",[],["uc_first"=>true,"lang"=>"users"])).'" required>'
          ),
        ),
        "content" => bof()->nest->user_input("get","message","equal",["value"=>"yes"])?bof()->object->language->turn("recovery_email_sent"):"",
        "btns" => array(
          '<div class="btn btn-primary submit"><span class="message">'.(bof()->object->language->turn("continue",[],["uc_first"=>true,"lang"=>"users"])).'</span><div class="loader"></div></div>',
          '<a class="btn btn-light" href="userAuth?do=login">'.(bof()->object->language->turn("login",[],["uc_first"=>true,"lang"=>"users"])).'</a>'
        )
      ),
      "verification" => array(
        "inputs" => array(
          "email" => array(
            "icon" => "email",
            "html" => '<input type="email" name="email" class="bof_input" placeholder="'.(bof()->object->language->turn("email",[],["uc_first"=>true,"lang"=>"users"])).'" value="'.(bof()->nest->user_input("get","email","email")?bof()->nest->user_input("get","email","email"):"").'" required>'
          ),
          "code" => array(
            "icon" => "shield",
            "html" => '<input type="text" name="code" class="bof_input" placeholder="'.(bof()->object->language->turn("verification_code",[],["uc_first"=>true,"lang"=>"users"])).'" value="'.(bof()->nest->user_input("get","code","md5")?bof()->nest->user_input("get","code","md5"):"").'" required>'
          ),
        ),
        "content" => bof()->nest->user_input("get","message","equal",["value"=>"yes"])?bof()->object->language->turn("verify_first"):"",
        "btns" => array(
          '<div class="btn btn-primary submit"><span class="message">'.(bof()->object->language->turn("continue",[],["uc_first"=>true,"lang"=>"users"])).'</span><div class="loader"></div></div>',
          '<a class="btn btn-light" href="userAuth?do=login">'.(bof()->object->language->turn("login",[],["uc_first"=>true,"lang"=>"users"])).'</a>'
        )
      ),
    );

  }

  public function endpoint(){

    $submit = bof()->nest->user_input( "get", "bof", "equal", array( "value" => "submit" ) );
    $do = bof()->nest->user_input( "get", "do", "in_array", array( "values" => array_keys( $this->_bof_this->actions() ) ), "login" );

    if ( $submit )
    $this->_bof_this->submit( $do );

    else
    $this->_bof_this->display( $do );

  }
  public function display( $action ){

    $actionData = $this->_bof_this->actions()[ $action ];

    bof()->api->set_message( "ok", array(
      "action" => $action,
      "title" => bof()->object->language->turn( $action, [], [ "uc_first" => true, "lang" => "users" ] ),
      "inputs" => $actionData["inputs"],
      "content" => $actionData["content"],
      "btns" => $actionData["btns"],
      "seo" => array(
        "title" => bof()->object->language->turn( "login", [], [ "uc_first" => true, "lang" => "users" ] )
      )
    ) );

  }
  public function submit( $action ){

    $_fn = "submit_{$action}";
    return $this->_bof_this->$_fn();

  }
  public function submit_login(){

    $errors = [];
    // Unified identifier: the app sends "identifier" holding a username,
    // email or phone number. Legacy clients still send "email".
    $identifier = bof()->nest->user_input( "post", "identifier", "string" );
    if ( $identifier ) $identifier = trim( $identifier );

    if ( !$identifier )
    $identifier = bof()->nest->user_input( "post", "email", "email" );

    if ( !$identifier ) $errors[] = bof()->object->language->turn( "invalid_input", [ "input_name" => "##identifier" ] );
    if ( !( $password = bof()->nest->user_input( "post", "password", "password" ) ) ) $errors[] = bof()->object->language->turn( "invalid_input", [ "input_name" => "##password" ] );

    if ( !empty( $errors ) ){
      bof()->api->set_error( $errors, array(
        "output_args" => array(
          "turn" => false
        )
      ) );
      return;
    }

    // Detect identifier type: email / phone / username
    if ( strpos( $identifier, "@" ) !== false ){
      $id_type = "email";
      $id_value = bof()->nest->user_input( "post", "identifier", "email" );
      if ( !$id_value ) $id_value = bof()->nest->user_input( "post", "email", "email" );
    }
    elseif ( preg_match( '/^\+?[0-9][0-9\s\-]{5,18}[0-9]$/', $identifier ) ){
      $id_type = "phone";
      // Try candidate forms: with/without leading +, and last-10-digits
      // (covers signup stored with country code but login without it).
      $digits = preg_replace( '/[^0-9]/', '', $identifier );
      $phone_candidates = array_unique( array_filter( array(
        preg_replace( '/[^0-9+]/', '', $identifier ),
        $digits,
        "+" . $digits,
        strlen( $digits ) > 10 ? substr( $digits, -10 ) : null,
      ) ) );
      $id_value = null;
    }
    else {
      $id_type = "username";
      $id_value = $identifier;
      if ( !bof()->nest->validate( $id_value, "username" ) )
      $id_value = strtolower( preg_replace( '/[^a-zA-Z0-9_.\-]/', '', $identifier ) );
      if ( !strlen( $id_value ) ) $id_value = null;
    }

    $auth = false;
    if ( $id_type == "phone" ){
      foreach ( $phone_candidates as $_pc ){
        $auth = bof()->object->user->authorize( "phone", $_pc, $password, "client" );
        if ( $auth ) break;
      }
      // Suffix fallback: account stored as "+91XXXXXXXXXX" but user typed
      // "XXXXXXXXXX" (or vice-versa). Password is still verified.
      if ( !$auth && strlen( $digits ) >= 10 ){
        $tail = substr( $digits, -10 );
        $_row = bof()->object->user->select( array( "phone_suffix" => "%{$tail}" ) );
        if ( $_row ? !empty( $_row["ID"] ) : false )
        $auth = bof()->object->user->authorize( "ID", $_row["ID"], $password, "client" );
      }
    }
    else {
      $auth = $id_value ? bof()->object->user->authorize( $id_type, $id_value, $password, "client" ) : false;
    }

    if ( !$auth ){
      bof()->api->set_error( "login_failed" );
      bof()->chapar->notify_admin( "login_failed", array(
        "identifier" => $identifier,
        "type" => $id_type,
        "error" => "Invalid password or identifier"
      ) );
      return;
    }

    $sess_data = $this->_bof_this->create( $auth["user"]["ID"], true );
    bof()->chapar->notify_admin("login_succeed", array(
      "identifier" => $identifier,
      "type" => $id_type,
      "user_id" => $auth["user"]["ID"]
    ));

    // Handle redirect based on where user came from
    $redirect = bof()->nest->user_input( "get", "redirect", "url" );
    if ( $redirect ) {
      // Validate redirect URL to prevent open redirect
      $redirect_host = parse_url( $redirect, PHP_URL_HOST );
      $current_host = parse_url( web_address, PHP_URL_HOST );
      if ( $redirect_host === $current_host || strpos( $redirect, '/' ) === 0 ) {
        $sess_data["redirect"] = $redirect;
      }
    }

    return array(
      "auth" => $auth,
      "sess" => $sess_data
    );

  }
  /**
   * Decode + verify a Firebase ID token (RS256) against Google's securetoken
   * certs. Returns claims array on success, null on failure.
   */
  public function _firebase_claims( $idToken ){

    $parts = explode( ".", (string) $idToken );
    if ( count( $parts ) !== 3 ) return null;
    list( $h64, $p64, $s64 ) = $parts;

    $b64d = function( $in ){
      return base64_decode( strtr( $in, "-_", "+/" ) . str_repeat( "=", ( 4 - strlen( $in ) % 4 ) % 4 ) );
    };

    $header = json_decode( $b64d( $h64 ), true );
    $kid = is_array( $header ) ? ( $header["kid"] ?? null ) : null;
    if ( !$kid ) return null;

    $cacheFile = "/tmp/ht_gsecure_certs.json";
    $certs = null;
    if ( is_file( $cacheFile ) && time() - filemtime( $cacheFile ) < 3000 )
      $certs = json_decode( file_get_contents( $cacheFile ), true );
    if ( !is_array( $certs ) || !isset( $certs[$kid] ) ){
      $ctx = stream_context_create( array( "http" => array( "timeout" => 10 ) ) );
      $raw = @file_get_contents( "https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com", false, $ctx );
      $certs = $raw ? json_decode( $raw, true ) : null;
      if ( is_array( $certs ) ) @file_put_contents( $cacheFile, $raw );
    }
    $pem = is_array( $certs ) && isset( $certs[$kid] ) ? $certs[$kid] : null;
    if ( !$pem ) return null;

    $ok = openssl_verify( $h64 . "." . $p64, $b64d( $s64 ), $pem, OPENSSL_ALGO_SHA256 );
    if ( $ok !== 1 ) return null;

    $claims = json_decode( $b64d( $p64 ), true );
    if ( !is_array( $claims ) ) return null;

    $projectId = bof()->object->core_setting->get( "firebase_project_id", null, null ) ?: "hitune-live-box";
    if ( ( $claims["iss"] ?? "" ) !== "https://securetoken.google.com/" . $projectId ) return null;
    if ( ( $claims["aud"] ?? "" ) !== $projectId ) return null;
    if ( ( $claims["exp"] ?? 0 ) < time() ) return null;
    if ( empty( $claims["sub"] ) ) return null;

    return $claims;

  }

  /**
   * do=phone_auth — Firebase Phone Auth login/signup (no password needed).
   * POST id_token=<firebase JWT> (optional: password, username)
   * Verified phone_number becomes the account's phone; session returned
   * exactly like password login.
   */
  public function submit_phone_auth(){

    $idToken = bof()->nest->user_input( "post", "id_token", "string" );
    if ( !$idToken ) $idToken = bof()->nest->user_input( "post", "firebase_token", "string" );
    if ( !$idToken ){
      bof()->api->set_error( "invalid_input", [ "input_name" => "##id_token" ] );
      return;
    }

    $claims = $this->_bof_this->_firebase_claims( $idToken );
    if ( !$claims || empty( $claims["phone_number"] ) ){
      bof()->api->set_error( "login_failed" );
      return;
    }

    $phone = preg_replace( '/[^0-9+]/', '', (string) $claims["phone_number"] );
    $digits = preg_replace( '/[^0-9]/', '', $phone );
    if ( strlen( $digits ) < 6 ){
      bof()->api->set_error( "invalid_input", [ "input_name" => "##phone" ] );
      return;
    }
    if ( strpos( $phone, "+" ) !== 0 ) $phone = "+" . $digits;

    // find account: exact phone / digits-only / last-10-digits suffix
    $row = bof()->object->user->select( array( "phone" => $phone ) );
    if ( !$row && $digits !== $phone ) $row = bof()->object->user->select( array( "phone" => $digits ) );
    if ( !$row && strlen( $digits ) >= 10 )
      $row = bof()->object->user->select( array( "phone_suffix" => "%" . substr( $digits, -10 ) ) );

    $password_in = bof()->nest->user_input( "post", "password", "password" );

    if ( $row && !empty( $row["ID"] ) ){

      $uid = (int) $row["ID"];
      // optional password set/upgrade after OTP verification
      if ( $password_in )
        bof()->object->user->update( array( "ID" => $uid ), array( "password" => $password_in ) );

      $sess_data = $this->_bof_this->create( $uid, true );
      bof()->chapar->notify_admin( "login_succeed", array(
        "identifier" => $phone, "type" => "phone_otp", "user_id" => $uid
      ) );
      return array( "auth" => array( "user" => $row ), "sess" => $sess_data );

    }

    // new account — phone is verified by Firebase so verify immediately
    $username = bof()->nest->user_input( "post", "username", "username" );
    if ( !$username ) $username = "u" . substr( $digits, -9 );
    $check = bof()->object->user->select( array( "username" => $username ) );
    for ( $i = 0; $check && $i < 20; $i++ ){
      $username = "u" . substr( $digits, -7 ) . rand( 10, 99 );
      $check = bof()->object->user->select( array( "username" => $username ) );
    }

    $create = bof()->object->user->create( array(), array(
      "username" => $username,
      "password" => $password_in ? $password_in : ( "fp" . bin2hex( random_bytes( 12 ) ) ),
      "phone" => $phone,
      "time_verify" => bof()->general->mysql_timestamp(),
      "initial" => true
    ), array() );

    if ( !$create ){
      bof()->api->set_error( "signup_failed" );
      return;
    }

    if ( !$password_in )
      bof()->db->_update( array(
        "table" => "_u_list",
        "where" => array( array( "ID", "=", $create ) ),
        "set" => array( array( "password_set", 0 ) )
      ) );

    bof()->chapar->notify( "welcome", array(
      "target" => array( "user_id" => $create ),
      "source" => array( "object" => null, "id" => null ),
      "triggerer" => array( "object" => null, "id" => null ),
      "message" => array( "params" => array() )
    ) );

    $sess_data = $this->_bof_this->create( $create, true );
    bof()->chapar->notify_admin( "signup_ok", array( "phone" => $phone ) );

    return array(
      "auth" => array( "user" => array( "ID" => $create ) ),
      "sess" => $sess_data
    );

  }

  /**
   * do=bind_phone — attach a Firebase-OTP-verified phone to the logged-in
   * account. Requires the app's session (PHPSESSID cookie + x-bof-sess-key).
   */
  public function submit_bind_phone(){

    bof()->session->open();
    $uid = bof()->session->check();
    if ( !$uid ){
      bof()->api->set_error( "login_failed" );
      return;
    }

    $idToken = bof()->nest->user_input( "post", "id_token", "string" );
    if ( !$idToken ) $idToken = bof()->nest->user_input( "post", "firebase_token", "string" );
    $claims = $idToken ? $this->_bof_this->_firebase_claims( $idToken ) : null;
    if ( !$claims || empty( $claims["phone_number"] ) ){
      bof()->api->set_error( "invalid_input", [ "input_name" => "##id_token" ] );
      return;
    }

    $phone = preg_replace( '/[^0-9+]/', '', (string) $claims["phone_number"] );
    $digits = preg_replace( '/[^0-9]/', '', $phone );
    if ( strpos( $phone, "+" ) !== 0 ) $phone = "+" . $digits;

    // phone already linked to a DIFFERENT account?
    $row = bof()->object->user->select( array( "phone" => $phone ) );
    if ( !$row && $digits !== $phone ) $row = bof()->object->user->select( array( "phone" => $digits ) );
    if ( !$row && strlen( $digits ) >= 10 )
      $row = bof()->object->user->select( array( "phone_suffix" => "%" . substr( $digits, -10 ) ) );
    if ( $row && !empty( $row["ID"] ) && (int) $row["ID"] !== (int) $uid ){
      bof()->api->set_error( "phone_taken" );
      return;
    }

    bof()->object->user->update( array( "ID" => $uid ), array( "phone" => $phone ) );
    bof()->user->save_session();

    bof()->api->set_message( "ok", array( "phone" => $phone ) );
    return array( "bound" => true, "phone" => $phone );

  }

  /**
   * do=iyol_grant — "Continue with IyolMe" for the app. The IyolMe app mints
   * a one-time grant (user/mintSsoGrant) and deep-links it back to the app;
   * we redeem it server-to-server, resolve/create the HiTune account by
   * email, and return a normal session like password login.
   */
  public function submit_iyol_grant(){

    $grant = bof()->nest->user_input( "post", "grant", "string" );
    if ( !$grant ){
      bof()->api->set_error( "invalid_input", [ "input_name" => "##grant" ] );
      return;
    }

    $res = bof()->iyolme->api_request( "POST", "/hitune/v1/grant_exchange", array( "grant" => $grant ) );
    if ( !is_array( $res ) || empty( $res["ok"] ) || empty( $res["user"]["email"] ) ){
      bof()->api->set_error( "login_failed" );
      return;
    }

    $email = strtolower( trim( (string) $res["user"]["email"] ) );
    if ( !filter_var( $email, FILTER_VALIDATE_EMAIL ) ){
      bof()->api->set_error( "login_failed" );
      return;
    }

    $db = bof()->db;
    $u = $db->_select( array(
      "table" => "_u_list", "columns" => "ID",
      "where" => array( array( "email", "=", $email ) ),
      "limit" => 1, "single" => true
    ) );

    $uid = $u ? (int) $u["ID"] : 0;

    if ( !$uid ){

      $base = strtolower( preg_replace( "/[^a-zA-Z0-9_]/", "", strstr( $email, "@", true ) ?: "user" ) );
      if ( $base === "" ) $base = "user";
      $base = substr( $base, 0, 40 );
      $username = $base;
      for ( $i = 0; $i < 20; $i++ ){
        $taken = bof()->object->user->select( array( "username" => $username ) );
        if ( !$taken ) break;
        $username = $base . random_int( 100, 9999 );
      }

      $name = trim( (string) ( $res["user"]["fullname"] ?? $res["user"]["username"] ?? $username ) );
      $create = bof()->object->user->create( array(), array(
        "username" => $username,
        "name" => $name,
        "password" => "fp" . bin2hex( random_bytes( 12 ) ),
        "email" => $email,
      "time_verify" => bof()->general->mysql_timestamp(),
      "initial" => true
    ), array() );

      if ( !$create ){
        bof()->api->set_error( "signup_failed" );
        return;
      }
      $uid = (int) $create;

      $db->_update( array(
        "table" => "_u_list",
        "where" => array( array( "ID", "=", $uid ) ),
        "set" => array( array( "password_set", 0 ) )
      ) );

    }

    $sess_data = $this->_bof_this->create( $uid, true );
    bof()->chapar->notify_admin( "login_succeed", array(
      "identifier" => $email, "type" => "iyol_grant", "user_id" => $uid
    ) );

    return array(
      "auth" => array( "user" => array( "ID" => $uid ) ),
      "sess" => $sess_data
    );

  }

  /**
   * do=set_password — session-authed password set/change.
   * Accounts created via OTP/social/grant (password_set=0) set a password
   * WITHOUT a current-password check; normal accounts must send
   * current_password.
   */
  public function submit_set_password(){

    bof()->session->open();
    $uid = bof()->session->check();
    if ( !$uid ){
      bof()->api->set_error( "login_failed" );
      return;
    }

    $password = bof()->nest->user_input( "post", "password", "password" );
    $password_repeat = bof()->nest->user_input( "post", "password_repeat", "password" );
    if ( !$password || !$password_repeat || $password !== $password_repeat ){
      bof()->api->set_error( "pws_dont_match" );
      return;
    }

    $db = bof()->db;
    $u = $db->_select( array(
      "table" => "_u_list",
      "columns" => "password,password_set",
      "where" => array( array( "ID", "=", $uid ) ),
      "limit" => 1, "single" => true
    ) );
    if ( !$u ){
      bof()->api->set_error( "login_failed" );
      return;
    }

    $has_real = (int) ( $u["password_set"] ?? 1 ) === 1;
    if ( $has_real ){
      $current = bof()->nest->user_input( "post", "current_password", "string" );
      if ( !$current || !password_verify( $current, (string) $u["password"] ) ){
        bof()->api->set_error( "login_failed" );
        return;
      }
    }

    bof()->object->user->update(
      array( "ID" => $uid ),
      array( "password" => bof()->object->user->hash_password( $password ) )
    );
    $db->_update( array(
      "table" => "_u_list",
      "where" => array( array( "ID", "=", $uid ) ),
      "set" => array( array( "password_set", 1 ) )
    ) );

    bof()->api->set_message( "ok", array( "password_set" => true ) );
    return array( "password_set" => true );

  }

  /**
   * do=security_status — which identities are verified/linked on this
   * account (powers the app's Account Security screen).
   */
  public function submit_security_status(){

    bof()->session->open();
    $uid = bof()->session->check();
    if ( !$uid ){
      bof()->api->set_error( "login_failed" );
      return;
    }

    $db = bof()->db;
    $u = $db->_select( array(
      "table" => "_u_list",
      "columns" => "email,phone,password_set,google_sub",
      "where" => array( array( "ID", "=", $uid ) ),
      "limit" => 1, "single" => true
    ) );
    if ( !$u ){
      bof()->api->set_error( "login_failed" );
      return;
    }

    $iyol_linked = false;
    $iyol_username = null;
    try {
      $iyol = bof()->iyolme;
      if ( $iyol->configured() && $iyol->api_base() ){
        $payload = $iyol->user_payload( $uid );
        if ( $payload ){
          $st = $iyol->api_request( "POST", "/hitune/v1/link_status", array( "sub" => $payload["sub"] ) );
          if ( !empty( $st["linked"] ) ){
            $iyol_linked = true;
            $iyol_username = $st["user"]["username"] ?? null;
          }
        }
      }
    } catch ( \Throwable $e ) {
      $iyol_linked = false;
    }

    bof()->api->set_message( "ok", array(
      "has_password"   => (int) ( $u["password_set"] ?? 1 ) === 1,
      "phone"          => $u["phone"] ?: null,
      "phone_verified" => !empty( $u["phone"] ),
      "email"          => $u["email"] ?: null,
      "google_linked"  => !empty( $u["google_sub"] ),
      "iyol_linked"    => $iyol_linked,
      "iyol_username"  => $iyol_username
    ) );

  }

  public function submit_signup(){

    $errors = [];
    // Email or phone — at least one is required. A phone-only signup gets
    // no verification email (there is nothing to send to), so the account
    // is marked verified immediately.
    $email = bof()->nest->user_input( "post", "email", "email" );
    $phone = bof()->nest->user_input( "post", "phone", "string" );
    if ( $phone ) $phone = preg_replace( '/[^0-9+]/', '', trim( $phone ) );
    if ( $phone && !preg_match( '/^\+?[0-9]{6,20}$/', $phone ) ) $phone = null;

    if ( !$email && !$phone )
    $errors[] = bof()->object->language->turn( "invalid_input", [ "input_name" => "##email" ] );

    if ( !( $username = bof()->nest->user_input( "post", "username", "username" ) ) ){
      // Auto-generate a username from the phone number when not provided
      if ( $phone )
      $username = "u" . substr( preg_replace( '/[^0-9]/', '', $phone ), -9 );
      else
      $errors[] = bof()->object->language->turn( "invalid_input", [ "input_name" => "##username" ] );
    }
    if ( !( $password = bof()->nest->user_input( "post", "password", "password" ) ) ) $errors[] = bof()->object->language->turn( "invalid_input", [ "input_name" => "##password" ] );
    if ( !( $password_repeat = bof()->nest->user_input( "post", "password_repeat", "password" ) ) ) $errors[] = bof()->object->language->turn( "invalid_input", [ "input_name" => "##password_repeat" ] );
    if ( $password != $password_repeat ) $errors[] = bof()->object->language->turn( "pws_dont_match" );
    if ( empty( $_POST["agree"] ) ) $errors[] = bof()->object->language->turn( "u_g2_agree" );

    $guest_role = bof()->object->user_role->select(["ID"=>1]);

    if ( empty( $guest_role["data_decoded"]["guest"]["guest_signup"] ) )
    $errors[] = bof()->object->language->turn( "signup_disabled" );

    if ( !empty( $errors ) ){
      bof()->api->set_error( $errors, array(
        "output_args" => array(
          "turn" => false
        )
      ) );
      bof()->chapar->notify_admin("signup_failed", array(
        "error" => implode( ", ", $errors ),
      ));
      return;
    }

    $check_username = bof()->object->user->select(array(
      "username" => $username
    ));

    // Auto-generated phone usernames may collide — append digits until free
    if ( $check_username && $phone && empty( $_POST["username"] ) ){
      for ( $i = 0; $i < 20 && $check_username; $i++ ){
        $username = "u" . substr( preg_replace( '/[^0-9]/', '', $phone ), -7 ) . rand( 10, 99 );
        $check_username = bof()->object->user->select(array( "username" => $username ));
      }
    }

    if ( $check_username ){
      bof()->api->set_error( "username_taken" );
      bof()->chapar->notify_admin("signup_failed", array(
        "error" => "username:{$username} taken",
      ));
      return;
    }

    if ( $email ){
      $check_email = bof()->object->user->select(array(
        "email" => $email
      ));

      if ( $check_email ){
        bof()->api->set_error( "email_taken" );
        bof()->chapar->notify_admin("signup_failed", array(
          "error" => "email:{$email} taken",
        ));
        return;
      }
    }

    if ( $phone ){
      $check_phone = bof()->object->user->select(array(
        "phone" => $phone
      ));

      if ( $check_phone ){
        bof()->api->set_error( "phone_taken" );
        bof()->chapar->notify_admin("signup_failed", array(
          "error" => "phone:{$phone} taken",
        ));
        return;
      }
    }

    // Email verification only makes sense when an email was provided.
    if ( ( $verification_required = ( $email && !empty( $guest_role["data_decoded"]["guest"]["guest_signup_verify"] ) ) ) ){

      $code = md5( uniqid() );
      $time_verify = null;
      $time_verify_try = bof()->general->mysql_timestamp();
      $link = web_address . "userAuth?do=verification&email={$email}&code={$code}";

    }
    else {

      $code = null;
      $time_verify = bof()->general->mysql_timestamp();
      $time_verify_try = null;

    }

    $insert = array(
      "username" => $username,
      "password" => $password,
      "verification_code" => $code,
      "time_verify" => $time_verify,
      "time_verify_try" => $time_verify_try,
      "initial" => true
    );
    if ( $email ) $insert["email"] = $email;
    if ( $phone ) $insert["phone"] = $phone;

    $create = bof()->object->user->create(
      array(),
      $insert,
      array()
    );

    if ( $verification_required ){

      bof()->api->set_error( "verify_first", [ "verify_email" => true ] );

      bof()->chapar->notify( "email_verify", array(
        "source" => array(
          "object" => null,
          "id" => null,
        ),
        "target" => array(
          "email" => $email
        ),
        "message" => array(
          "type" => "auth",
          "texts" => array(
            "title" => "Email verification",
            "email_title" => "Email verification",
            "email_content" => "Hi there, <br><br> Please copy the following code and use it to verify to your account or <a href='{$link}' target='_blank'>click here</a><br><br><b>{$code}</b><br><br>Regards"
          )
        ),
        "methods" => array(
          "email" => true
        )
      ) );

      bof()->chapar->notify_admin("signup_pending", array(
        "email" => $email,
      ));

    } else {

      bof()->chapar->notify( "welcome", array(
        "target" => array(
          "user_id" => $create
        ),
        "source" => array(
          "object" => null,
          "id" => null
        ),
        "triggerer" => array(
          "object" => null,
          "id" => null
        ),
        "message" => array(
          "params" => []
        ),
      ) );

      $this->_bof_this->create( $create, true );

      bof()->chapar->notify_admin("signup_ok", array(
        "email" => $email
      ));

    }

    return array(
      "verified" => !$verification_required,
      "id" => $create
    );

  }
  public function submit_recover(){

    $errors = [];
    if ( !( $email = bof()->nest->user_input( "post", "email", "email" ) ) ) $errors[] = bof()->object->language->turn( "invalid_input", [ "input_name" => "##email" ] );

    if ( !empty( $errors ) ){
      bof()->api->set_error( $errors, array(
        "output_args" => array(
          "turn" => false
        )
      ) );
      return;
    }

    $check_email = bof()->object->user->select(array(
      "email" => $email
    ));

    if ( $check_email ){
      $time_verify_try_ago = $check_email["time_verify_try"] ? time() - strtotime( $check_email["time_verify_try"] ) : false;
      if ( !$time_verify_try_ago ? true : $time_verify_try_ago > 5*60 ){

        $code = md5( uniqid() );

        bof()->object->user->update(
          array(
            "ID" => $check_email["ID"]
          ),
          array(
            "verification_code" => $code,
            "time_verify_try" => bof()->general->mysql_timestamp()
          )
        );

        $link = web_address . "userAuth?do=recover_confirm&email={$email}&code={$code}";

        $send = bof()->chapar->notify( "account_recovery", array(
          "source" => array(
            "object" => null,
            "id" => null,
          ),
          "target" => array(
            "email" => $email
          ),
          "message" => array(
            "type" => "auth",
            "texts" => array(
              "title" => "Account Recover",
              "email_title" => "Account Recover",
              "email_content" => "Hi there, <br><br> Please copy the following code and use it to recover to your account or <a href='{$link}' target='_blank'>click here</a><br><br><b>{$code}</b><br><br>Regards"
            )
          ),
          "methods" => array(
            "email" => true
          )
        ) );

        bof()->chapar->notify_admin("recover_r_ok", array(
          "email" => $email,
        ));

        bof()->api->set_message("recovery_email_sent",["recover_by_email"=>true]);
        return;

      } else {

        bof()->chapar->notify_admin("recover_r_failed", array(
          "email" => $email,
          "error" => "already requested"
        ));

      }
    } else {
      bof()->chapar->notify_admin("recover_r_failed", array(
        "email" => $email,
        "error" => "no such email"
      ));
    }

    bof()->api->set_message("recovery_email_sent",["recover_by_email"=>true]);

    return array(
      "auth" => $check_email,
      "code" => !empty( $code ) ? $code : null
    );

  }
  public function submit_recover_confirm(){

    $errors = [];
    if ( !( $email = bof()->nest->user_input( "post", "email", "email" ) ) ) $errors[] = bof()->object->language->turn( "invalid_input", [ "input_name" => "##email" ] );
    if ( !( $code = bof()->nest->user_input( "post", "code", "md5" ) ) ) $errors[] = bof()->object->language->turn( "invalid_input", [ "input_name" => "##code" ] );
    if ( !( $password = bof()->nest->user_input( "post", "password", "password" ) ) ) $errors[] = bof()->object->language->turn( "invalid_input", [ "input_name" => "##password" ] );
    if ( !( $password_repeat = bof()->nest->user_input( "post", "password_repeat", "password" ) ) ) $errors[] = bof()->object->language->turn( "invalid_input", [ "input_name" => "##password_repeat" ] );
    if ( $password != $password_repeat ) $errors[] = bof()->object->language->turn( "pws_dont_match" );

    if ( !empty( $errors ) ){
      bof()->api->set_error( $errors, array(
        "output_args" => array(
          "turn" => false
        )
      ) );
      bof()->chapar->notify_admin("recover_s_failed", array(
        "error" => "invalid inputs: " . implode( ", ", $errors ),
        "email" => $email
      ));
      return;
    }

    $check_email = bof()->object->user->select(array(
      "email" => $email
    ));

    if ( $check_email ){

      $time_verify_try_ago = $check_email["time_verify_try"] ? time() - strtotime( $check_email["time_verify_try"] ) : false;
      if ( $time_verify_try_ago < 30*60 ){
        if ( $check_email["verification_code"] == $code ){

          $updateArray = array(
            "time_verify_try" => false,
            "verification_code" => false,
            "password" => bof()->object->user->hash_password( $password )
          );

          if ( empty( $check_email["time_verify"] ) )
          $updateArray["time_verify"] = bof()->general->mysql_timestamp();

          bof()->object->user->update(
            array(
              "ID" => $check_email["ID"]
            ),
            $updateArray
          );

          bof()->chapar->notify_admin("recover_s_ok", array(
            "user_id" => $check_email["ID"],
            "email" => $email
          ));

          $sess_data = $this->_bof_this->create( $check_email["ID"], true );
        } else {
          bof()->chapar->notify_admin("recover_s_failed", array(
            "error" => "invalid code",
            "email" => $email
          ));
        }
      } else {
        bof()->chapar->notify_admin("recover_s_failed", array(
          "error" => "timed out",
          "email" => $email
        ));
      }

    } else {

      bof()->chapar->notify_admin("recover_s_failed", array(
        "error" => "no such email",
        "email" => $email
      ));

    }

    return array(
      "auth" => $check_email,
      "sess" => !empty( $sess_data ) ? $sess_data : null
    );

  }
  public function submit_verification(){

    $errors = [];
    if ( !( $email = bof()->nest->user_input( "post", "email", "email" ) ) ) $errors[] = bof()->object->language->turn( "invalid_input", [ "input_name" => "##email" ] );
    if ( !( $code = bof()->nest->user_input( "post", "code", "md5" ) ) ) $errors[] = bof()->object->language->turn( "invalid_input", [ "input_name" => "##code" ] );

    if ( !empty( $errors ) ){
      bof()->api->set_error( $errors, array(
        "output_args" => array(
          "turn" => false
        )
      ) );
      bof()->chapar->notify_admin("verify_failed", array(
        "error" => "invalid inputs: " . implode( ",", $errors ),
      ));
      return;
    }

    $check_email = bof()->object->user->select(array(
      "email" => $email
    ));

    if ( $check_email ){

      if ( empty( $check_email["time_verify"] ) && !empty( $check_email["time_verify_try"] ) ){

        $time_verify_try_ago = $check_email["time_verify_try"] ? time() - strtotime( $check_email["time_verify_try"] ) : false;
        if ( $time_verify_try_ago < 30*60 ){
          if ( $check_email["verification_code"] == $code ){

            bof()->object->user->update(
              array(
                "ID" => $check_email["ID"]
              ),
              array(
                "time_verify_try" => false,
                "verification_code" => false,
                "time_verify" => bof()->general->mysql_timestamp()
              )
            );

            bof()->chapar->notify( "welcome", array(
              "target" => array(
                "user_id" => $check_email["ID"]
              ),
              "source" => array(
                "object" => null,
                "id" => null
              ),
              "triggerer" => array(
                "object" => null,
                "id" => null
              ),
              "message" => array(
                "params" => []
              ),
            ) );

            bof()->chapar->notify_admin("verify_ok", array(
              "email" => $email,
              "user_id" => $check_email["ID"]
            ));

            $sess_data = $this->_bof_this->create( $check_email["ID"], true );
            
          } else {
            bof()->chapar->notify_admin("verify_failed", array(
              "error" => "invalid code",
              "email" => $email
            ));
          }
        } else {
          bof()->chapar->notify_admin("verify_failed", array(
            "error" => "timed out",
            "email" => $email
          ));
        }

      }

    } else {
      bof()->chapar->notify_admin("verify_failed", array(
        "error" => "no such email: {$email}",
      ));
    }

    return array(
      "auth" => $check_email,
      "sess" => !empty( $sess_data ) ? $sess_data : null
    );

  }

}

?>
