<?php

if ( !defined( "bof_root" ) ) die;

/**
 * be/developer_settings — view/update developer-program settings (admin).
 * POST (any): auto_approve, default_plan, portal_enabled, tos
 * Always returns the full current settings block.
 */

function endpoint_be_developer_settings( $loader, $excuter, $args ){

  $db_setting = $loader->object->db_setting;

  if ( isset( $_POST["auto_approve"] ) )
    $db_setting->set( "developer_auto_approve", $loader->nest->user_input( "post", "auto_approve", "boolean" ) ? 1 : 0, "digit" );

  if ( isset( $_POST["portal_enabled"] ) )
    $db_setting->set( "developer_portal_enabled", $loader->nest->user_input( "post", "portal_enabled", "boolean" ) ? 1 : 0, "digit" );

  if ( isset( $_POST["default_plan"] ) ){
    $plan = $loader->developer_api->plan( $loader->nest->user_input( "post", "default_plan", "string_abcd" ) );
    if ( $plan ) $db_setting->set( "developer_default_plan", $plan["plan_key"], "text" );
  }

  if ( isset( $_POST["tos"] ) )
    $db_setting->set( "developer_tos", $loader->nest->user_input( "post", "tos", "purify" ), "purify" );

  $loader->api->set_message( "ok", array(
    "settings" => array(
      "auto_approve" => $loader->developer_api->auto_approve(),
      "default_plan" => $loader->developer_api->setting( "default_plan", "sandbox" ),
      "portal_enabled" => $loader->developer_api->portal_enabled(),
      "tos" => $loader->developer_api->tos()
    )
  ) );

}

?>
