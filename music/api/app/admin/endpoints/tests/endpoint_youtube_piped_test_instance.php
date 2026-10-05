<?php

if (!defined("bof_root")) die;

function endpoint_youtube_piped_test_instance($loader, $excuter, $args){

    if (bof()->user->check()->ID != 1) {
        $loader->api->set_error("Only root-admin can do this");
        return;
    }

    $url = bof()->nest->user_input( "post", "url", "url" );
    $id = bof()->nest->user_input( "post", "ID", "string" );

    if ( $url && $id ){

        bof()->plugin("youtube");
        try {
            $s = microtime(true);
            bof()->youtube_piped->set_instance_urls( $url )->get_video_stream( "0e3GPea1Tyg", $url );
            $e = microtime(true) - $s;
            
        } catch( bofException $err ){
            $errMsg = $err->getMessage();
        }

        $stats = bof()->object->db_setting->get( "youtube_piped_ss", [] );
        
        if ( empty( $e ) ){
            $loader->api->set_error( "Req Failed: " . $errMsg, array(
                "trid" => $id
            ) );
            $stats[ crc32( $url ) ] = [ 0, $errMsg ];
        } else {
            $loader->api->set_message("ok",array(
                "time" => round( $e, 3 ),
                "trid" => $id
            ));
            $stats[ crc32( $url ) ] = [ 1, round( $e, 3 ) . "s" ];
        }

        bof()->object->db_setting->set( "youtube_piped_ss", json_encode( $stats ), "json" );

        return;
        
    }

    $loader->api->set_message("ok");
}

?>