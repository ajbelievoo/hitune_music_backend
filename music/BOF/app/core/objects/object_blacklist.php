<?php

if ( !defined( "bof_root" ) ) die;

class object_blacklist extends bof_type_object {

  public function bof(){
    return array(
      "name" => "blacklist",
      "label" => "Blacklist",
      "icon" => "block",
      "db_table_name" => "_bof_blacklist"
    );
  }
  public function columns(){
    return array(
      "object_type" => array(
        "label" => "Object type",
        "validator" => "string",
        "bofAdmin" => array(
          "list" => array(
            "type" => "tag"
          )
        )
      ),
      "code" => array(
        "label" => "Code",
        "validator" => "string",
        "bofAdmin" => array(
          "list" => array(
            "type" => "simple"
          )
        )
      ),
    );
  }
  public function bof_columns(){
    return array(
      "ID",
      "time_add"
    );
  }
  public function selectors(){
    return array(
      "object_type" => [ "object_type", "=" ],
      "code" => [ "code", "=" ],
    );
  }

  public function bof_admin(){
    return array(
      "config" => array(
        "search" => true,
        "create" => false,
        "edit" => false,
        "delete" => true,
        "pagination" => true,
        "edit_page_url" => "blacklist",
        "list_page_url" => "blacklists",
        "multi" => array(
          "select" => true,
          "delete" => true,
          "edit"   => false
        )
      ),
    );
  }

  public function select( $whereArgs=[], $selectArgs=[] ){

    return bof()->object->_select(
      $this,
      $whereArgs,
      $selectArgs
    );

  }


}

?>
