<?php

/**
 * Music Distribution - Artist's own release detail + edit/resubmit
 * GET  /api/dist/submission?id=N
 * POST /api/dist/submission (action=update)   id + editable fields   (only while submitted/rejected)
 * POST /api/dist/submission (action=resubmit) id                     (rejected -> submitted)
 */

if ( !defined("bof_root") ) die;

function endpoint_dist_submission( $loader, $excuter, $args ){

    $user = $loader->user->check();
    if ( !$user || empty($user->ID) )
        return $loader->api->set_error( "access_denied" );

    $user_id = (int)$user->ID;
    $db = $loader->db;

    if ( $_SERVER['REQUEST_METHOD'] === 'POST' ){
        $action = !empty($_POST['action']) ? $_POST['action'] : '';
        if ( $action === 'update' )   return dist_sub_update( $loader, $db, $user_id );
        if ( $action === 'resubmit' ) return dist_sub_resubmit( $loader, $db, $user_id );
        if ( $action === 'takedown' ) return dist_sub_takedown( $loader, $db, $user_id );
        return $loader->api->set_error( "invalid_input", array( 'code' => 'unknown_action' ) );
    }

    $id = !empty($_GET['id']) ? (int)$_GET['id'] : 0;
    if ( !$id )
        return $loader->api->set_error( "invalid_input", array( 'code' => 'missing_id' ) );

    $r = $db->query("SELECT * FROM `_dist_submissions` WHERE id = {$id} AND user_id = {$user_id} LIMIT 1");
    $sub = ( $r && $r->num_rows ) ? $r->fetch_assoc() : null;
    if ( !$sub )
        return $loader->api->set_error( "not_found", array( 'code' => 'sub_not_found' ) );

    $tracks = array();
    $r = $db->query("SELECT * FROM `_dist_tracks` WHERE submission_id = {$id} ORDER BY track_number ASC");
    while ( $r && ($row = $r->fetch_assoc()) ){
        $tracks[] = array(
            'id' => (int)$row['id'],
            'track_number' => (int)$row['track_number'],
            'title' => $row['title'],
            'artist' => $row['artist_name'],
            'isrc' => $row['isrc'],
            'duration' => (int)$row['duration'],
            'has_audio' => !empty($row['audio_file_path'])
        );
    }

    $timeline = array();
    $r = $db->query("SELECT action, old_status, new_status, details, time_add FROM `_dist_logs`
        WHERE submission_id = {$id} ORDER BY time_add DESC LIMIT 30");
    while ( $r && ($row = $r->fetch_assoc()) ){
        $timeline[] = array(
            'action' => $row['action'],
            'old_status' => $row['old_status'],
            'new_status' => $row['new_status'],
            'details' => $row['details'],
            'time' => $row['time_add']
        );
    }

    $royalties = array();
    $r = $db->query("SELECT report_month, platform, streams, downloads, revenue, currency, is_paid, payment_date
        FROM `_dist_royalties` WHERE submission_id = {$id} AND user_id = {$user_id} ORDER BY report_month DESC");
    while ( $r && ($row = $r->fetch_assoc()) ){
        $row['streams'] = (int)$row['streams'];
        $row['downloads'] = (int)$row['downloads'];
        $row['revenue'] = (float)$row['revenue'];
        $row['is_paid'] = (bool)$row['is_paid'];
        $royalties[] = $row;
    }

    $loader->api->set_message( "ok", array(
        'submission' => array(
            'id' => (int)$sub['id'],
            'type' => $sub['type'],
            'title' => $sub['title'],
            'artist_name' => $sub['artist_name'],
            'album_name' => $sub['album_name'],
            'genre' => $sub['genre'],
            'release_date' => $sub['release_date'],
            'language' => $sub['language'],
            'isrc' => $sub['isrc'],
            'upc' => $sub['upc'],
            'label_name' => $sub['label_name'],
            'description' => $sub['description'],
            'cover_art' => $sub['cover_art_path'],
            'platforms' => !empty($sub['platforms']) ? json_decode($sub['platforms'], true) : array(),
            'countries' => !empty($sub['countries']) ? json_decode($sub['countries'], true) : array(),
            'status' => $sub['status'],
            'status_label' => dist_getStatusLabel( $sub['status'] ),
            'dashboard_status' => function_exists("dist_dashboard_status") ? dist_dashboard_status( $sub ) : dist_getStatusLabel( $sub['status'] ),
            'ai_pct' => (int)( $sub['ai_pct'] ?? 0 ),
            'ai_tools' => $sub['ai_tools'] ?? null,
            'ai_badge' => !empty($sub['ai_pct']) ? 'AI Original' : null,
            'admin_notes' => $sub['admin_notes'],
            'tunecore_status' => $sub['tunecore_status'],
            'launch_date' => $sub['launch_date'],
            'submitted_at' => $sub['time_add'],
            'updated_at' => $sub['time_update']
        ),
        'tracks' => $tracks,
        'timeline' => $timeline,
        'royalties' => $royalties,
        'can_edit' => in_array( $sub['status'], array('submitted','rejected'), true ),
        'can_resubmit' => $sub['status'] === 'rejected',
        'can_takedown' => in_array( $sub['status'], array('approved','launched'), true ) && empty($sub['takedown_requested']),
        'takedown_requested' => !empty($sub['takedown_requested']),
        'takedown_reason' => $sub['takedown_reason'],
        'takedown_requested_at' => $sub['takedown_requested_at'],
        'catalog_published' => !empty($sub['catalog_published']),
        'catalog_track_ids' => !empty($sub['catalog_track_ids']) ? json_decode($sub['catalog_track_ids'], true) : array()
    ));
}

function dist_sub_getOwn( $loader, $db, $user_id ){

    $id = !empty($_POST['id']) ? (int)$_POST['id'] : ( !empty($_GET['id']) ? (int)$_GET['id'] : 0 );
    if ( !$id ){
        $loader->api->set_error( "invalid_input", array( 'code' => 'missing_id' ) );
        return null;
    }
    $r = $db->query("SELECT * FROM `_dist_submissions` WHERE id = {$id} AND user_id = {$user_id} LIMIT 1");
    $sub = ( $r && $r->num_rows ) ? $r->fetch_assoc() : null;
    if ( !$sub )
        $loader->api->set_error( "not_found", array( 'code' => 'sub_not_found' ) );
    return $sub;
}

function dist_sub_update( $loader, $db, $user_id ){

    $sub = dist_sub_getOwn( $loader, $db, $user_id );
    if ( !$sub ) return;

    if ( !in_array( $sub['status'], array('submitted','rejected'), true ) )
        return $loader->api->set_error( "invalid_input", array( 'code' => 'not_editable' ) );

    $editable = array( 'title','artist_name','album_name','genre','release_date','language','isrc','upc','label_name','recording_location','description' );
    $sets = array();

    foreach ( $editable as $f ){
        if ( array_key_exists( $f, $_POST ) ){
            $v = trim( (string)$_POST[$f] );
            if ( $v === '' ) $sets[] = "{$f} = NULL";
            else $sets[] = "{$f} = '" . $db->real_escape_string( $v ) . "'";
        }
    }

    // required fields can't be emptied
    if ( empty($_POST['title']) && $sub['title'] === null )
        return $loader->api->set_error( "invalid_input", array( 'input_name' => 'title', 'code' => 'missing_field' ) );

    if ( isset($_POST['platforms']) ){
        $p = $_POST['platforms'];
        if ( is_string($p) ) $p = json_decode( $p, true );
        if ( is_array($p) ) $sets[] = "platforms = '" . $db->real_escape_string( json_encode($p) ) . "'";
    }
    if ( isset($_POST['countries']) ){
        $c = $_POST['countries'];
        if ( is_string($c) ) $c = json_decode( $c, true );
        if ( is_array($c) ) $sets[] = "countries = '" . $db->real_escape_string( json_encode($c) ) . "'";
    }

    // title/artist can't be blank
    foreach ( array('title','artist_name') as $f ){
        if ( array_key_exists($f, $_POST) && trim((string)$_POST[$f]) === '' )
            return $loader->api->set_error( "invalid_input", array( 'input_name' => $f, 'code' => 'missing_field' ) );
    }

    if ( !$sets )
        return $loader->api->set_error( "invalid_input", array( 'code' => 'nothing_to_update' ) );

    $db->query("UPDATE `_dist_submissions` SET " . implode(', ', $sets) . " WHERE id = " . (int)$sub['id']);

    $db->query("INSERT INTO `_dist_logs` (user_id, submission_id, action, details, performed_by)
        VALUES ({$user_id}, " . (int)$sub['id'] . ", 'submission_edited', 'Artist edited release metadata', {$user_id})");

    $loader->api->set_message( "ok", array( 'id' => (int)$sub['id'] ) );
}

function dist_sub_resubmit( $loader, $db, $user_id ){

    $sub = dist_sub_getOwn( $loader, $db, $user_id );
    if ( !$sub ) return;

    if ( $sub['status'] !== 'rejected' )
        return $loader->api->set_error( "invalid_input", array( 'code' => 'not_resubmittable' ) );

    $db->query("UPDATE `_dist_submissions` SET status = 'submitted' WHERE id = " . (int)$sub['id']);

    $db->query("INSERT INTO `_dist_logs` (user_id, submission_id, action, old_status, new_status, details, performed_by)
        VALUES ({$user_id}, " . (int)$sub['id'] . ", 'resubmitted', 'rejected', 'submitted', 'Artist resubmitted release for review', {$user_id})");

    if ( function_exists("dist_notify_admin") ){
        dist_notify_admin(
            "Release resubmitted #{$sub['id']}",
            "<b>" . htmlspecialchars($sub['title']) . "</b> by " . htmlspecialchars($sub['artist_name']) .
            " was resubmitted after rejection.<br><br>Review it in the <a href='" . web_address . "/distribution/admin'>distribution admin</a>."
        );
    }

    $loader->api->set_message( "ok", array( 'id' => (int)$sub['id'], 'status' => 'submitted' ) );
}

function dist_sub_takedown( $loader, $db, $user_id ){

    $sub = dist_sub_getOwn( $loader, $db, $user_id );
    if ( !$sub ) return;

    if ( !in_array( $sub['status'], array('approved','launched'), true ) )
        return $loader->api->set_error( "invalid_input", array( 'code' => 'not_takedownable' ) );
    if ( !empty($sub['takedown_requested']) )
        return $loader->api->set_error( "invalid_input", array( 'code' => 'takedown_pending' ) );

    $reason = !empty($_POST['reason']) ? trim( (string)$_POST['reason'] ) : '';
    $reason_sql = $reason !== '' ? "'" . $db->real_escape_string( $reason ) . "'" : "NULL";

    $db->query("UPDATE `_dist_submissions` SET takedown_requested = 1, takedown_reason = {$reason_sql},
        takedown_requested_at = NOW() WHERE id = " . (int)$sub['id']);

    $db->query("INSERT INTO `_dist_logs` (user_id, submission_id, action, details, performed_by)
        VALUES ({$user_id}, " . (int)$sub['id'] . ", 'takedown_requested',
        'Artist requested takedown" . ( $reason !== '' ? ": " . $db->real_escape_string($reason) : "" ) . "', {$user_id})");

    if ( function_exists("dist_notify_admin") ){
        dist_notify_admin(
            "Takedown requested #{$sub['id']}",
            "<b>" . htmlspecialchars($sub['title']) . "</b> by " . htmlspecialchars($sub['artist_name']) .
            " — artist requested takedown." .
            ( $reason !== '' ? "<br><br><b>Reason:</b> " . htmlspecialchars($reason) : "" ) .
            "<br><br>Pull it from the aggregator, then mark it <b>Taken Down</b> in the " .
            "<a href='" . web_address . "/distribution/admin'>distribution admin</a>."
        );
    }

    $loader->api->set_message( "ok", array( 'id' => (int)$sub['id'], 'takedown_requested' => true ) );
}
?>
