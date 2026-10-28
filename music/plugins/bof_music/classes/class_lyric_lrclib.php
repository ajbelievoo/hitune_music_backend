<?php

if ( !defined( "bof_root" ) ) die;

/**
 * LRCLIB lyrics provider — free, no key, returns plain + synced lyrics.
 * https://lrclib.net/docs
 */
class lyric_lrclib extends bof_type_class {

	public $api_base = "https://lrclib.net";

	public function fetch( $item ){

		$artist = $this->_artist_name( $item );

		$title = preg_replace( "/\s*[\(\[].*?[\)\]]\s*/", " ", (string)$item["title"] );
		$title = trim( $title );
		if ( !strlen($title) || !strlen($artist) ) return false;

		// exact match first (duration-tolerant)
		$query = array(
			"track_name" => $title,
			"artist_name" => $artist,
		);
		if ( !empty( $item["duration"] ) )
			$query["duration"] = (int)$item["duration"];

		$get = $this->_get( "/api/get", $query );
		if ( is_array($get) && ( !empty( $get["plainLyrics"] ) || !empty( $get["syncedLyrics"] ) ) )
			return $this->_pack( $get );

		// fallback: fuzzy search (exact artist first, then title-only)
		foreach ( array(
			array( "track_name" => $title, "artist_name" => $artist ),
			array( "q" => $title . " " . $artist ),
		) as $q ){
			$get = $this->_get( "/api/search", $q );
			if ( !is_array( $get ) ) continue;
			$best = null; $bestScore = 0;
			foreach( $get as $row ){
				if ( empty($row["plainLyrics"]) && empty($row["syncedLyrics"]) ) continue;
				if ( !empty($row["instrumental"]) ) continue;
				$score = 50;
				if ( !empty($item["duration"]) && !empty($row["duration"]) ){
					$diff = abs( (int)$item["duration"] - (int)$row["duration"] );
					if ( $diff > 10 ) continue;
					$score += ( 10 - $diff ) * 3;
				}
				if ( !empty($row["syncedLyrics"]) ) $score += 10;
				if ( $score > $bestScore ){ $bestScore = $score; $best = $row; }
			}
			if ( $best ) return $this->_pack( $best );
		}

		return false;

	}

	private function _artist_name( $item ){
		// try the expanded relation first
		if ( !empty( $item["bof_dir_artist"]["name"] ) )
			return $item["bof_dir_artist"]["name"];
		if ( !empty( $item["sub_title"] ) ){
			// sub_title can be "A & B" — take the whole string; lrclib fuzzy handles it
			return $item["sub_title"];
		}
		// resolve via artist_id → _c_m_artists
		foreach ( array( "artist_id", "album_artist_id" ) as $f ){
			if ( empty( $item[$f] ) ) continue;
			try {
				$r = bof()->db->query( "SELECT name FROM `_c_m_artists` WHERE id = " . (int)$item[$f] . " LIMIT 1" );
				$row = $r ? $r->fetch_assoc() : null;
				if ( $row && !empty( $row["name"] ) ) return $row["name"];
			} catch ( Exception $e ){}
		}
		return "";
	}

	private function _pack( $row ){
		// Prefer synced (LRC) — the endpoint detects [mm:ss] tags and serves
		// both stripped lyrics + raw lrc to clients.
		$lrc = trim( (string)( $row["syncedLyrics"] ?? "" ) );
		$plain = trim( (string)( $row["plainLyrics"] ?? "" ) );
		$data = $lrc !== "" ? $lrc : $plain;
		if ( $data === "" ) return false;
		return array(
			"type" => "string",
			"data" => $data
		);
	}

	private function _get( $path, $query ){
		$get = bof()->curl->exe( array(
			"url" => $this->api_base . $path . "?" . http_build_query( $query ),
			"json" => true,
			"type" => "json",
			"cache" => true,
			"cache_save" => true,
			"cache_load" => true
		) );
		if ( empty($get) || ($get["http_code"] ?? 0) != 200 || empty($get["data"]) ) return null;
		return $get["data"];
	}

}

?>
