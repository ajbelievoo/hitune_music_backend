<?php

if ( !defined( "bof_root" ) ) die;

class hitune_extras {

  protected $faker = null;

  public function setup(){

    // MixCloud source type — available in both client and admin apps.
    // "inputs" feeds the admin source form + generic be_renderer;
    // "download_able" => -2 keeps it stream-only (iframed content).
    if ( bof()->object->db_setting->get( "htx_mixcloud" ) ){
      bof()->source->add_type( "mixcloud", array(
        "name" => "MixCloud",
        "download_able" => -2,
        "inputs" => array(
          "mixcloud_url" => array(
            "label" => "MixCloud URL",
            "tip"   => "Full URL, e.g. https://www.mixcloud.com/artist/mix-name/",
            "input" => array(
              "name" => "mixcloud_url",
              "type" => "text",
            ),
            "validator" => array(
              "url",
              array( "empty()" )
            ),
            "display_on" => array(
              "type" => array( "equal", "mixcloud" )
            )
          )
        )
      ) );
      bof()->source->add_supported_source( "mixcloud", "MixCloud" );
    }

    if ( bof()->getName() != "bof_admin" )
    return;

    $this->setup_admin();

  }
  protected function setup_admin(){

    // Tools sidebar link
    bof()->listen( "highlights", "display_pre", function( $method_args, $method_result, $loader ){
      $highlights = $loader->highlights->getData();
      $highlights["extensions_links"]["items"]["tools_links"]["args"]["childs"][] = array(
        "title" => "HiTune Tools",
        "icon"  => "extension",
        "link"  => "hitune_extras"
      );
      bof()->highlights->setData( $highlights );
    } );

    // Admin page
    bof()->listen( "client_config", "get_pages_after", function( $method_args, &$method_result, $loader ){
      $method_result["hitune_extras"] = array(
        "title" => "HiTune Tools",
        "url" => "^hitune_extras$",
        "link" => "hitune_extras",
        "theme_file" => "parts/content_setting",
        "becli" => array(
          (object) array(
            "endpoint" => "bofAdmin/setting/htx/",
            "key" => "setting"
          )
        ),
        "__sb_family" => "extensions",
      );
    } );

    // (Dead hook in this BOF version — kept for forward-compat)
    bof()->listen( "bofAdmin", "setting_pre", function( $method_args ){
      if ( $method_args[0] == "htx" ) bof()->hitune_extras->register_setting();
    } );

    $this->register_setting();

  }

  /* ---------------- Settings page ---------------- */

