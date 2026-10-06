<?php

if ( !defined( "bof_root" ) ) die;

class object_user_subs_plan extends bof_type_object {

  public function bof(){
    return array(
      "name" => "user_subs_plan",
      "label" => "User Subscription Plan",
      "icon" => "payment",
      "db_table_name" => "_u_subs_plans"
    );
  }
  public function columns(){
    return array(
      "name" => array(
        "public" => true,
        "label" => "Name",
        "validator" => "string",
        "input" => array(
          "type" => "text"
        ),
        "bofAdmin" => array(
          "sortable" => true,
          "list" => array(
            "type" => "simple",
            "class" => "title",
            "renderer" => function ( $displayItem, $item, $displayData ){
              $displayData["sub_data"] = $item["comment"];
              return $displayData;
            }
          ),
          "object" => array(
            "required" => true,
            "seo_slug_source" => true,
          ),
        )
      ),
      "comment" => array(
        "public" => true,
        "label" => "Comment",
        "tip" => "A few words to remember this subscription plan by",
        "input" => array(
          "type" => "textarea",
        ),
        "validator" => array(
          "string",
          array(
            "empty()",
            "allow_eol" => true,
            "strip_emoji" => false,
          ),
        ),
        "bofAdmin" => array(
          "object" => array()
        ),
      ),
      "detail" => array(
        "public" => true,
        "label" => "Description",
        "input" => array(
          "type" => "text_editor",
        ),
        "validator" => array(
          "editor_js",
          array(
            "empty()"
          ),
        ),
        "bofAdmin" => array(
          "object" => []
        )
      ),
      "target_role_id" => array(
        "label" => "Designated<br>User-Role",
        "validator" => "int",
        "bofInput" => array(
          "object",
          array(
            "type" => "user_role",
            "sub_type" => "user"
          ),
        ),
        "bofAdmin" => array(
          "sortable" => true,
          "list" => array(
            "type" => "simple",
            "renderer" => function ( $displayItem, $item, $displayData ){
              if ( !empty( $item["bof_dir_user_role"] ) )
              $displayData["data"] = $item["bof_dir_user_role"]["name"];
              return $displayData;
            }
          ),
          "object" => array(
            "required" => true,
            "display_on" => array(
              "free" => [ "equal", false ]
            ),
          ),
        ),
        "relations" => array(
          "user_role" => array(
            "exec" => array(
              "type" => "direct",
              "parent_object" => "user_role",
              "child_object" => "user_subs_plan",
              "child_object_selector_column" => "target_role_id",
              "delete_child_too" => true,
              "limit" => 1
            )
          )
        )
      ),
      "discount" => array(
        "public" => true,
        "label" => "Discount",
        "input" => array(
          "type" => "digit"
        ),
        "validator" => array(
          "float",
          array(
            "empty()",
            "min" => 0,
            "max" => 100,
            "forceZero" => true
          ),
        ),
        "bofAdmin" => array(
          "sortable" => true,
          "object" => array(
            "display_on" => array(
              "free" => [ "equal", false ]
            ),
          )
        ),
      ),
      "prices" => array(
        "public" => true,
        "label" => "Prices",
        "validator" => array(
          "json",
          array(
            "empty()",
            "encode" => true
          ),
        ),
        "bofAdmin" => array(
          "list" => array(
            "type" => "simple",
            "class" => "details",
            "renderer" => function ( $displayItem, $item, $displayData ){
              $displayData["data"] = "";
              if ( !empty( $item["_prices"]["original"] ) ){
                $displayData["data"] .= "<ul>";
                if ( !empty( $item["discount"] ) )
                $displayData["data"] .= "<li><b>Discount</b> <i style='color:rgba(var(--theme_color),0.6)'>{$item["discount"]}%</i>";
                foreach( $item["_prices"]["original"] as $_k => $v )
                $displayData["data"] .= "<li><b>{$_k}</b> ". bof()->object->currency->parse_price_string( $v ) ."</li>";
                $displayData["data"] .= "<ul>";
              }
              return $displayData;
            }
          )
        )
      ),
      "active" => array(
        "label" => "Active",
        "validator" => array(
          "boolean",
          array(
            "empty()",
            "int" => true
          ),
        ),
        "bofAdmin" => array(
          "sortable" => true,
          "list" => array(
            "type" => "boolean",
            "args" => array(
              "payloads" => [ "activate", "deactivate" ]
            )
          )
        ),
        "filters" => array(
          "active" => array(
            "name" => "active",
            "title" => "Status",
            "input" => array(
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
        )
      ),
      "priority" => array(
        "label" => "Priority",
        "tip" => "A number between 1 to 999 with 999 having the highest priority for being displayed in subscription page",
        "validator" => array(
          "int",
          array(
            "empty()",
            "forceZero" => true,
            "min" => 0,
            "max" => 999
          ),
        ),
        "input" => array(
          "type" => "digit"
        ),
        "bofAdmin" => array(
          "object" => []
        ),
      ),
      "free" => array(
        "public" => true,
        "label" => "Free",
        "tip" => "Is this a real subscription or just a display of free features? If it's for showcasing free features only, check the box to prevent purchases",
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
          "object" => []
        ),
      ),
      "data" => array(
        "validator" => array(
          "json",
          array(
            "empty()",
            "encode" => true
          ),
        ),
      ),
      "features" => array(
        "public" => true,
        "label" => "Features",
        "tip" => "JSON object of feature flags granted by this plan, e.g. {\"offline_downloads\":1,\"lyrics\":1}",
        "validator" => array(
          "json",
          array(
            "empty()",
            "encode" => true
          ),
        ),
        "bofAdmin" => array(
          "list" => array(
            "type" => "simple",
            "class" => "details",
            "renderer" => function ( $displayItem, $item, $displayData ){
              $_f = !empty( $item["features"] ) ? json_decode( $item["features"], true ) : null;
              if ( !is_array( $_f ) || !$_f ){
                $displayData["data"] = "<i style='opacity:.5'>none</i>";
                return $displayData;
              }
              $_keys = [];
              foreach( $_f as $_fk => $_fv )
              $_keys[] = is_int( $_fk ) ? $_fv : $_fk;
              $displayData["data"] = implode( ", ", $_keys );
              return $displayData;
            }
          )
        )
      ),
    );
  }
  public function feature_inputs(){

    // One admin checkbox per app feature flag; collected into the
    // `features` JSON column by object_be_renderer.
    $_inputs = array();
    foreach( self::feature_keys() as $_key => $_label ){
      $_inputs[ "feature_" . $_key ] = array(
        "label" => $_label,
        "input" => array(
          "type" => "checkbox"
        ),
        "validator" => array(
          "boolean",
          array(
            "empty()",
            "int" => true
          )
        ),
        "bofAdmin" => array(
          "object" => array(
            "group" => "features"
          )
        )
      );
    }
    return $_inputs;

  }
  public static function feature_keys(){
    // Feature flags the Flutter app understands (AppFeatures / plan_features)
    return array(
      "offline_downloads"        => "Offline downloads",
      "lyrics"                   => "Lyrics",
      "sleep_timer"              => "Sleep timer",
      "equalizer"                => "Equalizer",
      "sound_controls"           => "Sound controls (bass / speed / pitch)",
      "hq_audio"                 => "HQ audio",
      "no_ads"                   => "No ads",
      "casting"                  => "Casting (Chromecast / AirPlay)",
      "unlimited_skips"          => "Unlimited skips",
      "comments"                 => "Comments",
      "playlists"                => "Playlists",
      "push_notifications"       => "Push notifications",
      "voice_search"             => "Voice search",
      "collaborative_playlists"  => "Collaborative playlists",
      "recommendations"          => "Recommendations",
      "ai_studio"                => "AI Studio — all tools",
      "ai_song_gen"              => "AI Song Generator",
      "ai_mastering"             => "AI Mastering",
      "ai_karaoke"               => "AI Karaoke stems",
      "ai_lyrics_sync"           => "AI Lyrics Sync",
      "ai_cover_art"             => "AI Cover Art",
      "ai_clip_video"            => "AI Clip/Reel render",
    );
  }
  public function bof_columns(){
    return array(
      "ID",
      "time_add",
      "hash",
      "translations" => array(
        "name",
        "comment",
        "detail"
      ),
    );
  }
  public function relations(){
    return array(
      "subs" => array(
        "exec" => array(
          "type" => "direct",
          "parent_object" => "user_subs_plan",
          "child_object" => "user_subs",
          "child_object_selector_column" => "subs_plan_id",
          "delete_child_too" => true,
        )
      )
    );
  }
  public function selectors(){
    return array(
      "target_role_id" => [ "target_role_id", "=" ],
      "query" => [ "name", "LIKE%lower" ],
      "active" => [ "active", "=" ],
      "free" => [ "free", "=" ],
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
        "edit_page_url" => "user_subs_plan",
        "list_page_url" => "user_subs_plans",
        "multi" => array(
          "select" => false,
          "delete" => false,
          "edit"   => false
        )
      ),
      "object_groups" => array(
        array( "features", "Features" ),
      ),
      "object" => array_merge( array(

        "name" => null,
        "comment" => null,
        "priority" => null,
        "detail" => null,
        "free" => null,
        "target_role_id" => null,
        "plan_key" => array(
          "label" => "Plan Key",
          "tip" => "Machine key sent to the app in client_config.plan_features (e.g. <b>premium</b>, <b>family</b>). Leave empty to auto-derive from the plan name",
          "input" => array(
            "type" => "text"
          ),
          "validator" => array(
            "string",
            array(
              "empty()",
              "strict" => true,
              "strict_regex" => "[a-z0-9_]",
              "turn_lower" => true
            )
          ),
          "bofAdmin" => array(
            "object" => array(
              "group" => "features"
            )
          )
        ),
        "trial_days" => array(
          "label" => "Free Trial Days",
          "tip" => "First-time subscribers get this many days free via /api/subs_start_trial (0 disables trial)",
          "input" => array(
            "type" => "digit"
          ),
          "validator" => array(
            "int",
            array(
              "empty()",
              "forceZero" => true,
              "min" => 0,
              "max" => 365
            )
          ),
          "bofAdmin" => array(
            "object" => array(
              "group" => "features"
            )
          )
        ),
        "ai_quota" => array(
          "label" => "AI generations / day",
          "tip" => "Daily AI Studio quota for this plan (song gen, karaoke, mastering, lyrics, cover, clips). Empty = falls back to the global free/pro quota in AI Tools settings.",
          "input" => array(
            "type" => "digit"
          ),
          "validator" => array(
            "int",
            array(
              "empty()",
              "forceZero" => true,
              "min" => 0,
              "max" => 100000
            )
          ),
          "bofAdmin" => array(
            "object" => array(
              "group" => "features"
            )
          )
        ),
        "iap_weekly" => array(
          "label" => "IAP Product ID - Weekly",
          "tip" => "Google Play / App Store subscription product id for the weekly plan",
          "input" => array( "type" => "text" ),
          "validator" => array( "string", array( "empty()", "strict" => true, "strict_regex" => "[a-zA-Z0-9_.\-]" ) ),
          "bofAdmin" => array( "object" => array( "group" => "features" ) )
        ),
        "iap_monthly" => array(
          "label" => "IAP Product ID - Monthly",
          "input" => array( "type" => "text" ),
          "validator" => array( "string", array( "empty()", "strict" => true, "strict_regex" => "[a-zA-Z0-9_.\-]" ) ),
          "bofAdmin" => array( "object" => array( "group" => "features" ) )
        ),
        "iap_3months" => array(
          "label" => "IAP Product ID - 3 Months",
          "input" => array( "type" => "text" ),
          "validator" => array( "string", array( "empty()", "strict" => true, "strict_regex" => "[a-zA-Z0-9_.\-]" ) ),
          "bofAdmin" => array( "object" => array( "group" => "features" ) )
        ),
        "iap_4months" => array(
          "label" => "IAP Product ID - 4 Months",
          "input" => array( "type" => "text" ),
          "validator" => array( "string", array( "empty()", "strict" => true, "strict_regex" => "[a-zA-Z0-9_.\-]" ) ),
          "bofAdmin" => array( "object" => array( "group" => "features" ) )
        ),
        "iap_6months" => array(
          "label" => "IAP Product ID - 6 Months",
          "input" => array( "type" => "text" ),
          "validator" => array( "string", array( "empty()", "strict" => true, "strict_regex" => "[a-zA-Z0-9_.\-]" ) ),
          "bofAdmin" => array( "object" => array( "group" => "features" ) )
        ),
        "iap_9months" => array(
          "label" => "IAP Product ID - 9 Months",
          "input" => array( "type" => "text" ),
          "validator" => array( "string", array( "empty()", "strict" => true, "strict_regex" => "[a-zA-Z0-9_.\-]" ) ),
          "bofAdmin" => array( "object" => array( "group" => "features" ) )
        ),
        "iap_yearly" => array(
          "label" => "IAP Product ID - Yearly",
          "input" => array( "type" => "text" ),
          "validator" => array( "string", array( "empty()", "strict" => true, "strict_regex" => "[a-zA-Z0-9_.\-]" ) ),
          "bofAdmin" => array( "object" => array( "group" => "features" ) )
        ),

        "price_weekly" => array(
          "label" => "Weekly Price",
          "tip" => "The amount user has to pay to get subscribed to this plan for 1 week. Leaving empty disables this option",
          "bofInput" => array(
            "currency"
          ),
          "validator" => array(
            "float",
            array(
              "empty()",
              "min" => 0
            )
          ),
          "display_on" => array(
            "free" => [ "equal", false ]
          )
        ),
        "price_monthly" => array(
          "label" => "Monthly Price",
          "tip" => "The amount user has to pay to get subscribed to this plan for 1 month. Leaving empty disables this option",
          "bofInput" => array(
            "currency"
          ),
          "validator" => array(
            "float",
            array(
              "empty()",
              "min" => 0
            )
          ),
          "display_on" => array(
            "free" => [ "equal", false ]
          )
        ),
        "price_3months" => array(
          "label" => "3 Months Price",
          "tip" => "The amount user has to pay to get subscribed to this plan for 3 months. Leaving empty disables this option",
          "bofInput" => array(
            "currency"
          ),
          "validator" => array(
            "float",
            array(
              "empty()",
              "min" => 0
            )
          ),
          "display_on" => array(
            "free" => [ "equal", false ]
          )
        ),
        "price_4months" => array(
          "label" => "4 Months Price",
          "tip" => "The amount user has to pay to get subscribed to this plan for 4 months. Leaving empty disables this option",
          "bofInput" => array(
            "currency"
          ),
          "validator" => array(
            "float",
            array(
              "empty()",
              "min" => 0
            )
          ),
          "display_on" => array(
            "free" => [ "equal", false ]
          )
        ),
        "price_6months" => array(
          "label" => "6 Months Price",
          "tip" => "The amount user has to pay to get subscribed to this plan for 6 months. Leaving empty disables this option",
          "bofInput" => array(
            "currency"
          ),
          "validator" => array(
            "float",
            array(
              "empty()",
              "min" => 0
            )
          ),
          "display_on" => array(
            "free" => [ "equal", false ]
          )
        ),
        "price_9months" => array(
          "label" => "9 Months Price",
          "tip" => "The amount user has to pay to get subscribed to this plan for 9 months. Leaving empty disables this option",
          "bofInput" => array(
            "currency"
          ),
          "validator" => array(
            "float",
            array(
              "empty()",
              "min" => 0
            )
          ),
          "display_on" => array(
            "free" => [ "equal", false ]
          )
        ),
        "price_yearly" => array(
          "label" => "Yearly Price",
          "tip" => "The amount user has to pay to get subscribed to this plan for 1 year. Leaving empty disables this option",
          "bofInput" => array(
            "currency"
          ),
          "validator" => array(
            "float",
            array(
              "empty()",
              "min" => 0
            )
          ),
          "display_on" => array(
            "free" => [ "equal", false ]
          )
        ),
      ), $this->feature_inputs() ),
      "object_ui_renderer" => function( $object, $parsed, $args, $request, &$_inputs, &$data ){

        if ( $request["type"] != "single" )
        return;

        $item = $request["content"][ $request["IDS"][0] ];
        $prices = $item["_prices"]["original"];
        if ( $prices ){
          foreach( $prices as $_k => $_v ){
            $_inputs[ "price_{$_k}" ]["input"]["value"] = $_v;
          }
        }

        // Pre-tick feature checkboxes from stored JSON (list or map shape)
        $_features = !empty( $item["features"] ) ? json_decode( $item["features"], true ) : null;
        if ( is_array( $_features ) ){
          foreach( $_features as $_fk => $_fv ){
            $_key = is_int( $_fk ) ? $_fv : $_fk;
            if ( ( is_int( $_fk ) ? true : $_fv ) && isset( $_inputs[ "feature_{$_key}" ] ) )
            $_inputs[ "feature_{$_key}" ]["input"]["value"] = true;
          }
        }

        // Plan key / trial / iap mapping live inside the `data` JSON column
        $_pdata = !empty( $item["data"] ) ? json_decode( $item["data"], true ) : null;
        if ( isset( $_inputs["plan_key"] ) && !empty( $_pdata["plan_key"] ) )
        $_inputs["plan_key"]["input"]["value"] = $_pdata["plan_key"];

        if ( isset( $_inputs["trial_days"] ) && isset( $_pdata["trial_days"] ) )
        $_inputs["trial_days"]["input"]["value"] = intval( $_pdata["trial_days"] );

        if ( isset( $_inputs["ai_quota"] ) && isset( $_pdata["ai_quota"] ) )
        $_inputs["ai_quota"]["input"]["value"] = intval( $_pdata["ai_quota"] );

        if ( !empty( $_pdata["iap"] ) && is_array( $_pdata["iap"] ) ){
          foreach( $_pdata["iap"] as $_pid => $_pi ){
            $_period = is_array( $_pi ) && !empty( $_pi["period"] ) ? $_pi["period"] : null;
            if ( $_period && isset( $_inputs[ "iap_" . $_period ] ) )
            $_inputs[ "iap_" . $_period ]["input"]["value"] = $_pid;
          }
        }

      },
      "object_be_renderer" => function( $_inputs, $request ){

        if ( !empty( $_inputs["data"]["free"] ) ){
          $_inputs["set"]["target_role_id"] = $_inputs["update"]["target_role_id"] = 2;
          if ( !empty( $_inputs["report"]["fail"]["target_role_id"] ) )
          unset( $_inputs["report"]["fail"]["target_role_id"] );
        }

        $prices = [];
        foreach( [ "weekly", "monthly", "3months", "4months", "6months", "9months", "yearly", "2years" ] as $_k ){
          if ( !empty( $_inputs["data"][ "price_" . $_k ] ) )
          $prices[ $_k ] = $_inputs["data"][ "price_" . $_k ];
        }

        if ( empty( $prices ) && empty( $_inputs["data"]["free"] ) )
        $_inputs["report"]["fail"]["price_weekly"] = "Enter at least 1 price";

        $_inputs["set"]["prices"] = $_inputs["update"]["prices"] = $prices ? json_encode( $prices ) : null;

        // Feature checkboxes -> features JSON column
        $features = array();
        foreach( self::feature_keys() as $_fkey => $_flabel ){
          if ( !empty( $_inputs["data"][ "feature_" . $_fkey ] ) )
          $features[ $_fkey ] = 1;
        }
        $_inputs["set"]["features"] = $_inputs["update"]["features"] = json_encode( $features );

        // plan_key merges into `data` JSON without clobbering other keys
        $_data_json = array();
        if ( !empty( $request["content"] ) && !empty( $request["IDS"][0] ) ){
          $_row = $request["content"][ $request["IDS"][0] ];
          if ( !empty( $_row["data"] ) )
          $_data_json = json_decode( $_row["data"], true );
        }
        if ( !is_array( $_data_json ) ) $_data_json = array();
        if ( !empty( $_inputs["data"]["plan_key"] ) )
        $_data_json["plan_key"] = $_inputs["data"]["plan_key"];
        else unset( $_data_json["plan_key"] );

        // Free trial days
        $_trial = !empty( $_inputs["data"]["trial_days"] ) ? intval( $_inputs["data"]["trial_days"] ) : 0;
        if ( $_trial > 0 ) $_data_json["trial_days"] = $_trial;
        else unset( $_data_json["trial_days"] );

        // Per-plan daily AI quota (0/empty = global free/pro default)
        $_aiq = isset( $_inputs["data"]["ai_quota"] ) && $_inputs["data"]["ai_quota"] !== "" ? intval( $_inputs["data"]["ai_quota"] ) : null;
        if ( $_aiq !== null ) $_data_json["ai_quota"] = $_aiq;
        else unset( $_data_json["ai_quota"] );

        // IAP product id -> period map
        $_data_json["iap"] = array();
        foreach( [ "weekly", "monthly", "3months", "4months", "6months", "9months", "yearly" ] as $_period ){
          $_pid = !empty( $_inputs["data"][ "iap_" . $_period ] ) ? trim( $_inputs["data"][ "iap_" . $_period ] ) : null;
          if ( $_pid ) $_data_json["iap"][ $_pid ] = array( "period" => $_period );
        }
        if ( !$_data_json["iap"] ) unset( $_data_json["iap"] );

        $_inputs["set"]["data"] = $_inputs["update"]["data"] = json_encode( $_data_json );

        return $_inputs;

      },
      "buttons" => array(
        "activate" => array(
          "id" => "activate",
          "label" => "Activate",
          "payload" => array(
            "post" => array(
              "__action" => "activate"
            ),
          ),
        ),
        "deactivate" => array(
          "id" => "deactivate",
          "label" => "De-Activate",
          "payload" => array(
            "post" => array(
              "__action" => "deactivate"
            ),
          ),
        ),
      ),
      "buttons_renderer" => function( $item, $buttons ){

        if ( $item["active"] )
        unset( $buttons["activate"] );

        if ( !$item["active"] )
        unset( $buttons["deactivate"] );

        return $buttons;

      },
      "actions" => array(
        "deactivate" => function( $ids ){
          bof()->object->user_subs_plan->update(array(
            "ID_in" => $ids
          ),array(
            "active" => 0
          ));
          return [ true, "deactivated" ];
        },
        "activate" => function( $ids ){
          bof()->object->user_subs_plan->update(array(
            "ID_in" => $ids
          ),array(
            "active" => 1
          ));
          return [ true, "activated" ];
        },
      ),
    );
  }

