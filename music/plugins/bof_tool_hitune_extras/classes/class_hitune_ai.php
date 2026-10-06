<?php

if ( !defined( "bof_root" ) ) die;

/**
 * HiTune AI Studio — job queue + processing engines (doc §4/§6/§9).
 *
 * Jobs live in `_htx_ai_jobs` and are processed either inline (ffmpeg
 * fallbacks are fast) or by the cron worker scripts/ai_worker.php.
 *
 * Engine resolution per tool (admin panel picks the provider; the best
 * available engine that exists on the server wins):
 *   karaoke    demucs -> spleeter -> ffmpeg vocal-cut (always available)
 *   master     matchering -> configured API URL -> ffmpeg loudnorm
 *   lyrics     whisper binary -> faster-whisper -> configured API URL
 *   cover_art  configured provider API (sdxl/flux/dalle3/midjourney)
 *   clip_video ffmpeg (local, always available) — cover+extract -> reel video
 *
 * Every job decrements the per-plan daily quota via
 * bof()->hitune_extras->ai_quota_left() / ai_quota_spend().
 */
class hitune_ai {

  private $_tables_ready = false;

  const TOOLS = array( "song_gen", "karaoke", "master", "lyrics", "cover_art", "clip_video" );

  /* ------------------------------------------------------------------ */
  /* Schema                                                              */
  /* ------------------------------------------------------------------ */

