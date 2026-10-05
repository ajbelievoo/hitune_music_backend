<?php

class object_core_files {

  protected $files = array(
    "class" => array(

      "db"       => bof_root . "/app/core/classes/helpers/class_db.php",
      "general"  => bof_root . "/app/core/classes/helpers/class_general.php",
      "curl"     => bof_root . "/app/core/classes/helpers/class_curl.php",
      "file"     => bof_root . "/app/core/classes/helpers/class_file.php",
      "ftp"      => bof_root . "/app/core/classes/helpers/class_ftp.php",
      "plug"     => bof_root . "/app/core/classes/helpers/class_plug.php",
      "editorjs" => bof_root . "/app/core/classes/helpers/class_editorjs.php",
      "image"    => bof_root . "/app/core/classes/helpers/class_image.php",
      "crypto"   => bof_root . "/app/core/classes/helpers/class_crypto.php",
      "scrapper" => bof_root . "/app/core/classes/helpers/class_scrapper.php",


      "nest"      => bof_root . "/app/core/classes/class_nest.php",
      "endpoint"  => bof_root . "/app/core/classes/class_endpoint.php",
      "user"      => bof_root . "/app/core/classes/class_user.php",
      "user_auth" => bof_root . "/app/core/classes/class_user_auth.php",
      "request"   => bof_root . "/app/core/classes/class_request.php",
      "session"   => bof_root . "/app/core/classes/class_session.php",
      "execute"   => bof_root . "/app/core/classes/class_execute.php",
      "boac"      => bof_root . "/app/core/classes/class_boac.php",
      "seo"       => bof_root . "/app/core/classes/class_seo.php",
      "theme"     => bof_root . "/app/core/classes/class_theme.php",
      "upload"    => bof_root . "/app/core/classes/class_upload.php",
      "source"    => bof_root . "/app/core/classes/class_source.php",
      "cronjob"   => bof_root . "/app/core/classes/class_cronjob.php",
      "search"    => bof_root . "/app/core/classes/class_search.php",

      "bofAdmin"  => bof_root . "/app/core/classes/class_bofAdmin.php",
      "bofClient" => bof_root . "/app/core/classes/class_bofClient.php",
      "bofInput"  => bof_root . "/app/core/classes/class_bofInput.php",

      "response"       => bof_root . "/app/core/classes/class_response.php",
      "response_html"  => bof_root . "/app/core/classes/responsers/class_response_html.php",
      "response_theme" => bof_root . "/app/core/classes/responsers/class_response_theme.php",
      "response_json"  => bof_root . "/app/core/classes/responsers/class_response_json.php",
      "response_file"  => bof_root . "/app/core/classes/responsers/class_response_file.php",
      "response_css"   => bof_root . "/app/core/classes/responsers/class_response_css.php",
      "response_cli"   => bof_root . "/app/core/classes/responsers/class_response_cli.php",
      "response_raw"   => bof_root . "/app/core/classes/responsers/class_response_raw.php",
      "api"            => bof_root . "/app/core/classes/responsers/class_api.php",

    ),
    "object" => array(

      "core_files"   => bof_root . "/app/core/objects/object_core_files.php",
      "core_setting" => bof_root . "/app/core/objects/object_core_setting.php",
      "db_setting"   => bof_root . "/app/core/objects/object_db_setting.php",
      "endpoint"     => bof_root . "/app/core/objects/object_endpoint.php",
      "session"      => bof_root . "/app/core/objects/object_session.php",
      "theme"        => bof_root . "/app/core/objects/responsers/object_response_theme.php",
      "html"         => bof_root . "/app/core/objects/responsers/object_response_html.php",

      "blacklist"      => bof_root . "/app/core/objects/object_blacklist.php",
      "ads"            => bof_root . "/app/core/objects/object_ads.php",
      "payment"        => bof_root . "/app/core/objects/object_payment.php",
      "currency"       => bof_root . "/app/core/objects/object_currency.php",
      "transaction"    => bof_root . "/app/core/objects/object_transaction.php",
      "language"       => bof_root . "/app/core/objects/object_language.php",
      "language_item"  => bof_root . "/app/core/objects/object_language_item.php",
      "storage"        => bof_root . "/app/core/objects/object_storage.php",
      "file"           => bof_root . "/app/core/objects/object_file.php",
      "menu"           => bof_root . "/app/core/objects/object_menu.php",
      "page"           => bof_root . "/app/core/objects/object_page.php",
      "page_widget"    => bof_root . "/app/core/objects/object_page_widget.php",

      "user"              => bof_root . "/app/core/objects/user/object_user.php",
      "user_setting"      => bof_root . "/app/core/objects/user/object_user_setting.php",
      "user_role"         => bof_root . "/app/core/objects/user/object_user_role.php",
      "user_subs_plan"    => bof_root . "/app/core/objects/user/object_user_subs_plan.php",
      "user_subs"         => bof_root . "/app/core/objects/user/object_user_subs.php",
      "user_request"      => bof_root . "/app/core/objects/user/object_user_request.php",
      "user_withdraw"      => bof_root . "/app/core/objects/user/object_user_withdraw.php",

      "cronjob"        => bof_root . "/app/core/objects/object_cronjob.php",
      "error_log"      => bof_root . "/app/core/objects/object_error_log.php",
      "search_history" => bof_root . "/app/core/objects/object_search_history.php",

      "ugc_playlist"  => bof_root . "/app/core/objects/UGC/object_playlist.php",
      "ugc_property"  => bof_root . "/app/core/objects/UGC/object_property.php",
      "ugc_action"    => bof_root . "/app/core/objects/UGC/object_action.php",

      "b_post"         => bof_root . "/app/core/objects/blog/object_post.php",
      "b_tag"          => bof_root . "/app/core/objects/blog/object_tag.php",
      "b_category"     => bof_root . "/app/core/objects/blog/object_category.php",

      "tag"     => bof_root . "/app/core/objects/samples/object_tag.php",
      "source"  => bof_root . "/app/core/objects/samples/object_source.php",

    ),
    "validator" => array(

      "raw"             => bof_root . "/app/core/validators/validator_raw.php",
      "equal"           => bof_root . "/app/core/validators/validator_equal.php",
      "string"          => bof_root . "/app/core/validators/validator_string.php",
      "string_abcd"     => bof_root . "/app/core/validators/validator_string_abcd.php",
      "string_code"     => bof_root . "/app/core/validators/validator_string_code.php",
      "string_color_hex" => bof_root . "/app/core/validators/validator_string_color_hex.php",
      "ip"              => bof_root . "/app/core/validators/validator_ip.php",
      "int"             => bof_root . "/app/core/validators/validator_int.php",
      "int_imploded"    => bof_root . "/app/core/validators/validator_int_imploded.php",
      "float"           => bof_root . "/app/core/validators/validator_float.php",
      "email"           => bof_root . "/app/core/validators/validator_email.php",
      "domain"          => bof_root . "/app/core/validators/validator_domain.php",
      "password"        => bof_root . "/app/core/validators/validator_password.php",
      "username"        => bof_root . "/app/core/validators/validator_username.php",
      "timestamp"       => bof_root . "/app/core/validators/validator_timestamp.php",
      "timestamp_range" => bof_root . "/app/core/validators/validator_timestamp_range.php",
      "datetime"        => bof_root . "/app/core/validators/validator_datetime.php",
      "year_range"      => bof_root . "/app/core/validators/validator_year_range.php",
      "json"            => bof_root . "/app/core/validators/validator_json.php",
      "md5"             => bof_root . "/app/core/validators/validator_md5.php",
      "sha256"          => bof_root . "/app/core/validators/validator_sha256.php",
      "md5s"            => bof_root . "/app/core/validators/validator_md5s.php",
      "url"             => bof_root . "/app/core/validators/validator_url.php",
      "in_array"        => bof_root . "/app/core/validators/validator_in_array.php",
      "array_in_array"  => bof_root . "/app/core/validators/validator_array_in_array.php",
      "boolean"         => bof_root . "/app/core/validators/validator_boolean.php",
      "file"            => bof_root . "/app/core/validators/validator_file.php",
      "html"            => bof_root . "/app/core/validators/validator_html.php",
      "editor_js"       => bof_root . "/app/core/validators/validator_editor_js.php",
      "bofClient_object" => bof_root . "/app/core/validators/validator_bofClient_object.php",
      "youtube_uri"     => bof_root . "/app/core/validators/validator_youtube_uri.php",
      "web3_wallet"     => bof_root . "/app/core/validators/validator_web3_wallet.php",
      "web3_tx"         => bof_root . "/app/core/validators/validator_web3_tx.php",

    ),
    "comparator" => array(

      "url"          => bof_root . "/app/core/comparators/comparator_url.php",
      "userAgent"    => bof_root . "/app/core/comparators/comparator_userAgent.php",
      "userIP"       => bof_root . "/app/core/comparators/comparator_userIP.php",
      "user"         => bof_root . "/app/core/comparators/comparator_user.php",
      "userInput"    => bof_root . "/app/core/comparators/comparator_userInput.php",
      "bofSignature" => bof_root . "/app/core/comparators/comparator_bofSignature.php",
      "platform"     => bof_root . "/app/core/comparators/comparator_platform.php",

    ),
    "html_dom" => array(

      "wrapper"     => bof_root . "/app/core/html_doms/wrapper.php",
      "input"       => bof_root . "/app/core/html_doms/input.php",
      "input_group" => bof_root . "/app/core/html_doms/input_group.php",
      "form"        => bof_root . "/app/core/html_doms/form.php",
      "label"       => bof_root . "/app/core/html_doms/label.php",
      "button"      => bof_root . "/app/core/html_doms/button.php",
      "a"           => bof_root . "/app/core/html_doms/a.php",
      "image"       => bof_root . "/app/core/html_doms/image.php",

    )
  );

  public function __construct(){
  }
  public function validate_key( $type, $name ){

    if ( !in_array( $type, array_keys( $this->files ), true ) )
    return false;

    if ( !in_array( $name, array_keys( $this->files[ $type ] ), true ) )
    return false;

    return $this->files[ $type ][ $name ];

  }
  public function add_key( $type, $name, $path, $override=false ){

    if ( !in_array( $type, array_keys( $this->files ), true ) )
    return false;

    if ( in_array( $name, array_keys( $this->files[ $type ] ), true ) && !$override )
    return false;

    $this->files[ $type ][ $name ] = $path;

    return true;

  }
  public function add_class( $name, $path, $override=false ){
    return bof()->object->core_files->add_key( "class", $name, $path, $override );
  }
  public function add_object( $name, $path, $override=false ){
    return bof()->object->core_files->add_key( "object", $name, $path, $override );
  }
  public function get_files( $type ){
    return $this->files[ $type ];
  }

}

?>
