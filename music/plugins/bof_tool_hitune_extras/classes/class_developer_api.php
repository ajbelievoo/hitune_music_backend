<?php

/**
 * HiTune Developer API — core service class.
 *
 * Powers two surfaces (see DEVELOPER_API.md):
 *  - Public REST API  /api/v1/*   — publishable key OR Bearer token auth,
 *    rate limits, monthly quotas, plan gating (no BOF signing).
 *  - Signed app API   /api/developer_* — developer portal management.
 *
 * Registered as `developer_api` in the bof_tool_hitune_extras handshake so it
 * is available in both the client and admin BOF instances.
 *
 * Tables (scripts/migration_developer_api.sql):
 *   _dev_apps, _dev_plans, _dev_tokens, _dev_usage, _dev_rate
 */

if ( !defined( "bof_root" ) ) die;

class developer_api extends bof_type_class {

  /* ------------------------------------------------------------------ */
  /* Settings                                                            */
  /* ------------------------------------------------------------------ */

  public function setting( $name, $default=null ){
    $v = bof()->object->db_setting->get( "developer_{$name}" );
    return ( $v === null || $v === false || $v === "" ) ? $default : $v;
  }

  public function portal_enabled(){
    return $this->setting( "portal_enabled", 1 ) ? true : false;
  }

  public function auto_approve(){
    return $this->setting( "auto_approve", 1 ) ? true : false;
  }

  /* ------------------------------------------------------------------ */
  /* Plans                                                               */
  /* ------------------------------------------------------------------ */

  public function plans( $active_only=true ){
    $where = $active_only ? array( array( "active", "=", 1 ) ) : null;
    $rows = bof()->db->_select( array(
      "table" => "_dev_plans",
      "where" => $where,
      "order_by" => "sort",
      "order" => "ASC",
      "limit" => false,
      "single" => false
    ) );
    return $rows ? $rows : array();
  }

  public function plan( $key_or_hash_or_id ){
    if ( !$key_or_hash_or_id ) return null;
    if ( is_numeric( $key_or_hash_or_id ) )
      $where = array( array( "ID", "=", (int)$key_or_hash_or_id ) );
    elseif ( preg_match( "/^[a-f0-9]{32}$/", $key_or_hash_or_id ) )
      $where = array( array( "hash", "=", $key_or_hash_or_id ) );
    else
      $where = array( array( "plan_key", "=", $key_or_hash_or_id ) );
    return bof()->db->_select( array(
      "table" => "_dev_plans",
      "where" => $where,
      "limit" => 1,
      "single" => true
    ) );
  }

  public function default_plan(){
    $plan = $this->plan( $this->setting( "default_plan", "sandbox" ) );
    if ( !$plan ) $plan = $this->plan( "sandbox" );
    return $plan;
  }

  public function clean_plan( $plan ){
    if ( !$plan ) return null;
    return array(
      "hash" => $plan["hash"],
      "key" => $plan["plan_key"],
      "name" => $plan["name"],
      "price" => $plan["price"] !== null ? (float)$plan["price"] : null,
      "currency" => $plan["currency"],
      "monthly_requests" => $plan["monthly_requests"] !== null ? (int)$plan["monthly_requests"] : null,
      "rate_limit" => (int)$plan["rate_limit"],
      "max_apps" => $plan["max_apps"] !== null ? (int)$plan["max_apps"] : null,
      "allow_stream" => $plan["allow_stream"] ? true : false,
      "stream_quality" => $plan["stream_quality"],
      "monthly_streams" => $plan["monthly_streams"] !== null ? (int)$plan["monthly_streams"] : null,
      "commercial_use" => $plan["commercial_use"] ? true : false,
      "embed_whitelabel" => $plan["embed_whitelabel"] ? true : false,
      "contact_sales" => $plan["contact_sales"] ? true : false,
      "features" => !empty( $plan["features"] ) ? json_decode( $plan["features"], true ) : null
    );
  }

  /* ------------------------------------------------------------------ */
  /* Apps                                                                */
  /* ------------------------------------------------------------------ */

  public function app( $hash, $user_id=null ){
    $where = array( array( "hash", "=", $hash ) );
    if ( $user_id ) $where[] = array( "user_id", "=", (int)$user_id );
    return bof()->db->_select( array(
      "table" => "_dev_apps",
      "where" => $where,
      "limit" => 1,
      "single" => true
    ) );
  }

  public function app_by_client_id( $client_id ){
    return bof()->db->_select( array(
      "table" => "_dev_apps",
      "where" => array( array( "client_id", "=", $client_id ) ),
      "limit" => 1,
      "single" => true
    ) );
  }

  public function app_by_publishable_key( $key ){
    return bof()->db->_select( array(
      "table" => "_dev_apps",
      "where" => array( array( "publishable_key", "=", $key ) ),
      "limit" => 1,
      "single" => true
    ) );
  }