  public function ensure_tables(){
    if ( $this->_tables_ready ) return true;
    bof()->db->query( "CREATE TABLE IF NOT EXISTS `_htx_ai_jobs` (
      `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      `user_id` INT(11) UNSIGNED NOT NULL,
      `type` ENUM('karaoke','master','lyrics','cover_art','clip_video','song_gen') NOT NULL,
      `track_id` INT(11) UNSIGNED NULL,
      `file_id` INT(11) UNSIGNED NULL,
      `params` TEXT NULL,
      `engine` VARCHAR(40) NULL,
      `status` ENUM('pending','processing','done','failed') DEFAULT 'pending',
      `result_file_id` INT(11) UNSIGNED NULL,
      `result_path` VARCHAR(500) NULL,
      `result_url` VARCHAR(600) NULL,
      `result_data` TEXT NULL,
      `error` VARCHAR(500) NULL,
      `attempts` INT(3) DEFAULT 0,
      `time_add` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `time_done` TIMESTAMP NULL,
      INDEX `idx_status` (`status`),
      INDEX `idx_user` (`user_id`, `type`),
      INDEX `idx_track` (`track_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4" );
    $this->_tables_ready = true;
    return true;
  }

  /* ------------------------------------------------------------------ */
  /* Engine detection                                                    */
  /* ------------------------------------------------------------------ */

  protected function _which( $bin ){
    $bin = basename( (string)$bin );
    if ( !$bin ) return null;
    // dedicated ML venv first (demucs/matchering/whisper live here),
    // then the system PATH
    $venv = "/opt/htx-ai/bin/" . $bin;
    if ( is_file( $venv ) && is_executable( $venv ) ) return $venv;
    $out = @shell_exec( "command -v " . escapeshellarg( $bin ) . " 2>/dev/null" );
    $out = is_string($out) ? trim( $out ) : "";
    return ( $out && is_file( $out ) ) ? $out : null;
  }

  protected function _ffmpeg(){
    $bin = $this->_which( "ffmpeg" );
    return $bin ? $bin : "/usr/bin/ffmpeg";
  }
  protected function _ffprobe(){
    $bin = $this->_which( "ffprobe" );
    return $bin ? $bin : "/usr/bin/ffprobe";
  }

  /** Ordered list of engines that can serve a tool right now. */
  public function engines_for( $tool ){
    $cfg = bof()->hitune_extras->ai_tool_config( $tool === "master" ? "mastering" : ( $tool === "lyrics" ? "lyrics_sync" : $tool ) );
    $provider = !empty($cfg["provider"]) ? $cfg["provider"] : null;
    $list = array();
    switch ( $tool ){
      case "karaoke":
        if ( $provider === "demucs"   && $this->_which("demucs") )   $list[] = "demucs";
        if ( $provider === "spleeter" && $this->_which("spleeter") ) $list[] = "spleeter";
        if ( $this->_which("demucs") )   $list[] = "demucs";
        if ( $this->_which("spleeter") ) $list[] = "spleeter";
        $list[] = "ffmpeg_vocalcut"; // center-channel suppression fallback
        break;
      case "master":
        if ( $provider === "matchering" && $this->_which("matchering") ) $list[] = "matchering";
        if ( !empty($cfg["api_url"]) ) $list[] = "api";
        if ( $this->_which("matchering") ) $list[] = "matchering";
        $list[] = "ffmpeg_loudnorm"; // loudness normalize fallback
        break;
      case "lyrics":
        if ( $this->_which("whisper") ) $list[] = "whisper";
        if ( $this->_which("faster-whisper") ) $list[] = "faster_whisper";
        if ( !empty($cfg["api_url"]) ) $list[] = "api";
        break;
      case "cover_art":
        if ( !empty($cfg["api_key"]) || $provider === "pollinations" ) $list[] = "provider";
        break;
      case "song_gen":
        if ( !empty($cfg["api_key"]) && !empty($cfg["provider"]) ) $list[] = "song_provider";
        break;
      case "clip_video":
        $list[] = "ffmpeg";
        break;
    }
    return array_values( array_unique( $list ) );
  }

  public function tool_ready( $tool ){
    $name = $tool === "master" ? "mastering" : ( $tool === "lyrics" ? "lyrics_sync" : $tool );
    if ( $tool === "clip_video" )
      return !empty( bof()->object->db_setting->get( "ai_tools_enabled" ) ) && count( $this->engines_for("clip_video") ) > 0;
    if ( !bof()->hitune_extras->ai_tool_enabled( $name ) ) return false;
    return count( $this->engines_for( $tool ) ) > 0;
  }

  /** Public status block for the app. Pass $user_id to include per-plan gating. */
  public function tools_status( $user_id=null ){
    $map = array(
      "song_gen"   => array( "label" => "AI Song Generator",        "engine_note" => "Prompt-to-song provider (Suno/Stability/MusicGen)" ),
      "karaoke"    => array( "label" => "Karaoke vocal isolation", "engine_note" => "Demucs/Spleeter, ffmpeg fallback" ),
      "master"     => array( "label" => "AI audio mastering",       "engine_note" => "Matchering, ffmpeg loudnorm fallback" ),
      "lyrics"     => array( "label" => "Smart lyrics sync (LRC)",  "engine_note" => "Whisper / Wav2Vec" ),
      "cover_art"  => array( "label" => "AI cover art generator",   "engine_note" => "SDXL / FLUX / DALL-E provider" ),
      "clip_video" => array( "label" => "Reel clip render",         "engine_note" => "ffmpeg vertical video" ),
    );
    $out = array();
    foreach ( $map as $tool => $m )
      $out[$tool] = array_merge( $m, array(
        "enabled" => $this->tool_ready( $tool ),
        "engines" => $this->engines_for( $tool ),
        "allowed" => $user_id ? $this->plan_allows( $tool, $user_id ) : true,
      ) );
    return $out;
  }

  /* ------------------------------------------------------------------ */
  /* Plan gating (doc §9)                                                */
  /* ------------------------------------------------------------------ */

  /**
   * Which AI features the caller's subscription plan allows.
   * Plans expose checkboxes `feature_ai_*` (object_user_subs_plan) which are
   * serialised into the `features` JSON column and surfaced through
   * client_config->get_plan_features(). A per-plan `ai_quota` integer lives
   * in the plan `data` JSON (0/empty = fall back to global free/pro quota).
   * Fail-open: until the admin assigns any ai_* feature to a plan, every
   * plan is allowed (keeps current behaviour).
   */
  public function plan_features( $user_id ){
    $map  = bof()->client_config->get_plan_features();
    $plan = bof()->client_config->get_user_plan( $user_id );
    $feats = !empty( $map[ $plan["plan"] ] ) ? $map[ $plan["plan"] ] : ( !empty( $map["free"] ) ? $map["free"] : array() );
    if ( array_is_list( $feats ) ){
      $tmp = array(); foreach ( $feats as $f ) $tmp[$f] = 1; $feats = $tmp;
    }
    $plan_row = null;
    if ( !empty( $plan["plan_id"] ) ){
      $plan_row = bof()->db->_select( array(
        "table" => "_u_subs_plans", "columns" => "ID,data",
        "where" => array( array( "ID", "=", (int)$plan["plan_id"] ) ),
        "limit" => 1, "single" => true,
      ) );
    }
    else {
      // non-subscribers sit on the plan marked free=1 — read its ai_quota
      $plan_row = bof()->db->_select( array(
        "table" => "_u_subs_plans", "columns" => "ID,data",
        "where" => array( array( "free", "=", 1 ), array( "active", "=", 1 ) ),
        "order_by" => "priority", "order" => "ASC",
        "limit" => 1, "single" => true,
      ) );
    }
    $data = ( $plan_row && !empty($plan_row["data"]) ) ? json_decode( $plan_row["data"], true ) : array();
    if ( !is_array($data) ) $data = array();
    return array(
      "plan"      => $plan["plan"],
      "is_pro"    => !empty( $plan["is_premium"] ),
      "features"  => is_array($feats) ? $feats : array(),
      "ai_quota"  => !empty( $data["ai_quota"] ) ? (int)$data["ai_quota"] : null,
    );
  }

  /** True if the user's plan allows tool $tool (feature key ai_{tool}). */
  public function plan_allows( $tool, $user_id ){
    $map = bof()->client_config->get_plan_features();
    // Fail-open only while NO plan anywhere defines an ai_* feature.
    $any_configured = false;
    foreach ( $map as $_p => $_f ){
      if ( !is_array($_f) ) continue;
      foreach ( $_f as $k => $v ){
        $_k = is_int($k) ? $v : $k;
        if ( strpos( (string)$_k, "ai_" ) === 0 && ( is_int($k) || !empty($v) ) ){ $any_configured = true; break 2; }
      }
    }
    if ( !$any_configured ) return true; // admin hasn't gated yet — allow

    $pf = $this->plan_features( $user_id );
    $key = "ai_" . $tool;
    // ai_studio master switch inside the plan unlocks every tool
    if ( !empty( $pf["features"]["ai_studio"] ) ) return true;
    return !empty( $pf["features"][ $key ] );
  }

  /** Daily limit for this user: per-plan ai_quota overrides global free/pro. */
  public function quota_limit( $user_id ){
    $pf = $this->plan_features( $user_id );
    if ( $pf["ai_quota"] !== null ) return max( 0, $pf["ai_quota"] );
    return (int) bof()->object->db_setting->get( $pf["is_pro"] ? "ai_quota_pro" : "ai_quota_free" );
  }

  public function quota_left_for( $user_id ){
    $limit = $this->quota_limit( $user_id );
    if ( $limit <= 0 ) return 0;
    $day = date( "Y-m-d" );
    $used = (int) bof()->object->db_setting->get( "ai_quota_used_{$day}_" . (int)$user_id );
    return max( 0, $limit - $used );
  }

  /* ------------------------------------------------------------------ */
  /* Input resolution                                                    */
  /* ------------------------------------------------------------------ */

  /** Absolute path to the audio file backing a catalog track (local files only). */
  public function track_audio_path( $track_id ){
    $db = bof()->db;
    $r = $db->query( "SELECT f.path FROM `_c_m_tracks_sources` s
      JOIN `_bof_files` f ON f.ID = CAST(JSON_UNQUOTE(JSON_EXTRACT(s.data,'$.local_file')) AS UNSIGNED)
      WHERE s.target_id = " . (int)$track_id . " AND s.type='audio'
      ORDER BY s.quality DESC LIMIT 1" );
    if ( $r && $r->num_rows ){
      $p = base_root . "/" . $r->fetch_assoc()["path"];
      if ( is_file($p) ) return $p;
    }
    return null;
  }

  public function file_path( $file_id ){
    $db = bof()->db;
    $r = $db->query( "SELECT path FROM `_bof_files` WHERE ID = " . (int)$file_id . " LIMIT 1" );
    if ( $r && $r->num_rows ){
      $p = base_root . "/" . $r->fetch_assoc()["path"];
      if ( is_file($p) ) return $p;
    }
    return null;
  }

  /** Absolute path of a track's cover image (any size). */
  public function track_cover_path( $track_id ){
    $db = bof()->db;
    $r = $db->query( "SELECT f.path, f.data FROM `_bof_files` f
      JOIN `_c_m_tracks` t ON t.cover_id = f.ID WHERE t.ID = " . (int)$track_id . " LIMIT 1" );
    if ( $r && $r->num_rows ){
      $row = $r->fetch_assoc();
      $d = !empty($row["data"]) ? json_decode($row["data"], true) : null;
      if ( !empty($d["_sisters"]["500"]["path"]) && is_file( base_root . "/" . $d["_sisters"]["500"]["path"] ) )
        return base_root . "/" . $d["_sisters"]["500"]["path"];
      if ( is_file( base_root . "/" . $row["path"] ) ) return base_root . "/" . $row["path"];
    }
    return null;
  }

  /* ------------------------------------------------------------------ */
  /* Jobs                                                                */
  /* ------------------------------------------------------------------ */

  public function quota_left( $user_id, $is_pro=false ){
    return bof()->hitune_extras->ai_quota_left( $user_id, $is_pro );
  }

  public function enqueue( $user_id, $type, $args=array() ){
    if ( !in_array( $type, self::TOOLS, true ) )
      return array( "error" => "unknown_tool" );
    if ( !$this->tool_ready( $type ) )
      return array( "error" => "tool_disabled" );
    if ( !$this->plan_allows( $type, $user_id ) )
      return array( "error" => "plan_locked" );

    if ( $this->quota_left_for( $user_id ) <= 0 )
      return array( "error" => "quota_exceeded" );

    $this->ensure_tables();
    $db = bof()->db;

    $track_id = !empty($args["track_id"]) ? (int)$args["track_id"] : null;
    $file_id  = !empty($args["file_id"])  ? (int)$args["file_id"]  : null;

    if ( $type !== "cover_art" && $type !== "song_gen" && !$track_id && !$file_id )
      return array( "error" => "input_required" );
    if ( $type === "song_gen" && empty( trim( (string)($args["prompt"] ?? "") ) ) )
      return array( "error" => "prompt_required" );

    if ( $track_id ){
      $tr = bof()->object->m_track->select( array( "ID" => $track_id ) );
      if ( !$tr ) return array( "error" => "track_not_found" );
    }

    $params = json_encode( array(
      "prompt"      => $args["prompt"] ?? null,
      "start"       => isset($args["start"]) ? (float)$args["start"] : 0,
      "duration"    => isset($args["duration"]) ? min( 90, max( 5, (float)$args["duration"] ) ) : null,
      "caption"     => $args["caption"] ?? null,
      "style"       => $args["style"] ?? null,
      "track_hash"  => $args["track_hash"] ?? null,
      "reference_track_id" => !empty($args["reference_track_id"]) ? (int)$args["reference_track_id"] : null,
      "reference_file_id"  => !empty($args["reference_file_id"])  ? (int)$args["reference_file_id"]  : null,
      "title"       => $args["title"] ?? null,
      "tags"        => $args["tags"] ?? null,
      "instrumental"=> !empty($args["instrumental"]),
      "seconds"     => isset($args["seconds"]) ? min( 190, max( 5, (int)$args["seconds"] ) ) : null,
    ) );

    $job_id = (int) $db->_insert( array(
      "table" => "_htx_ai_jobs",
      "set"   => array(
        array( "user_id", (int)$user_id ),
        array( "type", $type ),
        $track_id ? array( "track_id", $track_id ) : array( "track_id", "NULL", true ),
        $file_id  ? array( "file_id",  $file_id )  : array( "file_id",  "NULL", true ),
        array( "params", $params ),
      )
    ) );
    if ( !$job_id ) return array( "error" => "enqueue_failed" );

    bof()->hitune_extras->ai_quota_spend( $user_id );

    // ffmpeg-backed jobs run inline; heavy ML engines go to the queue worker.
    $engines = $this->engines_for( $type );
    $first = $engines ? $engines[0] : null;
    if ( in_array( $first, array( "ffmpeg_vocalcut", "ffmpeg_loudnorm", "ffmpeg" ), true ) )
      $this->process( $job_id );

    return $this->job( $job_id, $user_id );
  }

  public function job( $job_id, $user_id=null ){
    $db = bof()->db;
    $where = array( array( "id", "=", (int)$job_id ) );
    if ( $user_id ) $where[] = array( "user_id", "=", (int)$user_id );
    $row = $db->_select( array( "table" => "_htx_ai_jobs", "where" => $where, "limit" => 1, "single" => true ) );
    if ( !$row ) return null;
    return $this->_job_out( $row );
  }

  public function jobs_for( $user_id, $limit=20 ){
    $db = bof()->db;
    $rows = $db->_select( array(
      "table" => "_htx_ai_jobs",
      "where" => array( array( "user_id", "=", (int)$user_id ) ),
      "order_by" => "id",
      "order" => "DESC",
      "limit" => min( 50, max( 1, (int)$limit ) ),
    ) ) ?: array();
    return array_map( array( $this, "_job_out" ), $rows );
  }

  public function stats_for( $user_id ){
    $db = bof()->db;
    $uid = (int)$user_id;
    $out = array(
      "total" => 0, "done" => 0, "failed" => 0, "running" => 0,
      "today" => 0, "by_type" => array(), "by_engine" => array(),
      "days" => array(), "first_at" => null, "last_at" => null,
    );
    $r = $db->query( "SELECT type, engine, status, DATE(`time_add`) d, COUNT(*) c FROM `_htx_ai_jobs` WHERE `user_id` = {$uid} GROUP BY type, engine, status, d" );
    if ( $r ) while ( $row = $r->fetch_assoc() ){
      $out["total"] += (int)$row["c"];
      if ( $row["status"] == "done" )   $out["done"] += (int)$row["c"];
      if ( $row["status"] == "failed" ) $out["failed"] += (int)$row["c"];
      if ( $row["status"] == "pending" || $row["status"] == "processing" ) $out["running"] += (int)$row["c"];
      if ( $row["d"] == date("Y-m-d") ) $out["today"] += (int)$row["c"];
      if ( $row["status"] == "done" ){
        $out["by_type"][$row["type"]] = ( $out["by_type"][$row["type"]] ?? 0 ) + (int)$row["c"];
        if ( $row["engine"] ) $out["by_engine"][$row["engine"]] = ( $out["by_engine"][$row["engine"]] ?? 0 ) + (int)$row["c"];
      }
    }
    $r = $db->query( "SELECT DATE(`time_add`) d, COUNT(*) c FROM `_htx_ai_jobs` WHERE `user_id` = {$uid} AND `time_add` >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY d ORDER BY d" );
    if ( $r ) while ( $row = $r->fetch_assoc() ) $out["days"][$row["d"]] = (int)$row["c"];
    $r = $db->query( "SELECT MIN(`time_add`) mn, MAX(`time_add`) mx FROM `_htx_ai_jobs` WHERE `user_id` = {$uid}" );
    if ( $r && ( $row = $r->fetch_assoc() ) ){ $out["first_at"] = $row["mn"]; $out["last_at"] = $row["mx"]; }
    $out["quota_left"] = $this->quota_left_for( $user_id );
    $out["quota_limit"] = $this->quota_limit( $user_id );
    return $out;
  }

  protected function _job_out( $row ){
    $params = !empty($row["params"]) ? json_decode($row["params"], true) : array();
    return array(
      "id"         => (int)$row["id"],
      "type"       => $row["type"],
      "status"     => $row["status"],
      "engine"     => $row["engine"],
      "track_id"   => !empty($row["track_id"]) ? (int)$row["track_id"] : null,
      "file_id"    => !empty($row["result_file_id"]) ? (int)$row["result_file_id"] : null,
      "result_url" => $row["result_url"],
      "result"     => !empty($row["result_data"]) ? json_decode($row["result_data"], true) : null,
      "error"      => $row["error"],
      "params"     => $params,
      "time_add"   => $row["time_add"],
    );
  }

  protected function _set( $job_id, $fields ){
    $db = bof()->db;
    $set = array();
    foreach ( $fields as $k => $v )
      $set[] = ( $v === null ) ? array( $k, "NULL", true ) : array( $k, $v );
    $db->_update( array( "table" => "_htx_ai_jobs", "set" => $set, "where" => array( array( "id", "=", (int)$job_id ) ) ) );
  }

  /* ------------------------------------------------------------------ */
  /* Processing                                                          */
  /* ------------------------------------------------------------------ */

  public function process( $job_id ){
    $this->ensure_tables();
    $db = bof()->db;
    $row = $db->_select( array( "table" => "_htx_ai_jobs", "where" => array( array( "id", "=", (int)$job_id ) ), "limit" => 1, "single" => true ) );
    if ( !$row || in_array( $row["status"], array( "done", "processing" ), true ) ) return false;

    $params = !empty($row["params"]) ? json_decode($row["params"], true) : array();
    $type = $row["type"];
    $engines = $this->engines_for( $type );
    if ( !$engines ){ $this->_set( $job_id, array( "status" => "failed", "error" => "no engine available" ) ); return false; }

    $this->_set( $job_id, array( "status" => "processing", "attempts" => (int)$row["attempts"] + 1, "time_done" => date("Y-m-d H:i:s") ) );

    $out_rel = "files/ai/" . date( "Y/m" ) . "/";
    $out_dir = base_root . "/" . $out_rel;
    if ( !is_dir( $out_dir ) ) @mkdir( $out_dir, 0755, true );

    $in = null; $tmp_in = null;
    if ( $type !== "cover_art" && $type !== "song_gen" ){
      $in = $row["track_id"] ? $this->track_audio_path( (int)$row["track_id"] ) : null;
      if ( !$in && $row["file_id"] ) $in = $this->file_path( (int)$row["file_id"] );
      if ( !$in && $row["track_id"] ){
        // remote/external source (youtube/saavn/mixcloud) — resolve to a direct
        // URL and pull a temp copy so ffmpeg-backed tools can work on it
        $tmp_in = $this->_fetch_remote_audio( (int)$row["track_id"], $out_dir );
        $in = $tmp_in;
      }
      if ( !$in ){ $this->_set( $job_id, array( "status" => "failed", "error" => "input file missing (remote/DRM sources unsupported)" ) ); return false; }
    }

    $err = null; $result = null; $tried = array();
    foreach ( $engines as $engine ){
      $result = $this->_run( $engine, $type, $in, $out_dir, $out_rel, $params, $row, $err );
      if ( $result ){ $this->_set( $job_id, array( "engine" => $engine ) ); break; }
      // keep a short tail of each failed engine for debugging
      $tried[] = $engine . ": " . substr( (string)$err, -120 );
    }
    if ( $tried ) @file_put_contents( base_root . "/files/ai/engine_errors.log",
      date("Y-m-d H:i:s") . " job#{$job_id} {$type} :: " . implode( " || ", $tried ) . "\n", FILE_APPEND );

    if ( !$result ){
      if ( $tmp_in ) @unlink( $tmp_in );
      $this->_set( $job_id, array( "status" => "failed", "error" => $this->_clean_err( $err ) ) );
      return false;
    }
    if ( $tmp_in ) @unlink( $tmp_in );

    $file_id = null;
    if ( !empty($result["path"]) ){
      $file_id = $this->_register_file( $result["path"], (int)$row["user_id"], "htx_ai_{$type}" );
    }
    $this->_set( $job_id, array(
      "status"         => "done",
      "result_file_id" => $file_id,
      "result_path"    => $result["path"] ?? null,
      "result_url"     => !empty($result["path"]) ? web_address . ltrim( $result["path"], "/" ) : null,
      "result_data"    => !empty($result["data"]) ? json_encode( $result["data"] ) : null,
      "error"          => null,
      "time_done"      => date( "Y-m-d H:i:s" ),
    ) );
    return true;
  }

  /** Cron: process up to $limit pending jobs. */
  public function process_queue( $limit=5 ){
    $this->ensure_tables();
    $db = bof()->db;
    // recover jobs stuck in "processing" because a worker was killed mid-run:
    // re-queue after 30 min, or fail once attempts are exhausted
    $db->_update( array(
      "table" => "_htx_ai_jobs",
      "set"   => array( array( "status", "pending" ) ),
      "where" => array(
        array( "status", "=", "processing" ),
        array( "attempts", "<", 3 ),
        array( "time_done", "<", date( "Y-m-d H:i:s", time() - 1800 ) ),
      ),
    ) );
    $db->_update( array(
      "table" => "_htx_ai_jobs",
      "set"   => array( array( "status", "failed" ), array( "error", "worker killed / timed out" ) ),
      "where" => array(
        array( "status", "=", "processing" ),
        array( "attempts", ">=", 3 ),
      ),
    ) );
    // stale pending jobs — never picked up for 24h+ (worker was down): fail once
    $db->_update( array(
      "table" => "_htx_ai_jobs",
      "set"   => array( array( "status", "failed" ), array( "error", "job expired (24h in queue)" ) ),
      "where" => array(
        array( "status", "=", "pending" ),
        array( "time_add", "<", date( "Y-m-d H:i:s", time() - 86400 ) ),
      ),
    ) );
    $rows = $db->_select( array(
      "table" => "_htx_ai_jobs",
      "where" => array( array( "status", "=", "pending" ), array( "attempts", "<", 3 ) ),
      "order_by" => "id",
      "order" => "ASC",
      "limit" => max( 1, (int)$limit ),
    ) ) ?: array();
    $done = 0;
    foreach ( $rows as $r ){ $this->process( (int)$r["id"] ); $done++; }
    return $done;
  }

  /** Resolve a remote/external track source to a local temp file for ffmpeg tools. */
  protected function _fetch_remote_audio( $track_id, $out_dir ){

    $db = bof()->db;
    $r = $db->query( "SELECT t.title, t.duration, t.youtube_id, a.name AS artist
      FROM `_c_m_tracks` t LEFT JOIN `_c_m_artists` a ON a.ID = t.artist_id
      WHERE t.ID = " . (int)$track_id . " LIMIT 1" );
    $track = ( $r && $r->num_rows ) ? $r->fetch_assoc() : null;
    if ( !$track ) return null;

    $url = null;
    // 1) saavn resolver (reliable, no expiry)
    try {
      $sv = bof()->hitune_saavn->resolve( $track["title"], $track["artist"], $track["duration"] );
      if ( $sv && !empty( $sv["url"] ) ) $url = $sv["url"];
    } catch ( \Throwable $e ) {}
    // 2) direct remote_address stored on the source row
    if ( !$url ){
      $r2 = $db->query( "SELECT data FROM `_c_m_tracks_sources` WHERE target_id=" . (int)$track_id . " AND type='audio' LIMIT 5" );
      while ( $r2 && $row2 = $r2->fetch_assoc() ){
        $d = json_decode( (string)$row2["data"], true );
        if ( !empty( $d["remote_address"] ) && preg_match( "/^https?:\/\//i", $d["remote_address"] ) ){ $url = $d["remote_address"]; break; }
      }
    }
    // 3) youtube piped cache
    if ( !$url && !empty( $track["youtube_id"] ) ){
      try {
        $st = bof()->youtube_piped->get_stream_cached( $track["youtube_id"] );
        if ( !empty( $st["url"] ) ) $url = $st["url"];
      } catch ( \Throwable $e ) {}
    }
    if ( !$url ) return null;

    if ( !is_dir( $out_dir ) ) @mkdir( $out_dir, 0755, true );
    $tmp = $out_dir . "tmp_in_" . uniqid() . ".audio";
    $dl = $this->_exec( escapeshellarg( $this->_which("curl") ?: "curl" ) . " -sL --max-time 120 -o " . escapeshellarg($tmp) . " " . escapeshellarg($url), $cerr, 150 );
    if ( !is_file($tmp) || filesize($tmp) < 32768 ){ @unlink($tmp); return null; }
    return $tmp;

  }

  /** Reduce captured stderr to the last meaningful line (drops ffmpeg banners). */
  protected function _clean_err( $err ){
    $err = trim( (string)$err );
    if ( $err === "" ) return "processing failed";
    $lines = array_values( array_filter( array_map( "trim", preg_split( "/\r?\n/", $err ) ) ) );
    // prefer the last line that looks like a real error, not config chatter
    for ( $i = count($lines)-1; $i >= 0; $i-- ){
      $l = $lines[$i];
      if ( preg_match( "/(denied|error|failed|invalid|not found|no such|unsupported|missing|cannot|unable|exception)/i", $l ) )
        return substr( $l, 0, 240 );
    }
    return substr( end($lines), 0, 240 );
  }

  /* ------------------------------------------------------------------ */
  /* Engines                                                             */
  /* ------------------------------------------------------------------ */

  protected function _run( $engine, $type, $in, $out_dir, $out_rel, $params, $job, &$err ){
    $err = null;
    try {
      switch ( $engine ){
        case "ffmpeg_vocalcut": return $this->_e_ffmpeg_vocalcut( $in, $out_dir, $out_rel, $err );
        case "ffmpeg_loudnorm": return $this->_e_ffmpeg_loudnorm( $in, $out_dir, $out_rel, $err );
        case "ffmpeg":          return $this->_e_ffmpeg_clip( $in, $out_dir, $out_rel, $params, $job, $err );
        case "demucs":          return $this->_e_demucs( $in, $out_dir, $out_rel, $err );
        case "spleeter":        return $this->_e_spleeter( $in, $out_dir, $out_rel, $err );
        case "matchering":      return $this->_e_matchering( $in, $out_dir, $out_rel, $params, $row, $err );
        case "whisper":         return $this->_e_whisper( $in, $out_dir, $out_rel, "whisper", $err );
        case "faster_whisper":  return $this->_e_whisper( $in, $out_dir, $out_rel, "faster-whisper", $err );
        case "api":             return $this->_e_remote_api( $type, $in, $out_dir, $out_rel, $params, $err );
        case "provider":        return $this->_e_cover_provider( $out_dir, $out_rel, $params, $err );
        case "song_provider":   return $this->_e_song_provider( $out_dir, $out_rel, $params, $err );
      }
    } catch ( \Throwable $e ){ $err = $e->getMessage(); }
    return null;
  }

  protected function _exec( $cmd, &$stderr, $timeout=600 ){
    $stderr = "";
    $desc = array( 1 => array("pipe","w"), 2 => array("pipe","w") );
    // model caches must live somewhere the www user can write
    $env = array(
      "TORCH_HOME" => "/opt/htx-ai/torch",
      "HF_HOME"    => "/opt/htx-ai/hf",
      "XDG_CACHE_HOME" => "/opt/htx-ai",
      "HOME"       => "/opt/htx-ai",
      "PATH"       => "/opt/htx-ai/bin:/usr/local/bin:/usr/bin:/bin",
    );
    $p = proc_open( $cmd . " 2>&1", $desc, $pipes, null, $env );
    if ( !is_resource($p) ){ $stderr = "proc_open failed"; return -1; }
    stream_set_blocking( $pipes[1], false );
    $start = time(); $buf = ""; $code = null;
    while ( true ){
      $st = proc_get_status( $p );
      $buf .= stream_get_contents( $pipes[1] );
      if ( !$st["running"] ){ $code = $st["exitcode"]; break; }
      if ( time() - $start > $timeout ){ proc_terminate( $p ); $stderr = "timeout"; return -1; }
      usleep( 100000 );
    }
    fclose( $pipes[1] ); fclose( $pipes[2] );
    // proc_close() returns -1 after proc_get_status() consumed the exit
    // code on some PHP builds — trust exitcode captured above instead.
    $closed = proc_close( $p );
    if ( $code === null || $code === -1 ) $code = ( $closed === -1 ) ? 0 : $closed;
    $stderr = trim( (string)$buf );
    return $code;
  }

  // Karaoke fallback: remove the center channel (vocals live in the middle).
  protected function _e_ffmpeg_vocalcut( $in, $out_dir, $out_rel, &$err ){
    $name = uniqid( "karaoke_" ) . ".mp3";
    $out = $out_dir . $name;
    $cmd = escapeshellarg( $this->_ffmpeg() ) . " -y -i " . escapeshellarg($in) .
      " -af \"pan=stereo|c0=c0-c1|c1=c1-c0\" -codec:a libmp3lame -q:a 4 " . escapeshellarg($out);
    $code = $this->_exec( $cmd, $err, 300 );
    if ( !is_file($out) || !filesize($out) ){ $err = substr( $err ?: "ffmpeg vocal-cut failed", -400 ); return null; }
    return array( "path" => $out_rel . $name, "data" => array( "engine" => "ffmpeg_vocalcut" ) );
  }

  // Mastering fallback: EBU R128 two-pass-equivalent single-pass loudnorm.
  protected function _e_ffmpeg_loudnorm( $in, $out_dir, $out_rel, &$err ){
    $name = uniqid( "master_" ) . ".mp3";
    $out = $out_dir . $name;
    $cmd = escapeshellarg( $this->_ffmpeg() ) . " -y -i " . escapeshellarg($in) .
      " -af \"loudnorm=I=-14:TP=-1.0:LRA=11\" -codec:a libmp3lame -q:a 2 " . escapeshellarg($out);
    $code = $this->_exec( $cmd, $err, 300 );
    if ( !is_file($out) || !filesize($out) ){ $err = substr( $err ?: "ffmpeg loudnorm failed", -400 ); return null; }
    return array( "path" => $out_rel . $name, "data" => array( "engine" => "ffmpeg_loudnorm", "target" => "EBU R128 -14 LUFS" ) );
  }

  // Reel clip: cover art + audio extract -> vertical 1080x1920 mp4.
  protected function _e_ffmpeg_clip( $in, $out_dir, $out_rel, $params, $job, &$err ){
    $start = isset($params["start"]) ? max( 0, (float)$params["start"] ) : 0;
    $dur   = isset($params["duration"]) ? min( 90, max( 5, (float)$params["duration"] ) ) : 30;
    $cover = $job["track_id"] ? $this->track_cover_path( (int)$job["track_id"] ) : null;

    $name = uniqid( "clip_" ) . ".mp4";
    $out  = $out_dir . $name;

    if ( $cover && is_file($cover) ){
      $vf = "scale=1080:1920:force_original_aspect_ratio=decrease,pad=1080:1920:(ow-iw)/2:(oh-ih)/2:color=black,format=yuv420p";
      $cmd = escapeshellarg( $this->_ffmpeg() ) . " -y -loop 1 -i " . escapeshellarg($cover) .
        " -ss {$start} -t {$dur} -i " . escapeshellarg($in) .
        " -c:v libx264 -preset veryfast -pix_fmt yuv420p -vf \"{$vf}\" -c:a aac -b:a 160k -shortest -movflags +faststart " . escapeshellarg($out);
    } else {
      // waveform visual fallback when the track has no cover:
      // waveform overlay on a black 1080x1920 canvas
      $fc = "[0:a]showwaves=s=1080x400:mode=line:colors=0x00b7ff[w];color=c=black:s=1080x1920:d={$dur}:r=30[bg];[bg][w]overlay=(W-w)/2:(H-h)/2,format=yuv420p[v]";
      $cmd = escapeshellarg( $this->_ffmpeg() ) . " -y -ss {$start} -t {$dur} -i " . escapeshellarg($in) .
        " -filter_complex " . escapeshellarg($fc) . " -map \"[v]\" -map 0:a -c:v libx264 -preset veryfast -pix_fmt yuv420p -c:a aac -b:a 160k -movflags +faststart -shortest " . escapeshellarg($out);
    }
    $code = $this->_exec( $cmd, $err, 240 );
    if ( !is_file($out) || !filesize($out) ){ $err = substr( $err ?: "clip render failed", -400 ); return null; }
    return array( "path" => $out_rel . $name, "data" => array( "engine" => "ffmpeg", "duration" => $dur, "start" => $start ) );
  }

  protected function _e_demucs( $in, $out_dir, $out_rel, &$err ){
    $stem_dir = $out_dir . uniqid( "dmx_" );
    @mkdir( $stem_dir, 0755, true );
    $cmd = escapeshellarg( $this->_which("demucs") ) . " --two-stems=vocals -o " . escapeshellarg($stem_dir) . " " . escapeshellarg($in);
    $code = $this->_exec( $cmd, $err, 1200 );
    // demucs writes <out>/<model>/<track>/no_vocals.wav
    $found = null;
    $it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $stem_dir ) );
    foreach ( $it as $f ){ if ( $f->isFile() && $f->getFilename() === "no_vocals.wav" ){ $found = $f->getPathname(); break; } }
    if ( $code !== 0 || !$found ){ $err = $err ?: "demucs produced no no_vocals stem"; return null; }
    $name = uniqid( "karaoke_" ) . ".mp3";
    $out = $out_dir . $name;
    $code = $this->_exec( escapeshellarg( $this->_ffmpeg() ) . " -y -i " . escapeshellarg($found) . " -codec:a libmp3lame -q:a 2 " . escapeshellarg($out), $err, 300 );
    $this->_rrmdir( $stem_dir );
    if ( !is_file($out) ){ $err = substr( $err ?: "stem encode failed", -400 ); return null; }
    return array( "path" => $out_rel . $name, "data" => array( "engine" => "demucs" ) );
  }

  protected function _e_spleeter( $in, $out_dir, $out_rel, &$err ){
    $stem_dir = $out_dir . uniqid( "spl_" );
    @mkdir( $stem_dir, 0755, true );
    $cmd = escapeshellarg( $this->_which("spleeter") ) . " separate -p spleeter:2stems -o " . escapeshellarg($stem_dir) . " " . escapeshellarg($in);
    $code = $this->_exec( $cmd, $err, 1200 );
    $found = null;
    $it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $stem_dir ) );
    foreach ( $it as $f ){ if ( $f->isFile() && $f->getFilename() === "accompaniment.wav" ){ $found = $f->getPathname(); break; } }
    if ( $code !== 0 || !$found ){ $err = $err ?: "spleeter produced no accompaniment stem"; return null; }
    $name = uniqid( "karaoke_" ) . ".mp3";
    $out = $out_dir . $name;
    $code = $this->_exec( escapeshellarg( $this->_ffmpeg() ) . " -y -i " . escapeshellarg($found) . " -codec:a libmp3lame -q:a 2 " . escapeshellarg($out), $err, 300 );
    $this->_rrmdir( $stem_dir );
    if ( !is_file($out) ){ $err = substr( $err ?: "stem encode failed", -400 ); return null; }
    return array( "path" => $out_rel . $name, "data" => array( "engine" => "spleeter" ) );
  }

