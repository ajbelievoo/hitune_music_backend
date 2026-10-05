<?php

function comparator_url( $args, $endpoint, $nest ){

  $equal = null;
  $startsWith = null;
  $dynamic = null;
  $dynamic_db = null;
  $dynamic_prefix = null;
  $dynamic_table = null;
  $dynamic_where_column = null;
  $dynamic_select_column = null;
  $predefined_pages = [];
  $function = null;
  $regex = null;
  extract( $args );
  $function = $function && gettype( $function ) == "object" ? $function : false;

  $url = bof()->request->get_requested_url();

  if ( $equal ){
    if ( $url == $equal )
    return true;
    return false;
  }

  if ( $startsWith ){
    if ( substr( $url, 0, strlen( $startsWith ) ) == $startsWith )
    return true;
    return false;
  }

  if ( $predefined_pages ){
    if ( in_array( $url, array_keys( $predefined_pages ), true ) )
    return $predefined_pages[ $url ];
    return false;
  }

  if ( $regex ){
    if ( $nest->validate( $url, "string", array(
      "strict" => true,
      "strict_regex" => $regex,
      "strict_regex_raw" => true,
      "strip_emoji" => false,
      "only_utf8" => false
    ) ) )
    return $function ? $function( $url ) : true;
    return false;
  }

  if ( $function ){
    return $function( $url );
  }

  if ( $dynamic === true && $dynamic_db === true ){

    if ( !$nest->validate( $dynamic_table, "string_abcd" ) ) fall("invalid dynamic_table: {$dynamic_table}");
    if ( !$nest->validate( $dynamic_where_column, "string_abcd" ) ) fall("invalid dynamic_where_column: {$dynamic_where_column}");
    if ( !$nest->validate( $dynamic_select_column, "string_abcd" ) ) fall("invalid dynamic_select_column: {$dynamic_select_column}");
    if ( $dynamic_prefix ? mb_substr( $url, 0, mb_strlen( $dynamic_prefix, "utf-8" ), "utf-8" ) != $dynamic_prefix : false ) return false;

    $_url = $dynamic_prefix ? mb_substr( $url, mb_strlen( $dynamic_prefix ), mb_strlen( $url ), "utf-8" ) : $url;

    // search table
    if ( ( $table_id = bof()->db->_select( array(
      "table" => $dynamic_table,
      "where" => [
        [ $dynamic_where_column, "=", $_url ]
      ],
      "columns" => $dynamic_select_column,
      "limit" => 1,
      "single" => true
    ) ) ) ) return $table_id[ $dynamic_select_column ];

  }

  return false;

}

?>
