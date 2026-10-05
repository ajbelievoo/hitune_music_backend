<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_analytics_report( $loader, $excuter, $args ){

  if ( !bof()->user->get()->data || !in_array( "admin", bof()->user->get()->groups, true ) ){
    $loader->api->set_error( "no_access", [ "output_args" => [ "turn" => false ] ] );
    return;
  }

  $range = $loader->nest->user_input( "get", "range", "in_array", [ "values" => [ "24h", "7d", "30d", "90d", "all" ], "empty" => true ] );
  $range = $range ? $range : "30d";

  $interval = null;
  if ( $range == "24h" ) $interval = "1 HOUR";
  elseif ( $range == "7d" ) $interval = "1 DAY";
  elseif ( $range == "30d" ) $interval = "1 DAY";
  elseif ( $range == "90d" ) $interval = "1 WEEK";

  $since = null;
  if ( $range != "all" ){
    $hours = [ "24h" => 24, "7d" => 168, "30d" => 720, "90d" => 2160 ];
    $since = date( "Y-m-d H:i:s", strtotime( "-{$hours[$range]} hours" ) );
  }

  $where = $since ? "WHERE time_add > '" . bof()->db->real_escape_string( $since ) . "'" : "";

  $events_by_type = bof()->db->query(
    "SELECT event, COUNT(*) as cnt FROM _u_analytics_events {$where} GROUP BY event ORDER BY cnt DESC",
    null,
    true
  );
  $events = [];
  if ( $events_by_type ){
    while( $row = $events_by_type->fetch_assoc() ) $events[ $row["event"] ] = (int) $row["cnt"];
  }

  $top_tracks = bof()->db->query(
    "SELECT object_id, COUNT(*) as cnt FROM _u_analytics_events {$where} AND event='play' AND object_type='track' GROUP BY object_id ORDER BY cnt DESC LIMIT 20",
    null,
    true
  );
  $tracks = [];
  if ( $top_tracks ){
    while( $row = $top_tracks->fetch_assoc() ) $tracks[] = [ "track_id" => (int) $row["object_id"], "plays" => (int) $row["cnt"] ];
  }

  $top_albums = bof()->db->query(
    "SELECT object_id, COUNT(*) as cnt FROM _u_analytics_events {$where} AND event='play' AND object_type='album' GROUP BY object_id ORDER BY cnt DESC LIMIT 20",
    null,
    true
  );
  $albums = [];
  if ( $top_albums ){
    while( $row = $top_albums->fetch_assoc() ) $albums[] = [ "album_id" => (int) $row["object_id"], "plays" => (int) $row["cnt"] ];
  }

  $unique_users = 0;
  $unique_q = bof()->db->query(
    "SELECT COUNT(DISTINCT user_id) as cnt FROM _u_analytics_events {$where} AND user_id IS NOT NULL",
    null,
    true
  );
  if ( $unique_q && $row = $unique_q->fetch_assoc() ) $unique_users = (int) $row["cnt"];

  $loader->api->set_message( "ok", [
    "output_args" => [ "turn" => false ],
    "report" => [
      "range" => $range,
      "events" => $events,
      "unique_users" => $unique_users,
      "top_tracks" => $tracks,
      "top_albums" => $albums,
    ]
  ]);

}