  // Matchering is reference-based mastering: it needs a reference track to
  // match loudness/EQ/dynamics against. Reference resolution order:
  //   params.reference_track_id > params.reference_file_id > ai_master_reference (file path)
  // With no reference the engine reports failure so the loudnorm fallback runs.
  protected function _e_matchering( $in, $out_dir, $out_rel, $params, $job, &$err ){
    $ref = null;
    if ( !empty($params["reference_track_id"]) ) $ref = $this->track_audio_path( (int)$params["reference_track_id"] );
    if ( !$ref && !empty($params["reference_file_id"]) ) $ref = $this->file_path( (int)$params["reference_file_id"] );
    if ( !$ref ){
      $rp = trim( (string)bof()->object->db_setting->get( "ai_master_reference" ) );
      if ( $rp !== "" ){
        $candidate = ( $rp[0] === "/" ) ? $rp : base_root . "/" . ltrim( $rp, "/" );
        if ( is_file( $candidate ) ) $ref = $candidate;
      }
    }
    if ( !$ref ){ $err = "matchering needs a reference track (params.reference_track_id / ai_master_reference)"; return null; }

    $name = uniqid( "master_" ) . ".wav";
    $out = $out_dir . $name;
    $cmd = escapeshellarg( $this->_which("matchering") ) . " " . escapeshellarg($in) . " " . escapeshellarg($out) . " " . escapeshellarg($ref);
    $code = $this->_exec( $cmd, $err, 900 );
    if ( $code !== 0 || !is_file($out) ){ $err = $err ?: "matchering failed"; return null; }
    return array( "path" => $out_rel . $name, "data" => array( "engine" => "matchering" ) );
  }

