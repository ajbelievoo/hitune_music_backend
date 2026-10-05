<?php

if ( !defined( "bof_root" ) ) die;

class ai_service_speech extends ai_service {

  // general
  public function get_all_available_cores( $pName, $onlyActive=true, $for=null ){

    $_cores = [];
    foreach( bof()->ai->get_cores( "speech" ) as $coreName => $coreArgs ){

      if ( $onlyActive ? !bof()->object->db_setting->get( $pName . "_" . $coreName ) : false )
      continue;

      if ( $for ? ( $for == "cloning" ? empty( $coreArgs["cloning"] ) : false ) : false )
      continue;

      if ( $for && $onlyActive ? ( $for == "cloning" ? !bof()->object->db_setting->get( $pName . "_" . $coreName . "_clone" ) : false ) : false )
      continue;

      $_cores[] = $coreName;

    }

    return $_cores;

  }
  public function get_all_available_voices( $pName, $filters=[] ){

    $_cores = false;
    $elevenlabs_shared = false;
    extract( $filters );

    $voices = [];
    foreach( bof()->ai->get_cores( "speech" ) as $coreName => $coreArgs ){

      if ( $_cores ? !in_array( $coreName, $_cores, true ) : false ) continue;

      try {
        $getVoices = bof()->ai->set_setting_db_var($pName)->__get( $coreName )->check_settings('none')->getVoices(array(
          "elevenlabs_shared" => $elevenlabs_shared
        ));
        if ( $getVoices ) $voices = array_merge( $voices, $getVoices );
      } catch ( Exception|bofException $err ){}

    }

    return $voices;

  }

  // core
  public function generateFromText( $prompt, $args=[] ){

    $max_tries = 30;
    $interval = 8;
    $voice_id = null;
    $force_download = false;
    extract( $args );

    try {
      $requestCreation = $this->core->ttsSubmit( $prompt, $voice_id, $args );
      if ( empty( $requestCreation["source_id"] ) ) throw new Exception("failed");
    } catch( Exception|bofException $err ){
      throw new Exception( "TTS submit failed: " . $err->getMessage() );
    }

    if ( !empty( $requestCreation["blob"] ) ){

      bof()->file->mkdir( base_root . "/files/tts_blob" );
      $_id = uniqid() . "-" .  uniqid() . "-" .  uniqid();
      file_put_contents( base_root . "/files/tts_blob/{$_id}.mp3", $requestCreation["blob"] );

      $words = str_word_count( $prompt );
      bof()->ai_service->fee( "speech", $this->core_name, "speech_from_text", array(
        "text" => $prompt,
        "characters" => mb_strlen( $prompt, "utf-8" ),
        "words" => $words ? $words : count( explode( " ", $prompt ) ),
        "total_price" => 0
      ) );

      return [ $requestCreation["source_id"], web_address . "/files/tts_blob/{$_id}.mp3", "blob" ];

    }

    $tries = 0;
    while( $tries < $max_tries ){

      $requestCreationCheck = $this->core->ttsCheck( $requestCreation["source_id"] );

      if ( !empty( $requestCreationCheck ) ){

        $words = str_word_count( $prompt );
        bof()->ai_service->fee( "speech", $this->core_name, "speech_from_text", array(
          "text" => $prompt,
          "characters" => mb_strlen( $prompt, "utf-8" ),
          "words" => $words ? $words : count( explode( " ", $prompt ) ),
          "total_price" => 0
        ) );

        if ( $force_download ){

          bof()->file->mkdir( base_root . "/files/tts_blob" );
          $_id = uniqid() . "-" .  uniqid() . "-" .  uniqid();
          file_put_contents( base_root . "/files/tts_blob/{$_id}.mp3", bof()->curl->exe(array(
            "url" => $requestCreationCheck,
            "agent" => "chrome",
            "type" => "file",
            "json" => false
          ))["body"] );

          return [ $requestCreation["source_id"], web_address . "/files/tts_blob/{$_id}.mp3", "blob" ];

        }

        return [ $requestCreation["source_id"], $requestCreationCheck, "url" ];
        break;

      }

      sleep( $interval );
      $tries++;

    }

    throw new Exception( "TTS creation failed: timed out" );

  }

  // models
  public function generateModelFromSpeech( $voiceName, $voiceGender, $voicePath ){
    return $this->core->cloneVoice( $voiceName, $voiceGender, $voicePath );
  }
  public function checkGenerateModelFromSpeech( $jobID, $data ){
    return $this->core->cloneVoiceCheck( $jobID, $data );
  }
  public function removeGenerateModelFromSpeech( $voiceID ){
    return $this->core->removeClonedVoice( $voiceID );
  }

}

?>
