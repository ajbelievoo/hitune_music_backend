<?php

/**
 * Music Distribution - Admin Management
 * Admin-only: review queue, status updates, royalty reports
 *
 * GET  /api/dist/admin?action=stats
 * GET  /api/dist/admin?action=list&status=&q=
 * GET  /api/dist/admin?action=view&id=N
 * GET  /api/dist/admin?action=royalties
 * GET  /api/dist/admin?action=logs
 * POST /api/dist/admin (action=update_status)  id, status, admin_notes, tunecore_url, tunecore_status, launch_date
 * POST /api/dist/admin (action=add_royalty)    submission_id, report_month, platform, streams, downloads, revenue, currency
 * POST /api/dist/admin (action=delete)         id
 */

if ( !defined("bof_root") ) die;

define("DIST_ADMIN_ROLE_ID", 4);

function endpoint_dist_admin( $loader, $excuter, $args ){

    $user = $loader->user->check();
    if ( !$user || empty($user->ID) )
        return $loader->api->set_error( "access_denied" );

    // Admin role check (client context always reports role "user", so check role ids)
    $role_ids = !empty($user->extra["role_ids"]) ? $user->extra["role_ids"] : array();
    if ( !in_array( DIST_ADMIN_ROLE_ID, array_map("intval", (array)$role_ids), true ) )
        return $loader->api->set_error( "access_denied", array( 'code' => 'admin_required' ) );

    $db = $loader->db;
    $action = $_SERVER['REQUEST_METHOD'] === 'POST'
        ? ( !empty($_POST['action']) ? $_POST['action'] : '' )
        : ( !empty($_GET['action']) ? $_GET['action'] : 'list' );

    switch ( $action ){

        case 'stats':       return dist_admin_stats( $loader, $db );
        case 'list':        return dist_admin_list( $loader, $db );
        case 'view':        return dist_admin_view( $loader, $db );
        case 'royalties':   return dist_admin_royalties( $loader, $db );
        case 'logs':        return dist_admin_logs( $loader, $db );
        case 'export':      return dist_admin_export( $loader, $db, $user );
        case 'payouts':     return dist_admin_payouts( $loader, $db );
        case 'subscriptions': return dist_admin_subscriptions( $loader, $db );
        case 'update_status': return dist_admin_update_status( $loader, $db, $user );
        case 'add_royalty': return dist_admin_add_royalty( $loader, $db, $user );
        case 'payout_update': return dist_admin_payout_update( $loader, $db, $user );
        case 'import_royalties': return dist_admin_import_royalties( $loader, $db, $user );
        case 'clear_takedown': return dist_admin_clear_takedown( $loader, $db, $user );
        case 'publish':    return dist_admin_publish( $loader, $db, $user );
        case 'delete':      return dist_admin_delete( $loader, $db, $user );
    }

    return $loader->api->set_error( "invalid_input", array( 'code' => 'unknown_action' ) );
}

function dist_admin_stats( $loader, $db ){

    $stats = array(
        'total' => 0, 'submitted' => 0, 'in_review' => 0, 'in_progress' => 0,
        'approved' => 0, 'rejected' => 0, 'launched' => 0, 'taken_down' => 0,
        'revenue_total' => 0, 'royalty_reports' => 0, 'active_subscriptions' => 0
    );

    $r = $db->query("SELECT status, COUNT(*) c FROM `_dist_submissions` GROUP BY status");
    while ( $r && ($row = $r->fetch_assoc()) ){
        $stats['total'] += (int)$row['c'];
        if ( isset($stats[$row['status']]) ) $stats[$row['status']] = (int)$row['c'];
    }

    $r = $db->query("SELECT SUM(revenue) t, COUNT(*) c FROM `_dist_royalties`");
    if ( $r && ($row = $r->fetch_assoc()) ){
        $stats['revenue_total'] = (float)$row['t'];
        $stats['royalty_reports'] = (int)$row['c'];
    }

    $r = $db->query("SELECT COUNT(*) c FROM `_dist_subscriptions` WHERE status='active' AND end_date >= CURDATE()");
    if ( $r && ($row = $r->fetch_assoc()) )
        $stats['active_subscriptions'] = (int)$row['c'];

    $loader->api->set_message( "ok", array( 'stats' => $stats ) );
}