  protected function _e_whisper( $in, $out_dir, $out_rel, $bin, &$err ){
    $wdir = $out_dir . uniqid( "wsp_" );
    @mkdir( $wdir, 0755, true );
    $model = trim( (string)bof()->object->db_setting->get( "ai_whisper_model" ) ) ?: "base";
    $cmd = escapeshellarg( $this->_which($bin) ) . " " . escapeshellarg($in) . " --output_dir " . escapeshellarg($wdir) . " --output_format srt --model " . escapeshellarg($model);
    $code = $this->_exec( $cmd, $err, 1800 );
    $srt = null;
    foreach ( glob( $wdir . "/*.srt" ) ?: array() as $f ) $srt = $f;
    if ( $code !== 0 || !$srt ){ $err = $err ?: "whisper produced no srt"; return null; }
    $lrc = $this->_srt_to_lrc( file_get_contents( $srt ) );
    $this->_rrmdir( $wdir );
    if ( !$lrc ){ $err = "srt->lrc conversion empty"; return null; }
    $name = uniqid( "lyrics_" ) . ".lrc";
    file_put_contents( $out_dir . $name, $lrc );
    return array( "path" => $out_rel . $name, "data" => array( "engine" => $bin, "format" => "lrc", "lines" => substr_count( $lrc, "\n" ) ) );
  }

