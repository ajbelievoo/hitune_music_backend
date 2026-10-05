<?php

if ( !defined( "bof_root" ) ) die;

class object_language extends bof_type_object {

  protected $cache = array(
    "default" => null,
    "users" => null,
    "users_code" => null
  );
  // BusyOwlFramework handshake
  public function bof(){
    return array(
      "name" => "language",
      "label" => "Language",
      "icon" => "translation",
      "db_table_name" => "_d_languages",
      "db_empty_select" => true
    );
  }
  public function columns(){
    $_languages = $this->_bof_this->get_supported_languages(["format"=>"options"]);
    return array(
      "name" => array(
        "public" => true,
        "label" => "Name",
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
          "sortable" => true,
          "list" => array(
            "type" => "simple",
            "class" => "title",
          ),
        )

      ),
      "code2" => array(
        "public" => true,
        "label" => "Code",
        "validator" => array(
          "in_array",
          array(
            "values" => array_keys( $_languages )
          ),
        ),
        "input" => array(
          "type" => "select",
          "options" => $_languages
        ),
        "bofAdmin" => array(
          "sortable" => true,
          "object" => array(
            "required" => true
          ),
          "list" => array(
            "type" => "tag"
          )
        )
      ),
      "code3" => array(
        "validator" => array(
          "string",
          array(
            "strict" => true,
            "strict_regex" => "[a-z]",
          ),
        ),
      ),
      "s_items" => array(
        "label" => "Translation items",
        "validator" => array(
          "int",
          array(
            "min" => 0,
            "empty()"
          ),
        ),
      ),
      "_default" => array(
        "label" => "Default<br>Language",
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
      "_index" => array(
        "label" => "Indexed<br>Language",
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
              "payloads" => [ "index", "unindex" ]
            )
          )
        )
      ),
      "time_add" => array(
        "validator" => array(
          "timestamp",
          array(
            "empty()",
          ),
        ),
      ),
    );
  }
  public function selectors(){
    return array(
      "code2" => [ "code2", "=" ],
      "code2_not" => [ "code2", "!=" ],
      "code3"  => [ "code3", "=" ],
      "_default" => [ "_default", "=" ],
      "query" => [ "name", "LIKE%lower" ],
      "_index"  => [ "_index", "=" ],
    );
  }
  public function bof_columns(){
    return array(
      "ID",
      "time_add"
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
        "edit_page_url" => "language",
        "list_page_url" => "languages",
        "multi" => array(
          "select" => true,
          "delete" => true,
          "edit"   => false
        )
      ),
      "buttons" => array(
        "index" => array(
          "id" => "index",
          "label" => "Index",
          "payload" => array(
            "post" => array(
              "__action" => "index"
            )
          )
        ),
        "unindex" => array(
          "id" => "unindex",
          "label" => "Un-Index",
          "payload" => array(
            "post" => array(
              "__action" => "unindex"
            )
          )
        ),
        "make_default" => array(
          "skip_multi" => true,
          "id" => "make_default",
          "label" => "Make default language",
          "payload" => array(
            "post" => array(
              "__action" => "make_default"
            )
          )
        ),
      ),
      "buttons_renderer" => function( $item, $buttons ){

        if ( $item["ID"] == 1 )
        unset( $buttons["delete"] );

        if ( $item["_index"] )
        unset( $buttons["index"] );

        if ( !$item["_index"] || $item["_default"] )
        unset( $buttons["unindex"] );

        if ( !$item["_default"] )
        $buttons["make_default"] = array(
          "id" => "make_default",
          "label" => "Make default language",
          "payload" => array(
            "post" => array(
              "__action" => "make_default"
            )
          )
        );

        return $buttons;

      },
      "filters" => array(),
      "list" => array(),
      "list_config" => array(),
      "object" => array(
      ),
      "object_ui_renderer" => function( $object, $parsed, $args, $request, $_inputs, &$data ){

        $items = [];

        if ( !empty( $data["request"]["IDS"] ) ){
          $ID = reset( $data["request"]["IDS"] );
          $item = $data["request"]["content"][$ID];
          if ( $item["code3"] != "eng" ){

            $en_items = bof()->object->language_item->select(array(
              "lang_code2" => "en"
            ),array(
              "limit" => false,
            ));

            if ( !empty( $item["bof_dir_items"] ) ){
              foreach( $item["bof_dir_items"] as $_item ){
                $_items[ $_item["hook"] ] = $_item["text"];
              }
            }

            foreach( $en_items as $en_item ){
              $items[] = array(
                "hook" => $en_item["hook"],
                "text_en" => $en_item["text"],
                "text" => isset( $_items[ $en_item["hook"] ] ) ? $_items[ $en_item["hook"] ] : null,
                "text_lang_code2" => $item["code2"],
                "text_lang_name" => $item["name"]
              );
            }

            $data["lang_name"] = $item["name"];

          }
          else {
            foreach( $item["bof_dir_items"] as $en_item ){
              $items[] = array(
                "hook" => $en_item["hook"],
                "text_en" => $en_item["text"],
              );
            }
          }

        }

        $data["_items"] = $items;

      },
      "object_item_renderer" => function( $item_name, &$item_data, $request ){
      },
      "object_be_renderer" => function( $_inputs, $request ){

        $_languages = bof()->object->language->get_supported_languages();

        if ( $request["type"] == "new" ){

          if ( !empty( $_inputs["data"]["code2"] ) ? $_languages[ $_inputs["data"]["code2"] ] : false ){

            $check_code = bof()->object->language->select(["code2"=>$_inputs["data"]["code2"]]);
            if ( $check_code ) $_inputs["report"]["fail"]["code2"] = "Already exists";

            $lang_data = $_languages[ $_inputs["data"]["code2"] ];
            $_inputs["data"]["code2"] = $_inputs["set"]["code2"] = $_inputs["update"]["code2"] = $lang_data["code2"];
            $_inputs["data"]["code3"] = $_inputs["set"]["code3"] = $_inputs["update"]["code3"] = $lang_data["code3"];
            $_inputs["data"]["name"] = $_inputs["set"]["name"] = $_inputs["update"]["name"] = $lang_data["name"];

            if ( is_file( ( $sqlPath = bof_root . "app/core/third/_translations/{$lang_data["code2"]}.sql" ) ) ){
              bof()->plug->import_sql( $sqlPath, false );
            }

          }
          else {
            $_inputs["report"]["fail"]["code2"] = "Invalid";
          }

        }

        return $_inputs;

      },
      "actions" => array(
        "index" => function( $ids ){
          bof()->object->language->update(array(
            "ID_in" => $ids
          ),array(
            "_index" => 1
          ));
          return [ true, "Indexed" ];
        },
        "unindex" => function( $ids ){
          bof()->object->language->update(array(
            "ID_in" => $ids,
            [ "_default", "!=", "1" ]
          ),array(
            "_index" => 0
          ));
          return [ true, "Un-Indexed" ];
        },
        "make_default" => function( $ids ){

          bof()->object->language->update(array(
            [ "_default", "=", "1" ]
          ),array(
            "_default" => 0
          ),array(
            "cache_load_rt" => false
          ));

          bof()->object->language->update(array(
            [ "ID", "=", is_array($ids)?$ids[0]:$ids ],
          ),array(
            "_default" => 1,
            "_index" => 1
          ),array(
            "cache_load_rt" => false
          ));

          return [ true, "Done" ];

        },
        "translate" => function( $ids ){

          $_languages = bof()->object->language->get_supported_languages();
          $key = bof()->nest->user_input( "post", "key", "string", [ "strict" => true, "strict_regex" => "[a-zA-Z0-9\-_]" ] );
          $val = bof()->nest->user_input( "post", "val", "html", [ "allowed_tags" => "<b><br><i><span><h1><h2><h3><h4><h5><h6><a><img><hr>", "encode" => true ] );

          if ( $key ){
            $key_exploded = explode( "_", $key );
            if ( count( $key_exploded ) >= 2 ){
              $_lang = reset( $key_exploded );
              $_key = implode( "_", array_slice( $key_exploded, 1 ) );
              if ( strlen( $_lang ) == 2 && in_array( $_lang, array_keys( $_languages ) ) && strlen( $_key ) >= 2 ){
                $_lang_check = bof()->object->language->select(["code2"=>$_lang]);
                if ( $_lang_check ){

                  if ( $val == "" || $val == " " ){

                    bof()->object->language_item->delete( array(
                      "full_hook" => $key
                    ) );

                    return [ true, "Removed" ];

                  }
                  else {

                    $_upsert = bof()->object->language_item->create(
                      array(
                        "full_hook" => $key,
                      ),
                      array(
                        "hook" => $_key,
                        "lang_code2" => $_lang,
                        "text" => $val
                      ),
                      array(
                        "hook" => $_key,
                        "lang_code2" => $_lang,
                        "text" => $val
                      )
                    );

                    if ( $_upsert ){
                      return [ true, "Saved" ];
                    }

                  }

                }
              }
            }
          }

        },
      ),
    );
  }
  public function relations(){
    return array(
      "items" => array(
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
  public function select( $whereArgs=[], $selectArgs=[] ){

    $listing = false;
    $editing = false;
    $deleting = false;
    $_eq = [];
    extract( $selectArgs );

    if ( $editing ){
      $_eq["items"] = array(
        "limit" => false
      );
    }

    if ( $deleting ){
      $whereArgs[] = array( "ID", "!=", "1" );
      $whereArgs[] = array( "_default", "!=", "1" );
    }

    if ( isset( $whereArgs[ "client" ] ) ){
      unset( $whereArgs[ "client"] );
      $whereArgs[] = array( "code2", "=", "en" );
    }

    $selectArgs["_eq"] = $_eq;
    return bof()->object->_select( $this, $whereArgs, $selectArgs );

  }
  public function clean( $item, $args ){

    $_eq = [];
    extract( $args );

    if ( !empty( $item["_default"] ) )
    $item["_index"] = 1;

    $item["direction"] = "ltr";
    if ( in_array( $item["code2"], [ "ar","he","fa","ku","pa","sd","ur" ], true ) )
    $item["direction"] = "rtl";

    return $item;

  }

  public function get_supported_languages( $args=[] ){

    $raw = '{"ab":{"name":"Abkhazian","native_name":"аҧсуа бызшәа, аҧсшәа","code2":"ab","code3":"abk"},"aa":{"name":"Afar","native_name":"Afaraf","code2":"aa","code3":"aar"},"af":{"name":"Afrikaans","native_name":"Afrikaans","code2":"af","code3":"afr"},"ak":{"name":"Akan","native_name":"Akan","code2":"ak","code3":"aka"},"sq":{"name":"Albanian","native_name":"Shqip","code2":"sq","code3":"sqi"},"am":{"name":"Amharic","native_name":"አማርኛ","code2":"am","code3":"amh"},"ar":{"name":"Arabic","native_name":"العربية","code2":"ar","code3":"ara"},"an":{"name":"Aragonese","native_name":"aragonés","code2":"an","code3":"arg"},"hy":{"name":"Armenian","native_name":"Հայերեն","code2":"hy","code3":"hye"},"as":{"name":"Assamese","native_name":"অসমীয়া","code2":"as","code3":"asm"},"av":{"name":"Avaric","native_name":"авар мацӀ, магӀарул мацӀ","code2":"av","code3":"ava"},"ae":{"name":"Avestan","native_name":"avesta","code2":"ae","code3":"ave"},"ay":{"name":"Aymara","native_name":"aymar aru","code2":"ay","code3":"aym"},"az":{"name":"Azerbaijani","native_name":"azərbaycan dili, تۆرکجه","code2":"az","code3":"aze"},"bm":{"name":"Bambara","native_name":"bamanankan","code2":"bm","code3":"bam"},"ba":{"name":"Bashkir","native_name":"башҡорт теле","code2":"ba","code3":"bak"},"eu":{"name":"Basque","native_name":"euskara, euskera","code2":"eu","code3":"eus"},"be":{"name":"Belarusian","native_name":"беларуская мова","code2":"be","code3":"bel"},"bn":{"name":"Bengali","native_name":"বাংলা","code2":"bn","code3":"ben"},"bi":{"name":"Bislama","native_name":"Bislama","code2":"bi","code3":"bis"},"bs":{"name":"Bosnian","native_name":"bosanski jezik","code2":"bs","code3":"bos"},"br":{"name":"Breton","native_name":"brezhoneg","code2":"br","code3":"bre"},"bg":{"name":"Bulgarian","native_name":"български език","code2":"bg","code3":"bul"},"my":{"name":"Burmese","native_name":"ဗမာစာ","code2":"my","code3":"mya"},"ca":{"name":"Catalan, Valencian","native_name":"català, valencià","code2":"ca","code3":"cat"},"ch":{"name":"Chamorro","native_name":"Chamoru","code2":"ch","code3":"cha"},"ce":{"name":"Chechen","native_name":"нохчийн мотт","code2":"ce","code3":"che"},"ny":{"name":"Chichewa, Chewa, Nyanja","native_name":"chiCheŵa, chinyanja","code2":"ny","code3":"nya"},"zh":{"name":"Chinese","native_name":"中文 (Zhōngwén), 汉语, 漢語","code2":"zh","code3":"zho"},"cv":{"name":"Chuvash","native_name":"чӑваш чӗлхи","code2":"cv","code3":"chv"},"kw":{"name":"Cornish","native_name":"Kernewek","code2":"kw","code3":"cor"},"co":{"name":"Corsican","native_name":"corsu, lingua corsa","code2":"co","code3":"cos"},"cr":{"name":"Cree","native_name":"ᓀᐦᐃᔭᐍᐏᐣ","code2":"cr","code3":"cre"},"hr":{"name":"Croatian","native_name":"hrvatski jezik","code2":"hr","code3":"hrv"},"cs":{"name":"Czech","native_name":"čeština, český jazyk","code2":"cs","code3":"ces"},"da":{"name":"Danish","native_name":"dansk","code2":"da","code3":"dan"},"dv":{"name":"Divehi, Dhivehi, Maldivian","native_name":"ދިވެހި","code2":"dv","code3":"div"},"nl":{"name":"Dutch, Flemish","native_name":"Nederlands, Vlaams","code2":"nl","code3":"nld"},"dz":{"name":"Dzongkha","native_name":"རྫོང་ཁ","code2":"dz","code3":"dzo"},"en":{"name":"English","native_name":"English","code2":"en","code3":"eng"},"eo":{"name":"Esperanto","native_name":"Esperanto","code2":"eo","code3":"epo"},"et":{"name":"Estonian","native_name":"eesti, eesti keel","code2":"et","code3":"est"},"ee":{"name":"Ewe","native_name":"Eʋegbe","code2":"ee","code3":"ewe"},"fo":{"name":"Faroese","native_name":"føroyskt","code2":"fo","code3":"fao"},"fj":{"name":"Fijian","native_name":"vosa Vakaviti","code2":"fj","code3":"fij"},"fi":{"name":"Finnish","native_name":"suomi, suomen kieli","code2":"fi","code3":"fin"},"fr":{"name":"French","native_name":"français","code2":"fr","code3":"fra"},"ff":{"name":"Fulah","native_name":"Fulfulde, Pulaar, Pular","code2":"ff","code3":"ful"},"gl":{"name":"Galician","native_name":"Galego","code2":"gl","code3":"glg"},"ka":{"name":"Georgian","native_name":"ქართული","code2":"ka","code3":"kat"},"de":{"name":"German","native_name":"Deutsch","code2":"de","code3":"deu"},"el":{"name":"Greek, Modern (1453–)","native_name":"Ελληνικά","code2":"el","code3":"ell"},"gn":{"name":"Guarani","native_name":"Avañe\'ẽ","code2":"gn","code3":"grn"},"gu":{"name":"Gujarati","native_name":"ગુજરાતી","code2":"gu","code3":"guj"},"ht":{"name":"Haitian, Haitian Creole","native_name":"Kreyòl ayisyen","code2":"ht","code3":"hat"},"ha":{"name":"Hausa","native_name":"(Hausa) هَوُسَ","code2":"ha","code3":"hau"},"he":{"name":"Hebrew","native_name":"עברית","code2":"he","code3":"heb"},"hz":{"name":"Herero","native_name":"Otjiherero","code2":"hz","code3":"her"},"hi":{"name":"Hindi","native_name":"हिन्दी, हिंदी","code2":"hi","code3":"hin"},"ho":{"name":"Hiri Motu","native_name":"Hiri Motu","code2":"ho","code3":"hmo"},"hu":{"name":"Hungarian","native_name":"magyar","code2":"hu","code3":"hun"},"ga":{"name":"Irish","native_name":"Gaeilge","code2":"ga","code3":"gle"},"ig":{"name":"Igbo","native_name":"Asụsụ Igbo","code2":"ig","code3":"ibo"},"ik":{"name":"Inupiaq","native_name":"Iñupiaq, Iñupiatun","code2":"ik","code3":"ipk"},"io":{"name":"Ido","native_name":"Ido","code2":"io","code3":"ido"},"is":{"name":"Icelandic","native_name":"Íslenska","code2":"is","code3":"isl"},"it":{"name":"Italian","native_name":"Italiano","code2":"it","code3":"ita"},"iu":{"name":"Inuktitut","native_name":"ᐃᓄᒃᑎᑐᑦ","code2":"iu","code3":"iku"},"ja":{"name":"Japanese","native_name":"日本語 (にほんご)","code2":"ja","code3":"jpn"},"jv":{"name":"Javanese","native_name":"ꦧꦱꦗꦮ, Basa Jawa","code2":"jv","code3":"jav"},"kl":{"name":"Kalaallisut, Greenlandic","native_name":"kalaallisut, kalaallit oqaasii","code2":"kl","code3":"kal"},"kn":{"name":"Kannada","native_name":"ಕನ್ನಡ","code2":"kn","code3":"kan"},"kr":{"name":"Kanuri","native_name":"Kanuri","code2":"kr","code3":"kau"},"ks":{"name":"Kashmiri","native_name":"कॉशुर, کٲشُر","code2":"ks","code3":"kas"},"kk":{"name":"Kazakh","native_name":"қазақ тілі","code2":"kk","code3":"kaz"},"km":{"name":"Central Khmer","native_name":"ខ្មែរ, ខេមរភាសា, ភាសាខ្មែរ","code2":"km","code3":"khm"},"ki":{"name":"Kikuyu, Gikuyu","native_name":"Gĩkũyũ","code2":"ki","code3":"kik"},"rw":{"name":"Kinyarwanda","native_name":"Ikinyarwanda","code2":"rw","code3":"kin"},"ky":{"name":"Kirghiz, Kyrgyz","native_name":"Кыргызча, Кыргыз тили","code2":"ky","code3":"kir"},"kv":{"name":"Komi","native_name":"коми кыв","code2":"kv","code3":"kom"},"kg":{"name":"Kongo","native_name":"Kikongo","code2":"kg","code3":"kon"},"ko":{"name":"Korean","native_name":"한국어","code2":"ko","code3":"kor"},"ku":{"name":"Kurdish","native_name":"Kurdî, کوردی","code2":"ku","code3":"kur"},"kj":{"name":"Kuanyama, Kwanyama","native_name":"Kuanyama","code2":"kj","code3":"kua"},"la":{"name":"Latin","native_name":"latine, lingua latina","code2":"la","code3":"lat"},"lb":{"name":"Luxembourgish, Letzeburgesch","native_name":"Lëtzebuergesch","code2":"lb","code3":"ltz"},"lg":{"name":"Ganda","native_name":"Luganda","code2":"lg","code3":"lug"},"li":{"name":"Limburgan, Limburger, Limburgish","native_name":"Limburgs","code2":"li","code3":"lim"},"ln":{"name":"Lingala","native_name":"Lingála","code2":"ln","code3":"lin"},"lo":{"name":"Lao","native_name":"ພາສາລາວ","code2":"lo","code3":"lao"},"lt":{"name":"Lithuanian","native_name":"lietuvių kalba","code2":"lt","code3":"lit"},"lu":{"name":"Luba-Katanga","native_name":"Kiluba","code2":"lu","code3":"lub"},"lv":{"name":"Latvian","native_name":"latviešu valoda","code2":"lv","code3":"lav"},"gv":{"name":"Manx","native_name":"Gaelg, Gailck","code2":"gv","code3":"glv"},"mk":{"name":"Macedonian","native_name":"македонски јазик","code2":"mk","code3":"mkd"},"mg":{"name":"Malagasy","native_name":"fiteny malagasy","code2":"mg","code3":"mlg"},"ms":{"name":"Malay","native_name":"Bahasa Melayu, بهاس ملايو","code2":"ms","code3":"msa"},"ml":{"name":"Malayalam","native_name":"മലയാളം","code2":"ml","code3":"mal"},"mt":{"name":"Maltese","native_name":"Malti","code2":"mt","code3":"mlt"},"mi":{"name":"Maori","native_name":"te reo Māori","code2":"mi","code3":"mri"},"mr":{"name":"Marathi","native_name":"मराठी","code2":"mr","code3":"mar"},"mh":{"name":"Marshallese","native_name":"Kajin M̧ajeļ","code2":"mh","code3":"mah"},"mn":{"name":"Mongolian","native_name":"Монгол хэл","code2":"mn","code3":"mon"},"na":{"name":"Nauru","native_name":"Dorerin Naoero","code2":"na","code3":"nau"},"nv":{"name":"Navajo, Navaho","native_name":"Diné bizaad","code2":"nv","code3":"nav"},"nd":{"name":"North Ndebele","native_name":"isiNdebele","code2":"nd","code3":"nde"},"ne":{"name":"Nepali","native_name":"नेपाली","code2":"ne","code3":"nep"},"ng":{"name":"Ndonga","native_name":"Owambo","code2":"ng","code3":"ndo"},"nb":{"name":"Norwegian Bokmål","native_name":"Norsk Bokmål","code2":"nb","code3":"nob"},"nn":{"name":"Norwegian Nynorsk","native_name":"Norsk Nynorsk","code2":"nn","code3":"nno"},"no":{"name":"Norwegian","native_name":"Norsk","code2":"no","code3":"nor"},"ii":{"name":"Sichuan Yi, Nuosu","native_name":"ꆈꌠ꒿ Nuosuhxop","code2":"ii","code3":"iii"},"nr":{"name":"South Ndebele","native_name":"isiNdebele","code2":"nr","code3":"nbl"},"oc":{"name":"Occitan","native_name":"occitan, lenga d\'òc","code2":"oc","code3":"oci"},"oj":{"name":"Ojibwa","native_name":"ᐊᓂᔑᓈᐯᒧᐎᓐ","code2":"oj","code3":"oji"},"om":{"name":"Oromo","native_name":"Afaan Oromoo","code2":"om","code3":"orm"},"or":{"name":"Oriya","native_name":"ଓଡ଼ିଆ","code2":"or","code3":"ori"},"os":{"name":"Ossetian, Ossetic","native_name":"ирон ӕвзаг","code2":"os","code3":"oss"},"pa":{"name":"Punjabi, Panjabi","native_name":"ਪੰਜਾਬੀ, پنجابی","code2":"pa","code3":"pan"},"pi":{"name":"Pali","native_name":"पालि, पाळि","code2":"pi","code3":"pli"},"fa":{"name":"Persian","native_name":"فارسی","code2":"fa","code3":"fas"},"pl":{"name":"Polish","native_name":"język polski, polszczyzna","code2":"pl","code3":"pol"},"ps":{"name":"Pashto, Pushto","native_name":"پښتو","code2":"ps","code3":"pus"},"pt":{"name":"Portuguese","native_name":"Português","code2":"pt","code3":"por"},"qu":{"name":"Quechua","native_name":"Runa Simi, Kichwa","code2":"qu","code3":"que"},"rm":{"name":"Romansh","native_name":"Rumantsch Grischun","code2":"rm","code3":"roh"},"rn":{"name":"Rundi","native_name":"Ikirundi","code2":"rn","code3":"run"},"ro":{"name":"Romanian, Moldavian, Moldovan","native_name":"Română, Moldovenească","code2":"ro","code3":"ron"},"ru":{"name":"Russian","native_name":"русский","code2":"ru","code3":"rus"},"sa":{"name":"Sanskrit","native_name":"संस्कृतम्, 𑌸𑌂𑌸𑍍𑌕𑍃𑌤𑌮𑍍","code2":"sa","code3":"san"},"sc":{"name":"Sardinian","native_name":"sardu","code2":"sc","code3":"srd"},"sd":{"name":"Sindhi","native_name":"सिंधी, سنڌي","code2":"sd","code3":"snd"},"se":{"name":"Northern Sami","native_name":"Davvisámegiella","code2":"se","code3":"sme"},"sm":{"name":"Samoan","native_name":"gagana fa\'a Samoa","code2":"sm","code3":"smo"},"sg":{"name":"Sango","native_name":"yângâ tî sängö","code2":"sg","code3":"sag"},"sr":{"name":"Serbian","native_name":"српски језик","code2":"sr","code3":"srp"},"gd":{"name":"Gaelic, Scottish Gaelic","native_name":"Gàidhlig","code2":"gd","code3":"gla"},"sn":{"name":"Shona","native_name":"chiShona","code2":"sn","code3":"sna"},"si":{"name":"Sinhala, Sinhalese","native_name":"සිංහල","code2":"si","code3":"sin"},"sk":{"name":"Slovak","native_name":"slovenčina, slovenský jazyk","code2":"sk","code3":"slk"},"sl":{"name":"Slovenian","native_name":"Slovenski jezik, Slovenščina","code2":"sl","code3":"slv"},"so":{"name":"Somali","native_name":"Soomaaliga, af Soomaali","code2":"so","code3":"som"},"st":{"name":"Southern Sotho","native_name":"Sesotho","code2":"st","code3":"sot"},"es":{"name":"Spanish, Castilian","native_name":"Español","code2":"es","code3":"spa"},"su":{"name":"Sundanese","native_name":"Basa Sunda","code2":"su","code3":"sun"},"sw":{"name":"Swahili","native_name":"Kiswahili","code2":"sw","code3":"swa"},"ss":{"name":"Swati","native_name":"SiSwati","code2":"ss","code3":"ssw"},"sv":{"name":"Swedish","native_name":"Svenska","code2":"sv","code3":"swe"},"ta":{"name":"Tamil","native_name":"தமிழ்","code2":"ta","code3":"tam"},"te":{"name":"Telugu","native_name":"తెలుగు","code2":"te","code3":"tel"},"tg":{"name":"Tajik","native_name":"тоҷикӣ, toçikī, تاجیکی","code2":"tg","code3":"tgk"},"th":{"name":"Thai","native_name":"ไทย","code2":"th","code3":"tha"},"ti":{"name":"Tigrinya","native_name":"ትግርኛ","code2":"ti","code3":"tir"},"bo":{"name":"Tibetan","native_name":"བོད་ཡིག","code2":"bo","code3":"bod"},"tk":{"name":"Turkmen","native_name":"Türkmençe, Türkmen dili","code2":"tk","code3":"tuk"},"tl":{"name":"Tagalog","native_name":"Wikang Tagalog","code2":"tl","code3":"tgl"},"tn":{"name":"Tswana","native_name":"Setswana","code2":"tn","code3":"tsn"},"to":{"name":"Tonga (Tonga Islands)","native_name":"Faka Tonga","code2":"to","code3":"ton"},"tr":{"name":"Turkish","native_name":"Türkçe","code2":"tr","code3":"tur"},"ts":{"name":"Tsonga","native_name":"Xitsonga","code2":"ts","code3":"tso"},"tt":{"name":"Tatar","native_name":"татар теле, tatar tele","code2":"tt","code3":"tat"},"tw":{"name":"Twi","native_name":"Twi","code2":"tw","code3":"twi"},"ty":{"name":"Tahitian","native_name":"Reo Tahiti","code2":"ty","code3":"tah"},"ug":{"name":"Uighur, Uyghur","native_name":"ئۇيغۇرچە, Uyghurche","code2":"ug","code3":"uig"},"uk":{"name":"Ukrainian","native_name":"Українська","code2":"uk","code3":"ukr"},"ur":{"name":"Urdu","native_name":"اردو","code2":"ur","code3":"urd"},"uz":{"name":"Uzbek","native_name":"Oʻzbek, Ўзбек, أۇزبېك","code2":"uz","code3":"uzb"},"ve":{"name":"Venda","native_name":"Tshivenḓa","code2":"ve","code3":"ven"},"vi":{"name":"Vietnamese","native_name":"Tiếng Việt","code2":"vi","code3":"vie"},"vo":{"name":"Volapük","native_name":"Volapük","code2":"vo","code3":"vol"},"wa":{"name":"Walloon","native_name":"Walon","code2":"wa","code3":"wln"},"cy":{"name":"Welsh","native_name":"Cymraeg","code2":"cy","code3":"cym"},"wo":{"name":"Wolof","native_name":"Wollof","code2":"wo","code3":"wol"},"fy":{"name":"Western Frisian","native_name":"Frysk","code2":"fy","code3":"fry"},"xh":{"name":"Xhosa","native_name":"isiXhosa","code2":"xh","code3":"xho"},"yi":{"name":"Yiddish","native_name":"ייִדיש","code2":"yi","code3":"yid"},"yo":{"name":"Yoruba","native_name":"Yorùbá","code2":"yo","code3":"yor"},"za":{"name":"Zhuang, Chuang","native_name":"Saɯ cueŋƅ, Saw cuengh","code2":"za","code3":"zha"},"zu":{"name":"Zulu","native_name":"isiZulu","code2":"zu","code3":"zul"}}';

    $format = "raw";
    $i_as_key = false;
    $add_all = false;
    extract( $args );

    $_langs = json_decode( $raw, 1 );

    if ( $format == "options" ){

      $_langs_o = $_langs;
      $_langs = [];

      if ( !empty( $add_all ) ){
        $_langs["__all__"] = [ "__all__", "All" ];
      }

      $i=0;
      foreach( $_langs_o as $_lang_code => $_lang_data ){
        $_langs[ $i_as_key ? $i : $_lang_code ] = array(
          $i_as_key ? $i : $_lang_code,
          "{$_lang_data["name"]}"
        );
        $i++;
      }

    }

    return $_langs;

  }
  public function get_country_language( $code2 ){

    $all = '{"aw":"nld","af":"prs","ao":"por","ai":"eng","ax":"swe","al":"sqi","ad":"cat","ae":"ara","ar":"grn","am":"hye","as":"eng","tf":"fra","ag":"eng","au":"eng","at":"bar","az":"aze","bi":"fra","be":"deu","bj":"fra","bf":"fra","bd":"ben","bg":"bul","bh":"ara","bs":"eng","ba":"bos","bl":"fra","by":"bel","bz":"bjz","bm":"eng","bo":"aym","br":"por","bb":"eng","bn":"msa","bt":"dzo","bv":"nor","bw":"eng","cf":"fra","ca":"eng","cc":"eng","ch":"fra","cl":"spa","cn":"cmn","ci":"fra","cm":"eng","cd":"fra","cg":"fra","ck":"eng","co":"spa","km":"ara","cv":"por","cr":"spa","cu":"spa","cw":"eng","cx":"eng","ky":"eng","cy":"ell","cz":"ces","de":"deu","dj":"ara","dm":"eng","dk":"dan","do":"spa","dz":"ara","ec":"spa","eg":"ara","er":"ara","eh":"ber","es":"cat","ee":"est","et":"amh","fi":"fin","fj":"eng","fk":"eng","fr":"fra","fo":"dan","fm":"eng","ga":"fra","gb":"eng","ge":"kat","gg":"eng","gh":"eng","gi":"eng","gn":"fra","gp":"fra","gm":"eng","gw":"por","gq":"fra","gr":"ell","gd":"eng","gl":"kal","gt":"spa","gf":"fra","gu":"cha","gy":"eng","hk":"eng","hm":"eng","hn":"spa","hr":"hrv","ht":"fra","hu":"hun","id":"ind","im":"eng","in":"eng","io":"eng","ie":"eng","ir":"fas","iq":"ara","is":"isl","il":"ara","it":"bar","jm":"eng","je":"eng","jo":"ara","jp":"jpn","kz":"kaz","ke":"eng","kg":"kir","kh":"khm","ki":"eng","kn":"eng","kr":"kor","xk":"sqi","kw":"ara","la":"lao","lb":"ara","lr":"eng","ly":"ara","lc":"eng","li":"deu","lk":"sin","ls":"eng","lt":"lit","lu":"deu","lv":"lav","mo":"por","mf":"fra","ma":"ara","mc":"fra","md":"ron","mg":"fra","mv":"div","mx":"spa","mh":"eng","mk":"mkd","ml":"fra","mt":"eng","mm":"mya","me":"srp","mn":"mon","mp":"cal","mz":"por","mr":"ara","ms":"eng","mq":"fra","mu":"eng","mw":"eng","my":"eng","yt":"fra","na":"afr","nc":"fra","ne":"fra","nf":"eng","ng":"eng","ni":"spa","nu":"eng","nl":"nld","no":"nno","np":"nep","nr":"eng","nz":"eng","om":"ara","pk":"eng","pa":"spa","pn":"eng","pe":"aym","ph":"eng","pw":"eng","pg":"eng","pl":"pol","pr":"eng","kp":"kor","pt":"por","py":"grn","ps":"ara","pf":"fra","qa":"ara","re":"fra","ro":"ron","ru":"rus","rw":"eng","sa":"ara","sd":"ara","sn":"fra","sg":"cmn","gs":"eng","sj":"nor","sb":"eng","sl":"eng","sv":"spa","sm":"ita","so":"ara","pm":"fra","rs":"srp","ss":"eng","st":"por","sr":"nld","sk":"slk","si":"slv","se":"swe","sz":"eng","sx":"eng","sc":"crs","sy":"ara","tc":"eng","td":"ara","tg":"fra","th":"tha","tj":"rus","tk":"eng","tm":"rus","tl":"por","to":"eng","tt":"eng","tn":"ara","tr":"tur","tv":"eng","tw":"cmn","tz":"eng","ug":"eng","ua":"rus","um":"eng","uy":"spa","us":"eng","uz":"rus","va":"ita","vc":"eng","ve":"spa","vg":"eng","vi":"eng","vn":"vie","vu":"bis","wf":"fra","ws":"eng","ye":"ara","za":"afr","zm":"eng","zw":"bwg","bq":"eng","sh":"eng"}';

    $all = json_decode( $all, true );

    if ( !empty( $all[ strtolower( $code2 ) ] ) )
    return $all[ strtolower( $code2 ) ];

    return false;

  }
  public function turn( $hook, $params=[], $args=[] ){

    $lang = bof()->getName() == "bof_client" ? "users" : $this->_bof_this->get_default();
    $uc_first = false;
    extract( $args );

    if ( $lang === "users" )
    $lang = $this->_bof_this->get_users();

    $hook_item = bof()->object->language_item->select(
      array(
        "lang_code2" => $lang,
        "hook" => $hook
      ),
      array(
        "no_bof_time" => true
      )
    );

    $output = !empty( $hook_item["text_decoded"] ) ? $hook_item["text_decoded"] : false;
    $output = $this->_bof_this->paste_params( $output, $params );

    if ( $uc_first )
    $output = ucfirst( $output );

    return $output;

  }
  public function parse_params( $string, $params ){

    if ( $params ){
      foreach( $params as $_r => $_v ){
        if ( $string && ( gettype( $_v ) == "string" || gettype( $_v ) == "integer" || gettype( $_v ) == "double" ) )
        $string = str_replace( "%{$_r}%", $_v, $string );
      }
    }

    return $string;

  }
  public function paste_params( $string, $params ){

    $output = $string;

    if ( $params ){
      foreach( $params as $_r => $_v ){

        if ( ( gettype( $_v ) == "string" || gettype( $_v ) == "integer" || gettype( $_v ) == "double" ) ? substr( $_v, 0, 2 ) == "##" : false )
        $_v = $this->_bof_this->turn( substr( $_v, 2 ) );

        if ( $output && ( gettype( $_v ) == "string" || gettype( $_v ) == "integer" || gettype( $_v ) == "double" ) )
        $output = str_replace( "%{$_r}%", $_v, $output );

      }
    }

    return $output;

  }

  public function get_default( $args=[] ){

    $just_code = true;
    extract( $args );

    $get = $this->cache["default"] ? $this->cache["default"] : $this->_bof_this->select(
      array(
        "_default" => 1
      )
    );

    if ( empty( $get ) )
    fall( "no_default_language" );

    return $just_code ? $get["code2"] : $get;

  }
  public function get_all( $args=[] ){

    $_index = 1;
    $_default = 0;
    extract( $args );

    return $this->_bof_this->select(
      array(
        "_index" => $_index,
        "_default" => $_default
      ),
      array(
        "limit" => false
      )
    );

  }
  public function get_users( $args=[] ){

    $check_language = false;
    $just_code = true;
    extract( $args );

    if ( bof()->getName() == "bof_admin" )
    return false;

    if ( $this->cache["users_code"] && $just_code )
    return $this->cache["users_code"];

    if ( $this->cache["users"] )
    return $this->cache["users"];

    $chosen = bof()->session->get( "language" );
    $userIP = bof()->request->get_userIP();
    if ( $chosen ){
      $code2 = $chosen;
    }
    elseif ( ( $chosen_by_header = bof()->nest->user_input( "http_header", "x-bof-language-code", "string", array(
      "strict" => true,
      "min_length" => 2,
      "max_length" => 2
    ) ) ) ){
      $code2 = $chosen_by_header;
      $check_language = true;
    }
    elseif (
      !empty( $userIP["country"] ) && ( defined( "assign_language_based_on_ip_location" ) ? assign_language_based_on_ip_location : true ) ?
      ( $country_language = $this->_bof_this->get_country_language( $userIP["country"] ) ) : false
    ){
      $code3 = $country_language;
      $language = $this->_bof_this->select(
        array(
          "code3" => $code3,
          "_index" => 1
        )
      );
    }

    if ( empty( $language ) && empty( $code2 ) ){
      $language = $this->_bof_this->get_default(["just_code"=>false]);
    }

    if ( $check_language && empty( $language ) && !empty( $code2 ) ){

      $language = $this->_bof_this->select(
        array(
          "code2" => $code2,
          "_index" => 1
        )
      );

      if ( empty( $language ) )
      $language = $this->_bof_this->get_default(["just_code"=>false]);

    }

    if ( !empty( $language ) ){
      $this->cache["users"] = $language;
      $this->cache["users_code"] = $language["code2"];
      return $just_code ? $language["code2"] : $language;
    }

    if ( !empty( $code2 ) && $just_code ){
      $this->cache["users_code"] = $code2;
      return $code2;
    }

    return false;

  }

}

?>
