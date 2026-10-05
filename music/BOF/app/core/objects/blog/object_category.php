<?php

if ( !defined( "bof_root" ) ) die;

class object_b_category extends bof_type_object_child {

  public $sample_parent = "tag";
  public function child(){
    return (object) array(
      "bof_admin_edit_url" => "blog_category",
      "bof_admin_list_url" => "blog_categories",
      "bof_client_single_url" => "blog/category",
      "bof_client_list_url" => "blog/categories",
      "relations" => array(
        "b_post" => array(
          "stat" => "posts",
          "hub_type" => "category",
          "plural" => "categories",
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
        "name" => "b_category",
        "label" => "Blog Category",
        "db_table_name" => "_c_b_categories",
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