  protected function _srt_to_lrc( $srt ){
    $out = "";
    foreach ( preg_split( "/\r?\n\r?\n/", trim( (string)$srt ) ) as $block ){
      if ( !preg_match( "/(\d{2}):(\d{2}):(\d{2})[,\.](\d{3})\s*-->\s*(\d{2}):(\d{2}):(\d{2})[,\.](\d{3})/", $block, $m ) ) continue;
      $lines = preg_split( "/\r?\n/", $block );
      $lines = array_filter( $lines, function( $l ){
        $l = trim( $l );
        return $l !== "" && strpos( $l, "-->" ) === false && !preg_match( "/^\d+$/", $l );
      } );
      $text = trim( preg_replace( "/<[^>]+>/", "", implode( " ", $lines ) ) );
      if ( $text === "" ) continue;
      $t = (int)$m[1]*60 + (int)$m[2] + ((int)$m[3] + (int)$m[4]/1000 ) / 60;
      $min = floor( (int)$m[1]*60 + (int)$m[2] );
      $sec = (float)$m[3] + (float)$m[4]/1000;
      $out .= sprintf( "[%02d:%05.2f]%s\n", $min, $sec, $text );
    }
    return $out ?: null;
  }

  // Generic configured remote mastering/lyrics endpoint: POSTs the file, expects binary or JSON {url|lrc}.
  protected function _e_remote_api( $type, $in, $out_dir, $out_rel, $params, &$err ){
    $cfg = bof()->hitune_extras->ai_tool_config( $type === "master" ? "mastering" : "lyrics_sync" );
    if ( empty($cfg["api_url"]) ){ $err = "api_url not configured"; return null; }
    $ch = curl_init( $cfg["api_url"] );
    $post = array( "audio" => new CURLFile( $in ), "type" => $type );
    curl_setopt_array( $ch, array(
      CURLOPT_POST => true, CURLOPT_POSTFIELDS => $post, CURLOPT_RETURNTRANSFER => true,
      CURLOPT_TIMEOUT => 600, CURLOPT_HTTPHEADER => array( "Accept: application/json, application/octet-stream" ),
    ) );
    $body = curl_exec( $ch ); $code = (int)curl_getinfo( $ch, CURLINFO_RESPONSE_CODE ); $ct = (string)curl_getinfo( $ch, CURLINFO_CONTENT_TYPE ); curl_close( $ch );
    if ( !$body || $code >= 400 ){ $err = "remote api http {$code}"; return null; }
    if ( strpos( $ct, "json" ) !== false ){
      $j = json_decode( $body, true );
      if ( !empty($j["lrc"]) ){
        $name = uniqid( "lyrics_" ) . ".lrc";
        file_put_contents( $out_dir . $name, $j["lrc"] );
        return array( "path" => $out_rel . $name, "data" => array( "engine" => "api", "format" => "lrc" ) );
      }
      if ( !empty($j["url"]) ) return array( "path" => null, "data" => array( "engine" => "api", "remote_url" => $j["url"] ) );
      $err = "remote api bad json"; return null;
    }
    $ext = ( $type === "lyrics" ) ? ".lrc" : ".wav";
    $name = uniqid( $type . "_" ) . $ext;
    file_put_contents( $out_dir . $name, $body );
    return array( "path" => $out_rel . $name, "data" => array( "engine" => "api" ) );
  }

