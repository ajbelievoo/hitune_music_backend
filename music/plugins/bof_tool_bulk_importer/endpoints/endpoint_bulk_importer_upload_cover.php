<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_bulk_importer_upload_cover( $loader, $excuter, $args ){

  $chunk = bof()->object->db_setting->get( "fs_chunk" );
  $chunk_size = bof()->object->db_setting->get( "fs_chunk_size" );
  $rules = bof()->object->file->get_rules( "image", "m_album_c" );

  $handle_upload = bof()->object->file->handle_dropzone_upload( array(
    "extensions" => $rules["validators"]["fl"],
    "min_size"   => $rules["validators"]["size_min"] * 1000 * 1000,
    "max_size"   => $rules["validators"]["size_max"] * 1000 * 1000,
    "accept"     => $chunk ? "both" : "uncut",
    "chunk_size" => $chunk_size * 1000 * 1000
  ) );

  if ( !$handle_upload[0] )
  return;

  if ( $handle_upload[0] ? $handle_upload[1] == "uploaded_chunk" : false ){
    $loader->api->set_message( "ok" );
    return;
  }

  $handle_upload = base_root . $handle_upload[1];

  // Validate per type && rules
  $valid = bof()->object->file->validate_file( "image", $handle_upload, $rules );
  if ( $valid !== true ){
    return;
  }

  $newName = uniqid();

  bof()->image->set( $handle_upload )->save( array(
    "save_ext" => "png",
    "force_ext" => "png",
    "path" => base_root . "/files/bulk_importer_covers/{$newName}.png"
  ) );

  @unlink( $handle_upload );

  $loader->api->set_message( "ok", array(
    "newFile" => $newName
  ) );

}

?>
