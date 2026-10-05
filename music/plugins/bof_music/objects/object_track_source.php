<?php

if ( !defined( "bof_root" ) ) die;

class object_m_track_source extends bof_type_object_child {

  public $sample_parent = "source";
  public function child(){
    return (object) array(
      "bof_admin_edit_url" => "music_source",
      "bof_admin_list_url" => "music_sources",
      "types" => "all",
      "searchable" => true
    );
  }

  // BusyOwlFramework handshake
  public function bof(){
    return $this->_parent->bof(
      $this->_bof_this,
      array(
        "name" => "m_track_source",
        "label" => "Music Track Source",
        "db_table_name" => "_c_m_tracks_sources",
      )
    );
  }
  public function columns(){

    return $this->_parent->columns(
      $this->_bof_this,
      array(
        "target_id" => array(
          "label" => "Target",
          "bofInput" => array(
            "object",
            array(
              "type" => "m_track"
            )
          ),
          "bofAdmin" => array(
            "object" => array(
              "required" => true
            ),
            "list" => array(
              "type" => "simple",
              "renderer" => function( $displayItem, $item, $displayData ){
                $displayData["data"] = !empty( $item["bof_dir_target"]["title"] ) ? $item["bof_dir_target"]["title"] : "?";
                $displayData["data"] .= "<span class='sub'>";
                $displayData["data"] .= ucfirst( $item["_title"] );
                $displayData["data"] .= "</span>";
                return $displayData;
              },
            ),
            "filters" => array(
              "col_track" => array(
                "title" => "Track",
                "input" => array(
                  "name" => "col_track",
                ),
                "bofInput" => array(
                  "object",
                  array(
                    "type" => "m_track",
                    "multi" => true,
                    "autoload" => false
                  )
                )
              ),
            )
          ),
          "relations" => array(
            "target" => array(
              "exec" => array(
                "type" => "direct",
                "parent_object" => "m_track",
                "child_object" => "m_track_source",
                "child_object_selector_column" => "target_id",
                "delete_child_too" => true,
                "limit" => 1
              )
            )
          ),
          "selectors" => array(
            "target_id" => [ "target_id", "=" ],
            "track_id" => [ "target_id", "=" ],
            "col_track" => [ "target_id", "by_column" ],
            "col_target" => [ "target_id", "by_column" ],
          )
        ),
      )
    );

  }
  public function selectors(){

    return $this->_parent->selectors(
      $this->_bof_this,
      array(
        "query" => function( $val ){
          
          $tracks = bof()->object->m_track->select(
            array(
              "query" => $val
            ),
            array(
              "single" => false,
              "limit" => 10000,
              "clean" => false
            )
          );

          if ( $tracks ){
            $tids = [];
            foreach( $tracks as $t ){
              $tids[] = $t["ID"];
            }
            // return [ "target_id", "IN", $tids, true ];
            return [ "target_id", "IN", implode( ",", $tids ), true ];
          }

        }
      )
    );

  }

  public function fetch_file_data( $target_id ){

    $track = bof()->object->m_track->select(
      array(
        "ID" => $target_id
      ),
      array(
        "_eq" => array(
          "artist" => array(
            "cache_load_rt" => false
          ),
          "album" => array(
            "_eq" => array(
              "cover" => array(
                "cache_load_rt" => false
              )
            ),
            "cache_load_rt" => false
          ),
          "genres" => array(
            "cache_load_rt" => false
          ),
          "cover" => array(
            "cache_load_rt" => false
          )
        ),
        "cache_load_rt" => false
      )
    );

    $_cover = null;
    if ( !empty( $track["bof_file_cover"]["host_id"] ) ? $track["bof_file_cover"]["host_id"] == 1 : false ){
      $_cover = $track["bof_file_cover"]["abs_path"];
    } elseif ( !empty( $track["bof_dir_album"]["bof_file_cover"]["host_id"] ) ? $track["bof_dir_album"]["bof_file_cover"]["host_id"] == 1 : false ){
      $_cover = $track["bof_dir_album"]["bof_file_cover"]["abs_path"];
    }

    return array(
      "premium" => $track["premium"],
      "new_name" => $track["bof_dir_artist"]["name"] . " - " . $track["bof_dir_album"]["title"] . ( $track["bof_dir_album"]["type"] == "single" ? " - single" : " - " . $track["title"] ),
      "new_id3_tags" => array(
        "title"        => [ $track["title"] ],
        "artist"       => [ $track["bof_dir_artist"]["name"] ],
        "album"        => [ $track["bof_dir_album"]["title"] ],
        "track_number" => [ $track["album_index"] ],
        // "genre"        => [ $track["bof_rel_genres"] ],
        "year"         => [ $track["time_release"] ? substr( $track["time_release"][0], 0, 4 ) : null ],
        "cover"        => $_cover
      )
    );

  }

}

?>