  // Cover art: OpenAI-compatible images endpoint ({api_url or default}/v1/images/generations),
  // or free pollinations.ai GET endpoint when provider="pollinations" (no key needed).
  protected function _e_cover_provider( $out_dir, $out_rel, $params, &$err ){
    $cfg = bof()->hitune_extras->ai_tool_config( "cover_art" );
    $prompt = !empty($params["prompt"]) ? $params["prompt"] : "Album cover art, abstract music visual, high detail";

    if ( ( $cfg["provider"] ?? "" ) === "pollinations" ){
      $url = "https://image.pollinations.ai/prompt/" . rawurlencode( $prompt ) . "?width=1024&height=1024&nologo=true&enhance=true";
      $img = @file_get_contents( $url );
      if ( !$img || strlen( $img ) < 10000 ){ $err = "pollinations empty image"; return null; }
      $name = uniqid( "cover_" ) . ".jpg";
      file_put_contents( $out_dir . $name, $img );
      return array( "path" => $out_rel . $name, "data" => array( "engine" => "provider", "model" => "pollinations" ) );
    }

    if ( empty($cfg["api_key"]) ){ $err = "cover art api_key not configured"; return null; }
    $base = !empty($cfg["api_url"]) ? rtrim($cfg["api_url"],"/") : "https://api.openai.com/v1";
    $url = ( strpos( $base, "/images/" ) !== false ) ? $base : $base . "/images/generations";
    $model_map = array( "dalle3" => "dall-e-3", "sdxl" => "sdxl", "flux" => "flux.1-pro", "midjourney" => "midjourney" );
    $model = $model_map[ $cfg["provider"] ?? "" ] ?? ( $cfg["provider"] ?: "dall-e-3" );
    $prompt = !empty($params["prompt"]) ? $params["prompt"] : "Album cover art, abstract music visual, high detail";

    $ch = curl_init( $url );
    curl_setopt_array( $ch, array(
      CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 180,
      CURLOPT_HTTPHEADER => array( "Content-Type: application/json", "Authorization: Bearer " . $cfg["api_key"] ),
      CURLOPT_POSTFIELDS => json_encode( array( "model" => $model, "prompt" => $prompt, "n" => 1, "size" => "1024x1024" ) ),
    ) );
    $body = curl_exec( $ch ); $code = (int)curl_getinfo( $ch, CURLINFO_RESPONSE_CODE ); curl_close( $ch );
    if ( !$body ){ $err = "cover provider unreachable"; return null; }
    $j = json_decode( $body, true );
    if ( $code >= 400 || empty($j["data"][0]) ){ $err = "cover provider http {$code}: " . substr( (string)($j["error"]["message"] ?? $body), 0, 200 ); return null; }

    $img = null;
    if ( !empty($j["data"][0]["b64_json"]) ) $img = base64_decode( $j["data"][0]["b64_json"] );
    elseif ( !empty($j["data"][0]["url"]) ) $img = @file_get_contents( $j["data"][0]["url"] );
    if ( !$img ){ $err = "cover provider empty image"; return null; }

    $name = uniqid( "cover_" ) . ".jpg";
    file_put_contents( $out_dir . $name, $img );
    return array( "path" => $out_rel . $name, "data" => array( "engine" => "provider", "model" => $model ) );
  }

