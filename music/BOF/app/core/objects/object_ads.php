<?php

if ( !defined( "bof_root" ) ) die;

class object_ads extends bof_type_object {

  public function bof(){
    return array(
      "name" => "ads",
      "label" => "advertisement",
      "icon" => "star",
      "db_table_name" => "_bof_ads",
    );
  }
  public function columns(){
    return array(

      "name" => array(
        "public" => true,
        "label" => "Name",
        "input" => array(
          "type" => "text"
        ),
        "validator" => "string",
        "bofAdmin" => array(
          "list" => array(
            "type" => "simple"
          ),
          "object" => array(
            "required" => true
          )
        )
      ),
      "data" => array(
        "validator" => array(
          "json",
          array(
            "encode" => true,
            "empty()"
          )
        )
      ),
      "url" => array(
        "public" => true,
        "label" => "Target URL",
        "tip" => "The URL or link of website that users will be redirected to if they interact with advertisement",
        "input" => array(
          "type" => "text"
        ),
        "validator" => array(
          "url",
          array(
            "empty()",
            "default_scheme_add" => true,
            "default_scheme" => "https"
          )
        ),
        "bofAdmin" => array(
          "object" => array()
        )
      ),
      "type" => array(
        "public" => true,
        "label" => "Type",
        "tip" => "<b>Banner</b>: Upload an image which will be displayed in chosen `placement` and can be clicked by viewers<br>
        <b>JavaScript</b>: Can be used for JS, HTML and etc<br>
        <b>Google AdUnit</b>: Can be used to display Google AdSense Ad-Unit<br>
        <b>Audio</b>: Upload an audio which will be played between client queue list. A clickabble banner will also be displayed when audio ad is active<br>
        <b>Video</b>: Upload a video which will be played between client queue list. A clickabble banner will also be displayed when video ad is active<br>
        <b>YouTube</b>: Enter ID of YouTube video which will be played between client queue list. A clickabble banner will also be displayed when video is active<br>
        <b>AdMob Banner</b>: Only works in Android App. Banner ads are rectangular image or text ads that occupy a spot within an app's layout. They stay on screen while users are interacting with the app, and can refresh automatically after a certain period of time<br>
        <b>AdMob Interstitial</b>: Only works in Android App. Interstitial ads are full-screen ads that cover the interface of their host app. When an app shows an interstitial ad, the user has the choice to either tap on the ad and continue to its destination or close it and return to the app.<br>

        ",
        "input" => array(
          "type" => "select_i",
          "value" => "banner",
          "options" => array(
            [ "banner", "Banner" ],
            [ "script", "JavaScript" ],
            [ "gau", "Google Ad Unit" ],
            [ "audio", "Audio" ],
            [ "video", "Video" ],
            [ "youtube", "YouTube" ],
            [ "admob", "AdMob" ],
          )
        ),
        "validator" => array(
          "in_array",
          array(
            "values" => [ "banner", "audio", "video", "youtube", "script", "popup", "gau", "admob" ]
          )
        ),
        "bofAdmin" => array(
          "list" => array(
            "type" => "tag",
            "renderer" => function( $displayItem, $item, $displayData ){
              $displayData["data"] = $displayData["data"] == "gau" ? "Google-Ad" : $displayData["data"];
              $displayData["data"] = $displayData["data"] == "admob" ? "AdMob" : $displayData["data"];
              return $displayData;
            }
          ),
          "object" => array(
            "required" => true,
          )
        ),
        "selectors" => array(
          "type" => [ "type", "=" ],
          "col_type" => [ "type", "by_column" ]
        )
      ),
      "place_id" => array(
        "label" => "Placement",
        "tip" => "Where should this banner be displayed?",
        "input" => array(
          "type" => "select_i",
          "options" => []
        ),
        "validator" => array(
          "string",
          array(
            "empty()"
          )
        ),
        "bofAdmin" => array(
          "list" => array(
            "type" => "tag",
            "renderer" => function( $displayItem, $item, $displayData ){

              if ( $item["type"] != "banner" && $item["type"] != "script" && $item["type"] != "gau" )
              $displayData["data"] = null;

              return $displayData;

            },
          ),
          "object" => array(
            "display_on" => array(
              "type" => [ "in_array", [ "banner", "script", "gau" ] ],
            ),
          )
        ),
      ),

      "fund_total" => array(
        "label" => "Fund - Total",
        "tip" => "Total funding for this advertisement campaign. Campaign will be de-activated when fund runs out",
        "bofInput" => array(
          "currency",
          []
        ),
        "validator" => array(
          "float",
          array()
        ),
        "bofAdmin" => array(
          "object" => array(
            "required" => true
          )
        ),
        "selectors" => array(
          "fund_total" => [ "fund_total", "=" ]
        )
      ),
      "fund_spent" => array(
        "label" => "Fund - Spent",
        "bofInput" => array(
          "currency",
          []
        ),
        "bofAdmin" => array(
          "object" => array()
        ),
        "validator" => array(
          "float",
          array(
            "empty()",
            "min" => 0,
            "forceZero" => true
          )
        ),
        "selectors" => array(
          "fund_spent" => [ "fund_spent", "=" ]
        )
      ),
      "fund_remain" => array(
        "label" => "Fund<br>Remaining",
        "bofInput" => array(
          "currency",
          []
        ),
        "validator" => array(
          "float",
          array(
            "empty()",
            "min" => 0,
            "forceZero" => true
          )
        ),
        "bofAdmin" => array(
          "object" => array()
        ),
        "selectors" => array(
          "fund_remain" => [ "fund_remain", "=" ],
          "has_fund_remain" => function( $val ){
            return [ "fund_remain", ">", "0" ];
          }
        )
      ),
      "fund_limit" => array(
        "label" => "Fund - Daily limit",
        "input" => array(
          "type" => "text"
        ),
        "validator" => array(
          "float",
          array(
            "empty()",
            "min" => 0,
            "forceZero" => true
          )
        ),
        "bofAdmin" => array(
          "object" => []
        ),
        "selectors" => array(
          "fund_limit" => [ "fund_limit", "=" ],
        )
      ),
      "fund_spent_day" => array(
        "label" => "Fund<br>Spent today",
        "input" => array(
          "type" => "text"
        ),
        "validator" => array(
          "float",
          array(
            "empty()",
            "min" => 0,
            "forceZero" => true
          )
        ),
        "bofAdmin" => array(
        ),
        "selectors" => array(
          "fund_spent_day" => [ "fund_spent_day", "=" ],
        )
      ),
      "fund_spent_day_code" => array(
        "validator" => array(
          "string",
          array(
            "empty()"
          )
        ),
        "selectors" => array(
          "fund_spent_day_code" => [ "fund_spent_day_code", "=" ],
        )
      ),
      "sta_clicks" => array(
        "label" => "Clicks",
        "validator" => array(
          "int",
          array(
            "min" => 0,
            "empty()"
          )
        ),
        "bofAdmin" => array(
        )
      ),
      "sta_views" => array(
        "label" => "Views",
        "validator" => array(
          "int",
          array(
            "empty()",
            "min" => 0
          )
        ),
        "bofAdmin" => array(
        )
      ),
      "active" => array(
        "label" => "Active",
        "tip" => "Make sure campaign has <b>`Remaining` funds</b> or it will be deactivated automatically",
        "validator" => array(
          "boolean",
          array(
            "empty()",
            "int" => true
          ),
        ),
        "input" => array(
          "type" => "checkbox"
        ),
        "bofAdmin" => array(
          "list" => array(
            "type" => "boolean",
            "args" => array(
              "payloads" => [ "activate", "deactivate" ]
            ),
            "renderer" => function( $displayItem, $item, $displayData ){
              if ( !$item["fund_remain"] )
              $displayData["data"] = 0;
              return $displayData;
            }
          ),
          "object" => array()
        ),
        "selectors" => array(
          "active" => [ "active", "=" ]
        )
      ),

    );
  }
  public function bof_columns(){
    return array(
      "ID",
      "time_add",
    );
  }
  public function selectors(){
    return array(
      "query" => [ "name", "LIKE%lower" ],
      "place_id" => [ "place_id", "=" ],
      "displayable" => function( $val ){
        return array(
          "oper" => "AND",
          "cond" => array(
            [ "active", "=", "1" ],
            [ "fund_remain", ">", "0" ],
            array(
              "oper" => "OR",
              "cond" => array(
                [ "fund_limit", "=", "0" ],
                [ "fund_spent_day_code", "!=", bof()->general->daycode() ],
                [ "fund_limit", ">", "fund_spent_day", true ]
              )
            )
          )
        );
      },
      "for_muse" => function( $val ){
        if ( $val ){
          return [ "type", "IN", ["'audio'","'video'","'youtube'"], true ];
        }
      }
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
        "edit_page_url" => "ads",
        "list_page_url" => "ads_list",
        "multi" => array(
          "select" => true,
          "delete" => true,
          "edit"   => false
        )
      ),
      "filters" => array(
        "active" => array(
          "name" => "active",
          "title" => "Status",
          "input" => array(
            "name" => "active",
            "type" => "select_i",
            "options" => array(
              [ 0, "in-active" ],
              [ 1, "active" ],
              [ "__all__", "all" ]
            ),
            "value" => "__all__"
          ),
          "validator" => array(
            "in_array",
            array(
              "values" => [ "__all__", "0", "1" ]
            )
          )
        ),
      ),
      "buttons" => array(
        "activate" => array(
          "id" => "activate",
          "label" => "Activate",
          "payload" => array(
            "post" => array(
              "__action" => "activate"
            )
          )
        ),
        "deactivate" => array(
          "id" => "deactivate",
          "label" => "De-Activate",
          "payload" => array(
            "post" => array(
              "__action" => "deactivate"
            )
          )
        ),
      ),
      "buttons_renderer" => function( $item, $buttons ){

        if ( $item["active"] )
        unset( $buttons["activate"] );

        if ( !$item["active"] )
        unset( $buttons["deactivate"] );

        return $buttons;

      },
      "list" => array(
        "name" => null,
        "type" => null,
        "place_id" => null,
        "fund" => array(
          "type" => "simple",
          "class" => "details",
          "label" => "Funds",
          "renderer" => function( $displayItem, $item, $displayData ){
            $displayData["data"] = "<ul>";
            $displayData["data"] .= "<li><b>Total</b>" . bof()->object->currency->parse_price($item["fund_total"],["zero_is_free"=>false])["string"] . "</li>";
            $remainColor = $item["fund_remain"] ? "green" : "red";
            $displayData["data"] .= "<li><b>Remain</b><span style='color: rgb(var(--c_{$remainColor}))'>" . bof()->object->currency->parse_price($item["fund_remain"],["zero_is_free"=>false])["string"] . "</span></li>";
            if ( bof()->general->daycode() != $item["fund_spent_day_code"] )
            $item["fund_spent_day"] = 0;
            $displayData["data"] .= "<li><b>Spent - Today</b>" . bof()->object->currency->parse_price($item["fund_spent_day"],["zero_is_free"=>false])["string"] . "</li>";
            if ( $item["fund_limit"] )
            $displayData["data"] .= "<li><b>Limit - Today</b>" . bof()->object->currency->parse_price($item["fund_limit"],["zero_is_free"=>false])["string"] . "</li>";
            $displayData["data"] .= "</ul>";
            return $displayData;
          },
        ),
        "sta" => array(
          "type" => "simple",
          "class" => "details",
          "label" => "Sta",
          "renderer" => function( $displayItem, $item, $displayData ){
            $displayData["data"] = "<ul>";
            $displayData["data"] .= "<li><b>Click</b>" . number_format( $item["sta_clicks"] ) . "</li>";
            $displayData["data"] .= "<li><b>View</b>" . number_format( $item["sta_views"] ) . "</li>";
            $displayData["data"] .= "</ul>";
            return $displayData;
          },
        ),
        "active" => null,
      ),
      "object" => array(
        "name" => null,
        "url" => null,
        "type" => null,
        "place_id" => null,
        "banner_file" => array(
          "label" => "Banner",
          "tip" => "An image fit for chosen `placement`",
          "bofInput" => array(
            "file",
            array(
              "type" => "image",
              "object_type" => "thingie",
            )
          ),
          "display_on" => array(
            "type" => [ "equal", "banner" ]
          ),
        ),
        "audio_file" => array(
          "label" => "Audio",
          "tip" => "An audio to be played between clients' queue list. We suggest uploading a maximum of 20 seconds",
          "bofInput" => array(
            "file",
            array(
              "type" => "audio",
              "object_type" => "thingie",
            )
          ),
          "display_on" => array(
            "type" => [ "equal", "audio" ]
          ),
        ),
        "video_file" => array(
          "label" => "Video",
          "tip" => "An video to be played between clients' queue list. We suggest uploading a maximum of 20 seconds",
          "bofInput" => array(
            "file",
            array(
              "type" => "video",
              "object_type" => "thingie",
            )
          ),
          "display_on" => array(
            "type" => [ "equal", "video" ]
          ),
        ),
        "youtube_id" => array(
          "label" => "YouTube ID",
          "tip" => "An youtube to be played between clients' queue list. We suggest uploading a maximum of 20 seconds",
          "input" => array(
            "name" => "youtube_id",
            "type" => "text"
          ),
          "validator" => array(
            "youtube_uri",
            array(
              "empty()"
            )
          ),
          "display_on" => array(
            "type" => [ "equal", "youtube" ]
          ),
        ),
        "audio_banner" => array(
          "label" => "Banner",
          "tip" => "An small banner to be displayed while media advertisement is active",
          "bofInput" => array(
            "file",
            array(
              "type" => "image",
              "object_type" => "thingie",
            )
          ),
          "display_on" => array(
            "type" => [ "in_array", [ "audio", "video", "youtube" ] ]
          ),
        ),
        "gau_client_id" => array(
          "label" => "Client-ID",
          "tip" => "Get a AdUnit code from Google and copy your client-id from that. <b>data-ad-client=\"{Here Be Your ID}\"</b>",
          "input" => array(
            "type" => "text",
            "placeholder" => "ca-pub-"
          ),
          "validator" => array(
            "string",
            array(
              "empty()"
            )
          ),
          "display_on" => array(
            "type" => [ "equal", "gau" ]
          )
        ),
        "gau_ad_id" => array(
          "label" => "AD-ID",
          "tip" => "Get a AdUnit code from Google and copy your AD-id from that. <b>data-ad-slot=\"{Here Be Your ID}\"</b>",
          "input" => array(
            "type" => "text",
          ),
          "validator" => array(
            "int",
            array(
              "empty()"
            )
          ),
          "display_on" => array(
            "type" => [ "equal", "gau" ]
          )
        ),
        "admob_type" => array(
          "label" => "AdMob AD Unit Type",
          "input" => array(
            "type" => "select_i",
            "value" => "banner",
            "options" => array(
              [ "banner", "Banner" ],
              [ "interstitial", "Interstitial" ]
            )
          ),
          "validator" => array(
            "in_array",
            array(
              "empty()",
              "values" => [ "banner", "interstitial" ]
            )
          ),
          "display_on" => array(
            "type" => [ "in_array", [ "admob", "admob_b" ] ]
          )
        ),
        "admob_id" => array(
          "label" => "AdMob AD Unit ID",
          "input" => array(
            "type" => "text",
          ),
          "validator" => array(
            "string",
            array(
              "empty()"
            )
          ),
          "display_on" => array(
            "type" => [ "in_array", [ "admob", "admob_b" ] ]
          )
        ),
        "javascript_code" => array(
          "label" => "Javascript",
          "tip" => "The javascript/HTML code to be used inside chosen `placement`",
          "input" => array(
            "type" => "textarea"
          ),
          "validator" => array(
            "raw",
            array(
              "empty()"
            )
          ),
          "display_on" => array(
            "type" => [ "equal", "script" ]
          )
        ),
        "popup_type" => array(
          "label" => "PopUp - Type",
          "input" => array(
            "type" => "select_i",
            "options" => array(
              [ "image", "Image" ],
              [ "text", "Text" ]
            ),
            "value" => "image"
          ),
          "validator" => array(
            "in_array",
            array(
              "values" => [ "image", "text" ],
              "empty()"
            )
          ),
          "display_on" => array(
            "type" => [ "equal", "popup" ]
          )
        ),
        "popup_banner" => array(
          "label" => "PopUp - Banner",
          "bofInput" => array(
            "file",
            array(
              "type" => "image",
              "object_type" => "thingie",
            )
          ),
          "display_on" => array(
            "type" => [ "equal", "popup" ],
            "popup_type" => [ "equal", "image" ]
          )
        ),
        "popup_text" => array(
          "label" => "PopUp - Text",
          "input" => array(
            "type" => "text_editor"
          ),
          "validator" => array(
            "editor_js",
            array(
              "empty()"
            )
          ),
          "display_on" => array(
            "type" => [ "equal", "popup" ],
            "popup_type" => [ "equal", "text" ]
          )
        ),
      ),
      "object_item_renderer" => function( $item_name, &$item_data, $request ){

        if ( $item_name == "place_id" ){
          $placements = bof()->object->ads->get_placements();
          $item_data["input"]["options"] = bof()->general->bofify_options( $placements );
        }

        if ( $request["type"] != "single" )
        return;

        $content = $request["content"][ $request["IDS"][0] ];

        if ( $item_name == "banner_file" && $content["type"] == "banner" && !empty( $content["data_decoded"]["file"] ) )
        $item_data["input"]["value"] = $content["data_decoded"]["file"];

        if ( $item_name == "audio_file" && $content["type"] == "audio" && !empty( $content["data_decoded"]["file"] ) )
        $item_data["input"]["value"] = $content["data_decoded"]["file"];

        if ( $item_name == "youtube_id" && $content["type"] == "youtube" && !empty( $content["data_decoded"]["youtube_id"] ) )
        $item_data["input"]["value"] = $content["data_decoded"]["youtube_id"];

        if ( $item_name == "video_file" && $content["type"] == "video" && !empty( $content["data_decoded"]["file"] ) )
        $item_data["input"]["value"] = $content["data_decoded"]["file"];

        if ( $item_name == "audio_banner" && in_array( $content["type"], ["audio","video","youtube"], true ) && !empty( $content["data_decoded"]["banner"] ) )
        $item_data["input"]["value"] = $content["data_decoded"]["banner"];

        if ( $item_name == "javascript_code" && $content["type"] == "script" && !empty( $content["data_decoded"]["script"] ) )
        $item_data["input"]["value"] = $content["data_decoded"]["script"];

        if ( $item_name == "popup_type" && $content["type"] == "popup" && !empty( $content["data_decoded"]["type"] ) )
        $item_data["input"]["value"] = $content["data_decoded"]["type"];

        if ( $item_name == "popup_banner" && $content["type"] == "popup" && !empty( $content["data_decoded"]["banner"] ) )
        $item_data["input"]["value"] = $content["data_decoded"]["banner"];

        if ( $item_name == "popup_text" && $content["type"] == "popup" && !empty( $content["data_decoded"]["text"] ) )
        $item_data["input"]["value"] = $content["data_decoded"]["text"];

        if ( $item_name == "gau_client_id" && $content["type"] == "gau" && !empty( $content["data_decoded"]["client"] ) )
        $item_data["input"]["value"] = $content["data_decoded"]["client"];

        if ( $item_name == "gau_ad_id" && $content["type"] == "gau" && !empty( $content["data_decoded"]["ad"] ) )
        $item_data["input"]["value"] = $content["data_decoded"]["ad"];

        if ( $item_name == "admob_id" && ( $content["type"] == "admob" || $content["type"] == "admob_b" ) && !empty( $content["data_decoded"]["id"] ) )
        $item_data["input"]["value"] = $content["data_decoded"]["id"];

        if ( $item_name == "admob_type" && ( $content["type"] == "admob" || $content["type"] == "admob_b" ) && !empty( $content["data_decoded"]["type"] ) )
        $item_data["input"]["value"] = $content["data_decoded"]["type"];

      },
      "object_be_renderer" => function( $_inputs, $request ){

        if ( $request["type"] == "multi" ) return;

        $data = [];

        if ( $_inputs["data"]["type"] == "banner" ){

          if ( !$_inputs["data"]["place_id"] )
          $_inputs["report"]["fail"]["place_id"] = "Select one";

          if ( !$_inputs["data"]["banner_file"] )
          $_inputs["report"]["fail"]["banner_file"] = "Upload a banner";

          $data = array(
            "file" => $_inputs["data"]["banner_file"]
          );

        }
        if ( $_inputs["data"]["type"] == "audio" ){

          if ( !$_inputs["data"]["audio_file"] )
          $_inputs["report"]["fail"]["audio_file"] = "Upload an audio";

          if ( !$_inputs["data"]["audio_banner"] )
          $_inputs["report"]["fail"]["audio_banner"] = "Upload a banner";

          $data = array(
            "file" => $_inputs["data"]["audio_file"],
            "banner" => $_inputs["data"]["audio_banner"]
          );

        }
        if ( $_inputs["data"]["type"] == "video" ){

          if ( !$_inputs["data"]["video_file"] )
          $_inputs["report"]["fail"]["video_file"] = "Upload an video";

          if ( !$_inputs["data"]["audio_banner"] )
          $_inputs["report"]["fail"]["audio_banner"] = "Upload a banner";

          $data = array(
            "file" => $_inputs["data"]["video_file"],
            "banner" => $_inputs["data"]["audio_banner"]
          );

        }
        if ( $_inputs["data"]["type"] == "youtube" ){

          if ( !$_inputs["data"]["youtube_id"] )
          $_inputs["report"]["fail"]["youtube_id"] = "Enter youtube id";

          if ( !$_inputs["data"]["audio_banner"] )
          $_inputs["report"]["fail"]["audio_banner"] = "Upload a banner";

          $data = array(
            "youtube_id" => $_inputs["data"]["youtube_id"],
            "banner" => $_inputs["data"]["audio_banner"]
          );

        }
        if ( $_inputs["data"]["type"] == "script" ){

          $data = array(
            "script" => $_inputs["data"]["javascript_code"]
          );

        }
        if ( $_inputs["data"]["type"] == "gau" ){

          if ( !$_inputs["data"]["gau_client_id"] )
          $_inputs["report"]["fail"]["gau_client_id"] = "Enter";

          if ( !$_inputs["data"]["gau_ad_id"] )
          $_inputs["report"]["fail"]["gau_ad_id"] = "Enter";

          $data = array(
            "client" => $_inputs["data"]["gau_client_id"],
            "ad" => $_inputs["data"]["gau_ad_id"],
          );

        }
        if ( $_inputs["data"]["type"] == "admob" || $_inputs["data"]["type"] == "admob_i" ){

          if ( !$_inputs["data"]["admob_id"] )
          $_inputs["report"]["fail"]["admob_id"] = "Enter";

          if ( !$_inputs["data"]["admob_type"] )
          $_inputs["report"]["fail"]["admob_type"] = "Enter";

          $data = array(
            "id" => $_inputs["data"]["admob_id"],
            "type" => $_inputs["data"]["admob_type"],
          );

        }
        if ( $_inputs["data"]["type"] == "popup" ){

          if ( $_inputs["data"]["popup_type"] == "image" && !$_inputs["data"]["popup_banner"] )
          $_inputs["report"]["fail"]["popup_banner"] = "Upload an image";

          $data = array(
            "type" => $_inputs["data"]["popup_type"],
            "banner" => $_inputs["data"]["popup_banner"],
            "text" => $_inputs["data"]["popup_text"]
          );

        }

        $_inputs["data"]["data"] = $_inputs["set"]["data"] = $_inputs["update"]["data"] = json_encode( $data );
        return $_inputs;

      },
      "object_be_renderer_after" => function( $_inputs, $request, $IDS ){

        $ID = is_array( $IDS ) ? reset( $IDS ) : $IDS;
        $data = json_decode( $_inputs["data"]["data"], 1 );

        if ( $_inputs["data"]["type"] == "banner" && $_inputs["data"]["banner_file"] ){

          $_validate_file = bof()->object->file->finalize_upload(
            "image",
            "thingie",
            "thingie" . $ID,
            $_inputs["data"]["banner_file"],
            null,
            array()
          );

        }

        if ( $_inputs["data"]["type"] == "audio" && $_inputs["data"]["audio_file"] ){

          $_validate_file = bof()->object->file->finalize_upload(
            "audio",
            "thingie",
            "thingie" . $ID,
            $_inputs["data"]["audio_file"],
            null,
            array(
              "protect" => false,
              "convert" => false,
              "real" => false,
              "lower" => false,
              "encrypt" => false,
              "preview" => false,
            )
          );

        }

        if ( $_inputs["data"]["type"] == "video" && $_inputs["data"]["video_file"] ){

          $_validate_file = bof()->object->file->finalize_upload(
            "video",
            "thingie",
            "thingie" . $ID,
            $_inputs["data"]["video_file"],
            null,
            array(
              "protect" => false,
              "convert" => true,
              "real" => false,
              "lower" => false,
              "encrypt" => false,
              "preview" => false,
            )
          );

        }

        if ( in_array( $_inputs["data"]["type"], ["audio","video","youtube"], true ) && $_inputs["data"]["audio_banner"] ){

          $_validate_file = bof()->object->file->finalize_upload(
            "image",
            "thingie",
            "thingie" . $ID,
            $_inputs["data"]["audio_banner"],
            null,
            array()
          );

        }

        if ( $_inputs["data"]["type"] == "popup" &&  $data["type"] == "image" && $_inputs["data"]["popup_banner"] ){

          $_validate_file = bof()->object->file->finalize_upload(
            "image",
            "thingie",
            "thingie" . $ID,
            $_inputs["data"]["popup_banner"],
            null,
            array()
          );

        }

      },
      "actions" => array(
        "activate" => function( $ids ){
          $this->_bof_this->update(array(
            "ID_in" => $ids
          ),array(
            "active" => 1
          ));
          return [ true, "Activated" ];
        },
        "deactivate" => function( $ids ){
          $this->_bof_this->update(array(
            "ID_in" => $ids,
          ),array(
            "active" => 0
          ));
          return [ true, "De-Activated" ];
        },
      ),
    );
  }

  public function clean( $item, $args=[] ){

    $for_display = false;
    $for_click = false;
    $get_files = false;
    extract( $args );

    if ( $for_display || $get_files ){

      if ( $item["type"] == "banner" && !empty( $item["data_decoded"]["file"] ) )
      $item["banner_file"] = bof()->object->file->select(["ID"=>$item["data_decoded"]["file"]]);

      if ( $item["type"] == "audio" && !empty( $item["data_decoded"]["file"] ) ){
        $item["audio_file"] = bof()->object->file->select(["ID"=>$item["data_decoded"]["file"]]);
      }

      if ( $item["type"] == "video" && !empty( $item["data_decoded"]["file"] ) ){
        $item["video_file"] = bof()->object->file->select(["ID"=>$item["data_decoded"]["file"]]);
      }

      if ( in_array( $item["type"], [ "audio", "video", "youtube" ], true ) && !empty( $item["data_decoded"]["banner"] ) ){
        $item["banner_file"] = bof()->object->file->select(["ID"=>$item["data_decoded"]["banner"]]);
      }

    }

    if ( $for_display || $for_click ){

      if ( $item["fund_spent_day_code"] != bof()->general->daycode() )
      $this->_bof_this->update(
        array(
          "ID" => $item["ID"]
        ),
        array(
          "fund_spent_day" => 0,
          "fund_spent_day_code" => bof()->general->daycode()
        )
      );

    }

    if ( $for_display ){

      $display_fee = bof()->object->db_setting->get( "ads_{$item["type"]}_v_f" );
      if ( $display_fee ){
        bof()->db->query("UPDATE {$this->bof()["db_table_name"]} SET fund_spent_day = fund_spent_day + {$display_fee}, fund_remain = fund_remain - {$display_fee}, sta_views = sta_views + 1 WHERE ID = '{$item["ID"]}' ");
      }
      else {
        bof()->db->query("UPDATE {$this->bof()["db_table_name"]} SET sta_views = sta_views + 1 WHERE ID = '{$item["ID"]}' ");
      }

      if ( $item["type"] == "banner" && !empty( $item["banner_file"] ) )
      $item = "<a target='_blank' href='api/redirect_to/{$item["ID"]}/?t=".microtime(true)."'><div class='thingie_holder' style='background-image:url(\"{$item["banner_file"]["image_original"]}\")'></div></a>";

      elseif ( $item["type"] == "script" )
      $item = $item["data_decoded"]["script"];

      elseif ( $item["type"] == "gau" )
      $item = array(
        "type" => "gau",
        "data" => $item["data_decoded"]
      );

      elseif ( $item["type"] == "admob" )
      $item = array(
        "type" => $item["data_decoded"]["type"],
        "id" => $item["data_decoded"]["id"]
      );

    }

    if ( $for_click ){

      $click_fee = bof()->object->db_setting->get( "ads_{$item["type"]}_c_f" );
      if ( $click_fee ){
        bof()->db->query("UPDATE {$this->bof()["db_table_name"]} SET fund_spent_day = fund_spent_day + {$click_fee}, fund_remain = fund_remain - {$click_fee}, sta_clicks = sta_clicks + 1 WHERE ID = '{$item["ID"]}' ");
      }
      else {
        bof()->db->query("UPDATE {$this->bof()["db_table_name"]} SET sta_clicks = sta_clicks + 1 WHERE ID = '{$item["ID"]}' ");
      }

      $item = $item["url"];

    }

    return $item;

  }

  public function get_placements(){

    $ads_placements = [];

    $get_ads_widgets = bof()->object->page_widget->select(
      array(
        "name" => "ads",
      ),
      array(
        "limit" => false,
        "single" => false
      )
    );

    if ( $get_ads_widgets ){
      foreach( $get_ads_widgets as $get_ads_widget ){
        $ads_placements[ "widget_{$get_ads_widget["ID"]}" ] = $get_ads_widget["args_decoded"]["place_id"] . " - " . $get_ads_widget["args_decoded"]["banner_size"];
      }
    }

    return $ads_placements;

  }

}

?>
