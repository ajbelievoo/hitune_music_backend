<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_email_unsubscribe( $loader, $excuter, $args ){

  $email = bof()->nest->user_input( "get", "email", "email" );
  $key1 = bof()->nest->user_input( "get", "key1", "md5" );
  $key2 = bof()->nest->user_input( "get", "keys2", "md5" );
  $key3 = bof()->nest->user_input( "get", "keys3", "md5" );

  $check = bof()->db->_select(array(
    "table" => "_bof_cache_unsubscribe_links",
    "where" => array(
      [ "key1", "=", $key1 ],
      [ "key2", "=", $key2 ],
      [ "key3", "=", $key3 ],
      [ "time_add", ">", "SUBDATE( now(), INTERVAL 7 DAY )", true ],
      [ "time_used", null, null, true ]
    ),
    "limit" => 1,
    "single" => true
  ));

  if ( $check ){
    $user = bof()->object->user->sid( $check["user_id"] );
    if ( $user["email"] == $email ){

      $loader->response_html->set( array(
        "metaDatas" => array(
          "title" => array(
            "wrapper" => "title",
            "content" => bof()->object->language->turn( "done", [], [ "uc_first" => true, "lang" => "users" ] )
          )
        ),
        "content" => array(
          "inline_styles" => array(
            "wrapper" => array(
              "tag" => "style"
            ),
            "content" => '
            body {
                background: #fbfbfb;
                font-size: 13pt;
                font-family: "Roboto", sans-serif;
                color: #838383;
            }
            div {
                width: 500px;
                height: 400px;
                height: fit-content;
                max-width: 100%;
                padding: 40px;
                margin: auto;
                position: absolute;
                top: 0;
                bottom: 0;
                left: 0;
                right: 0;
                background: #fff;
                box-shadow: 0 0 8px 2px #f7f7f7;
                border-radius: 10px;
                box-sizing: border-box;
                text-align: center;
            }
            '
          ),
          "main" => array(
            "content" => "<div>".bof()->object->language->turn( "email_unsubscribed", [], [ "uc_first" => true, "lang" => "users" ] )."</div>"
          ),
        ),
        "styles" => array(
          "mdi" => array(
            "address" => "https://cdn.jsdelivr.net/npm/@mdi/font@6.5.95/css/materialdesignicons.min.css"
          ),
          "font" => array(
            "address" => "https://fonts.googleapis.com/css?family=Roboto:300,400,500&display=swap"
          )
        ),
      ) );

      bof()->object->user_setting->set( $user["ID"], "notify_email", 0 );

      bof()->db->_update(array(
        "table" => "_bof_cache_unsubscribe_links",
        "set" => array(
          [ "time_used", bof()->general->mysql_timestamp() ]
        ),
        "where" => array(
          [ "key1", "=", $key1 ],
          [ "key2", "=", $key2 ],
          [ "key3", "=", $key3 ],
          [ "user_id", "=", $user["ID"] ],
        )
      ));

    }
  }

}

?>