  /**
   * AI song generation (prompt -> full audio file). Providers, picked via the
   * admin setting `ai_song_gen_provider` (stored under ai_song_* keys):
   *
   *   stability  -> POST https://api.stability.ai/v2beta/audio/stable-audio-2/text-to-audio
   *                 multipart form (prompt, seconds->duration, output_format=mp3),
   *                 Bearer key, returns audio bytes directly. Stable Audio is
   *                 instrumentals/SFX — vocals need the suno path.
   *   replicate  -> POST https://api.replicate.com/v1/predictions
   *                 {version: ai_song_model, input:{prompt,duration,...}},
   *                 polls the prediction until succeeded, downloads output.
   *   suno       -> Suno-compatible APIs (sunoapi.org-style):
   *                 POST {ai_song_api_url}/generate {prompt,tags,make_instrumental,title}
   *                 then GET {api_url}/get?ids=<id> until audio_url appears.
   *                 Also accepts a synchronous {audio_url|data[].audio_url} reply.
   *
   * Params: prompt (required), tags/genre, title, instrumental, seconds.
   */
  protected function _e_song_provider( $out_dir, $out_rel, $params, &$err ){
    $s        = bof()->object->db_setting;
    $provider = trim( (string)$s->get( "ai_song_gen_provider" ) ) ?: "stability";
    $key      = trim( (string)$s->get( "ai_song_gen_api_key" ) );
    if ( !$key ){ $err = "ai_song_gen_api_key not configured"; return null; }

    $prompt = trim( (string)( $params["prompt"] ?? "" ) );
    $tags   = trim( (string)( $params["tags"] ?? "" ) );
    $title  = trim( (string)( $params["title"] ?? "" ) ) ?: "AI Track";
    $secs   = !empty($params["seconds"]) ? min( 190, max( 5, (int)$params["seconds"] ) ) : 30;
    $instrumental = !empty( $params["instrumental"] );
    if ( $tags ) $prompt = trim( $prompt . " — " . $tags );

    if ( $provider === "stability" ){
      $url = "https://api.stability.ai/v2beta/audio/stable-audio-2/text-to-audio";
      $ch = curl_init( $url );
      curl_setopt_array( $ch, array(
        CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 300,
        CURLOPT_HTTPHEADER => array( "Authorization: Bearer " . $key, "Accept: audio/*" ),
        CURLOPT_POSTFIELDS => array(
          "prompt" => $prompt,
          "duration" => min( 190, $secs ),
          "output_format" => "mp3",
        ),
      ) );
      $body = curl_exec( $ch ); $code = (int)curl_getinfo( $ch, CURLINFO_RESPONSE_CODE ); curl_close( $ch );
      if ( !$body || $code >= 400 ){ $err = "stability http {$code}: " . substr( (string)$body, 0, 200 ); return null; }
      $name = uniqid( "song_" ) . ".mp3";
      file_put_contents( $out_dir . $name, $body );
      return array( "path" => $out_rel . $name, "data" => array( "engine" => "stability", "title" => $title, "seconds" => $secs ) );
    }

    if ( $provider === "replicate" ){
      $model = trim( (string)$s->get( "ai_song_model" ) );
      if ( !$model ){ $err = "ai_song_model (replicate version) not configured"; return null; }
      $input = array( "prompt" => $prompt );
      if ( $secs ) $input["duration"] = $secs;
      $ch = curl_init( "https://api.replicate.com/v1/predictions" );
      curl_setopt_array( $ch, array(
        CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 90,
        CURLOPT_HTTPHEADER => array(
          "Authorization: Bearer " . $key,
          "Content-Type: application/json",
          "Prefer: wait=55",
        ),
        CURLOPT_POSTFIELDS => json_encode( array( "version" => $model, "input" => $input ) ),
      ) );
      $body = curl_exec( $ch ); $code = (int)curl_getinfo( $ch, CURLINFO_RESPONSE_CODE ); curl_close( $ch );
      $j = json_decode( (string)$body, true );
      if ( $code >= 400 || empty($j) ){ $err = "replicate http {$code}: " . substr( (string)$body, 0, 200 ); return null; }

      // poll until done (max ~5 min)
      $get_url = !empty($j["urls"]["get"]) ? $j["urls"]["get"] : null;
      $deadline = time() + 300;
      while ( $get_url && time() < $deadline ){
        if ( in_array( $j["status"] ?? "", array( "succeeded", "failed", "canceled" ), true ) ) break;
        sleep( 5 );
        $ch = curl_init( $get_url );
        curl_setopt_array( $ch, array(
          CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTPHEADER => array( "Authorization: Bearer " . $key ),
        ) );
        $j = json_decode( (string)curl_exec( $ch ), true ) ?: $j; curl_close( $ch );
      }
      if ( ( $j["status"] ?? "" ) !== "succeeded" ){ $err = "replicate status: " . ( $j["status"] ?? "unknown" ); return null; }
      $out_url = is_array( $j["output"] ?? null ) ? end( $j["output"] ) : ( $j["output"] ?? null );
      if ( !$out_url ){ $err = "replicate empty output"; return null; }
      return $this->_download_song( $out_url, $out_dir, $out_rel, $title, "replicate", $err );
    }

    // suno-compatible custom provider
    $base = rtrim( (string)$s->get( "ai_song_api_url" ), "/" );
    if ( !$base ){ $err = "ai_song_api_url not configured"; return null; }
    $ch = curl_init( $base . "/generate" );
    curl_setopt_array( $ch, array(
      CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 120,
      CURLOPT_HTTPHEADER => array( "Content-Type: application/json", "Authorization: Bearer " . $key ),
      CURLOPT_POSTFIELDS => json_encode( array(
        "prompt" => $prompt, "tags" => $tags, "title" => $title,
        "make_instrumental" => $instrumental,
      ) ),
    ) );
    $body = curl_exec( $ch ); $code = (int)curl_getinfo( $ch, CURLINFO_RESPONSE_CODE ); curl_close( $ch );
    $j = json_decode( (string)$body, true );
    if ( $code >= 400 ){ $err = "song api http {$code}: " . substr( (string)$body, 0, 200 ); return null; }

    $audio_url = null; $task = null;
    if ( !empty($j["audio_url"]) ) $audio_url = $j["audio_url"];
    if ( !$audio_url && !empty($j["data"][0]["audio_url"]) ) $audio_url = $j["data"][0]["audio_url"];
    if ( !$audio_url ) $task = $j["task_id"] ?? $j["id"] ?? ( $j["data"][0]["id"] ?? null );

    $deadline = time() + 300;
    while ( !$audio_url && $task && time() < $deadline ){
      sleep( 8 );
      $ch = curl_init( $base . "/get?ids=" . urlencode( $task ) );
      curl_setopt_array( $ch, array(
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => array( "Authorization: Bearer " . $key ),
      ) );
      $p = json_decode( (string)curl_exec( $ch ), true ); curl_close( $ch );
      if ( !empty($p["audio_url"]) ) $audio_url = $p["audio_url"];
      elseif ( !empty($p["data"][0]["audio_url"]) ) $audio_url = $p["data"][0]["audio_url"];
      elseif ( !empty($p[0]["audio_url"]) ) $audio_url = $p[0]["audio_url"];
    }
    if ( !$audio_url ){ $err = "song provider no audio_url (task=" . (string)$task . ")"; return null; }
    return $this->_download_song( $audio_url, $out_dir, $out_rel, $title, "suno_api", $err );
  }