function dist_admin_list( $loader, $db ){

    $where = "1";
    if ( !empty($_GET['status']) && preg_match('/^[a-z_]+$/', $_GET['status']) )
        $where .= " AND s.status = '" . $db->real_escape_string($_GET['status']) . "'";
    if ( !empty($_GET['q']) )
        $where .= " AND (s.title LIKE '%" . $db->real_escape_string($_GET['q']) . "%'
            OR s.artist_name LIKE '%" . $db->real_escape_string($_GET['q']) . "%')";

    $submissions = array();
    $r = $db->query("SELECT s.*, u.username, u.email
        FROM `_dist_submissions` s
        LEFT JOIN `_u_list` u ON s.user_id = u.ID
        WHERE {$where}
        ORDER BY s.time_add DESC
        LIMIT 200");
    while ( $r && ($row = $r->fetch_assoc()) ){
        $submissions[] = dist_admin_submission_row( $row );
    }

    $loader->api->set_message( "ok", array( 'submissions' => $submissions ) );
}

function dist_admin_view( $loader, $db ){

    $id = !empty($_GET['id']) ? (int)$_GET['id'] : 0;
    if ( !$id )
        return $loader->api->set_error( "invalid_input", array( 'code' => 'missing_id' ) );

    $r = $db->query("SELECT s.*, u.username, u.email, u.name as user_name,
        p.name as plan_name
        FROM `_dist_submissions` s
        LEFT JOIN `_u_list` u ON s.user_id = u.ID
        LEFT JOIN `_dist_subscriptions` sub ON s.subscription_id = sub.id
        LEFT JOIN `_dist_plans` p ON sub.plan_id = p.id
        WHERE s.id = {$id} LIMIT 1");
    $sub = ( $r && $r->num_rows ) ? $r->fetch_assoc() : null;
    if ( !$sub )
        return $loader->api->set_error( "not_found", array( 'code' => 'sub_not_found' ) );

    $tracks = array();
    $r = $db->query("SELECT * FROM `_dist_tracks` WHERE submission_id = {$id} ORDER BY track_number ASC");
    while ( $r && ($row = $r->fetch_assoc()) )
        $tracks[] = $row;

    $logs = array();
    $r = $db->query("SELECT * FROM `_dist_logs` WHERE submission_id = {$id} ORDER BY time_add DESC LIMIT 20");
    while ( $r && ($row = $r->fetch_assoc()) )
        $logs[] = $row;

    $loader->api->set_message( "ok", array(
        'submission' => dist_admin_submission_row( $sub ),
        'tracks' => $tracks,
        'logs' => $logs
    ));
}

function dist_admin_submission_row( $row ){
    return array(
        'id' => (int)$row['id'],
        'user_id' => (int)$row['user_id'],
        'user' => !empty($row['username']) ? $row['username'] : null,
        'user_email' => !empty($row['email']) ? $row['email'] : null,
        'plan_name' => !empty($row['plan_name']) ? $row['plan_name'] : null,
        'type' => $row['type'],
        'title' => $row['title'],
        'artist_name' => $row['artist_name'],
        'album_name' => $row['album_name'],
        'genre' => $row['genre'],
        'release_date' => $row['release_date'],
        'language' => $row['language'],
        'isrc' => $row['isrc'],
        'upc' => $row['upc'],
        'label_name' => $row['label_name'],
        'recording_location' => $row['recording_location'],
        'description' => isset($row['description']) ? $row['description'] : null,
        'cover_art' => $row['cover_art_path'],
        'audio_file' => $row['audio_file_path'],
        'platforms' => !empty($row['platforms']) ? json_decode($row['platforms'], true) : array(),
        'countries' => !empty($row['countries']) ? json_decode($row['countries'], true) : array(),
        'status' => $row['status'],
        'admin_notes' => $row['admin_notes'],
        'tunecore_url' => $row['tunecore_url'],
        'tunecore_status' => $row['tunecore_status'],
        'launch_date' => $row['launch_date'],
        'submitted_at' => $row['time_add'],
        'updated_at' => $row['time_update'],
        'takedown_requested' => !empty($row['takedown_requested']),
        'takedown_reason' => isset($row['takedown_reason']) ? $row['takedown_reason'] : null,
        'takedown_requested_at' => isset($row['takedown_requested_at']) ? $row['takedown_requested_at'] : null,
        'catalog_published' => !empty($row['catalog_published']),
        'catalog_track_ids' => !empty($row['catalog_track_ids']) ? json_decode($row['catalog_track_ids'], true) : array(),
        'dashboard_status' => function_exists("dist_dashboard_status") ? dist_dashboard_status( $row ) : $row['status'],
        'ai_pct' => (int)( $row['ai_pct'] ?? 0 ),
        'ai_tools' => isset($row['ai_tools']) ? $row['ai_tools'] : null,
        'ai_declared' => !empty($row['ai_declared']),
        'ai_badge' => !empty($row['ai_pct']) ? 'AI Original' : null
    );
}

function dist_admin_update_status( $loader, $db, $user ){

    if ( $_SERVER['REQUEST_METHOD'] !== 'POST' )
        return $loader->api->set_error( "invalid_input", array( 'code' => 'post_required' ) );

    $id = !empty($_POST['id']) ? (int)$_POST['id'] : 0;
    $new_status = !empty($_POST['status']) ? $_POST['status'] : '';
    $valid = array('submitted','in_review','in_progress','approved','rejected','launched','taken_down');
    if ( !$id || !in_array($new_status, $valid, true) )
        return $loader->api->set_error( "invalid_input", array( 'code' => 'invalid_status' ) );

    $r = $db->query("SELECT status FROM `_dist_submissions` WHERE id = {$id} LIMIT 1");
    $old = ( $r && $r->num_rows ) ? $r->fetch_assoc() : null;
    if ( !$old )
        return $loader->api->set_error( "not_found", array( 'code' => 'sub_not_found' ) );

    $sets = array( "status = '" . $db->real_escape_string($new_status) . "'" );

    if ( isset($_POST['admin_notes']) )
        $sets[] = "admin_notes = '" . $db->real_escape_string($_POST['admin_notes']) . "'";
    if ( isset($_POST['tunecore_url']) )
        $sets[] = "tunecore_url = '" . $db->real_escape_string($_POST['tunecore_url']) . "'";
    if ( isset($_POST['tunecore_status']) )
        $sets[] = "tunecore_status = '" . $db->real_escape_string($_POST['tunecore_status']) . "'";
    if ( !empty($_POST['launch_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['launch_date']) )
        $sets[] = "launch_date = '" . $db->real_escape_string($_POST['launch_date']) . "'";
    elseif ( $new_status === 'launched' )
        $sets[] = "launch_date = CURDATE()";

    // completing a takedown clears the pending request flag
    if ( $new_status === 'taken_down' )
        $sets[] = "takedown_requested = 0";

    $db->query("UPDATE `_dist_submissions` SET " . implode(', ', $sets) . " WHERE id = {$id}");

    dist_admin_log( $db, $user->ID, $id, 'status_update', $old['status'], $new_status,
        "Status changed {$old['status']} -> {$new_status}" );

    // launching a release also publishes it to the streaming catalog
    $publish_result = null;
    if ( $new_status === 'launched' && function_exists("dist_publish_to_catalog") ){
        $pub = dist_publish_to_catalog( $db, $id );
        if ( !empty($pub['error']) && $pub['error'] !== 'already_published' ){
            dist_admin_log( $db, $user->ID, $id, 'catalog_publish_failed', null, null, "Auto-publish failed: {$pub['error']}" );
            $publish_result = array( 'error' => $pub['error'] );
        } elseif ( empty($pub['error']) ){
            dist_admin_log( $db, $user->ID, $id, 'catalog_published', null, null,
                "Published to streaming catalog: " . count($pub['track_ids']) . " track(s)" );
            $publish_result = array( 'track_ids' => $pub['track_ids'] );
        }
    }

    // full rejection or takedown also pulls the release off HiTune + IyolMe (doc §7)
    if ( in_array( $new_status, array('rejected','taken_down'), true ) ){
        try {
            $r3 = $db->query("SELECT catalog_published, catalog_track_ids FROM `_dist_submissions` WHERE id = {$id} LIMIT 1");
            $s3 = ( $r3 && $r3->num_rows ) ? $r3->fetch_assoc() : null;
            $_tid_list = array_filter( array_map( "intval", (array) json_decode( (string)( $s3['catalog_track_ids'] ?? '' ), true ) ) );

            // queue IyolMe takedowns BEFORE unpublish — payload needs the live track hash
            foreach ( $_tid_list as $_tid )
                bof()->iyolme->queue_takedown( (int)$_tid );

            if ( !empty($s3['catalog_published']) && function_exists("dist_unpublish_catalog") ){
                $unpub = dist_unpublish_catalog( $db, $id );
                dist_admin_log( $db, $user->ID, $id, 'catalog_unpublished', null, null,
                    "Removed " . (int)$unpub['removed'] . " catalog track(s) on {$new_status}" );
            }
        } catch ( \Throwable $e ) {}
    }

    // notify the artist
    $r2 = $db->query("SELECT user_id, title FROM `_dist_submissions` WHERE id = {$id} LIMIT 1");
    $sub2 = ( $r2 && $r2->num_rows ) ? $r2->fetch_assoc() : null;
    if ( $sub2 && function_exists("dist_notify_user") ){
        $labels = array(
            'submitted'=>'Pending Admin Review','in_review'=>'Pending Admin Review','in_progress'=>'Pending Admin Review',
            'approved'=>'Approved','rejected'=>'Rejected for All Platforms (Including HiTune Music)',
            'launched'=>'Live on HiTune','taken_down'=>'Taken Down'
        );
        $label = isset($labels[$new_status]) ? $labels[$new_status] : $new_status;
        $notes = !empty($_POST['admin_notes']) ? "<br><br><b>Admin notes:</b> " . htmlspecialchars($_POST['admin_notes']) : "";
        dist_notify_user( (int)$sub2['user_id'],
            "Release status update: " . $label,
            "Your release <b>" . htmlspecialchars($sub2['title']) . "</b> is now <b>{$label}</b>.{$notes}<br><br>Check your artist dashboard for details." );
    }

    $loader->api->set_message( "ok", array( 'id' => $id, 'status' => $new_status, 'catalog' => $publish_result ) );
}

function dist_admin_subscriptions( $loader, $db ){

    $subs = array();
    $r = $db->query("SELECT s.*, p.name as plan_name, p.slug as plan_slug, u.username, u.email,
        (SELECT COUNT(*) FROM `_dist_submissions` d WHERE d.subscription_id = s.id) as releases_used
        FROM `_dist_subscriptions` s
        JOIN `_dist_plans` p ON s.plan_id = p.id
        LEFT JOIN `_u_list` u ON s.user_id = u.ID
        ORDER BY s.id DESC LIMIT 300");
    while ( $r && ($row = $r->fetch_assoc()) ){
        $subs[] = array(
            'id' => (int)$row['id'],
            'user_id' => (int)$row['user_id'],
            'user' => $row['username'],
            'user_email' => $row['email'],
            'plan' => $row['plan_name'],
            'plan_slug' => $row['plan_slug'],
            'status' => $row['status'],
            'payment_status' => $row['payment_status'],
            'amount_paid' => (float)$row['amount_paid'],
            'start_date' => $row['start_date'],
            'end_date' => $row['end_date'],
            'releases_used' => (int)$row['releases_used'],
            'transaction_id' => $row['transaction_id']
        );
    }

    $loader->api->set_message( "ok", array( 'subscriptions' => $subs ) );
}

function dist_admin_clear_takedown( $loader, $db, $user ){

    if ( $_SERVER['REQUEST_METHOD'] !== 'POST' )
        return $loader->api->set_error( "invalid_input", array( 'code' => 'post_required' ) );

    $id = !empty($_POST['id']) ? (int)$_POST['id'] : 0;
    $r = $db->query("SELECT user_id, title, takedown_requested FROM `_dist_submissions` WHERE id = {$id} LIMIT 1");
    $sub = ( $r && $r->num_rows ) ? $r->fetch_assoc() : null;
    if ( !$sub )
        return $loader->api->set_error( "not_found", array( 'code' => 'sub_not_found' ) );
    if ( empty($sub['takedown_requested']) )
        return $loader->api->set_error( "invalid_input", array( 'code' => 'no_takedown_request' ) );

    $db->query("UPDATE `_dist_submissions` SET takedown_requested = 0, takedown_reason = NULL, takedown_requested_at = NULL WHERE id = {$id}");

    dist_admin_log( $db, $user->ID, $id, 'takedown_denied', null, null, "Takedown request dismissed by admin" );

    if ( function_exists("dist_notify_user") ){
        dist_notify_user( (int)$sub['user_id'],
            "Takedown request declined",
            "Your takedown request for <b>" . htmlspecialchars($sub['title']) . "</b> was reviewed and declined. The release stays live. Check your artist dashboard for details." );
    }

    $loader->api->set_message( "ok", array( 'id' => $id ) );
}

function dist_admin_publish( $loader, $db, $user ){

    $id = !empty($_GET['id']) ? (int)$_GET['id'] : ( !empty($_POST['id']) ? (int)$_POST['id'] : 0 );
    if ( !$id )
        return $loader->api->set_error( "invalid_input", array( 'code' => 'missing_id' ) );

    if ( !function_exists("dist_publish_to_catalog") )
        return $loader->api->set_error( "failed", array( 'code' => 'publisher_missing' ) );

    $res = dist_publish_to_catalog( $db, $id );
    if ( !empty($res['error']) )
        return $loader->api->set_error( "invalid_input", array( 'code' => $res['error'] ) );

    dist_admin_log( $db, $user->ID, $id, 'catalog_published', null, null,
        "Published to streaming catalog: " . count($res['track_ids']) . " track(s)" );

    $loader->api->set_message( "ok", array(
        'id' => $id,
        'track_ids' => $res['track_ids'],
        'album_id' => $res['album_id'],
        'artist_id' => $res['artist_id']
    ));
}

function dist_admin_export( $loader, $db, $user ){

    $id = !empty($_GET['id']) ? (int)$_GET['id'] : ( !empty($_POST['id']) ? (int)$_POST['id'] : 0 );
    if ( !$id )
        return $loader->api->set_error( "invalid_input", array( 'code' => 'missing_id' ) );

    $r = $db->query("SELECT s.*, u.username, u.email FROM `_dist_submissions` s
        LEFT JOIN `_u_list` u ON s.user_id = u.ID WHERE s.id = {$id} LIMIT 1");
    $sub = ( $r && $r->num_rows ) ? $r->fetch_assoc() : null;
    if ( !$sub )
        return $loader->api->set_error( "not_found", array( 'code' => 'sub_not_found' ) );

    $tracks = array();
    $r = $db->query("SELECT * FROM `_dist_tracks` WHERE submission_id = {$id} ORDER BY track_number ASC");
    while ( $r && ($row = $r->fetch_assoc()) )
        $tracks[] = $row;

    $export_dir = base_root . "/files/dist/exports";
    if ( !is_dir( $export_dir ) )
        mkdir( $export_dir, 0755, true );

    $zip_name = "release_{$id}_" . date("Ymd_His") . ".zip";
    $zip_path = $export_dir . "/" . $zip_name;

    $meta = array(
        'release' => array(
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
            'recording_location' => $sub['recording_location'],
            'description' => $sub['description'],
            'platforms' => !empty($sub['platforms']) ? json_decode($sub['platforms'], true) : array(),
            'countries' => !empty($sub['countries']) ? json_decode($sub['countries'], true) : array(),
        ),
        'artist_account' => array(
            'user_id' => (int)$sub['user_id'],
            'username' => $sub['username'],
            'email' => $sub['email'],
        ),
        'tracks' => array_map( function($t){
            return array(
                'track_number' => (int)$t['track_number'],
                'title' => $t['title'],
                'artist_name' => $t['artist_name'],
                'isrc' => $t['isrc'],
                'duration_seconds' => (int)$t['duration'],
                'file' => basename( $t['audio_file_path'] ? $t['audio_file_path'] : "" )
            );
        }, $tracks ),
        'exported_at' => date("c"),
        'exported_by' => "Hitune Music Distribution"
    );

    $zip = new ZipArchive();
    if ( $zip->open( $zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE ) !== true )
        return $loader->api->set_error( "failed", array( 'code' => 'zip_failed' ) );

    $zip->addFromString( "metadata.json", json_encode( $meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );

    // flat CSV for aggregator upload
    $csv = "track_number,track_title,artist_name,isrc,duration_seconds,audio_file\n";
    foreach ( $meta['tracks'] as $t )
        $csv .= "{$t['track_number']},\"" . str_replace('"','""',$t['title']) . "\",\"" . str_replace('"','""',$t['artist_name']) . "\",{$t['isrc']},{$t['duration_seconds']},{$t['file']}\n";
    $csv .= "\nrelease_title,release_type,artist,album,genre,language,release_date,upc,label\n";
    $csv .= "\"" . str_replace('"','""',$sub['title']) . "\",{$sub['type']},\"" . str_replace('"','""',$sub['artist_name']) . "\",\"" . str_replace('"','""',$sub['album_name']) . "\",{$sub['genre']},{$sub['language']},{$sub['release_date']},{$sub['upc']},\"" . str_replace('"','""',$sub['label_name']) . "\"\n";
    $zip->addFromString( "metadata.csv", $csv );

    $files_added = 0;
    $missing = array();

    $add_file = function( $rel_path, $zip_name_in ) use ( $zip, &$files_added, &$missing ){
        $abs = base_root . "/" . ltrim( $rel_path, "/" );
        if ( $rel_path && is_file( $abs ) ){
            $zip->addFile( $abs, $zip_name_in );
            $files_added++;
        } else {
            $missing[] = $rel_path;
        }
    };

    if ( !empty($sub['cover_art_path']) )
        $add_file( $sub['cover_art_path'], "cover/" . basename($sub['cover_art_path']) );

    foreach ( $tracks as $t ){
        if ( !empty($t['audio_file_path']) )
            $add_file( $t['audio_file_path'], "audio/" . basename($t['audio_file_path']) );
    }
    if ( !empty($sub['audio_file_path']) )
        $add_file( $sub['audio_file_path'], "audio/" . basename($sub['audio_file_path']) );

    $zip->close();

    dist_admin_log( $db, $user->ID, $id, 'package_exported', null, null, "Export package generated ({$files_added} files)" );

    $loader->api->set_message( "ok", array(
        'download_url' => rtrim( web_address, "/" ) . "/files/dist/exports/" . $zip_name,
        'files_added' => $files_added,
        'missing_files' => $missing
    ));
}

function dist_admin_payouts( $loader, $db ){

    $where = "1";
    if ( !empty($_GET['status']) && preg_match('/^[a-z_]+$/', $_GET['status']) )
        $where .= " AND p.status = '" . $db->real_escape_string($_GET['status']) . "'";

    $payouts = array();
    $r = $db->query("SELECT p.*, u.username, u.email
        FROM `_dist_payouts` p
        LEFT JOIN `_u_list` u ON p.user_id = u.ID
        WHERE {$where}
        ORDER BY p.time_add DESC LIMIT 200");
    while ( $r && ($row = $r->fetch_assoc()) ){
        $row['id'] = (int)$row['id'];
        $row['user_id'] = (int)$row['user_id'];
        $row['amount'] = (float)$row['amount'];
        $payouts[] = $row;
    }

    $loader->api->set_message( "ok", array( 'payouts' => $payouts ) );
}

function dist_admin_payout_update( $loader, $db, $user ){

    if ( $_SERVER['REQUEST_METHOD'] !== 'POST' )
        return $loader->api->set_error( "invalid_input", array( 'code' => 'post_required' ) );

    $id = !empty($_POST['id']) ? (int)$_POST['id'] : 0;
    $new_status = !empty($_POST['status']) ? $_POST['status'] : '';
    if ( !$id || !in_array( $new_status, array('requested','processing','paid','rejected'), true ) )
        return $loader->api->set_error( "invalid_input", array( 'code' => 'invalid_status' ) );

    $r = $db->query("SELECT * FROM `_dist_payouts` WHERE id = {$id} LIMIT 1");
    $payout = ( $r && $r->num_rows ) ? $r->fetch_assoc() : null;
    if ( !$payout )
        return $loader->api->set_error( "not_found", array( 'code' => 'payout_not_found' ) );

    $sets = array( "status = '" . $db->real_escape_string($new_status) . "'" );
    if ( isset($_POST['payment_reference']) )
        $sets[] = "payment_reference = '" . $db->real_escape_string($_POST['payment_reference']) . "'";
    if ( isset($_POST['admin_notes']) )
        $sets[] = "admin_notes = '" . $db->real_escape_string($_POST['admin_notes']) . "'";

    $db->query("UPDATE `_dist_payouts` SET " . implode(', ', $sets) . " WHERE id = {$id}");

    // When marked paid: mark the user's unpaid royalties as paid, up to the payout amount
    if ( $new_status === 'paid' && $payout['status'] !== 'paid' ){

        $target = (float)$payout['amount'];
        $covered = 0;
        $mark_ids = array();

        $r2 = $db->query("SELECT id, revenue FROM `_dist_royalties`
            WHERE user_id = " . (int)$payout['user_id'] . " AND is_paid = 0 ORDER BY id ASC");
        while ( $r2 && ($row = $r2->fetch_assoc()) ){
            if ( $covered >= $target ) break;
            $mark_ids[] = (int)$row['id'];
            $covered += (float)$row['revenue'];
        }
        if ( $mark_ids ){
            $ids = implode(',', $mark_ids);
            $method = $db->real_escape_string( $payout['method'] );
            $db->query("UPDATE `_dist_royalties` SET is_paid = 1, payment_date = CURDATE(), payment_method = '{$method}'
                WHERE id IN ({$ids})");
        }
    }

    dist_admin_log( $db, $user->ID, null, 'payout_update', $payout['status'], $new_status,
        "Payout #{$id} (user {$payout['user_id']}, INR {$payout['amount']}): {$payout['status']} -> {$new_status}" );

    if ( function_exists("dist_notify_user") ){
        $labels = array( 'requested'=>'Requested', 'processing'=>'Processing', 'paid'=>'Paid', 'rejected'=>'Rejected' );
        $label = isset($labels[$new_status]) ? $labels[$new_status] : $new_status;
        $ref = !empty($_POST['payment_reference']) ? "<br>Reference: " . htmlspecialchars($_POST['payment_reference']) : "";
        dist_notify_user( (int)$payout['user_id'],
            "Payout {$label}",
            "Your payout request #{$id} for <b>INR {$payout['amount']}</b> is now <b>{$label}</b>.{$ref}" );
    }

    $loader->api->set_message( "ok", array( 'id' => $id, 'status' => $new_status ) );
}

function dist_admin_add_royalty( $loader, $db, $user ){

    if ( $_SERVER['REQUEST_METHOD'] !== 'POST' )
        return $loader->api->set_error( "invalid_input", array( 'code' => 'post_required' ) );

    $submission_id = !empty($_POST['submission_id']) ? (int)$_POST['submission_id'] : 0;
    $report_month  = !empty($_POST['report_month']) ? $db->real_escape_string($_POST['report_month']) : null;
    $platform      = !empty($_POST['platform']) ? $db->real_escape_string($_POST['platform']) : null;
    $streams       = !empty($_POST['streams']) ? (int)$_POST['streams'] : 0;
    $downloads     = !empty($_POST['downloads']) ? (int)$_POST['downloads'] : 0;
    $revenue       = isset($_POST['revenue']) ? (float)$_POST['revenue'] : null;
    $currency      = !empty($_POST['currency']) ? $db->real_escape_string($_POST['currency']) : 'USD';

    if ( !$submission_id || !$report_month || !$platform || $revenue === null )
        return $loader->api->set_error( "invalid_input", array( 'code' => 'missing_fields' ) );

    $r = $db->query("SELECT user_id FROM `_dist_submissions` WHERE id = {$submission_id} LIMIT 1");
    $sub = ( $r && $r->num_rows ) ? $r->fetch_assoc() : null;
    if ( !$sub )
        return $loader->api->set_error( "not_found", array( 'code' => 'sub_not_found' ) );

    $user_id = (int)$sub['user_id'];
    $db->query("INSERT INTO `_dist_royalties`
        (user_id, submission_id, report_month, platform, streams, downloads, revenue, currency)
        VALUES ({$user_id}, {$submission_id}, '{$report_month}', '{$platform}',
        {$streams}, {$downloads}, {$revenue}, '{$currency}')");

    dist_admin_log( $db, $user->ID, $submission_id, 'royalty_added', null, null,
        "Royalty report added: {$platform} {$report_month} {$currency} {$revenue}" );

    $loader->api->set_message( "ok", array( 'id' => (int)$db->insert_id ) );
}

function dist_admin_royalties( $loader, $db ){

    $reports = array();
    $r = $db->query("SELECT ro.*, s.title as submission_title, s.artist_name, u.username
        FROM `_dist_royalties` ro
        LEFT JOIN `_dist_submissions` s ON ro.submission_id = s.id
        LEFT JOIN `_u_list` u ON ro.user_id = u.ID
        ORDER BY ro.time_add DESC LIMIT 200");
    while ( $r && ($row = $r->fetch_assoc()) )
        $reports[] = $row;

    $loader->api->set_message( "ok", array( 'reports' => $reports ) );
}

function dist_admin_logs( $loader, $db ){

    $logs = array();
    $r = $db->query("SELECT l.*, u.username, s.title as submission_title
        FROM `_dist_logs` l
        LEFT JOIN `_u_list` u ON l.performed_by = u.ID
        LEFT JOIN `_dist_submissions` s ON l.submission_id = s.id
        ORDER BY l.time_add DESC LIMIT 100");
    while ( $r && ($row = $r->fetch_assoc()) )
        $logs[] = $row;

    $loader->api->set_message( "ok", array( 'logs' => $logs ) );
}

function dist_admin_delete( $loader, $db, $user ){

    if ( $_SERVER['REQUEST_METHOD'] !== 'POST' )
        return $loader->api->set_error( "invalid_input", array( 'code' => 'post_required' ) );

    $id = !empty($_POST['id']) ? (int)$_POST['id'] : 0;
    if ( !$id )
        return $loader->api->set_error( "invalid_input", array( 'code' => 'missing_id' ) );

    dist_admin_log( $db, $user->ID, $id, 'submission_deleted', null, null, "Submission #{$id} deleted by admin" );

    $db->query("DELETE FROM `_dist_tracks` WHERE submission_id = {$id}");
    $db->query("DELETE FROM `_dist_submissions` WHERE id = {$id}");

    $loader->api->set_message( "ok", array( 'id' => $id ) );
}

function dist_admin_import_royalties( $loader, $db, $user ){

    if ( $_SERVER['REQUEST_METHOD'] !== 'POST' )
        return $loader->api->set_error( "invalid_input", array( 'code' => 'post_required' ) );

    $csv_text = '';
    if ( !empty($_POST['csv']) )
        $csv_text = $_POST['csv'];
    elseif ( !empty($_FILES['csv_file']) && $_FILES['csv_file']['error'] === 0 )
        $csv_text = file_get_contents( $_FILES['csv_file']['tmp_name'] );

    if ( !trim( $csv_text ) )
        return $loader->api->set_error( "invalid_input", array( 'code' => 'no_csv' ) );

    $created = 0;
    $skipped = array();
    $line_no = 0;

    $fh = fopen( 'php://memory', 'r+' );
    fwrite( $fh, $csv_text );
    rewind( $fh );

    while ( ($row = fgetcsv( $fh )) !== false ){

        $line_no++;
        if ( !count( array_filter( $row ) ) ) continue;

        // skip header row
        if ( $line_no === 1 && preg_match('/sub|month|platform/i', implode(',', $row)) )
            continue;

        // columns: submission_id, report_month, platform, streams, downloads, revenue, currency
        $submission_id = isset($row[0]) ? (int)trim($row[0]) : 0;
        $report_month  = isset($row[1]) ? trim($row[1]) : '';
        $platform      = isset($row[2]) ? trim($row[2]) : '';
        $streams       = isset($row[3]) ? (int)trim($row[3]) : 0;
        $downloads     = isset($row[4]) ? (int)trim($row[4]) : 0;
        $revenue       = isset($row[5]) ? (float)trim($row[5]) : null;
        $currency      = isset($row[6]) && trim($row[6]) ? trim($row[6]) : 'USD';

        if ( !$submission_id || !$report_month || !$platform || $revenue === null ){
            $skipped[] = array( 'line' => $line_no, 'reason' => 'missing fields' );
            continue;
        }

        $r = $db->query("SELECT user_id FROM `_dist_submissions` WHERE id = {$submission_id} LIMIT 1");
        $sub = ( $r && $r->num_rows ) ? $r->fetch_assoc() : null;
        if ( !$sub ){
            $skipped[] = array( 'line' => $line_no, 'reason' => "submission #{$submission_id} not found" );
            continue;
        }

        $uid = (int)$sub['user_id'];
        $rm = $db->real_escape_string( $report_month );
        $pl = $db->real_escape_string( $platform );
        $cu = $db->real_escape_string( $currency );

        $db->query("INSERT INTO `_dist_royalties`
            (user_id, submission_id, report_month, platform, streams, downloads, revenue, currency)
            VALUES ({$uid}, {$submission_id}, '{$rm}', '{$pl}', {$streams}, {$downloads}, {$revenue}, '{$cu}')");
        $created++;
    }
    fclose( $fh );

    dist_admin_log( $db, $user->ID, null, 'royalty_import', null, null,
        "Royalty CSV import: {$created} rows created, " . count($skipped) . " skipped" );

    $loader->api->set_message( "ok", array(
        'created' => $created,
        'skipped' => $skipped
    ));
}

function dist_admin_log( $db, $user_id, $submission_id, $action, $old_status, $new_status, $details ){
    $user_id = (int)$user_id;
    $submission_id = (int)$submission_id;
    $action = $db->real_escape_string($action);
    $details = $db->real_escape_string($details);
    $old_sql = $old_status ? "'" . $db->real_escape_string($old_status) . "'" : "NULL";
    $new_sql = $new_status ? "'" . $db->real_escape_string($new_status) . "'" : "NULL";
    $db->query("INSERT INTO `_dist_logs` (user_id, submission_id, action, old_status, new_status, details, performed_by)
        VALUES ({$user_id}, {$submission_id}, '{$action}', {$old_sql}, {$new_sql}, '{$details}', {$user_id})");
}
