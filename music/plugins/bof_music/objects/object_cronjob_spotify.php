<?php

if ( !defined( "bof_root" ) ) die;

class object_m_cronjob_spotify extends bof_type_object {

  // BusyOwlFramework handshake
  public function bof(){
    return array(
      "name" => "m_cronjob_spotify",
      "label" => "Music Cronjob - Playlist",
      "icon" => "precision_manufacturing",
      "db_table_name" => "_c_m_cronjobs_spotify",
      "db_empty_select" => true,
    );
  }
  public function columns(){
    return array(
      "cron_id" => array(
        "validator" => array(
          "int",
          []
        ),
      ),
      "spotify_id" => array(
        "validator" => array(
          "string",
          []
        ),
      ),
      "local_id" => array(
        "validator" => array(
          "int",
          array(
            "empty()",
            "forceNull" => true,
            "forceZero" => true,
          )
        )
      ),
      "time_check" => array(
        "validator" => array(
          "timestamp",
          array(
            "empty()",
          ),
        ),
      ),
    );
  }
  public function bof_columns(){
    return array(
      "time_add"
    );
  }
  public function selectors(){
    return array(
      "cron_id" => [ "cron_id", "=" ],
      "spotify_id" => [ "spotify_id", "=" ],
      "ID" => function( $val ){

        $key_exploded = explode( "_", $val );
        $_cron_id = reset( $key_exploded );
        $_spotify_id = implode( "_", array_slice( $key_exploded, 1 ) );

        return array(
          "oper" => "AND",
          "cond" => array(
            [ "cron_id", "=", $_cron_id ],
            [ "spotify_id", "=", $_spotify_id ]
          )
        );

      },
    );
  }

  public function time( $id, $interval=false ){
    $time = $this->_bof_this->sid( $id, [ "time" => true ] );
    if ( !$interval ) return $time;
    return $time ? $time > ($interval*24*60*60) : true;
  }

  public function clean( $item, $args=[] ){

    $time = false;
    extract( $args );

    if ( $time ){
      if ( $item["time_check"] ){
        $item = time() - strtotime( $item["time_check"] );
      } else {
        $item = false;
      }
    }

    return $item;

  }

}

?>
