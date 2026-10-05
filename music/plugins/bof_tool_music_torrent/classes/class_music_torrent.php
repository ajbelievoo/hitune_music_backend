<?php

if (!defined("bof_root")) die;

// sta::null => non-exists
// sta::0 => downloading
// sta::1 => no torrent found
// sta::2 => download faild
// sta::3 => download stalled ( todo )
// sta::4 => no audio-in-dled-torrent found
// sta::7 => research ( todo )
// sta::8 => redownload ( todo )
// sta::9 => ok



class music_torrent
{

  protected $inied = null;
  protected $trans_url = null;
  protected $trans_auth = null;
  protected $trans_path = null;
  protected $sta_hr = array(
    null => "Non-existent",
    0 => "Downloading",
    1 => "No torrent found",
    2 => "Download failed",
    3 => "Download stalled",
    4 => "No audio found in downloaded file/folder",
    7 => "Researching",
    8 => "Redownloading",
    9 => "All ready"
  );

  public function setup()
  {

    if (bof()->getName() == "bof_client")
      return;

    

    bof()->listen("bofAdmin", "object_parse_caller_after", function ($is, &$os) {
      $os["items"]["api_name"]["input"]["options"][] = ["torrent", "Torrent"];
      $os["items"]["api_name"]["tip"] = "<b>Torrent</b> only works with `Album` object type && `Dynamic IDs`";
    });

    bof()->listen("_custom", "music_cronjob_item", function ($cron, &$os) {
      if ($cron["api_name"] == "torrent" && $cron["object_type"] == "album") {

        $item = null;
        $local_id = null;
        extract($os);

        bof()->music_torrent->setup_trans();
        $done = bof()->music_torrent->handle_cron($item,$cron["PID"],$cron["GID"]);

        if ( $done === true || $done === false ){
          $os = array(
            "local_id" => $item["ID"],
            "spotify_id" => $item["spotify_id"],
            "item" => $item,
          );
        }
        elseif ( $done === null ){
          $os = array(
            "local_id" => "skip_record",
            "spotify_id" => $item["spotify_id"],
            "item" => $item,
          );
        }
        
      }
    });

    bof()->listen("highlights", "display_pre", function ($method_args, $method_result, $loader) {
      $highlights = $loader->highlights->getData();
      $highlights["extensions_links"]["items"]["tools_links"]["args"]["childs"][] = array(
              "icon" => "download",
              "title" => "Music Torrent",
              "link" => "music_torrent_setting"
      );
      bof()->highlights->setData($highlights);
    });

    bof()->listen("client_config", "get_pages_after", function ($method_args, &$method_result, $loader) {

      if (is_array($method_result)) {
        $method_result["music_torrent"] = array(
          "title" => "Music Torrent Setting",
          "url" => "^music_torrent_setting$",
          "link" => "music_torrent_setting",
          "theme_file" => "parts/content_setting",
          "becli" => array(
            (object) array(
              "endpoint" => "bofAdmin/setting/music_torrent/",
              "key" => "setting"
            )
          ),

          "__sb_family" => "extensions",
        );
      }
    });

    bof()->bofAdmin->_add_setting("music_torrent", array(
      "functions" => array(),
      "groups" => array(
        "trans" => array(
          "title" => "Transmission",
          "icon" => "table_chart",
          "inputs" => array(
            "tor_tra_url" => array(
              "title" => "Transmission RPC URL",
              "col_name" => "tor_tra_url",
              "input" => array(
                "name" => "tor_tra_url",
                "type" => "text",
              ),
              "validator" => array(
                "string",
                array(
                  "empty()",
                )
              )
            ),
            "tor_tra_auth" => array(
              "title" => "Transmission Auth",
              "tip" => "username:password",
              "col_name" => "tor_tra_auth",
              "input" => array(
                "name" => "tor_tra_auth",
                "type" => "text",
              ),
              "validator" => array(
                "string",
                array(
                  "empty()",
                )
              )
            ),
            "tor_tra_path" => array(
              "title" => "Transmission Download Dir",
              "col_name" => "tor_tra_path",
              "input" => array(
                "name" => "tor_tra_path",
                "type" => "text",
              ),
              "validator" => array(
                "string",
                array(
                  "empty()",
                )
              )
            ),
          )
        ),
        "tor" => array(
          "title" => "Torrent",
          "icon" => "table_chart",
          "inputs" => array(
            "tor_source" => array(
              "title" => "Search Source",
              "col_name" => "tor_source",
              "input" => array(
                "name" => "tor_source",
                "type" => "select_i",
                "options" => array(
                  ["thepiratebay", "The Pirate Bay"],
                  ["1337x", "1337x"],
                  ["limetorrents", "LimeTorrents"]
                ),
                "value" => "thepiratebay"
              ),
              "validator" => array(
                "string",
                array(
                  "empty()",
                )
              )
            ),
            "tor_minseed" => array(
              "title" => "Minimum seeds",
              "col_name" => "tor_minseed",
              "input" => array(
                "name" => "tor_minseed",
                "type" => "digit",
                "value" => 6
              ),
              "validator" => array(
                "int",
                array(
                  "empty()",
                )
              )
            ),
          )
        ),
      ),
      "action_btn_title" => "Save"
    ));
  }
  public function setup_trans()
  {

    if ($this->inied)
      return;

    $trans_url = bof()->object->db_setting->get("tor_tra_url");
    $trans_path = bof()->object->db_setting->get("tor_tra_path");
    $trans_auth = bof()->object->db_setting->get("tor_tra_auth");

    if (!$trans_url || !$trans_path || !$trans_auth)
      fall("Transmission not configured");

    $this->trans_url = $trans_url;
    $this->trans_path = $trans_path . ( substr( $trans_path, -1, 1 ) == "/" ? "" : "/" );
    $this->trans_auth = $trans_auth;

    if (!bof()->plugin_exists("torrent"))
      bof()->plugin("torrent", array(
        "transmission_rpc_addr" => $trans_url,
        "transmission_user_pwd" => $trans_auth,
        "transmission_dl_dir" => $this->trans_path
      ));

    $this->inied = true;
  }

