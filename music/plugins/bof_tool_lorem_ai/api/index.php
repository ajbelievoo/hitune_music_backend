<?php

require_once( dirname(dirname(dirname(dirname(__FILE__))))."/api/app/config.php" );

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: *");

require_once( bof_root . "/loader.php" );

$bof_instance = new BusyOwlFramework(array(
  "name" => "lorem_ai_api",
  "plugins" => array()
));

function bof(){
  global $bof_instance;
  return $bof_instance;
}

bof()->__setup();
require_once( root . "/app/admin/_setup/db.php" );

foreach( [ "bof_radio", "bof_audiobook", "bof_podcast", "bof_music", "bof_tool_lorem_ai" ] as $plugin ){
  $handshake_file = plugins_root . "/{$plugin}/_handshake.php";
  if ( !is_file( $handshake_file ) ) continue;
  bof()->plugin( $plugin, array(
    "handshake_file" => $handshake_file
  ));
}

if ( !bof()->plug->activated_plugins() ? true : !in_array( "bof_tool_lorem_ai", bof()->plug->activated_plugins(), true ) )
die("lorem_ai is not activated");

if ( !bof()->object->db_setting->get( "lorem_ai_api" ) )
die("lorem_ai api is not activated");

if ( !( $auth = bof()->nest->user_input( "http_header", "auth", "string" ) ) ||
!( $auth2 = bof()->nest->user_input( "http_header", "auth2", "string" ) ) )
die("auth failed (1)");

if ( $auth != bof()->object->db_setting->get( "lorem_ai_api_x" ) ||
$auth2 != bof()->object->db_setting->get( "lorem_ai_api_xx" ) )
die("auth failed (2)");

$object = bof()->nest->user_input( "post", "object", "string" );

bof()->general->set_full_fall(false);

try {
  $so_self = bof()->object->__get( $object );
} catch( Exception|bofException $err ){
  die("invalid req (1)");
}

if ( !in_array( $object, array_keys( bof()->lorem_ai->get_supported_objects() ), true ) )
die("invalid req(2)");

$lorem_object = bof()->lorem_ai->parse_object( $object );
$action = bof()->nest->user_input( "post", "action", "in_array", [ "values" => [ "get", "submit", "sample" ] ] );

if ( !$action )
die("invalid req(3)");

if ( $action == "get" ){

  $items = bof()->object->__get( $object )->select(
    array_merge(
      $lorem_object["user_filters"] ? $lorem_object["user_filters"] : [],
      array(
        [ "ID", "NOT IN", "SELECT item_id FROM _bof_lorem_ai_cache WHERE object_name = '{$object}'", true ]
      )
    ),
    array(
      "empty_select" => true,
      "limit" => 1,
      "clean" => false,
      "single" => true
    )
  );
  
  if ( !$items )
  die( "nada" );
  
  $items = array(
    "ID" => $items["ID"],
    "query" => $lorem_object["args"]["query_function"]( $items )
  );

  die( json_encode( $items ) );
  
}
elseif ( $action == "submit" ){

  $ID = bof()->nest->user_input( "post", "ID", "int" );
  $file = bof()->nest->user_input( "file", "file", "file", array(
    "acceptable_extensions" => ["png"]
  ) );

  if ( !$ID || !$file )
  die( "invalid req (6)" );

  try {
    $handle_url = bof()->object->file->handle_string( file_get_contents( $file["tmp_name"] ), array(
      "object_type" => $object
    ) );
  } catch( Exception $err ){
    die( "invalid req (7)" );
  }

  if ( $handle_url[0] ? !empty( $handle_url[1]["file_id"] ) : false ){

    $file_id = $handle_url[1]["file_id"];
    $finalize = bof()->object->file->finalize_upload(
      "image",
      $object,
      "{$object}{$ID}",
      $file_id
    );

    if ( $file_id && $finalize ){
      bof()->object->__get( $object )->update(
        array(
          "ID" => $ID
        ),
        array(
          "cover_id" => $file_id
        ),
      );
    }

    bof()->db->_insert(array(
      "table" => "_bof_lorem_ai_cache",
      "set" => array(
        [ "object_name", $object ],
        [ "item_id", $ID ],
        [ "sta", 1 ]
      )
    ));

    die(json_encode(["ok"=>"ok"]));

  }

  die("failled");

}

?>
