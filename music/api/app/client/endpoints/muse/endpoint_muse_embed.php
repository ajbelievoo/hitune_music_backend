<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_muse_embed( $loader, $excuter, $args ){

  preg_match( "/^muse_embed\/([a-z_]{1,30})\/([a-zA-Z0-9\-_]{32})\/$/", $loader->request->get_requested_url(), $m );
  list( $url, $object, $hash ) = $m;

  if (
    !$loader->nest->validate( $object, "bofClient_object", [ "has_button" => "source" ] ) ||
    !$loader->nest->validate( $hash, "md5" )
  ) return;

  if ( !bof()->object->db_setting->get( "muse_embedable", 1 ) )
  return;

  $_POST["type"] = !empty( $_REQUEST["_type"] ) ? $_REQUEST["_type"] : false;

  $the_object = $loader->object->__get( $object );
  $object_item = $the_object->select(
    array(
      "hash" => $hash
    ),
    array(
      "muse_source" => true,
      "_eq" => array(
        "sources" => array(),
        "cover" => []
      )
    )
  );

  if ( empty( $object_item ) )
  return;

  if ( empty( $object_item["sources"] ) )
  die("failed_no_source");

  $sources_gs = $object_item["sources"];

  foreach( $sources_gs as $source_G ){

    $sources_data = $source_G["data"];
    $sources_by_type = $loader->source->get( "stream", $source_G["ot"], $source_G["raw"], $source_G["sources"], "stream" );

    if ( $sources_by_type === "pending" )
    die("media_pending");

    if ( !empty( $sources_by_type["user"] ) ){

      $sources_data["link"] = web_address . $sources_data["link"];
      $sources_data["sub_link"] = web_address . $sources_data["sub_link"];

      $source["data"] = $sources_data;
      $source["source"] = $sources_by_type["user"]["muse"];
      $source["types"] = $sources_by_type["all"];

      $source["data"]["ID"] = $sources_by_type["user"]["hash"];

      if ( !empty( $source["source"]["type"][0] ) ?
        ( $source["source"]["type"][0] == "audio" || $source["source"]["type"][0] == "video" ) &&
        !empty( $source["source"]["type"][1]["address"] ) &&
        !empty( $sources_by_type["user"]["protected"] )
      : false ){
        if ( preg_match( "/\/files\/protected\//", $source["source"]["type"][1]["address"] ) )
        continue;
      }

      if ( empty( $source["data"]["cover"] ) ){
        $placeholder = $loader->object->db_setting->get( "placeholder" );
        if ( $placeholder ){
          $placeholder = $loader->object->file->select( [ "ID" => $placeholder ] );
          $source["data"]["cover"] = $placeholder["image_thumb"];
        }
      }

      if ( empty( $source["data"]["preview"] ) ? true : (  $source["data"]["preview"]["type"] == "image" && empty(  $source["data"]["preview"]["image"] ) ) ){
        $placeholder = $loader->object->db_setting->get( "placeholder" );
        if ( $placeholder ){
          $placeholder = $loader->object->file->select( [ "ID" => $placeholder ] );
          $source["data"]["preview"] = array(
            "type" => "image",
            "image" => $placeholder["image_strings"][1]["html"]
          );
        }
      }

      $sources[] = $source;

    }

  }

  if ( empty( $sources_by_type["all"]["count"] ) ){
    die("cant_play");
  }

  if ( empty( $sources ) ){
    die("access_denied");
  }

  $the_source_Data = reset( $sources );
  $the_data = $the_source_Data["data"];
  $the_source = $the_source_Data["source"];

  $loader->response_html->set( array(
    "bodyClass" => array(
      isset( $_REQUEST["_dark"] ) ? "dark" : "light"
    ),
    "metaDatas" => array(
      "title" => array(
        "wrapper" => "title",
        "content" => $the_data["title"]
      )
    ),
    "content" => array(
      "inline_styles" => array(
        "wrapper" => array(
          "tag" => "style"
        ),
        "content" => '
        :root {
          --m_color: #'.($loader->nest->user_input("get","_m_color","string_color_hex",[],"009ad2")).';
          --cover_w: 120px;
          --cover_h: 120px;
          --padding: 10px;
          --font_color: #000;
          --bg_color: #fff;
        }
        body.dark {
          --bg_color: #000;
          --font_color: #fff;
        }
        body {
          background: var(--bg_color);
          font-family: "Roboto", sans-serif;
          color: var(--font_color);
        }
        .queue {
          display: none;
          width: var(--cover_w);
          position: absolute;
          z-index: 1;
        }
        #player {
          position: relative;
          min-height: var(--cover_h);
          padding-left: calc( var(--cover_w) + ( var(--padding) * 2 ) );
        }
        #player .source_data .cover_holder {
          width: var(--cover_w);
          height: var(--cover_h);
          position: absolute;
          left: 0;
          top: 0;
          bottom: 0;
          margin: auto;
        }
        #player .source_data .cover_holder div {
          width: 100%;
          height: 100%;
          background-size: cover;
        }
        #player .controls_wrapper .control {
          display: none;
          background: var(--m_color);
          width: 40px;
          height: 40px;
          border-radius: 50%;
          line-height: 40px;
          text-align: center;
          font-size: 21pt;
          color: #fff;
          position: absolute;
          top: 22px;
        }
        #player .controls_wrapper .control.play {
          display: block;
          position: absolute;
        }
        #player .data_wrapper {
          padding-left: calc( 40px + (var(--padding)*2) );
        }
        #player .data_wrapper .more {
          display: none;
        }
        #player .data_wrapper .data {
          display: flex;
          flex-direction: column-reverse;
          min-height: 40px;
          padding-top: 20px;
        }
        #player .progress_bar {
          position: relative;
          box-sizing: border-box;
          width: 100%;
          height: 20px;
          padding: 0 60px;
          margin-top: calc( var(--padding) * 2 );
        }
        #player .progress_bar .progress {
          position: relative;
          width: 100%;
          height: 8px;
        }
        #player .progress_bar .progress input {
          width: 100%;
          height: 100%;
          position: absolute;
          right: 0;
          left: 0;
          top: 6px;
          bottom: 0;
          margin: auto;
          display: block;
          opacity: 0;
          z-index: 3;
        }
        #player .progress_bar .progress .progress_b,
        #player .progress_bar .progress .progress_e {
          position: absolute;
          top: 6px;
          right: 0;
          left: 0;
          border-radius: 4px;
          height: 6px;
          box-sizing: border-box;
          width: 0;
        }
        #player .progress_bar .progress .progress_b {
          width: 100% !important;
          background: var(--font_color);
          opacity: 0.06;
        }
        #player .progress_bar .progress .progress_e {
          background: var(--m_color);
          z-index: 1;
        }
        #player .buttons_wrapper {
          display: none;
        }
        #player .progress_bar .progress .time {
          position: absolute;
          left: -55px;
          font-size: 10pt;
          text-align: right;
          width: 40px;
          font-weight: 400;
          opacity: 0.4;
        }
        #player .progress_bar .progress .time.tot {
          left: auto;
          right: -55px;
          text-align: left;
        }
        #player .data_wrapper .data a {
          text-decoration: none;
          color: inherit;
          font-size: 10pt;
          font-weight: 400;
        }
        #player .data_wrapper .data a._title {
          font-size: 13pt;
          font-weight: 500;
        }
        body.muse_video_active .queue {
          display: block;
        }
        .queue #players .a_player {
            display: none;
            height: var(--cover_h);
            width: var(--cover_w);
            overflow: hidden;
            text-align: center;
            position: relative;
        }
        body.muse_video_active .queue #players .a_player#plyr {
            display: block;
        }
        .queue #players .a_player > * {
            margin: auto;
            position: absolute;
            right: 0;
            left: 0;
            top: 0;
            bottom: 0;
            margin: auto;
            width: var(--cover_w);
            height: var(--cover_h);
        }
        body.muse_video_active,
        body.muse_youtube_active {
            --cover_w: 200px;
        }
        .plyr__control--overlaid {
            background: var(--m_color);
            color: var(--bg_color);
        }'

      ),
      "main" => array(
        "content" => ""
      ),
      "json" => array(
        "content" => "var _iniData=".json_encode($sources).";var _ot='{$object}'; var _hash='{$hash}';window.config={cache_prefix:'rkhmads'}",
        "wrapper" => array(
          "tag" => "script"
        )
      ),
      "json2" => array(
        "content" => "var \$_bof_config=".json_encode( array(
          "assets_address" => endpoint_address . "assets/",
          "bof_assets_address" => bof_assets_address,
          "production" => false,
          "web_address" => web_address
        ) ).";",
        "wrapper" => array(
          "tag" => "script"
        )
      ),
      "scripts" => array(
        "content" => "window.bof_disable_autoload = true; window.lang = { return: function(){return \"\";} }",
        "wrapper" => array(
          "tag" => "script"
        )
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
    "scripts" => array(
      "jquery" => array(
        "address" => "https://code.jquery.com/jquery-3.6.3.min.js"
      ),
      "general" => array(
        "address" => bof_assets_address . "js/bof/helper/general.js?bof_version=" . 99999,
      ),
      "cache" => array(
        "address" => bof_assets_address . "js/bof/helper/cache.js?bof_version=" . 99999,
      ),
      "ui" => array(
        "address" => bof_assets_address . "js/bof/ui.js?bof_version=" . 99999,
      ),
      "bof" => array(
        "address" => bof_assets_address . "js/bof/bof.js?bof_version=" . 99999,
      ),
      "embed" => array(
        "address" => endpoint_address . "assets/js/app/bof_embed.js?bof_version=" . 99999,
      ),
      "muse" => array(
        "address" => bof_assets_address . "js/bof/bof_mini_muse.js?bof_version=" . 99999,
      ),
    ),
  ) );

  return;

}

?>