  public function apps_for_user( $user_id ){
    $rows = bof()->db->_select( array(
      "table" => "_dev_apps",
      "where" => array( array( "user_id", "=", (int)$user_id ) ),
      "order_by" => "time_add",
      "order" => "DESC",
      "limit" => false,
      "single" => false
    ) );
    return $rows ? $rows : array();
  }

  public function create_app( $user_id, $args ){

    $name     = !empty( $args["name"] ) ? trim( $args["name"] ) : null;
    $website  = !empty( $args["website"] ) ? trim( $args["website"] ) : null;
    $platform = !empty( $args["platform"] ) ? $args["platform"] : "web";
    $origins  = !empty( $args["origins"] ) && is_array( $args["origins"] ) ? $args["origins"] : array();
    $bundles  = !empty( $args["bundles"] ) && is_array( $args["bundles"] ) ? $args["bundles"] : array();

    if ( !$name ) return array( "error" => "name_cant_be_empty" );
    if ( strlen( $name ) > 120 ) return array( "error" => "invalid_input" );
    if ( !in_array( $platform, array( "web", "android", "ios", "server" ), true ) )
      return array( "error" => "invalid_input" );

    // Per-plan apps cap: how many apps the user may run on the default plan
    $default_plan = $this->default_plan();
    if ( $default_plan && $default_plan["max_apps"] !== null ){
      $count = 0;
      foreach( $this->apps_for_user( $user_id ) as $_app )
        if ( (int)$_app["plan_id"] === (int)$default_plan["ID"] ) $count++;
      if ( $count >= (int)$default_plan["max_apps"] )
        return array( "error" => "app_limit", "limit" => (int)$default_plan["max_apps"], "plan" => $default_plan["plan_key"] );
    }

    $hash = md5( uniqid( "devapp", true ) . mt_rand() );
    $env  = ( defined( "production" ) ? production : true ) ? "live" : "test";
    $client_id   = "ht_{$env}_" . $this->_rand( 24 );
    $secret      = "ht_sec_" . $this->_rand( 32 );
    $publishable = "ht_pub_" . $this->_rand( 24 );

    // Website domain doubles as the first allowed origin for web apps
    if ( $website && $platform === "web" && !$origins ){
      $host = parse_url( preg_match( "/^https?:\/\//", $website ) ? $website : "https://{$website}", PHP_URL_HOST );
      if ( $host ) $origins[] = $host;
    }

    $app_id = bof()->db->_insert( array(
      "table" => "_dev_apps",
      "set" => array(
        array( "hash", $hash ),
        array( "user_id", (int)$user_id ),
        array( "name", $name ),
        array( "website", $website ),
        array( "platform", $platform ),
        array( "status", $this->auto_approve() ? "active" : "pending" ),
        array( "plan_id", $default_plan ? (int)$default_plan["ID"] : null ),
        array( "client_id", $client_id ),
        array( "client_secret_hash", hash( "sha256", $secret ) ),
        array( "publishable_key", $publishable ),
        array( "allowed_origins", $origins ? json_encode( array_values( $origins ) ) : null ),
        array( "allowed_bundles", $bundles ? json_encode( array_values( $bundles ) ) : null )
      )
    ) );

    if ( !$app_id ) return array( "error" => "failed" );

    $app = $this->app( $hash );
    $out = $this->clean_app( $app );
    $out["client_secret"] = $secret; // shown once, never stored in plain text
    return array( "app" => $out );
  }

  public function update_app( $app, $args ){

    $set = array();
    if ( isset( $args["name"] ) && $args["name"] !== "" )
      $set[] = array( "name", substr( trim( $args["name"] ), 0, 120 ) );
    if ( isset( $args["website"] ) )
      $set[] = array( "website", $args["website"] ? substr( trim( $args["website"] ), 0, 255 ) : null );
    if ( isset( $args["origins"] ) && is_array( $args["origins"] ) )
      $set[] = array( "allowed_origins", $args["origins"] ? json_encode( array_values( $args["origins"] ) ) : null );
    if ( isset( $args["bundles"] ) && is_array( $args["bundles"] ) )
      $set[] = array( "allowed_bundles", $args["bundles"] ? json_encode( array_values( $args["bundles"] ) ) : null );

    if ( !$set ) return false;

    bof()->db->_update( array(
      "table" => "_dev_apps",
      "set" => $set,
      "where" => array( array( "ID", "=", (int)$app["ID"] ) )
    ) );
    return true;
  }

  public function delete_app( $app ){
    bof()->db->_delete( array(
      "table" => "_dev_tokens",
      "where" => array( array( "app_id", "=", (int)$app["ID"] ) )
    ) );
    bof()->db->_delete( array(
      "table" => "_dev_apps",
      "where" => array( array( "ID", "=", (int)$app["ID"] ) ),
      "limit" => 1
    ) );
    return true;
  }

