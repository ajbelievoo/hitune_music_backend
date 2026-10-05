<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_muse_record( $loader, $excuter, $args ){

  $object_name = $loader->nest->user_input( "post", "object_type", "bofClient_object", [ "has_button" => "source" ] );
  $object_hash = $loader->nest->user_input( "post", "object_hash", "md5" );

  if ( !$object_name ? $loader->nest->user_input( "post", "object_type", "equal", [ "value" => "a_book" ] ) : false )
  $object_name = "a_book"; 

  if ( $object_name && $object_hash ){

    $object_item = $loader->object->__get( $object_name )->select(array(
      "hash" => $object_hash
    ));

    if ( $object_item ){

      // Artist Panel analytics: every play (guests too) with country/city
      if ( $object_name === "m_track" ){
        try {
          require_once( dirname( __FILE__ ) . "/../dist/play_log.php" );
          htx_log_play( $loader, $object_item, $loader->user->get()->ID ? (int)$loader->user->get()->ID : null );
        } catch ( \Throwable $e ) {}
      }

      $unique_play = false;
      if ( $loader->user->get()->ID ){

        $ugc_action_array = array(
          "user_id" => $loader->user->get()->ID,
          "type" => "stream",
          "object_name" => $object_name,
          "object_id" => $object_item["ID"],
        );

        $heard = $loader->object->ugc_action->select( $ugc_action_array );
        $loader->object->ugc_action->insert( $ugc_action_array );

        if ( !$heard )
        $unique_play = true;

      }

      $loader->object->__get( $object_name )->update(
        array(
          "ID" => $object_item["ID"]
        ),
        array(
          "s_plays" => $object_item["s_plays"] + 1,
          "s_plays_unique" => $object_item["s_plays_unique"] + ( $unique_play ? 1 : 0 )
        )
      );

    }

  }

  $loader->api->set_message("ok");

}

?>
