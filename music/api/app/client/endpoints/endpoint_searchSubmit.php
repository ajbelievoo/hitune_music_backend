<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_searchSubmit( $loader, $excuter, $args ){

  $ot = bof()->nest->user_input( "post", "ot", "string", array(
    "strict" => true,
  ) );
  $hash = bof()->nest->user_input( "post", "hash", "md5" );
  $history = bof()->nest->user_input( "post", "history", "md5" );

  if ( !$ot || !$hash || !$history )
  return;

  if ( bof()->object->core_setting->get("search_index_type") != "inverted_indexing" )
  return;

  try {
    $item = bof()->object->__get( $ot )->shash( $hash );
    if ( !$item ) throw new Exception("nada");
  } catch( Exception|Error $err ){
    return;
  }

  bof()->db->_update(array(
    "table" => "_d_search_history",
    "set" => array(
      [ "target_object_type", $ot ],
      [ "target_object_id", $item["ID"] ],
      [ "time_redirect", bof()->general->mysql_timestamp() ]
    ),
    "where" => array(
      [ "hash", "=", $history ],
      [ "time_redirect", null, null, true ],
      [ "time_add", ">", "SUBDATE(now(), INTERVAL 5 MINUTE)", true ]
    )
  ));

  $loader->api->set_message( "ok" );

}

?>