  protected function _download_song( $url, $out_dir, $out_rel, $title, $engine, &$err ){
    $host = parse_url( $url, PHP_URL_HOST );
    if ( !$host || !preg_match( "/^https?:/i", $url ) ){ $err = "bad audio url"; return null; }
    $ch = curl_init( $url );
    curl_setopt_array( $ch, array(
      CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 180, CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_MAXREDIRS => 3, CURLOPT_USERAGENT => "HiTune-AI/1.0",
    ) );
    $audio = curl_exec( $ch ); $code = (int)curl_getinfo( $ch, CURLINFO_RESPONSE_CODE ); curl_close( $ch );
    if ( !$audio || $code >= 400 ){ $err = "audio download http {$code}"; return null; }
    if ( strlen( $audio ) < 20000 ){ $err = "audio too small (provider error?)"; return null; }
    $name = uniqid( "song_" ) . ".mp3";
    file_put_contents( $out_dir . $name, $audio );
    return array( "path" => $out_rel . $name, "data" => array( "engine" => $engine, "title" => $title ) );
  }

  /* ------------------------------------------------------------------ */
  /* Files                                                               */
  /* ------------------------------------------------------------------ */

  protected function _register_file( $rel_path, $user_id, $object_type ){
    $full = base_root . "/" . ltrim( $rel_path, "/" );
    if ( !is_file( $full ) ) return null;
    $db = bof()->db;
    $ext  = strtolower( pathinfo( $full, PATHINFO_EXTENSION ) );
    $mime = function_exists("mime_content_type") ? (mime_content_type( $full ) ?: "application/octet-stream") : "application/octet-stream";
    $type = in_array( $ext, array("mp3","wav","m4a","ogg","flac","aac"), true ) ? "audio"
          : ( in_array( $ext, array("mp4","webm","mov"), true ) ? "video" : "other" );
    $db->query( "INSERT INTO `_bof_files` (pass, type, host_id, dest_host_id, user_id, path, name, extension, mime_type, object_type, size, used)
      VALUES ('" . substr( md5(uniqid().mt_rand()), 0, 10 ) . "', '{$type}', 1, 0, " . (int)$user_id . ", '" . $db->real_escape_string($rel_path) . "',
      '" . $db->real_escape_string(basename($full)) . "', '{$ext}', '" . $db->real_escape_string($mime) . "',
      '" . $db->real_escape_string($object_type) . "', " . (float)filesize($full) . ", 1)" );
    return (int)$db->insert_id;
  }

  protected function _rrmdir( $dir ){
    if ( !is_dir($dir) ) return;
    $it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST );
    foreach ( $it as $f ){ $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname()); }
    @rmdir( $dir );
  }

}

?>
