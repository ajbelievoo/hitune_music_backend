<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_cancel_subs_plan( $loader, $excuter, $args ){

  $id = bof()->nest->user_input( "post", "id", "string", [ "strict" => true ] );
  if ( $id && bof()->object->db_setting->get( "gateway_stripe_subs" ) && bof()->object->db_setting->get( "gateway_stripe" ) ){

    $sub = bof()->object->user_subs->select(
      array(
        "user_id" => bof()->user->get()->ID,
        [ "gateway_time_recur", "NOT", null, true ],
        "gateway_sub_id" => $id
      ),
    );

    if ( $sub ){

      $try = bof()->pgt_stripe->cancel_sub( $sub["gateway_sub_id"] );

      if ( $try ){

        bof()->object->user_subs->update(
          array(
            "ID" => $sub["ID"]
          ),
          array(
            "time_expire" => bof()->general->mysql_timestamp(),
            "gateway_time_recur" => false,
          )
        );

        bof()->user->save_session();

        bof()->chapar->notify_admin("sub_canceled", array(
          "sub_id" => $sub["ID"]
        ));

      }

    }


  }
  $loader->api->set_message( "success" );

}

?>
