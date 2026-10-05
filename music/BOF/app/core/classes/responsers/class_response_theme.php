<?php

if ( !defined( "bof_root" ) ) die;

class response_theme {

  protected $name = null;
  protected $args = [];
  protected $data = null;

  public function __construct(){

    $this->data = (object) array(
      "content" => null,
      "scripts" => null,
      "styles" => null,
      "httpHeaders" => null,
      "metaDatas" => null,
      "bodyClass" => null,
      "langCode" => null,
    );

  }

  public function active(){
    if ( !$this->name )
    fall("Response: Theme: Active: no active theme defined!");
    $theme = bof()->object->theme->get( $this->name );
    return $theme;
  }

  public function set( $args ){

    $name = null;
    $page_name = null;
    extract( $args );

    if ( empty( $name ) )
    fall("Response: Theme: No name passed");

    if ( !bof()->object->theme->validate_name( $name ) )
    fall("Response: Theme: {$name} is not defined");

    $this->name = $name;
    $this->args = $args;

    return $this;

  }

  public function display(){

    $html_data = $theme_html_data = bof()->response->theme->get_html_data();

    foreach( (array) $theme_html_data as $_k => $_v  ){
      if ( !empty( $this->args["html_data"][ $_k ] ) )
      $html_data->$_k = array_merge( $_v, $this->args["html_data"][ $_k ] );
    }

    bof()->response->html
    ->set( $theme_html_data )
    ->display();

  }

  public function get_html_data(){

    $theme = $this->active();
    $theme->set_args( $this->args );
    $content = $theme->get_html_data();

    if ( !( $content = bof()->object->html->validate_args( $content ) ) )
    fall( "Theme: Display: Invalid theme content" );

    $this->data = $content;
    return $this->data;

  }

  public function load( $path, $args=[], $addToContent=false ){

    extract( $args );

    if ( !file_exists( $path ) )
    fall("Response: Theme: Load failed {$path} doesn't exists");

    $loader = bof();
    $html = bof()->response->html;
    $html_object = bof()->object->html;
    $theme = bof()->response->theme;
    $theme_object = bof()->object->theme;

    if ( !function_exists( "dom" ) ){
      function dom( $name, $args, $display=true ){
        global $bof;
        return $bof->object->html->dom_display( $name, $args, $display );
      }
    }

    ob_start();
    require_once( $path );
    $content = ob_get_contents();
    ob_end_clean();

    if ( $addToContent ){

      if ( !is_array( $addToContent ) ){
        $addToContent = array(
          "name"    => pathinfo( $path, PATHINFO_FILENAME )
        );
      }

      $addToContent["content"] = $content;

      bof()->response->html->add_content( $addToContent["name"], $addToContent );

    }

    return $content;

  }

}

?>
