<?php

if ( !defined( "bof_root" ) ) die;

class object_user_subs extends bof_type_object {

  public function bof(){
    return array(
      "name" => "user_subs",
      "label" => "User Subscription",
      "icon" => "subscription",
      "db_table_name" => "_u_subs",
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
              if ( !empty( $item["bof_dir_user"]["bof_file_avatar"] ) )
              $displayData["image_preview"] = $item["bof_dir_user"]["bof_file_avatar"]["image_thumb"];
              $displayData["data"] = $item["bof_dir_user"]["name_styled"];
              return $displayData;
            }
          ),
          "object" => array(
            "required" => true
          )
        ),
        "bofInput" => array(
          "object",
          array(
            "type" => "user",
            "multi" => false
          )
        ),
        "relations" => array(
          "user" => array(
            "exec" => array(
              "type" => "direct",
              "parent_object" => "user",
              "child_object" => "user_subs",
              "child_object_selector_column" => "user_id",
              "delete_child_too" => true,
              "limit" => 1
            ),
          ),
        ),
      ),
      "subs_plan_id" => array(
        "label" => "Plan",
        "validator" => "int",
        "bofAdmin" => array(
          "list" => array(
            "type" => "simple",
            "renderer" => function( $displayItem, $item, $displayData ){
              $displayData["data"] = $item["bof_dir_subs_plan"]["name"];
              return $displayData;
            }
          ),
          "object" => array(
            "required" => true
          )
        ),
        "bofInput" => array(
          "object",
          array(
            "type" => "user_subs_plan",
            "multi" => false
          )
        ),
        "relations" => array(
          "subs_plan" => array(
            "exec" => array(
              "type" => "direct",
              "parent_object" => "user_subs_plan",
              "child_object" => "user_subs",
              "child_object_selector_column" => "subs_plan_id",
              "delete_child_too" => true,
              "limit" => 1
            )
          )
        ),
        "selectors" => array(
          "subs_plan_id" => [ "subs_plan_id", "=" ]
        )
      ),
      "subs_plan_time_range" => array(
        "label" => "Subscription Period",
        "validator" => array(
          "in_array",
          array(
            "values" => [ "weekly", "monthly", "3months", "6months", "yearly", "2years" ]
          ),
        ),
        "input" => array(
          "type" => "select_i",
          "options" => array(
            [ "weekly", "Weekly" ],
            [ "monthly", "Monthly" ],
            [ "3months", "3-Months" ],
            [ "6months", "6-Months" ],
            [ "yearly", "Yearly" ],
            [ "2years", "2-Years" ],
          )
        ),
        "bofAdmin" => array(
          "object" => array(
            "required" => true
          )
        )
      ),
      "subs_plan_price" => array(
        "label" => "Paid Price",
        "validator" => array(
          "float",
          array(
            "empty()",
            "min" => 0
          ),
        ),
        "bofInput" => array(
          "currency"
        ),
        "bofAdmin" => array(
          "object" => []
        )
      ),
      /*
      "active" => array(
        "label" => "Active",
        "validator" => array(
          "boolean",
          array(
            "int" => true,
            "empty()"
          ),
        ),
        "bofAdmin" => array(
          "list" => array(
            "type" => "boolean",
          ),
        ),
      ),
      */
      "time_purchased" => array(
        "label" => "Purchase Time",
        "validator" => array(
          "timestamp",
          array(
            "empty()"
          ),
        ),
        "input" => array(
          "type" => "time",
        ),
        "bofAdmin" => array(
          "list" => array(
            "type" => "time",
          ),
          "object" => array(
            "required" => true
          )
        ),
      ),
      "time_expire" => array(
        "label" => "Expire Time",
        "validator" => "timestamp",
        "input" => array(
          "type" => "time",
        ),
        "bofAdmin" => array(
          "list" => array(
            "type" => "time_f",
            "renderer" => function ($displayItem, $item, $displayData) {

              if ( $item["bof_time_expire_seconds_ago"] > 0 )
              $displayData["sub_data"] = "<span style='color:red; font-weight:800; font-size:115%'>{$displayData["sub_data"]}</span>";
              else
              $displayData["sub_data"] = "<span style='color:green; font-weight:800; font-size:115%'>{$displayData["sub_data"]}</span>";

              return $displayData;
            }
          ),
          "object" => array(
            "required" => true,
          )
        ),
      ),
      "payment_id" => array(
        "validator" => array(
          "int",
          array(
            "empty()"
          )
        )
      ),
      "payment_count" => array(
        "label" => "Number<br>of pays",
        "validator" => array(
          "int",
          array(
            "empty()"
          )
          ),
          "bofAdmin" => array(
            "lists" => array(
              "type" => "tag",
              "renderer" => function( $displayItem, $item, $displayData ){
                $displayData["data"] = "money";
                return $displayData;
              }
            )
          )
      ),
      "payment_time" => array(
        "label" => "Last<br>Payment",
        "validator" => array(
          "timestamp",
          array(
            "empty()"
          ),
        ),
        "bofAdmin" => array(
          "lists" => array(
            "type" => "time",
          ),
        ),
      ),
      "gateway_name" => array(
        "validator" => array(
          "string",
          array(
            "empty()"
          )
        )
      ),
      "gateway_sub_id" => array(
        "label" => "Recurring",
        "validator" => array(
          "string",
          array(
            "empty()"
          )
        ),
        "bofAdmin" => array(
          "list" => array(
            "type" => "boolean_d",
            "renderer" => function ($displayItem, $item, $displayData) {
              if ( !$item["gateway_time_recur"] )
              $displayData["data"] = false;
              return $displayData;
            }
          )
        )
      ),
      "gateway_time_recur" => array(
        "label" => "",
        "validator" => array(
          "timestamp",
          array(
            "empty()"
          )
        ),
        "bofAdmin" => array(
          "list" => array(
            "type" => "simple",
            "class" => "details",
            "renderer" => function ($displayItem, $item, $displayData) {

              if (!$item["gateway_sub_id"])
              return;

              $displayData["data"] = "<ul>";
              $displayData["data"] .= "<li><b>ID</b> <span style='user-select:all'>".$item["gateway_sub_id"]."</span></li>";
              $displayData["data"] .= "<li><b>Next payment</b> ".($item["gateway_time_recur"]?bof()->general->time_in_future_hr(strtotime($item["gateway_time_recur"])):"-")."</li>";
              $displayData["data"] .= "<li><b>Last payment</b> ".bof()->general->time_in_future_hr(strtotime($item["payment_time"]))."</li>";
              $displayData["data"] .= "<li><b>Total payments</b> ".number_format($item["payment_count"])." <a href='payments?sub_id={$item["gateway_sub_id"]}' style='text-decoration:underline'>(list)</a></li>";
              $displayData["data"] .= "</ul>";
              return $displayData;

            }
          ),
        ),
      )
    );
  }
  public function bof_columns(){
    return array(
      "ID"
    );
  }
  public function selectors(){
    return array(
      "user_id" => [ "user_id", "=" ],
      "has_time" => function( $val ){

        if ( $val > 0 )
        return [ "time_expire", ">", "NOW()", true ];
        return [ "time_expire", "<", "NOW()", true ];

      },
      "time_purchased_range" => [ "time_purchased", "timestamp_range" ],
      "time_expire_range" => [ "time_expire", "timestamp_range" ],
      "col_user" => [ "user_id", "by_column" ],
      "col_subs_plan" => [ "subs_plan_id", "by_column" ],
      "gateway_sub_id" => [ "gateway_sub_id", "=" ],
      "recurring" => function( $val ){
        if ( $val )
        return [ "gateway_time_recur", ">", "SUBDATE( now(), INTERVAL 2 DAY )", true ];
      }
    );

    $this->select_shortcuts["has_time"] = function( $val ){

      if ( $val > 0 )
      return [ "time_expire", ">", "NOW()", true ];
      return [ "time_expire", "<", "NOW()", true ];

    };
    $this->select_shortcuts["time_purchased_range"] = function( $val ){
      return bof()->object_helper->__select_helper( "timestamp_range", $val, "time_purchased", "user_subs", [] );
    };
    $this->select_shortcuts["time_expire_range"] = function( $val ){
      return bof()->object_helper->__select_helper( "timestamp_range", $val, "time_expire", "user_subs", [] );
    };
    $this->select_shortcuts["col_user"] = function( $val ){
      return bof()->object_helper->__select_helper( "by_column", $val, "user_id", "user_subs", [] );
    };
    $this->select_shortcuts["col_subs_plan"] = function( $val ){
      return bof()->object_helper->__select_helper( "by_column", $val, "subs_plan_id", "user_subs", [] );
    };
  }
  public function bof_admin(){
    return array(
      "config" => array(
        "search" => false,
        "create" => true,
        "edit" => true,
        "delete" => true,
        "pagination" => true,
        "edit_page_url" => "user_sub",
        "list_page_url" => "user_subs",
        "multi" => array(
          "select" => true,
          "delete" => true,
          "edit"   => false
        )
      ),
      "filters" => array(
        "has_time" => array(
          "title" => "Active ( not expired yet )",
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
        "col_user" => array(
          "title" => "User",
          "input" => array(
            "type" => "bof_input",
          ),
          "bofInput" => array(
            "object",
            array(
              "type" => "user",
              "multi" => true,
            )
          )
        ),
        "col_subs_plan" => array(
          "title" => "Subscription Plan",
          "input" => array(
            "type" => "bof_input",
          ),
          "bof_input" => array(
            "object",
            array(
              "type" => "user_subs_plan",
              "multi" => true,
              "args" => array(
                "filter" => "col_subs_plan",
              )
            )
          )
        ),
        "time_purchased_range" => array(
          "title" => "Time purchased range",
          "input" => array(
            "type" => "time_range",
          ),
          "validator" => array(
            "string",
            array(
              "strict" => true,
              "strict_regex" => "20()-12-07 06:40 - 2021-12-23 06:40",
              "strict_regex" => "/^20([0-9]{2})\-(0|1)([0-9]{1})\-(0|1|2|3)([0-9]{1}) (0|1|2)([0-9]{1}):([0-6]{1})([0-9]{1}) \- 20([0-9]{2})\-(0|1)([0-9]{1})\-(0|1|2|3)([0-9]{1}) (0|1|2)([0-9]{1}):([0-6]{1})([0-9]{1})$/",
              "strict_regex_raw" => true,
              "empty()"
            )
          )
        ),
        "time_expire_range" => array(
          "title" => "Time expire range",
          "input" => array(
            "type" => "time_range",
          ),
          "validator" => array(
            "string",
            array(
              "strict" => true,
              "strict_regex" => "20()-12-07 06:40 - 2021-12-23 06:40",
              "strict_regex" => "/^20([0-9]{2})\-(0|1)([0-9]{1})\-(0|1|2|3)([0-9]{1}) (0|1|2)([0-9]{1}):([0-6]{1})([0-9]{1}) \- 20([0-9]{2})\-(0|1)([0-9]{1})\-(0|1|2|3)([0-9]{1}) (0|1|2)([0-9]{1}):([0-6]{1})([0-9]{1})$/",
              "strict_regex_raw" => true,
              "empty()"
            )
          )
        ),
      ),
      "buttons_renderer" => function( $item, $buttons ){

        if ( $item["gateway_sub_id"] ){
          $buttons["payments"] = array(
            "label" => "List Payments",
            "link" => "payments?sub_id={$item["gateway_sub_id"]}",
            "icon" => "star"
          );          
        }

        return $buttons;

      },
    );
  }

  public $admin_structures = array(

    "list" => array(
      "items" => array(

        "user_id" => array(
          "head" => "User",
          "type" => "simple",
        ),

        "subs_plan_id" => array(
          "head" => "Subscription<br>Plan name",
          "type" => "simple",
        ),

        "ID" => array(
          "head" => "Active",
          "type" => "boolean_d"
        ),

        "time_purchased" => array(
          "head" => "Purchase<br>Date",
          "type" => "time",
        ),

        "time_expire" => array(
          "head" => "Expiriration<br>Date",
          "type" => "time",
        ),

      ),
      "buttons" => array(),

    ),
    "object" => array(
      "endpoint" => "bofAdmin/object/user_subs/",
      "inputs" => array(
        "user_id" => array(
          "label" => "User",
          "multi" => false,
          "col_name" => "user_id",
          "input" => array(
            "type" => "bof_input",
            "name" => "user_id"
          ),
          "bof_input" => array(
            "object",
            array(
              "type" => "user",
              "multi" => false
            )
          )
        ),
        "subs_plan_id" => array(
          "label" => "Subscription Plan",
          "multi" => true,
          "col_name" => "subs_plan_id",
          "input" => array(
            "type" => "bof_input",
            "name" => "subs_plan_id"
          ),
          "bof_input" => array(
            "object",
            array(
              "type" => "user_subs_plan",
              "multi" => false
            )
          )
        ),
        "subs_plan_time_range" => array(
          "label" => "Subscription Period",
          "multi" => true,
          "col_name" => "subs_plan_time_range",
          "input" => array(
            "type" => "select_i",
            "name" => "subs_plan_time_range",
            "options" => array(
              [ "weekly", "weekly" ],
              [ "monthly", "monthly" ],
              [ "3months", "3months" ],
              [ "6months", "6months" ],
              [ "yearly", "yearly" ],
              [ "2years", "2years" ],
            )
          )
        ),
        "time_purchased" => array(
          "label" => "Purchase time",
          "multi" => false,
          "col_name" => "time_purchased",
          "input" => array(
            "name" => "time_purchased",
            "type" => "time",
            "placeholder" => "yyyy-mm-dd hh:mm:ss"
          )
        ),
        "time_expire" => array(
          "label" => "Expiration time",
          "multi" => false,
          "col_name" => "time_expire",
          "input" => array(
            "name" => "time_expire",
            "type" => "time",
            "placeholder" => "yyyy-mm-dd hh:mm:ss"
          )
        ),
      )
    ),

  );

  public function select( $whereArgs=[], $selectArgs=[] ){

    $search = null;
    $listing = null;
    $deleting = null;
    $editing = null;
    $_eq = [];
    extract( $selectArgs );

    if ( $listing ){
      $_eq["subs_plan"] = [];
      $_eq["user"] = [ "_eq" => [ "avatar" => [] ] ];
    }

    $selectArgs["_eq"] = $_eq;
    return bof()->object->_select( $this, $whereArgs, $selectArgs );

  }
  public function insert( $insertArray=[] ){

    $ranges = array(
      "weekly" => "1 WEEK",
      "monthly" => "1 MONTH",
      "3months" => "3 MONTH",
      "6months" => "6 MONTH",
      "yearly" => "1 YEAR",
      "2years" => "2 YEAR",
    );

    if ( empty( $insertArray["time_expire"] ) && !empty( $insertArray["subs_plan_time_range"] ) )
    $insertArray["time_expire"] = bof()->general->mysql_timestamp( strtotime( "+" . $ranges[ $insertArray["subs_plan_time_range"] ] ) );

    return bof()->object->_insert( $this, $insertArray );

  }

  public function admin_list_item_display( $item, $displayData, $displayName, $request ){

    if ( $displayName == "user_id" ){
      $displayData["data"] = $item["user"]["name_styled"];
      if ( !empty( $item["user"]["avatar"]["image_strings"][10] ) ){
        $displayData["data"] =
        "<div class='data_with_cover'>" .
          "<div class='cover_wrapper'>".
          $item["user"]["avatar"]["image_strings"][10]["html"].
          "</div>" .
          "<div class='the_data'>" .
          $displayData["data"] .
          "</div>" .
        "</div>";
      }
    }
    if ( $displayName == "ID" )
    $displayData["data"] = $item["bof_time_expire_seconds_ago"] < 0;

    if ( $displayName == "subs_plan_id" )
    $displayData["data"] = $item["subs_plan"]["name"];

    return $displayData;

  }
  public function admin_list_item_buttons( $item, $buttons ){

    return $buttons;

  }
  public function admin_object_ui_before( $object_structure, $request ){

    if ( $request["type"] == "single" )
    $_item = $request["content"][ $request["IDS"][0] ];

    if ( $request["type"] == "new" )
    $object_structure["inputs"]["time_purchased"]["input"]["placeholder"] = bof()->general->mysql_timestamp();

    return $object_structure;

  }
  public function admin_object_be( $_inputs, $request ){

    if ( empty( $_inputs["data"]["subs_plan_id"] ) )
    $_inputs["report"]["fail"]["subs_plan_id"] = "Select one";

    if ( $request["type"] == "multi" )
    return $_inputs;

    if ( empty( $_inputs["data"]["user_id"] ) )
    $_inputs["report"]["fail"]["user_id"] = "Select one";

    return $_inputs;

  }
  public function admin_object_be_action( $action, $ids ){


  }

}

?>
