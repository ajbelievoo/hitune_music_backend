<?php

if ( !defined( "bof_root" ) ) die;

class object_bi_item extends bof_type_object {

  // BusyOwlFramework handshake
  public function bof(){
    return array(
      "name" => "bi_item",
      "label" => "BulkImporter Item",
      "db_table_name" => "_bof_tool_bulk_importer_files",
    );
  }
  public function columns(){
    return array(
    );
  }
  public function bof_columns(){
    return array(
      "ID",
      "time_add",
    );
  }
  public function selectors(){
    return array(
    );
  }

}

?>
