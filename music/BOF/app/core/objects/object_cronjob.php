<?php

if ( !defined( "bof_root" ) ) die;

class object_cronjob extends bof_type_object {

  public function bof(){
    return array(
      "name" => "cronjob",
      "label" => "cronjob",
      "icon" => "robot",
      "db_table_name" => "_bof_log_cronjob_g",
    );
  }
  public function columns(){
    return array(
      "PID" => array(
        "label" => "Process<br>ID",
        "validator" => array(
          "int"
        ),
        "bofAdmin" => array(
          "list" => array(
            "type" => "simple"
          )
        )
      ),
      "code" => array(
        "validator" => array(
          "string",
        ),
      ),
      "title" => array(
        "label" => "Title",
        "validator" => array(
          "string",
          array(
            "strip_emoji" => false,
            "empty()"
          )
        ),
        "bofAdmin" => array(
          "list" => array(
            "type" => "simple"
          )
        )
      ),
      "detail" => array(
        "label" => "Detail",
        "validator" => array(
          "string",
          array(
            "strip_emoji" => false,
            "allow_eol" => true,
            "empty()"
          )
        ),
        "bofAdmin" => array(
          "list" => array(
            "type" => "simple",
            "renderer" => function( $displayItem, $item, $displayData ){
              if ( $displayData["data"] )
              $displayData["data"] = str_replace( "\r\n", "<BR>", $displayData["data"] );
              return $displayData;
            },
          )
        )
      ),
      "sta" => array(
        "label" => "Status",
        "validator" => array(
          "int",
          array(
            "min" => 0,
            "max" => 9,
            "forceZero" => true
          )
        ),
        "bofAdmin" => array(
          "list" => array(
            "type" => "tag",
            "renderer" => function( $displayItem, $item, $displayData ){
              if ( $item["sta"] == 0 ) $displayData["data"] = "failed";
              if ( $item["sta"] == 1 ) $displayData["data"] = "running";
              if ( $item["sta"] == 2 ) $displayData["data"] = "finished";
              if ( $item["sta"] == 3 ) $displayData["data"] = "skipped";
              return $displayData;
            },
          ),
          "filters" => array(
            "sta" => array(
              "title" => "Status",
              "input" => array(
                "type" => "select_i",
                "options" => array(
                  [ "__all__", "all" ],
                  [ "0", "Failed" ],
                  [ "1", "Running" ],
                  [ "2", "Finished" ],
                  [ "3", "Skipped" ],
                ),
                "value" => "__all__"
              ),
              "validator" => array(
                "in_array",
                array(
                  "values" => [ "0", "1", "2", "3" ]
                )
              )
            )
          )
        )
      ),
      "time_start" => array(
        "validator" => array(
          "timestamp",
          array(
            "empty()"
          ),
        ),
      ),
      "time_end" => array(
        "label" => "End<br>Time",
        "validator" => array(
          "timestamp",
          array(
            "empty()"
          ),
        ),
        "bofAdmin" => array(
          "list" => array(
            "type" => "time"
          )
        )
      ),
    );
  }
  public function bof_columns(){
    return array(
      "ID",
    );
  }
  public function selectors(){
    return array(
      "sta" => [ "sta", "=" ],
      "PID" => [ "PID", "=" ],
      "code" => [ "code", "=" ],
    );
  }
  public function bof_admin(){

    $jobs = bof()->cronjob->get_jobs();
    if ( !empty( $jobs ) ){
      foreach( $jobs as $c => $as ){
        $jobs_cs[ $c ] = [ $c, $as["title"] ];
      }
    }

    return array(
      "config" => array(
        "search" => false,
        "create" => false,
        "edit" => false,
        "delete" => false,
        "pagination" => true,
        "edit_page_url" => "cronjob",
        "list_page_url" => "cronjobs",
        "multi" => array(
          "select" => false,
          "delete" => false,
          "edit"   => false
        )
      ),
      "list" => array(
        "PID" => null,
        "title" => null,
        "detail" => null,
        "sta" => null,
        "button" => array(
          "type" => "simple",
          "label" => "Logs",
          "class" => "a_btn",
          "renderer" => function( $displayItem, $item, $displayData ){
            if ( $item["sta"] != 3 )
            $displayData["data"] = "<a class='btn btn-".($item["sta"]==2?"secondary":"primary")."' data-gid='{$item["ID"]}'>Logs</a>";
            return $displayData;
          },
        ),
        "time_end" => null
      ),
      "buttons" => array(
        "logs" => array(
          "id" => "logs",
          "label" => "Activate",
          "payload" => array(
            "post" => array(
              "__action" => "logs"
            )
          )
        ),
      ),
      "buttons_renderer" => function( $item, $buttons ){
        if ( $item["sta"] == 3 )
        unset( $buttons["logs"] );
        return $buttons;
      },
      "actions" => array(
        "logs" => function( $ids ){


          if ( !bof()->general->numeric( $ids ) )
          return;

          $logs = bof()->db->_select(array(
            "table" => "_bof_log_cronjob_p",
            "where" => array(
              [ "GID", "=", $ids ]
            ),
            "order_by" => "ID",
            "order" => "DESC",
            "limit" => 1000,
            "single" => false,
            "singular" => false
          ));

          return [ true, "loaded", array(
            "logs" => $logs
          ) ];

        }
      ),
      "filters" => array(
        "code" => array(
          "title" => "Type",
          "input" => array(
            "name" => "code",
            "type" => "select_i",
            "options" => array_merge(
              [ ["__all__", "All" ] ],
              array_values( $jobs_cs )
            ),
            "value" => "__all__"
          ),
          "validator" => array(
            "in_array",
            [ "values" => array_merge( array_keys( $jobs_cs ), [ "__all__" ] ) ]
          )
        ),

      )
    );
  }

  public function get_schedule( $name ){

    $get_setting = bof()->object->db_setting->get( "cd_{$name}", "d0,d1,d2,d3,d4,d5,d6" );
    return explode( ",", $get_setting );

  }
  public function get_schedule_sta( $name ){

    $get_data = $this->_bof_this->get_schedule( $name );
    $today = "d" . date("w");

    return in_array( $today, $get_data, true );

  }

}

?>
