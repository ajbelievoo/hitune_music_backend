<?php

if ( !defined( "bof_root" ) ) die;

class play_store_scraper {

  public function scrape( $package_id ){

    if ( empty( $package_id ) )
    return false;

    $package_id = trim( $package_id );

    if ( !preg_match( '/^[a-zA-Z0-9_]+(\.[a-zA-Z0-9_]+)+$/', $package_id ) )
    return false;

    $url = "https://play.google.com/store/apps/details?id=" . urlencode( $package_id ) . "&hl=en&gl=US";

    $res = bof()->curl->exe( array(
      "url" => $url,
      "type" => "html",
      "return" => true,
      "follow" => true,
      "timeout" => 20,
      "ctimeout" => 10,
      "cache" => false,
      "cache_save" => false,
      "cache_load" => false,
      "agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36",
      "headers" => array(
        "Accept-Language: en-US,en;q=0.9",
        "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
      )
    ) );

    if ( empty( $res["http_code"] ) || $res["http_code"] != 200 )
    return false;

    $html = !empty( $res["body"] ) ? $res["body"] : "";
    if ( !$html )
    return false;

    $name = $this->parse_name( $html );
    $icon_url = $this->parse_icon_url( $html );
    $rating = $this->parse_rating( $html );
    $downloads = $this->parse_downloads( $html );

    return array(
      "package_id" => $package_id,
      "name" => $name,
      "icon_url" => $icon_url,
      "rating" => $rating,
      "downloads" => $downloads,
      "url" => $url,
    );

  }

  protected function parse_name( $html ){

    if ( preg_match( '/property="og:title" content="([^"]+)"/i', $html, $m ) ){
      $title = html_entity_decode( trim( $m[1] ) );
      $title = preg_replace( '/\s*-\s*Apps on Google Play\s*$/i', '', $title );
      if ( $title ) return $title;
    }

    if ( preg_match( '/<h1[^>]*>\\s*<span[^>]*>(.*?)<\\/span>\\s*<\\/h1>/is', $html, $m ) ){
      $title = strip_tags( $m[1] );
      $title = html_entity_decode( trim( $title ) );
      if ( $title ) return $title;
    }

    return "";

  }

  protected function parse_icon_url( $html ){

    if ( preg_match( '/property="og:image" content="([^"]+)"/i', $html, $m ) ){
      $icon = trim( $m[1] );
      if ( $icon ) return $icon;
    }

    return "";

  }

  protected function parse_rating( $html ){

    if ( preg_match( '/itemprop="ratingValue" content="([0-9.]+)"/i', $html, $m ) ){
      return $m[1];
    }

    if ( preg_match( '/aria-label="Rated\\s*([0-9.]+)\\s*stars\\s*out\\s*of\\s*five\\s*stars"/i', $html, $m ) ){
      return $m[1];
    }

    return "0.0";

  }

  protected function parse_downloads( $html ){

    if ( preg_match( '/<div[^>]*class="wVqUob"[^>]*>\\s*<div[^>]*class="ClM7O"[^>]*>([^<]+)<\\/div>\\s*<div[^>]*class="g1rdde"[^>]*>\\s*Downloads\\s*<\\/div>/is', $html, $m ) ){
      $val = html_entity_decode( trim( $m[1] ) );
      if ( $val ) return $val;
    }

    if ( preg_match( '/>Downloads<\\/div>\\s*<div[^>]*>\\s*<span[^>]*>([^<]+)<\\/span>/is', $html, $m ) ){
      $val = html_entity_decode( trim( $m[1] ) );
      if ( $val ) return $val;
    }

    if ( preg_match( '/>Installs<\\/div>\\s*<div[^>]*>\\s*<span[^>]*>([^<]+)<\\/span>/is', $html, $m ) ){
      $val = html_entity_decode( trim( $m[1] ) );
      if ( $val ) return $val;
    }

    if ( preg_match( '/"([0-9][0-9,\\.]*\\+?)"\\s*,\\s*"[^"]*"\\s*,\\s*"[^"]*"\\s*,\\s*"downloads"/i', $html, $m ) ){
      $val = trim( $m[1] );
      if ( $val ) return $val;
    }

    return "0";

  }

}
