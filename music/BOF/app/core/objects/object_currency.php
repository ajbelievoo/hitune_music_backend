<?php

if ( !defined( "bof_root" ) ) die;

class object_currency extends bof_type_object {

  public function bof(){
    return array(
      "name" => "currency",
      "label" => "currency",
      "icon" => "currency_pound",
      "db_table_name" => "_bof_currencies",
    );
  }
  public function columns(){
    return array(
      "name" => array(
        "public" => true,
        "label" => "Name",
        "tip" => 'Currency name. Browse <a href="https://en.wikipedia.org/wiki/List_of_circulating_currencies" target="_blank">this list</a>. Example: United States dollar',
        "validator" => array(
          "string",
          array(
            "strip_emoji" => false
          ),
        ),
        "input" => array(
          "type" => "text"
        ),
        "bofAdmin" => array(
          "list" => array(
            "type" => "simple",
            "class" => "title",
          ),
          "object" => array(
            "seo_slug_source" => true,
            "required" => true
          )
        )
      ),
      "iso_code" => array(
        "public" => true,
        "label" => "ISO Code",
        "tip" => 'Currency ISO code. Browse <a href="https://en.wikipedia.org/wiki/List_of_circulating_currencies" target="_blank">this list</a>. Example: USD, EUR, BTC, ETH',
        "validator" => array(
          "string",
          array(
            "min_length" => 3,
            "max_length" => 10,
            "strict" => true,
            "turn_upper" => true
          ),
        ),
        "input" => array(
          "type" => "text",
        ),
        "bofAdmin" => array(
          "sortable" => true,
          "object" => array(
            "required" => true
          ),
        )
      ),
      "type" => array(
        "public" => false,
        "label" => "Type",
        "validator" => array(
          "in_array",
          array(
            "values" => [ "c", "n" ]
          ),
        ),
        "input" => array(
          "type" => "select_i",
          "options" => array(
            [ "n", "Traditional money" ],
            [ "c", "Crypto currency"],
          ),
          "value" => "n"
        ),
        "bofAdmin" => array(
          "object" => array(
            "required" => true
          ),
        )
      ),
      "format" => array(
        "public" => true,
        "label" => "Display Format",
        "tip" => "If $ is your currency symbol, how should script display <b>2,555.666</b> dollars?",
        "input" => array(
          "name" => "cur_format",
          "type" => "select_i",
          "options" => array(
            [ "right_np_-2", "2,500$" ],
            [ "right_np_-1", "2,550$" ],
            [ "right_np_0", "2,555$" ],
            [ "right_np_1", "2,555.6$" ],
            [ "right_np_2", "2,555.66$" ],
            [ "right_p_-2", "2,500 $" ],
            [ "right_p_-1", "2,550 $" ],
            [ "right_p_0", "2,555 $" ],
            [ "right_p_1", "2,555.6 $" ],
            [ "right_p_2", "2,555.66 $" ],
            [ "left_np_-2", "$2,500" ],
            [ "left_np_-1", "$2,550" ],
            [ "left_np_0", "$2,555" ],
            [ "left_np_1", "$2,555.6" ],
            [ "left_np_2", "$2,555.66" ],
            [ "left_p_-2", "$ 2,500" ],
            [ "left_p_-1", "$ 2,550" ],
            [ "left_p_0", "$ 2,555" ],
            [ "left_p_1", "$ 2,555.6" ],
            [ "left_p_2", "$ 2,555.66" ],
            
            [ "right_np_-2SP", "2 500$" ],
            [ "right_np_-1SP", "2 550$" ],
            [ "right_np_0SP", "2 555$" ],
            [ "right_np_1SP", "2 555.6$" ],
            [ "right_np_2SP", "2 555.66$" ],
            [ "right_p_-2SP", "2 500 $" ],
            [ "right_p_-1SP", "2 550 $" ],
            [ "right_p_0SP", "2 555 $" ],
            [ "right_p_1SP", "2 555.6 $" ],
            [ "right_p_2SP", "2 555.66 $" ],
            [ "left_np_-2SP", "$2 500" ],
            [ "left_np_-1SP", "$2 550" ],
            [ "left_np_0SP", "$2 555" ],
            [ "left_np_1SP", "$2 555.6" ],
            [ "left_np_2SP", "$2 555.66" ],
            [ "left_p_-2SP", "$ 2 500" ],
            [ "left_p_-1SP", "$ 2 550" ],
            [ "left_p_0SP", "$ 2 555" ],
            [ "left_p_1SP", "$ 2 555.6" ],
            [ "left_p_2SP", "$ 2 555.66" ],

          )
        ),
        "validator" => array(
          "string",
          array(
            "strict" => true,
            "strict_regex_raw" => true,
            "strict_regex" => "/^(right|left)_(p|np)_(\-?)(0|1|2|3)(\SP)?$/u"
          )
        ),
        "bofAdmin" => array(
          "object" => array(
            "required" => true
          )
        )
      ),
      "exchange_rate" => array(
        "label" => "Echange rate",
        "tip" => "Echange rate from default currency ( <b>%DEF_CUR%</b> ) to this currency. Script will try to get exchange rate from <a href='https://exchangerate.host' target='_blank'>exchangerate.host</a> or <a href='https://coingate.com'>coingate.com</a> if left empty or set to 0",
        "validator" => array(
          "float",
          array(
            "empty()",
            "min" => 0,
            "forceZero" => true
          ),
        ),
        "input" => array(
          "type" => "text"
        ),
        "bofAdmin" => array(
          "object" => array(
          ),
          "list" => array(
            "type" => "simple",
            "renderer" => function( $displayItem, $item, $displayData ){

              if ( $item["_default"] ){
                $displayData["data"] = "<b>Source</b>";
              }

              else {

                $default_currency = $this->_bof_this->get_default();

                if ( !empty( $item["exchange_rate"] ) ){
                  $displayData["data"] = "<i>1 {$default_currency["symbol"]} = </i>{$item["exchange_rate"]} {$item["symbol"]}";
                  $displayData["sub_data"] = "Manually entered";
                }
                else {
                  $exchange_rate = $this->_bof_this->get_exchange_rate_from_api( $item["type"], $default_currency["type"], $default_currency["iso_code"], $item["iso_code"] );
                  $displayData["data"] = "<i>1 {$default_currency["symbol"]} = </i>{$exchange_rate} {$item["symbol"]}";
                  $displayData["sub_data"] = "From " . ( ( $default_currency["type"] == "c" || $item["type"] == "c" ) ? "<a href='https://developer.coingate.com/reference/get-rate' target='_blank'>Coingate.com</a>" : "<a href='https://github.com/fawazahmed0/exchange-api' target='_blank'>fawazahmed0/exchange-api</a>" );
                }

              }


              return $displayData;
            },
          )
        )
      ),
      "symbol" => array(
        "public" => true,
        "label" => "Symbol",
        "tip" => 'Currency Symbol. Browse <a href="https://en.wikipedia.org/wiki/List_of_circulating_currencies" target="_blank">this list</a>. Example: $, €, ¥',
        "validator" => array(
          "string",
          array(
          ),
        ),
        "input" => array(
          "type" => "text",
        ),
        "bofAdmin" => array(
          "sortable" => true,
          "object" => array(
            "required" => true
          ),
          "list" => array(
            "type" => "tag",
          )
        )
      ),
      "_default" => array(
        "label" => "Default",
        "validator" => array(
          "boolean",
          array(
            "empty()",
            "int" => true
          ),
        ),
        "bofAdmin" => array(
          "list" => array(
            "type" => "boolean_d"
          )
        )
      ),
      "active" => array(
        "label" => "Active",
        "validator" => array(
          "boolean",
          array(
            "empty()",
            "int" => true
          ),
        ),
        "bofAdmin" => array(
          "list" => array(
            "type" => "boolean",
            "args" => array(
              "payloads" => [ "activate", "deactivate" ]
            )
          )
        )
      ),
    );
  }
  public function bof_columns(){
    return array(
      "ID",
      "code" => array(
        "from" => array(
          "name"
        )
      ),
      "time_add",
    );
  }
  public function selectors(){
    return array(
      "query" => [ "name", "LIKE%lower" ],
      "active" => [ "active", "=" ],
      "iso_code" => [ "iso_code", "=" ],
      "_default" => [ "_default", "=" ],
    );
  }
  public function bof_admin(){
    return array(
      "config" => array(
        "search" => true,
        "create" => true,
        "edit" => true,
        "delete" => true,
        "pagination" => true,
        "edit_page_url" => "currency",
        "list_page_url" => "currencies",
        "multi" => array(
          "select" => false,
          "delete" => false,
          "edit"   => false
        )
      ),
      "filters" => array(
        "active" => array(
          "name" => "active",
          "title" => "Status",
          "input" => array(
            "name" => "active",
            "type" => "select_i",
            "options" => array(
              [ 0, "in-active" ],
              [ 1, "active" ],
              [ "__all__", "all" ]
            ),
            "value" => "__all__"
          ),
          "validator" => array(
            "in_array",
            array(
              "values" => [ "__all__", "0", "1" ]
            )
          )
        ),
      ),
      "buttons" => array(
        "activate" => array(
          "id" => "activate",
          "label" => "Activate",
          "payload" => array(
            "post" => array(
              "__action" => "activate"
            )
          )
        ),
        "deactivate" => array(
          "id" => "deactivate",
          "label" => "De-Activate",
          "payload" => array(
            "post" => array(
              "__action" => "deactivate"
            )
          )
        ),
        "make_default" => array(
          "skip_multi" => true,
          "id" => "make_default",
          "label" => "Make default currency",
          "payload" => array(
            "post" => array(
              "__action" => "make_default"
            )
          )
        ),
      ),
      "buttons_renderer" => function( $item, $buttons ){

        if ( $item["_default"] )
        unset( $buttons["delete"] );

        if ( $item["active"] )
        unset( $buttons["activate"] );

        if ( !$item["active"] || $item["_default"] )
        unset( $buttons["deactivate"] );

        if ( $item["_default"] )
        unset( $buttons["make_default"] );

        return $buttons;

      },
      "list" => array(
      ),
      "object_ui_renderer" => function( $object, $parsed, $args, $request, $_inputs, &$data ){

        if ( $request["type"] == "single" ){
          $_d = $request["content"][ $request["IDS"][0] ];
          if ( $_d["_default"] )
          unset( $data["display"]["exchange_rate"] );
        }

      },
      "object_item_renderer" => function( $item_name, &$item_data, $request ){
        if ( $item_name == "exchange_rate" ){
          $default_currency = $this->_bof_this->get_default();
          $item_data["tip"] = str_replace( "%DEF_CUR%", $default_currency["name"], $item_data["tip"] );
        }
      },
      "actions" => array(
        "activate" => function( $ids ){
          $this->_bof_this->update(array(
            "ID_in" => $ids
          ),array(
            "active" => 1
          ));
          return [ true, "Activated" ];
        },
        "deactivate" => function( $ids ){
          $this->_bof_this->update(array(
            "ID_in" => $ids,
            [ "_default", "!=", "1" ]
          ),array(
            "active" => 0
          ));
          return [ true, "De-Activated" ];
        },
        "make_default" => function( $ids ){

          $this->_bof_this->update(array(
            [ "_default", "=", "1" ]
          ),array(
            "_default" => 0,
          ),array(
            "cache_load_rt" => false
          ));

          $this->_bof_this->update(array(
            "ID_in" => $ids,
          ),array(
            "_default" => 1,
            "active" => 1,
          ),array(
            "cache_load_rt" => false
          ));

          return [ true, "Done" ];

        },
      ),
    );
  }

