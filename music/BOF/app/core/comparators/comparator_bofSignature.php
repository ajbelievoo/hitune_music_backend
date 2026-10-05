<?php

function http_build_query_custom( $array, $pKey=false ) {

  $items = [];
  foreach( $array as $k => $v ){
    if ( is_array( $k ) || is_array( $v ) ){
      $d = http_build_query_custom( $v, $k );
      $items[] = $d;
    } else {

      $_k = $pKey ? "{$pKey}%5B{$k}%5D" : $k;
      $items[] = trim(
        $_k .
        "=" .
        str_replace( [ "%21", "%27", "%28", "%29", "%2A" ], [ "!", "'", "(", ")", "*" ], urlencode( $v ) )
      );

    }
  }

  return implode( "&", $items );

}

function comparator_bofSignature( $args, $endpoint, $nest ){

  if ( !empty( $_POST ) && empty( $endpoint["skip_key_check"] ) ){

    $signature = $nest->user_input( "post", "bof_signature", "raw" );

    if ( $signature ){

      unset( $_POST["bof_signature"] );

      if ( !empty( $_POST ) ){

        $post_string1 = http_build_query( $_POST );
        $post_hashed1 = md5( hash_hmac( 'sha256', $post_string1, sign_key ) );

        if ( $post_hashed1 == $signature )
        return true;

        $post_string2 = urldecode( http_build_query( $_POST ) );
        $post_hashed2 = md5( hash_hmac( 'sha256', $post_string2, sign_key ) );

        if ( $post_hashed2 == $signature )
        return true;

        $post_string3 = http_build_query_custom( $_POST );
        $post_hashed3 = md5( hash_hmac( 'sha256', $post_string3, sign_key ) );

        if ( $post_hashed3 == $signature )
        return true;

      }

    }

  }
  else
  return true;

  return false;

}

?>
