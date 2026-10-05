<?php

if ( !defined( "bof_root" ) ) die;

class object_b_tag extends bof_type_object_child {

  public $sample_parent = "tag";
  public function child(){
    return (object) array(
      "bof_admin_edit_url" => "blog_tag",
      "bof_admin_list_url" => "blog_tags",
      "bof_client_single_url" => "blog/tag",
      "bof_client_list_url" => "blog/tags",
      "relations" => array(
        "b_post" => array(
          "stat" => "posts",
          "hub_type" => "tag",
          "plural" => "tags",
          "bof_admin_list_url" => "blog_posts",
          "label" => bof()->object->language->turn( "posts" ),
        )
      )
    );
  }

  // BusyOwlFramework handshake
  public function bof(){
    return $this->_parent->bof(
      $this->_bof_this,
      array(
        "name" => "b_tag",
        "label" => "Blog Tag",
        "db_table_name" => "_c_b_tags",
      )
    );
  }

  public function clean_search_terms( $item ){

    $o = array(
      $item["name"] => 1,
    );

    return $o;

  }

}

?>
