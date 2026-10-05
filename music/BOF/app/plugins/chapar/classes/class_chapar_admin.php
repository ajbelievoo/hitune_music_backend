<?php

if ( !defined( "bof_root" ) ) die;

class chapar_admin extends bof_type_class  {

  public function get_args( $event ){

    $args = array(
      "admin_login_failed" => array(
        "title" => "Failed admin login",
        "text" => "A login request has resulted in failure. Email: %email%. Reason: %error%",
        "icon" => "key_off"
      ),
      "admin_login_ok" => array(
        "title" => "Successful admin login",
        "text" => "An admin/moderator has logged in. Email: %email%",
        "icon" => "key"
      ),
      "signup_ok" => array(
        "title" => "Successful sign up",
        "text" => "New successful sign up. Email: %email%",
        "icon" => "person_add"
      ),
      "signup_pending" => array(
        "title" => "Pending sign up",
        "text" => "New pending sign up. Will be successful after email:%email% verification",
        "icon" => "person_add"
      ),
      "signup_failed" => array(
        "title" => "Failed sign up",
        "text" => "A sign up request has been failed. Reason: %error%",
        "icon" => "person_add_disabled"
      ),
      "login_failed" => array(
        "title" => "Failed login",
        "text" => "A login request has resulted in failure. Email: %email%. Reason: %error%",
        "icon" => "key_off"
      ),
      "slogin_failed" => array(
        "title" => "Failed Social login",
        "text" => "A social-login request has resulted in failure. Reason: %error%",
        "icon" => "key_off"
      ),
      "recover_r_failed" => array(
        "title" => "Failed password reset request",
        "text" => "A user request for resetting password has failed. Email: %email%. Reason: %error%",
        "icon" => "key_off"
      ),
      "recover_s_failed" => array(
        "title" => "Failed password reset",
        "text" => "A user attempt for resetting password has failed. Email: %email%. Reason: %error%",
        "icon" => "key_off"
      ),
      "verify_failed" => array(
        "title" => "Failed email verification",
        "text" => "A user attempt for verifying email has failed. Reason: %error%",
        "icon" => "key_off"
      ),
      "login_ok" => array(
        "title" => "Successful login",
        "text" => "A user has logged in. Email: %email%",
        "icon" => "key"
      ),
      "slogin_ok" => array(
        "title" => "Successful social login",
        "text" => "A user has logged in with their social-media account",
        "icon" => "key"
      ),
      "recover_r_ok" => array(
        "title" => "Successful password reset request",
        "text" => "A user has initiated a password reset for email: %email%",
        "icon" => "key"
      ),
      "recover_s_ok" => array(
        "title" => "Successful password reset",
        "text" => "A user has initiated a password reset for email: %email%",
        "icon" => "key"
      ),
      "verify_ok" => array(
        "title" => "Successful email verification",
        "text" => "A user has successfully reset their password for email: %email%",
        "icon" => "key"
      ),
      "royalty_paid" => array(
        "title" => "Royalty paid",
        "text" => "Paid %amount% to creator for their %streams% streams",
        "icon" => "money_off"
      ),
      "new_purchase_share" => array(
        "title" => "User share paid",
        "text" => "Paid %amount% to creator for new sale. Role: %role%. Object: %object_name%. Amount: %amount%. Item title: %item_title%. Item ID: %item_id%",
        "icon" => "money_off"
      ),
      "sub_canceled" => array(
        "title" => "Subscription cancelled",
        "text" => "A subscription has been canceled. Sub ID: %sub_id%",
        "icon" => "money_off"
      ),
      "pay_ini_ok" => array(
        "title" => "Payment initiated",
        "text" => "A user has initiated a payment through %gateway_name% for amount: %amount%",
        "icon" => "credit_card"
      ),
      "pay_ini_failed" => array(
        "title" => "Payment initiation failed",
        "text" => "A user tried to initiate a payment through %gateway_name% but failed. Reason: %error%",
        "icon" => "credit_card_off"
      ),
      "pay_check_failed" => array(
        "title" => "Payment check failed",
        "text" => "A user failed to finaize a payment supposedly paid through %gateway_name%. Reason: %error%",
        "icon" => "credit_card_off"
      ),
      "pay_check_ok" => array(
        "title" => "Successful payment",
        "text" => "A user successfully made a payment through %gateway_name%. Amount: %amount%",
        "icon" => "credit_score"
      ),
      "pay_check_approved" => array(
        "title" => "Successful payment approved",
        "text" => "A payment made by user has been approved. Gateway: %gateway_name%. Amount: %amount%",
        "icon" => "credit_score"
      ),
      "upload_ok" => array(
        "title" => "New upload",
        "text" => "A user has successfully uploaded new content",
        "icon" => "upload"
      ),
      "new_purchase" => array(
        "title" => "New purchase",
        "text" => "A user has made a new purchase. Object: %object_name%. Amount: %amount%. Item title: %item_title%. Item ID: %item_id%",
        "icon" => "add_shopping_cart"
      ),
      "ad_clicked" => array(
        "title" => "Advertisement clicked",
        "text" => "An advertisement was clicked by a visitor! Ad URL: %ad_url%. Ad ID: %ad_id%",
        "icon" => "campaign"
      ),
      "liked" => array(
        "title" => "New like",
        "text" => "A `%item_ot%` ID:#%item_id% `Title:%item_title%` was liked by user",
        "icon" => "favorite"
      ),
      "subscribed" => array(
        "title" => "New follow",
        "text" => "A %object_name% named `%item_title%` was followed by a user",
        "icon" => "favorite"
      ),
      "unliked" => array(
        "title" => "New unlike",
        "text" => "A `%item_ot%` ID:#%item_id% `Title:%item_title%` previously liked by user has now been unliked",
        "icon" => "heart_minus"
      ),
      "unsubscribed" => array(
        "title" => "New unfollow",
        "text" => "A %object_name% named `%item_title%` previously followed has now been unfollowed by a user",
        "icon" => "heart_minus"
      ),
      "playlist_new" => array(
        "title" => "New playlist",
        "text" => "A new playlist has been created. Name: %name%",
        "icon" => "playlist_add_check"
      ),
      "playlist_edited" => array(
        "title" => "Playlist edited",
        "text" => "A playlist named `%name%` has been edited",
        "icon" => "playlist_add_check"
      ),
      "playlist_kept" => array(
        "title" => "Playlist added to library",
        "text" => "A playlist named `%name%` has been added to user's library",
        "icon" => "playlist_add_check"
      ),
      "playlist_extended" => array(
        "title" => "Playlist extended",
        "text" => "New content added to a user's playlist named %name%",
        "icon" => "playlist_add"
      ),
      "playlist_shorten" => array(
        "title" => "Playlist shortened",
        "text" => "Some content has been removed from a user's playlist named `%name%`",
        "icon" => "playlist_remove"
      ),
      "playlist_removed" => array(
        "title" => "Playlist removed",
        "text" => "A playlist named `%name%` has been removed",
        "icon" => "playlist_remove"
      ),
      "playlist_released" => array(
        "title" => "Playlist removed from library",
        "text" => "A playlist named `%name%` has been removed from the user's library",
        "icon" => "playlist_remove"
      ),
      "user_session_delete" => array(
        "title" => "Session deleted",
        "text" => "A session has been deleted by user",
        "icon" => "person_edit"
      ),
      "user_edit_ok" => array(
        "title" => "User profile edited",
        "text" => "A user has made changes to their profile",
        "icon" => "person_edit"
      ),
      "user_secure_failed" => array(
        "title" => "User setting change",
        "text" => "A user has failed to change their password. Reason: %error%",
        "icon" => "person_edit"
      ),
      "user_secure_ok" => array(
        "title" => "User setting change",
        "text" => "A user has successfully changed their password",
        "icon" => "person_edit"
      ),
      "user_notification_ok" => array(
        "title" => "User setting change",
        "text" => "A user has successfully changed their notification setting",
        "icon" => "person_edit"
      ),
      "user_social_ok" => array(
        "title" => "User setting change",
        "text" => "A user has successfully changed their social profile links",
        "icon" => "person_edit"
      ),
      "user_delete_ok" => array(
        "title" => "User account deleted",
        "text" => "A user has successfully deleted their account",
        "icon" => "delete"
      ),
      "edit_ok" => array(
        "title" => "User edited content",
        "text" => "A user has successfully edited their uploaded content. Object: %object_name%. Type: %type%. Item: %item_title%",
        "icon" => "edit"
      ),
      "edit_failed" => array(
        "title" => "User failed to edit content",
        "text" => "A user has failed to edit their uploaded content. Object: %object_name%. Type: %type%. Item: %item_title%. Reason: %error%",
        "icon" => "edit_off"
      ),
      "delete_ok" => array(
        "title" => "User deleted their content",
        "text" => "A user has successfully removed some of their uploaded content. Object: %object_name%. Item: %item_title%",
        "icon" => "delete"
      ),
      "user_logged_out" => array(
        "title" => "User logout",
        "text" => "A user has logged out",
        "icon" => "fingerprint_off"
      ),
      "push_not_ok" => array(
        "title" => "Push notification registered",
        "text" => "A user has successfully registered for push notification. Type: %type%",
        "icon" => "notification_add"
      ),
      "man_verify_failed" => array(
        "title" => "ID-verification failed",
        "text" => "َA user request for becoming partner failed. Reason: %error%",
        "icon" => "add_moderator"
      ),
      "man_verify_requested" => array(
        "title" => "ID-verification request submitted",
        "text" => "َA user request for becoming a partner has been submitted. Waiting for your review. Type: %type%. Name: %real_name%",
        "icon" => "add_moderator"
      ),
      "man_verify_approved" => array(
        "title" => "ID-verification approved",
        "text" => "َA user request for becoming a partner has been approved. Type: %type%. Name: %real_name%",
        "icon" => "add_moderator"
      ),
      "withdraw_ok" => array(
        "title" => "Withdrawal request submitted",
        "text" => "َA partner has submitted a request for fund withdrawal. Amount: %amount%. Waiting for your action and status update",
        "icon" => "approval_delegation"
      ),
      "withdraw_failed" => array(
        "title" => "Withdrawal request failed",
        "text" => "َA partner has failed to submit a request for fund withdrawal. Reason: %error%",
        "icon" => "approval_delegation"
      ),
    );

    if ( empty( $event ) )
    return $args;

    if ( empty( $args[ $event ] ) )
    return;

    return $args[ $event ];

  }

