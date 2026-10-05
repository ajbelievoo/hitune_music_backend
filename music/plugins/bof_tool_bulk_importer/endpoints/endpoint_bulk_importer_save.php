<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_bulk_importer_save( $loader, $excuter, $args ){

  $ids = bof()->nest->user_input( "post", "ids", "int_imploded" );

  if ( !$ids )
  return;

  $requiredInputs = array(
    'title', 'artist', 'album', 'album_artist', 'genres', 'tags', 'ft_artists', "langs",
    'album_order' => array(
      'int',
      array(
        'empty()',
        'forceNull' => true,
      )
    ),
    'cd_order' => array(
      'int',
      array(
        'empty()',
        'forceNull' => true,
      )
    ),
    'album_type' => array(
      'in_array',
      array(
        'values' => [ 'mixtape', 'single', 'studio', 'compilation' ]
      )
    ),
    'time_release' => array(
      'timestamp',
      []
    ),
    'cover' => array(
      'string',
      array(
        'empty()'
      )
    )
  );

  $newTags = [];

  foreach( $requiredInputs as $_k => $_v ){

    $inputName = is_int( $_k ) ? $_v : $_k;
    $validator = is_int( $_k ) ? array(
      'string',
      array(
        'strip_emoji' => false
      )
    ) : $_v;

    if ( empty( $_POST["{$inputName}_active"] ) )
    continue;

    $givenValue = bof()->nest->user_input( "post", $inputName, $validator[0], $validator[1] );

    $newTags[ $inputName ] = $givenValue;
    if ( $inputName == "cover" ){
      $setArray[] = [ "cover", $givenValue ];
      $setArray[] = [ "cover_hash", md5_file( base_root . "/files/bulk_importer_covers/{$givenValue}.png" ) ];
    } else {
      $setArray[] = [ "tag_{$inputName}", $givenValue ];
    }

  }

  if ( empty( $setArray ) )
  return;

  bof()->db->_update(array(
    "table" => "_bof_tool_bulk_importer_files",
    "set" => $setArray,
    "where" => array(
      [ "ID", "IN", $ids, true ]
    )
  ));

  $loader->api->set_message( "ok", array(
    "tags" => $newTags
  ) );

}

?>
