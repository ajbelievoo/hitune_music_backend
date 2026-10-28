<?php

if ( !defined( "bof_root" ) ) die;

/**
 * be/developer_apps — list developer applications (moderator+).
 * POST fields: status, plan, search, page
 */

function endpoint_be_developer_apps( $loader, $excuter, $args ){

  $db = $loader->db;

  $status  = $loader->nest->user_input( "post", "status", "in_array", [ "values" => [ "pending", "active", "suspended" ] ] );
  $plan    = $loader->nest->user_input( "post", "plan", "string_abcd" );
  $search  = $loader->nest->user_input( "post", "search", "string" );
  $page    = $loader->nest->user_input( "post", "page", "int", [ "min" => 1 ], 1 );
  $page    = $page ? $page : 1;
  $limit   = 30;
  $offset  = ( $page - 1 ) * $limit;

  $where = "WHERE 1=1";
  if ( $status ) $where .= " AND a.status = '" . $db->real_escape_string( $status ) . "'";
  if ( $search ){
    $esc = $db->real_escape_string( $search );
    $where .= " AND ( a.name LIKE '%{$esc}%' OR a.client_id LIKE '%{$esc}%' OR u.email LIKE '%{$esc}%' OR u.username LIKE '%{$esc}%' )";
  }
  if ( $plan ){
    $p = $loader->developer_api->plan( $plan );
    $where .= $p ? " AND a.plan_id = " . (int)$p["ID"] : " AND a.plan_id = -1";
  }

  $apps = array();
  $r = $db->query( "SELECT a.*, u.username, u.email, p.plan_key, p.name AS plan_name
    FROM `_dev_apps` a
    LEFT JOIN `_u_list` u ON a.user_id = u.ID
    LEFT JOIN `_dev_plans` p ON a.plan_id = p.ID
    {$where} ORDER BY a.time_add DESC LIMIT {$offset}, {$limit}" );

  while ( $r && ( $row = $r->fetch_assoc() ) ){
    $apps[] = array(
      "id" => (int)$row["ID"],
      "hash" => $row["hash"],
      "name" => $row["name"],
      "website" => $row["website"],
      "platform" => $row["platform"],
      "status" => $row["status"],
      "plan" => $row["plan_key"],
      "plan_name" => $row["plan_name"],
      "plan_expire" => $row["plan_expire"],
      "client_id" => $row["client_id"],
      "publishable_key" => $row["publishable_key"],
      "allowed_origins" => !empty( $row["allowed_origins"] ) ? json_decode( $row["allowed_origins"], true ) : array(),
      "user" => array(
        "id" => (int)$row["user_id"],
        "username" => $row["username"],
        "email" => $row["email"]
      ),
      "created_at" => $row["time_add"],
      "usage" => $loader->developer_api->usage_summary( (int)$row["ID"] )
    );
  }

  $total = 0;
  $c = $db->query( "SELECT COUNT(*) c FROM `_dev_apps` a LEFT JOIN `_u_list` u ON a.user_id = u.ID {$where}" );
  if ( $c && $c->num_rows ) $total = (int)$c->fetch_assoc()["c"];

  $plans = array();
  foreach( $loader->developer_api->plans( false ) as $_p )
    $plans[] = $loader->developer_api->clean_plan( $_p );

  $loader->api->set_message( "ok", array(
    "apps" => $apps,
    "total" => $total,
    "page" => $page,
    "plans" => $plans,
    "settings" => array(
      "auto_approve" => $loader->developer_api->auto_approve(),
      "default_plan" => $loader->developer_api->setting( "default_plan", "sandbox" ),
      "portal_enabled" => $loader->developer_api->portal_enabled()
    )
  ) );

}

?>
