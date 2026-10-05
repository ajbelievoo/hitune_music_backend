<?php

if ( !defined( "bof_root" ) ) die;

class youtube_helper extends bof_type_class {

	public function get_track_video( $track, $cron_args, $caller=null ){

		$download_vidz = false;
		extract( $cron_args );

		if ( !$caller )
		$caller = bof()->music;

		$artist = bof()->object->m_artist->sid( $track["artist_id"] );
		$caller->_cli( "Youtube_helper: Getting `ID` query:`{$track["title"]}`" );

		// ID
		$youtube_source = bof()->object->m_track_source->select(
			array(
				"target_id" => $track["ID"],
				"type" => "youtube"
			),
			array(
				"limit" => 1,
				"single" => true,
			)
		);

		if ( !$youtube_source ){

			$request_video = bof()->youtube->find_video( array(
				"title" => $track["title"],
				"sub_title" => $artist["name"],
				"duration" => $track["duration"]
			) );

		  if ( $request_video[0] ){

		    $youtube_source_id = bof()->object->m_track_source->insert(array(
		      "target_id" => $track["ID"],
		      "type" => "youtube",
		      "data" => json_encode( [ "youtube_id" => $request_video[1] ] ),
		      "stream_able" => 1,
		      "download_able" => -2,
		      "encrypted" => 0
		    ));

				$youtube_id = $request_video[1];

				$caller->_cli( "Youtube_helper: Got `ID` query:`{$track["title"]}` ID:{$youtube_id}" );

		  }

		}
		else {

			$youtube_source_id = $youtube_source["ID"];
			$youtube_source_args = json_decode( $youtube_source["data"], 1 );
			$youtube_id = $youtube_source_args["youtube_id"];

			$caller->_cli( "Youtube_helper: Already gotten `ID` query:`{$track["title"]}` ID:{$youtube_id}" );

		}

		if ( empty( $youtube_id ) )
		return "noYoutubeID";

		if ( !$download_vidz )
		return $youtube_source_id;

		// Video
		$audio_source = bof()->object->m_track_source->select(
			array(
				"target_id" => $track["ID"],
				"type" => "audio"
			),
			array(
				"limit" => 1,
				"single" => true,
			)
		);

		if ( $audio_source ){
			$caller->_cli( "Youtube_helper: Already gotten `Audio` query:`{$track["title"]}` ID:{$youtube_id}" );
			return $youtube_source_id;
		}

		try {
			$download_and_convert = bof()->youtube->download( $youtube_id );
		} catch( Exception $err ){
			$caller->_cli( "Youtube_helper: Failed to download `Video` query:`{$track["title"]}` ID:{$youtube_id} " . $err->getMessage() );
			return;
		}

		$rules = bof()->object->file->get_rules( "audio", "m_track_source", [ "get_host" => true ] );

		$convert_file_id = bof()->object->file->insert(
			array(
				"type" => "audio",
				"host_id" => "1",
				"dest_host_id" => $rules["file_host"],
				"path" => bof()->object->file->clean_path( $download_and_convert, true ),
				"object_type" => "m_track_source",
			)
		);

		$convert_source = bof()->object->m_track_source->create(
			[],
			array(
				"target_id" => $track["ID"],
				"type" => "audio",
				"data" => array(
					"file_type" => "local",
					"local_file" => $convert_file_id,
				),
			),
			[]
		);

		$caller->_cli( "Youtube_helper: Dwonloaded `video` query:`{$track["title"]}` ID:{$youtube_id}" );
		return $youtube_source_id;

	}

}

?>
