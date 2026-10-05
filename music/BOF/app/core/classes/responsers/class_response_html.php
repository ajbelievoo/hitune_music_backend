<?php

if ( !defined( "bof_root" ) ) die;

class response_html {

  private $data = array(
    "content" => [],
    "assets" => [
      "css" => [],
      "js" => []
    ],
    "bodyClass" => [],
    "langCode" => "",
    "metaDatas" => [],
    "httpHeaders" => []
  );

  public function __construct(){
    $this->data = (object) $this->data;
  }
  public function set( $args ){

    if ( !( $args = bof()->object->html->validate_args( $args ) ) )
    fall( "Response HTML: Set: Invalid Args" );
    extract( (array) $args );

    if ( $metaDatas ){
      foreach( $metaDatas as $metaData_name => $metaData_args ){
        $this->add_metaData( $metaData_name, $metaData_args );
      }
    }

    if ( $bodyClass ){
      foreach( $bodyClass as $_a_bodyclass ){
        $this->add_bodyClass( $_a_bodyclass );
      }
    }

    if ( $content ){
      foreach( $content as $_a_content_name => $_a_content ){
        $this->add_content( $_a_content_name, $_a_content );
      }
    }

    if ( $styles ){
      foreach( $styles as $a_style_name => $a_style ){
        $this->add_asset( "css", $a_style_name, $a_style );
      }
    }

    if ( $scripts ){
      foreach( $scripts as $a_java_name => $a_java ){
        $this->add_asset( "js", $a_java_name, $a_java );
      }
    }

    return $this;

  }
  public function display(){

    /*if ( !empty( $this->http_headers ) ){
      foreach( $this->http_headers as $http_header ){
        header( $http_header );
      }
    }*/

    echo "<!DOCTYPE html>\n";
    echo "<html>\n";
    echo "<head>\n";
    echo bof()->response->html->display_metaDatas( true );
    echo bof()->response->html->display_styles( true );
    echo "</head>\n";
    $__C = bof()->response->html->display_content( true );
    echo bof()->object->html->dom_display( "wrapper", array(
      "tag" => "body",
      "class" => $this->data->bodyClass,
      "content" => $__C ? trim( $__C ) : ""
    ), false );
    echo bof()->response->html->display_javas( true );
    echo "</html>";

  }
  private function sort_by_priority( $a, $b ) {
    return $b['priority'] - $a['priority'];
  }

  public function add_content( $name, $args ){

    if ( !is_array( $args ) )
    fall("Response: Html: Add content: invalid args");

    $content = null;
    $priority = 1;
    $wrapper = null;
    extract( $args );

    if ( $content === null )
    fall( "Response: Html: Add content: {$name} has no content!" );

    if ( $wrapper ? is_array( $wrapper ) : false ){
      $wrapper["content"] = $content;
      $content = bof()->object->html->dom_display( "wrapper", $wrapper, false );
    }

    $this->data->content[ $name ] = array(
      "content"  => $content,
      "priority" => $priority
    );

    return true;

  }
  public function display_content( $return=false ){

    if ( !$this->data->content ) return;
    $data = $this->data->content;
    $output = "";

    usort(
      $data ,
      [ $this, "sort_by_priority" ]
    );

    foreach( $data as $_item ){
      $output .= $_item["content"] . PHP_EOL;
    }

    if ( $return ) return $output;
    echo $output;

  }

  public function add_metaData( $name, $args ){

    if ( !$this->validate_metaData( $args ) )
    return false;

    $this->data->metaDatas[ $name ] = $args;
    return true;

  }
  public function validate_metaData( &$args ){

    if ( !is_array( $args ) )
    return false;

    $wrapper  = null;
    $content  = null;
    $attributes = [];
    $priority = 1;
    extract( $args );

    if ( !$content )
    return false;

    $final_content = $content;

    if ( $wrapper )
    $final_content = "<{$wrapper}>{$content}</{$wrapper}>";

    $args = array(
      "wrapper"       => $wrapper,
      "content"       => $content,
      "priority"      => $priority,
      "attributes"    => $attributes,
      "final_content" => $final_content
    );

    return true;

  }
  public function display_metaDatas( $return=false ){

    $output = "";
    if ( !$this->data->metaDatas ) return;
    $data = $this->data->metaDatas;

    usort(
      $data ,
      [ $this, "sort_by_priority" ]
    );

    foreach( $data as $_item ){
      $output .= $_item["final_content"] . PHP_EOL;
    }

    if ( $return ) return $output;
    echo $output;

  }

  public function add_asset( $type, $name, $args ){

    if ( ( $args = bof()->response->html->validate_asset( $type, $args ) ) == false )
    return false;

    $this->data->assets[ $type ][ $name ] = $args;
    return true;

  }
  public function validate_asset( $type, $args ){

    if ( !is_array( $args ) )
    return false;

    $placements = [
      "theme_path" => null,
    ];
    $address = null;
    $priority = 1;
    extract( $args );

    if ( !$address )
    return false;

    foreach( $placements as $key => $val ){
      if ( preg_match( "/%{$key}%/", $address ) ){
        if ( $key == "theme_path" && $val === null )
        $val = bof()->response->theme->active()->get_address();
        if ( !$val ) continue;
        $address = str_replace( "%$key%", $val, $address );
      }
    }

    $final_address = $address;

    $args = array(
      "priority" => $priority,
      "address" => $address,
      "final_address" => $final_address
    );

    return $args;

  }
  public function display_styles( $return=false ){

    $output = "";
    if ( !$this->data->assets["css"] ) return;
    $data = $this->data->assets["css"];

    usort(
      $data ,
      [ $this, "sort_by_priority" ]
    );

    foreach( $data as $_item ){
      $output .= "<link href='{$_item["final_address"]}' rel='stylesheet' media='all' type='text/css' >" . PHP_EOL;
    }

    if ( $return ) return $output;
    echo $output;

  }
  public function display_javas( $return=false ){

    $output = "";
    if ( !$this->data->assets["js"] ) return;
    $data = $this->data->assets["js"];

    usort(
      $data ,
      [ $this, "sort_by_priority" ]
    );

    $output .= PHP_EOL;
    foreach( $data as $_item ){
      $output .= "<script src='{$_item["final_address"]}'></script>" . PHP_EOL;
    }

    if ( $return ) return $output;
    echo $output;

  }

  public function add_bodyClass( $name ){
    $this->data->bodyClass[] = $name;
  }

}

?>