  public function handle_cron($album,$PID,$GID)
  {

    if ( empty( $album["bof_dir_artist"] ) )
    $album["bof_dir_artist"] = bof()->object->m_artist->sid( $album["artist_id"] );

    $mt_data = bof()->db->_select(array(
      "table" => "_music_torrent",
      "where" => array(
        ["album_id", "=", $album["ID"]]
      ),
      "single" => true,
      "limit" => 1
    ));

    $torrent_hash = !empty($mt_data["torrent_hash"]) ? $mt_data["torrent_hash"] : null;
    try {
      list($sta, $torrent_hash) = $this->handle_album($album, $mt_data);
    } catch (bofException $err) {
      $negative_sta = $sta = $err->getExtra()["sta"];
      $err_msg = $err->getMessage();
    }

    bof()->cronjob->log_p($PID, $GID, "Album: {$album["title"]}. Sta: " . ( $this->sta_hr[ $sta ] ) . (!empty($err_msg) ? " Err:{$err_msg}" : ""));

    if ( !empty( $err_msg ) )
    return false;

    if (empty($mt_data)) {
      bof()->db->_insert(array(
        "table" => "_music_torrent",
        "set" => array(
          ["album_id", $album["ID"]],
          ["sta", $sta],
          ["query", $album["title"]],
          ["torrent_hash", $torrent_hash],
        )
      ));
    } else {
      bof()->db->_update(array(
        "table" => "_music_torrent",
        "where" => array(
          ["album_id", "=", $album["ID"]],
        ),
        "set" => array(
          ["sta", $sta],
          ["torrent_hash", $torrent_hash],
        )
      ));
    }

    return !empty($sta) ? ( $sta == 9 ? true : null ) : null;

  }
  protected function handle_album($album, $mt_data)
  {

    $sta = $mt_data ? $mt_data["sta"] : null;

    $a_search = false;
    $a_download = false;
    $a_check = false;

    if ($sta === null || $sta == 7)
      $a_search = true;

    if ($sta === null || $sta == 8)
      $a_download = true;

    if ($sta === 0 || $sta === "0")
      $a_check = true;

    $torrent_hash = !empty($mt_data["torrent_hash"]) ? $mt_data["torrent_hash"] : null;
    if ($a_search) {
      try {
        $search_album = $this->handle_album_search($album);
        $torrent_hash = $search_album["link"];
      } catch (Exception $err) {
        fall("search failed: " . $err->getMessage(), ["sta" => 1]);
      }
    }

    if ($a_download && empty($torrent_hash))
      fall("download failed: no torrent hash", ["sta" => 1]);

    if ($a_download) {
      try {
        $exe_download = $this->handle_album_download($torrent_hash);
      } catch (Exception $err) {
        fall("download failed: " . $err->getMessage(), ["sta" => 2]);
      }
      return array(0, $torrent_hash);
    }

    if ($a_check)
      $this->handle_album_check($album, $torrent_hash, $sta);

    return array($sta, $torrent_hash);

  }
  protected function handle_album_search($album)
  {

    $query = htmlspecialchars_decode("{$album["bof_dir_artist"]["name"]} {$album["title"]}");
    $query = trim(strtolower(str_replace(["&", ",", "  ", "   "], [" ", " ", " ", " "], $query)));

    $search_torrent = bof()->torrent->search("thepiratebay", $query);
    if (empty($search_torrent) ? true : !is_array($search_torrent))
      throw new Exception("noResult");

    $highestSeeds = 0;
    foreach ($search_torrent as $aTorrent) {
      if ($aTorrent["seed"] > $highestSeeds)
        $highestSeeds = $aTorrent["seed"];
      $torrents[$aTorrent["link"]] = $aTorrent;
    }

    $scores = [];
    foreach ($torrents as $aTorrent) {

      if ($aTorrent["seed"] < 7)
        continue;

      $seed_score = (60 / $highestSeeds) * $aTorrent["seed"];

      similar_text($query, $aTorrent["name"], $sim_score);
      $sim_score = $sim_score * .2;

      $key_score = 0;
      if (preg_match("/(320kbps|320 kbps)/", strtolower($aTorrent["name"])))
        $key_score = 20;
      elseif (preg_match("/(mp3)/", strtolower($aTorrent["name"])))
        $key_score = 10;

      $total_score = $seed_score + $key_score + $sim_score;

      if ($total_score > 30)
        $scores[$aTorrent["link"]] = $total_score;
    }

    if (empty($scores) ? true : !is_array($scores))
      throw new Exception("noGoodResult:1");

    krsort($scores);

    $sks = array_keys($scores);
    $skf = reset($sks);

    return $torrents[$skf];
  }
  protected function handle_album_download($hash)
  {
    return bof()->torrent->transmission_add($hash);
  }
  protected function handle_album_check($album, $hash, &$sta)
  {

    $getData = bof()->torrent->transmission_check($hash);

    // check stalled

    if ($getData["isFinished"]) {
      $this->handle_album_move($album, $hash, $sta);
    }

  }
  protected function handle_album_move($album, $hash, &$sta)
  {

    $hash = strtolower($hash);
    $check_dl_dir = bof()->file->scandir($this->trans_path . "{$hash}", array(
      "search_by_extension" => ["mp3", "wav", "aac", "flac", "ogg"]
    ));

    $tracks = bof()->object->m_track->select(
      array(
        "album_id" => $album["ID"]
      ),
      array(
        "limit" => false,
        "single" => false,
        "clean" => false,
        "columns" => "ID,title,album_index,duration"
      )
    );

    if (empty($tracks) )
    fall("No tracks found in database. Album has no tracks in database!", ["sta" => 4]);

    if (empty($check_dl_dir["search"]))
    fall("No valid tracks found", ["sta" => 4]);

    $compares = [];
    foreach ($tracks as $track) {
      foreach ($check_dl_dir["search"] as $dl_track) {

        $dl_track_tags = bof()->id3->read_tags($dl_track);
        if (!empty($dl_track_tags["tags"]["cover_string"])) unset($dl_track_tags["tags"]["cover_string"]);

        $duration_score = $dl_track_tags["duration"] && $track["duration"] ? 20 - abs(($dl_track_tags["duration"] - $track["duration"]) * 0.7) : 20;
        $i_score = $track["album_index"] == $dl_track_tags["tags"]["album_order"] ? 20 : 0;
        similar_text($track["title"], $dl_track_tags["tags"]["title"], $title_score);
        $title_score = ceil($title_score * .6);

        $total_score = ceil(($duration_score > 0 ? $duration_score : 0) + $i_score + $title_score);

        if ($total_score > 44 ? (empty($compares[$dl_track]) ? true : $compares[$dl_track]["score"] < $total_score) : false) {
          $compares[$dl_track] = array(
            "score" => $total_score,
            "track" => $track
          );
        }
      }
    }

    if (empty($compares))
      fall("tracks comparasion went wrong", ["sta" => 4]);

    $rules = bof()->object->file->get_rules("audio", "m_track_source", ["get_host" => true]);

    foreach ($compares as $file => $score) {

      bof()->object->m_track_source->delete(
        array(
          "track_id" => $score["track"]["ID"]
        )
      );

      $t_file_id = bof()->object->file->insert(
        array(
          "type" => "audio",
          "host_id" => "1",
          "dest_host_id" => $rules["file_host"],
          "path" => bof()->object->file->clean_path($file, true),
          "object_type" => "m_track_source",
        )
      );

      $t_source = bof()->object->m_track_source->create(
        [],
        array(
          "target_id" => $score["track"]["ID"],
          "type" => "audio",
          "data" => array(
            "file_type" => "local",
            "local_file" => $t_file_id,
          ),
        ),
        []
      );
    }

    $sta = 9;
    bof()->torrent->transmission_remove($hash);
    exec("rm -rf " . $this->trans_path . "{$hash}");

  }
}