  public function register_setting(){

    bof()->bofAdmin->_add_setting( "htx", array(
      "action_btn_title" => "Save / Run",
      "functions" => array(
        "ui_pre"   => function( $groups ){ return $this->ui_pre( $groups ); },
        "be_after" => function( $groups, $inputs ){ return $this->be_after( $groups, $inputs ); },
      ),
      "groups" => array(

        "proxy" => array(
          "title" => "Outbound Proxy (cURL Proxy)",
          "icon"  => "vpn_key",
          "tip"   => "Route all outbound requests (Piped/API calls) through a proxy. For yt-dlp use the existing <b>Youtube-DL → proxy</b> setting.",
          "inputs" => array(
            "curl_proxy" => array(
              "title" => "Global proxy",
              "tip"   => "Format: <code>type://user:pass@host:port</code> or JSON <code>{\"address\":\"h\",\"port\":8080,\"type\":\"socks5\"}</code>. Empty = direct.",
              "col_name" => "curl_proxy",
              "input" => array( "name" => "curl_proxy", "type" => "text" ),
              "validator" => array( "string", array( "empty()" ) )
            ),
            "htx_proxy_test" => array(
              "title" => "Test proxy on save",
              "tip"   => "Fetches api.ipify.org through the proxy and stores the result below",
              "input" => array( "name" => "htx_proxy_test", "type" => "checkbox" ),
              "validator" => array( "boolean", array( "empty()", "int" => true ) )
            ),
          )
        ),

        "adblock" => array(
          "title" => "AdBlock Blocker",
          "icon"  => "block",
          "tip"   => "Detect adblockers on the public web pages",
          "inputs" => array(
            "htx_adblock" => array(
              "title" => "Enable detection",
              "col_name" => "htx_adblock",
              "input" => array( "name" => "htx_adblock", "type" => "checkbox" ),
              "validator" => array( "boolean", array( "empty()", "int" => true ) )
            ),
            "htx_adblock_mode" => array(
              "title" => "Action",
              "col_name" => "htx_adblock_mode",
              "input" => array(
                "name" => "htx_adblock_mode",
                "type" => "select",
                "options" => array(
                  "overlay"  => "Overlay warning (page still usable)",
                  "block"    => "Block page until disabled",
                  "redirect" => "Redirect to URL"
                ),
                "value" => "overlay"
              ),
              "validator" => array( "in_array", array( "empty()", "values" => [ "overlay", "block", "redirect" ] ) )
            ),
            "htx_adblock_url" => array(
              "title" => "Redirect URL",
              "tip"   => "Used only when action = Redirect",
              "col_name" => "htx_adblock_url",
              "input" => array( "name" => "htx_adblock_url", "type" => "text" ),
              "validator" => array( "string", array( "empty()" ) )
            ),
          )
        ),

        "fake_users" => array(
          "title" => "Fake User Generator",
          "icon"  => "group_add",
          "tip"   => "Creates demo users (role: Users). Their email is <code>@hitune.fake</code> so they are easy to spot and clean.",
          "inputs" => array(
            "htx_fu_count" => array(
              "title" => "How many users",
              "input" => array( "name" => "htx_fu_count", "type" => "number", "value" => 5 ),
              "validator" => array( "int", array( "empty()", "min" => 1, "max" => 50 ) )
            ),
            "htx_fu_gender" => array(
              "title" => "Gender",
              "input" => array(
                "name" => "htx_fu_gender",
                "type" => "select",
                "options" => array( "any" => "Any", "male" => "Male", "female" => "Female" ),
                "value" => "any"
              ),
              "validator" => array( "in_array", array( "empty()", "values" => [ "any", "male", "female" ] ) )
            ),
            "htx_fu_verified" => array(
              "title" => "Mark email verified",
              "input" => array( "name" => "htx_fu_verified", "type" => "checkbox", "value" => true ),
              "validator" => array( "boolean", array( "empty()", "int" => true ) )
            ),
            "htx_fu_run" => array(
              "title" => "Generate now (on Save)",
              "input" => array( "name" => "htx_fu_run", "type" => "checkbox" ),
              "validator" => array( "boolean", array( "empty()", "int" => true ) )
            ),
          )
        ),

        "demo_import" => array(
          "title" => "1-Click Demo Importer",
          "icon"  => "cloud_download",
          "tip"   => "Imports real tracks from iTunes (30-sec previews as remote sources + artwork + artist/album). Safe: re-running skips duplicates.",
          "inputs" => array(
            "htx_demo_term" => array(
              "title" => "Genre / search term",
              "input" => array( "name" => "htx_demo_term", "type" => "text", "value" => "pop hits" ),
              "validator" => array( "string", array( "empty()" ) )
            ),
            "htx_demo_count" => array(
              "title" => "Tracks to import",
              "input" => array( "name" => "htx_demo_count", "type" => "number", "value" => 10 ),
              "validator" => array( "int", array( "empty()", "min" => 1, "max" => 20 ) )
            ),
            "htx_demo_run" => array(
              "title" => "Import now (on Save)",
              "input" => array( "name" => "htx_demo_run", "type" => "checkbox" ),
              "validator" => array( "boolean", array( "empty()", "int" => true ) )
            ),
          )
        ),

        "archiver" => array(
          "title" => "Content Archiver",
          "icon"  => "archive",
          "tip"   => "Export object rows to files/exports/ as a downloadable file",
          "inputs" => array(
            "htx_exp_type" => array(
              "title" => "Object",
              "input" => array(
                "name" => "htx_exp_type",
                "type" => "select",
                "options" => array(
                  "m_track"   => "Tracks",
                  "m_album"   => "Albums",
                  "m_artist"  => "Artists",
                  "user"      => "Users",
                  "user_subs" => "Subscriptions",
                  "ugc_playlist" => "Playlists"
                ),
                "value" => "m_track"
              ),
              "validator" => array( "in_array", array( "empty()", "values" => [ "m_track", "m_album", "m_artist", "user", "user_subs", "ugc_playlist" ] ) )
            ),
            "htx_exp_format" => array(
              "title" => "Format",
              "input" => array(
                "name" => "htx_exp_format",
                "type" => "select",
                "options" => array( "json" => "JSON", "csv" => "CSV" ),
                "value" => "json"
              ),
              "validator" => array( "in_array", array( "empty()", "values" => [ "json", "csv" ] ) )
            ),
            "htx_exp_run" => array(
              "title" => "Export now (on Save)",
              "input" => array( "name" => "htx_exp_run", "type" => "checkbox" ),
              "validator" => array( "boolean", array( "empty()", "int" => true ) )
            ),
          )
        ),

        "iyolme" => array(
          "title" => "IyolMe Integration",
          "icon"  => "movie",
          "tip"   => "HiTune &harr; IyolMe link: OAuth SSO provider, reel publishing and sound-registry sync. The IyolMe backend lives on a separate server — paste its API base URL and the shared credentials here.",
          "inputs" => array(
            "iyol_enabled" => array(
              "title" => "Enable integration",
              "col_name" => "iyol_enabled",
              "input" => array( "name" => "iyol_enabled", "type" => "checkbox" ),
              "validator" => array( "boolean", array( "empty()", "int" => true ) )
            ),
            "iyol_api_base" => array(
              "title" => "IyolMe API base URL",
              "tip"   => "e.g. <code>https://api.iyolme.com</code> — HiTune calls <code>{base}/hitune/v1/…</code>",
              "col_name" => "iyol_api_base",
              "input" => array( "name" => "iyol_api_base", "type" => "text" ),
              "validator" => array( "string", array( "empty()" ) )
            ),
            "iyol_client_id" => array(
              "title" => "Client ID (issued to IyolMe)",
              "tip"   => "Leave empty and tick 'Generate credentials' to create one.",
              "col_name" => "iyol_client_id",
              "input" => array( "name" => "iyol_client_id", "type" => "text" ),
              "validator" => array( "string", array( "empty()" ) )
            ),
            "iyol_client_secret" => array(
              "title" => "Client Secret",
              "tip"   => "Also the HMAC key for HiTune → IyolMe calls. Stored in settings — share it with the IyolMe backend team only.",
              "col_name" => "iyol_client_secret",
              "input" => array( "name" => "iyol_client_secret", "type" => "text" ),
              "validator" => array( "string", array( "empty()" ) )
            ),
            "iyol_webhook_secret" => array(
              "title" => "Webhook secret (inbound)",
              "tip"   => "HMAC key IyolMe uses to sign events it sends to <code>/api/v1/iyol/webhook</code>. Defaults to the client secret.",
              "col_name" => "iyol_webhook_secret",
              "input" => array( "name" => "iyol_webhook_secret", "type" => "text" ),
              "validator" => array( "string", array( "empty()" ) )
            ),
            "iyol_redirect_uris" => array(
              "title" => "Allowed redirect URIs",
              "tip"   => 'JSON array, e.g. <code>["https://iyolme.com/auth/hitune/callback","iyolme://auth/hitune"]</code>',
              "col_name" => "iyol_redirect_uris",
              "input" => array( "name" => "iyol_redirect_uris", "type" => "text" ),
              "validator" => array( "string", array( "empty()" ) )
            ),
            "htx_iyol_gen" => array(
              "title" => "Generate credentials (on Save)",
              "tip"   => "Creates a fresh client_id + client_secret pair and fills the fields above. Regenerating breaks IyolMe until they update their config.",
              "input" => array( "name" => "htx_iyol_gen", "type" => "checkbox" ),
              "validator" => array( "boolean", array( "empty()", "int" => true ) )
            ),
            "htx_iyol_test" => array(
              "title" => "Test connection (on Save)",
              "tip"   => "Calls <code>{api_base}/hitune/v1/ping</code> with a signed request and reports the result.",
              "input" => array( "name" => "htx_iyol_test", "type" => "checkbox" ),
              "validator" => array( "boolean", array( "empty()", "int" => true ) )
            ),
          )
        ),

        "ai_tools" => array(
          "title" => "AI Tools & Policy",
          "icon"  => "smart_toy",
          "tip"   => "Strategy doc §9 — per-tool ON/OFF switches, provider/model swapping and API keys, plus per-plan daily quotas. Features read these via <code>bof()->hitune_extras->ai_tool_enabled()</code>.",
          "inputs" => array(
            "ai_tools_enabled" => array(
              "title" => "Enable AI tools globally",
              "col_name" => "ai_tools_enabled",
              "input" => array( "name" => "ai_tools_enabled", "type" => "checkbox" ),
              "validator" => array( "boolean", array( "empty()", "int" => true ) )
            ),
            "ai_cover_art" => array(
              "title" => "AI Cover Art Generator",
              "col_name" => "ai_cover_art",
              "input" => array( "name" => "ai_cover_art", "type" => "checkbox" ),
              "validator" => array( "boolean", array( "empty()", "int" => true ) )
            ),
            "ai_cover_provider" => array(
              "title" => "Cover art provider",
              "col_name" => "ai_cover_provider",
              "input" => array(
                "name" => "ai_cover_provider", "type" => "select",
                "options" => array( "sdxl" => "Stable Diffusion XL", "flux" => "FLUX.1", "dalle3" => "DALL-E 3", "midjourney" => "Midjourney API" ),
                "value" => "sdxl"
              ),
              "validator" => array( "in_array", array( "empty()", "values" => [ "sdxl", "flux", "dalle3", "midjourney" ] ) )
            ),
            "ai_cover_api_key" => array(
              "title" => "Cover art API key",
              "col_name" => "ai_cover_api_key",
              "input" => array( "name" => "ai_cover_api_key", "type" => "text" ),
              "validator" => array( "string", array( "empty()" ) )
            ),
            "ai_mastering" => array(
              "title" => "AI Audio Mastering",
              "col_name" => "ai_mastering",
              "input" => array( "name" => "ai_mastering", "type" => "checkbox" ),
              "validator" => array( "boolean", array( "empty()", "int" => true ) )
            ),
            "ai_mastering_provider" => array(
              "title" => "Mastering provider",
              "col_name" => "ai_mastering_provider",
              "input" => array(
                "name" => "ai_mastering_provider", "type" => "select",
                "options" => array( "matchering" => "Matchering (local)", "deepafx" => "DeepAFx", "masterchannel" => "Masterchannel API" ),
                "value" => "matchering"
              ),
              "validator" => array( "in_array", array( "empty()", "values" => [ "matchering", "deepafx", "masterchannel" ] ) )
            ),
            "ai_mastering_api_url" => array(
              "title" => "Mastering API endpoint",
              "col_name" => "ai_mastering_api_url",
              "input" => array( "name" => "ai_mastering_api_url", "type" => "text" ),
              "validator" => array( "string", array( "empty()" ) )
            ),
            "ai_karaoke" => array(
              "title" => "Karaoke / vocal isolation",
              "col_name" => "ai_karaoke",
              "input" => array( "name" => "ai_karaoke", "type" => "checkbox" ),
              "validator" => array( "boolean", array( "empty()", "int" => true ) )
            ),
            "ai_karaoke_provider" => array(
              "title" => "Vocal isolation engine",
              "col_name" => "ai_karaoke_provider",
              "input" => array(
                "name" => "ai_karaoke_provider", "type" => "select",
                "options" => array( "demucs" => "Demucs v4 (Meta)", "spleeter" => "Spleeter" ),
                "value" => "demucs"
              ),
              "validator" => array( "in_array", array( "empty()", "values" => [ "demucs", "spleeter" ] ) )
            ),
            "ai_lyrics_sync" => array(
              "title" => "Smart Lyrics Sync",
              "col_name" => "ai_lyrics_sync",
              "input" => array( "name" => "ai_lyrics_sync", "type" => "checkbox" ),
              "validator" => array( "boolean", array( "empty()", "int" => true ) )
            ),
            "ai_lyrics_provider" => array(
              "title" => "Lyrics sync engine",
              "col_name" => "ai_lyrics_provider",
              "input" => array(
                "name" => "ai_lyrics_provider", "type" => "select",
                "options" => array( "whisper" => "OpenAI Whisper", "wav2vec2" => "Wav2Vec 2.0" ),
                "value" => "whisper"
              ),
              "validator" => array( "in_array", array( "empty()", "values" => [ "whisper", "wav2vec2" ] ) )
            ),
            "ai_song_gen" => array(
              "title" => "AI Song Generator (prompt → song)",
              "tip"   => "Full track generation from a text prompt. Needs a provider API key below. Plan-level control: use the ai_* checkboxes on each subscription plan.",
              "col_name" => "ai_song_gen",
              "input" => array( "name" => "ai_song_gen", "type" => "checkbox" ),
              "validator" => array( "boolean", array( "empty()", "int" => true ) )
            ),
            "ai_song_gen_provider" => array(
              "title" => "Song generation provider",
              "col_name" => "ai_song_gen_provider",
              "input" => array(
                "name" => "ai_song_gen_provider", "type" => "select",
                "options" => array(
                  "stability" => "Stability AI — Stable Audio (instrumentals/SFX)",
                  "suno"      => "Suno-compatible API (vocals + instrumentals)",
                  "replicate" => "Replicate — MusicGen/other model",
                ),
                "value" => "stability"
              ),
              "validator" => array( "in_array", array( "empty()", "values" => [ "stability", "suno", "replicate" ] ) )
            ),
            "ai_song_gen_api_key" => array(
              "title" => "Song generation API key",
              "col_name" => "ai_song_gen_api_key",
              "input" => array( "name" => "ai_song_gen_api_key", "type" => "text" ),
              "validator" => array( "string", array( "empty()" ) )
            ),
            "ai_song_gen_api_url" => array(
              "title" => "Song API base URL (suno-compatible only)",
              "tip"   => "e.g. https://api.sunoapi.org/api/v1 — the code calls {base}/generate and {base}/get?ids=",
              "col_name" => "ai_song_gen_api_url",
              "input" => array( "name" => "ai_song_gen_api_url", "type" => "text" ),
              "validator" => array( "string", array( "empty()" ) )
            ),
            "ai_song_model" => array(
              "title" => "Song model/version (replicate only)",
              "tip"   => "Replicate model version hash, e.g. a MusicGen version id",
              "col_name" => "ai_song_model",
              "input" => array( "name" => "ai_song_model", "type" => "text" ),
              "validator" => array( "string", array( "empty()" ) )
            ),
            "ai_quota_free" => array(
              "title" => "Daily AI quota — free users",
              "col_name" => "ai_quota_free",
              "input" => array( "name" => "ai_quota_free", "type" => "number", "value" => 3 ),
              "validator" => array( "int", array( "empty()", "min" => 0, "max" => 1000 ) )
            ),
            "ai_quota_pro" => array(
              "title" => "Daily AI quota — pro users",
              "col_name" => "ai_quota_pro",
              "input" => array( "name" => "ai_quota_pro", "type" => "number", "value" => 50 ),
              "validator" => array( "int", array( "empty()", "min" => 0, "max" => 10000 ) )
            ),
          )
        ),

        "monetization" => array(
          "title" => "Monetization & Creator Events",
          "icon"  => "payments",
          "tip"   => "Strategy doc §4/§8 — fan tipping split and 100%-royalty event windows (Creator Day).",
          "inputs" => array(
            "tip_artist_pct" => array(
              "title" => "Artist share of tips (%)",
              "tip"   => "Platform keeps the remainder. Overridden to 100% during the event window below.",
              "col_name" => "tip_artist_pct",
              "input" => array( "name" => "tip_artist_pct", "type" => "number", "value" => 80 ),
              "validator" => array( "int", array( "empty()", "min" => 1, "max" => 100 ) )
            ),
            "tip_event_until" => array(
              "title" => "100% royalty event — ends at",
              "tip"   => "Datetime like <code>2026-10-15 23:59:59</code>. While active, tips pay 100% to the artist (Creator Day). Leave empty to disable.",
              "col_name" => "tip_event_until",
              "input" => array( "name" => "tip_event_until", "type" => "text", "placeholder" => "YYYY-MM-DD HH:MM:SS" ),
              "validator" => array( "string", array( "empty()", "max" => 30 ) )
            ),
          )
        ),

        "mixcloud" => array(
          "title" => "MixCloud iFrame",
          "icon"  => "cloud_queue",
          "tip"   => "Adds MixCloud support to the player. Add a track source with type <code>mixcloud</code> and a full MixCloud URL in data.url — the API returns a playable iframe embed.",
          "inputs" => array(
            "htx_mixcloud" => array(
              "title" => "Enable MixCloud sources",
              "col_name" => "htx_mixcloud",
              "input" => array( "name" => "htx_mixcloud", "type" => "checkbox" ),
              "validator" => array( "boolean", array( "empty()", "int" => true ) )
            ),
          )
        ),

      )
    ) );

  }

