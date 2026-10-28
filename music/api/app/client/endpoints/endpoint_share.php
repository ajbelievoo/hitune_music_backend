<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_share( $loader, $excuter, $args ){

  $object_name = $loader->nest->user_input( "post", "object_type", "bofClient_object" );
  $object_has_source = $loader->nest->user_input( "post", "object_type", "bofClient_object", [ "has_button" => "source" ] );
  $object_hash = $loader->nest->user_input( "post", "object_hash", "md5" );

  if ( $object_name && $object_hash ){

    $the_object = $loader->object->__get( $object_name );

    $object_item_widget = $the_object->select(
      array(
        "hash" => $object_hash
      ),
      array(
      )
    );

    if ( $object_item_widget ){

      $object_item = $the_object->select(
        array(
          "ID" => $object_item_widget["ID"]
        ),
        array(
          "muse_source" => true,
          "_eq" => array(
            "sources" => array(),
          ),
          "cache_load_rt" => false
        )
      );
      $object_item2 = $the_object->select(
        array(
          "ID" => $object_item_widget["ID"]
        ),
        array(
          "indexed" => true,
          "match_page" => true,
        )
      );


      $sources = [];
      if ( !empty( $object_item["sources"] ) && $object_has_source ){

        $sources_gs = $object_item["sources"];

        foreach( $sources_gs as $source_G ){

          $sources_data = $source_G["data"];
          $sources_by_type = $loader->source->get( "stream", $source_G["ot"], $source_G["raw"], $source_G["sources"], "embed" );

          if ( !empty( $sources_by_type["all"]["sources"] ) ){
            foreach( $sources_by_type["all"]["sources"] as $_s_group ){
              if ( !empty( $_s_group["sources"] ) ){
                foreach( $_s_group["sources"] as $_s_group_source ){
                  if ( !$_s_group_source["locked"] )
                  $sources[] = $_s_group_source["hook"];
                }
              }
            }
          }

        }

      }

    }

    $_seo_item = bof()->seo->fetch( array(
      "object" => $object_name,
      "item" => $object_item2,
      "lang" => null,
    ), true );

    // IyolMe "Create Reel" deep link — spec §deep links:
    // iyolme://reel/create resolves/registers the sound keyed by
    // hitune_track_hash and opens the composer with the song attached.
    $_reel = null;
    if ( $object_name == "m_track" && !empty( $object_item ) ){

      $_artist_name = null;
      if ( !empty( $object_item["bof_dir_artist"]["name"] ) )
        $_artist_name = $object_item["bof_dir_artist"]["name"];
      elseif ( !empty( $object_item["artist_id"] ) ){
        try {
          $_art = bof()->object->m_artist->select( array( "ID" => $object_item["artist_id"] ) );
          if ( $_art && !empty( $_art["name"] ) ) $_artist_name = $_art["name"];
        } catch( Exception | bofException | Error $err ){}
      }

      $_turl = !empty( $_seo_item["url"] ) ? $_seo_item["url"] : ( web_address . "track/" . $object_item["hash"] );
      if ( strpos( (string)$_turl, "http" ) !== 0 )
        $_turl = web_address . ltrim( (string)$_turl, "/" );

      $_ai_pct = isset( $object_item["ai_pct"] ) ? (int)$object_item["ai_pct"] : ( isset( $object_item["raw"]["ai_pct"] ) ? (int)$object_item["raw"]["ai_pct"] : 0 );

      $_params = array(
        "hitune_track_hash" => $object_item["hash"],
        "title" => $object_item["title"],
        "artist" => $_artist_name,
        "cover" => !empty( $_seo_item["image"] ) ? $_seo_item["image"] : null,
        "duration_ms" => !empty( $object_item["duration"] ) ? intval( $object_item["duration"] ) * 1000 : null,
        "hitune_url" => $_turl,
        "ai_pct" => $_ai_pct ? $_ai_pct : null,
        "ai_badge" => $_ai_pct ? "AI" : null,
      );

      // audio_url is optional — attach only when a stream is already
      // cached so the share dialog never waits on a live resolve.
      try {
        $_ytid = !empty( $object_item["youtube_id"] ) ? $object_item["youtube_id"] : null;
        if ( $_ytid ){
          $_st = bof()->youtube_piped->set_setting()->get_stream_cached( $_ytid, array( "allow_live" => false ) );
          if ( $_st && !empty( $_st["url"] ) )
          $_params["audio_url"] = $_st["url"];
        }
      } catch( Exception | bofException | Error $err ){}

      $_q = array();
      foreach( $_params as $_k => $_v )
        if ( $_v !== null && $_v !== "" )
        $_q[] = $_k . "=" . rawurlencode( $_v );
      $_q = implode( "&", $_q );

      $_reel = array(
        "deeplink" => "iyolme://reel/create?" . $_q,
        "web_link" => "https://iyolme.com/reel/create?" . $_q,
      );
    }

    $loader->api->set_message( "ok", array(
      "item" => $_seo_item,
      "embedable" => ( bof()->object->db_setting->get( "muse_embedable", 1 ) && !empty( $sources ) ) ? array_unique( $sources ) : null,
      "reel" => $_reel
    ) );

  }

}

?>
