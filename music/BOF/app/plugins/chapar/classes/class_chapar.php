<?php

if ( !defined( "bof_root" ) ) die;

class chapar extends bof_type_class  {

  public $debug = false;

  public function set_debug( $val ){
    $this->debug = $val;
  }
  public function get_debug(){
    return $this->debug;
  }

  public function notify( $hook, $args ){

    try {
      $this->_bof_this->exe( $hook, $args );
    } catch( bofException $err ){
      return $err->getExtra()["reason"];
    }

    return true;

  }
  public function unnotify( $whereArray ){
    bof()->object->user_notification->delete( $whereArray );
  }

  public function exe( $hook, $args ){

    // Default args
    $source = array(
      "object" => bof()->user->check()->logged ? "user" : null,
      "id" => bof()->user->check()->logged ? bof()->user->check()->ID : null
    );
    $triggerer = array(
      "object" => null,
      "id" => null,
    );
    $target = array(
      "user_id" => bof()->user->check()->logged ? bof()->user->check()->ID : null,
      "user_hash" => null,
      "user_data" => null,
      "email" => null,
      "push_ids" => []
    );
    $message = array(
      "type" => "basic",
      "texts" => null,
      "image" => null,
      "link" => null,
      "params" => []
    );
    $methods = array(
      "db" => null,
      "email" => null,
      "push" => null
    );
    $extra = [];

    extract( $source, EXTR_PREFIX_ALL, "source" );
    extract( $triggerer, EXTR_PREFIX_ALL, "triggerer" );
    extract( $target, EXTR_PREFIX_ALL, "target" );
    extract( $message, EXTR_PREFIX_ALL, "message" );
    extract( $methods, EXTR_PREFIX_ALL, "method" );

    // Given args
    if ( !empty( $args["source"] ) ) extract( $args["source"], EXTR_PREFIX_ALL, "source" );
    if ( !empty( $args["triggerer"] ) ) extract( $args["triggerer"], EXTR_PREFIX_ALL, "triggerer" );
    if ( !empty( $args["target"] ) ) extract( $args["target"], EXTR_PREFIX_ALL, "target" );
    if ( !empty( $args["message"] ) ) extract( $args["message"], EXTR_PREFIX_ALL, "message" );
    if ( !empty( $args["methods"] ) ) extract( $args["methods"], EXTR_PREFIX_ALL, "method" );
    if ( !empty( $args["extra"] ) ) $extra = $args["extra"];

    if ( empty( $message["texts"] ) )
    $message["texts"] = [];

    if ( $hook && $message_type == "basic" ){

      $hook_data = bof()->object->notification->select([ "hook" => $hook ]);

      if ( empty( $hook_data ) )
      throw new bofException( "invalid_args", 0, null, [ "reason" => "invalid_hook" ] );

      // dynamics ( in lack of statics )
      if ( !empty( $hook_data["texts_decoded"]["title"] ) && empty( $message_texts["title"] ) )
      $message_texts["title"] = $message_texts["db_title"] = $message_texts["email_title"] = $message_texts["push_title"] = bof()->object->language->parse_params( $hook_data["texts_decoded"]["title"], $message_params );

      if ( !empty( $hook_data["texts_decoded"]["content"] ) && empty( $message_texts["content"] ) )
      $message_texts["content"] = $message_texts["email_content"] = bof()->object->language->parse_params( $hook_data["texts_decoded"]["content"], $message_params );

      if ( !empty( $hook_data["texts_decoded"]["email_title"] ) && empty( $message_texts["title_email"] ) )
      $message_texts["email_title"] = bof()->object->language->parse_params( $hook_data["texts_decoded"]["email_title"], $message_params );

      if ( !empty( $hook_data["texts_decoded"]["push_title"] ) && empty( $message_texts["title_push"] ) )
      $message_texts["push_title"] = bof()->object->language->parse_params( $hook_data["texts_decoded"]["push_title"], $message_params );

      if ( !empty( $hook_data["texts_decoded"]["db_title"] ) && empty( $message_texts["title_db"] ) )
      $message_texts["db_title"] = bof()->object->language->parse_params( $hook_data["texts_decoded"]["db_title"], $message_params );

      if ( !empty( $hook_data["texts_decoded"]["email_content"] ) && empty( $message_texts["content_email"] ) )
      $message_texts["email_content"] = bof()->object->language->parse_params( $hook_data["texts_decoded"]["email_content"], $message_params );

      if ( empty( $hook_data["setting_decoded"]["methods"]["all"] ) )
      throw new bofException( "deactivated", 0, null, [ "reason" => "given" ] );

      $method_db = !empty( $hook_data["setting_decoded"]["methods"]["db"] );
      $method_email = !empty( $hook_data["setting_decoded"]["methods"]["email"] );
      $method_push = !empty( $hook_data["setting_decoded"]["methods"]["push"] );

    }

    if ( empty( $message_texts["title"] ) )
    throw new bofException( "invalid_args", 0, null, [ "reason" => "invalid_message_title" ] );

    if ( empty( $message["texts"]["title"] ) && !empty( $message_texts["db_title"] ) )
    $message["texts"]["title"] = $message_texts["db_title"];

    // Validate target
    if ( empty( $target_user_id ) && empty( $target_user_hash ) && empty( $target_user_data ) && empty( $target_email ) && empty( $target_push_id ) )
    throw new bofException( "invalid_args", 0, null, [ "reason" => "no_valid_target" ] );
    if ( empty( $target_user_data ) && ( !empty( $target_user_id ) || !empty( $target_user_hash ) ) ){

      $_get_user_data = bof()->object->user->select( [
        !empty( $target_user_id ) ? "ID" : "hash" =>
        !empty( $target_user_id ) ? $target_user_id : $target_user_hash
      ] );

      if ( empty( $_get_user_data ) )
      throw new bofException( "invalid_args", 0, null, [ "reason" => "invalid_user_hook" ] );

      $target_user_data = $_get_user_data;

      $user_setting = bof()->object->user_setting->get_notification( $target_user_data["ID"] );
      if ( empty( $user_setting[$hook] ) ){
        $method_db = $method_push = $method_email = false;
      }

      if ( $method_email && !bof()->object->user_setting->get( $target_user_data["ID"], "notify_email", bof()->object->db_setting->get("ma_sub_default") ) )
      $method_email = false;

    }

    if ( empty( $target_email ) && !empty( $target_user_data["email"] ) )
    $target_email = $target_user_data["email"];
    if ( empty( $target_push_ids ) && !empty( $target_user_data ) ){

      $get_push_ids = bof()->db->_select(array(
        "table" => "_u_push_subs",
        "where" => array(
          [ "user_id", "=", $target_user_data["ID"] ]
        ),
        "limit" => 10,
        "order_by" => "ID",
        "single" => false
      ));

      if ( $get_push_ids ){
        foreach( $get_push_ids as $get_push_id ){
          $target_push_ids[] = array(
            "platform" => $get_push_id["platform"],
            "data" => json_decode( $get_push_id["data"], true )
          );
        }
      }

    }

    if ( $method_db ){

      bof()->object->user_notification->insert( array(

        "hook" => $hook,

        "user_id" => !empty( $target_user_data["ID"] ) ? $target_user_data["ID"] : null,
        "target_email" => $method_email ? $target_email : null,
        "target_push_count" => $method_push ? count($target_push_ids) : null,

        "triggerer_object" => $triggerer_object,
        "triggerer_id" => $triggerer_id,

        "message_type" => $message_type,
        "message_params" => $message_params,
        "message_texts" => !empty( $message["texts"] ) ? json_encode( $message["texts"] ) : null,
        "message_image" => $message_image,
        "message_link" => $message_link,

        "source_object" => $source_object,
        "source_id" => $source_id,

        "method_email" => $method_email,
        "method_push" => $method_push,

        "extra" => $extra

      ) );

    }

    if ( $method_email )
    $this->_bof_this->method_exe( "email", array(
      "target_user_id" => $target_user_id,
      "target_email" => $target_email,
      "message_title" => strip_tags( $message_texts["email_title"], "" ),
      "message_content" => $message_texts["email_content"],
      "message_image" => $message_image,
      "extra" => $extra
    ) );

    if ( $method_push )
    $this->_bof_this->method_exe( "push", array(
      "target_push_ids" => $target_push_ids,
      "message_title" => strip_tags( $message_texts["push_title"], "" ),
      "message_image" => $message_image,
      "extra" => $extra
    ) );

  }
  public function method_exe( $type, $args ){
    bof()->__get( "chapar_{$type}" )->exe( $args );
  }

  public function notify_admin( $event, $args=[] ){
    bof()->chapar_admin->notify( $event, $args );
  }

}

?>
