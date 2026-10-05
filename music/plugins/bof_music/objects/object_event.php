<?php

if ( !defined( "bof_root" ) ) die;

class object_m_event extends bof_type_object {

  // BusyOwlFramework handshake
  public function bof(){
    return array(
      "name" => "m_event",
      "label" => "Music Event",
      "icon" => "celebration",
      "db_table_name" => "_c_m_events",
      "db_rel_table_name" => "_c_m_events_relations",
      "db_rel_table_col_name" => "event_id",
      "widgetable" => true,
    );
  }
  public function columns(){
    return array(

      "name" => array(
        "label" => "Name",
        "validator" => array(
          "string",
          array(
            "strip_emoji" => false,
            "only_utf8" => false
          ),
        ),
        "input" => array(
          "type" => "text",
        ),
        "bofAdmin" => array(
          "sortable" => true,
          "list" => array(
            "type" => "simple",
            "class" => "title",
            "renderer" => function( $displayItem, $item, $displayData ){
              if ( !empty( $item["bof_file_cover"] ) )
              $displayData["image_preview"] = $item["bof_file_cover"]["image_thumb"];
              return $displayData;
            }
          ),
          "object" => array(
            "required" => true,
            "seo_slug_source" => true
          )
        ),
      ),
      "cover_id" => array(
        "label" => "Cover",
        "bofInput" => array(
          "file",
          array(
            "type" => "image",
            "object_type" => "m_event"
          )
        ),
        "selectors" => array(
          "has_cover" => function( $val ){

            if ( $val > 0 )
            return [ "cover_id", ">", "0" ];

            return array(
              "oper" => "OR",
              "cond" => array(
                [ "cover_id", "=", "0" ],
                [ "cover_id", null, null, true ]
              )
            );

          },
        ),
        "bofAdmin" => array(
          "object" => array(),
          "filters" => array(
            "has_cover" => array(
              "title" => "Has cover",
              "input" => array(
                "type" => "select_i",
                "options" => array(
                  [ -1, "no" ],
                  [ 1, "yes" ],
                  [ "__all__", "all" ]
                ),
                "value" => "__all__"
              ),
              "validator" => array(
                "in_array",
                [ "values" => [ "-1", "1", "__all__" ] ]
              )
            ),
          ),
        ),
      ),
      "manager_id" => array(
        "label" => "Manager",
        "tip" => "Music manager in charge of this event",
        "bofInput" => array(
          "object",
          array(
            "type" => "user",
            "multi" => false
          )
        ),
        "bofAdmin" => array(
          "sortable" => true,
          "object" => array(
            "required" => true
          ),
          "filters" => array(
            "has_manager" => array(
              "title" => "Has Manager",
              "tip" => "Artists with a `connected user as manager` or verified artists",
              "input" => array(
                "type" => "select_i",
                "options" => array(
                  [ -1, "no" ],
                  [ 1, "yes" ],
                  [ "__all__", "all" ]
                ),
                "value" => "__all__"
              ),
              "validator" => array(
                "in_array",
                [ "values" => [ "-1", "1", "__all__" ] ]
              )
            ),
            "col_manager" => array(
              "title" => "Manager",
              "bofInput" => array(
                "object",
                array(
                  "type" => "user",
                  "multi" => true,
                )
              )
            ),
          ),
        ),
        "selectors" => array(
          "has_manager" => function( $val ){

              if ( $val == 1 )
              return [ "manager_id", ">", "0" ];

              return array(
                "oper" => "OR",
                "cond" => array(
                  [ "manager_id", null, null, true ],
                  [ "manager_id", "=", 0 ]
                )
              );

          },
          "col_manager" => [ "manager_id", "by_column" ],
        ),
        "relations" => array(
          "manager" => array(
            "exec" => array(
              "type" => "direct",
              "parent_object" => "user",
              "child_object" => "m_artist",
              "child_object_selector_column" => "manager_id",
              "limit" => 1
            ),
          ),
        ),
      ),

      "description" => array(
        "label" => "Detail",
        "validator" => array(
          "editor_js",
          array(
            "empty()",
          ),
        ),
        "input" => array(
          "type" => "text_editor",
        ),
        "bofAdmin" => array(
          "object" => array(),
        ),
      ),
      "website" => array(
        "label" => "Website",
        "validator" => array(
          "url",
          array(
            "empty()"
          )
        ),
        "input" => array(
          "type" => "text"
        ),
        "bofAdmin" => array(
          "object" => array(),
        )
      ),
      "price" => array(
        "label" => "Ticket Price",
        "validator" => "float",
        "input" => array(
          "type" => "digit"
        ),
        "bofAdmin" => array(
          "object" => array(
            "required" => true
          ),
          "list" => array(
            "type" => "tag"
          )
        )
      ),
      "maximum" => array(
        "label" => "Maximum available tickets",
        "tip" => "Leave empty or enter 0 if there are no limits",
        "validator" => array(
          "int",
          array(
            "empty()",
            "min" => 0
          )
        ),
        "input" => array(
          "type" => "digit"
        ),
        "bofAdmin" => array(
          "object" => array()
        )
      ),

      "s_views" => array(
        "label" => "Total views",
        "validator" => array(
          "int",
          array(
            "empty()",
            "min" => 0
          )
        ),
        "bofAdmin" => array(
          "sortable" => true,
        ),
      ),
      "s_views_unique" => array(
        "label" => "Total unique views",
        "validator" => array(
          "int",
          array(
            "empty()",
            "min" => 0
          )
        ),
        "bofAdmin" => array(
          "sortable" => true,
        ),
      ),
      "s_sales" => array(
        "label" => "Total Sales",
        "validator" => array(
          "int",
          array(
            "empty()",
            "min" => 0
          )
        ),
        "bofAdmin" => array(
          "sortable" => true,
          "list" => array(
            "type" => "simple",
            "renderer" => function( $displayItem, $item, $displayData ){
              if ( !$item["s_sales"] )
              $displayData["data"] = $item["s_sales"] ? $item["s_sales"] : 0;
              return $displayData;
            }
          )
        ),
      )

    );
  }
  public function bof_columns(){
    return array(
      "ID",
      "hash",
      "time_add",
      "seo",
    );
  }
  public function selectors(){
    return array(
      "manager_id"  => [ "manager_id", "=" ],
      "query" => [ "name", "LIKE%lower" ],
    );
  }
  public function relations(){
    return array(
    );
  }
  public function bof_admin(){
    return array(
      "config" => array(
        "search" => true,
        "create" => true,
        "edit" => true,
        "delete" => true,
        "pagination" => true,
        "edit_page_url" => "music_event",
        "list_page_url" => "music_events",
        "multi" => array(
          "select" => true,
          "delete" => true,
          "edit"   => true
        )
      ),
      "buttons_renderer" => function( $item, $buttons ){


        return $buttons;

      },
    );
  }

