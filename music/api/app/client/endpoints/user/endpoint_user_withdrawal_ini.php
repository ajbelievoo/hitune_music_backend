<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_user_withdrawal_ini( $loader, $excuter, $args ){

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

  $funds = bof()->object->user->sid(bof()->user->check()->ID,["cache_load_rt"=>false])["fund"];
  $bofForm["tabs"][0][1] = bof()->object->language->turn( "withdrawal", [], [ "uc_first" => true, "lang" => "users" ] );
  $bofForm["bofForm"]["inputs"]["amount"]["label"] = bof()->object->language->turn( "amount", [], [ "uc_first" => true, "lang" => "users" ] );
  $bofForm["bofForm"]["inputs"]["amount"]["tip"] = bof()->object->language->turn( "your_funds", [], [ "uc_first" => true, "lang" => "users" ] ) . ": " . $funds;
  $bofForm["bofForm"]["inputs"]["amount"]["input"]["value"] = $funds;
  $bofForm["bofForm"]["inputs"]["receiver"]["label"] = bof()->object->language->turn( "withdraw_rec_label", [], [ "uc_first" => true, "lang" => "users" ] );
  $bofForm["bofForm"]["inputs"]["ad"]["label"] = bof()->object->language->turn( "additional_data", [], [ "uc_first" => true, "lang" => "users" ] );


  try {
    $inputs_parsed = $loader->bofForm->parse( $bofForm["bofForm"] );
  } catch( Exception $err ){
    $loader->api->set_error( "failed: " . $err->getMessage() );
    return;
  }

  $loader->api->set_message( "ok", array(
    "tab" => $bofForm["tab"],
    "tabs" => $bofForm["tabs"],
    "bofForm" => $inputs_parsed,
    "seo" => array(
      "title" => bof()->object->language->turn( "withdrawal", [], [ "uc_first" => true, "lang" => "users" ] )
    )
  ) );

}

?>
