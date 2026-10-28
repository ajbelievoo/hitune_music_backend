<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_comment_add( $loader, $excuter, $args ){

  $userID = $loader->user->check()->ID;
  if ( !$userID ) return;

  $object_name = $loader->nest->user_input( "post", "object_type", "bofClient_object" );
  $object_hash = $loader->nest->user_input( "post", "object_hash", "md5" );
  if ( !$object_hash )
  $object_hash = $loader->nest->user_input( "post", "object", "md5" );
  if ( !$object_hash )
  $object_hash = $loader->nest->user_input( "post", "hash", "md5" );
  $text = $loader->nest->user_input( "post", "text", "string", [ "strip_emoji" => false, "min" => 1, "max" => 2000 ] );

  if ( !$object_name || !$object_hash || !$text ){
    if ( !$text ) $loader->api->set_error( "text_cant_be_empty" );
    return;
  }

  $the_object = $loader->object->__get( $object_name );
  $object_item = $the_object->select(
    array( "hash" => $object_hash ),
    array( "clean" => false )
  );

  if ( !$object_item ) return;

  $insert = $loader->db->_insert( array(
    "table" => "_u_comments",
    "set" => array(
      [ "hash", md5( $userID . $object_name . $object_item["ID"] . microtime( true ) ) ],
      [ "user_id", $userID ],
      [ "object_name", $object_name ],
      [ "object_id", $object_item["ID"] ],
      [ "text", $text ]
    )
  ) );

  // bump s_comments counter on the target object when the column exists
  try {
    $table = $the_object->bof()["db_table_name"];
    if ( $table )
    $loader->db->query( "UPDATE `{$table}` SET s_comments = s_comments + 1 WHERE ID = " . intval( $object_item["ID"] ) . " AND s_comments IS NOT NULL" );
  } catch( Exception $err ){}

  $author = $loader->object->user->sid(
    $userID,
    array(
      "_eq" => array( "avatar" => [] )
    )
  );

  $loader->api->set_message( "ok", array(
    "comment" => array(
      "id" => $insert ? intval( $insert ) : null,
      "author" => $author ? ( $author["name"] ? $author["name"] : $author["username"] ) : null,
      "avatar" => !empty( $author["bof_file_avatar"]["image_thumb"] ) ? $author["bof_file_avatar"]["image_thumb"] : null,
      "text" => $text,
      "likes" => 0,
      "mine" => 1,
      "created_at" => time()
    )
  ) );

}

?>
