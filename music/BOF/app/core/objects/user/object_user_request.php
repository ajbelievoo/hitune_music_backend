<?php

if ( !defined( "bof_root" ) ) die;

class object_user_request extends bof_type_object {

  protected $cache = array(
    "tabs" => [],
    "types" => []
  );

  // BusyOwlFramework handshake
  public function bof(){
    return array(
      "name" => "user_request",
      "label" => "User Requests",
      "icon" => "alert",
      "db_table_name" => "_u_requests",
    );
  }
  public function columns(){
    return array(
      "user_id" => array(
        "label" => "User",
        "validator" => "int",
        "bofAdmin" => array(
          "list" => array(
            "type" => "simple",
            "renderer" => function( $displayItem, $item, $displayData ){
              $displayData["data"] = $item["bof_dir_user"]["name_styled"];
              $displayData["sub_data"] = "Email: {$item["bof_dir_user"]["email"]}<br>ID: {$item["bof_dir_user"]["ID"]}";
              return $displayData;
            },
          )
        ),
        "relations" => array(
          "user" => array(
            "exec" => array(
              "type" => "direct",
              "parent_object" => "user",
              "child_object" => "user_request",
              "child_object_selector_column" => "user_id",
              "delete_child_too" => true,
              "limit" => 1
            ),
          ),
        ),
      ),
      "type" => array(
        "label" => "Type",
        "validator" => "string",
      ),
      "real_name" => array(
        "label" => "Real name",
        "validator" => "string",
        "bofAdmin" => array(
          "list" => array(
            "type" => "simple",
          )
        ),
      ),
      "extra_data" => array(
        "validator" => array(
          "json",
          array(
            "encode" => true,
            "empty()"
          )
        )
      ),
      "additional_data" => array(
        "label" => "Additional data",
        "validator" => "string",
        "bofAdmin" => array(
          "list" => array(
            "type" => "simple",
          )
        ),
      ),
      "sta" => array(
        "label" => "Status",
        "validator" => array(
          "int",
          array(
            "empty()",
            "min" => -2
          ),
        ),
        "bofAdmin" => array(
          "list" => array(
            "type" => "tag",
            "renderer" => function( $displayItem, $item, $displayData ){
              $displayData["data"] = $item["sta"] == 1 ? "Approved" : ( $item["sta"] == -1 ? "Rejeted" : "Pending" );
              return $displayData;
            },
          )
        )
      ),
      "time_review" => array(
        "label" => "Review<br>Time",
        "validator" => array(
          "timestamp",
          array(
            "empty()"
          ),
        ),
        "bofAdmin" => array(
          "list" => array(
            "type" => "time",
          ),
        ),
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
      "user_id"  => [ "user_id", "=" ],
      "col_user" => [ "user_id", "by_column" ],
      "type"     => [ "type", "=" ],
      "sta"      => [ "sta", "=" ]
    );
  }
  public function bof_admin(){

    $tabs = $this->_bof_this->_get_tabs();
    $types = $this->_bof_this->_get_types();
    $types_for_dispaly = [];

    if ( $types ){
      foreach( $types as $id => $k ){
        $types[ $id ] = [ $id, $k ];
      }
    }

    return array(
      "config" => array(
        "search" => false,
        "create" => false,
        "edit" => false,
        "delete" => true,
        "pagination" => true,
        "edit_page_url" => "user_request",
        "list_page_url" => "user_requests",
        "multi" => array(
          "select" => false,
          "delete" => false,
          "edit"   => false
        )
      ),
      "buttons" => array(
        "approve" => array(
          "id" => "approve",
          "label" => "Approve",
          "payload" => array(
            "post" => array(
              "__action" => "approve"
            )
          )
        ),
        "unapprove" => array(
          "id" => "unapprove",
          "label" => "Reject",
          "payload" => array(
            "post" => array(
              "__action" => "unapprove"
            )
          )
        ),
      ),
      "buttons_renderer" => function( $item, $buttons ){

        if ( $item["sta"] == 1 ){
          unset( $buttons["approve"] );
          unset( $buttons["delete"] );
        }
        elseif ( $item["sta"] == -1 ) {
          unset( $buttons["unapprove"] );
        }

        return $buttons;

      },
      "filters" => array(
        "type" => array(
          "title" => "Type",
          "input" => array(
            "name" => "type",
            "type" => "select_i",
            "options" => array_values( $types ),
            "value" => "__all__"
          ),
          "validator" => array(
            "in_array",
            [ "values" => array_merge( array_keys( $types ), [ "__all__" ] ) ]
          )
        ),
        "sta" => array(
          "title" => "Status",
          "input" => array(
            "name" => "sta",
            "type" => "select_i",
            "options" => array(
              [ "__all__", "All" ],
              [ "1", "Approved" ],
              [ "0", "Pending" ],
              [ "-1", "Rejected" ],
            ),
            "value" => "__all__"
          ),
          "validator" => array(
            "in_array",
            [ "values" => [ "0", "1", "-1", "__all__" ] ]
          )
        ),
        "col_user" => array(
          "title" => "User(s)",
          "input" => array(
            "name" => "col_user",
            "type" => "bof_input",
          ),
          "bofInput" => array(
            "object",
            array(
              "type" => "user",
              "multi" => true,
              "args" => array(
                "filter" => "col_user",
              )
            )
          )
        ),
      ),
      "list" => array(
        "user_id" => null,
        "real_name" => null,
        "stats" => array(
          "type" => "simple",
          "class" => "details",
          "label" => "Extra data",
          "renderer" => function( $displayItem, $item, $displayData ){
            $displayData["data"] = "<ul>";
            if ( $item["extra_data_decoded"] ){
              foreach ( $item["extra_data_decoded"] as $_k => $_ds ){

                $data = $_ds["data"];
                if ( $_ds["type"] == "int" ) $data = bof()->general->number_format_hr( $data );
                if ( $_ds["type"] == "file" ) {
                  $file = bof()->object->file->sid( $data, [ "unlock" => true ] );
                  if ( $file ) $data = "<a href='{$file["web_address"]}'>View</a>";
                  else $data = "file not found";
                }
                $displayData["data"] .= "<li><b>{$_k}</b>{$data}</li>";

              }
            }
            $displayData["data"] .= "</ul>";
            return $displayData;
          },
        ),
        "additional_data" => null,
        "sta" => null,
        "time_review" => null,
      ),
      "actions" => array(
        "approve" => function( $ids ){
          bof()->object->user_request->_approve( $ids );
          return [ 1, "Marked" ];
        },
        "unapprove" => function( $ids ){
          bof()->object->user_request->_reject( $ids );
          return [ 1, "Marked" ];
        },
      ),
    );

  }

  // BusyOwlFramework helpers
  public function select( $whereArgs=[], $selectArgs=[] ){

    $listing = false;
    $editing = false;
    $deleting = false;
    $_eq = [];
    extract( $selectArgs );

    if ( $listing )
    $_eq[ "user" ] = [ "_eq" => [] ];

    $selectArgs[ "_eq" ] = $_eq;
    return bof()->object->_select( $this, $whereArgs, $selectArgs );

  }

  public function _approve( $ids ){

    $ids = is_array( $ids ) ? $ids : [ $ids ];

    foreach( $ids as $id ){

      $id_data = bof()->object->user_request->select(["ID"=>$id],array(
        "cache_load_rt" => false
      ));
      if ( $id_data ? $id_data["sta"] != 1 : false ){

        $user_data = bof()->object->user->sid( $id_data["user_id"] );
        $user_roles = bof()->object->user_role->parse_users( 
          $user_data,
          "client"
        );

        $_roles = explode( ",", $user_data["role_ids"] );
        $_new_roles = false;

        // mark
        bof()->object->user_request->update(
          array(
            "ID" => $id,
          ),
          array(
            "sta" => 1,
            "time_review" => bof()->general->mysql_timestamp(),
          )
        );

        if ( $id_data["type"] == "m_artist" ){

          $artist_code = bof()->general->make_code( $id_data["extra_data_decoded"]["stage_name"]["data"] );
          $artist_data = bof()->object->m_artist->select(array(
            "code" => $artist_code
          ));

          if ( $artist_data ){

            bof()->object->m_artist->update(
              array(
                "ID" => $artist_data["ID"]
              ),
              array(
                "manager_id" => $id_data["user_id"]
              )
            );
            $artist_id = $artist_data["ID"];

          }
          else {

            $artist_id = bof()->object->m_artist->insert(array(
              "code" => bof()->general->make_code( $id_data["extra_data_decoded"]["stage_name"]["data"] ),
              "name" => $id_data["extra_data_decoded"]["stage_name"]["data"],
              "manager_id" => $id_data["user_id"],
              "hash" => bof()->object->m_artist->get_free_hash(),
              "seo_url"  => bof()->object->m_artist->get_free_url( $id_data["extra_data_decoded"]["stage_name"]["data"] ),
            ));

          }

          bof()->object->user->update(
            array(
              "ID" => $id_data["user_id"]
            ),
            array(
              "s_managed_artists" => 1
            )
          );

          if ( !empty( $user_roles["user"]["verify_m_nur"] ) ){
            $_new_roles = $user_roles["user"]["verify_m_nur"]; 
          }

        }
        elseif ( $id_data["type"] == "mb_producer" ){

          $producer_code = bof()->general->make_code( $id_data["extra_data_decoded"]["stage_name"]["data"] );
          $producer_data = bof()->object->mb_producer->select(array(
            "code" => $producer_code
          ));

          if ( $producer_data ){

            bof()->object->mb_producer->update(
              array(
                "ID" => $producer_data["ID"]
              ),
              array(
                "manager_id" => $id_data["user_id"]
              )
            );
            $producer_id = $producer_data["ID"];

          }
          else {

            $producer_id = bof()->object->mb_producer->insert(array(
              "code" => bof()->general->make_code( $id_data["extra_data_decoded"]["stage_name"]["data"] ),
              "name" => $id_data["extra_data_decoded"]["stage_name"]["data"],
              "manager_id" => $id_data["user_id"],
              "hash" => bof()->object->mb_producer->get_free_hash(),
              "seo_url"  => bof()->object->mb_producer->get_free_url( $id_data["extra_data_decoded"]["stage_name"]["data"] ),
            ));

          }

          bof()->object->user->update(
            array(
              "ID" => $id_data["user_id"]
            ),
            array(
              "s_managed_producers" => 1
            )
          );

          if ( !empty( $user_roles["user"]["verify_mb_nur"] ) ){
            $_new_roles = $user_roles["user"]["verify_mb_nur"]; 
          }

        }
        elseif ( $id_data["type"] == "p_podcaster" ){

          $artist_code = bof()->general->make_code( $id_data["extra_data_decoded"]["podcaster_name"]["data"] );
          $artist_data = bof()->object->p_podcaster->select(array(
            "code" => $artist_code
          ));

          if ( $artist_data ){

            bof()->object->p_podcaster->update(
              array(
                "ID" => $artist_data["ID"]
              ),
              array(
                "manager_id" => $id_data["user_id"]
              )
            );
            $artist_id = $artist_data["ID"];

          }
          else {

            $artist_id = bof()->object->p_podcaster->insert(array(
              "code" => bof()->general->make_code( $id_data["extra_data_decoded"]["podcaster_name"]["data"] ),
              "name" => $id_data["extra_data_decoded"]["podcaster_name"]["data"],
              "manager_id" => $id_data["user_id"],
              "hash" => bof()->object->p_podcaster->get_free_hash(),
              "seo_url"  => bof()->object->p_podcaster->get_free_url( $id_data["extra_data_decoded"]["podcaster_name"]["data"] ),
            ));

          }

          bof()->object->user->update(
            array(
              "ID" => $id_data["user_id"]
            ),
            array(
              "s_managed_podcasters" => 1
            )
          );

          if ( !empty( $user_roles["user"]["verify_p_nur"] ) ){
            $_new_roles = $user_roles["user"]["verify_p_nur"]; 
          }

        }
        elseif ( $id_data["type"] == "affiliate" ){

          bof()->object->user->update(
            array(
              "ID" => $id_data["user_id"]
            ),
            array(
              "s_affiliate" => 1
            )
          );

          if ( !empty( $user_roles["user"]["verify_a_nur"] ) ){
            $_new_roles = $user_roles["user"]["verify_a_nur"]; 
          }

        }

        if ( !empty( $_new_roles ) ){

          $_roles = array_unique( array_merge( $_roles, $_new_roles ) );

          bof()->object->user->update(
            array(
              "ID" => $user_data["ID"]
            ),
            array(
              "role_ids" => implode( ",", $_roles )
            )
          );
          
        }

        bof()->chapar->notify( "verification_ok", array(
          "target" => array(
            "user_id" => $id_data["user_id"]
          ),
          "source" => array(
            "object" => "user_request",
            "id" => $id
          ),
        ) );

      }

    }

  }
  public function _reject( $ids ){

    $ids = is_array( $ids ) ? $ids : [ $ids ];

    foreach( $ids as $id ){

      $id_data = bof()->object->user_request->select(["ID"=>$id]);
      if ( $id_data ? $id_data["sta"] != -1 : false ){

        bof()->object->user_request->update(
          array(
            "ID" => $id,
          ),
          array(
            "sta" => -1,
            "time_review" => bof()->general->mysql_timestamp(),
          )
        );

        bof()->chapar->notify( "verification_rejected", array(
          "target" => array(
            "user_id" => $id_data["user_id"]
          ),
          "source" => array(
            "object" => "user_request",
            "id" => $id
          ),
        ) );

      }

    }

  }

  public function _add_tab( $name, $data ){
    $data["ID"] = $name;
    $this->cache["tabs"][ $name ] = $data;
    $this->cache["types"][ $data["type"] ] = $name;
  }
  public function _add_type( $ID, $name ){
    $this->cache["types"][ $ID ] = $name;
  }
  public function _get_tabs(){
    return $this->cache["tabs"];
  }
  public function _get_types(){
    return $this->cache["types"];
  }

}

?>
