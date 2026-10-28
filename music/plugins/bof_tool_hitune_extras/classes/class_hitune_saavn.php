<?php

if ( !defined( "bof_root" ) ) die;

// JioSaavn fallback resolver.
// Resolves a playable aac.saavncdn.com MP4 URL from title/artist/duration.
// Used when YouTube resolution is unavailable (IP bot-block etc.) — saavn
// CDN URLs are plain static MP4s, no poToken/expiry.
class hitune_saavn extends bof_type_class {

  // $quality: "audio_lq"|"lq"|"low" keeps the ~96/160kbps file for data-saver
  // users; anything else upgrades to the highest verified bitrate (320 max).
  public function resolve( $title, $artist=null, $duration=null, $quality=null ){

    $title = trim( (string) $title );
    if ( !$title )
    return null;

    // Imported catalog titles often carry decoration a real JioSaavn song
    // name never has — "(From "X")", "- Live", "- Karaoke", or "A / B / C"
    // medley blobs. Try the raw title first, then progressively cleaned
    // variants, otherwise these tracks always drop to the 30s iTunes
    // preview and users hear the song cut off mid-way ("aadha-adhura").
    $titles = array( $title );

    $_c = trim( preg_replace( "/[\(\[][^\)\]]*[\)\]]/u", " ", $title ) );
    $_c = trim( preg_replace( "/\s*[-–—_:]\s*(live|karaoke|remix|unplugged|slowed|reverb|sped ?up|nightcore|cover|version|lo-?fi|8d|bass boosted|reprise|revamped|acoustic|instrumental|vocals?[- ]?only)\s*$/iu", "", $_c ) );
    $_c = trim( preg_replace( "/\s{2,}/", " ", $_c ) );
    if ( $_c && strcasecmp( $_c, $title ) !== 0 )
    $titles[] = $_c;

    if ( strpos( $_c, "/" ) !== false ){
      foreach( preg_split( "#\s*/\s*#", $_c ) as $_seg ){
        $_seg = trim( $_seg );
        if ( mb_strlen( $_seg ) > 3 )
        $titles[] = $_seg;
      }
    }

    $best = null;
    $_first = true;
    foreach( array_unique( $titles ) as $_title ){
      // Cleaned variants legitimately differ in length from the catalog
      // entry (a lofi/live cut vs the studio song), so loosen the duration
      // gate on retries — title+artist scoring still gates correctness.
      $best = $this->_find_best( $_title, $artist, $duration, $_first ? 8 : 20 );
      if ( $best )
      break;
      $_first = false;
    }
    if ( !$best )
    return null;

    $url = $this->decrypt_media_url( $best["encrypted_media_url"] );
    if ( !$url )
    return null;

    // The encrypted URL points at _96.mp4; upgrade to the highest bitrate
    // the song advertises, verifying the file actually exists — unless the
    // caller asked for a data-saver stream, then keep _96 (or _160 if _96 404s).
    $bitrate = 96;
    $_hi = str_replace( "_96.mp4", "_320.mp4", $url );
    $_mid = str_replace( "_96.mp4", "_160.mp4", $url );
    $_lq = is_string( $quality ) && in_array( strtolower( trim( $quality ) ), [ "audio_lq", "lq", "low" ], true );
    if ( $_lq ){
      if ( !$this->url_exists( $url ) && $_mid !== $url && $this->url_exists( $_mid ) ){
        $url = $_mid;
        $bitrate = 160;
      }
    }
    elseif ( !empty( $best["320kbps"] ) && $best["320kbps"] !== "false" && $_hi !== $url && $this->url_exists( $_hi ) ){
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
  // Single search+score pass for one title candidate. Returns the winning
  // JioSaavn result row or null when nothing clears the score gate.
  protected function _find_best( $title, $artist, $duration, $dur_gate=8 ){

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

    // Unicode dash variants ("Salim–Sulaiman" vs "Salim-Sulaiman") tank
    // otherwise-perfect artist matches — normalize before comparing.
    $_norm = function( $s ){ return mb_strtolower( preg_replace( "/[\x{2010}-\x{2015}\x{2212}\x{FE58}\x{FE63}\x{FF0D}]/u", "-", (string) $s ), "UTF-8" ); };
    $_want_title = $_norm( $title );
    $_want_artist = $_norm( $artist );

    $best = null;
    $best_score = 0;

    foreach( $exe["data"]["results"] as $r ){

      if ( empty( $r["song"] ) || empty( $r["encrypted_media_url"] ) )
      continue;

      $score = 0;
      similar_text( $_norm( $r["song"] ), $_want_title, $_p );

      // Title floor — without this, a loosely-similar title could ride
      // artist+duration bonuses past the score gate and play the wrong
      // song entirely.
      if ( $_p < ( $dur_gate <= 8 ? 45 : 35 ) )
      continue;

      $score += $_p;

      if ( $artist && !empty( $r["primary_artists"] ) ){
        similar_text( $_norm( $r["primary_artists"] ), $_want_artist, $_a );
        $score += $_a * 0.5;
      }

      // Variant penalty: a remix/cover/lofi cut is not the requested song.
      // Penalize rather than skip — some legit titles contain these words.
      $_variant = "/(remix|reprise|revamped|cover|karaoke|lo-?fi|slowed|reverb|8d|bass boosted|unplugged|acoustic|instrumental|live|sped ?up|nightcore|mashup|version)/i";
      if ( !preg_match( $_variant, $title ) && preg_match( $_variant, (string) $r["song"] ) )
      $score -= 60;

      // Hard duration gate when the track duration is known (youtube and
      // saavn cuts can differ a little; allow +/- gate seconds, reward a
      // match). Gate widens on cleaned-title retries (variant durations
      // naturally differ from the studio cut).
      if ( $duration && !empty( $r["duration"] ) && is_numeric( $r["duration"] ) ){
        if ( abs( intval( $r["duration"] ) - intval( $duration ) ) > $dur_gate )
        continue;
        $score += 20;
      }

      if ( $score > $best_score ){
        $best_score = $score;
        $best = $r;
      }

    }

    // When the catalog track's duration is unknown the duration gate
    // can't vouch for the match — demand a stronger title+artist score
    // before trusting the resolve.
    $_min_score = $duration ? 70 : 85;
    if ( !$best || $best_score < $_min_score )
    return null;

    return $best;

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
