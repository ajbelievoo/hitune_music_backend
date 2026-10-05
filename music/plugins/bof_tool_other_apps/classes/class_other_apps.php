<?php

if ( !defined( "bof_root" ) ) die;

class other_apps extends bof_type_class {

  public function setup(){
    
    bof()->object->core_files->add_object( "other_app", bof_other_apps_root . "/objects/object_other_app.php" );
    
    try {
      $check = bof()->db->query("SHOW COLUMNS FROM _c_other_apps LIKE 'icon_url'");
      if ( !$check || $check->num_rows == 0 ){
        bof()->db->query("ALTER TABLE _c_other_apps ADD COLUMN icon_url VARCHAR(512) NULL AFTER icon_id");
      }
    } catch ( Exception|Error $err ){}
    
    if ( bof()->getName() == "bof_client" ){
      bof()->bofClient->_add_object( "other_app", array(
        "list" => true,
        "single" => true,
        "search" => true
      ) );

      bof()->object->endpoint->add( "other_apps_list_api", array(
        "url" => "other_apps_list_api",
        "groups" => [ "api" ],
        "executers" => array(
          bof_other_apps_root . "/endpoints/endpoint_other_apps.php"
        )
      ) );
      bof()->object->endpoint->add( "other_apps_list_public", array(
        "url" => "other_apps_list_public",
        "groups" => [ "website" ],
        "response_type" => "json",
        "executers" => array(
          bof_other_apps_root . "/endpoints/endpoint_other_apps.php"
        )
      ) );
      
      bof()->object->endpoint->add( "other_app_public", array(
        "url" => "other_app_public",
        "groups" => [ "website" ],
        "response_type" => "json",
        "executers" => array(
          bof_other_apps_root . "/endpoints/endpoint_other_app_public.php"
        )
      ) );

      bof()->listen( "client_config", "get_pages_replace", function( $method_args, &$method_result, $loader ){
        if ( isset( $method_result["other_app_list"] ) ){
          $method_result["other_app_list"]["theme_file"] = "theme/pages/other_apps";
          $method_result["other_app_list"]["title"] = "Other Apps";
          $method_result["other_app_list"]["theme_args"] = isset( $method_result["other_app_list"]["theme_args"] ) && is_array( $method_result["other_app_list"]["theme_args"] ) ? $method_result["other_app_list"]["theme_args"] : [];
          $method_result["other_app_list"]["theme_args"]["cache"] = false;
          $method_result["other_app_list"]["theme_args"]["reload"] = true;
          $method_result["other_app_list"]["becli"] = array(
            array(
              "key" => "other_apps_data",
              "endpoint" => "other_apps_list_public"
            )
          );
        }
        
        if ( isset( $method_result["other_app_single"] ) ){
          $method_result["other_app_single"]["free_content"] = true;
          if ( isset( $method_result["other_app_single"]["object"] ) ) unset( $method_result["other_app_single"]["object"] );
          if ( isset( $method_result["other_app_single"]["object_column"] ) ) unset( $method_result["other_app_single"]["object_column"] );
          $method_result["other_app_single"]["theme_file"] = "theme/pages/other_app_single";
          $method_result["other_app_single"]["title"] = "Other App";
          $method_result["other_app_single"]["theme_args"] = isset( $method_result["other_app_single"]["theme_args"] ) && is_array( $method_result["other_app_single"]["theme_args"] ) ? $method_result["other_app_single"]["theme_args"] : [];
          $method_result["other_app_single"]["theme_args"]["cache"] = false;
          $method_result["other_app_single"]["theme_args"]["reload"] = true;
          $method_result["other_app_single"]["becli"] = array(
            array(
              "key" => "single",
              "endpoint" => "other_app_public?id=\$bof ? urlData^url^match^0\$"
            )
          );
        }
      } );

    }

    bof()->listen( "cronjob", "get_jobs_after", function( $method_args, &$method_result, $loader ){
      if ( !is_array( $method_result ) )
      return;

      $method_result["other_apps_refresh"] = array(
        "title" => "Other Apps Refresh",
        "interval" => 24*60,
        "exe" => function( $PID, $GID, $loader ){
          return $loader->other_apps->refresh_all_apps( $PID, $GID );
        }
      );

      return $method_result;
    } );

    if ( bof()->getName() == "bof_admin" ){
      
      bof()->bofAdmin->_add_object( "other_app", array(
        "seo" => false
      ) );

      bof()->listen( "client_config", "get_pages_after", function( $method_args, &$method_result, $loader ){
        if ( is_array( $method_result ) ){
          $method_result["other_apps"] = array(
            "title" => "Other Apps",
            "url" => "^other_apps$",
            "link" => "other_apps",
            "theme_file" => "parts/content_table",
            "becli" => array(
              (object) array(
                "endpoint" => "bofAdmin/list/other_app/?\$bof ? urlData^url^query_s\$",
                "key" => "content"
              )
            )
          );
          $method_result["playstore_apps"] = array(
            "title" => "Other Apps",
            "url" => "^playstore-apps$",
            "link" => "playstore-apps",
            "theme_file" => "parts/content_table",
            "becli" => array(
              (object) array(
                "endpoint" => "bofAdmin/list/other_app/?\$bof ? urlData^url^query_s\$",
                "key" => "content"
              )
            )
          );
          $method_result["other_app"] = array(
            "title" => "Other App",
            "url" => "^other_app\/(.*?)$",
            "link" => "other_app",
            "theme_file" => "parts/content_single",
            "becli" => array(
              (object) array(
                "endpoint" => "bofAdmin/object/other_app/?IDs=\$bof ? urlData^url^match^0\$&\$bof ? urlData^url^query_s\$",
                "key" => "entity"
              )
            )
          );
        }
      } );

      $this->setup_admin();
    }

    $this->setup_common();

  }

