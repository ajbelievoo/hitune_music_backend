<?php

class limetorrents {

	public function scrap_search_result( $query, $sort=null, $page=1 ){

		$page = $page ? $page : 1;
		$query = urlencode( $query );
		$dataString = bof()->curl->exe(array(
			"url" => "https://www.limetorrents.info/search/all/{$query}/seeds/{$page}/",
			"type" => "html"
		))["body"];

		$torrents = [];
		preg_match_all( "/<tr bgcolor=\"(.*?)\">(.*?)<\/tr>/s", $dataString, $matches );
		if ( !empty( $matches[2] ) ){
			foreach( $matches[2] as $tr ){
				preg_match_all("/<td class=\"(.*?)\">(.*?)<\/td>/s",$tr,$tds);
				if ( !empty( $tds[2] ) ? count($tds[2]) == 6 : false ){

					$tdd = $tds[2];

					preg_match_all( "/href=\"(.*?)\">/", $tdd[0], $m );

					$torrents[] = [
						"source" => "limetorrents",
						"name" => strip_tags( $tdd[0], "" ),
						"link" => $m[1][1],
						"seed" => intval( str_replace( ",", "", $tdd[3] ) ),
						"leech" => intval( str_replace( ",", "", $tdd[4] ) ),
						"time" => str_replace( "+", "", @reset( explode( "-", $tdd[1] ) ) ),
						"size" => bof()->general->filesize_cr( $tdd[2] ),
						"size_hr" => $tdd[2],
						"uploader" => "UN",
					];

				}
			}
		}

		return $torrents;

	}
	public function scrap_list_result( $listPath ){

		$link = "https://www.limetorrents.info/{$listPath}";
		$dataString = $this->dad->loader->_req( $link );

		$torrents = [];
		preg_match_all( "/<tr bgcolor=\"(.*?)\">(.*?)<\/tr>/s", $dataString, $matches );
		if ( !empty( $matches[2] ) ){
			foreach( $matches[2] as $tr ){
				preg_match_all("/<td class=\"(.*?)\">(.*?)<\/td>/s",$tr,$tds);
				if ( !empty( $tds[2] ) ? count($tds[2]) == 6 : false ){

					$tdd = $tds[2];

					preg_match_all( "/href=\"(.*?)\">/", $tdd[0], $m );

					$torrents[] = [
						"name" => strip_tags( $tdd[0], "" ),
						"link" => $m[1][1],
						"seed" => intval( str_replace( ",", "", $tdd[3] ) ),
						"leech" => intval( str_replace( ",", "", $tdd[4] ) ),
						"time" => str_replace( "+", "", @reset( explode( "-", $tdd[1] ) ) ),
						"size" => $tdd[2],
						"uploader" => "UN",
					];

				}
			}
		}

		return $torrents;

	}
	public function scrap_mag_from_link( $link ){

		$data = bof()->curl->exe(array(
			"url" => "https://www.limetorrents.info" . $link
		))["body"];

		preg_match( "/magnet:\?xt=urn:btih:(.*?)&/", $data, $m );
		return strtolower( substr( $m[0], 0, 60 ) );

	}

}

?>
