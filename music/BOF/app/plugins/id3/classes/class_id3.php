<?php

if ( !defined( "bof_root" ) ) die;

class id3 {

  public function core( $type ){

    if ( $type == "reader" ){

      require_once( realpath( id3_plugin_root . "/JamesHeinrich_getID3/vendor/james-heinrich/getid3/getid3/getid3.php" ) );
      $id3 = new getID3;
      $id3->setOption(array('encoding'=>"UTF-8"));
      return $id3;

    }
    elseif ( $type == "writer" ){

      require_once( realpath( id3_plugin_root . "/JamesHeinrich_getID3/vendor/james-heinrich/getid3/getid3/getid3.php" ) );
      require_once( realpath( id3_plugin_root . "/JamesHeinrich_getID3/vendor/james-heinrich/getid3/getid3/write.php" ) );
      $id3 = new getID3;
      $id3->setOption(array('encoding'=>"UTF-8"));
      $id3_writer = new getid3_writetags;
      $id3_writer->tagformats = array('id3v2.3');
      $id3_writer->overwrite_tags = true;
      $id3_writer->tag_encoding = "UTF-8";
      $id3_writer->remove_other_tags = true;
      return $id3_writer;

    }

    return false;

  }
  public function read_tags( $filePath, $type="audio" ){

    $getID3 = $this->core("reader");
    $data = $getID3->analyze( $filePath );

    if ( !empty( $data["error"] ) )
    return false;

    if ( empty( $data["audio"] ) && $type == "audio" )
    return false;

    if ( empty( $data["video"] ) && $type == "video" )
    return false;

    if ( empty( $data["playtime_seconds"] ) )
    return false;

    $simplified = array(
      "duration" => ceil( $data["playtime_seconds"] ),
    );

    if ( $type == "audio" ){

      $simplified["format"] = $data["audio"]["dataformat"];
      $simplified["bitrate"] = round( $data["audio"]["bitrate"] / 1000 );

      $getID3->CopyTagsToComments( $data );
      if ( !empty( $data["comments"] ) ){

        $raw_tags = $data["comments"];

        foreach( $raw_tags as $_tk => $_tv ){
          $raw_tags[ $_tk ] = reset( $_tv );
        }

        if ( !empty( $raw_tags["track_number"] ) ){
          $track_number_raw = explode( " ", str_replace( [ "/","-","_","of","\\",".",","], " ", $raw_tags["track_number"] ) );
          $track_number_raw = reset( $track_number_raw );
          $raw_tags[ "track_number" ] = $track_number_raw;
        }

        if ( !empty( $raw_tags["year"] ) )
        $raw_tags["year_to_time"] = date( "Y-m-d", strtotime( $raw_tags["year"] . "-01-01" ) );

        $tags = [];

        foreach( array(
          "title" => [ "string", [ "strip_emoji" => false ] ],
          "artist" => [ "string", [ "strip_emoji" => false ], "artist_name" ],
          "album" => [ "string", [ "strip_emoji" => false ], "album_title" ],
          "band" => [ "string", [ "strip_emoji" => false ], "album_artist_name" ],
          "track_number" => [ "int", [ "min" => 1 ], "album_order" ],
          "year_to_time" => [ "datetime", [], "album_time" ],
        ) as $raw_tag_name => $tag_args ){
          $tags[ empty( $tag_args[2] ) ? $raw_tag_name : $tag_args[2] ] = null;
          if ( !empty( $raw_tags[ $raw_tag_name ] ) ? bof()->nest->validate( $raw_tags[ $raw_tag_name ], $tag_args[0], $tag_args[1] ) : false )
          $tags[ empty( $tag_args[2] ) ? $raw_tag_name : $tag_args[2] ] = $raw_tags[ $raw_tag_name ];
        }

        if ( !empty( $raw_tags["picture"]["data"] ) ){
          $tags["cover_string"] = $raw_tags["picture"]["data"];
        }

        if ( empty( $tags[ "album_artist_name" ] ) && !empty( $tags[ "artist_name" ] ) )
        $tags[ "album_artist_name" ] = $tags[ "artist_name" ];

        if ( !empty( $raw_tags["genre"] ) ){
          $raw_genres = explode( "_BOF_SEPERATOR_", str_replace( [ ",", "|", "/", ";", "\\" ], "_BOF_SEPERATOR_", $raw_tags["genre"] ) );
          foreach( $raw_genres as $raw_genre ){
            $raw_genre = trim( $raw_genre );
            if ( bof()->nest->validate( $raw_genre, "string", [] ) )
            $tags["genres"][] = $raw_genre;
          }
        }

        $simplified["tags"] = $tags;

      }

      if ( empty( $simplified["tags"] ) )
      $simplified["tags"] = [];

      if ( empty( $simplified["tags"]["bitrate"] ) )
      $simplified["tags"]["bitrate"] = $simplified["bitrate"];

    }
    else {

      $simplified["format"] = $data["video"]["dataformat"];
      $simplified["frame_rate"] = $data["video"]["frame_rate"];
      $simplified["width"] = $data["video"]["resolution_x"];
      $simplified["height"] = $data["video"]["resolution_y"];
      $simplified["v_quality"] = "240p";

      if ( $simplified["width"] >= 3840 ) $simplified["v_quality"] = "4k";
      elseif ( $simplified["width"] >= 1920 ) $simplified["v_quality"] = "1080p";
      elseif ( $simplified["width"] >= 1280 ) $simplified["v_quality"] = "720p";
      elseif ( $simplified["width"] >= 640 ) $simplified["v_quality"] = "480p";

    }

    return $simplified;

  }
  public function write_tags( $filePath, $tags ){

    foreach( $tags as $i => $v ){
      if ( is_array( $v ) ){
        foreach( $v as $i2 => $v2 ){
          if ( is_string( $v2 ) ){
            $tags[ $i ][ $i2 ] = htmlspecialchars_decode( $v2, ENT_QUOTES );
          }
        }
      }
    }

    $writer = $this->core("writer");
    $writer->tag_data = $tags;
    $writer->filename = $filePath;
    $writer->WriteTags();

    if ( !empty( $writer->warnings ) ) return false;
    return true;

  }

}

?>
