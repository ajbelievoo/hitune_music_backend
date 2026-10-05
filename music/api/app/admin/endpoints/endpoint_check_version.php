<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_check_version( $loader, $excuter, $args ){

  $extensions = $loader->plug->list( [ "plugin", "tool", "theme", "script" ], true, true );
  $latest_version = $extensions["script_version"];
  $ups = [];

  if ( $latest_version > version )
  $ups[] = array(
    "icon" => "celebration",
    "title" => "Framework update!",
    "text" => "RKHM version <b>" .  ( substr( $latest_version, 0, 1 ) . "." . substr( $latest_version, 1, 1 ) . "." . substr( $latest_version, 2, 2 ) ) . " available!</b>",
    "buttons" => array(
      [ "primary", "Update", "extension/self&do=update" ],
      [ "secondary", "Changelog", "https://support.busyowl.co/changelog" ],
    )
  );

  if ( $extensions["list"] ){
    foreach ( $extensions["list"] as $extension_code => $extension ){

      if ( empty( $extension["installed"] ) ) continue;
      $extension_local = bof()->plug->read( $extension["type"], $extension_code );
      if ( empty( $extension_local["version"] ) ) continue;

      if ( $extension_local["version"] < $extension["version"] )
      $ups[] = array(
        "icon" => "celebration",
        "title" => ucfirst( $extension["type"] ) . " update!",
        "text" => "{$extension["name"]} version <b>" .  ( substr( $extension["version"], 0, 1 ) . "." . substr( $extension["version"], 1, 1 ) . "." . substr( $extension["version"], 2, 2 ) ) . "</b> available!",
        "buttons" => array(
          [ "primary", "Update", "extension/{$extension_code}&do=update" ],
          [ "secondary", "Changelog", "https://support.busyowl.co/changelog" ],
        )
      );

    }
  }

  if ( !production )
  $ups[] = array(
    "icon" => "dangerous",
    "title" => "Development mode",
    "text" => "RKHM is in <b>development</b> mode right now. Most of caching functionalities are disabled, minified version of css/js files are not being used & script produces more logs/errors. Make sure you put script into <b>production</b> mode before going live!",
    "buttons" => array(
      [ "secondary", "Docs", "https://support.busyowl.co/documentation/setup101" ],
    )
  );

  $loader->api->set_message( "ok", array(
    "has_update" => !empty( $ups ),
    "ups" => $ups
  ) );

}

?>