  public function regenerate_key( $app, $which ){

    if ( $which === "secret" ){
      $secret = "ht_sec_" . $this->_rand( 32 );
      bof()->db->_update( array(
        "table" => "_dev_apps",
        "set" => array( array( "client_secret_hash", hash( "sha256", $secret ) ) ),
        "where" => array( array( "ID", "=", (int)$app["ID"] ) )
      ) );
      // existing bearer tokens die with the secret
      bof()->db->_delete( array(
        "table" => "_dev_tokens",
        "where" => array( array( "app_id", "=", (int)$app["ID"] ) )
      ) );
      return array( "client_secret" => $secret );
    }

    if ( $which === "publishable" ){
      $key = "ht_pub_" . $this->_rand( 24 );
      bof()->db->_update( array(
        "table" => "_dev_apps",
        "set" => array( array( "publishable_key", $key ) ),
        "where" => array( array( "ID", "=", (int)$app["ID"] ) )
      ) );
      return array( "publishable_key" => $key );
    }

    return false;
  }

  public function clean_app( $app ){
    if ( !$app ) return null;
    $plan = $app["plan_id"] ? $this->plan( (int)$app["plan_id"] ) : null;
    return array(
      "hash" => $app["hash"],
      "name" => $app["name"],
      "website" => $app["website"],
      "platform" => $app["platform"],
      "status" => $app["status"],
      "plan" => $plan ? $plan["plan_key"] : null,
      "plan_name" => $plan ? $plan["name"] : null,
      "plan_expire" => !empty( $app["plan_expire"] ) ? strtotime( $app["plan_expire"] ) : null,
      "client_id" => $app["client_id"],
      "publishable_key" => $app["publishable_key"],
      "allowed_origins" => !empty( $app["allowed_origins"] ) ? json_decode( $app["allowed_origins"], true ) : array(),
      "allowed_bundles" => !empty( $app["allowed_bundles"] ) ? json_decode( $app["allowed_bundles"], true ) : array(),
      "created_at" => strtotime( $app["time_add"] ),
      "usage" => $this->usage_summary( (int)$app["ID"] )
    );
  }

  /* ------------------------------------------------------------------ */
  /* Tokens (client_credentials grant)                                   */
  /* ------------------------------------------------------------------ */

  public function issue_token( $client_id, $client_secret ){

    $app = $this->app_by_client_id( $client_id );
    if ( !$app ) return array( "error" => "invalid_client" );
    if ( $app["status"] !== "active" ) return array( "error" => "app_not_active", "status" => $app["status"] );
    if ( !hash_equals( $app["client_secret_hash"], hash( "sha256", (string)$client_secret ) ) )
      return array( "error" => "invalid_client" );

    $token = "ht_tok_" . $this->_rand( 40 );
    $expires = time() + 3600;

    bof()->db->_insert( array(
      "table" => "_dev_tokens",
      "set" => array(
        array( "app_id", (int)$app["ID"] ),
        array( "token_hash", hash( "sha256", $token ) ),
        array( "time_expire", bof()->general->mysql_timestamp( $expires ) )
      )
    ) );

    // opportunistic cleanup of expired tokens
    if ( mt_rand( 0, 20 ) === 0 )
      bof()->db->_delete( array(
        "table" => "_dev_tokens",
        "where" => array( array( "time_expire", "<", bof()->general->mysql_timestamp() ) )
      ) );

    return array(
      "access_token" => $token,
      "token_type" => "bearer",
      "expires_in" => 3600,
      "expires_at" => $expires
    );
  }

  public function app_by_token( $token ){
    $row = bof()->db->_select( array(
      "table" => "_dev_tokens",
      "where" => array(
        array( "token_hash", "=", hash( "sha256", $token ) ),
        array( "time_expire", ">", bof()->general->mysql_timestamp() )
      ),
      "limit" => 1,
      "single" => true
    ) );
    if ( !$row ) return null;
    return bof()->db->_select( array(
      "table" => "_dev_apps",
      "where" => array( array( "ID", "=", (int)$row["app_id"] ) ),
      "limit" => 1,
      "single" => true
    ) );
  }

  /* ------------------------------------------------------------------ */
  /* Public v1 auth: publishable key or Bearer token                     */
  /* ------------------------------------------------------------------ */

