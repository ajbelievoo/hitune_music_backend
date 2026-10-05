<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_bulk_importer_mark( $loader, $excuter, $args ){

  $ids = bof()->nest->user_input( "post", "ids", "int_imploded" );
  $type = "music";

  if ( !$ids || !$type )
  return;

  $files = bof()->db->_select(array(
    "table" => "_bof_tool_bulk_importer_files",
    "where" => array(
      [ "ID", "IN", $ids, true ],
      [ "sta", "=", "1" ],
      [ "mp3", "=", "1" ]
    ),
    "limit" => false,
    "single" => false
  ));

  if ( !$files )
  return;

  $stats = $oks = [];

  foreach( $files as $file ){
    $getFileErrors = bof()->bulk_importer->validate_file( $file );
    if ( empty( $getFileErrors ) ){
      bof()->db->_update(array(
        "table" => "_bof_tool_bulk_importer_files",
        "set" => array(
          [ "sta", "2" ]
        ),
        "where" => array(
          [ "ID", "=", $file["ID"] ],
        )
      ));
      $stats[] = "<b>{$file["path_name"]}</b> <span class='_ee ss'>successfully</span> marked for import";
      $oks[] = $file["ID"];
    } else {
      $stats[] = "<b>{$file["path_name"]}</b> <span class='_ee ff'>failed</span> for import. Errors:<br><span class='_es'>" . ( implode( "<br>", $getFileErrors ) ) . "</span>";
    }
  }

  $loader->api->set_message( "ok", array(
    "stats" => $stats ? "<div class='_st'>" . implode( "</div><div class='_st'>", $stats ) . "</div>" : "",
    "oks" => $oks
  ) );

}

?>