  public function notify($event, $args = []){

    $user_id = !empty(bof()->user->get()->ID) ? bof()->user->get()->ID : null;
    $user_ip = !empty(bof()->request->get_userIP()["string"]) ? bof()->request->get_userIP()["string"] : null;
    $icon = null;
    $text = null;
    $title = null;
    extract($args);

    bof()->db->_insert(array(
      "table" => "_admin_notifications",
      "set" => array(
        ["event", $event],
        ["icon", $icon],
        ["title", $title],
        ["text", $text],
        ["user_id", $user_id],
        ["user_ip", $user_ip],
        ["args", json_encode($args)]
      )
    ));

  }

  public function get_endpoint(){

    $nots = [];
    $notLimit = 5;
    $notTime = false;

    if (($get_paid_unapproved_payments = bof()->object->payment->count(
      array(
        "paid" => true,
        "approved" => false
      ),
      array(
        "cache" => false
      )
    ))){
      $nots[] = array(
        "icon" => "payments",
        "title" => "Unapproved payments",
        "text" => "There are currently {$get_paid_unapproved_payments} paid, but unapproved payment(s)",
        "buttons" => array(
          ["primary", "Manage", "payments"],
        )
      );
    }

    if (($get_pending_user_requests = bof()->object->user_request->count(
      array(
        "sta" => 0
      ),
      array(
        "cache" => false
      )
    ))){
      $nots[] = array(
        "icon" => "new_releases",
        "title" => "User Requests",
        "text" => "There are currently {$get_pending_user_requests} pending user request(s)",
        "buttons" => array(
          ["primary", "Manage", "user_requests?sta=0"],
        )
      );
    }

    $page = bof()->nest->user_input( "post", "page", "int", [ "min" => 1 ], 1 );

    $db_nots = bof()->db->_select(array(
      "table" => "_admin_notifications",
      "limit" => $notLimit,
      "single" => false,
      "order_by" => "ID",
      "order" => "DESC",
      "offset" => ($page-1)*$notLimit,
      "where" => array(
        [ "time_send", "NOT", null, true ]
      )
    ));

    if ($db_nots) {
      foreach ($db_nots as $db_not) {
        $nots[] = array(
          "icon" => $db_not["icon"] ? $db_not["icon"] : "help",
          "title" => $db_not["title"],
          "text" => str_replace( "-- User", "<br><br>User", $db_not["text_parsed"] ),
        );
        $ts = strtotime( $db_not["time_send"] );
        if ( $ts && $page == 1 ? ( $ts > $notTime ) : false ){
          $notTime = $ts;
        }
      }
    }

    return array(
      "list" => $nots,
      "next" => $nots ? ( count( $nots ) >= $notLimit ) : false,
      "time" => $notTime
    );

  }

