<?php

if ( !defined( "root" ) || !defined( "bof_root" ) ) die;

class request_extend extends request {

  public function insert_log( $__args ){

    if ( ( $platform = bof()->nest->user_input( "http_header", "x_bof_platform", "in_array", [
      "values" => bof()->object->core_setting->get( "supported_platforms", null, [ "invalid_death" => true ] )
     ] ) ) ){

      $version = bof()->nest->user_input( "http_header", "x_bof_version", "string", [ "strict" => true, "strict_regex" => "[0-9\.]" ] );

      if ( $platform == "web" ){

        $device_cordova = null;
        $device_is_virtual = null;
        $device_manufacturer = null;
        $device_model = $__args["agent_model"];
        $device_platform = $__args["agent_browser"];
        $device_version = null;
        $device_uuid = md5( $__args["agent"] );
        $device_serial = md5( $__args["agent"] );

      }
      else {

        if (
          !( $device_cordova  = bof()->nest->user_input( "http_header", "x_bof_device_cordova", "string", [ "strict" => true, "strict_regex" => "[0-9\.]" ] ) ) ||
          !( $device_is_virtual = bof()->nest->user_input( "http_header", "x_bof_device_is_virtual", "in_array", [ "values" => [ false, true, "false", "true" ] ] ) ) ||
          !( $device_manufacturer = bof()->nest->user_input( "http_header", "x_bof_device_manufacturer", "string", [ "turn_lower" => true ] ) ) ||
          !( $device_model = bof()->nest->user_input( "http_header", "x_bof_device_model", "string", [ "turn_lower" => true ] ) ) ||
          !( $device_platform = bof()->nest->user_input( "http_header", "x_bof_device_platform", "string", [ "turn_lower" => true ] ) ) ||
          !( $device_version  = bof()->nest->user_input( "http_header", "x_bof_device_version", "string", [ "strict" => true, "strict_regex" => "[0-9\.]" ] ) ) ||
          !( $device_uuid = bof()->nest->user_input( "http_header", "x_bof_device_uuid", "string", [ "strict" => true, "strict_regex" => "[a-zA-Z0-9\.]" ] ) ) ||
          !( $device_serial = bof()->nest->user_input( "http_header", "x_bof_device_serial", "string", [ "strict" => true, "strict_regex" => "[a-zA-Z0-9\.\-_]" ] ) )
        ){

          parent::insert_log( array_merge( $__args, array(
            "sta" => -9,
            "result" => "Invalid Device Data"
          ) ) );
          die;

        }

      }

      bof()->db->_insert( array(
        "table" => "_bof_log_api_requests",
        "table" => bof()->object->core_setting->get( "api_request_log_table_name", null, [ "invalid_death" => true ] ),
        "set"   => array(
          [ "endpoint_name", $__args["endpoint_name"] ],
          [ "endpoint_data", $__args["endpoint_data"] ],
          [ "user_id", $__args["user_id"] ],
          [ "request_url", $__args["request_url"] ],
          [ "request_sessid", $__args["request_sessid"] ],
          [ "request_cookies", $__args["request_cookies"] ],
          [ "request_posts", $__args["request_posts"] ],
          [ "request_params", $__args["request_params"] ],
          [ "request_headers", $__args["request_headers"] ],
          [ "ip", $__args["ip"] ],
          [ "ip_country", $__args["ip_country"] ],
          [ "device_cordova", $device_cordova ],
          [ "device_is_virtual", $device_is_virtual ? ( $device_is_virtual == "true" || $device_is_virtual === true ? 1 : 0 ) : 0 ],
          [ "device_manufacturer", $device_manufacturer ],
          [ "device_model", $device_model ],
          [ "device_platform", $device_platform ],
          [ "device_version", $device_version ],
          [ "device_uuid", $device_uuid ],
          [ "device_serial", $device_serial ],
        )
      ) );

      if ( $__args["endpoint_name"] == "404" || $__args["endpoint_name"] == "no_access" ){
        parent::insert_log( array_merge( $__args, array(
          "sta" => 1,
        ) ) );
      }


      return;

    }

    parent::insert_log( $__args );

  }

}

?>
