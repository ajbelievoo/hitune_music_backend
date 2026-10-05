<?php

if ( !defined( "bof_root" ) ) die;

class object_language_item extends bof_type_object {

  // BusyOwlFramework handshake
  public function bof(){
    return array(
      "name" => "language_item",
      "label" => "Language Trarnslations",
      "icon" => "translation",
      "db_table_name" => "_d_languages_items"
    );
  }
  public function columns(){
    return array(
      "hook" => array(
        "public" => true,
        "validator" => array(
          "string",
          array(
            "strict" => true,
            "strict_regex" => "[a-zA-Z0-9\-_]"
          ),
        )
      ),
      "lang_code2" => array(
        "validator" => array(
          "string",
          array(
            "strict" => true,
            "strict_regex" => "[a-z]"
          ),
        ),
      ),
      "text" => array(
        "public" => true,
        "validator" => array(
          "html",
          array(
            "allowed_tags" => "<b><br><i><span><h1><h2><h3><h4><h5><h6><a><img><hr>",
            "encode" => true,
            "empty()"
          )
        )
      ),
    );
  }
  public function selectors(){
    return array(
      "hook" => [ "hook", "=" ],
      "lang_code2" => [ "lang_code2", "=" ],
      "full_hook" => function( $val ){

        $key_exploded = explode( "_", $val );
        $_lang = reset( $key_exploded );
        $_key = implode( "_", array_slice( $key_exploded, 1 ) );

        return array(
          "oper" => "AND",
          "cond" => array(
            [ "lang_code2", "=", $_lang ],
            [ "hook", "=", $_key ]
          )
        );

      },
    );
  }
  public function bof_columns(){
    return array(
      "ID",
      "time_add"
    );
  }
  public function relations(){
    return array(
      array(
        "exec" => array(
          "type" => "direct",
          "parent_object" => "language",
          "parent_object_selector_column" => "code2",
          "parent_object_stats_column" => "s_items",
          "child_object" => "language_item",
          "child_object_selector_column" => "lang_code2",
          "delete_child_too" => true
        )
      )
    );
  }

  // BusyOwlFramework helpers
  public function clean( $item, $args ){

    if ( !empty( $item["text"] ) )
    $item["text_decoded"] = htmlspecialchars_decode( $item["text"] );

    return $item;

  }

}

?>
