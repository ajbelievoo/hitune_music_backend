<?php

if ( !defined( "bof_root" ) ) die;

class lyric_azlyrics extends bof_type_class {

	public function fetch( $item ){

		$song_name = preg_replace('/[\W_]+/', '', $item["title"]);
		$song_name = strtolower($song_name);

		$artist_name = preg_replace('/[\W_]+/', '', $item["bof_dir_artist"]["name"] );
		$artist_name = strtolower($artist_name);

		$proxy = bof()->object->db_setting->get( "azlyrics_proxy" );
		$curlArr = array(
			"url" => "https://www.azlyrics.com/lyrics/{$artist_name}/{$song_name}.html",
			"json" => false,
			"type" => "file",
			"agent" => "chrome",
			"cache" => true,
			"cache_save" => true,
			"cache_load" => true,
			"timeout" => 30,
			"ctimeout" => 20
		);

		if ( $proxy ){
			$curlArr["proxy"] = $proxy;
		}

		$get = bof()->curl->exe( $curlArr );

		if ( $get["http_code"] != 200 )
		throw new Exception("connection_failed");

		try {
			$o = explode( "Sorry about that. -->", $get["body"] );
			$o2 = explode(  "</div>", $o[1] );
			$o3 = str_replace( [ "<br>" ], PHP_EOL, $o2[0] );
			$o4 = strip_tags( $o3, "" );
			$o5 = trim( $o4 );
			$ps = [];
			foreach( explode( PHP_EOL, $o5 ) as $s ){
				$s = trim( $s );
				if ( $s )
				$ps[] = $s;
			}
			$lyrics = implode( PHP_EOL, $ps );
		} catch( Error|Exception $err ){
			throw new Exception("invalid_body");
		}

		return array(
			"type" => "string",
			"data" => $lyrics
		);

	}

}

?>
