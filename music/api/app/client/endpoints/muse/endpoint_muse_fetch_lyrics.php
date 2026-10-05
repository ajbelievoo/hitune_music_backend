<?php

if (!defined("bof_root")) die;

function endpoint_muse_fetch_lyrics($loader, $excuter, $args)
{

  $object_name = $loader->nest->user_input("post", "ot", "bofClient_object", ["has_button" => "source"]);
  $object_hash = $loader->nest->user_input("post", "hash", "md5");

  if ($object_name && $object_hash ? $object_name == "m_track" : false) {

    $object_item = $loader->object->__get($object_name)->select(
      array(
        "hash" => $object_hash
      ),
      array(
        "_eq" => array(
          "artist" => []
        )
      )
    );

    if ($object_item) {

      if (!empty($object_item["lyrics"])) {
        $loader->api->set_message("ok", array(
          "type" => "local",
          "lyrics" => str_replace(["\r\n", "\n", PHP_EOL], "<br>", $object_item["lyrics"])
        ));
        return;
      }

      $fetch_automated = bof()->lyric->fetch( $object_item );

      if ( $fetch_automated === false || $fetch_automated === null )
      return;

      if ( $fetch_automated["type"] === "string" ){
        $loader->api->set_message("ok", array(
          "type" => "local",
          "lyrics" => $fetch_automated["data"]
        ));
        return;
      }
      elseif ( $fetch_automated["type"] === "musixmatch" ) {
        $loader->api->set_message("ok", array(
          "type" => "musixmatch",
          "lyrics" => $fetch_automated["lyrics"]
        ));
        return;
      }

      /*if (bof()->object->db_setting->get("musixmatch_lyrics")) {

        $musixmatch_id = null;
        if ($object_item["musixmatch_id"]) {
          $musixmatch_id = $object_item["musixmatch_id"];
        } elseif (!$object_item["time_musixmatch"]) {

          $loader->object->__get($object_name)->update(
            array(
              "ID" => $object_item["ID"]
            ),
            array(
              "time_musixmatch" => $loader->general->mysql_timestamp()
            )
          );

          try {
            $try_searching_musixmatch = $loader->musixmatch->_track_find(
              $object_item["bof_dir_artist"]["name"],
              $object_item["title"],
              !empty($object_item["time_release"]) ? $object_item["time_release"] : null
            );
          } catch (Exception $err) {
            $loader->api->set_error($err->getMessage(), ["output_args" => ["turn" => false]]);
            return;
          }

          $musixmatch_id = $try_searching_musixmatch["track_id"];
          $loader->object->__get($object_name)->update(
            array(
              "ID" => $object_item["ID"]
            ),
            array(
              "musixmatch_id" => $musixmatch_id
            )
          );
        }
        if (!$musixmatch_id) {
          $loader->api->set_error("found_nothing");
          return;
        }

        try {
          $get_lyrics = $loader->musixmatch->track_lyrics_get($musixmatch_id);
        } catch (Exception $err) {
          $loader->api->set_error($err->getMessage(), ["output_args" => ["turn" => false]]);
          return;
        }

        $loader->api->set_message("ok", array(
          "type" => "musixmatch",
          "lyrics" => $get_lyrics
        ));
      }*/

    }

  }

}
