<?php

if ( !defined( "bof_root" ) ) die;

class wikipedia_api {

	public $api_base = "https://en.wikipedia.org/w/api.php";

	protected $config = array(
		"cache" => true,
		"cache_load" => true,
		"cache_age" => 12,
		"cache_update" => true,
		"req_interval" => 0.3
	);

	public function request( $params = array(), $config = [] ){

		$_def_params = array(
			"format" => "json",
		);

		extract( $this->config );
		if ( $config )
		extract( $config );

		$headers = array();
		if ( $_def_params ) $params = $params ? array_merge( $_def_params, $params ) : $_def_params;
		$params_string = $params ? http_build_query( $params ) : false;

		$full_url = $this->api_base . ( !empty( $params_string ) ? "?" . $params_string : "" );
		$exec_curl = bof()->curl->exe( array(
			"url"          => $full_url,
			"hook"         => md5( $full_url ),
			"headers"      => $headers,
			"cache"        => $cache,
			"cache_load"   => $cache_load,
			"cache_age"    => $cache_age,
		) );

		if ( $this->config["req_interval"] )
		usleep( $this->config["req_interval"] * 1000000 );

		return $exec_curl["data"];

	}

	public function search( $query, $args=[] ){

		$prop = "pageimages";
		$piprop = "original";
		extract( $args );

		$exe_req = bof()->wikipedia_api->request( array(
			"action" => "query",
			"prop" => $prop,
			"piprop" => $piprop,
			"titles" => $query,
		) );

		return $exe_req;

	}
	public function license( $filename ){

		$prop = "imageinfo";
		$iiprop = "extmetadata";

		$exe_req = bof()->wikipedia_api->request( array(
			"action" => "query",
			"prop" => $prop,
			"iiprop" => $iiprop,
			"titles" => "File:{$filename}"
		) );
		return $exe_req;

	}


}

?>