  /* ---------------- UI / BE hooks ---------------- */

  protected function ui_pre( $groups ){

    $reports = array(
      "proxy"       => array( "htx_proxy_last", "Proxy test" ),
      "fake_users"  => array( "htx_fu_last",    "Last run" ),
      "demo_import" => array( "htx_demo_last",  "Last import" ),
      "archiver"    => array( "htx_exp_last",   "Last export" ),
      "iyolme"      => array( "htx_iyol_last",  "IyolMe status" ),
    );

    foreach( $reports as $_g => $_r ){
      $_v = bof()->object->db_setting->get( $_r[0] );
      if ( $_v )
      $groups[$_g]["tip"] = ( !empty( $groups[$_g]["tip"] ) ? $groups[$_g]["tip"] . "<br>" : "" ) . "<b>{$_r[1]}:</b> " . $_v;
    }

    return $groups;

  }
  protected function be_after( $groups, $_inputs ){

    $data = !empty( $_inputs["data"] ) ? $_inputs["data"] : array();

    if ( !empty( $data["htx_proxy_test"] ) ){
      $_inputs["set"]["htx_proxy_last"] = $this->run_proxy_test( !empty( $data["curl_proxy"] ) ? $data["curl_proxy"] : bof()->object->db_setting->get( "curl_proxy" ) );
    }

    if ( !empty( $data["htx_fu_run"] ) ){
      $_inputs["set"]["htx_fu_last"] = $this->run_fake_users(
        !empty( $data["htx_fu_count"] ) ? intval( $data["htx_fu_count"] ) : 5,
        !empty( $data["htx_fu_gender"] ) ? $data["htx_fu_gender"] : "any",
        !empty( $data["htx_fu_verified"] )
      );
    }

    if ( !empty( $data["htx_demo_run"] ) ){
      $_inputs["set"]["htx_demo_last"] = $this->run_demo_import(
        !empty( $data["htx_demo_term"] ) ? $data["htx_demo_term"] : "pop hits",
        !empty( $data["htx_demo_count"] ) ? intval( $data["htx_demo_count"] ) : 10
      );
    }

    if ( !empty( $data["htx_exp_run"] ) ){
      $_inputs["set"]["htx_exp_last"] = $this->run_export(
        !empty( $data["htx_exp_type"] ) ? $data["htx_exp_type"] : "m_track",
        !empty( $data["htx_exp_format"] ) ? $data["htx_exp_format"] : "json"
      );
    }

    if ( !empty( $data["htx_iyol_gen"] ) ){
      $_inputs["set"]["iyol_client_id"]     = "iyol_live_" . bin2hex( random_bytes( 8 ) );
      $_inputs["set"]["iyol_client_secret"] = "iyol_sec_" . bin2hex( random_bytes( 24 ) );
      $_inputs["set"]["htx_iyol_last"]      = "New credentials generated " . date( "Y-m-d H:i:s" ) . " — share client_id + client_secret with the IyolMe backend team";
    }

    if ( !empty( $data["htx_iyol_test"] ) ){
      $_inputs["set"]["htx_iyol_last"] = $this->run_iyol_test(
        !empty( $data["iyol_api_base"] ) ? $data["iyol_api_base"] : bof()->object->db_setting->get( "iyol_api_base" )
      );
    }

    if ( isset( $data["htx_mixcloud"] ) )
    $this->sync_mixcloud_access( !empty( $data["htx_mixcloud"] ) );

    return $_inputs;

  }

