<?php

if ( !defined( "bof_root" ) ) die;

class object_error_log extends bof_type_object {

  public function bof(){
    return array(
      "name" => "error_log",
      "label" => "Error Log",
      "icon" => "priority_high",
      "db_table_name" => "_bof_log_errors",
    );
  }
  public function columns(){
    return array(
      "severity" => array(
        "label" => "Severity",
        "validator" => array(
          "string"
        ),
        "bofAdmin" => array(
          "list" => array(
            "type" => "simple"
          )
        )
      ),
      "severity_name" => array(
        "label" => "severity_name",
        "validator" => array(
          "string"
        ),
      ),
      "file" => array(
        "label" => "File",
        "validator" => array(
          "string"
        ),
        "bofAdmin" => array(
          "list" => array(
            "type" => "simple",
            "renderer" => function( $displayItem, $item, $displayData ){
              $displayData["sub_data"] = "line: {$item["line"]}";
              return $displayData;
            },
          )
        )
      ),
      "line" => array(
        "label" => "Line",
        "validator" => array(
          "int"
        ),
      ),
      "message" => array(
        "label" => "Message",
        "validator" => array(
          "string"
        ),
        "bofAdmin" => array(
          "list" => array(
            "type" => "simple"
          )
        )
      ),
      "bof_version" => array(
        "label" => "BOF version",
        "validator" => array(
          "int"
        ),
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
    return array();
  }
  public function bof_admin(){

    return array(
      "config" => array(
        "search" => false,
        "create" => false,
        "edit" => false,
        "delete" => false,
        "pagination" => true,
        "edit_page_url" => "error_log",
        "list_page_url" => "error_logs",
        "multi" => array(
          "select" => false,
          "delete" => false,
          "edit"   => false
        )
      ),
    );
  }

}

?>
