<?php

/**
 * Music Distribution Module Loader - Client Endpoints
 * Registers client-facing distribution endpoints
 */

if ( !defined( "root" ) || !defined( "bof_root" ) ) die;

$endpoints_dir = root . "/app/client/endpoints/dist/";

// Shared notification helpers (used by submit/admin/payout endpoints)
function dist_notify_admin( $title, $content_html ){
    try {
        $admin_email = bof()->object->db_setting->get( "admin_email" );
        if ( !$admin_email ){
            $adm = bof()->object->user->sid( 1, array( "clean" => false ) );
            $admin_email = !empty( $adm["email"] ) ? $adm["email"] : null;
        }
        if ( !$admin_email ) return;
        bof()->chapar->method_exe( "email", array(
            "target_email" => $admin_email,
            "message_title" => $title,
            "message_content" => $content_html,
            "extra" => array()
        ));
    } catch ( \Throwable $e ) {}
}

function dist_notify_user( $user_id, $title, $content_html ){
    try {
        $u = bof()->object->user->sid( (int)$user_id, array( "clean" => false ) );
        $email = !empty( $u["email"] ) ? $u["email"] : null;
        if ( !$email ) return;
        bof()->chapar->method_exe( "email", array(
            "target_email" => $email,
            "target_user_id" => (int)$user_id,
            "message_title" => $title,
            "message_content" => $content_html,
            "extra" => array()
        ));
    } catch ( \Throwable $e ) {}
}

if ( !function_exists("dist_getStatusLabel") ){
function dist_getStatusLabel($status) {
    $labels = [
        'submitted' => 'Submitted',
        'in_review' => 'In Review',
        'in_progress' => 'In Progress',
        'approved' => 'Approved',
        'launched' => 'Launched',
        'rejected' => 'Rejected',
        'taken_down' => 'Taken Down'
    ];
    return $labels[$status] ?? $status;
}
}

// Strategy-doc dashboard labels (§7): HiTune-local vs global-DSP scope
if ( !function_exists("dist_dashboard_status") ){
function dist_dashboard_status( $row ){

    $status   = is_array($row) ? ( $row['status'] ?? '' ) : $row;
    $catalog  = is_array($row) ? !empty($row['catalog_published']) : false;
    $tc       = strtolower( trim( (string)( is_array($row) ? ( $row['tunecore_status'] ?? '' ) : '' ) ) );

    if ( in_array( $status, array('submitted','in_review','in_progress'), true ) )
        return 'Pending Admin Review';
    if ( $status === 'approved' )
        return 'Approved';
    if ( $status === 'rejected' )
        return 'Rejected for All Platforms (Including HiTune Music)';
    if ( $status === 'taken_down' )
        return 'Taken Down';
    if ( $status === 'launched' ){
        if ( !$catalog ) return 'Live on HiTune';
        if ( $tc === '' ) return 'Live on HiTune';
        if ( preg_match('/declin|reject|fail|not.?distrib|refus/', $tc ) )
            return 'Live on HiTune Music | Not Distributed Globally';
        if ( preg_match('/live|distrib|deliver|accept|approv/', $tc ) )
            return 'Globally Distributed';
        return 'Live on HiTune';
    }
    return dist_getStatusLabel( $status );
}
}

if ( !defined( "DIST_PAYOUT_MIN" ) )
    define( "DIST_PAYOUT_MIN", 500.00 ); // minimum withdrawal in INR

