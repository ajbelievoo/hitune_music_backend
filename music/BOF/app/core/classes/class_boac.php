<?php

if ( !defined( "bof_root" ) ) ;

class boac extends bof_type_class {

  protected $endpoint_address = "https://api.busyowl.co/";

  public function get_script_version(){

    $req = $this->_request(
      "get_script_version"
    );

    if ( !$req ) return $req;
    return $req["version"];

  }
  public function get_extensions( $types, $simple=false ){

    $req = $this->_request(
      "get_extensions",
      array(
        "posts" => array(
          "types" => implode( ",", $types ),
          "simple" => $simple ? "yes" : false
        )
      )
    );

    if ( !$req ) return $req;
    return $req;

  }
  public function get_release( $extension_type, $extension_name, $extension_version ){

    $req = $this->_request(
      "get_release",
      array(
        "params" => array(
          "type" => $extension_type,
          "name" => $extension_name,
          "version" => $extension_version
        )
      )
    );

    if ( !$req ) return $req;
    return $req;

  }

  public function check_spc( $code ){

    $req = $this->_request(
      "check_spc",
      array(
        "params" => array(
          "code" => $code
        ),
        "skip_listing" => true
      ),
      true
    );

    if ( !$req )
    throw new exception( "request failed" );

    return $req;

  }
  public function submit_spc( $code, $email, $domain ){

    $req = $this->_request(
      "submit_spc",
      array(
        "params" => array(
          "code" => $code,
          "email" => $email,
          "domain" => $domain
        ),
        "skip_listing" => true
      ),
      true
    );

    if ( !$req )
    throw new exception( "request failed" );

    return $req;

  }
  public function submit_ppc( $code ){

    $req = $this->_request(
      "submit_ppc",
      array(
        "params" => array(
          "code" => $code,
        )
      ),
      true
    );

    if ( !$req )
    throw new exception( "request failed" );

    return $req["messages"][0];

  }

  public function download_release( $url, $args=[] ){

    $__path = parse_url( $url, PHP_URL_PATH );
    $extension = pathinfo( $__path, PATHINFO_EXTENSION) ;
    $filename = urldecode( pathinfo( $__path, PATHINFO_FILENAME ) );
    $sub_directory = "release";
    $expected_header = 200;
    $tmp_file = base_root . "/" . bof()->object->core_setting->get( "file_save_base_directory" ) . "/tmp_" . uniqid();
    extract( $args );

    // load url content
    $data = bof()->curl->exe( array(
      "url" => $url,
      "cache" => false,
      "cache_save" => false,
      "cache_load" => false,
      "proxy" => false
    ) );

    if ( !empty( $data["error"] ) )
    throw new bofException( "cURL request failed: {$data["error"]}" );

    if ( $data["http_code"] != 200 )
    throw new bofException( "cURL request failed: http_header: {$data["http_code"]}" );

    fopen( $tmp_file, 'w+' );

    if ( !is_writable( $tmp_file ) )
    throw new bofException( "RKHM can not access {$tmp_file} for writing. Give RKHM sufficient access to your files" );

    file_put_contents( $tmp_file, $data["body"] );

    $saved = base_root . bof()->file->save( $tmp_file, array(
			"filename"      => $filename,
			"extension"     => $extension,
			"sub_directory" => $sub_directory,
      "remove_src" => true
		) );

    return $saved;

  }

  public function varys( $type ){

    $items = bof()->db->_select(array(
      "table" => "_c_" . substr( $type, 0, 1 ) . "_automate_cache",
      "where" => array(
        [ "sta", null, null, true ]
      ),
      "limit" => $type == "podcast" ? 100 : 10,
      "single" => false
    ));

    if ( !$items )
    return;

    foreach( $items as $item )
    $IDs[] = $item["ID"];

    $req = $this->_request(
      "varys_{$type}",
      array(
        "posts" => array(
          "ID" => implode( ",", $IDs )
        )
      )
    );

    bof()->db->query("UPDATE _c_" . substr( $type, 0, 1 ) . "_automate_cache SET sta = 1 WHERE ID IN ( '".implode( "' ,'", $IDs )."' ) ");

    if ( !$req ) return $req;
    return $req["list"];

  }
  public function varys_radio(){
    return $this->_bof_this->varys( "radio" );
  }
  public function varys_podcast(){
    return $this->_bof_this->varys( "podcast" );
  }
  public function varys_audiobook(){
    return $this->_bof_this->varys( "audiobook" );
  }
  public function varys_filters( $type, $args=[] ){

    $req = $this->_request(
      "varys_filters",
      array(
        "params" => array(
          "type" => $type
        )
      )
    );

    if ( !$req ) return $req;
    return $req;

  }
  public function varys_filters_check( $type, $filters, $save=false, $args=[] ){

    $req = $this->_request(
      "varys_filters_check",
      array(
        "posts" => array(
          "type" => $type,
          "filters_cs" => is_array( $filters["cs"] ) ? implode( ",", $filters["cs"] ) : $filters["cs"],
          "filters_ls" => is_array( $filters["ls"] ) ? implode( ",", $filters["ls"] ) : $filters["ls"],
          "save" => $save ? "yes" : "no"
        )
      )
    );

    if ( $save ? !empty( $req["ids"] ) : false ){
      $table = "_c_".substr($type,0,1)."_automate_cache";
      bof()->db->query("TRUNCATE {$table}");
      $ids_c = count($req["ids"]);
      for ( $h=0; $h<ceil($ids_c/500); $h++ ){
        $h_ids = array_slice( $req["ids"], 500*$h, 500 );
        if ( !empty( $h_ids ) )
        bof()->db->query("INSERT INTO {$table} ( ID ) VALUES (" . implode("), (",$h_ids) . ")");
      }
    }

    if ( !$req ) return $req;
    return $req;

  }