  public function select( $whereArgs=[], $selectArgs=[] ){

    $listing = false;
    $editing = false;
    $deleting = false;
    $_eq = [];
    extract( $selectArgs );

    if ( $deleting ){
      $whereArgs[] = array( "_default", "!=", "1" );
    }

    $selectArgs["_eq"] = $_eq;
    return bof()->object->_select( $this, $whereArgs, $selectArgs );

  }

  public function get_default( $selectArgs=[] ){
    return $this->_bof_this->select( ["_default"=>1], $selectArgs );
  }
  public function get_users( $args=[] ){

    if ( bof()->getName() != "bof_client" )
    return $this->_bof_this->get_default();

    $just_code = false;
    extract( $args );

    $chosen = bof()->session->get( "currency" );

    if ( $chosen ){
      $currency = $this->_bof_this->select(["iso_code"=>$chosen,"active"=>1]);
    }

    if ( empty( $currency ) ) {
      $currency = $this->_bof_this->get_default();
    }

    if ( $just_code )
    return $currency["iso_code"];

    return $currency;

  }

  public function parse_price( $price_float, $args=[] ){

    $target_currency = $this->_bof_this->get_users();
    $default_currency = $target_currency["_default"] ? $target_currency : $this->_bof_this->get_default();
    $zero_is_free = true;
    extract( $args );

    if ( empty( $target_currency ) )
    fall( "No currency defined" );

    if ( !$price_float )
    return array(
      "string" => $zero_is_free ? ( bof()->getName() == "bof_client" ? bof()->object->language->turn( "free", [], [ "uc_first" => true ] ) : "free" ) : $this->_bof_this->parse_price_string( 0 ),
      "default" => array(
        "price" => 0,
        "currency" => $default_currency["iso_code"]
      ),
      "user" => array(
        "price" => 0,
        "currency" => $target_currency["iso_code"]
      )
    );

    if ( $target_currency["_default"] ){
      $price_exchanged = $price_float;
    }
    else {

      if ( $target_currency["exchange_rate"] != 0 && $target_currency["exchange_rate"] != "" && ( is_int( $target_currency["exchange_rate"] ) || is_float( $target_currency["exchange_rate"] ) || is_numeric( $target_currency["exchange_rate"] ) ) ){
        $exchange_rate = $target_currency["exchange_rate"];
      }
      else {
        $exchange_rate = $this->_bof_this->get_exchange_rate_from_api( $target_currency["type"], $default_currency["type"], $default_currency["iso_code"], $target_currency["iso_code"] );
      }

      if ( empty( $exchange_rate ) ){
        $price_exchanged = $price_float;
        $target_currency = $default_currency;
      }
      else {

        if(is_string($price_float))
        fall("dead");

        $price_exchanged = round( $price_float * $exchange_rate, 3 );

      }

    }

    /*
    list( $sym_pos, $sym_separate, $round ) = explode( "_", $user_currency["format"] );

    $price_rounded = round( $price_exchanged, $round );
    $price_formated = number_format( (float) $price_rounded, $round > 0 ? $round : 0 );

    $string = ( $sym_pos == "left" ? $user_currency["symbol"] . ( $sym_separate == "p" ? " " : "" ) : "" ) .
    $price_formated . ( $sym_pos == "right" ? ( $sym_separate == "p" ? " " : "" ) . $user_currency["symbol"] : "" );
    */

    $string = $this->_bof_this->parse_price_string( $price_exchanged, $target_currency );

    return array(
      "string" => $string,
      "default" => array(
        "price" => $price_float,
        "currency" => $default_currency["iso_code"]
      ),
      "user" => array(
        "price" => $price_exchanged,
        "currency" => $target_currency["iso_code"]
      )
    );

  }
  public function parse_price_string( $price, $currency=null, $addTags=false ){

    if ( !$currency )
    $currency = $this->_bof_this->get_default();

    list( $sym_pos, $sym_separate, $round ) = explode( "_", $currency["format"] );

    $seperator = ",";

    if ( substr( $round, -2 ) == "SP" ){
      $round = substr( $round, 0, -2 );
      $seperator = " ";
    }

    $price = str_replace( ",", "", $price );

    $price_rounded = round( $price, $round );
    $price_formated = number_format( (float) $price_rounded, $round > 0 ? $round : 0 );

    if ( $seperator != "," )
    $price_formated = str_replace( ",", $seperator, $price_formated );

    if ( !$addTags ){
      return ( $sym_pos == "left" ? $currency["symbol"] . ( $sym_separate == "p" ? " " : "" ) : "" ) .
      $price_formated . ( $sym_pos == "right" ? ( $sym_separate == "p" ? " " : "" ) . $currency["symbol"] : "" );
    }

    return "<span class='price_wrapper'>" . ( $sym_pos == "left" ? "<span class='currency_wrapper onLeft'>{$currency["symbol"]}</span>" . ( $sym_separate == "p" ? " " : "" ) : "" ) .
    "<span class='_n'>{$price_formated}</span>" . ( $sym_pos == "right" ? ( $sym_separate == "p" ? " " : "" ) . "<span class='currency_wrapper onRight'>{$currency["symbol"]}</span>" : "" ) . "</span>";

  }
  public function get_exchange_rate_from_api( $type, $type2, $from, $to ){

    $_fl = strtolower( $from );

    if ( $type == "c" || $type2 == "c" ){
      $exe = bof()->curl->exe(
        array(
          "url" => "https://api.coingate.com/api/v2/rates/merchant/{$from}/{$to}",
          "agent" => "chrome",
          "cache" => true,
          "cache_load" => true,
          "cache_save" => true,
          "cache_age" => 1
        )
      )["data"];
      if ( !empty( $exe ) )
      return $exe;
    }
    else {
      $exe = bof()->curl->exe(
        array(
          "url" => "https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies/{$_fl}.json",
          "agent" => "chrome",
          "cache" => true,
          "cache_load" => true,
          "cache_save" => true,
          "cache_age" => 4
        )
      )["data"];
      if ( !empty( $exe[$_fl][strtolower($to)] ) )
      return $exe[$_fl][strtolower($to)];
    }

    return false;

  }

}

?>
