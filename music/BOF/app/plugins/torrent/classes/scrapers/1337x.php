<?php

class x1337 {

	public function scrap_search_result( $query, $sort=null, $page=null ){

		$page = $page ? $page : 1;
		$query = urlencode( $query );
		$sortName = $sort == "recent" ? "time" : "seeders";

		return $this->scrap_list_result( "sort-search/{$query}/{$sortName}/desc/{$page}/" );

	}
	public function scrap_list_result( $listPath ){

		$link = "https://1337x.to/{$listPath}";
		$dataString = bof()->curl->exe(array(
			"url" => $link,
			"type" => "html"
		))["body"];

		return $this->__html_string_to_data( $dataString );

	}
	public function scrap_mag_from_link( $link ){

		$data = bof()->curl->exe(array(
			"url" => "https://1337x.to" . $link
		))["body"];

		preg_match( "/magnet:\?xt=urn:btih:(.*?)&/", $data, $m );
		return strtolower( substr( $m[0], 0, 60 ) );

	}

	private function __html_string_to_data( $dataString ){

		$torrents = [];
		preg_match_all( "/<tr>(.*?)<\/tr>/s", $dataString, $matches );
		if ( !empty( $matches[1] ) ){
			foreach( $matches[1] as $tr ){
				preg_match_all("/<td class=\"(.*?)\">(.*?)<\/td>/s",$tr,$tds);
				if ( !empty( $tds[2] ) ){

					$tdd = $tds[2];

					preg_match_all( "/href=\"\/torrent(.*?)\">/", $tdd[0], $m );

					$torrents[] = [
						"source" => "1337x",
						"name" => strip_tags( $tdd[0], "" ),
						"link" => "/torrent" . $m[1][0],
						"seed" => intval( $tdd[1] ),
						"leech" => intval( $tdd[2] ),
						"time" => $tdd[3],
						"size" => bof()->general->filesize_cr( @reset( explode( "<", $tdd[4] ) ) ),
						"size_hr" => @reset( explode( "<", $tdd[4] ) ),
						"uploader" => @reset( explode( "<", explode( ">", $tdd[5] )[1] ) ),
					];

				}
			}
		}

		return $torrents;

	}

}

?>