  /**
   * Authenticate a /api/v1/* request. Emits the JSON error + HTTP code and
   * returns false on failure; returns array( app, plan, mode ) on success.
   *
   * $args: "modes"  => which credentials are accepted (default both)
   *        "stream" => count this call against the monthly stream quota
   */
  public function v1_auth( $args=array() ){

    $modes = !empty( $args["modes"] ) ? $args["modes"] : array( "publishable", "token" );

    if ( !$this->portal_enabled() )
      return $this->v1_error( "api_disabled", 503, "Developer API is disabled" );

    // --- resolve credential ---
    $app = null;
    $mode = null;

    $auth_header = bof()->nest->user_input( "server", "HTTP_AUTHORIZATION", "string" );
    $api_key = bof()->nest->user_input( "http_header", "x-api-key", "string" );
    if ( !$api_key ) $api_key = bof()->nest->user_input( "get", "key", "string" );
    if ( !$api_key ) $api_key = bof()->nest->user_input( "get", "api_key", "string" );

    if ( $auth_header && preg_match( "/^Bearer\s+(\S+)$/i", trim( $auth_header ), $m ) ){
      if ( !in_array( "token", $modes, true ) )
        return $this->v1_error( "forbidden", 403, "Bearer tokens are not accepted on this endpoint" );
      $app = $this->app_by_token( $m[1] );
      $mode = "token";
      if ( !$app )
        return $this->v1_error( "invalid_key", 401, "Invalid or expired access token" );
    }
    elseif ( $api_key ){
      if ( !in_array( "publishable", $modes, true ) )
        return $this->v1_error( "forbidden", 403, "Publishable keys are not accepted on this endpoint" );
      $app = $this->app_by_publishable_key( $api_key );
      $mode = "publishable";
      if ( !$app )
        return $this->v1_error( "invalid_key", 401, "Invalid API key" );
      if ( !$this->_check_origin( $app ) )
        return $this->v1_error( "forbidden", 403, "Origin not allowed for this key" );
    }
    else {
      return $this->v1_error( "invalid_key", 401, "Missing API key — send x-api-key or Authorization: Bearer" );
    }

    if ( $app["status"] === "suspended" )
      return $this->v1_error( "forbidden", 403, "Application suspended" );
    if ( $app["status"] === "pending" )
      return $this->v1_error( "forbidden", 403, "Application is pending approval" );

    // paid plan lapsed -> fall back to the free plan's limits
    $plan = $app["plan_id"] ? $this->plan( (int)$app["plan_id"] ) : null;
    if ( $plan && !empty( $app["plan_expire"] ) && strtotime( $app["plan_expire"] ) < time() && (float)$plan["price"] > 0 )
      $plan = $this->plan( "sandbox" );
    if ( !$plan ) $plan = $this->plan( "sandbox" );

    // --- rate limit (per-minute sliding window) ---
    $rate = $this->rate_hit( (int)$app["ID"], (int)$plan["rate_limit"] );
    $this->_rate_headers( $rate, $app, $plan );

    if ( !$rate["allowed"] )
      return $this->v1_error( "rate_limited", 429, "Rate limit exceeded — slow down", array(
        "retry_after" => $rate["reset"] - time()
      ) );

    // --- monthly quota ---
    $quota = $this->quota( $app, $plan, !empty( $args["stream"] ) );
    if ( !$quota["allowed"] )
      return $this->v1_error( "quota_exceeded", 402, "Monthly quota exhausted", array(
        "plan" => $plan["plan_key"],
        "upgrade_url" => web_address . "subscription_plans"
      ) );

    // --- count usage ---
    $this->usage_hit( (int)$app["ID"], !empty( $args["stream"] ) );

    // CORS for browser calls from registered origins
    $this->_cors( $app );

    return array( "app" => $app, "plan" => $plan, "mode" => $mode );
  }

  /* ------------------------------------------------------------------ */
  /* Rate limiting + quotas                                              */
  /* ------------------------------------------------------------------ */

