<?php

if ( !defined( "bof_root" ) ) die;

class object_other_app extends bof_type_object {

  // BusyOwlFramework handshake
  public function bof(){
    return array(
      "name" => "other_app",
      "label" => "Other App",
      "icon" => "apps",
      "db_table_name" => "_c_other_apps",
    );
  }

  public function columns(){
    return array(

      "package_id" => array(
        "public" => true,
        "label" => "Package ID",
        "tip" => "e.g. com.hitune.music",
        "validator" => array(
          "string",
          array(
            "empty()" => false,
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
          ),
          "object" => array(
            "required" => true
          )
        ),
      ),

      "name" => array(
        "public" => true,
        "label" => "App Name",
        "validator" => array(
          "string",
          array(
            "empty()" => true,
          ),
        ),
        "input" => array(
          "type" => "text",
        ),
        "bofAdmin" => array(
          "sortable" => true,
          "list" => array(
            "type" => "simple",
          ),
        ),
      ),

      "icon_id" => array(
        "label" => "Icon",
        "input" => array(
          "type" => "file",
        ),
        "bofInput" => array(
          "file",
          array(
            "type" => "image",
            "object_type" => "other_app_icon"
          )
        ),
        "validator" => array(
          "int",
          array(
            "empty()" => true
          )
        ),
      ),
      
      "icon_url" => array(
        "public" => true,
        "label" => "Icon URL",
        "validator" => array(
          "url",
          array(
            "empty()" => true,
          ),
        ),
        "input" => array(
          "type" => "text",
        ),
        "bofAdmin" => array(
          "list" => array(
            "type" => "simple",
          ),
        ),
      ),

      "downloads" => array(
        "public" => true,
        "label" => "Downloads",
        "validator" => array(
          "string",
          array(
            "empty()" => true,
          ),
        ),
        "input" => array(
          "type" => "text",
        ),
        "bofAdmin" => array(
          "list" => array(
            "type" => "simple",
          ),
        ),
      ),

      "rating" => array(
        "public" => true,
        "label" => "Rating",
        "validator" => array(
          "float",
          array(
            "empty()" => true,
          ),
        ),
        "input" => array(
          "type" => "text",
        ),
        "bofAdmin" => array(
          "sortable" => true,
          "list" => array(
            "type" => "simple",
          ),
        ),
      ),

      "url" => array(
        "public" => true,
        "label" => "Play Store URL",
        "validator" => array(
          "url",
          array(
            "empty()" => true,
          ),
        ),
        "input" => array(
          "type" => "text",
        ),
        "bofAdmin" => array(
          "list" => array(
            "type" => "simple",
          ),
        ),
      ),

    );
  }

  public function bof_columns(){
    return array(
      "ID"
    );
  }

  public function selectors(){
    return array(
      "ID" => [ "ID", "=" ],
      "package_id" => [ "package_id", "=" ],
    );
  }
  
  protected function scrape_package( $package_id ){
    
    if ( empty( $package_id ) )
    return false;
    
    require_once( bof_other_apps_root . "/classes/class_scraper.php" );
    $scraper = new play_store_scraper();
    return $scraper->scrape( $package_id );
    
  }
  
  public function insert( $setArgs ){
    
    if ( !empty( $setArgs["package_id"] ) ){
      $data = $this->scrape_package( $setArgs["package_id"] );
      if ( $data ){
        $setArgs["name"] = !empty( $data["name"] ) ? $data["name"] : ( !empty( $setArgs["name"] ) ? $setArgs["name"] : "" );
        $setArgs["downloads"] = isset( $data["downloads"] ) ? $data["downloads"] : ( isset( $setArgs["downloads"] ) ? $setArgs["downloads"] : "" );
        $setArgs["rating"] = isset( $data["rating"] ) ? $data["rating"] : ( isset( $setArgs["rating"] ) ? $setArgs["rating"] : 0 );
        $setArgs["url"] = !empty( $data["url"] ) ? $data["url"] : ( !empty( $setArgs["url"] ) ? $setArgs["url"] : "" );
        $setArgs["icon_url"] = !empty( $data["icon_url"] ) ? $data["icon_url"] : ( !empty( $setArgs["icon_url"] ) ? $setArgs["icon_url"] : "" );
      }
    }
    
    return bof()->object->_insert( $this, $setArgs );
    
  }
  
  public function update( $whereArgs, $setArgs, $exeRelations=true ){
    
    if ( !empty( $setArgs["__scraped"] ) ){
      unset( $setArgs["__scraped"] );
      return bof()->object->_update( $this, $whereArgs, $setArgs, $exeRelations );
    }
    
    if ( !empty( $setArgs["package_id"] ) ){
      $data = $this->scrape_package( $setArgs["package_id"] );
      if ( $data ){
        if ( !empty( $data["name"] ) ) $setArgs["name"] = $data["name"];
        if ( isset( $data["downloads"] ) ) $setArgs["downloads"] = $data["downloads"];
        if ( isset( $data["rating"] ) ) $setArgs["rating"] = $data["rating"];
        if ( !empty( $data["url"] ) ) $setArgs["url"] = $data["url"];
        if ( !empty( $data["icon_url"] ) ) $setArgs["icon_url"] = $data["icon_url"];
      }
    }
    
    return bof()->object->_update( $this, $whereArgs, $setArgs, $exeRelations );
    
  }

  public function bof_client(){
    return array(
      "list_url" => "playstore-apps",
      "single_url_prefix" => "other_app",
      "buttons" => array(
        "link" => true,
        "share" => true,
      ),
    );
  }

  public function publicize( $loader, $item, $args ){

    if ( !empty( $item["icon_id"] ) ){
      $file = bof()->object->file->select( [ "ID" => $item["icon_id"] ] );
      if ( $file ) $item["icon"] = $file["image_thumb"];
    }
    elseif ( !empty( $item["icon_url"] ) ){
      $item["icon"] = $item["icon_url"];
    }

    return $item;

  }

  public function bof_admin(){
    return array(
      "config" => array(
        "search" => true,
        "create" => true,
        "edit" => true,
        "delete" => true,
        "pagination" => true,
        "list_order_by" => "ID",
        "list_order_direction" => "DESC",
        "list_page_url" => "other_apps",
        "edit_page_url" => "other_app",
        "multi" => array(
          "select" => true,
          "delete" => true,
          "edit"   => true
        )
      ),
      "buttons" => array(
        "scrape" => array(
          "label" => "Scrape Data",
          "action" => "scrape_data",
          "icon" => "refresh",
        )
      )
    );
  }

}
