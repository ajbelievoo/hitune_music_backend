<?php

if ( !defined( "bof_root" ) ) die;

/**
 * be/developer_usage_report — API usage overview (moderator+).
 * POST: days (default 30)
 * -> totals, per-app top consumers, daily timeseries
 */

function endpoint_be_developer_usage_report( $loader, $excuter, $args ){

  $db = $loader->db;

  $days = $loader->nest->user_input( "post", "days", "int", [ "min" => 1, "max" => 365 ], 30 );
  $days = $days ? $days : 30;

  // totals per app
  $per_app = array();
  $r = $db->query( "SELECT u.app_id, a.name, a.hash, p.plan_key,
      SUM(u.requests) requests, SUM(u.streams) streams
    FROM `_dev_usage` u
    LEFT JOIN `_dev_apps` a ON u.app_id = a.ID
    LEFT JOIN `_dev_plans` p ON a.plan_id = p.ID
    WHERE u.date >= DATE_SUB( CURDATE(), INTERVAL {$days} DAY )
    GROUP BY u.app_id ORDER BY requests DESC LIMIT 50" );
  while ( $r && ( $row = $r->fetch_assoc() ) )
    $per_app[] = array(
      "app_id" => (int)$row["app_id"],
      "app_name" => $row["name"],
      "app_hash" => $row["hash"],
      "plan" => $row["plan_key"],
      "requests" => (int)$row["requests"],
      "streams" => (int)$row["streams"]
    );

  // daily totals across all apps
  $daily = array();
  $r = $db->query( "SELECT date, SUM(requests) requests, SUM(streams) streams
    FROM `_dev_usage`
    WHERE date >= DATE_SUB( CURDATE(), INTERVAL {$days} DAY )
    GROUP BY date ORDER BY date ASC" );
  while ( $r && ( $row = $r->fetch_assoc() ) )
    $daily[] = array(
      "date" => $row["date"],
      "requests" => (int)$row["requests"],
      "streams" => (int)$row["streams"]
    );

  $loader->api->set_message( "ok", array(
    "period_days" => $days,
    "apps" => $per_app,
    "daily" => $daily
  ) );

}

?>
