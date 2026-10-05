<?php

if ( !defined( "bof_root" ) ) die;

class object_m_lang extends bof_type_object_child {

  public $sample_parent = "tag";
  public function child(){
    return (object) array(
      "bof_admin_edit_url" => "music_lang",
      "bof_admin_list_url" => "music_langs",
      "bof_client_single_url" => "music/lang",
      "bof_client_list_url" => "music/langs",
      "relations" => array(
        "m_album" => array(
          "stat" => "albums",
          "hub_type" => "lang",
          "plural" => "langs",
          "bof_admin_list_url" => "music_albums"
        ),
        "m_artist" => array(
          "stat" => "artists",
          "hub_type" => "lang",
          "plural" => "langs",
          "bof_admin_list_url" => "music_artists"
        ),
        "m_track" => array(
          "stat" => "tracks",
          "hub_type" => "lang",
          "plural" => "langs",
          "bof_admin_list_url" => "music_tracks"
        ),
      )
    );
  }

  // BusyOwlFramework handshake
  public function bof(){
    return $this->_parent->bof(
      $this->_bof_this,
      array(
        "name" => "m_lang",
        "label" => "Music Language",
        "db_table_name" => "_c_m_langs",
      )
    );
  }

}

?>
