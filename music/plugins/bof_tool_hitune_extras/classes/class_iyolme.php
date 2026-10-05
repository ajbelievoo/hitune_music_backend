<?php

if ( !defined( "bof_root" ) ) die;

/**
 * IyolMe integration hub — HiTune side.
 *
 * HiTune acts as the OAuth 2.0 identity provider for the ecosystem
 * (HiTune Music + HiTune Distribution + IyolMe). IyolMe runs on a
 * separate server, so all cross-platform traffic is HTTP:
 *
 *   - OAuth 2.0 authorization-code + refresh_token + client_credentials
 *     grants, HS256 JWT access tokens (token endpoint, userinfo, revoke)
 *   - One-time SSO codes minted for logged-in app users
 *     (POST /api/iyol_sso_link -> exchange via /api/v1/oauth/token)
 *   - Signed outbound calls to IyolMe (reel publish, sound registry)
 *   - Signed inbound webhooks from IyolMe
 *
 * Settings (all in _bof_setting):
 *   iyol_enabled          - master switch
 *   iyol_api_base         - e.g. https://api.iyolme.example.com  (no trailing slash needed)
 *   iyol_client_id        - OAuth client_id issued to IyolMe
 *   iyol_client_secret    - OAuth secret + HMAC key for outbound calls
 *   iyol_webhook_secret   - HMAC key for inbound webhooks (falls back to client_secret)
 *   iyol_jwt_secret       - HS256 signing secret for issued JWTs (falls back to sign_key)
 *   iyol_redirect_uris    - JSON array of allowed redirect URIs for the OAuth client
 */
class iyolme {

  private $_tables_ready = false;

  /* ------------------------------------------------------------------ */
  /* Settings                                                            */
  /* ------------------------------------------------------------------ */

  public function setting( $name, $default=null ){
    $v = bof()->object->db_setting->get( $name );
    return ( $v === null || $v === "" || $v === false ) ? $default : $v;
  }
  public function enabled(){
    return (bool) $this->setting( "iyol_enabled", false );
  }
  public function api_base(){
    return rtrim( (string) $this->setting( "iyol_api_base", "" ), "/" );
  }
  public function client_id(){
    return (string) $this->setting( "iyol_client_id", "" );
  }
  public function client_secret(){
    return (string) $this->setting( "iyol_client_secret", "" );
  }
  public function webhook_secret(){
    return (string) $this->setting( "iyol_webhook_secret", $this->client_secret() );
  }
  public function jwt_secret(){
    return (string) $this->setting( "iyol_jwt_secret", "iyol.jwt." . sign_key );
  }
  public function redirect_uris(){
    $uris = $this->setting( "iyol_redirect_uris", array() );
    if ( is_string( $uris ) ) $uris = json_decode( $uris, true );
    return is_array( $uris ) ? $uris : array();
  }
  public function configured(){
    return $this->enabled() && $this->client_id() && $this->client_secret();
  }

  /* ------------------------------------------------------------------ */
  /* Schema (lazy, mirrors scripts/migration_iyolme.sql)                 */
  /* ------------------------------------------------------------------ */

