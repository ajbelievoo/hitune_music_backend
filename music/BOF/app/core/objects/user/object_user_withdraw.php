<?php

if ( !defined( "bof_root" ) ) die;

class object_user_withdraw extends bof_type_object {

  public function stas(){
    return array(
      1 => [ 1, "pending" ],
      2 => [ 2, "paid" ],
      3 => [ 3, "rejected" ],
    );
  }
  // BusyOwlFramework handshake
  public function bof(){
    return array(
      "name" => "user_withdraw",
      "label" => "User Withdrawal",
      "icon" => "cases",
      "db_table_name" => "_u_withdrawal",
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
              "child_object" => "user_withdraw",
              "child_object_selector_column" => "user_id",
              "delete_child_too" => true,
              "limit" => 1
            ),
          ),
        ),
      ),
      "amount" => array(
        "label" => "Amount",
        "validator" => array(
          "float",
          array(
            "empty()",
          ),
        ),
        "bofAdmin" => array(
          "list" => array(
            "type" => "simple",
            "renderer" => function( $displayItem, $item, $displayData ){
              $displayData["data"] .= " " . bof()->object->currency->get_default()["iso_code"];
              return $displayData;
            },
          ),

        )
      ),
      "receiver" => array(
        "label" => "Receiver",
        "validator" => array(
          "string",
          array(
            "encode" => true,
            "empty()"
          )
        ),
        "bofAdmin" => array(
          "list" => array(
            "type" => "simple",
          )
        ),
      ),
      "additional_data" => array(
        "label" => "Additional data",
        "validator" => array(
          "string",
          array(
            "empty()"
          )
        ),
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
            "min" => 1,
            "max" => 3
          ),
        ),
        "bofAdmin" => array(
          "list" => array(
            "type" => "tag",
            "renderer" => function( $displayItem, $item, $displayData ){
              $stas = bof()->object->user_withdraw->stas();
              $displayData["data"] = ucfirst( $stas[ $displayData["data"] ][1] );
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
      "sta"      => [ "sta", "=" ]
    );
  }
  public function bof_admin(){

    return array(
      "config" => array(
        "search" => false,
        "create" => false,
        "edit" => false,
        "delete" => true,
        "pagination" => true,
        "edit_page_url" => "user_withdraw",
        "list_page_url" => "user_withdraws",
        "multi" => array(
          "select" => false,
          "delete" => false,
          "edit"   => false
        )
      ),
      "buttons" => array(
        "approve" => array(
          "id" => "approve",
          "label" => "Mark as paid",
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

        if ( $item["sta"] == 2 ){
          unset( $buttons["approve"] );
        }
        elseif ( $item["sta"] == 3 ) {
          unset( $buttons["unapprove"] );
        }

        return $buttons;

      },
      "filters" => array(
        "sta" => array(
          "title" => "Status",
          "input" => array(
            "name" => "sta",
            "type" => "select_i",
            "options" => array(
              [ "__all__", "All" ],
              [ "1", "Pending" ],
              [ "2", "Approved" ],
              [ "3", "Rejected" ],
            ),
            "value" => "__all__"
          ),
          "validator" => array(
            "in_array",
            [ "values" => [ "1", "2", "3", "__all__" ] ]
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
        "amount" => null,
        "receiver" => null,
        "additional_data" => null,
        "sta" => null,
        "time_review" => null,
        "time_add" => array(
          "type" => "time",
          "label" => "Creation<br>Time"
        ),
      ),
      "actions" => array(
        "approve" => function( $ids ){

          bof()->object->user_withdraw->update(
            array(
              "ID" => $ids
            ),
            array(
              "sta" => 2,
              "time_review" => bof()->general->mysql_timestamp()
            )
          );

          return [ 1, "Marked" ];

        },
        "unapprove" => function( $ids ){

          $withdrawData = bof()->object->user_withdraw->sid( $ids );

          bof()->object->user_withdraw->update(
            array(
              "ID" => $ids
            ),
            array(
              "sta" => 3,
              "time_review" => bof()->general->mysql_timestamp()
            )
          );

          $transaction = bof()->object->user->add_fund(
            bof()->user->check()->ID,
            $withdrawData["amount"],
            array(
              "type" => "deposit",
              "object_type" => "user_withdraw",
              "object_id" => $ids,
              "revenue" => 0
            )
          );

          return [ 1, "Rejeted" ];

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

      $id_data = bof()->object->user_request->select(["ID"=>$id]);
      if ( $id_data ? $id_data["sta"] != 1 : false ){

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

          return true;

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

          return true;

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

          return true;

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
