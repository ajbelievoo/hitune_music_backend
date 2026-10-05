<?php

class object_html {

  protected $checked_doms = [];

  public function dom_client( $name ){

    if ( !in_array( $name, $this->checked_doms, true ) ){

      if ( !( $path = bof()->object->core_files->validate_key( "html_dom", $name ) ) )
      fall("html_dom {$name} is undefined");

      if ( !file_exists( $path ) )
      fall("html_dom {$name} file: {$path} is missing");

      require_once( realpath( $path ) );

      if ( !class_exists( "html_dom_{$name}" ) )
      fall("html_dom {$name} class missing");

      $this->checked_doms[] = $name;

    }

    $_f_n = "html_dom_{$name}";
    $dom = new $_f_n( bof(), bof()->object->html );
    return $dom;

  }

  public function dom_display( $name, $args, $display=true ){

    $output = $this
    ->dom_client( $name )
    ->display( $args, $display );

    return $output;

  }

  public function dom_validate( $name, $args ){

    $args = $this
    ->dom_client( $name )
    ->validate( $args );

    return $args;

  }

  public function validate_args( $args ){

    $content = [];
    $scripts = [];
    $styles = [];
    $bodyClass = [];
    $langCode = "en";
    $metaDatas = [];
    $httpHeaders = [];
    extract( (array) $args );



    return (object) array(
      "content"     => $content,
      "scripts"     => $scripts,
      "styles"      => $styles,
      "bodyClass"   => $bodyClass,
      "langCode"    => $langCode,
      "metaDatas"   => $metaDatas,
      "httpHeaders" => $httpHeaders
    );

  }

}

?>