  public function select( $whereArgs=[], $selectArgs=[] ){

    $search = null;
    $listing = null;
    $deleting = null;
    $editing = null;
    $_eq = [];
    extract( $selectArgs );

    if ( $listing )
    $_eq["user_role"] = [];

    $selectArgs["_eq"] = $_eq;
    return bof()->object->_select( $this, $whereArgs, $selectArgs );

  }
  public function clean( $item, $args=[] ){

    $search = false;
    $_eq = [];
    extract( $args );

    $item["_prices"] = array(
      "original" => [],
      "original_parsed" => [],
      "final" => [],
      "final_parsed" => [],
      "min" => null
    );

    if ( !empty( $item["free"] ) ){
      $item["prices_decoded"] = array(
        "monthly" => 0
      );
    }
    if ( !empty( $item["prices_decoded"] ) ){

      $item["_prices"]["original"] = $item["prices_decoded"];
      foreach( $item["prices_decoded"] as $k => $price ){

        $parse_price = bof()->object->currency->parse_price( $price, [ "zero_is_free" => false ] );
        $item["_prices"]["original_parsed"][ $k ] = $parse_price["string"];

        $final_price_raw = $item["discount"] ? ( 1 - ( $item["discount"] / 100 ) ) * $price : $price;
        $item["_prices"]["final_raw"][ $k ] = $final_price_raw;

        $final_price = $item["discount"] ? number_format( ( 1 - ( $item["discount"] / 100 ) ) * $price, 3 ) : $price;
        $item["_prices"]["final"][ $k ] = $final_price;

        $parse_final_price = bof()->object->currency->parse_price( $final_price_raw, [ "zero_is_free" => false ] );
        $item["_prices"]["final_parsed"][ $k ] = $parse_final_price["string"];

      }

      $item["_prices"]["min"] = reset( $item["_prices"]["final_parsed"] );
      $item["_prices"]["min_original"] = reset( $item["_prices"]["original_parsed"] );
      unset( $item["prices"], $item["prices_decoded"] );

    }

    // Expose plan metadata to client payloads (subscription page, trial CTA)
    $_pdata = !empty( $item["data"] ) ? json_decode( $item["data"], true ) : null;
    if ( is_array( $_pdata ) ){
      if ( !empty( $_pdata["plan_key"] ) ) $item["plan_key"] = $_pdata["plan_key"];
      if ( !empty( $_pdata["trial_days"] ) ) $item["trial_days"] = intval( $_pdata["trial_days"] );
    }

    $_feats = !empty( $item["features"] ) ? json_decode( $item["features"], true ) : null;
    if ( is_array( $_feats ) ){
      $item["features_decoded"] = array();
      foreach( $_feats as $_fk => $_fv )
      if ( is_int( $_fk ) ? true : $_fv ) $item["features_decoded"][] = is_int( $_fk ) ? $_fv : $_fk;
    }

    if ( $search ){
      $item = array(
        "ID" => $item["ID"],
        "title" => $item["name"],
        "image" => false
      );
    }

    return $item;

  }

  public function clean_as_widget( $item, $args ){

    return array(
      "title"    => $item["name"],
      "sub_data" => null,
      "sub_link" => null,
      "cover"    => null,
      "raw"      => $item,
      "ot"       => "user_subs_plan",
      "on"       => $this->_bof_this->bof()["label"],
      "buttons"  => null,
      "hash"     => $item["hash"]
    );

  }

}

?>
