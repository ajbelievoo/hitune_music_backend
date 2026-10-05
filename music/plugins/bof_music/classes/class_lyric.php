<?php

if ( !defined( "bof_root" ) ) die;

class lyric extends bof_type_class {

	public function __construct(){
	}
	
	public function is_automated(){
		$api = bof()->object->db_setting->get( "lyrics_source" );
		return $api ? ( $api != "none" ? $api : false ) : false;
	}

	public function fetch( $item ){

		$source = $this->is_automated();
		if ( !$source ) return false;

		try {
			$fetch = bof()->__get( "lyric_" . $source )->fetch( $item );
		} catch( Exception|bofException $err ){
			return false;
		}

		if ( $fetch !== false ){
			if ( $fetch["type"] == "string" ){
				bof()->object->m_track->update(
					array(
						"ID" => $item["ID"]
					),
					array(
						"lyrics" => $fetch["data"]
					)
				);
				$fetch["data"] = str_replace( PHP_EOL, "<br>", $fetch["data"] );
			}
		}

		return $fetch;

	}

}

?>
