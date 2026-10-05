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
    if ( !( $email = bof()->nest->user_input( "post", "email", "email" ) ) ) $errors[] = bof()->object->language->turn( "invalid_input", [ "input_name" => "##email" ] );
    if ( !( $password = bof()->nest->user_input( "post", "password", "password" ) ) ) $errors[] = bof()->object->language->turn( "invalid_input", [ "input_name" => "##password" ] );

    if ( !empty( $errors ) ){
      bof()->api->set_error( $errors, array(
        "output_args" => array(
          "turn" => false
        )
      ) );
      return;
    }

    $auth = bof()->object->user->authorize( "email", $email, $password, "client" );

    if ( !$auth ){
      bof()->api->set_error( "login_failed" );
      bof()->chapar->notify_admin( "login_failed", array(
        "email" => $email,
        "error" => "Invalid password"
      ) );
      return;
    }

    $sess_data = $this->_bof_this->create( $auth["user"]["ID"], true );
    bof()->chapar->notify_admin("login_succeed", array(
      "email" => $email,
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
  public function submit_signup(){

    $errors = [];
    if ( !( $email = bof()->nest->user_input( "post", "email", "email" ) ) ) $errors[] = bof()->object->language->turn( "invalid_input", [ "input_name" => "##email" ] );
    if ( !( $username = bof()->nest->user_input( "post", "username", "username" ) ) ) $errors[] = bof()->object->language->turn( "invalid_input", [ "input_name" => "##username" ] );
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

    if ( $check_username ){
      bof()->api->set_error( "username_taken" );
      bof()->chapar->notify_admin("signup_failed", array(
        "error" => "username:{$username} taken",
      ));
      return;
    }

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

    if ( ( $verification_required = !empty( $guest_role["data_decoded"]["guest"]["guest_signup_verify"] ) ) ){

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

    $create = bof()->object->user->create(
      array(),
      array(
        "username" => $username,
        "email" => $email,
        "password" => $password,
        "verification_code" => $code,
        "time_verify" => $time_verify,
        "time_verify_try" => $time_verify_try,
        "initial" => true
      ),
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
