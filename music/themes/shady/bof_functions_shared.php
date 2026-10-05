<?php

if ( !defined( "bof_root" ) ) die;

$bof->listen( "object_page", "_get_pre_designs_pre", function( $args, &$designs ){

  bof()->object->page->_add_pre_design( "bigslider", array(
    "name" => "Big Slider #1",
    "image" => "https://support.busyowl.co/bigslider.png",
    "demo" => "https://demo.busyowl.co/bigslider",
    "required_plugins" => [ "bof_music" ],
    "widgets" => array(
      "bigslider" => array(
        "manage" => array(
          "edit" => false,
          "delete" => false,
          "move" => false,
          "drops" => false
        ),
        "install" => array(
          "t" => "grid",
          "i" => "0",
          "args" => "{\"wid_name\":\"grid\",\"fitMain\":1,\"columns\":\"4_4_4\",\"wid_id\":\"%ID%\"}"
        )
      ),
      "bsi0" => array(
        "manage" => array(
          "delete" => false,
          "move" => false,
        ),
        "inputs" => array(
          "height" => array(
            "locked" => true
          ),
          "font_size" => array(
            "locked" => true
          ),
          "img_place" => array(
            "locked" => true
          ),
        ),
        "install" => array(
          "t" => "cta",
          "i" => "%bigslider_ID%_0",
          "args" => "{\"wid_name\":\"cta\",\"wid_title\":\"Slider One\",\"wid_sub_data\":\"Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry&#039;s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it\",\"background_img_url\":\"https:\\/\\/images.unsplash.com\\/photo-1537730748877-5d8fcd41a7ff?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1470&q=80\",\"background_img_dim\":\"radial_med_high\",\"font_size\":\"vlarge\",\"img_place\":\"right\",\"height\":\"full\",\"btn_title_1\":\"Read more\",\"btn_link_1\":\"https:\\/\\/facebook.com\",\"wid_id\":\"%ID%\"}"
        )
      ),
      "bsi1" => array(
        "manage" => array(
          "delete" => false,
          "move" => false,
        ),
        "inputs" => array(
          "height" => array(
            "locked" => true
          ),
          "font_size" => array(
            "locked" => true
          ),
          "img_place" => array(
            "locked" => true
          ),
        ),
        "install" => array(
          "t" => "cta",
          "i" => "%bigslider_ID%_1",
          "args" => "{\"wid_name\":\"cta\",\"wid_title\":\"Slider Two\",\"wid_sub_data\":\"Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry&#039;s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it\",\"background_img_url\":\"https:\\/\\/images.unsplash.com\\/photo-1516981442399-a91139e20ff8?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1470&q=80\",\"background_img_dim\":\"radial_med_high\",\"font_size\":\"vlarge\",\"img_place\":\"right\",\"height\":\"full\",\"btn_title_1\":\"Read more\",\"btn_link_1\":\"https:\\/\\/facebook.com\",\"btn_title_2\":\"Test\",\"btn_link_2\":\"https:\\/\\/facebook.com\",\"wid_id\":\"%ID%\"}"
        )
      ),
      "bsi2" => array(
        "manage" => array(
          "delete" => false,
          "move" => false,
        ),
        "inputs" => array(
          "height" => array(
            "locked" => true
          ),
          "font_size" => array(
            "locked" => true
          ),
          "img_place" => array(
            "locked" => true
          ),
        ),
        "install" => array(
          "t" => "cta",
          "i" => "%bigslider_ID%_2",
          "args" => "{\"wid_name\":\"cta\",\"wid_title\":\"Slider Three\",\"wid_sub_data\":\"Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry&#039;s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it\",\"background_img_url\":\"https:\\/\\/images.unsplash.com\\/photo-1531077435623-9520a54b5046?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1470&q=80\",\"background_img_dim\":\"radial_med_high\",\"font_size\":\"vlarge\",\"img_place\":\"right\",\"height\":\"full\",\"btn_title_1\":\"Read more\",\"btn_link_1\":\"https:\\/\\/google.com\",\"wid_id\":\"%ID%\"}"
        )
      ),
      "footer" => array(
        "manage" => array(
          "delete" => false,
          "move" => false
        ),
        "inputs" => array(
          "wid_title" => array(
            "locked" => true
          ),
          "wid_sub_data" => array(
            "locked" => true
          ),
          "wid_pagination" => array(
            "locked" => true
          ),
          "wid_link" => array(
            "locked" => true
          ),
          "wid_bg_img" => array(
            "locked" => true
          ),
          "wid_limit" => array(
            "locked" => true
          ),
          "wid_type" => array(
            "locked" => true
          ),
          "wid_slider_size" => array(
            "locked" => true
          ),
          "wid_slider_mason" => array(
            "locked" => true
          ),
          "wid_slider_rows" => array(
            "locked" => true
          ),
          "user_roles_only" => array(
            "locked" => true
          ),
          "user_roles_exclude" => array(
            "locked" => true
          ),
        ),
        "install" => array(
          "t" => "m_album",
          "i" => "99",
          "args" => "{\"wid_name\":\"m_album\",\"wid_title\":\"Footer albums\",\"order_by\":\"title\",\"wid_title\":\"\",\"wid_limit\":18,\"wid_type\":\"slider\",\"wid_slider_size\":\"large\",\"wid_slider_rows\":1,\"wid_id\":\"%ID%\"}"
        )
      ),
    ),
    "generic_widgets" => array(
      "tl1" => array(
        "t" => "m_track",
        "i" => "2",
        "args" => "{\"wid_name\":\"m_track\",\"order_by\":\"title\",\"wid_title\":\"Track list #1\",\"wid_sub_data\":\"Title #2 be here!\",\"wid_limit\":10,\"wid_type\":\"slider\",\"wid_slider_size\":\"medium\",\"wid_slider_rows\":1,\"wid_id\":\"%ID%\"}"
      ),
      "tl2" => array(
        "t" => "m_track",
        "i" => "3",
        "args" => "{\"wid_name\":\"m_track\",\"order_by\":\"title\",\"wid_title\":\"Track list #2\",\"wid_sub_data\":\"Title #2 be here!\",\"wid_limit\":10,\"wid_type\":\"slider\",\"wid_slider_size\":\"medium\",\"wid_slider_rows\":1,\"wid_id\":\"%ID%\"}"
      ),
      "tl3" => array(
        "t" => "m_track",
        "i" => "4",
        "args" => "{\"wid_name\":\"m_track\",\"order_by\":\"title\",\"wid_title\":\"Track list #3\",\"wid_sub_data\":\"Title #2 be here!\",\"wid_limit\":10,\"wid_type\":\"slider\",\"wid_slider_size\":\"medium\",\"wid_slider_rows\":1,\"wid_id\":\"%ID%\"}"
      ),
      "tl4" => array(
        "t" => "m_track",
        "i" => "5",
        "args" => "{\"wid_name\":\"m_track\",\"order_by\":\"title\",\"wid_title\":\"Track list #4\",\"wid_sub_data\":\"Title #2 be here!\",\"wid_limit\":10,\"wid_type\":\"slider\",\"wid_slider_size\":\"medium\",\"wid_slider_rows\":1,\"wid_id\":\"%ID%\"}"
      ),
      "midcta" => array(
        "t" => "cta",
        "i" => "6",
        "args" => "{\"wid_name\":\"cta\",\"wid_title\":\"Call to action\",\"wid_sub_data\":\"Ask users to do smth like signing up!\",\"background_img_url\":\"https:\\/\\/images.unsplash.com\\/photo-1566055909643-a51b4271aa47?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1470&q=80\",\"background_img_dim\":\"radial_high_high\",\"font_color\":\"#fff\",\"font_size\":\"vlarge\",\"img_place\":\"left\",\"height\":\"auto\",\"btn_title_1\":\"Click me\",\"btn_link_1\":\"https:\\/\\/google.com\",\"wid_id\":\"%ID%\"}"
      ),
      "footgrid" => array(
        "t" => "grid",
        "i" => "7",
        "args" => "{\"wid_name\":\"grid\",\"columns\":\"4_4_4\",\"wid_id\":\"%ID%\"}"
      ),
      "tt1" => array(
        "t" => "m_track",
        "i" => "%footgrid_ID%_0",
        "args" => "{\"wid_name\":\"m_track\",\"order_by\":\"s_likes\",\"wid_table_column\":[\"s_likes\"],\"wid_table_title\":\"Likes\",\"wid_title\":\"Track table #1\",\"wid_sub_data\":\"Title #2 be here!\",\"wid_pagination\":true,\"wid_limit\":10,\"wid_type\":\"table\",\"wid_id\":\"%ID%\"}"
      ),
      "tt2" => array(
        "t" => "m_track",
        "i" => "%footgrid_ID%_1",
        "args" => "{\"wid_name\":\"m_track\",\"order_by\":\"s_plays\",\"wid_table_column\":[\"s_plays\"],\"wid_table_title\":\"Streams\",\"wid_title\":\"Track table #2\",\"wid_sub_data\":\"Title #2 be here!\",\"wid_pagination\":true,\"wid_limit\":10,\"wid_type\":\"table\",\"wid_id\":\"%ID%\"}"
      ),
      "tt3" => array(
        "t" => "m_track",
        "i" => "%footgrid_ID%_2",
        "args" => "{\"wid_name\":\"m_track\",\"order_by\":\"title\",\"wid_table_column\":[\"duration\"],\"wid_table_title\":\"Duration\",\"wid_title\":\"Track table #3\",\"wid_sub_data\":\"Title #2 be here!\",\"wid_pagination\":true,\"wid_limit\":10,\"wid_type\":\"table\",\"wid_id\":\"%ID%\"}"
      )
    ),
    "append" => false,
    "exec" => function( &$page ){
      $page["page"]["classes"][] = "_cd_bigslider";
      // $page["page"]["classes"][] = "no_sidebar ";
      $page["page"]["classes"][] = "fw_container";
    }
  ) );

  bof()->object->page->_add_pre_design( "vertical_slider", array(
    "name" => "Vertical Slider",
    "image" => "https://support.busyowl.co/verticalslider.png",
    "demo" => "https://demo-full.busyowl.co/",
    "required_plugins" => [],
    "widgets" => array(),
    "generic_widgets" => array(
      "cta1" => array(
        "t" => "cta",
        "i" => "0",
        "args" => "{\"wid_name\":\"cta\",\"wid_title\":\"Slider Number #1\",\"wid_sub_data\":\"Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry&#039;s standard dummy text ever since the 1500s\",\"background_img_url\":\"https:\/\/images.unsplash.com\/photo-1490272951680-512033991007?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1471&q=80\",\"background_img_dim\":\"radial_med_med\",\"font_size\":\"vlarge\",\"img_place\":\"left\",\"height\":\"full\",\"btn_title_1\":\"Button\",\"btn_link_1\":\"button\",\"wid_id\":\"%ID%\"}"
      ),
      "cta2" => array(
        "t" => "cta",
        "i" => "1",
        "args" => "{\"wid_name\":\"cta\",\"wid_title\":\"Slider number #2\",\"wid_sub_data\":\"when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries\",\"background_img_url\":\"https:\/\/images.unsplash.com\/photo-1521230495671-1ae6797e96c4?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1074&q=80\",\"background_img_dim\":\"radial_high_high\",\"font_size\":\"vlarge\",\"img_place\":\"left\",\"height\":\"full\",\"wid_id\":\"%ID%\"}"
      ),
      "cta3" => array(
        "t" => "cta",
        "i" => "2",
        "args" => "{\"wid_name\":\"cta\",\"wid_title\":\"Slider number #3\",\"wid_sub_data\":\"it is a long established fact that a reader will be distracted by the readable content of a page when looking at its layout. The point of using Lorem Ipsum is that it has a more-or-less normal distribution of letters\",\"background_img_url\":\"https:\/\/images.unsplash.com\/photo-1634129366530-61d3e56a84fb?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1632&q=80\",\"background_img_dim\":\"radial_high_high\",\"font_size\":\"vlarge\",\"img_place\":\"left\",\"height\":\"full\",\"wid_id\":\"%ID%\"}"
      ),
    ),
    "append" => true,
    "supported_widgets" => [ "cta" ],
    "exec" => function( &$page ){
      $page["page"]["classes"][] = "_cd_vs";
      $page["page"]["classes"][] = "no_sidebar ";
      $page["page"]["classes"][] = "fw_container";
      $page["page"]["classes"][] = "no_footer";
      $page["page"]["classes"][] = "muse_hide";
    }
  ) );

  // Music Distribution Pages
  // Note: Pre-design redirects don't work because headers already sent
  // Using standalone files instead: /distribution.php, /distribution-dashboard.php, /distribution-submit.php

} );


?>