  protected function setup_common(){

  }

  protected function setup_admin(){

    bof()->listen( "highlights", "display_pre", function( $method_args, $method_result, $loader ){
      try {
        $sb_family = $method_args[0];
        $highlights = bof()->highlights->getData();

        if ( $sb_family == "content" ){
          $highlights["content_links"]["items"]["other_apps"] = array(
            "icon" => "apps",
            "title" => "Other Apps",
            "link" => "other_apps"
          );
        }
        
        if ( $sb_family == "extensions" ){
          if ( isset($highlights["extensions_links"]["items"]["tools_links"]) ){
            $highlights["extensions_links"]["items"]["tools_links"]["args"]["childs"][] = array(
              "icon" => "apps",
              "title" => "Other Apps",
              "link" => "other_apps"
            );
          }
        }
        
        bof()->highlights->setData($highlights);
      } catch ( Exception $e ) {
      }
    } );

    bof()->listen( "bofAdmin", "object_action_after", function( $method_args, &$method_result, $loader ){

      $object_name = $method_args[0];
      $action_name = $method_args[1];

      if ( $object_name == "other_app" && $action_name == "scrape_data" ){
        
        $id = bof()->nest->user_input( "get", "id", "int" );
        if ( $id ){
          bof()->other_apps->update_app( $id );
          $method_result = array(
            "success" => true,
            "messages" => [ "Data scraped successfully" ]
          );
        }

      }

    } );

  }

  public function update_app( $id ){

    $app = bof()->object->other_app->select( [ "ID" => $id ] );
    if ( !$app || empty( $app["package_id"] ) ) return;

    require_once( bof_other_apps_root . "/classes/class_scraper.php" );
    $scraper = new play_store_scraper();
    $data = $scraper->scrape( $app["package_id"] );

    if ( $data ){
      
      $update_data = [];
      if ( !empty( $data["name"] ) )
      $update_data["name"] = $data["name"];
      if ( isset( $data["downloads"] ) )
      $update_data["downloads"] = $data["downloads"];
      if ( isset( $data["rating"] ) )
      $update_data["rating"] = $data["rating"];
      if ( !empty( $data["url"] ) )
      $update_data["url"] = $data["url"];

      if ( !empty( $data["icon_url"] ) )
      $update_data["icon_url"] = $data["icon_url"];

      if ( empty( $update_data ) )
      return;

      bof()->object->other_app->update( 
        [ "ID" => $id ],
        array_merge( $update_data, [ "__scraped" => 1 ] )
      );

    }

  }

  public function refresh_all_apps( $PID=null, $GID=null ){
    
    $apps = bof()->object->other_app->select( [], [ "empty_select" => true, "limit" => false, "single" => false ] );
    $updated = 0;
    if ( $apps ){
      foreach( $apps as $app ){
        $this->update_app( $app["ID"] );
        $updated++;
      }
    }
    
    if ( $PID && $GID )
    bof()->cronjob->log_p( $PID, $GID, "Updated {$updated} app(s)" );

    return "Updated {$updated} app(s)";

  }

}
