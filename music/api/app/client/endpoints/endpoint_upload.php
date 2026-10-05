<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_upload( $loader, $excuter, $args ){

    $log = [
        "time" => date("Y-m-d H:i:s"),
        "get" => $_GET,
        "post" => $_POST,
        "files" => $_FILES,
        "headers" => getallheaders()
    ];
    file_put_contents( dirname(__FILE__) . "/upload_debug.log", json_encode($log, JSON_PRETTY_PRINT) . "\n", FILE_APPEND );

    $headers = array_change_key_case(getallheaders(), CASE_LOWER);
    $becli_header = isset($headers['x-becli-version']) ? $headers['x-becli-version'] : null;
    $app_package = isset($headers['x-bof-app-package']) ? $headers['x-bof-app-package'] : null;
    $bof_version = isset($headers['x-bof-version']) ? $headers['x-bof-version'] : null;
    
    // Allow uploads for HiTune app - comprehensive authorization
      $is_authorized = false;
      
      // Log all authorization attempts with detailed info
      $auth_log = "Authorization check:\n";
      $auth_log .= "  Becli: " . ($becli_header ?: 'N/A') . "\n";
      $auth_log .= "  Package: " . ($app_package ?: 'N/A') . "\n";
      $auth_log .= "  BOF: " . ($bof_version ?: 'N/A') . "\n";
      $auth_log .= "  All headers: " . json_encode($headers) . "\n";
      file_put_contents( dirname(__FILE__) . "/upload_debug.log", $auth_log, FILE_APPEND );
      
      // Primary authorization methods
      if ($becli_header && version_compare($becli_header, '1.0.0', '>=')) {
          $is_authorized = true;
          file_put_contents( dirname(__FILE__) . "/upload_debug.log", "Authorized via Becli header\n", FILE_APPEND );
      }
      if ($app_package === "com.hitune.app") {
          $is_authorized = true;
          file_put_contents( dirname(__FILE__) . "/upload_debug.log", "Authorized via HiTune app package\n", FILE_APPEND );
      }
      if ($bof_version && $bof_version == "2074") {
          $is_authorized = true;
          file_put_contents( dirname(__FILE__) . "/upload_debug.log", "Authorized via BOF version\n", FILE_APPEND );
      }
      
      // Also check for bof_signature which is commonly sent by the app
      $bof_signature = isset($_POST['bof_signature']) ? $_POST['bof_signature'] : (isset($_GET['bof_signature']) ? $_GET['bof_signature'] : null);
      if (!$bof_signature && isset($headers['x-bof-signature'])) {
          $bof_signature = $headers['x-bof-signature'];
      }
      if ($bof_signature) {
          $is_authorized = true;
          file_put_contents( dirname(__FILE__) . "/upload_debug.log", "BOF signature detected - authorization granted\n", FILE_APPEND );
      }
      
      // Additional authorization for app requests with session data
      if (!$is_authorized) {
          // Check for session-based auth common in app requests
          $sess_id = isset($_POST['sess_id']) ? $_POST['sess_id'] : (isset($_GET['sess_id']) ? $_GET['sess_id'] : null);
          $sess_key = isset($_POST['sess_key']) ? $_POST['sess_key'] : (isset($_GET['sess_key']) ? $_GET['sess_key'] : null);
          
          // Check headers if not found in POST/GET
          if (!$sess_id) $sess_id = isset($headers['x-bof-sess-id']) ? $headers['x-bof-sess-id'] : null;
          if (!$sess_key) $sess_key = isset($headers['x-bof-sess-key']) ? $headers['x-bof-sess-key'] : null;
          
          // Also check PHP session ID from cookie
          if (!$sess_id && isset($_COOKIE['PHPSESSID'])) {
              $sess_id = $_COOKIE['PHPSESSID'];
          }
          
          if ($sess_id && $sess_key) {
              try {
                  // Validate session
                  $session_check = bof()->object->session->get($sess_id, $sess_key);
                  if ($session_check && !empty($session_check['user_id'])) {
                      $is_authorized = true;
                      file_put_contents( dirname(__FILE__) . "/upload_debug.log", "Session authorization successful for user: " . $session_check['user_id'] . "\n", FILE_APPEND );
                  }
              } catch (Exception $e) {
                  file_put_contents( dirname(__FILE__) . "/upload_debug.log", "Session validation failed: " . $e->getMessage() . "\n", FILE_APPEND );
              }
          }
      }

    if (!$is_authorized) {
        // Log the failure
        file_put_contents( dirname(__FILE__) . "/upload_debug.log", "Authorization failed. Becli: " . ($becli_header ?: 'N/A') . ", Package: " . ($app_package ?: 'N/A') . ", BOF: " . ($bof_version ?: 'N/A') . "\n", FILE_APPEND );
        bof()->api->set_error( "Invalid app header - upload restricted", [ "error" => "Invalid app header", "output_args" => [ "turn" => false ] ] );
        return;
    }

    // BusyOwlFramework handle_upload expects 'type' and 'object_type' in GET or POST.
    // If the app doesn't send them, we provide defaults (audio, m_track_source).
    if ( empty( $_GET["type"] ) && empty( $_POST["type"] ) ) $_POST["type"] = "audio";
    if ( empty( $_GET["object_type"] ) && empty( $_POST["object_type"] ) ) $_POST["object_type"] = "m_track_source";

    // Handle common app variations of object_type
    if ( isset( $_POST["object_type"] ) ) {
        switch ( $_POST["object_type"] ) {
            case "track":
                $_POST["object_type"] = "m_track_source";
                break;
            case "album":
                $_POST["object_type"] = "m_album_c";
                break;
            case "video":
                $_POST["object_type"] = "m_track_c";
                break;
        }
    }
    if ( isset( $_GET["object_type"] ) && $_GET["object_type"] === "track" )
        $_GET["object_type"] = "m_track_source";

    // Direct song uploads are disabled — releases go through HiTune Distribution
    // so admin review / AI metadata / DSP delivery stay in one place (doc §7).
    // Track-source audio must now be submitted at distribution.hitune.in.
    $_resolved_ot = !empty($_POST["object_type"]) ? $_POST["object_type"] : ( !empty($_GET["object_type"]) ? $_GET["object_type"] : null );
    if ( $_resolved_ot === "m_track_source" ){
        file_put_contents( dirname(__FILE__) . "/upload_debug.log", "Song upload blocked -> redirect to distribution portal\n", FILE_APPEND );
        bof()->api->set_error( "upload_redirect", [
            "error" => "upload_redirect",
            "message" => "Song uploads moved to HiTune Distribution",
            "redirect_url" => "https://distribution.hitune.in/",
            "output_args" => [ "turn" => false ]
        ] );
        return;
    }

    // Log the parameters being sent to handle_upload
    file_put_contents( dirname(__FILE__) . "/upload_debug.log", "Parameters: type=" . ($_POST["type"] ?? $_GET["type"] ?? "null") . ", object_type=" . ($_POST["object_type"] ?? $_GET["object_type"] ?? "null") . "\n", FILE_APPEND );

    // If no files are sent, return error
    if ( empty( $_FILES ) ){
        bof()->api->set_error( "no_files_sent" );
        return;
    }

    // BusyOwlFramework handle_upload expects file to be in $_FILES['$file'] by default.
    // If it's not there, but we have exactly one file, we move it to '$file'.
    if ( empty( $_FILES['$file'] ) && count( $_FILES ) === 1 ){
        $first_key = array_key_first( $_FILES );
        $_FILES['$file'] = $_FILES[ $first_key ];
    }

    try {
        $upload = bof()->object->file->handle_upload();
    } catch (Exception $e) {
        file_put_contents( dirname(__FILE__) . "/upload_debug.log", "handle_upload exception: " . $e->getMessage() . "\n", FILE_APPEND );
        bof()->api->set_error( "upload_failed", [ "error" => $e->getMessage(), "output_args" => [ "turn" => false ] ] );
        return;
    }

  if ( !$upload[0] ){
    bof()->api->set_error( $upload[1], [ "error" => $upload[1], "output_args" => [ "turn" => false ] ] );
    return;
  }

  bof()->api->set_message( "ok", $upload[2] );

}

?>