  /* ---------------- AI tools runtime gates (doc §9) ---------------- */

  // True when the global switch AND the specific tool switch are on.
  // $tool: cover_art | mastering | karaoke | lyrics_sync
  public function ai_tool_enabled( $tool ){
    if ( empty( bof()->object->db_setting->get( "ai_tools_enabled" ) ) ) return false;
    return !empty( bof()->object->db_setting->get( "ai_" . $tool ) );
  }

  // Provider/model + key/url currently selected for a tool.
  public function ai_tool_config( $tool ){
    $s = bof()->object->db_setting;
    return array(
      "enabled"  => $this->ai_tool_enabled( $tool ),
      "provider" => $s->get( "ai_{$tool}_provider" ),
      "api_key"  => $s->get( "ai_{$tool}_api_key" ),
      "api_url"  => $s->get( "ai_{$tool}_api_url" ),
    );
  }

  // Remaining daily AI generations for a user (pro = subscriber role/plan).
  public function ai_quota_left( $user_id, $is_pro = false ){
    $limit = (int) bof()->object->db_setting->get( $is_pro ? "ai_quota_pro" : "ai_quota_free" );
    if ( $limit <= 0 ) return 0;
    $day = date( "Y-m-d" );
    $key = "ai_quota_used_{$day}_" . (int)$user_id;
    $used = (int) bof()->object->db_setting->get( $key );
    return max( 0, $limit - $used );
  }

