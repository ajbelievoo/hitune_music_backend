<?php

if ( !defined( "bof_root" ) ) die;

class ai_service_text extends ai_service {

  public function generateFromText( $prompt, $args=[] ){

    $json = false;
    extract( $args );

    $do = $this->core->generate_text_from_text( $prompt, $args );

    if ( $json ){
      try {
        $do = json_decode( $do, true );
      } catch (Exception|Warning $e) {
        throw new Exception("{$this->core_name}.text Invalid json returned");
      }
    }

    return $do;

  }
  public function generateFromAudio( $filePath, $args=[] ){

    return $this->core->generate_text_from_audio( $filePath, $args );

  }


}

?>