  public function spotify_scrapper_keys( $proxy ){

    $req = $this->_request(
      "spotify_scrapper_keys",
      array(
        "posts" => array(
          "proxy" => $proxy
        )
      )
    );

    return $req["keys"];

  }
  public function spotify_scrapper_keys_can(){

    $req = $this->_request(
      "spotify_scrapper_keys_check",
      array()
    );

    return $req;

  }

  public function whatsapp_notify( $to, $message ){

    $this->_request(
      "whatsapp_notify",
      array(
        "posts" => array(
          "to" => $to,
          "message" => $message
        )
      )
    );

  }
  public function telegram_notify( $to, $message ){

    $this->_request(
      "telegram_notify",
      array(
        "posts" => array(
          "to" => $to,
          "message" => $message
        )
      )
    );

  }

  public function evm_gateway_get_link( $coin, $amount ){

    return $this->_request( "pg_evm_get", array(
      "posts" => array(
        "coin" => $coin,
        "amount" => $amount
      )
    ) );

  }
  public function evm_gateway_get_check( $id ){

    return $this->_request( "pg_evm_check", array(
      "posts" => array(
        "ID" => $id
      )
    ) );

  }

  protected function _request( $endpoint, $args=[], $returnFailure=false ){

    $params = [];
    $posts = [];
    $headers = array(
      'x-bof-script-version: ' . ( ( defined("beta_tester") && bof()->getName() == "bof_admin" ) ? version-1 : version ),
      'x-bof-version: ' . ( ( defined("beta_tester") && bof()->getName() == "bof_admin" ) ? bof_version-1 : bof_version ),
      'x-bof-purchase-code: ' . purchase_code,
      'x-bof-sign-code: ' . sign_code,
      'x-bof-web-address: ' . web_address,
      'x-bof-http-host: ' . ( !empty( $_SERVER["HTTP_HOST"] ) ? $_SERVER["HTTP_HOST"] : null ),
      'x-bof-server-name: ' . ( !empty( $_SERVER["SERVER_NAME"] ) ? $_SERVER["SERVER_NAME"] : null ),
      'x-bof-diagnostic: ' . ( defined("api_send_diagnostics") ? api_send_diagnostics : "undefined" ),
      'x-bof-plugins: ' . json_encode(
        array(
          "exists" => empty( $args["skip_listing"] ) ? bof()->plug->existing_plugins( true ) : null,
          "active" => empty( $args["skip_listing"] ) ? bof()->plug->activated_plugins() : null
        )
      )
    );
    $raw_url = false;
    extract( $args );

    $url = $endpoint;
    if ( !$raw_url )
    $url = ( defined( "beta_tester" ) ? beta_tester : $this->endpoint_address ) . $endpoint . ( $params ? "?" . http_build_query( $params ) : "" );

    $exe_curl = bof()->curl->exe(array(
      "url" => $url,
      "posts" => $posts,
      "headers" => $headers,
      "cache" => false,
      "cache_save" => false,
      "cache_load" => false,
      "proxy" => false,
      "echo" => false,
      "ctimeout" => 20,
      "timeout" => 600
    ));

    $data = $exe_curl["data"];

    if ( empty( $data ) ? true : !is_array( $data ) )
    return false;

    if ( empty( $data["success"] ) ){
      if ( $returnFailure )
      throw new exception( $data["messages"][0] );
      else return false;
    }

    if ( in_array( "invalid_certificate", array_keys( $data ), true ) )
    bof()->object->db_setting->set( "_ic", !empty( $data["invalid_certificate"] ) ? 1 : 0 );

    return $data;

  }

}

?>