  public function ensure_tables(){

    if ( $this->_tables_ready ) return true;
    $db = bof()->db;

    $db->query( "CREATE TABLE IF NOT EXISTS `_oauth_clients` (
      `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      `client_id` VARCHAR(64) NOT NULL UNIQUE,
      `secret_hash` CHAR(64) NOT NULL,
      `name` VARCHAR(100) NOT NULL,
      `redirect_uris` TEXT NULL,
      `scopes` VARCHAR(255) DEFAULT 'profile email attribution',
      `status` ENUM('active','disabled') DEFAULT 'active',
      `time_add` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      INDEX `idx_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4" );

    $db->query( "CREATE TABLE IF NOT EXISTS `_oauth_codes` (
      `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      `code_hash` CHAR(64) NOT NULL UNIQUE,
      `client_id` VARCHAR(64) NOT NULL,
      `user_id` INT(11) UNSIGNED NOT NULL,
      `redirect_uri` VARCHAR(500) NULL,
      `scope` VARCHAR(255) NULL,
      `expires` INT(11) UNSIGNED NOT NULL,
      `used` TINYINT(1) DEFAULT 0,
      `time_add` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      INDEX `idx_lookup` (`code_hash`, `used`, `expires`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4" );

    $db->query( "CREATE TABLE IF NOT EXISTS `_oauth_tokens` (
      `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      `token_hash` CHAR(64) NOT NULL UNIQUE,
      `client_id` VARCHAR(64) NOT NULL,
      `user_id` INT(11) UNSIGNED NULL,
      `type` ENUM('access','refresh') NOT NULL,
      `scope` VARCHAR(255) NULL,
      `expires` INT(11) UNSIGNED NOT NULL,
      `revoked` TINYINT(1) DEFAULT 0,
      `time_add` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      INDEX `idx_lookup` (`token_hash`, `revoked`, `expires`),
      INDEX `idx_user` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4" );

    $db->query( "CREATE TABLE IF NOT EXISTS `_iyol_outbox` (
      `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      `kind` VARCHAR(40) NOT NULL,
      `track_id` INT(11) UNSIGNED NULL,
      `payload` TEXT NULL,
      `status` ENUM('pending','sent','failed') DEFAULT 'pending',
      `attempts` INT(3) DEFAULT 0,
      `last_error` VARCHAR(255) NULL,
      `time_add` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `time_sent` TIMESTAMP NULL,
      INDEX `idx_status` (`status`),
      INDEX `idx_track` (`track_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4" );

    $db->query( "CREATE TABLE IF NOT EXISTS `_iyol_events` (
      `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      `event` VARCHAR(60) NOT NULL,
      `payload` TEXT NULL,
      `signature_ok` TINYINT(1) DEFAULT 0,
      `time_add` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      INDEX `idx_event` (`event`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4" );

    $this->_tables_ready = true;
    return true;

  }

  /**
   * Keep the _oauth_clients row for IyolMe in sync with the configured
   * client_id/secret/redirect URIs. Called lazily before any OAuth check.
   */
  public function ensure_client(){

    $this->ensure_tables();
    $cid = $this->client_id();
    $secret = $this->client_secret();
    if ( !$cid || !$secret ) return null;

    $uris = json_encode( $this->redirect_uris() );
    $secret_hash = hash( "sha256", $secret );
    $db = bof()->db;

    $existing = $db->_select( array(
      "table" => "_oauth_clients",
      "where" => array( array( "client_id", "=", $cid ) ),
      "limit" => 1,
      "single" => true
    ) );

    if ( !$existing ){
      $db->_insert( array(
        "table" => "_oauth_clients",
        "set" => array(
          array( "client_id", $cid ),
          array( "secret_hash", $secret_hash ),
          array( "name", "IyolMe" ),
          array( "redirect_uris", $uris ),
          array( "scopes", "profile email attribution reels" )
        )
      ) );
    }
    elseif ( $existing["secret_hash"] !== $secret_hash || (string)$existing["redirect_uris"] !== (string)$uris ){
      $db->_update( array(
        "table" => "_oauth_clients",
        "set" => array(
          array( "secret_hash", $secret_hash ),
          array( "redirect_uris", $uris )
        ),
        "where" => array( array( "client_id", "=", $cid ) )
      ) );
    }

    return $this->oauth_client( $cid );

  }

  /* ------------------------------------------------------------------ */
  /* JWT (HS256)                                                         */
  /* ------------------------------------------------------------------ */

  protected function _b64( $data ){
    return rtrim( strtr( base64_encode( $data ), "+/", "-_" ), "=" );
  }
  protected function _b64d( $data ){
    return base64_decode( strtr( $data, "-_", "+/" ) );
  }

  public function jwt_encode( $payload ){
    $header = $this->_b64( json_encode( array( "alg" => "HS256", "typ" => "JWT" ) ) );
    $body   = $this->_b64( json_encode( $payload ) );
    $sig    = $this->_b64( hash_hmac( "sha256", "{$header}.{$body}", $this->jwt_secret(), true ) );
    return "{$header}.{$body}.{$sig}";
  }

  /** Returns decoded payload array or false. Checks signature + exp. */
  public function jwt_decode( $token ){

    $parts = explode( ".", (string)$token );
    if ( count( $parts ) !== 3 ) return false;

    $sig = $this->_b64( hash_hmac( "sha256", "{$parts[0]}.{$parts[1]}", $this->jwt_secret(), true ) );
    if ( !hash_equals( $sig, $parts[2] ) ) return false;

    $payload = json_decode( $this->_b64d( $parts[1] ), true );
    if ( !is_array( $payload ) ) return false;
    if ( !empty( $payload["exp"] ) && $payload["exp"] < time() ) return false;

    return $payload;

  }

  /* ------------------------------------------------------------------ */
  /* OAuth 2.0 provider                                                  */
  /* ------------------------------------------------------------------ */

  public function oauth_client( $client_id ){
    $this->ensure_tables();
    return bof()->db->_select( array(
      "table" => "_oauth_clients",
      "where" => array( array( "client_id", "=", (string)$client_id ) ),
      "limit" => 1,
      "single" => true
    ) );
  }

  public function oauth_client_ok( $client, $client_secret=null, $redirect_uri=null ){

    if ( !$client || $client["status"] !== "active" ) return false;

    if ( $client_secret !== null
      && !hash_equals( (string)$client["secret_hash"], hash( "sha256", (string)$client_secret ) ) )
      return false;

    if ( $redirect_uri !== null ){
      $allowed = json_decode( (string)$client["redirect_uris"], true );
      if ( !is_array( $allowed ) || !in_array( $redirect_uri, $allowed, true ) )
        return false;
    }

    return true;

  }

  /** Mint a single-use authorization code (10 min TTL). Returns plaintext code. */
  public function create_code( $client_id, $user_id, $redirect_uri=null, $scope="profile email" ){

    $this->ensure_tables();
    $code = "htc_" . bin2hex( random_bytes( 24 ) );

    bof()->db->_insert( array(
      "table" => "_oauth_codes",
      "set" => array(
        array( "code_hash", hash( "sha256", $code ) ),
        array( "client_id", (string)$client_id ),
        array( "user_id", (int)$user_id ),
        array( "redirect_uri", $redirect_uri ),
        array( "scope", $scope ),
        array( "expires", time() + 600 )
      )
    ) );

    return $code;

  }

  /** Consume an authorization code -> user_id + scope, or false. */
  public function consume_code( $client_id, $code, $redirect_uri=null ){

    $this->ensure_tables();
    $db = bof()->db;
    $row = $db->_select( array(
      "table" => "_oauth_codes",
      "where" => array(
        array( "code_hash", "=", hash( "sha256", (string)$code ) ),
        array( "client_id", "=", (string)$client_id ),
        array( "used", "=", 0 ),
        array( "expires", ">", time() )
      ),
      "limit" => 1,
      "single" => true
    ) );

    if ( !$row ) return false;
    if ( $redirect_uri !== null && !empty( $row["redirect_uri"] ) && $row["redirect_uri"] !== $redirect_uri )
      return false;

    $db->_update( array(
      "table" => "_oauth_codes",
      "set" => array( array( "used", 1 ) ),
      "where" => array( array( "id", "=", (int)$row["id"] ) )
    ) );

    return $row;

  }

  /**
   * Issue an access JWT (+refresh token when a user is attached).
   * $user_id = null  -> client_credentials grant (no sub-user)
   */
  public function issue_tokens( $client_id, $user_id=null, $scope="profile" ){

    $this->ensure_tables();
    $db = bof()->db;
    $now = time();

    $access_ttl = 3600;
    $claims = array(
      "iss"   => web_address,
      "aud"   => $client_id,
      "iat"   => $now,
      "exp"   => $now + $access_ttl,
      "scope" => $scope,
      "jti"   => bin2hex( random_bytes( 8 ) )
    );

    if ( $user_id ){
      $user = $this->user_payload( $user_id );
      if ( !$user ) return false;
      $claims["sub"] = $user["sub"];
      $claims["uid"] = (int)$user_id;
      $claims["username"] = $user["username"];
    } else {
      $claims["sub"] = "client:{$client_id}";
    }

    $access = $this->jwt_encode( $claims );

    $token_set = array(
      array( "token_hash", hash( "sha256", $access ) ),
      array( "client_id", (string)$client_id ),
      array( "type", "access" ),
      array( "scope", $scope ),
      array( "expires", $now + $access_ttl )
    );
    $token_set[] = $user_id ? array( "user_id", (int)$user_id ) : array( "user_id", "NULL", true );

    $db->_insert( array(
      "table" => "_oauth_tokens",
      "set" => $token_set
    ) );

    $out = array(
      "access_token" => $access,
      "token_type"   => "Bearer",
      "expires_in"   => $access_ttl,
      "scope"        => $scope
    );

    if ( $user_id ){
      $refresh = "htr_" . bin2hex( random_bytes( 32 ) );
      $db->_insert( array(
        "table" => "_oauth_tokens",
        "set" => array(
          array( "token_hash", hash( "sha256", $refresh ) ),
          array( "client_id", (string)$client_id ),
          array( "user_id", (int)$user_id ),
          array( "type", "refresh" ),
          array( "scope", $scope ),
          array( "expires", $now + 60*60*24*30 )
        )
      ) );
      $out["refresh_token"] = $refresh;
    }

    // opportunistic cleanup
    if ( mt_rand( 0, 30 ) === 0 ){
      $db->query( "DELETE FROM `_oauth_tokens` WHERE `expires` < " . time() );
      $db->query( "DELETE FROM `_oauth_codes` WHERE `expires` < " . ( time() - 3600 ) );
    }

    return $out;

  }

  /** Valid refresh token -> new token set (refresh rotates). */
  public function refresh( $client_id, $refresh_token ){

    $this->ensure_tables();
    $db = bof()->db;
    $row = $db->_select( array(
      "table" => "_oauth_tokens",
      "where" => array(
        array( "token_hash", "=", hash( "sha256", (string)$refresh_token ) ),
        array( "client_id", "=", (string)$client_id ),
        array( "type", "=", "refresh" ),
        array( "revoked", "=", 0 ),
        array( "expires", ">", time() )
      ),
      "limit" => 1,
      "single" => true
    ) );

    if ( !$row ) return false;

    $db->_update( array(
      "table" => "_oauth_tokens",
      "set" => array( array( "revoked", 1 ) ),
      "where" => array( array( "id", "=", (int)$row["id"] ) )
    ) );

    return $this->issue_tokens( $client_id, (int)$row["user_id"], $row["scope"] ?: "profile" );

  }

  public function revoke( $token ){
    $this->ensure_tables();
    bof()->db->_update( array(
      "table" => "_oauth_tokens",
      "set" => array( array( "revoked", 1 ) ),
      "where" => array( array( "token_hash", "=", hash( "sha256", (string)$token ) ) )
    ) );
    return true;
  }

  /**
   * Bearer-token auth for /api/v1/* IyolMe endpoints.
   * Verifies JWT signature + checks the token is not revoked in _oauth_tokens.
   * Returns payload array or false.
   */
  public function verify_bearer(){

    $auth = bof()->nest->user_input( "server", "HTTP_AUTHORIZATION", "string" );
    if ( !$auth || !preg_match( "/^Bearer\s+(\S+)$/i", trim( $auth ), $m ) )
      return false;

    $payload = $this->jwt_decode( $m[1] );
    if ( !$payload ) return false;

    // revoked-check only when the token was issued by us and recorded
    $this->ensure_tables();
    $row = bof()->db->_select( array(
      "table" => "_oauth_tokens",
      "columns" => "revoked",
      "where" => array( array( "token_hash", "=", hash( "sha256", $m[1] ) ) ),
      "limit" => 1,
      "single" => true
    ) );
    if ( $row && $row["revoked"] ) return false;

    return $payload;

  }

  /* ------------------------------------------------------------------ */
  /* User payload                                                        */
  /* ------------------------------------------------------------------ */

  public function user_payload( $user_id ){

    $user = bof()->db->_select( array(
      "table" => "_u_list",
      "where" => array( array( "ID", "=", (int)$user_id ) ),
      "limit" => 1,
      "single" => true
    ) );
    if ( !$user ) return null;

    $avatar = null;
    if ( !empty( $user["avatar_id"] ) ){
      try {
        $file = bof()->object->file->select( array( "ID" => (int)$user["avatar_id"] ) );
        if ( $file ){
          $file = bof()->object->file->clean( $file, array() );
          $avatar = !empty( $file["web_address"] ) ? $file["web_address"] : null;
        }
      } catch ( \Throwable $e ) {}
    }

    return array(
      "sub"            => "u:" . $user["hash"],
      "uid"            => (int)$user["ID"],
      "username"       => $user["username"],
      "name"           => $user["name"],
      "email"          => $user["email"],
      "email_verified" => !empty( $user["time_verify"] ),
      "avatar"         => $avatar,
      "profile_url"    => web_address . "@" . $user["username"]
    );

  }

  /* ------------------------------------------------------------------ */
  /* Outbound signed API client (HiTune -> IyolMe)                       */
  /*                                                                     */
  /* Contract:                                                           */
  /*   X-HT-Client:    {client_id}                                       */
  /*   X-HT-Timestamp: unix seconds                                      */
  /*   X-HT-Signature: hex hmac_sha256( "{ts}.{raw_json_body}", secret ) */
  /* ------------------------------------------------------------------ */

  public function api_request( $method, $path, $payload=null ){

    $base = $this->api_base();
    if ( !$base ) return array( "error" => "iyol_api_base_not_set" );
    if ( !$this->client_id() || !$this->client_secret() )
      return array( "error" => "iyol_credentials_not_set" );

    $body = $payload !== null ? json_encode( $payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) : "";
    $ts   = time();
    $sig  = hash_hmac( "sha256", "{$ts}.{$body}", $this->client_secret() );

    $res = bof()->curl->exe( array(
      "url" => $base . $path,
      "posts" => ( strtoupper( $method ) === "GET" ? null : $body ),
      "posts_force_get" => ( strtoupper( $method ) === "GET" ),
      "custom_request" => strtoupper( $method ),
      "json" => true,
      "headers" => array(
        "X-HT-Client: " . $this->client_id(),
        "X-HT-Timestamp: {$ts}",
        "X-HT-Signature: {$sig}",
        "Accept: application/json"
      ),
      "cache" => false, "cache_save" => false, "cache_load" => false,
      "timeout" => 12,
      "ctimeout" => 6,
      "echo" => false
    ) );

    if ( !empty( $res["error"] ) ) return array( "error" => "connection_failed", "detail" => $res["error"] );
    if ( empty( $res["http_code"] ) || $res["http_code"] >= 500 )
      return array( "error" => "iyol_unavailable", "http_code" => $res["http_code"] );

    $data = is_array( $res["data"] ) ? $res["data"] : array( "raw" => $res["body"] );
    $data["_http_code"] = $res["http_code"];
    return $data;

  }

  /* ------------------------------------------------------------------ */
  /* Sound registry (Distribution -> IyolMe audio library)               */
  /* ------------------------------------------------------------------ */

  /** Build the sound-registry payload for a catalog track id. */
  public function sound_payload( $track_id ){

    $track = bof()->object->m_track->select(
      array( "ID" => (int)$track_id ),
      array( "_eq" => array( "cover" => array(), "album" => array( "cover" => array() ), "artist" => array() ) )
    );
    if ( !$track ) return null;

    $uploader = null;
    if ( !empty( $track["uploader_id"] ) )
      $uploader = $this->user_payload( (int)$track["uploader_id"] );

    $cover = null;
    if ( !empty( $track["bof_file_cover"]["image_thumb"] ) ) $cover = $track["bof_file_cover"]["image_thumb"];
    elseif ( !empty( $track["bof_file_cover"]["image_original"] ) ) $cover = $track["bof_file_cover"]["image_original"];
    elseif ( !empty( $track["cover"] ) && is_string( $track["cover"] ) ) $cover = $track["cover"];

    $hitune_url = !empty( $track["url"] ) ? $track["url"] : web_address . "track/" . $track["hash"];
    if ( strpos( (string)$hitune_url, "http" ) !== 0 )
      $hitune_url = web_address . ltrim( (string)$hitune_url, "/" );

    return array(
      "sound" => array(
        "hitune_track_hash" => $track["hash"],
        "title"             => $track["title"],
        "artist"            => !empty( $track["bof_dir_artist"]["name"] ) ? $track["bof_dir_artist"]["name"] : null,
        "artist_hash"       => !empty( $track["bof_dir_artist"]["hash"] ) ? $track["bof_dir_artist"]["hash"] : null,
        "album"             => !empty( $track["bof_dir_album"]["title"] ) ? $track["bof_dir_album"]["title"] : null,
        "duration_ms"       => !empty( $track["duration"] ) ? (int)$track["duration"] * 1000 : null,
        "cover"             => $cover,
        "explicit"          => !empty( $track["explicit"] ) ? true : false,
        "ai_pct"            => (int)( $track["ai_pct"] ?? 0 ),
        "ai_badge"          => !empty( $track["ai_pct"] ) ? "AI Original" : null,
        "hitune_url"        => $hitune_url,
        "attribution_label" => "Original Sound by @" . ( $uploader ? $uploader["username"] : "hitune" ) . " on HiTune Music",
        "uploader"          => $uploader ? array( "sub" => $uploader["sub"], "username" => $uploader["username"], "name" => $uploader["name"] ) : null
      )
    );

  }

  /** Queue + immediately attempt a sound registration for a catalog track id. */
  public function queue_sound( $track_id ){

    $this->ensure_tables();
    $payload = $this->sound_payload( $track_id );
    if ( !$payload ) return array( "error" => "track_not_found" );

    // dedupe: one live row per track+kind
    $existing = bof()->db->_select( array(
      "table" => "_iyol_outbox",
      "where" => array(
        array( "kind", "=", "sound_register" ),
        array( "track_id", "=", (int)$track_id ),
        array( "status", "!=", "failed" )
      ),
      "limit" => 1,
      "single" => true
    ) );
    if ( $existing ) return array( "queued" => true, "id" => (int)$existing["id"], "dedup" => true );

    bof()->db->_insert( array(
      "table" => "_iyol_outbox",
      "set" => array(
        array( "kind", "sound_register" ),
        array( "track_id", (int)$track_id ),
        array( "payload", json_encode( $payload ) )
      )
    ) );
    $id = (int) bof()->db->insert_id;

    $res = $this->_send_outbox_row( $id, "POST", "/hitune/v1/sounds", $payload );
    return array_merge( array( "id" => $id ), $res );

  }

  /** Takedown notice to IyolMe for a catalog track id. */
  public function queue_takedown( $track_id ){

    $this->ensure_tables();
    $track = bof()->object->m_track->select( array( "ID" => (int)$track_id ), array( "clean" => false ) );
    if ( !$track ) return array( "error" => "track_not_found" );

    // a pending registration for a now-removed track must not fire later
    try {
      bof()->db->query( "UPDATE `_iyol_outbox` SET status = 'failed'
        WHERE kind = 'sound_register' AND track_id = " . (int)$track_id . " AND status = 'pending'" );
    } catch ( \Throwable $e ) {}

    $payload = array( "sound" => array( "hitune_track_hash" => $track["hash"] ) );
    bof()->db->_insert( array(
      "table" => "_iyol_outbox",
      "set" => array(
        array( "kind", "sound_takedown" ),
        array( "track_id", (int)$track_id ),
        array( "payload", json_encode( $payload ) )
      )
    ) );
    $id = (int) bof()->db->insert_id;

    $res = $this->_send_outbox_row( $id, "POST", "/hitune/v1/sounds/takedown", $payload );
    return array_merge( array( "id" => $id ), $res );

  }

  /** Retry pending/failed outbox rows. Returns counts. */
  public function process_outbox( $limit=10 ){

    $this->ensure_tables();
    $rows = bof()->db->_select( array(
      "table" => "_iyol_outbox",
      "where" => array(
        array( "status", "!=", "sent" ),
        array( "attempts", "<", 10 )
      ),
      "limit" => (int)$limit,
      "single" => false,
      "cache_load_rt" => false
    ) );

    $sent = 0; $failed = 0;
    foreach( (array)$rows as $row ){
      $path = $row["kind"] === "sound_takedown" ? "/hitune/v1/sounds/takedown" : "/hitune/v1/sounds";
      $res = $this->_send_outbox_row( (int)$row["id"], "POST", $path, json_decode( (string)$row["payload"], true ) );
      !empty( $res["sent"] ) ? $sent++ : $failed++;
    }

    return array( "sent" => $sent, "failed" => $failed, "processed" => count( (array)$rows ) );

  }

  protected function _send_outbox_row( $id, $method, $path, $payload ){

    $res = $this->api_request( $method, $path, $payload );
    $ok = empty( $res["error"] ) && (int)( $res["_http_code"] ?? 0 ) < 400;

    $sets = array(
      array( "status", $ok ? "sent" : ( $this->_attempts( $id ) + 1 >= 10 ? "failed" : "pending" ) ),
      array( "attempts", $this->_attempts( $id ) + 1 ),
      array( "last_error", $ok ? "" : substr( (string)( $res["error"] ?? "http_" . ( $res["_http_code"] ?? "?" ) ), 0, 250 ) )
    );
    if ( $ok ) $sets[] = array( "time_sent", "NOW()", true );

    bof()->db->_update( array(
      "table" => "_iyol_outbox",
      "set" => $sets,
      "where" => array( array( "id", "=", (int)$id ) )
    ) );

    return array( "sent" => $ok, "response" => $res );

  }

  protected function _attempts( $id ){
    $row = bof()->db->_select( array(
      "table" => "_iyol_outbox",
      "columns" => "attempts",
      "where" => array( array( "id", "=", (int)$id ) ),
      "limit" => 1,
      "single" => true,
      "cache_load_rt" => false
    ) );
    return $row ? (int)$row["attempts"] : 0;
  }

  /* ------------------------------------------------------------------ */
  /* Reel publish (HiTune -> IyolMe)                                     */
  /* ------------------------------------------------------------------ */

  /**
   * Push a rendered reel to IyolMe. $args: media_url, media_type,
   * caption, duration_ms, thumb_url, track_hash, ai_generated, ai_pct.
   * Includes a one-time SSO code so IyolMe can bind the IyolMe account
   * to this HiTune user via /api/v1/oauth/token.
   */
  public function push_reel( $user_id, $args ){

    $user = $this->user_payload( $user_id );
    if ( !$user ) return array( "error" => "user_not_found" );

    $sso_code = $this->create_code( $this->client_id(), $user_id, null, "profile email reels" );

    $payload = array(
      "reel" => array(
        "media_url"   => $args["media_url"],
        "media_type"  => !empty( $args["media_type"] ) ? $args["media_type"] : "video",
        "thumb_url"   => !empty( $args["thumb_url"] ) ? $args["thumb_url"] : null,
        "caption"     => !empty( $args["caption"] ) ? $args["caption"] : "",
        "duration_ms" => !empty( $args["duration_ms"] ) ? (int)$args["duration_ms"] : null,
        "source"      => "hitune"
      ),
      "attribution" => null,
      "creator" => array(
        "sub" => $user["sub"], "username" => $user["username"],
        "name" => $user["name"], "avatar" => $user["avatar"],
        "profile_url" => $user["profile_url"]
      ),
      "sso_code" => $sso_code,
      "ai_disclosure" => array(
        "ai_generated" => !empty( $args["ai_generated"] ) ? true : false,
        "ai_percent"   => isset( $args["ai_pct"] ) ? (int)$args["ai_pct"] : null
      )
    );

    if ( !empty( $args["track_hash"] ) ){
      $track = bof()->object->m_track->select(
        array( "hash" => $args["track_hash"] ),
        array( "_eq" => array( "artist" => array() ) )
      );
      if ( $track ){
        $turl = !empty( $track["url"] ) ? $track["url"] : web_address . "track/" . $track["hash"];
        if ( strpos( (string)$turl, "http" ) !== 0 )
          $turl = web_address . ltrim( (string)$turl, "/" );
        $payload["attribution"] = array(
          "track_hash"  => $track["hash"],
          "title"       => $track["title"],
          "artist"      => !empty( $track["bof_dir_artist"]["name"] ) ? $track["bof_dir_artist"]["name"] : null,
          "hitune_url"  => $turl,
          "label"       => "Original Sound by @{$user["username"]} on HiTune Music"
        );
      }
    }

    return $this->api_request( "POST", "/hitune/v1/reels/create-from-hitune", $payload );

  }

  /* ------------------------------------------------------------------ */
  /* Inbound events                                                      */
  /* ------------------------------------------------------------------ */

  public function log_event( $event, $payload, $signature_ok ){
    $this->ensure_tables();
    bof()->db->_insert( array(
      "table" => "_iyol_events",
      "set" => array(
        array( "event", substr( (string)$event, 0, 60 ) ),
        array( "payload", is_string( $payload ) ? $payload : json_encode( $payload ) ),
        array( "signature_ok", $signature_ok ? 1 : 0 )
      )
    ) );
  }

  public function verify_inbound_signature( $raw_body ){

    $secret = $this->webhook_secret();
    if ( !$secret ) return false;

    $ts  = bof()->nest->user_input( "server", "HTTP_X_IYOL_TIMESTAMP", "string" );
    $sig = bof()->nest->user_input( "server", "HTTP_X_IYOL_SIGNATURE", "string" );
    if ( !$ts || !$sig ) return false;
    if ( abs( time() - (int)$ts ) > 300 ) return false; // 5 min replay window

    $expect = hash_hmac( "sha256", "{$ts}.{$raw_body}", $secret );
    return hash_equals( $expect, (string)$sig );

  }

}

?>
