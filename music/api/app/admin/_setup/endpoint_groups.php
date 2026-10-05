<?php

if ( !defined( "root" ) || !defined( "bof_root" ) ) die;

bof()->object->endpoint->add_group( "website", array(
  "url" => true,
  "response_type" => "html",
  "response_data" => array(
  )
) );

bof()->object->endpoint->add_group( "api", array(

  "response_type" => "json",

  "comparators" => array(

    "request_code_header" => array(
      "userInput",
      array(
        "type" => "http_header",
        "name" => "x-bof-request-code",
        "validator" => "equal",
        "validator_args" => array(
          "value" => "BusyOwlFrameWorkVersion201"
        )
      )
    ),

    "platform_header" => array(
      "userInput",
      array(
        "type" => "http_header",
        "name" => "x-bof-platform",
        "validator" => "in_array",
        "validator_args" => array(
          "values" => array(
            "web",
          )
        )
      )
    ),

    "version_header" => array(
      "userInput",
      array(
        "type" => "http_header",
        "name" => "x-bof-version",
        "validator" => "string",
        "validator_args" => array(
          "strict" => true,
          "strict_regex" => "[0-9\.]"
        )
      )
    )
  )

) );

bof()->object->endpoint->add_group( "guest", array(
  "groups" => [ "api" ],
  "comparators" => array(
    "just_unlogged" => array(
      "user",
      [ "is_logged" => false ]
    ),
  ),
) );

bof()->object->endpoint->add_group( "admin", array(
  "groups" => [ "api" ],
  "comparators" => array(
    "admin_only" => array(
      "user",
      array(
        "is_logged" => true,
        "valid_groups" => [ "admin" ]
      )
    ),
  ),
) );

bof()->object->endpoint->add_group( "moderator", array(
  "groups" => [ "api" ],
  "comparators" => array(
    "admin_and_moderators" => array(
      "user",
      array(
        "is_logged" => true,
        "valid_groups" => [ "moderator", "admin" ]
      )
    ),
  ),
) );

?>
