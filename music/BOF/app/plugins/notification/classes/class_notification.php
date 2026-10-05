<?php

if ( !defined( "bof_root" ) ) die;

class notification {

  protected $clients = [];
  protected $keys = [];

  public function set_key( $var, $val ){
    $this->keys[ $var ] = $val;
  }
  protected function getClient( $type ){

    if (!empty($this->clients[$type]))
    return $this->clients[$type];

    if ( $type == "push" ){

      if ( empty( $this->keys[ "push_server_api_key" ] ) || empty( $this->keys[ "push_sender_id" ] ) )
      return false;

      require_once( dirname(__FILE__) . "/third/EdwinHoksberg_php-fcm/autoload.php" );
      $client = new \Fcm\FcmClient( $this->keys[ "push_server_api_key" ], $this->keys[ "push_sender_id" ] );

    }


    $this->clients[$type] = $client;
    return $client;

  }
  public function send( $user_id, $args ){

    $user_telegram = null;
    $user_email = null;
    $user_push_ids = null;
    $user_phone = null;
    $send_telegram = false;
    $send_email = true;
    $send_push = false;
    $send_push_badge = false;
    $send_push_sound = false;
    $send_push_icon = false;
    $send_push_color = false;
    $send_push_datas = false;
    $send_phone = false;
    $object_type = null;
    $object_event = null;
    $object_args = null;
    extract( $args );

    if ( !$object_type || !$object_event )
    return false;

    $notification_content = bof()->object->notification->convert( $object_type, $object_event, $object_args );
    if ( !$notification_content ) return false;

    $content_title = null;
    $content_short = null;
    $content_long = null;
    $content_icon = null;
    extract( $notification_content, EXTR_PREFIX_ALL, "content" );
    if ( !$content_long && $content_short )
    $content_long = $content_short;

    // send telegram
    $send_telegram_sta = $send_telegram_result = null;
    if ( $user_telegram && $send_telegram ){

    }

    // send email
    $send_email_sta = $send_email_result = null;
    if ( $user_email && $send_email ){

    }

    // send push notification
    $send_push_sta = $send_push_result = null;
    if ( $user_push_ids && $send_push ){
      foreach( $user_push_ids as $user_push_id ){
        list( $_send_push_sta, $_send_push_result ) = $this->send_push( $user_push_id, $content_title, $content_short, array(
          "badge" => $send_push_badge,
          "color" => $send_push_color,
          "icon" => $send_push_icon,
          "datas" => $send_push_datas,
          "sound" => $send_push_sound
        ) );
        $send_push_sta = $send_push_sta === null ? $_send_push_sta : ( $_send_push_sta ? $_send_push_sta : false );
        $send_push_result[ $user_push_id ] = $_send_push_result;
      }
      $send_push_result = json_encode( $send_push_result );
    }

    // send sms
    $send_phone_sta = $send_phone_result = null;
    if ( $user_phone && $send_phone ){

    }

    $notification_id = bof()->object->notification->create(array(

      "user_id" => $user_id,

      "user_email" => $user_email,
      "user_phone" => $user_phone,
      "user_telegram" => $user_telegram,
      "user_push_ids" => json_encode( $user_push_ids ),

      "send_email_sta" => $send_email_sta,
      "send_email_data" => $send_email_result,
      "send_telegram_sta" => $send_telegram_sta,
      "send_telegram_data" => $send_telegram_result,
      "send_push_sta" => $send_push_sta,
      "send_push_data" => $send_push_result,
      "send_sms_sta" => $send_phone_sta,
      "send_sms_data" => $send_phone_result,

      "object_type" => $object_type,
      "object_args" => json_encode( $object_args ),
      "object_event" => $object_event

    ));

  }

  public function send_push( $push_id, $title, $content, $args ){

    $color = null;
    $sound = null;
    $badge = null;
    $datas = null;
    $icon = null;
    extract( $args );

    $client = $this->getClient( "push" );
    if ( !$client ) return [ 0, "clientFailure" ];

    $notification = new \Fcm\Push\Notification();

    $notification
    ->addRecipient( $push_id )
    ->setTitle( $title )
    ->setBody( $content );

    if ( $color )
    $notification->setColor( $color );

    if ( $sound )
    $notification->setSound( $sound );

    if ( $badge )
    $notification->setBadge( $badge );

    if ( $datas )
    $notification->addDataArray( $datas );

    if ( $icon )
    $notification->setIcon( $icon );

    $response = $client->send( $notification );
    return [ 1, $response ];

  }

}

?>
