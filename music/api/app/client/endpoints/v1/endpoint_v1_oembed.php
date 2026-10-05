<?php

if ( !defined( "bof_root" ) ) die;

/**
 * GET /api/v1/oembed?url=<hitune url>
 * oEmbed 1.0 "rich" response for auto-embeds (WordPress etc.).
 * Auth: publishable key or Bearer token.
 */

function endpoint_v1_oembed( $loader, $excuter, $args ){

  dev_v1_preflight();

  $auth = $loader->developer_api->v1_auth();
  if ( !$auth ) return;

  $url = $loader->nest->user_input( "get", "url", "string" );
  if ( !$url )
    return $loader->developer_api->v1_error( "invalid_request", 400, "Missing url parameter" );

  // accepted forms: {web_address}[music/]{track|album|artist|playlist}/{hash}
  if ( !preg_match( "/(?:^|\/)(?:music\/)?(track|album|artist|playlist)\/([a-zA-Z0-9\-_]{32})/", $url, $m ) )
    return $loader->developer_api->v1_error( "invalid_request", 400, "URL is not a HiTune track/album/artist/playlist link" );

  $type = $m[1];
  $hash = $m[2];

  $objects = array(
    "track" => array( "m_track", "track_payload" ),
    "album" => array( "m_album", "album_payload" ),
    "artist" => array( "m_artist", "artist_payload" ),
    "playlist" => array( "ugc_playlist", "playlist_payload" )
  );
  list( $object_name, $formatter ) = $objects[ $type ];

  $item = $loader->object->__get( $object_name )->select(
    array( "hash" => $hash ),
    array( "_eq" => array( "cover" => array(), "album" => array( "cover" => array() ), "artist" => array() ) )
  );
  if ( !$item )
    return $loader->developer_api->v1_error( "not_found", 404, ucfirst( $type ) . " not found" );

  if ( $type === "playlist" && !empty( $item["private"] ) )
    return $loader->developer_api->v1_error( "not_found", 404, "Playlist not found" );

  $payload = $loader->developer_api->$formatter( $item );
  $title = !empty( $payload["title"] ) ? $payload["title"] : ( !empty( $payload["name"] ) ? $payload["name"] : "HiTune" );
  $author = null;
  if ( !empty( $payload["artists"][0]["name"] ) ) $author = $payload["artists"][0]["name"];
  elseif ( !empty( $payload["artist"]["name"] ) ) $author = $payload["artist"]["name"];
  elseif ( !empty( $payload["name"] ) ) $author = $payload["name"];

  $embed_url = web_address . "api/v1/embed/{$type}/{$hash}";
  $key = $loader->nest->user_input( "get", "key", "string" ) ?: $loader->nest->user_input( "http_header", "x-api-key", "string" );
  if ( $key ) $embed_url .= "?key=" . urlencode( $key );

  $loader->developer_api->v1_send( array(
    "type" => "rich",
    "version" => "1.0",
    "title" => $title,
    "author_name" => $author,
    "provider_name" => "HiTune",
    "provider_url" => web_address,
    "thumbnail_url" => !empty( $payload["cover"] ) ? $payload["cover"] : null,
    "html" => '<iframe src="' . htmlspecialchars( $embed_url, ENT_QUOTES ) . '" width="100%" height="152" frameborder="0" allow="encrypted-media"></iframe>',
    "width" => 480,
    "height" => 152
  ) );

}

?>
