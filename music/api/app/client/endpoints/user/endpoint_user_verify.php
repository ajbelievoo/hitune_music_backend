<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_user_verify( $loader, $excuter, $args ){

  $userData = $loader->object->user->select(["ID"=>$loader->user->get()->ID]);
  $tabs = $loader->object->user_request->_get_tabs();

  if ( !$tabs ){
    $loader->api->set_error( "failed" );
    return;
  }

  $tab = $loader->nest->user_input( "get", "tab", "in_array", [ "values" => array_keys( $tabs ) ], array_keys( $tabs )[0] );
  $submit = $loader->nest->user_input( "get", "action", "equal", [ "value" => "submit" ] );

  if ( $submit ){
    
    $hasPendingRequest = bof()->object->user_request->select(
      array(
        "user_id" => $loader->user->get()->ID,
        "sta" => 0,
        "type" => $tabs[ $tab ]["type"]
      )
    );

    if ( $hasPendingRequest ){
      $loader->api->set_error( "pending_request" );
      return;
    }

    try {
      $validate = $loader->bofForm->validate( $tabs[ $tab ], true );
    } catch( Exception $err ){
      bof()->chapar->notify_admin("man_verify_failed", array(
        "error" => $err->getMessage()
      ));
      $loader->api->set_error( $err->getMessage(), [ "output_args" => [ "turn" => false ] ] );
      return;
    }

    $urid = $loader->object->user_request->insert(array(
      "type" => $tabs[ $tab ]["type"],
      "user_id" => $loader->user->get()->ID,
      "real_name" => $validate["real_name"],
      "extra_data" => json_encode( array(
        !empty( $validate["podcaster_name"] ) ? "podcaster_name" : "stage_name" => array(
          "type" => "text",
          "data" => !empty( $validate["podcaster_name"] ) ? $validate["podcaster_name"] : $validate["stage_name"]
        ),
        "document_id" => array(
          "type" => "file",
          "data" => $validate["document"]
        )
      ) ),
      "additional_data" => $validate["ad"],
    ));

    bof()->chapar->notify_admin("man_verify_requested", array(
      "type" => $tabs[$tab]["type"],
      "real_name" => $validate["real_name"],
      "additional_data" => $validate["ad"],
    ));

    $tn = substr( $tab, 0, 1 );
    if ( !empty( bof()->user->get()->extra["roles"]["verify_{$tn}_aa"] ) ){

      bof()->object->user_request->_approve( $urid );
      bof()->user->save_session();
      
      bof()->chapar->notify_admin("man_verify_approved", array(
        "type" => $tabs[$tab]["type"],
        "real_name" => $validate["real_name"],
        "additional_data" => $validate["ad"],
      ));

    }

    $loader->api->set_message( "request_submited" );

  }
  else {

    foreach( $tabs as $tabN => $tabV ){
      $tabs_titles[] = [ $tabN, bof()->object->language->turn( $tabN, [], [ "uc_first" => true, "lang" => "users" ] ) ];
    }

    $hasPendingRequest = bof()->object->user_request->select(
      array(
        "user_id" => $loader->user->get()->ID,
        "sta" => 0,
        "type" => $tabs[ $tab ]["type"]
      )
    );

    if ( $hasPendingRequest ){
      $loader->api->set_message( "ok", array(
        "tab" => $tab,
        "tabs" => $tabs_titles,
        "bofForm" => null,
        "html" => "<div class=\"form_text warning\"><div class=\"title\">".bof()->object->language->turn( "pending", [], [ "uc_first" => true, "lang" => "users" ] )."</div><div class=\"text\">".(bof()->object->language->turn( "pending_request", [], [ "uc_first" => true, "lang" => "users" ] ))."</div></div>",
        "seo" => array(
          "title" => bof()->object->language->turn( "verification", [], [ "uc_first" => true, "lang" => "users" ] )
        )
      ) );
      return;
    }

    try {
      $inputs_parsed = $loader->bofForm->parse( $tabs[ $tab ] );
    } catch( Exception $err ){
      $loader->api->set_error( "failed: " . $err->getMessage() );
      return;
    }

    $the_message = false;

    if ( $tab == "m_artist" ? $userData["s_managed_artists"] : ( $tab == "mb_producer" ? $userData["s_managed_producers"] : $userData["s_managed_podcasters"] ) ){
      $the_message = bof()->object->language->turn( "manager_help", array(
        "user_hash" => $userData["hash"],
        "web_address" => web_address,
        "icon" => "<span class='mdi mdi-open-in-new'></span>"
      ) );
    }


    $loader->api->set_message( "ok", array(
      "tab" => $tab,
      "tabs" => $tabs_titles,
      "bofForm" => $inputs_parsed,
      "the_message" => $the_message,
      "seo" => array(
        "title" => bof()->object->language->turn( "verification", [], [ "uc_first" => true, "lang" => "users" ] )
      )
    ) );

  }

}

?>
