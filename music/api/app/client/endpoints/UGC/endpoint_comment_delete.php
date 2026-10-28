<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_comment_delete( $loader, $excuter, $args ){

  $userID = $loader->user->check()->ID;
  if ( !$userID ) return;

  $comment_id = $loader->nest->user_input( "post", "comment_id", "int", [ "min" => 1 ] );
  if ( !$comment_id )
  $comment_id = $loader->nest->user_input( "post", "id", "int", [ "min" => 1 ] );

  if ( !$comment_id ) return;

  $comment = $loader->db->_select( array(
    "table" => "_u_comments",
    "where" => array(
      [ "ID", "=", $comment_id ]
    ),
    "single" => true
  ) );

  if ( !$comment || $comment["user_id"] != $userID ){
    $loader->api->set_error( "no_access" );
    return;
  }

  $loader->db->_delete( array(
    "table" => "_u_comments",
    "where" => array(
      [ "ID", "=", $comment_id ],
      [ "user_id", "=", $userID ]
    )
  ) );

  // decrement s_comments counter on the target object when the column exists
  try {
    $the_object = $loader->object->__get( $comment["object_name"] );
    $table = $the_object ? $the_object->bof()["db_table_name"] : null;
    if ( $table )
    $loader->db->query( "UPDATE `{$table}` SET s_comments = GREATEST( s_comments - 1, 0 ) WHERE ID = " . intval( $comment["object_id"] ) . " AND s_comments IS NOT NULL" );
  } catch( Exception $err ){}

  $loader->api->set_message( "deleted" );

}

?>
