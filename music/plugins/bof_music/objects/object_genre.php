<?php

if ( !defined( "bof_root" ) ) die;

class object_m_genre extends bof_type_object_child {

  public $sample_parent = "tag";
  public function child(){
    return (object) array(
      "bof_admin_edit_url" => "music_genre",
      "bof_admin_list_url" => "music_genres",
      "bof_client_single_url" => "music/genre",
      "bof_client_list_url" => "music/genres",
      "relations" => array(
        "m_album" => array(
          "stat" => "albums",
          "hub_type" => "genre",
          "plural" => "genres",
          "bof_admin_list_url" => "music_albums",
          "label_hook" => "albums",
          "label" => "albums",
          "display" => array(
            "order_by" => "time_release"
          )
        ),
        "m_artist" => array(
          "stat" => "artists",
          "hub_type" => "genre",
          "plural" => "genres",
          "bof_admin_list_url" => "music_artists",
          "label_hook" => "artists",
          "label" => "artists"
        ),
        "m_track" => array(
          "stat" => "tracks",
          "hub_type" => "genre",
          "plural" => "genres",
          "bof_admin_list_url" => "music_tracks",
          "label_hook" => "tracks",
          "label" => "tracks",
          "display" => array(
            "order_by" => "time_release"
          )
        ),
      ),
      "hiearchy" => bof()->object->m_genre->genre_hiearchy() ? array( true ) : false,
    );
  }

  // BusyOwlFramework handshake
  public function bof(){
    return $this->_parent->bof(
      $this->_bof_this,
      array(
        "name" => "m_genre",
        "label" => "Music Genre",
        "db_table_name" => "_c_m_genres",
        "browsable" => true,
      )
    );
  }
  public function selectors(){
    return $this->_parent->selectors(
      $this->_bof_this,
      array(
        "type" => function( $val ){
          if ( $val == "master" || $val == "parent" )
          return [ "parent_id", null, null, true ];
          return [ "parent_id", "NOT", null, true ];
        }
      )
    );
  }
  public function bof_admin(){
    return $this->_parent->bof_admin( $this->_bof_this );
  }

  public function clean_search_terms( $item ){

    $o = array(
      $item["name"] => 1,
    );

    return $o;

  }

  public function clean_client_single( $item, $args ){
    return $this->_parent->clean_client_single( $this->_bof_this, $item, $args );
  }
  public function clean_client_single_widget( $item, $widget_name, $args=[] ){
    return $this->_parent->clean_client_single_widget( $this->_bof_this, $item, $widget_name, $args );
  }

  public function genre_hiearchy(){
		return defined("disable_music_genre_hiearchy") ? !disable_music_genre_hiearchy : true;
	}

}

?>
