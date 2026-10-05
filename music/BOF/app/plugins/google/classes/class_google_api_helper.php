<?php

if ( !defined( "bof_root" ) ) die;

class google_api_helper {

  protected $tokens = [];
  protected $key = null;

  protected function getClient(){
    require_once( google_plugin_root . "/third/google-api-php-client/vendor/autoload.php" );
  }

  public function setTokens( $data ){
    $this->tokens = $data;
    return $this;
  }

  public function setKey( $data ){
    $this->key = hex2bin( $data );
    return $this;
  }

  public function likeVideo( $youtubeID, $type="like" ){

    $this->getClient();


    $client = new Google_Client();
    $client->setClientId( bof()->object->db_setting->get( "sl_gg_id" ) );
    $client->setClientSecret( bof()->object->db_setting->get( "sl_gg_secret" ) );
    $client->setAccessToken( $this->tokens );

    if ( $client->isAccessTokenExpired() ){

      $newTokens = $client->refreshToken( $this->tokens["refresh_token"] );
      $lock_tokens = bof()->crypto->lock( json_encode( $newTokens ), [ "key" => $this->key ] );
      $userExtraData = bof()->user->get()->data["extraData_decoded"];
      $userExtraData["google_token"] = array(
        "sign" => $lock_tokens["sign"],
        "nonce" => $lock_tokens["nonce"]
      );

      bof()->object->user->update(
        array(
          "ID" => bof()->user->get()->ID
        ),
        array(
          "extraData" => json_encode( $userExtraData )
        )
      );

    }

    $youtube = new Google_Service_YouTube( $client );
    $like = $youtube->videos->rate( $youtubeID, $type );
    if ( $like )
    return true;
    return false;


  }

}

?>
