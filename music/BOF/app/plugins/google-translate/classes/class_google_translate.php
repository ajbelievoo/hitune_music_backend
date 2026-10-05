<?php

if ( !defined( "bof_root" ) ) die;

use \Statickidz\GoogleTranslate;

class google_translate {

  public function getClient(){

    require_once (dirname(__FILE__)."/third/statickidz_php_google_translate_free/autoload.php");
    $client = new GoogleTranslate();
    return $client;

  }

  public function translate( $text, $source, $target ){

    if ( ( $cached = $this->__cached( $text, $source, $target ) ) )
    return $cached["target_text"];

    $client = $this->getClient();
    $target_text = @$client->translate( $source, $target, $text );

    if ( ( $cached = $this->__cached( $text, $source, $target ) ) )
    return $cached["target_text"];

    $this->__cache( $text, $source, $target, $target_text );
    return $target_text;

  }

  protected function __cached( $text, $source, $target ){

    $hash = md5( $text . $source . $target );
    $cached = bof()->db->_select(array(
      "table" => "_bof_p_google_translate_cache",
      "where" => array(
        [ "hash", "=", $hash ]
      ),
      "limit" => 1,
      "single" => true
    ));

    if ( $cached ){
      bof()->db->_update(array(
        "table" => "_bof_p_google_translate_cache",
        "where" => array(
          [ "ID", "=", $cached["ID"] ]
        ),
        "set" => array(
          [ "used", $cached["used"]+1 ],
          [ "time_used", "now()", true ]
        )
      ));
    }

    return $cached;

  }
  protected function __cache( $text, $source, $target, $translation ){

    $hash = md5( $text . $source . $target );
    bof()->db->_insert(array(
      "table" => "_bof_p_google_translate_cache",
      "set" => array(
        [ "hash", $hash ],
        [ "source_text", $text ],
        [ "source_lang", $source ],
        [ "target_text", $translation ],
        [ "target_lang", $target ]
      )
    ));

  }

}

?>
