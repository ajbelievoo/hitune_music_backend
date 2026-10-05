<?php

if ( !defined( "bof_root" ) ) die;

class ai_service_image extends ai_service {

  public function generateFromText( $prompt, $args=[] ){
    return $this->core->generate_image_from_text( $prompt, $args );
  }

}

?>
