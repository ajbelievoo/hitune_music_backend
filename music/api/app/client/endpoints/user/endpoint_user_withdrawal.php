<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_user_withdrawal( $loader, $excuter, $args ){

  $user = bof()->user->check();

  if ( empty( $user->extra["roles"]["withdraw"] ) && empty( array_intersect( $user->extra["role_ids"], array( "artist", "podcaster", "affiliate" ) ) ) )
  return;

  $bofForm = array(
    "tab" => "withdrawal",
    "tabs" => array(
      array(
        "withdrawal",
        "withdrawal"
      )
    ),
    "bofForm" => array(
      "ID" => "withdrawal",
      "becli" => array(
        "endpoint" => "user_withdrawal"
      ),
      "inputs" => array(
        "amount" => array(
          "required" => true,
          "input" => array(
            "type" => "digit",
            "name" => "amount",
          ),
          "validator" => array(
            "int",
            []
          )
        ),
        "receiver" => array(
          "required" => true,
          "input" => array(
            "type" => "text",
            "name" => "receiver",
          ),
          "validator" => array(
            "string",
            []
          )
        ),
        "ad" => array(
          "input" => array(
            "type" => "textarea",
            "name" => "ad"
          ),
          "validator" => array(
            "string",
            []
          )
        ),
      )
    )
  );

  try {
    $validate = $loader->bofForm->validate( $bofForm["bofForm"], true );
  } catch( Exception $err ){
    $loader->api->set_error( $err->getMessage(), [ "output_args" => [ "turn" => false ] ] );
    bof()->chapar->notify_admin("withdraw_failed", array(
      "error" => $err->getMessage()
		));
    return;
  }

  $user_funds = bof()->object->user->sid($user->ID,["cache_load_rt"=>false,"cache_load"=>false,"cache"=>false,"cache_save"=>false,"bof_cache"=>false])["fund"];

  if ( $validate["amount"] > $user_funds ){
    $loader->api->set_error( "insufficient_fund" );
    bof()->chapar->notify_admin("withdraw_failed", array(
      "error" => "insufficient funds"
		));
    return;
  }

  $withdrawID = bof()->object->user_withdraw->insert( array(
    "user_id" => $user->ID,
    "amount" => $validate["amount"],
    "receiver" => $validate["receiver"],
    "additional_data" => $validate["ad"]
  ) );

  $transaction = bof()->object->user->remove_fund(
    $user->ID,
    $validate["amount"],
    array(
      "type" => "withdraw",
      "object_type" => "user_withdraw",
      "object_id" => $withdrawID,
      "revenue" => 0
    )
  );

  bof()->chapar->notify_admin("withdraw_ok", array(
    "amount" => $validate["amount"],
    "receiver" => $validate["receiver"]
  ));

  $loader->api->set_message( "submitted" );

}

?>
