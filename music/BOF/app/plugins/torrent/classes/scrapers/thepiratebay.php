<?php

class thepiratebay {

	public function scrap_search_result( $query, $sort=null, $page=null ){

		$query = urlencode( $query );
	  $getTorrents = bof()->curl->exe(array(
			"url" => "https://apibay.org/q.php?q={$query}",
			"cache" => true,
			"cache_load" => true,
		))["data"];

		$torrents = [];
		foreach( $getTorrents as $rawTorrent ){
			$torrents[] = [
				"source" => "thepiratebay",
				"name" => $rawTorrent["name"],
				"link" => $rawTorrent["info_hash"],
				"seed" => $rawTorrent["seeders"],
				"leech" => $rawTorrent["leechers"],
				"time" => $rawTorrent["added"],
				"size" => $rawTorrent["size"],
				"size_hr" => bof()->general->filesize_hr( $rawTorrent["size"] ),
				"uploader" => $rawTorrent["username"],
			];
		}
		return $torrents;

	}
	public function scrap_mag_from_link( $link ){

		return "magnet:?xt=urn:btih:" . $link;

	}

}


?>
