<?php

if ( !defined( "bof_root" ) ) die;

class torrent {

  protected $clients_pre_datas = array(

    "deluge" => array(
      "path" => "third/kaysond/deluge.class.php"
    ),
    "transmission" => array(
      "path" => "third/kleiram_transmission-php/autoload.php"
    ),
    "1337x" => array(
      "path" => "scrapers/1337x.php"
    ),
    "limetorrents" => array(
      "path" => "scrapers/limetorrents.php"
    ),
    "thepiratebay" => array(
      "path" => "scrapers/thepiratebay.php"
    ),

  );

  protected $options = array();
  protected $clients = [];

  public function set_option( $var, $val ){
    $this->options[ $var ] = $val;
  }
  public function get_option( $var, $fallback_val=null ){
    if ( isset( $this->options[ $var ] ) ) return $this->options[ $var ];
    return $fallback_val;
  }

  protected function getClient( $name ){

    if ( !$name ) return false;
    if ( !in_array( $name, array_keys( $this->clients_pre_datas ) ) ) return false;
    if ( !empty( $this->clients[ $name ] ) )
    return $this->clients[ $name ];

    $client_pre_data = $this->clients_pre_datas[ $name ];
    require_once( dirname(__FILE__)."/{$client_pre_data["path"]}" );

    if ( $name == "deluge" ){
      if ( ( $deluge_web_api_host = $this->get_option( "deluge_web_api_host" ) ) && ( $deluge_web_api_password = $this->get_option( "deluge_web_api_password" ) ) )
      $client = new deluge( $deluge_web_api_host, $deluge_web_api_password );
      else
      return false;
    }
    elseif ( $name == "transmission" ){
      if ( !$this->get_option( "transmission_rpc_addr" )  || !$this->get_option( "transmission_user_pwd" ) )
      return false;
      return true;
    }
    elseif ( $name == "1337x" )
    $client = new x1337( bof() );
    else
    $client = new $name( bof() );

    $this->clients[ $name ] = $client;
    return $client;

  }

  public function search( $source, $query ){

    $client = $this->getClient( $source );
    if ( !$client ) return false;

    return $client->scrap_search_result( $query, "seeders", 1 );

  }
  public function get_magnet( $source, $link ){

    $client = $this->getClient( $source );
    if ( !$client ) return false;

    return $client->scrap_mag_from_link( $link );

  }

  public function deluge_add( $torrent_hash, $args=[] ){

    $deluge = $this->getClient( "deluge" );
    if ( !$deluge ) return false;

    $download_dir_location = null;
    extract( $args );
    if ( !$download_dir_location ) return false;

    $deluge->getWebAPIVersion( "magnet:?xt=urn:btih:{$torrent_hash}", [
			"download_location"   => "{$download_dir_location}/doing/{$torrent_hash}/",
			"move_completed_path" => "{$download_dir_location}/done/{$torrent_hash}/"
		] );

  }
  public function deluge_check( $torrent_hash, $args=[] ){

    $deluge = $this->getClient( "deluge" );
    if ( !$deluge ) return false;

    $get = $deluge->getTorrents( [ strtolower( $torrent_hash ) ], [ 'progress', 'time_added' ] );
    if ( !$get ) return false;

    return (array) @reset( $get );

  }
  public function deluge_remove( $torrent_hash, $args=[] ){

    $deluge = $this->getClient( "deluge" );
    if ( !$deluge ) return false;

    $deluge->removeTorrent( strtolower( $torrent_hash ), true );

  }

  public function transmission_add( $torrent_hash, $args=[] ){

    $this->__transmission_request( "torrent-add", array(
      "paused" => false,
      "download-dir" => $this->get_option( "transmission_dl_dir" ) . strtolower( $torrent_hash ),
      "filename" => "magnet:?xt=urn:btih:" . $torrent_hash
    ) );

    return true;

  }
  public function transmission_add_file( $torrent_hash, $torrent, $args=[] ){

    return $this->__transmission_request( "torrent-add", array(
      "paused" => false,
      "download-dir" => $this->get_option( "transmission_dl_dir" ) . strtolower( $torrent_hash ),
      "metainfo" => base64_encode( $torrent )
    ) );

    return true;

  }
  public function transmission_remove( $torrent_hash, $args=[] ){

    $this->__transmission_request( "torrent-remove", array(
      "ids" => $torrent_hash,
      "delete-local-data" => true
    ) );

    return true;

  }
  public function transmission_check( $torrent_hash, $args=[] ){

    $req = $this->__transmission_request( "torrent-get", array(
      "fields" => array(
        "id",
        "error",
        "errorString",
        "eta",
        "isFinished",
        "isStalled",
        "leftUntilDone",
        "metadataPercentComplete",
        "peersConnected",
        "peersGettingFromUs",
        "peersSendingToUs",
        "percentDone",
        "queuePosition",
        "rateDownload",
        "rateUpload",
        "recheckProgress",
        "seedRatioMode",
        "seedRatioLimit",
        "sizeWhenDone",
        "status",
        "trackers",
        "downloadDir",
        "uploadedEver",
        "uploadRatio",
        "webseedsSendingToUs"
      ),
      "ids" => $torrent_hash
    ) );

    if ( empty( $req["torrents"][0] ) )
    throw new Exception( "noTorrentFound" );

    return $req["torrents"][0];

  }
  protected function __transmission_request( $method, $posts=[], $args=[] ){

    $x_sess_id = bof()->object->db_setting->get( "trans_x_sess_id", "idk" );
    extract( $args );

    $req = bof()->curl->exe( array(
      "url" => $this->get_option( "transmission_rpc_addr" ),
      "headers" => array(
        "X-Transmission-Session-Id: " . $x_sess_id
      ),
      "auth" => CURLAUTH_BASIC,
      "auth_pass" => $this->get_option( "transmission_user_pwd" ),
      "posts" => json_encode( array(
        "method" => $method,
        "arguments" => $posts
      ) ),
      "proxy" => false,
      "cache" => true,
      "cache_save" => true
    ) );

    if ( $req["http_code"] == 200 && !empty( $req["data"]["result"] ) ? $req["data"]["result"] === "success" : false ){
      return $req["data"]["arguments"];
    }
    elseif ( $req["http_code"] == 409 ){

      $headers = bof()->general->explode_by_line( $req["header"] );
      foreach( $headers as $header ){
        if ( strtolower( substr( $header, 0, strlen( "X-Transmission-Session-Id" ) ) ) == strtolower( "X-Transmission-Session-Id" ) )
        $new_x_sess_id = @trim( explode( ":", $header )[1] );
      }

      if ( !empty( $new_x_sess_id ) ){
        bof()->object->db_setting->set( "trans_x_sess_id", $new_x_sess_id );
        return $this->__transmission_request( $method, $posts, array_merge( $args, array(
          "x_sess_id" => $new_x_sess_id
        ) ) );
      }

    }

    throw new Exception( "Request Failed: {$method}" );

  }

}

?>