  public function ai_quota_spend( $user_id ){
    $day = date( "Y-m-d" );
    $key = "ai_quota_used_{$day}_" . (int)$user_id;
    $used = (int) bof()->object->db_setting->get( $key );
    bof()->object->db_setting->set( $key, $used + 1 );
  }

  /* ---------------- MixCloud access sync ---------------- */

  // Adds/removes "mixcloud" from muse_available_sources and the
  // guest/user role player lists so the source actually reaches clients
  // (class_source->get() filters by both).
  protected function sync_mixcloud_access( $enable ){

    $sources = bof()->object->db_setting->get( "muse_available_sources" );
    $sources = is_array( $sources ) ? $sources : array_filter( explode( ",", (string)$sources ) );
    $sources = array_values( array_diff( $sources, array( "mixcloud" ) ) );
    if ( $enable ) $sources[] = "mixcloud";
    bof()->object->db_setting->set( "muse_available_sources", implode( ",", $sources ) );

    $roles = bof()->db->_select( array(
      "table" => "_u_roles",
      "columns" => "ID,data",
      "single" => false,
      "limit" => false,
      "cache_load_rt" => false
    ) );

    foreach( (array)$roles as $role ){

      $data = json_decode( (string)$role["data"], true );
      if ( !is_array( $data ) ) continue;

      $changed = false;

      foreach( $data as $scope => &$perms ){

        if ( !is_array( $perms ) ) continue;

        foreach( array( "guest_player", "user_player", "user_p_player" ) as $pk ){
          if ( empty( $perms[$pk] ) ) continue;
          $list = array_filter( explode( ";", $perms[$pk] ) );
          $list = array_values( array_diff( $list, array( "mixcloud" ) ) );
          if ( $enable ) $list[] = "mixcloud";
          $new = implode( ";", $list );
          if ( $new !== $perms[$pk] ){ $perms[$pk] = $new; $changed = true; }
        }

      }

      if ( $changed )
      bof()->db->_update( array(
        "table" => "_u_roles",
        "set" => array( array( "data", json_encode( $data ) ) ),
        "where" => array( array( "ID", "=", $role["ID"] ) )
      ) );

    }

  }

