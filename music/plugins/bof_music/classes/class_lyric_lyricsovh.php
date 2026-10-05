<?php

if ( !defined( "bof_root" ) ) die;

class lyric_lyricsovh extends bof_type_class {

	public $api_base = "https://api.lyrics.ovh/v1/";

	public function fetch( $item ){

		$get = bof()->curl->exe( array(
			"url" => $this->api_base . urlencode( $item["bof_dir_artist"]["name"] ) . "/" . urlencode( $item["title"] ),
			"json" => true,
			"type" => "json",
			"cache" => true,
			"cache_save" => true,
			"cache_load" => true
		) );

		if ( $get["http_code"] != 200 )
		throw new Exception("not_found");

		if ( empty( $get["data"]["lyrics"] ) )
		throw new Exception("invalid_body");

		return array(
			"type" => "string",
			"data" => $get["data"]["lyrics"]
		);

	}

}

?>