function dist_payout_balance( $db, $user_id ){

    $user_id = (int)$user_id;
    $unpaid = 0; $earned = 0;
    $r = $db->query("SELECT
        SUM(revenue) total,
        SUM(CASE WHEN is_paid = 0 THEN revenue ELSE 0 END) unpaid
        FROM `_dist_royalties` WHERE user_id = {$user_id}");
    if ( $r && ($row = $r->fetch_assoc()) ){
        $earned = (float)$row['total'];
        $unpaid = (float)$row['unpaid'];
    }

    $pending = 0; $paid_out = 0;
    $r = $db->query("SELECT
        SUM(CASE WHEN status IN ('requested','processing') THEN amount ELSE 0 END) pending,
        SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) paid
        FROM `_dist_payouts` WHERE user_id = {$user_id}");
    if ( $r && ($row = $r->fetch_assoc()) ){
        $pending = (float)$row['pending'];
        $paid_out = (float)$row['paid'];
    }

    return array(
        'total_earned' => round( $earned, 2 ),
        'total_paid' => round( $paid_out, 2 ),
        'pending_payouts' => round( $pending, 2 ),
        'available' => round( max( 0, $unpaid - $pending ), 2 ),
        'min_withdrawal' => DIST_PAYOUT_MIN
    );
}

// Fetch the user's active subscription + plan features (assoc) or null
function dist_user_plan( $db, $user_id ){
    $user_id = (int)$user_id;
    $r = $db->query("SELECT s.id as subscription_id, s.start_date, s.end_date, s.status,
        p.id as plan_id, p.name as plan_name, p.slug as plan_slug, p.features
        FROM `_dist_subscriptions` s
        JOIN `_dist_plans` p ON s.plan_id = p.id
        WHERE s.user_id = {$user_id} AND s.status = 'active' AND s.end_date >= CURDATE()
        ORDER BY s.id DESC LIMIT 1");
    $sub = ( $r && $r->num_rows ) ? $r->fetch_assoc() : null;
    if ( !$sub ) return null;
    $sub['features'] = !empty($sub['features']) ? (array)json_decode( $sub['features'], true ) : array();
    return $sub;
}

// Count releases submitted under a subscription
function dist_release_count( $db, $user_id, $subscription_id ){
    $user_id = (int)$user_id; $subscription_id = (int)$subscription_id;
    $r = $db->query("SELECT COUNT(*) c FROM `_dist_submissions`
        WHERE user_id = {$user_id} AND subscription_id = {$subscription_id}");
    return ( $r && ($row = $r->fetch_assoc()) ) ? (int)$row['c'] : 0;
}

// Auto-generate a unique UPC-A (11 digits + valid check digit)
function dist_gen_upc( $db ){
    for ( $i = 0; $i < 25; $i++ ){
        $d11 = str_pad( (string)( ( time() % 100000000 ) * 1000 + mt_rand( 0, 999 ) ), 11, '0', STR_PAD_LEFT );
        $sum = 0;
        for ( $p = 0; $p < 11; $p++ )
            $sum += (int)$d11[$p] * ( $p % 2 === 0 ? 3 : 1 );
        $upc = $d11 . ( ( 10 - ( $sum % 10 ) ) % 10 );
        $r = $db->query("SELECT id FROM `_dist_submissions` WHERE upc = '{$upc}' LIMIT 1");
        if ( !$r || !$r->num_rows ) return $upc;
    }
    return null;
}

// Auto-generate the next ISRC: IN<registrant><year><5-digit seq>
// Registrant code configurable via setting "dist_isrc_registrant" (default HTN)
function dist_gen_isrc( $db ){
    $reg = 'HTN';
    try {
        $s = bof()->object->db_setting->get( "dist_isrc_registrant" );
        if ( $s ) $reg = strtoupper( substr( preg_replace( '/[^A-Za-z0-9]/', '', $s ), 0, 3 ) );
    } catch ( \Throwable $e ) {}
    $prefix = 'IN' . $reg . date('y');
    $seq = 0;
    foreach ( array('_dist_submissions', '_dist_tracks') as $t ){
        $r = $db->query("SELECT isrc FROM `{$t}` WHERE isrc LIKE '{$prefix}%' ORDER BY isrc DESC LIMIT 1");
        if ( $r && $r->num_rows )
            $seq = max( $seq, (int)substr( $r->fetch_assoc()['isrc'], -5 ) );
    }
    return $prefix . str_pad( $seq + 1, 5, '0', STR_PAD_LEFT );
}

// Probe an audio file's duration in seconds via ffprobe (null on failure)
function dist_probe_duration( $relative_path ){
    if ( !$relative_path ) return null;
    $full = base_root . "/" . ltrim( $relative_path, "/" );
    if ( !is_file( $full ) ) return null;
    $out = @shell_exec( "/usr/bin/ffprobe -v error -show_entries format=duration -of csv=p=0 " . escapeshellarg( $full ) . " 2>/dev/null" );
    $d = (int)round( (float)trim( (string)$out ) );
    return $d > 0 ? $d : null;
}

// Content fingerprint for copyright/dup detection (strategy doc §3 review queue).
// sha256 of the file bytes; falls back to a size+name hash when unreadable.
function dist_track_fingerprint( $relative_path ){
    if ( !$relative_path ) return null;
    $full = base_root . "/" . ltrim( $relative_path, "/" );
    if ( !is_file( $full ) ) return null;
    $h = @hash_file( "sha256", $full );
    if ( $h ) return $h;
    return md5( filesize($full) . ":" . basename( $relative_path ) );
}

// If any track of this submission shares a fingerprint with a DIFFERENT
// submission, flag it for manual review (in_review + admin_notes).
function dist_flag_duplicate_fingerprint( $db, $submission_id ){
    $sid = (int)$submission_id;
    $r = $db->query( "SELECT t.fingerprint, GROUP_CONCAT(DISTINCT o.submission_id) others
        FROM `_dist_tracks` t
        JOIN `_dist_tracks` o ON o.fingerprint = t.fingerprint AND o.submission_id != t.submission_id
        WHERE t.submission_id = {$sid} AND t.fingerprint IS NOT NULL AND t.fingerprint != ''
        GROUP BY t.fingerprint LIMIT 1" );
    if ( !$r || !$r->num_rows ) return false;
    $others = $r->fetch_assoc()["others"];
    $note = "Duplicate audio fingerprint matches submission #" . $others . " — flagged for manual copyright/reupload review";
    $db->query( "UPDATE `_dist_submissions` SET status='in_review',
        admin_notes = CONCAT(COALESCE(admin_notes,''), '\n[review] {$note}')
        WHERE id = {$sid} AND status IN ('submitted','approved')" );
    if ( function_exists("dist_admin_log") )
        dist_admin_log( $db, null, $sid, 'fingerprint_flag', null, 'in_review', $note );
    return true;
}

// §3 policy review heuristics — runs alongside the fingerprint flag.
// Flags for MANUAL review (never auto-rejects):
//   - declared AI content whose tool list mentions voice-clone / free-tier
//     generators (policy only allows paid commercial tiers like Suno Pro/Udio)
//   - AI tools listed but ai_pct = 0 / ai_declared missing (contradiction)
//   - bulk upload spam: same uploader submitted >= 5 releases in 24h
function dist_flag_ai_risk( $db, $submission_id, $note_only=false ){
    $sid = (int)$submission_id;
    $r = $db->query( "SELECT ai_pct, ai_tools, ai_declared, user_id, time_add FROM `_dist_submissions` WHERE id = {$sid} LIMIT 1" );
    if ( !$r || !$r->num_rows ) return false;
    $sub = $r->fetch_assoc();

    $notes = array();
    $ai_pct = (int)$sub["ai_pct"];
    $tools  = strtolower( (string)$sub["ai_tools"] );

    if ( $ai_pct > 0 || !empty($sub["ai_declared"]) ){
        // voice cloning / free-tier generators are prohibited by policy
        $banned = array( "rvc", "so-vits", "so_vits", "uberduck", "voice clone", "voiceclone", "voice cloning",
                         "elevenlabs clone", "imitat", "deepfake", "faceless", "kits.ai free", "free tier", "suno free", "udio free" );
        foreach ( $banned as $b )
            if ( $b !== "" && strpos( $tools, $b ) !== false )
                $notes[] = "Declared AI tools contain prohibited/clone-tier keyword '{$b}' — verify commercial license & voice rights";
    }
    if ( $tools !== "" && $ai_pct <= 0 )
        $notes[] = "AI tools declared ({$tools}) but AI percentage is 0 — metadata contradiction, verify declaration";
    if ( $ai_pct > 0 && empty($sub["ai_declared"]) )
        $notes[] = "AI-generated content ({$ai_pct}%) without the policy declaration checkbox";

    // bulk / spam upload heuristic — royalty-farming guard (doc §3 'Bulk / Spam Uploads')
    $uid = (int)$sub["user_id"];
    if ( $uid ){
        $c = $db->query( "SELECT COUNT(*) c FROM `_dist_submissions` WHERE user_id = {$uid} AND time_add >= DATE_SUB(NOW(), INTERVAL 24 HOUR)" );
        $cnt = $c ? (int)$c->fetch_assoc()["c"] : 0;
        if ( $cnt >= 5 )
            $notes[] = "Bulk upload pattern: {$cnt} submissions by user #{$uid} within 24h";
    }

    if ( !$notes ) return false;

    $note = implode( "; ", $notes );
    if ( $note_only ){
        $db->query( "UPDATE `_dist_submissions` SET
            admin_notes = CONCAT(COALESCE(admin_notes,''), '\n[review] {$db->real_escape_string($note)}')
            WHERE id = {$sid}" );
    } else {
        $db->query( "UPDATE `_dist_submissions` SET status='in_review',
            admin_notes = CONCAT(COALESCE(admin_notes,''), '\n[review] {$db->real_escape_string($note)}')
            WHERE id = {$sid} AND status IN ('submitted','approved')" );
    }
    if ( function_exists("dist_admin_log") )
        dist_admin_log( $db, null, $sid, 'ai_risk_flag', null, 'in_review', $note );
    return true;
}

// Register a file path in _bof_files and return its ID (reuses existing file on disk)
function dist_register_file( $db, $rel_path, $type, $object_type, $user_id ){
    $full = base_root . "/" . ltrim( $rel_path, "/" );
    if ( !is_file( $full ) ) return null;
    $r = $db->query("SELECT ID FROM `_bof_files` WHERE path = '" . $db->real_escape_string( $rel_path ) . "' LIMIT 1");
    if ( $r && $r->num_rows ) return (int)$r->fetch_assoc()['ID'];
    $pass  = substr( md5( uniqid() . mt_rand() ), 0, 10 );
    $name  = $db->real_escape_string( pathinfo( $rel_path, PATHINFO_FILENAME ) );
    $ext   = $db->real_escape_string( strtolower( pathinfo( $rel_path, PATHINFO_EXTENSION ) ) );
    $mime  = $db->real_escape_string( $type === 'image' ? ( $ext === 'png' ? 'image/png' : ( $ext === 'webp' ? 'image/webp' : 'image/jpeg' ) ) : 'audio/mpeg' );
    $size  = (float)filesize( $full );
    $data_sql = "NULL";
    if ( $type === 'image' ){
        $idata = dist_make_thumbs( $full, $rel_path );
        if ( $idata ) $data_sql = "'" . $db->real_escape_string( json_encode( $idata ) ) . "'";
    }
    $db->query("INSERT INTO `_bof_files` (pass, type, host_id, dest_host_id, user_id, path, name, extension, mime_type, object_type, size, used, data)
        VALUES ('{$pass}', '{$type}', 1, 0, " . (int)$user_id . ", '" . $db->real_escape_string($rel_path) . "', '{$name}', '{$ext}', '{$mime}', '{$object_type}', {$size}, 1, {$data_sql})");
    return (int)$db->insert_id;
}

// Generate sister thumbnails (500/300/100) next to the image + build the data JSON
function dist_make_thumbs( $full_path, $rel_path ){
    $info = @getimagesize( $full_path );
    if ( !$info ) return null;
    list( $w, $h ) = $info;
    $ext = strtolower( pathinfo( $full_path, PATHINFO_EXTENSION ) );
    $img = null;
    if ( $ext === 'png' ) $img = @imagecreatefrompng( $full_path );
    elseif ( $ext === 'webp' && function_exists('imagecreatefromwebp') ) $img = @imagecreatefromwebp( $full_path );
    else $img = @imagecreatefromjpeg( $full_path );
    if ( !$img ) return null;

    $dir = dirname( $full_path );
    $rel_dir = dirname( $rel_path );
    $sisters = array();
    foreach ( array( 500 => '500', 300 => '300', 100 => 'thumb' ) as $px => $key ){
        $tw = min( $px, $w ); $th = (int)round( $h * ( $tw / $w ) );
        $thumb = imagecreatetruecolor( $tw, $th );
        imagecopyresampled( $thumb, $img, 0, 0, 0, 0, $tw, $th, $w, $h );
        $tname = uniqid();
        $tfile = $dir . "/" . $tname . ".jpg";
        imagejpeg( $thumb, $tfile, 88 );
        imagedestroy( $thumb );
        $sisters[$key] = array(
            'name' => $tname,
            'path' => $rel_dir . "/" . $tname . ".jpg",
            'size' => filesize( $tfile ),
            'width' => $tw,
            'height' => $th
        );
    }
    imagedestroy( $img );

    return array(
        'total_size' => filesize( $full_path ),
        'width' => $w,
        'height' => $h,
        'size' => filesize( $full_path ),
        '_sisters' => $sisters
    );
}

function dist_slug( $s ){
    $s = strtolower( trim( (string)$s ) );
    $s = preg_replace( '/[^a-z0-9]+/', '', $s );
    return $s !== '' ? $s : 'x' . substr( md5( microtime() . mt_rand() ), 0, 6 );
}

// seo_url style slug: spaces -> _, other separators -> -
function dist_seo( $s ){
    $s = strtolower( trim( (string)$s ) );
    $s = preg_replace( '/[\s]+/', '_', $s );
    $s = preg_replace( '/[^a-z0-9_\-]+/', '-', $s );
    $s = trim( preg_replace( '/[\-_]{2,}/', '_', $s ), '-_' );
    return $s !== '' ? mb_substr( $s, 0, 95 ) : 'x' . substr( md5( microtime() . mt_rand() ), 0, 6 );
}

// Find or create a catalog artist by name; returns artist ID
function dist_catalog_artist( $db, $name, $user_id, $cover_file_id = null ){
    $code = dist_slug( $name );
    $name_sql = $db->real_escape_string( $name );
    $seo = $db->real_escape_string( dist_seo( $name ) );
    $r = $db->query("SELECT ID FROM `_c_m_artists` WHERE code = '" . $db->real_escape_string($code) . "' OR name = '{$name_sql}' OR seo_url = '{$seo}' LIMIT 1");
    if ( $r && $r->num_rows ) return (int)$r->fetch_assoc()['ID'];
    $hash = md5( uniqid() . mt_rand() );
    $cover_sql = $cover_file_id ? (int)$cover_file_id : "NULL";
    $db->query("INSERT INTO `_c_m_artists` (hash, code, name, seo_url, cover_id, manager_id)
        VALUES ('{$hash}', '{$code}', '{$name_sql}', '{$seo}', {$cover_sql}, " . (int)$user_id . ")");
    if ( $db->insert_id ) return (int)$db->insert_id;
    // unique-key race/case collision — re-resolve
    $r = $db->query("SELECT ID FROM `_c_m_artists` WHERE code = '" . $db->real_escape_string($code) . "' OR seo_url = '{$seo}' LIMIT 1");
    return ( $r && $r->num_rows ) ? (int)$r->fetch_assoc()['ID'] : null;
}

// Find or create a genre row by name; returns genre ID or null
function dist_catalog_genre( $db, $name ){
    if ( !$name ) return null;
    $name_sql = $db->real_escape_string( trim( $name ) );
    $seo  = $db->real_escape_string( dist_seo( $name ) );
    $code = $db->real_escape_string( dist_slug( $name ) );
    // match on every unique-looking key — name lookup alone misses rows whose
    // stored case/collation differs, then the seo_url unique key collides
    $r = $db->query("SELECT ID FROM `_c_m_genres` WHERE name = '{$name_sql}' OR seo_url = '{$seo}' OR code = '{$code}' LIMIT 1");
    if ( $r && $r->num_rows ) return (int)$r->fetch_assoc()['ID'];
    $hash = md5( uniqid() . mt_rand() );
    if ( !$db->query("INSERT INTO `_c_m_genres` (hash, code, name, seo_url) VALUES ('{$hash}', '{$code}', '{$name_sql}', '{$seo}')") ){
        $r = $db->query("SELECT ID FROM `_c_m_genres` WHERE seo_url = '{$seo}' OR code = '{$code}' LIMIT 1");
        return ( $r && $r->num_rows ) ? (int)$r->fetch_assoc()['ID'] : null;
    }
    return $db->insert_id ? (int)$db->insert_id : null;
}

/**
 * Publish a dist submission into the streaming catalog (_c_m_* tables).
 * Returns array( 'track_ids' => [...] ) on success or array('error'=>...) on failure.
 */
function dist_publish_to_catalog( $db, $submission_id ){
    try {
        return dist_publish_to_catalog_run( $db, $submission_id );
    } catch ( \Throwable $e ) {
        return array( 'error' => 'publish_exception', 'detail' => substr( $e->getMessage(), 0, 200 ) );
    }
}

function dist_publish_to_catalog_run( $db, $submission_id ){

    $submission_id = (int)$submission_id;
    $r = $db->query("SELECT * FROM `_dist_submissions` WHERE id = {$submission_id} LIMIT 1");
    $sub = ( $r && $r->num_rows ) ? $r->fetch_assoc() : null;
    if ( !$sub ) return array( 'error' => 'sub_not_found' );
    if ( !empty($sub['catalog_published']) ) return array( 'error' => 'already_published' );

    $user_id = (int)$sub['user_id'];

    $tracks = array();
    $r = $db->query("SELECT * FROM `_dist_tracks` WHERE submission_id = {$submission_id} ORDER BY track_number ASC");
    while ( $r && ($row = $r->fetch_assoc()) ) $tracks[] = $row;

    // fallback: no track rows but a main audio file exists → single-track release
    if ( !$tracks && !empty($sub['audio_file_path']) )
        $tracks[] = array( 'track_number' => 1, 'title' => $sub['title'], 'artist_name' => $sub['artist_name'], 'duration' => null, 'audio_file_path' => $sub['audio_file_path'] );

    if ( !$tracks ) return array( 'error' => 'no_tracks' );

    // cover art → _bof_files (m_track_c)
    $cover_file_id = null;
    if ( !empty($sub['cover_art_path']) )
        $cover_file_id = dist_register_file( $db, $sub['cover_art_path'], 'image', 'm_track_c', $user_id );

    // artist
    $artist_id = dist_catalog_artist( $db, $sub['artist_name'], $user_id, $cover_file_id );
    if ( !$artist_id ) return array( 'error' => 'artist_failed' );

    // album row (singles get type=single albums, like existing catalog data)
    $album_title = !empty($sub['album_name']) ? $sub['album_name'] : $sub['title'];
    $album_type  = in_array( $sub['type'], array('single','album','ep'), true ) ? $sub['type'] : 'album';
    $album_hash  = md5( uniqid() . mt_rand() );
    $album_code  = dist_unique_code( $db, "_c_m_albums", dist_slug( $sub['artist_name'] . '_' . $album_title . '_' . $album_type ) );
    $album_seo   = $db->real_escape_string( dist_unique_code( $db, "_c_m_albums", dist_seo( $sub['artist_name'] ) . '-' . dist_seo( $album_title ), "seo_url" ) );
    $cover_sql   = $cover_file_id ? (int)$cover_file_id : "NULL";
    $db->query("INSERT INTO `_c_m_albums` (hash, code, title, type, seo_url, cover_id, artist_id, uploader_id, description)
        VALUES ('{$album_hash}', '{$album_code}', '" . $db->real_escape_string($album_title) . "', '{$album_type}', '{$album_seo}',
        {$cover_sql}, {$artist_id}, {$user_id}, " . ( !empty($sub['description']) ? "'" . $db->real_escape_string($sub['description']) . "'" : "NULL" ) . ")");
    $album_id = (int)$db->insert_id;
    if ( !$album_id ) return array( 'error' => 'album_failed' );

    // genre relations
    $genre_id = dist_catalog_genre( $db, $sub['genre'] );
    if ( $genre_id && $album_id )
        $db->query("INSERT INTO `_c_m_albums_relations` (album_id, target_id, type, i) VALUES ({$album_id}, {$genre_id}, 'genre', 0)");
    if ( $genre_id && $artist_id )
        $db->query("INSERT IGNORE INTO `_c_m_artists_relations` (artist_id, target_id, type, i)
            SELECT {$artist_id}, {$genre_id}, 'genre', COALESCE(MAX(i),-1)+1 FROM `_c_m_artists_relations` WHERE artist_id = {$artist_id} AND type = 'genre'");

    $track_ids = array();
    foreach ( $tracks as $t ){
        $audio_rel = !empty($t['audio_file_path']) ? $t['audio_file_path'] : null;
        if ( !$audio_rel ) continue;

        $file_id = dist_register_file( $db, $audio_rel, 'audio', 'm_track_source', $user_id );
        if ( !$file_id ) continue;

        $duration = !empty($t['duration']) ? (float)$t['duration'] : null;
        if ( !$duration && function_exists("dist_probe_duration") ) $duration = dist_probe_duration( $audio_rel );
        $title = !empty($t['title']) ? $t['title'] : $sub['title'];
        $track_hash = md5( uniqid() . mt_rand() );
        $track_code = dist_unique_code( $db, "_c_m_tracks", dist_slug( $sub['artist_name'] . '_' . $title ) );
        $track_seo  = $db->real_escape_string( dist_unique_code( $db, "_c_m_tracks", dist_seo( $sub['artist_name'] ) . '-' . dist_seo( $title ), "seo_url" ) );
        $dur_sql = $duration ? (float)$duration : "NULL";
        $idx = (int)$t['track_number'];

        $track_ai = isset($t['ai_pct']) && (int)$t['ai_pct'] > 0 ? (int)$t['ai_pct'] : (int)( $sub['ai_pct'] ?? 0 );

        $db->query("INSERT INTO `_c_m_tracks` (hash, code, title, duration, seo_url, cover_id, artist_id, album_id, album_index, uploader_id, s_sources, s_sources_local, explicit, ai_pct)
            VALUES ('{$track_hash}', '{$track_code}', '" . $db->real_escape_string($title) . "', {$dur_sql}, '{$track_seo}',
            {$cover_sql}, {$artist_id}, {$album_id}, {$idx}, {$user_id}, 1, 1, 0, {$track_ai})");
        $track_id = (int)$db->insert_id;
        if ( !$track_id ) continue;

        // source row
        $src_hash = md5( uniqid() . mt_rand() );
        $src_data = $db->real_escape_string( json_encode( array( 'file_type' => 'local', 'local_file' => $file_id ) ) );
        $db->query("INSERT INTO `_c_m_tracks_sources` (hash, target_id, type, download_able, stream_able, encrypted, duration, quality, data)
            VALUES ('{$src_hash}', {$track_id}, 'audio', 1, 1, 0, " . ($duration ? (float)$duration : 0) . ", 4, '{$src_data}')");
        $src_id = (int)$db->insert_id;

        // file usage links
        $db->query("UPDATE `_bof_files` SET used_in = 'm_track_source{$src_id}', used_in_object = 'm_track_source' WHERE ID = {$file_id}");
        if ( $cover_file_id )
            $db->query("UPDATE `_bof_files` SET used_in = 'm_track{$track_id}', used_in_object = 'm_track' WHERE ID = {$cover_file_id}");

        if ( $genre_id )
            $db->query("INSERT INTO `_c_m_tracks_relations` (track_id, target_id, type, i) VALUES ({$track_id}, {$genre_id}, 'genre', 0)");

        $track_ids[] = $track_id;
    }

    if ( !$track_ids ) return array( 'error' => 'no_tracks_published' );

    // keep artist/album counters roughly accurate
    $db->query("UPDATE `_c_m_albums` SET s_tracks = " . count($track_ids) . " WHERE ID = {$album_id}");
    $db->query("UPDATE `_c_m_artists` a SET s_tracks = (SELECT COUNT(*) FROM `_c_m_tracks` t WHERE t.artist_id = a.ID) WHERE a.ID = {$artist_id}");

    $ids_sql = $db->real_escape_string( json_encode( $track_ids ) );
    $db->query("UPDATE `_dist_submissions` SET catalog_published = 1, catalog_track_ids = '{$ids_sql}' WHERE id = {$submission_id}");

    // IyolMe sound-registry sync — best effort, never blocks the publish
    try {
        foreach ( $track_ids as $_tid )
            bof()->iyolme->queue_sound( (int)$_tid );
    } catch ( \Throwable $e ) {
        if ( function_exists("dist_admin_log") )
            dist_admin_log( $db, null, $submission_id, 'iyol_sync_failed', null, null,
                "IyolMe sync error: " . substr( $e->getMessage(), 0, 200 ) );
    }

    return array( 'track_ids' => $track_ids, 'album_id' => $album_id, 'artist_id' => $artist_id );
}

// Make a slug unique in a catalog table's unique column (appends -2, -3, ...)
if ( !function_exists("dist_unique_code") ){
function dist_unique_code( $db, $table, $code, $col = "code" ){
    $base = $code; $i = 1;
    $col = preg_match('/^[a-z_]+$/', $col) ? $col : "code";
    while ( true ){
        $c = $db->real_escape_string( $code );
        $r = $db->query("SELECT ID FROM `{$table}` WHERE `{$col}` = '{$c}' LIMIT 1");
        if ( !$r || !$r->num_rows ) return $code;
        $code = $base . "-" . (++$i);
        if ( $i > 50 ) return $base . "-" . substr( md5(uniqid().mt_rand()), 0, 6 );
    }
}
}

// Pull a release off the HiTune catalog (full rejection / takedown) — doc §7
// Removes the catalog rows created by dist_publish_to_catalog and resets the
// submission so it can be re-published if re-approved later.
if ( !function_exists("dist_unpublish_catalog") ){
function dist_unpublish_catalog( $db, $submission_id ){

    $submission_id = (int)$submission_id;
    $r = $db->query("SELECT catalog_track_ids FROM `_dist_submissions` WHERE id = {$submission_id} LIMIT 1");
    $sub = ( $r && $r->num_rows ) ? $r->fetch_assoc() : null;
    $track_ids = array_filter( array_map( "intval", (array) json_decode( (string)( $sub['catalog_track_ids'] ?? '' ), true ) ) );
    if ( !$track_ids ) return array( 'removed' => 0 );

    $album_ids = array(); $artist_ids = array(); $removed = 0;
    $in = implode( ',', $track_ids );
    $r2 = $db->query("SELECT ID, album_id, artist_id FROM `_c_m_tracks` WHERE ID IN ({$in})");
    while ( $r2 && ($t = $r2->fetch_assoc()) ){
        if ( $t['album_id'] ) $album_ids[] = (int)$t['album_id'];
        if ( $t['artist_id'] ) $artist_ids[] = (int)$t['artist_id'];
    }

    $db->query("DELETE FROM `_c_m_tracks_sources` WHERE target_id IN ({$in})");
    $db->query("DELETE FROM `_c_m_tracks_relations` WHERE track_id IN ({$in})");
    $db->query("DELETE FROM `_c_m_tracks` WHERE ID IN ({$in})");
    $removed = (int)$db->affected_rows;

    // drop albums that were created solely for this release (now empty)
    foreach ( array_unique($album_ids) as $aid ){
        $r3 = $db->query("SELECT COUNT(*) c FROM `_c_m_tracks` WHERE album_id = {$aid}");
        $c = ( $r3 && $r3->num_rows ) ? (int)$r3->fetch_assoc()['c'] : 0;
        if ( !$c ){
            $db->query("DELETE FROM `_c_m_albums` WHERE ID = {$aid}");
            $db->query("DELETE FROM `_c_m_albums_relations` WHERE album_id = {$aid}");
        }
    }

    // artist stats
    foreach ( array_unique($artist_ids) as $aid )
        $db->query("UPDATE `_c_m_artists` a SET s_tracks = (SELECT COUNT(*) FROM `_c_m_tracks` t WHERE t.artist_id = a.ID) WHERE a.ID = {$aid}");

    $db->query("UPDATE `_dist_submissions` SET catalog_published = 0, catalog_track_ids = NULL WHERE id = {$submission_id}");

    return array( 'removed' => $removed, 'track_ids' => $track_ids );
}
}

// Register client endpoints
bof()->object->endpoint->add( "dist_db_setup", array(
    "url" => "dist/db-setup",
    "skip_key_check" => true,
    "groups" => [ "api" ],
    "executers" => array( $endpoints_dir . "endpoint_dist_db_setup.php" )
));

bof()->object->endpoint->add( "dist_plans", array(
    "url" => "dist/plans",
    "groups" => [ "api" ],
    "executers" => array( $endpoints_dir . "endpoint_dist_plans.php" )
));

bof()->object->endpoint->add( "dist_submit", array(
    "url" => "dist/submit",
    "skip_key_check" => true,
    "groups" => [ "user" ],
    "executers" => array( $endpoints_dir . "endpoint_dist_submit.php" )
));

bof()->object->endpoint->add( "dist_dashboard", array(
    "url" => "dist/dashboard",
    "groups" => [ "user" ],
    "executers" => array( $endpoints_dir . "endpoint_dist_dashboard.php" )
));

bof()->object->endpoint->add( "dist_checkout", array(
    "url" => "dist/checkout",
    "skip_key_check" => true,
    "groups" => [ "api" ],
    "executers" => array( $endpoints_dir . "endpoint_dist_checkout.php" )
));

bof()->object->endpoint->add( "dist_admin", array(
    "url" => "dist/admin",
    "skip_key_check" => true,
    "groups" => [ "user" ],
    "executers" => array( $endpoints_dir . "endpoint_dist_admin.php" )
));

bof()->object->endpoint->add( "dist_submission", array(
    "url" => "dist/submission",
    "skip_key_check" => true,
    "groups" => [ "user" ],
    "executers" => array( $endpoints_dir . "endpoint_dist_submission.php" )
));

bof()->object->endpoint->add( "dist_payout", array(
    "url" => "dist/payout",
    "skip_key_check" => true,
    "groups" => [ "user" ],
    "executers" => array( $endpoints_dir . "endpoint_dist_payout.php" )
));

// Fan-to-artist tipping wallet (doc §4)
bof()->object->endpoint->add( "dist_tip", array(
    "url" => "dist/tip",
    "skip_key_check" => true,
    "groups" => [ "user" ],
    "executers" => array( $endpoints_dir . "endpoint_dist_tip.php" )
));

// Live listening parties (doc §8)
bof()->object->endpoint->add( "htx_party", array(
    "url" => "htx/party",
    "skip_key_check" => true,
    "groups" => [ "user" ],
    "executers" => array( $endpoints_dir . "endpoint_htx_party.php" )
));

// Internal bridge: distribution web portal -> catalog + IyolMe (HMAC auth)
bof()->object->endpoint->add( "dist_ecosystem", array(
    "url" => "v1/dist/ecosystem",
    "skip_key_check" => true,
    "groups" => [],
    "response_type" => "json",
    "executers" => array( $endpoints_dir . "endpoint_dist_ecosystem.php" )
));

// Artist Panel bridge: verification + analytics for distribution.hitune.in (HMAC auth)
bof()->object->endpoint->add( "dist_artist", array(
    "url" => "v1/dist/artist",
    "skip_key_check" => true,
    "groups" => [],
    "response_type" => "json",
    "executers" => array( $endpoints_dir . "endpoint_dist_artist.php" )
));

// AI Creator Studio job API (doc §4/§9)
bof()->object->endpoint->add( "ai_studio", array(
    "url" => "ai_studio",
    "skip_key_check" => true,
    "groups" => [ "user" ],
    "executers" => array( $endpoints_dir . "endpoint_ai_studio.php" )
));

// Public web mirrors of the signed htx app endpoints — hub.php calls these
// from the browser without request signing (read-only public data).
foreach ( array(
    "hub_charts"   => array( "hub/charts",   "endpoint_htx_charts.php" ),
    "hub_clips"    => array( "hub/clips",    "endpoint_htx_clips.php" ),
    "hub_radio"    => array( "hub/radio",    "endpoint_htx_radio.php" ),
    "hub_contests" => array( "hub/contests", "endpoint_htx_contests.php" ),
) as $hub_name => $hub ){
    bof()->object->endpoint->add( $hub_name, array(
        "url" => $hub[0],
        "skip_key_check" => true,
        "groups" => [ "v1_public" ],
        "response_type" => "json",
        "executers" => array( root . "/app/client/endpoints/" . $hub[1] )
    ));
}

// Party listing is public inside the executor (write actions still require
// a logged-in session) — mirror it for the web hub too.
bof()->object->endpoint->add( "hub_party", array(
    "url" => "hub/party",
    "skip_key_check" => true,
    "groups" => [ "v1_public" ],
    "response_type" => "json",
    "executers" => array( $endpoints_dir . "endpoint_htx_party.php" )
));