  public function rate_hit( $app_id, $limit_per_min ){

    $app_id = (int)$app_id;
    $bucket = (int)floor( time() / 60 );
    $reset  = ( $bucket + 1 ) * 60;
    $db = bof()->db;

    $db->query( "INSERT INTO `_dev_rate` (app_id, bucket, requests) VALUES ({$app_id}, {$bucket}, 1)
      ON DUPLICATE KEY UPDATE requests = requests + 1" );
    $r = $db->query( "SELECT requests FROM `_dev_rate` WHERE app_id = {$app_id} AND bucket = {$bucket}" );
    $used = ( $r && $r->num_rows ) ? (int)$r->fetch_assoc()["requests"] : 1;

    // drop stale buckets ~1% of calls
    if ( mt_rand( 1, 100 ) === 1 )
      $db->query( "DELETE FROM `_dev_rate` WHERE bucket < " . ( $bucket - 3 ) );

    $limit = $limit_per_min > 0 ? $limit_per_min : 10;
    return array(
      "allowed" => $used <= $limit,
      "limit" => $limit,
      "remaining" => max( 0, $limit - $used ),
      "reset" => $reset
    );
  }

  public function quota( $app, $plan, $is_stream=false ){

    $used = $this->usage_summary( (int)$app["ID"] );
    $req_cap = $plan["monthly_requests"] !== null ? (int)$plan["monthly_requests"] : null;
    $str_cap = $plan["monthly_streams"] !== null ? (int)$plan["monthly_streams"] : null;

    $allowed = true;
    if ( $req_cap !== null && $used["requests"] >= $req_cap ) $allowed = false;
    if ( $is_stream && $str_cap !== null && $used["streams"] >= $str_cap ) $allowed = false;
    if ( $is_stream && !$plan["allow_stream"] ) $allowed = false;

    return array(
      "allowed" => $allowed,
      "requests_used" => $used["requests"],
      "requests_cap" => $req_cap,
      "streams_used" => $used["streams"],
      "streams_cap" => $str_cap
    );
  }

  public function usage_hit( $app_id, $is_stream=false ){
    $app_id = (int)$app_id;
    $db = bof()->db;
    $db->query( "INSERT INTO `_dev_usage` (app_id, date, requests, streams)
      VALUES ({$app_id}, CURDATE(), 1, " . ( $is_stream ? 1 : 0 ) . ")
      ON DUPLICATE KEY UPDATE requests = requests + 1" . ( $is_stream ? ", streams = streams + 1" : "" ) );
  }

  public function usage_summary( $app_id ){
    $app_id = (int)$app_id;
    $r = bof()->db->query( "SELECT COALESCE(SUM(requests),0) rq, COALESCE(SUM(streams),0) st
      FROM `_dev_usage` WHERE app_id = {$app_id} AND date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')" );
    $row = ( $r && $r->num_rows ) ? $r->fetch_assoc() : array( "rq" => 0, "st" => 0 );
    return array( "requests" => (int)$row["rq"], "streams" => (int)$row["st"] );
  }

  public function usage_daily( $app_id, $days=30 ){
    $app_id = (int)$app_id;
    $days = max( 1, min( 365, (int)$days ) );
    $r = bof()->db->query( "SELECT date, requests, streams FROM `_dev_usage`
      WHERE app_id = {$app_id} AND date >= DATE_SUB(CURDATE(), INTERVAL {$days} DAY)
      ORDER BY date ASC" );
    $out = array();
    while ( $r && ( $row = $r->fetch_assoc() ) )
      $out[] = array( "date" => $row["date"], "requests" => (int)$row["requests"], "streams" => (int)$row["streams"] );
    return $out;
  }

  /* ------------------------------------------------------------------ */
  /* v1 responses                                                        */
  /* ------------------------------------------------------------------ */

  public function v1_error( $code, $http, $message, $extra=array() ){
    bof()->response->json->disableMessage();
    if ( !headers_sent() ) http_response_code( $http );
    bof()->response->json->set( array(
      "error" => array_merge( array( "code" => $code, "message" => $message ), $extra )
    ) );
    return false;
  }

  public function v1_send( $data, $http=200 ){
    bof()->response->json->disableMessage();
    if ( !headers_sent() ) http_response_code( $http );
    bof()->response->json->set( $data );
  }

  /* ------------------------------------------------------------------ */
  /* Signed stream tokens (v1 stream endpoint)                           */
  /* ------------------------------------------------------------------ */

  public function sign_stream_token( $app_id, $track_hash, $ttl=14400 ){
    $payload = array(
      "a" => (int)$app_id,
      "t" => $track_hash,
      "e" => time() + (int)$ttl
    );
    $b64 = rtrim( strtr( base64_encode( json_encode( $payload ) ), "+/", "-_" ), "=" );
    $sig = hash_hmac( "sha256", $b64, "devstream:" . sign_key );
    return $b64 . "." . substr( $sig, 0, 43 );
  }

  public function verify_stream_token( $token ){
    $parts = explode( ".", (string)$token );
    if ( count( $parts ) !== 2 ) return false;
    $sig = hash_hmac( "sha256", $parts[0], "devstream:" . sign_key );
    if ( !hash_equals( substr( $sig, 0, 43 ), $parts[1] ) ) return false;
    $payload = json_decode( base64_decode( strtr( $parts[0], "-_", "+/" ) ), true );
    if ( !is_array( $payload ) || empty( $payload["t"] ) || empty( $payload["e"] ) ) return false;
    if ( $payload["e"] < time() ) return false;
    return $payload;
  }

  /* ------------------------------------------------------------------ */
  /* v1 payload formatters                                               */
  /* ------------------------------------------------------------------ */

  public function track_payload( $item, $args=array() ){

    $artists = array();
    if ( !empty( $item["bof_dir_artist"] ) ){
      $artists[] = array(
        "hash" => !empty( $item["bof_dir_artist"]["hash"] ) ? $item["bof_dir_artist"]["hash"] : null,
        "name" => !empty( $item["bof_dir_artist"]["name"] ) ? $item["bof_dir_artist"]["name"] : ( !empty( $item["bof_dir_artist"]["title"] ) ? $item["bof_dir_artist"]["title"] : null )
      );
    }

    $album = null;
    if ( !empty( $item["bof_dir_album"] ) ){
      $album = array(
        "hash" => !empty( $item["bof_dir_album"]["hash"] ) ? $item["bof_dir_album"]["hash"] : null,
        "title" => !empty( $item["bof_dir_album"]["title"] ) ? $item["bof_dir_album"]["title"] : null,
        "cover" => $this->_cover( $item["bof_dir_album"] )
      );
    }

    $out = array(
      "hash" => $item["hash"],
      "title" => $item["title"],
      "artists" => $artists,
      "album" => $album,
      "duration_ms" => !empty( $item["duration"] ) ? (int)$item["duration"] * 1000 : null,
      "explicit" => !empty( $item["explicit"] ) ? true : false,
      "cover" => $this->_cover( $item ),
      "preview_url" => $this->preview_url( $item ),
      "links" => array(
        "hitune" => !empty( $item["url"] ) ? $item["url"] : web_address . "track/" . $item["hash"]
      )
    );

    if ( !empty( $item["lufs"] ) || !empty( $item["peak_db"] ) )
      $out["loudness"] = array(
        "lufs" => $item["lufs"] !== null ? (float)$item["lufs"] : null,
        "peak_db" => $item["peak_db"] !== null ? (float)$item["peak_db"] : null
      );

    return $out;
  }

  public function album_payload( $item, $tracks=array() ){
    return array(
      "hash" => $item["hash"],
      "title" => $item["title"],
      "artist" => !empty( $item["bof_dir_artist"] ) ? array(
        "hash" => !empty( $item["bof_dir_artist"]["hash"] ) ? $item["bof_dir_artist"]["hash"] : null,
        "name" => !empty( $item["bof_dir_artist"]["name"] ) ? $item["bof_dir_artist"]["name"] : null
      ) : null,
      "cover" => $this->_cover( $item ),
      "total_tracks" => !empty( $item["s_tracks"] ) ? (int)$item["s_tracks"] : count( $tracks ),
      "release_date" => !empty( $item["time_release"] ) ? substr( $item["time_release"], 0, 10 ) : null,
      "tracks" => $tracks,
      "links" => array(
        "hitune" => !empty( $item["url"] ) ? $item["url"] : web_address . "album/" . $item["hash"]
      )
    );
  }

  public function artist_payload( $item, $top_tracks=array(), $albums=array() ){
    $out = array(
      "hash" => $item["hash"],
      "name" => !empty( $item["name"] ) ? $item["name"] : ( !empty( $item["title"] ) ? $item["title"] : null ),
      "cover" => $this->_cover( $item ),
      "links" => array(
        "hitune" => !empty( $item["url"] ) ? $item["url"] : web_address . "artist/" . $item["hash"]
      )
    );
    if ( $top_tracks ) $out["top_tracks"] = $top_tracks;
    if ( $albums ) $out["albums"] = $albums;
    return $out;
  }

  public function playlist_payload( $item, $tracks=array() ){
    return array(
      "hash" => $item["hash"],
      "title" => !empty( $item["name"] ) ? $item["name"] : ( !empty( $item["title"] ) ? $item["title"] : null ),
      "description" => !empty( $item["description"] ) ? $item["description"] : null,
      "cover" => $this->_cover( $item ),
      "total_tracks" => !empty( $item["s_items"] ) ? (int)$item["s_items"] : count( $tracks ),
      "tracks" => $tracks,
      "links" => array(
        "hitune" => !empty( $item["url"] ) ? $item["url"] : web_address . "playlist/" . $item["hash"]
      )
    );
  }

  /* ------------------------------------------------------------------ */
  /* Stream resolution (trimmed `play` endpoint logic)                   */
  /* ------------------------------------------------------------------ */

  public function load_track( $hash, $with_sources=true ){

    $sel = array( "hash" => $hash );
    $opt = array(
      "_eq" => array(
        "cover" => array(),
        "album" => array( "cover" => array() ),
        "artist" => array()
      )
    );
    if ( $with_sources ){
      $opt["muse_source"] = true;
      $opt["_eq"]["sources"] = array();
    }
    return bof()->object->m_track->select( $sel, $opt );
  }

  /**
   * Resolve a playable URL for a cleaned m_track item.
   * Mirrors endpoint_muse_play: local/remote source -> stream cache ->
   * live piped/yt-dlp -> saavn -> iTunes preview.
   */
  public function resolve_stream( $object_item, $prefer=null ){

    $duration = !empty( $object_item["duration"] ) ? intval( $object_item["duration"] ) : null;
    $playable = null;
    $youtube_id = !empty( $object_item["youtube_id"] ) ? $object_item["youtube_id"] : null;

    if ( !empty( $object_item["sources"] ) ){
      foreach( $object_item["sources"] as $source_G ){

        $sources_by_type = bof()->source->get( "stream", $source_G["ot"], $source_G["raw"], $source_G["sources"], "stream" );
        if ( !$sources_by_type || $sources_by_type === "pending" || empty( $sources_by_type["user"] ) )
          continue;

        $_muse = $sources_by_type["user"]["muse"];
        $_t = !empty( $_muse["type"] ) ? $_muse["type"] : null;
        if ( !is_array( $_t ) || empty( $_t[0] ) )
          continue;

        if ( ( $_t[0] == "audio" || $_t[0] == "video" ) && is_array( $_t[1] ) && !empty( $_t[1]["address"] ) ){

          $_address = bof()->general->https_url( $_t[1]["address"] );

          if ( !empty( $sources_by_type["user"]["protected"] ) && preg_match( "/\/files\/protected\//", $_address ) ){
            $_address = bof()->source->grant_access( $source_G["ot"], $source_G["raw"]["hash"], $sources_by_type["user"]["hash"], $_address, "20 MINUTE" );
          }

          $playable = array(
            "url" => $_address,
            "type" => $_t[0],
            "mime" => !empty( $_t[1]["mime"] ) ? $_t[1]["mime"] : ( !empty( $_t[1]["format"] ) ? $_t[1]["format"] : null ),
            "hls" => !empty( $_t[1]["hls"] ) ? true : false,
            "cached" => true
          );
          break;
        }

        if ( $_t[0] == "youtube" || ( is_array( $_t[1] ) ? !empty( $_t[1]["raaz"] ) : false ) ){
          if ( !$youtube_id && is_array( $_t[1] ) )
            $youtube_id = !empty( $_t[1]["ID"] ) ? $_t[1]["ID"] : ( !empty( $_t[1]["youtube_id"] ) ? $_t[1]["youtube_id"] : null );
        }

      }
    }

    if ( !$youtube_id && !empty( $object_item["bof_dir_sources"] ) ){
      foreach( $object_item["bof_dir_sources"] as $_source ){
        if ( $_source["type"] == "youtube" && !empty( $_source["data_decoded"]["youtube_id"] ) ){
          $youtube_id = $_source["data_decoded"]["youtube_id"];
          break;
        }
      }
    }

    if ( !$playable && $youtube_id ){

      if ( empty( $object_item["youtube_id"] ) )
        bof()->music->set_track_youtube_id( $object_item["ID"], $youtube_id );

      try {
        $stream = bof()->youtube_piped->set_setting()->get_stream_cached( $youtube_id, array(
          "prefer" => $prefer
        ) );
        if ( $stream ){
          $playable = array(
            "url" => $stream["url"],
            "type" => $stream["type"],
            "mime" => $stream["mime"],
            "duration" => !empty( $stream["duration"] ) ? $stream["duration"] : $duration,
            "expires" => !empty( $stream["expire"] ) ? $stream["expire"] : null,
            "cached" => !empty( $stream["cached"] ) ? true : false
          );
        }
      } catch( Exception | bofException | Error $err ){}

    }

    if ( !$playable ){
      try {
        $_sv = bof()->hitune_saavn->resolve(
          $object_item["title"],
          !empty( $object_item["bof_dir_artist"]["name"] ) ? $object_item["bof_dir_artist"]["name"] : null,
          $duration
        );
        if ( $_sv && !empty( $_sv["url"] ) ){
          $playable = array(
            "url" => $_sv["url"],
            "type" => "audio",
            "mime" => $_sv["mime"],
            "duration" => $_sv["duration"],
            "saavn" => true,
            "cached" => false
          );
        }
      } catch( Exception | bofException | Error $err ){}
    }

    if ( !$playable ){
      // last resort: iTunes ~30s preview
      try {
        $_q = trim( $object_item["title"] . " " . ( !empty( $object_item["bof_dir_artist"]["name"] ) ? $object_item["bof_dir_artist"]["name"] : "" ) );
        $_ctx = stream_context_create( array( "http" => array( "timeout" => 8 ) ) );
        $_it = @json_decode( @file_get_contents( "https://itunes.apple.com/search?term=" . urlencode( $_q ) . "&entity=song&limit=5", false, $_ctx ), true );
        if ( !empty( $_it["results"] ) ){
          $_best = null; $_best_score = 0;
          foreach( $_it["results"] as $_r ){
            if ( empty( $_r["previewUrl"] ) ) continue;
            $_score = 0;
            similar_text( strtolower( $_r["trackName"] ), strtolower( $object_item["title"] ), $_t_pct );
            $_score += $_t_pct;
            if ( !empty( $_r["artistName"] ) && !empty( $object_item["bof_dir_artist"]["name"] ) ){
              similar_text( strtolower( $_r["artistName"] ), strtolower( $object_item["bof_dir_artist"]["name"] ), $_a_pct );
              $_score += $_a_pct * 0.5;
            }
            if ( $_score > $_best_score ){ $_best_score = $_score; $_best = $_r; }
          }
          if ( $_best && $_best_score > 60 )
            $playable = array(
              "url" => $_best["previewUrl"],
              "type" => "audio",
              "mime" => "audio/mp4",
              "preview" => true,
              "duration" => $duration ? $duration : 30,
              "cached" => false
            );
        }
      } catch( Exception $err ){}
    }

    if ( $playable ) $playable["youtube_id"] = $youtube_id;
    return $playable;
  }

  /**
   * Best-effort 30s/preview URL for a track — preview-flagged or known
   * preview-CDN sources only; never a protected local file.
   */
  public function preview_url( $object_item ){

    if ( empty( $object_item["sources"] ) ) return null;

    foreach( $object_item["sources"] as $source_G ){

      $sources_by_type = bof()->source->get( "stream", $source_G["ot"], $source_G["raw"], $source_G["sources"], "stream" );
      if ( !$sources_by_type || $sources_by_type === "pending" || empty( $sources_by_type["user"]["muse"]["type"] ) )
        continue;

      $_t = $sources_by_type["user"]["muse"]["type"];
      if ( !is_array( $_t ) || empty( $_t[0] ) || $_t[0] !== "audio" || empty( $_t[1]["address"] ) )
        continue;

      $address = bof()->general->https_url( $_t[1]["address"] );

      // never expose protected local files as "preview"
      if ( preg_match( "/\/files\/protected\//", $address ) ) continue;

      $is_preview = !empty( $_t[1]["preview"] )
        || preg_match( "/(itunes|mzstatic|apple)/i", $address );

      if ( $is_preview ) return $address;
    }

    return null;
  }

  /* ------------------------------------------------------------------ */
  /* Internals                                                           */
  /* ------------------------------------------------------------------ */

  protected function _rand( $len ){
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $out = "";
    $max = strlen( $chars ) - 1;
    for( $i = 0; $i < $len; $i++ ) $out .= $chars[ random_int( 0, $max ) ];
    return $out;
  }

  protected function _cover( $item ){
    if ( !empty( $item["bof_file_cover"]["image_thumb"] ) ) return $item["bof_file_cover"]["image_thumb"];
    if ( !empty( $item["bof_file_cover"]["image_original"] ) ) return $item["bof_file_cover"]["image_original"];
    if ( !empty( $item["cover"] ) && is_string( $item["cover"] ) ) return $item["cover"];
    return null;
  }

  protected function _check_origin( $app ){

    $origins = !empty( $app["allowed_origins"] ) ? json_decode( $app["allowed_origins"], true ) : array();
    if ( !is_array( $origins ) ) $origins = array();

    $origin_raw = !empty( $_SERVER["HTTP_ORIGIN"] ) ? $_SERVER["HTTP_ORIGIN"] : ( !empty( $_SERVER["HTTP_REFERER"] ) ? $_SERVER["HTTP_REFERER"] : null );
    $host = $origin_raw ? parse_url( $origin_raw, PHP_URL_HOST ) : null;

    // non-browser call (no Origin/Referer): publishable keys on web apps are
    // browser-side credentials — without an origin header they must be
    // rejected (server-side callers should use a Bearer token)
    if ( !$host )
      return $app["platform"] === "web" ? false : true;

    foreach( $origins as $allowed ){
      $allowed = strtolower( trim( $allowed ) );
      if ( !$allowed ) continue;
      $allowed = preg_replace( "/^https?:\/\//", "", $allowed );
      $allowed = rtrim( $allowed, "/" );
      if ( strcasecmp( $host, $allowed ) === 0 ) return true;
      if ( substr( $allowed, 0, 2 ) === "*." && substr( strtolower( $host ), -strlen( $allowed ) + 1 ) === substr( $allowed, 1 ) ) return true;
    }

    return false;
  }

  protected function _cors( $app ){

    if ( headers_sent() ) return;
    $origin = !empty( $_SERVER["HTTP_ORIGIN"] ) ? $_SERVER["HTTP_ORIGIN"] : null;
    if ( !$origin ) return;

    $origins = !empty( $app["allowed_origins"] ) ? json_decode( $app["allowed_origins"], true ) : array();
    $host = parse_url( $origin, PHP_URL_HOST );

    $ok = false;
    foreach( ( is_array( $origins ) ? $origins : array() ) as $allowed ){
      $allowed = strtolower( preg_replace( "/^https?:\/\//", "", rtrim( trim( $allowed ), "/" ) ) );
      if ( $allowed && ( strcasecmp( $host, $allowed ) === 0 || ( substr( $allowed, 0, 2 ) === "*." && substr( strtolower( $host ), -strlen( $allowed ) + 1 ) === substr( $allowed, 1 ) ) ) ){
        $ok = true; break;
      }
    }

    if ( $ok ){
      header( "Access-Control-Allow-Origin: {$origin}" );
      header( "Vary: Origin" );
      header( "Access-Control-Allow-Headers: x-api-key, authorization, content-type" );
      header( "Access-Control-Allow-Methods: GET, POST, OPTIONS" );
    }
  }

  protected function _rate_headers( $rate, $app, $plan ){
    if ( headers_sent() ) return;
    header( "X-RateLimit-Limit: " . $rate["limit"] );
    header( "X-RateLimit-Remaining: " . $rate["remaining"] );
    header( "X-RateLimit-Reset: " . $rate["reset"] );
    if ( $plan["monthly_requests"] !== null ){
      $used = $this->usage_summary( (int)$app["ID"] );
      header( "X-Quota-Limit: " . (int)$plan["monthly_requests"] );
      header( "X-Quota-Remaining: " . max( 0, (int)$plan["monthly_requests"] - $used["requests"] ) );
    }
  }

}

?>
