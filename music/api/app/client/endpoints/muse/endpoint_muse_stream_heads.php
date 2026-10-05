<?php

if ( !defined( "bof_root" ) ) die;

function getMp3StreamTitle( $streamingUrl, $metaint, $tries=1 ){

  $stream = fopen( $streamingUrl, 'r', false, stream_context_create( array(
    'http' => array(
      'method' => 'GET',
      'header' => 'Icy-MetaData: 1',
      'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
    )
  ) ) );

  if ( !$stream )
  return;

  if ( $tries > 5 )
  return;

  $buffer = stream_get_contents( $stream, $metaint, ($tries*$metaint)+1 );
  fclose( $stream );

  if ( strpos( $buffer, 'StreamTitle=' ) !== false ){
    $title = explode( 'StreamTitle=', $buffer )[1];
    $title = html_entity_decode($title, ENT_QUOTES, 'UTF-8');
    $title = substr( $title, 1, strpos($title, ';') - 2 );
    $title = str_replace( [ "+", "  " ], [ " ", " " ], $title );
    return $title;
  }

  return getMp3StreamTitle( $streamingUrl, $metaint, $tries+1 );

}

function endpoint_muse_stream_heads( $loader, $excuter, $args ){

  $object = bof()->nest->user_input( "post", "object", "in_array", [ "values" => [ "r_station" ] ] );
  $hash = bof()->nest->user_input( "post", "hash", "md5" );
  $ID = bof()->nest->user_input( "post", "ID", "md5" );

  if ( !$object || !$hash || !$ID )
  return;

  $item = bof()->object->__get( $object )->select( [ "hash" => $hash ], array(
    "_eq" => array(
      "sources" => array(
        "limit" => false
      )
    )
  ) );

  if ( !$item ? true : empty( $item["bof_dir_sources"] ) )
  return;

  if ( $item["icy_time"] ? bof()->general->timestamp_difference( $item["icy_time"], "-", 60 ) : false ){
    if ( empty( trim( $item["icy_title"] ) ) ) return;
    $loader->api->set_message( "ok", array(
      "name" => $item["title"],
      "desc" => htmlspecialchars_decode( trim( $item["icy_title"] ), ENT_QUOTES )
    ) );
    return;
  }

  foreach( $item["bof_dir_sources"] as $source ){
    if ( $source["hash"] === $ID ){
      
      if ( !empty( $source["data_decoded"]["remote_address"] ) ){

        bof()->object->r_station->update(
          array(
            "ID" => $item["ID"]
          ),
          array(
            "icy_time" => bof()->general->mysql_timestamp()
          )
        );

        $curl = bof()->curl->exe(array(
          "url" => $source["data_decoded"]["remote_address"],
          "type" => "stream",
          "agent" => "chrome",
          "nobody" => true,
          "headers" => array(
            "Icy-MetaData: 1"
          ),
          "ctimeout" => 5,
          "timeout" => 10
        ));

        if ( $curl["header"] ){

          foreach( bof()->general->explode_by_line( $curl["header"] ) as $hLine ){
            if ( bof()->general->startsWith( strtolower( $hLine ), "icy-metaint:" ) )
            $metaint = trim( substr( $hLine, strlen("icy-metaint:") ) );
          }

          if ( empty( $metaint ) )
          $metaint= 8192;

          $data = getMp3StreamTitle( $source["data_decoded"]["remote_address"], $metaint );
          if ( $data ){
            $loader->api->set_message( "ok", array(
              "name" => $item["title"],
              "desc" => $data
            ) );
            bof()->object->r_station->update(
              array(
                "ID" => $item["ID"]
              ),
              array(
                "icy_title" => $data,
                "icy_time" => bof()->general->mysql_timestamp()
              )
            );
            return;
          } else {
            bof()->object->r_station->update(
              array(
                "ID" => $item["ID"]
              ),
              array(
                "icy_title" => " ",
                "icy_time" => bof()->general->mysql_timestamp()
              )
            );
          }

        }

      }
      break;
    }
  }

}

?>
