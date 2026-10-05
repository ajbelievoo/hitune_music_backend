<?php

if ( !defined( "bof_root" ) ) die;

class object_m_tag extends bof_type_object_child {

  public $sample_parent = "tag";
  public function child(){
    return (object) array(
      "bof_admin_edit_url" => "music_tag",
      "bof_admin_list_url" => "music_tags",
      "bof_client_single_url" => "music/tag",
      "bof_client_list_url" => "music/tags",
      "relations" => array(
        "m_album" => array(
          "stat" => "albums",
          "hub_type" => "tag",
          "plural" => "tags",
          "bof_admin_list_url" => "music_albums",
          "label" => "albums",
          "label_hook" => "albums",
          "display" => array(
            "order_by" => "time_release"
          )
        ),
        "m_artist" => array(
          "stat" => "artists",
          "hub_type" => "tag",
          "plural" => "tags",
          "bof_admin_list_url" => "music_artists",
          "label" => "artists",
          "label_hook" => "artists",
        ),
        "m_track" => array(
          "stat" => "tracks",
          "hub_type" => "tag",
          "plural" => "tags",
          "bof_admin_list_url" => "music_tracks",
          "label" => "tracks",
          "label_hook" => "tracks",
          "display" => array(
            "order_by" => "time_release"
          )
        ),
      )
    );
  }

  // BusyOwlFramework handshake
  public function bof(){
    return $this->_parent->bof(
      $this->_bof_this,
      array(
        "name" => "m_tag",
        "label" => "Music Tag",
        "db_table_name" => "_c_m_tags",
      )
    );
  }
  public function clean_search_terms( $item ){

    $o = array(
      $item["name"] => 1,
    );

    return $o;

  }

}

?>
