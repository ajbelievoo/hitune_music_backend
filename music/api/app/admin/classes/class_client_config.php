<?php

if ( !defined( "bof_root" ) ) die;

class client_config {

  public $pages = array(
    "login" => array(
      "url" => "^login$",
    ),
    "index" => array(
      "url" => "^index$",
    ),
    "setting" => array(
      "url" => "^setting$",
    ),
    "content" => array(
      "url" => "^blog_list$",
    ),
    "users" => array(
      "url" => "^user_list$",
    ),
    "business" => array(
      "url" => "^payment_list$",
    ),
    "plugins" => array(
      "url" => "^plugins$",
    ),
    "files_hosts" => array(
      "url" => "^files_hosts$",
    ),
    "files_host" => array(
      "url" => "^files_host\/(.*?)$",
    ),
    "files_storage_setting" => array(
      "url" => "^files_storage_setting$",
    ),
    "files_upload_setting" => array(
      "url" => "^files_upload_setting$",
    ),
    "files_list" => array(
      "url" => "^files_list$",
    ),
  );

  public function add_page( $name, $arr ){
    $this->pages[ $name ] = $arr;
  }
  public function get_pages(){
    return $this->pages;
  }
  public function endpoint(){

    $AdminLogoAddr = web_address . "/admin/theme/assets/img/rkhm_logo.png";
    $AdminLogoID = bof()->object->db_setting->get( "admin_logo" );
    if ( $AdminLogoID ){
      $AdminLogo = bof()->object->file->select(["ID"=>$AdminLogoID]);
      if ( $AdminLogo ) $AdminLogoAddr = $AdminLogo["image_original"];
    }

    bof()->api->set_message( "ok", array(
      "pages" => bof()->client_config->get_pages(),
      "setting" => array(
        "logo" => $AdminLogoAddr
      ),
      "_ic" => bof()->object->db_setting->get( "_ic" ) ? true : false
    ) );

  }

}

?>
