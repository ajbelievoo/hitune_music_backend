<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_bulk_importer( $loader, $excuter, $args ){

  $stas = array(
    1 => "pending",
    2 => "processing",
    7 => "invalid file",
    8 => "convert failed",
    9 => "import failed"
  );

  if ( !is_dir( base_root . "/files/bulk_importer" ) )
  mkdir( base_root . "/files/bulk_importer" );

  if ( !is_dir( base_root . "/files/bulk_importer_covers" ) )
  mkdir( base_root . "/files/bulk_importer_covers" );

  $sort = bof()->nest->user_input( "get", "sort", "in_array", [ "values" => [ "title", "album", "album_artist", "artist", "album_order", "time_release", "path_dir_name" ] ] );
  if ( $sort ){
    if ( $sort !== "path_dir_name" )
    $sort = "tag_{$sort}";
  } else {
    $sort = "ID";
  }

  $files = bof()->db->_select( array(
    "table" => "_bof_tool_bulk_importer_files",
    "limit" => false,
    "order_by" => $sort == "tag_album_order" ? "tag_album ASC, tag_album_order" : $sort,
    "order" => "ASC"
  ) );

  if ( $files ){
    $i=0;
    foreach( $files as &$file ){
      $file["i"] = $i;
      $file["sta_hr"] = $stas[ $file["sta"] ];
      $file["cover_address"] = web_address . "/files/bulk_importer_covers/{$file["cover"]}.png";
      $i++;
    }
  }

  $cover_input = bof()->bofInput->parse( array_merge( array(
    "input" => array(
      "type" => "bofInput",
      "name" => "cover_id"
    )
  ), bof()->object->parse_caller( "m_track" )->parsed->columns["cover_id"] ) );

  $loader->api->set_message( "ok", array(
    "files" => $files ? $files : false,
    "sort" => substr( $sort, 0, 4 ) == "tag_" ? substr( $sort, 4 ) : $sort,
    "dir_path" => bof()->object->file->clean_path( base_root . "/files/bulk_importer" ),
    "cover" => $cover_input["data"]["input"],
    "state" => bof()->object->db_setting->get( "bulk_import_state", 0 )
  ) );

}

?>
