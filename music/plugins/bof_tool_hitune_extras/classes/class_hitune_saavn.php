<?php

if ( !defined( "bof_root" ) ) die;

// JioSaavn fallback resolver.
// Resolves a playable aac.saavncdn.com MP4 URL from title/artist/duration.
// Used when YouTube resolution is unavailable (IP bot-block etc.) — saavn
// CDN URLs are plain static MP4s, no poToken/expiry.
class hitune_saavn extends bof_type_class {

  public function resolve( $title, $artist=null, $duration=null ){

    $title = trim( (string) $title );
    if ( !$title )
    return null;

    $query = trim( $title . " " . trim( (string) $artist ) );

    $exe = bof()->curl->exe( array(
      "url" => "https://www.jiosaavn.com/api.php?__call=search.getResults&_format=json&_marker=0&cc=in&includeMetaTags=1&n=10&q=" . urlencode( $query ),
      "cache" => false,
      "timeout" => 8,
      "ctimeout" => 4,
      "agent" => "chrome"
    ) );

    if ( empty( $exe["data"]["results"] ) || !is_array( $exe["data"]["results"] ) )
    return null;

    $best = null;
    $best_score = 0;

    foreach( $exe["data"]["results"] as $r ){

      if ( empty( $r["song"] ) || empty( $r["encrypted_media_url"] ) )
      continue;

      $score = 0;
      similar_text( mb_strtolower( $r["song"], "UTF-8" ), mb_strtolower( $title, "UTF-8" ), $_p );
      $score += $_p;

      if ( $artist && !empty( $r["primary_artists"] ) ){
        similar_text( mb_strtolower( $r["primary_artists"], "UTF-8" ), mb_strtolower( $artist, "UTF-8" ), $_a );
        $score += $_a * 0.5;
      }

      // Hard duration gate when the track duration is known (youtube and
      // saavn cuts can differ a little; allow +/- 15s, reward a match).
      if ( $duration && !empty( $r["duration"] ) && is_numeric( $r["duration"] ) ){
        if ( abs( intval( $r["duration"] ) - intval( $duration ) ) > 15 )
        continue;
        $score += 20;
      }

      if ( $score > $best_score ){
        $best_score = $score;
        $best = $r;
      }

    }

    if ( !$best || $best_score < 55 )
    return null;

    $url = $this->decrypt_media_url( $best["encrypted_media_url"] );
    if ( !$url )
    return null;

    // The encrypted URL points at _96.mp4; upgrade to the highest bitrate
    // the song advertises, verifying the file actually exists.
    $bitrate = 96;
    $_hi = str_replace( "_96.mp4", "_320.mp4", $url );
    $_mid = str_replace( "_96.mp4", "_160.mp4", $url );
    if ( !empty( $best["320kbps"] ) && $best["320kbps"] !== "false" && $_hi !== $url && $this->url_exists( $_hi ) ){
      $url = $_hi;
      $bitrate = 320;
    }
    elseif ( $_mid !== $url && $this->url_exists( $_mid ) ){
      $url = $_mid;
      $bitrate = 160;
    }

    return array(
      "url" => bof()->general->https_url( $url ),
      "mime" => "audio/mp4",
      "type" => "audio",
      "duration" => !empty( $best["duration"] ) && is_numeric( $best["duration"] ) ? intval( $best["duration"] ) : ( $duration ? intval( $duration ) : null ),
      "bitrate" => $bitrate,
      "saavn_id" => !empty( $best["id"] ) ? $best["id"] : null
    );

  }
  public function decrypt_media_url( $encrypted ){

    $dec = openssl_decrypt( base64_decode( (string) $encrypted ), "des-ecb", "38346591", OPENSSL_RAW_DATA );
    if ( !$dec || strpos( $dec, "http" ) !== 0 )
    return null;

    return trim( $dec );

  }
  protected function url_exists( $url ){

    $exe = bof()->curl->exe( array(
      "url" => $url,
      "cache" => false,
      "timeout" => 6,
      "ctimeout" => 3,
      "nobody" => true,
      "type" => "raw",
      "agent" => "chrome"
    ) );

    return !empty( $exe["http_code"] ) && $exe["http_code"] >= 200 && $exe["http_code"] < 400;

  }

}

?>
