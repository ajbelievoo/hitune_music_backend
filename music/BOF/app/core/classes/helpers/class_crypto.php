<?php

if ( !defined( "bof_root" ) ) die;

class crypto extends bof_type_class {

  public function lock( $data, $args=[] ){

    require_once( bof_root . "/app/core/third/sodium_compat/vendor/autoload.php" );

    $key = random_bytes(32);
    $nonce = random_bytes(24);
    extract( $args );

    $sign = ParagonIE_Sodium_Compat::crypto_secretbox( $data, $nonce, $key );

    return array(
      "key" => bin2hex($key),
      "nonce" => bin2hex($nonce),
      "sign" => bin2hex($sign)
    );

  }

  public function unlock( $sign, $nonce, $key ){

    require_once( bof_root . "/app/core/third/sodium_compat/vendor/autoload.php" );

    if ( !ctype_xdigit($sign) || strlen($sign) % 2 != 0 )
    throw new Exception( "invalid_sign" );

    if ( !ctype_xdigit($nonce) || strlen($nonce) % 2 != 0 )
    throw new Exception( "invalid_nonce" );

    if ( !ctype_xdigit($key) || strlen($key) % 2 != 0 )
    throw new Exception( "invalid_key" );

    $sign = hex2bin( $sign );
    $nonce = hex2bin( $nonce );
    $key = hex2bin( $key );

    $data = ParagonIE_Sodium_Compat::crypto_secretbox_open( $sign, $nonce, $key );

    return $data;

  }


}

?>
