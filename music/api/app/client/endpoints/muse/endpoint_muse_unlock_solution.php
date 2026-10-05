<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_muse_unlock_solution( $loader, $excuter, $args ){

  $object_name = $loader->nest->user_input( "post", "object_type", "bofClient_object", [ "has_button" => "purchase" ] );
  $object_hash = $loader->nest->user_input( "post", "object_hook", "md5" );
  $source_type = $loader->nest->user_input( "post", "source_type", "in_array", [ "values" => $loader->source->get_supported( "stream" )["types"] ] );
  $source_hook = $loader->nest->user_input( "post", "source_hook", "string" );
  $source_id   = $loader->nest->user_input( "post", "source_id", "md5" );

  $subs_purchasable = [];

  if ( !$object_name || !$object_hash )
  return;

  $the_object = $loader->object->__get( $object_name );
  $item = $the_object->select( [ "hash" => $object_hash ], [ "purchase" => true, "purchase_check" => true, "endpoint" => "muse_unlock_solution", "_eq" => [ "cover" => [] ] ] );

  if ( !$item )
  return;

  $item_property_access = $loader->object->ugc_property->owned( $object_name, $item );

  if ( !$source_hook ){
    $source_access = true;
  }
  else {
    $source_access = in_array( $source_hook, $loader->user->get()->extra["roles"]["player"], true );
    if ( !$source_access && $item_property_access["purchasable"] )
    $item_property_access["purchasable"] = false;
  }

  if ( ( $item_property_access["purchasable"] || $source_hook ) && empty( $item_property_access["purchasable"]["price_setting_decoded"]["disable_subs"] ) ){

    $subs_plans = $loader->object->user_subs_plan->select(["active"=>1],["limit"=>false,"single"=>false,"_eq"=>["user_role"=>[]]]);
    if ( $subs_plans ){
      foreach( $subs_plans as $subs_plan ){
        $parse_subs_plan = $loader->object->user_role->parse_user_roles( [ $subs_plan["bof_dir_user_role"] ], true );
        if ( $parse_subs_plan ){
          $access_by_subs_plan = $loader->object->user_role->has_access( $parse_subs_plan, array(
            "object_item" => $item,
            "object_name" => $object_name,
            "object_hash" => $object_hash,
            "source_type" => $source_type,
            "source_hook" => $source_hook,
            "source_id"   => $source_id
          ) );
          if ( $access_by_subs_plan ){
            $subs_purchasable[] = $subs_plan;
          }
        }
      }
    }

  }

  if ( !$item_property_access["purchasable"] && empty( $subs_purchasable ) && empty( $source_hook ) )
  return;

  if ( $item_property_access["purchasable"] ){
    $item_property_access["purchasable"] = array(
      "ot" => $item_property_access["purchasable"]["ot"],
      "on" => $loader->object->language->turn( $item_property_access["purchasable"]["ot"], [], [ "uc_first" => true, "lang" => "users" ] ),
      "hash" => $item_property_access["purchasable"]["hash"],
      "name" => !empty( $item_property_access["purchasable"]["title"] ) ? $item_property_access["purchasable"]["title"] : $item_property_access["purchasable"]["name"],
      "price" => $item_property_access["purchasable"]["price_hr"],
      "cover" => !empty( $item_property_access["purchasable"]["bof_file_cover"]["image_strings"] ) ? $item_property_access["purchasable"]["bof_file_cover"]["image_strings"][12]["html"] : ""
    );
  }

  if ( !empty( $subs_purchasable ) ){
    foreach( $subs_purchasable as &$subs_purchasable_i ){
      $subs_purchasable_i = array(
        "ot" => "user_subs_plan",
        "hash" => $subs_purchasable_i["hash"],
        "name" => $subs_purchasable_i["name"],
        "price" => $subs_purchasable_i["_prices"]["min"],
        "cover" => ""
      );
    }
  }

  $loader->api->set_message( "ok", array(
    "output_args" => array(
      "turn" => false
    ),
    "items" => !empty( $item_property_access["purchasable"] ) ? array(
      $item_property_access["purchasable"]
    ) : false,
    "subs_plans" => !empty( $subs_purchasable ) ? $subs_purchasable : false,
    "title" => $loader->object->language->turn( "purchase", [], array(
      "uc_first" => true,
      "lang" => "users"
    ) ),
  ) );

}

?>