  // BusyOwlFramework helpers
  public function select( $whereArgs=[], $selectArgs=[] ){

    $search = false;
    $listing = false;
    $_eq = [];
    extract( $selectArgs );

    if ( $search || $listing ){
      $_eq["cover"] = [];
    }
    if ( $listing ){
      $_eq["manager"] = [];
    }

    $selectArgs[ "_eq" ] = $_eq;
    return bof()->object->_select( $this, $whereArgs, $selectArgs );

  }
  public function create( $whereArray, $insertArray, $updateArray=false ){

    $create_data = bof()->object->_create( $this, $whereArray, $insertArray, $updateArray, true );
    $db_id = $create_data["ID"];

    if ( $db_id && !empty( $insertArray["genre_string_array"] ) ){

			$artist_genre_ids = [];

			foreach( $insertArray["genre_string_array"]  as $_genreName ){
				$_genreID = bof()->object->m_genre->get_id( $_genreName );
				if ( $_genreID ) $artist_genre_ids[] = $_genreID;
			}

			if ( $artist_genre_ids ){
				bof()->object->m_artist->make_rels( $db_id, $artist_genre_ids, "genre" );
			}

		}

    return $db_id;

  }
  public function insert( $setArray ){

    $setArray["hash"] = !empty( $setArray["hash"] ) ? $setArray["hash"] : bof()->object->m_artist->get_free_hash();
    return bof()->object->_insert( $this, $setArray );

  }
  public function clean( $item, $args ){

    $_eq = [];
    $search = false;
    $listing = false;
    extract( $args );

    if ( in_array( "cover", array_keys( $_eq ), true ) && empty( $item["bof_file_cover"] ) && !empty( $item["spotify_cover_decoded"] ) ){
      foreach( $item["spotify_cover_decoded"] as $_s_cover )
      $_spotify_covers_raw[ $_s_cover["url"] ] = [ $_s_cover["width"], $_s_cover["height"] ];
      $_images = array_keys( $_spotify_covers_raw );
      $item["bof_file_cover"]["image_strings"] = bof()->image->html( $_spotify_covers_raw );
      $item["bof_file_cover"]["image_thumb"] = end( $_images );
    }

    if ( !empty( $item["name"] ) ){
      $_raw_name = $item["name"];
      $item["name_styled"] = $item["name"];
      bof()->nest->validate( $_raw_name, "string", [ "strip_emoji" => false ] );
      $item["name_clean"] = $_raw_name;
      if ( $item["manager_id"] )
      $item["name_styled"] .= '<span class="material-icons verified">verified</span>';
    }

    if ( $search ){
      $item = array(
        "ID" => $item["ID"],
        "title" => $item["name_clean"],
        "image" => !empty( $item["bof_file_cover"]["image_thumb"] ) ? $item["bof_file_cover"]["image_thumb"] : false
      );
    }

    return $item;

  }

}

?>
