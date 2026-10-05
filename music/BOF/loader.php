<?php

if ( !defined( "bof_root" ) ) die();

define( "time_ini", microtime(true) );

if ( !production ){
  ini_set( "display_errors", 1 );
  ini_set( "display_startup_errors", 1 );
  error_reporting( E_ALL );
}
else {
  ini_set( "display_errors", 0 );
  ini_set( "display_startup_errors", 0 );
  error_reporting( 0 );
}

class bofException extends \Exception {

  private $_extra;

  public function __construct( $message, $code = 0, Exception $previous = null, $extra=[] ){

    parent::__construct( $message, $code, $previous );
    $this->_extra = $extra;

  }

  public function getExtra(){
    return $this->_extra;
  }
}

require_once( bof_root . "/app/core/classes/class_loader.php" );
require_once( bof_root . "/app/core/classes/class_proxy.php" );

function fall( $reason=null, $extraData=null ){
  bof()->general->fall( $reason, $extraData );
}
function error_inspector( $err_severity, $err_msg, $err_file, $err_line ){

  if (
    preg_match( "/whichbrowser/", $err_file ) ||
    preg_match( "/minishlink_web-push/", $err_file ) ||
    preg_match( "/paypal-1.14/", $err_file ) ||
    preg_match( "/PayPal/", $err_file ) ||
    preg_match( "/editorjs-php/", $err_file ) ||
    preg_match( "/nicolab-php-ftp-client/", $err_file ) ||
    preg_match( "/fakerphp_faker/", $err_file ) ||
    preg_match( "/JamesHeinrich_getID3/", $err_file ) ||
    preg_match( "/fedapay-php/", $err_file ) ||
    preg_match( "/libpng warning: iCCP: known incorrect sRGB profile/", $err_msg )
  ){
    return;
  }

  $err_severity_name = $err_severity;
  switch( $err_severity ){
    case E_ERROR:              $err_severity_name = "Error";
    case E_WARNING:            $err_severity_name = "Warning";
    case E_PARSE:              $err_severity_name = "Parse";
    case E_NOTICE:             $err_severity_name = "Notice";
    case E_CORE_ERROR:         $err_severity_name = "CoreError";
    case E_CORE_WARNING:       $err_severity_name = "CoreWarning";
    case E_COMPILE_ERROR:      $err_severity_name = "CompileError";
    case E_COMPILE_WARNING:    $err_severity_name = "CoreWarning";
    case E_USER_ERROR:         $err_severity_name = "UserError";
    case E_USER_WARNING:       $err_severity_name = "UserWarning";
    case E_USER_NOTICE:        $err_severity_name = "UserNotice";
    case E_STRICT:             $err_severity_name = "Strict";
    case E_RECOVERABLE_ERROR:  $err_severity_name = "RecoverableErro";
    case E_DEPRECATED:         $err_severity_name = "Deprecated";
    case E_USER_DEPRECATED:    $err_severity_name = "UserDeprecated";
  }

  $hide_echo = false;
  try {
    $hide_echo = bof()->defined("response") ? bof()->response->getType("json") : false;
  } catch( Exception|bofException|Warning|Error $err ){}
  if ( !$hide_echo && defined("root") && !empty( $_SERVER["REQUEST_URI"] ) ){
    if ( strpos( $_SERVER["REQUEST_URI"], "/api/" ) !== false ) $hide_echo = true;
  }
  if ( !$hide_echo && !( error_reporting() == 0 || ini_get('error_reporting') == 0 ) )
  echo "<b>{$err_severity_name}</b>: {$err_msg} in {$err_file}:{$err_line}\n<br>";

  if ( !defined("api_sent_dia") && bof()->defined("db") ){
    try {
      bof()->object->error_log->insert(array(
        "file" => str_replace( base_root, "", $err_file ),
        "line" => $err_line,
        "severity" => $err_severity,
        "severity_name" => $err_severity_name,
        "message" => json_encode( $err_msg ),
        "bof_version" => bof_version
      ));
    } catch( Exception|bofException|Warning|Error $err ){}
  }

  // Send the error log to busyowl API if user allowed such a thing
  if ( function_exists('curl_version') && !defined("api_sent_dia") ){

    $c = curl_init();
		curl_setopt( $c, CURLOPT_URL, "https://api.busyowl.co/report_error" );
		curl_setopt( $c, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $c, CURLOPT_FOLLOWLOCATION, true );
    curl_setopt( $c, CURLOPT_TIMEOUT, 1 );
    curl_setopt( $c, CURLOPT_CONNECTTIMEOUT, 1 );
    curl_setopt( $c, CURLOPT_USERAGENT, "bof_error_reporter" );
    curl_setopt( $c, CURLOPT_POST, true );
    curl_setopt( $c, CURLOPT_POSTFIELDS, array(

      "web_address" => defined( "web_address" ) ? web_address : null,
      "version" => defined( "version" ) ? version : null,
      "bof_version" => defined( "bof_version" ) ? bof_version : null,

      "err_severity" => $err_severity,
      "err_severity_name" => $err_severity_name,
      "err_file" => str_replace( base_root, "", $err_file ),
      "err_line" => $err_line,
      "err_message" => json_encode( $err_msg ),
      "err_trace" => json_encode( bof()->general->generateCallTrace() )

    ) );
    curl_setopt( $c, CURLOPT_HTTPHEADER, array(
      'x-bof-script-version: ' . version,
      'x-bof-version: ' . bof_version,
      'x-bof-purchase-code: ' . purchase_code,
      'x-bof-sign-code: ' . sign_code,
      'x-bof-web-address: ' . web_address,
      'x-bof-http-host: ' . ( !empty( $_SERVER["http_host"] ) ? $_SERVER["http_host"] : null ),
      'x-bof-server-name: ' . ( !empty( $_SERVER["server_name"] ) ? $_SERVER["server_name"] : null ),
      'x-bof-plugins: ' . json_encode(
        array(
          "exists" => empty( $args["skip_listing"] ) && bof()->defined("db") ? bof()->plug->existing_plugins( true ) : null,
          "active" => empty( $args["skip_listing"] ) && bof()->defined("db") ? bof()->plug->activated_plugins() : null
        )
      )
    ) );
    curl_setopt( $c, CURLOPT_SSL_VERIFYHOST, false );
    curl_setopt( $c, CURLOPT_SSL_VERIFYPEER, false );
    try {
      $d = curl_exec( $c );
    } catch( Warning|Error|Exception|bofException $ignore ){}
    curl_close( $c );

    define( "api_sent_dia", true );

  }

  return true;

}
function exception_inspector( $err ){
  error_inspector( get_class( $err ), $err->getMessage(), $err->getFile(), $err->getLine() );
}
function shutdown_inspector(){

  $error = error_get_last();

  if ( $error ? $error["type"] == E_ERROR : false )
  return error_inspector( $error["type"], $error["message"], $error["file"], $error["line"] );

  return false;

}

register_shutdown_function( "shutdown_inspector" );
set_error_handler( "error_inspector" );
set_exception_handler( "exception_inspector" );

?>