  /* ---------------- Actions ---------------- */

  protected function run_proxy_test( $proxy ){

    if ( !$proxy )
    return "no proxy configured — direct connection";

    $proxy_parsed = json_decode( $proxy, true );
    $proxy_arg = is_array( $proxy_parsed ) ? $proxy_parsed : $proxy;

    $exe = bof()->curl->exe( array(
      "url" => "https://api.ipify.org?format=json",
      "cache" => false, "cache_save" => false, "cache_load" => false,
      "proxy" => $proxy_arg,
      "timeout" => 12,
      "ctimeout" => 6,
      "echo" => false
    ) );

    if ( !empty( $exe["data"]["ip"] ) )
    return "OK — visible IP via proxy: " . $exe["data"]["ip"] . " (" . date( "H:i:s" ) . ")";

    return "FAILED — " . ( !empty( $exe["error"] ) ? $exe["error"] : "http " . ( $exe["http_code"] ?? "?" ) ) . " (" . date( "H:i:s" ) . ")";

  }
  protected function run_iyol_test( $api_base ){

    if ( !$api_base )
    return "iyol_api_base is empty — set the IyolMe API base URL first";

    try {
      $res = bof()->iyolme->api_request( "POST", "/hitune/v1/ping", array( "ping" => time() ) );
    } catch ( \Throwable $e ) {
      return "FAILED — " . substr( $e->getMessage(), 0, 150 );
    }

    if ( !empty( $res["error"] ) )
    return "FAILED — {$res["error"]}" . ( !empty( $res["detail"] ) ? " ({$res["detail"]})" : "" ) . " (" . date( "H:i:s" ) . ")";

    return "OK — IyolMe responded http " . ( $res["_http_code"] ?? 200 ) . " (" . date( "H:i:s" ) . ")";

  }
  protected function faker(){

    if ( $this->faker ) return $this->faker;
    require_once( bof_root . "/app/core/third/fakerphp_faker/vendor/autoload.php" );
    $this->faker = Faker\Factory::create( "en_IN" );
    return $this->faker;

  }
  public function run_fake_users( $count=5, $gender="any", $verified=true ){

    $faker = $this->faker();
    $created = 0;
    $skipped = 0;
    $samples = array();

    for( $i=0; $i<$count; $i++ ){

      $_g = $gender == "any" ? ( rand(0,1) ? "male" : "female" ) : $gender;
      $name = $faker->name( $_g );
      $username = strtolower( preg_replace( "/[^a-z0-9]/", "", str_replace( " ", "", strtolower( $name ) ) ) ) . rand( 100, 9999 );
      $email = $username . "@" . $faker->freeEmailDomain;
      $email = str_replace( array( "@gmail.com", "@yahoo.com", "@hotmail.com" ), "@hitune.fake", $email );

      $insert = array(
        "username" => $username,
        "name" => $name,
        "email" => $email,
        "password" => $faker->password( 10, 16 ),
        "role_ids" => "2",
      );
      if ( $verified ) $insert["time_verify"] = date( "Y-m-d H:i:s" );

      $res = bof()->object->user->create(
        array( "email" => $email ),
        $insert
      );

      if ( $res ){ $created++; if ( count( $samples ) < 5 ) $samples[] = $username; }
      else $skipped++;

    }

    return "{$created} users created, {$skipped} skipped (exists)" . ( $samples ? " — e.g. " . implode( ", ", $samples ) : "" );

  }
  // Generates a unique slug for code/seo_url columns (NOT NULL UNIQUE).
  protected function _uniq_slug( $base, $table, $column ){

    $slug = bof()->general->make_code( $base );
    if ( !$slug ) $slug = "item";

    $try = $slug; $i = 1;
    while ( true ){
      $exists = bof()->db->_select( array(
        "table" => $table,
        "columns" => "ID",
        "where" => array( array( $column, "=", $try ) ),
        "single" => true,
        "limit" => 1,
        "cache_load_rt" => false
      ) );
      if ( !$exists ) return $try;
      $try = substr( $slug, 0, 90 ) . "-" . ++$i;
    }

  }
  public function run_demo_import( $term="pop hits", $count=10 ){

    $ctx = stream_context_create( array( "http" => array( "timeout" => 15 ) ) );
    $url = "https://itunes.apple.com/search?term=" . urlencode( $term ) . "&entity=song&media=music&limit=" . intval( $count );
    $res = @json_decode( @file_get_contents( $url, false, $ctx ), true );

    if ( empty( $res["results"] ) )
    return "iTunes search returned nothing for \"" . htmlspecialchars( $term ) . "\"";

    $created_t = 0; $skipped_t = 0;

    foreach( $res["results"] as $song ){

      if ( empty( $song["previewUrl"] ) || empty( $song["trackName"] ) || empty( $song["artistName"] ) )
      continue;

      // Artist — find-or-create (m_artist has no `name` equality selector)
      $artist_id = null;
      $_artist_hits = bof()->object->m_artist->select(
        array( "query" => $song["artistName"] ),
        array( "single" => false, "limit" => 10, "clean" => false, "cache_load_rt" => false )
      );
      foreach( (array)$_artist_hits as $_a )
      if ( strcasecmp( trim( $_a["name"] ), trim( $song["artistName"] ) ) === 0 ){ $artist_id = $_a["ID"]; break; }
      if ( !$artist_id )
      $artist_id = bof()->object->m_artist->insert( array(
        "name" => $song["artistName"],
        "code" => $this->_uniq_slug( $song["artistName"], "_c_m_artists", "code" ),
        "seo_url" => $this->_uniq_slug( $song["artistName"], "_c_m_artists", "seo_url" ),
      ) );
      if ( !$artist_id ) continue;

      // Album
      $album_title = !empty( $song["collectionName"] ) ? $song["collectionName"] : $song["trackName"];
      $album_id = null;
      $_album_hits = bof()->object->m_album->select(
        array( "query" => $album_title ),
        array( "single" => false, "limit" => 10, "clean" => false, "cache_load_rt" => false )
      );
      foreach( (array)$_album_hits as $_al )
      if ( strcasecmp( trim( $_al["title"] ), trim( $album_title ) ) === 0 && $_al["artist_id"] == $artist_id ){ $album_id = $_al["ID"]; break; }
      if ( !$album_id )
      $album_id = bof()->object->m_album->insert( array(
        "title" => $album_title,
        "artist_id" => $artist_id,
        "type" => "single",
        "code" => $this->_uniq_slug( $album_title, "_c_m_albums", "code" ),
        "seo_url" => $this->_uniq_slug( $album_title, "_c_m_albums", "seo_url" ),
      ) );
      if ( !$album_id ) continue;

      // Track — dedupe on title+artist
      $track_id = null;
      $_track_hits = bof()->object->m_track->select(
        array( "query" => $song["trackName"] ),
        array( "single" => false, "limit" => 10, "clean" => false, "cache_load_rt" => false )
      );
      foreach( (array)$_track_hits as $_t )
      if ( strcasecmp( trim( $_t["title"] ), trim( $song["trackName"] ) ) === 0 && $_t["artist_id"] == $artist_id ){ $track_id = $_t["ID"]; break; }

      if ( $track_id ){ $skipped_t++; continue; }

      $track_id = bof()->object->m_track->insert( array(
        "title" => $song["trackName"],
        "code" => $this->_uniq_slug( $song["trackName"], "_c_m_tracks", "code" ),
        "seo_url" => $this->_uniq_slug( $song["trackName"], "_c_m_tracks", "seo_url" ),
        "artist_id" => $artist_id,
        "album_id" => $album_id,
        "duration" => !empty( $song["trackTimeMillis"] ) ? intval( round( $song["trackTimeMillis"] / 1000 ) ) : 30,
        "spotify_cover" => !empty( $song["artworkUrl100"] ) ? json_encode( array(
          array( "url" => str_replace( "100x100bb", "600x600bb", $song["artworkUrl100"] ), "width" => 600, "height" => 600 ),
          array( "url" => $song["artworkUrl100"], "width" => 100, "height" => 100 ),
        ) ) : null,
      ) );
      if ( !$track_id ) continue;
      $created_t++;

      // Remote audio source (iTunes 30s preview)
      bof()->object->m_track_source->insert( array(
        "target_id" => $track_id,
        "type" => "audio",
        "data" => json_encode( array(
          "file_type" => "remote",
          "remote_address" => $song["previewUrl"],
        ) ),
        "stream_able" => 1,
        "download_able" => 0,
        "encrypted" => 0,
        "force_free" => 1
      ) );

    }

    return "imported {$created_t} new tracks ({$skipped_t} already existed) for \"" . htmlspecialchars( $term ) . "\"";

  }
  public function run_export( $type="m_track", $format="json" ){

    try {
      $object = bof()->object->$type;
    } catch( Exception | bofException | Error $err ){
      return "unknown object type: " . htmlspecialchars( $type );
    }
    if ( !$object || !is_object( $object ) )
    return "unknown object type: " . htmlspecialchars( $type );

    $items = $object->select( array(), array(
      "single" => false,
      "limit" => false,
      "clean" => false,
      "empty_select" => true
    ) );

    if ( !$items ) return "no rows found for {$type}";

    $dir = base_root . "/files/exports";
    if ( !is_dir( $dir ) ) @mkdir( $dir, 0775, true );

    $fname = "export_{$type}_" . date( "Ymd_His" ) . "." . $format;
    $fpath = $dir . "/" . $fname;
    $furl  = web_address . "files/exports/" . $fname;

    if ( $format == "csv" ){
      $fp = fopen( $fpath, "w" );
      $headers = array_keys( reset( $items ) );
      fputcsv( $fp, $headers );
      foreach( $items as $row ){
        foreach( $row as &$v ) if ( is_array( $v ) ) $v = json_encode( $v );
        fputcsv( $fp, $row );
      }
      fclose( $fp );
    } else {
      file_put_contents( $fpath, json_encode( $items, JSON_PRETTY_PRINT ) );
    }

    return count( $items ) . " rows exported → <a href='{$furl}' target='_blank'>{$fname}</a>";

  }

}

?>