  public function exe_notifications( $PID, $GID ){

    $db_nots = bof()->db->_select(array(
      "table" => "_admin_notifications",
      "limit" => false,
      "single" => false,
      "order_by" => "ID",
      "order" => "ASC",
      "where" => array(
        [ "time_send", null, null, true ]
      )
    ));

    if ( !$db_nots ) return "Nothing to process";

    $setting = bof()->object->db_setting->get( "admin_notifications" );

    foreach( $db_nots as $db_not ){

      bof()->cronjob->log_p( $PID, $GID, "Processed notification #{$db_not["ID"]}. Event: {$db_not["event"]}" );
      $this->_bof_this->exe_notification( $db_not, $setting );

    }

  }
  public function exe_notification( $db_record, $setting ){

    $event = $db_record["event"];

    $defArgs = $this->_bof_this->get_args($event);

    $icon = !empty( $db_record["icon"] ) ? $db_record["icon"] : $defArgs["icon"];
    $text = !empty( $db_record["text"] ) ? $db_record["text"] : $defArgs["text"];
    $title = !empty( $db_record["title"] ) ? $db_record["title"] : $defArgs["title"];

    $user_id = $db_record["user_id"];
    if ( !empty( $user_id ) ) {
      $user_data = bof()->object->user->sid($user_id, ["clean" => false]);
      $user_email = !empty($db_record["user_email"]) ? $db_record["user_email"] : $user_data["email"];
      $user_name = !empty($db_record["user_name"]) ? $db_record["user_name"] : $user_data["username"];
    }

    $text_parsed = bof()->object->language->parse_params( $text, !empty( $db_record["args"] ) ? json_decode( $db_record["args"], true ) : [] );
    $text_parsed .= " -- User: IP: {$db_record["user_ip"]}";
    if ( !empty( $user_id ) ){
      $text_parsed .= ", ID: {$user_id}, Username: {$user_name}";
    }

    if (empty($setting["active"]) || empty($setting["{$event}_active"])) {

      bof()->db->_delete(array(
        "table" => "_admin_notifications",
        "where" => array(
          ["ID", "=", $db_record["ID"]]
        ),
      ));

    } 
    else {

      // DB
      bof()->db->_update(array(
        "table" => "_admin_notifications",
        "where" => array(
          ["ID", "=", $db_record["ID"]]
        ),
        "set" => array(
          ["time_send", "now()", true],
          ["icon", $icon],
          ["text", $text],
          ["title", $title],
          ["user_email", !empty( $user_email ) ? $user_email : null],
          ["user_name", !empty( $user_name ) ? $user_name : null],
          ["text_parsed", $text_parsed]
        )
      ));

      // Email
      if ( 
        !empty( $setting["email_active"] ) && 
        !empty( $setting["{$event}_email_active"] )
      ){
        $admin = bof()->object->user->sid( 1, [ "clean" => false ] );
        bof()->chapar->method_exe("email", array(
          "target_email" => $admin["email"],
          "message_title" => $title,
          "message_content" => $text_parsed,
          "extra" => []
        ));
      }

      // Telegram
      if ( 
        !empty( $setting["telegram_active"] ) && 
        !empty( $setting["telegram_receiver"] ) && 
        !empty( $setting["{$event}_telegram_active"] ) 
      ){

        $telegram_text_parsed = "*{$title}*\n\n";
        $telegram_text_parsed .= bof()->object->language->parse_params($text, !empty($db_record["args"]) ? json_decode($db_record["args"], true) : []);
        $telegram_text_parsed .= "\n\n--- User IP: {$db_record["user_ip"]}";
        if (!empty($user_id)) $telegram_text_parsed .= ", ID: {$user_id}, Username: {$user_name}";

        bof()->boac->telegram_notify($setting["telegram_receiver"], str_replace( "#", "", $telegram_text_parsed ) );

      }

      // Whatsapp
      if ( 
        !empty( $setting["whatsapp_active"] ) && 
        !empty( $setting["whatsapp_phone"] ) && 
        !empty( $setting["{$event}_whatsapp_active"] ) 
      ){

        $whatsapp_text_parsed = "*{$title}*\n\n";
        $whatsapp_text_parsed .= bof()->object->language->parse_params($text, !empty($db_record["args"]) ? json_decode($db_record["args"], true) : []);
        $whatsapp_text_parsed .= "\n\n--- User IP: {$db_record["user_ip"]}";
        if (!empty($user_id)) $whatsapp_text_parsed .= ", ID: {$user_id}, Username: {$user_name}";
        
        bof()->boac->whatsapp_notify( $setting["whatsapp_phone"], str_replace( "#", "", $whatsapp_text_parsed ) );

      }

    }

  }

}

?>
