<?php

/**
 * Music Distribution - Submit Music
 * Allows users to submit their music for distribution
 */

if ( !defined("bof_root") ) die;

function endpoint_dist_submit( $loader, $excuter, $args ){

    $user = $loader->user->check();
    if (!$user || empty($user->ID)) {
        return $loader->api->set_error("access_denied");
    }
    $user_id = $user->ID;
    $input = dist_getInput();

    // Check if user has active subscription
    $db = $loader->db;
        $sub_check = $db->query("SELECT id, plan_id FROM `_dist_subscriptions`
            WHERE user_id = {$user_id} AND status = 'active' AND end_date >= CURDATE()");

        if (!$sub_check || $sub_check->num_rows === 0) {
            return $loader->api->set_error("get_access_by_plans", array('code' => 'no_subscription'));
        }
        
        $subscription = $sub_check->fetch_assoc();
        $subscription_id = $subscription['id'];

        // Plan features enforcement
        $plan = function_exists("dist_user_plan") ? dist_user_plan( $db, $user_id ) : null;
        $features = $plan ? $plan['features'] : array();
        if ( $plan && empty($features['unlimited_releases']) && !empty($features['max_releases']) ){
            $used = function_exists("dist_release_count") ? dist_release_count( $db, $user_id, $subscription_id ) : 0;
            if ( $used >= (int)$features['max_releases'] )
                return $loader->api->set_error("invalid_input", array(
                    'code' => 'release_limit_reached',
                    'limit' => (int)$features['max_releases'],
                    'used' => $used
                ));
        }

        // Validate required fields
        $required = ['title', 'artist_name', 'type'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                return $loader->api->set_error("invalid_input", array('input_name' => $field, 'code' => 'missing_field'));
            }
        }

        $type = in_array($input['type'], ['single', 'album', 'ep']) ? $input['type'] : 'single';
        $title = $db->real_escape_string($input['title']);
        $artist_name = $db->real_escape_string($input['artist_name']);
        $album_name = !empty($input['album_name']) ? $db->real_escape_string($input['album_name']) : null;
        $genre = !empty($input['genre']) ? $db->real_escape_string($input['genre']) : null;
        $description = !empty($input['description']) ? $db->real_escape_string($input['description']) : null;
        $language = !empty($input['language']) ? $db->real_escape_string($input['language']) : null;
        $isrc = !empty($input['isrc']) ? $db->real_escape_string( strtoupper(trim($input['isrc'])) ) : null;
        $upc = !empty($input['upc']) ? $db->real_escape_string( preg_replace('/\D/', '', $input['upc']) ) : null;

        // Plan may disallow artist-supplied codes — auto-generate instead
        if ( !empty($features) && empty($features['own_isrc']) ) $isrc = null;
        if ( !empty($features) && empty($features['own_upc']) ) $upc = null;

        // Auto-generate codes when the artist leaves them blank (or plan disallows them)
        $isrc_auto = false; $upc_auto = false;
        if ( !$isrc && function_exists("dist_gen_isrc") ){ $isrc = dist_gen_isrc( $db ); $isrc_auto = true; }
        if ( !$upc && function_exists("dist_gen_upc") ){ $upc = dist_gen_upc( $db ); $upc_auto = true; }
        $label_name = !empty($input['label_name']) ? $db->real_escape_string($input['label_name']) : null;
        if ( !empty($features) && empty($features['custom_label']) ) $label_name = "Hitune Music";
        $recording_location = !empty($input['recording_location']) ? $db->real_escape_string($input['recording_location']) : null;

        // AI-generation metadata (strategy doc §3 — mandatory tagging)
        $ai_pct = isset($input['ai_pct']) ? max(0, min(100, (int)$input['ai_pct'])) : 0;
        $ai_tools = !empty($input['ai_tools']) ? $db->real_escape_string( substr($input['ai_tools'], 0, 255) ) : null;
        $ai_declared = !empty($input['ai_declaration']) ? 1 : 0;
        
        $release_date = !empty($input['release_date']) ? $db->real_escape_string($input['release_date']) : null;
        
        // Platforms / countries arrive as JSON strings from FormData — decode before re-encoding
        if ( !empty($input['platforms']) && is_string($input['platforms']) ) {
            $decoded = json_decode($input['platforms'], true);
            $input['platforms'] = is_array($decoded) ? $decoded : [];
        }
        if ( !empty($input['countries']) && is_string($input['countries']) ) {
            $decoded = json_decode($input['countries'], true);
            $input['countries'] = is_array($decoded) ? $decoded : [];
        }

        // Handle platforms
        $platforms = !empty($input['platforms']) ? json_encode($input['platforms']) : json_encode([
            'Spotify', 'Apple Music', 'Amazon Music', 'YouTube Music', 'Tidal'
        ]);

        // Handle country restrictions (some plans can't restrict territories)
        $countries = !empty($input['countries']) ? json_encode($input['countries']) : json_encode(['all']);
        if ( !empty($features) && empty($features['country_restrictions']) )
            $countries = json_encode(['all']);

        // Plans without release scheduling go live immediately
        if ( !empty($features) && empty($features['schedule_release']) )
            $release_date = null;

        // Handle file uploads
        $cover_art_path = null;
        $audio_file_path = null;

        // Process cover art upload (DSPs require square art, min 1400x1400)
        if (!empty($_FILES['cover_art']) && $_FILES['cover_art']['error'] === 0) {
            $cover_art_path = dist_handleFileUpload($loader, $_FILES['cover_art'], 'covers', $user_id);
            if ( $cover_art_path ){
                $info = @getimagesize( base_root . "/" . $cover_art_path );
                if ( !$info ){
                    @unlink( base_root . "/" . $cover_art_path );
                    $cover_art_path = null;
                } else {
                    $w = (int)$info[0]; $h = (int)$info[1];
                    if ( $w < 1400 || $h < 1400 ){
                        @unlink( base_root . "/" . $cover_art_path );
                        return $loader->api->set_error("invalid_input", array('input_name' => 'cover_art', 'code' => 'cover_too_small', 'min' => 1400, 'got' => $w . 'x' . $h));
                    }
                    if ( abs($w - $h) > max(2, (int)($w * 0.02)) ){
                        @unlink( base_root . "/" . $cover_art_path );
                        return $loader->api->set_error("invalid_input", array('input_name' => 'cover_art', 'code' => 'cover_not_square', 'got' => $w . 'x' . $h));
                    }
                }
            }
        }
        if ( !$cover_art_path )
            return $loader->api->set_error("invalid_input", array('input_name' => 'cover_art', 'code' => 'cover_required'));

        // Process audio file upload (direct audio_file or first tracks[i][audio])
        $main_audio_track_index = null;
        if (!empty($_FILES['audio_file']) && $_FILES['audio_file']['error'] === 0) {
            $audio_file_path = dist_handleFileUpload($loader, $_FILES['audio_file'], 'audio', $user_id);
        } else if ( !empty($input['tracks']) && is_array($input['tracks']) ) {
            $first_track_index = array_key_first($input['tracks']);
            $first_track_file = dist_getTrackFile( $first_track_index );
            if ( $first_track_file ) {
                $audio_file_path = dist_handleFileUpload($loader, $first_track_file, 'audio', $user_id);
                $main_audio_track_index = $first_track_index;
            }
        }

        // At least one audio file is required for distribution
        if ( !$audio_file_path )
            return $loader->api->set_error("invalid_input", array('input_name' => 'audio', 'code' => 'audio_required'));

        // Insert submission
        $sql = "INSERT INTO `_dist_submissions` 
            (user_id, subscription_id, type, title, artist_name, album_name, genre, 
            release_date, description, language, isrc, upc, label_name, recording_location,
            cover_art_path, audio_file_path, platforms, countries, status,
            ai_pct, ai_tools, ai_declared)
            VALUES 
            ({$user_id}, {$subscription_id}, '{$type}', '{$title}', '{$artist_name}', 
            " . ($album_name ? "'{$album_name}'" : "NULL") . ",
            " . ($genre ? "'{$genre}'" : "NULL") . ",
            " . ($release_date ? "'{$release_date}'" : "NULL") . ",
            " . ($description ? "'{$description}'" : "NULL") . ",
            " . ($language ? "'{$language}'" : "NULL") . ",
            " . ($isrc ? "'{$isrc}'" : "NULL") . ",
            " . ($upc ? "'{$upc}'" : "NULL") . ",
            " . ($label_name ? "'{$label_name}'" : "NULL") . ",
            " . ($recording_location ? "'{$recording_location}'" : "NULL") . ",
            " . ($cover_art_path ? "'{$cover_art_path}'" : "NULL") . ",
            " . ($audio_file_path ? "'{$audio_file_path}'" : "NULL") . ",
            '{$platforms}', '{$countries}', 'submitted',
            {$ai_pct}, " . ($ai_tools ? "'{$ai_tools}'" : "NULL") . ", {$ai_declared})";

        if ($db->query($sql)) {
            $submission_id = $db->insert_id;
            
            // Log the activity
            $log_details = "New {$type} submission: {$title} by {$artist_name}";
            if ( $isrc_auto && $isrc ) $log_details .= " | auto-ISRC: {$isrc}";
            if ( $upc_auto && $upc ) $log_details .= " | auto-UPC: {$upc}";
            dist_logActivity($loader, $user_id, $submission_id, 'submission_created', null, 'submitted', $log_details);
            
            // Handle release tracks (single, EP and album forms all send tracks[])
            if (!empty($input['tracks']) && is_array($input['tracks'])) {
                dist_saveAlbumTracks($loader, $submission_id, $input['tracks'], $user_id, $audio_file_path, $main_audio_track_index, $ai_pct);
            }

            // §3 review queue — flag duplicate audio fingerprints + AI policy risks
            if ( function_exists("dist_flag_duplicate_fingerprint") )
                dist_flag_duplicate_fingerprint( $db, $submission_id );
            if ( function_exists("dist_flag_ai_risk") )
                dist_flag_ai_risk( $db, $submission_id );

            // Notify admin of the new release submission
            if ( function_exists("dist_notify_admin") ){
                dist_notify_admin(
                    "New release submission #{$submission_id}",
                    "A new {$type} was submitted for distribution.<br><br>" .
                    "<b>Title:</b> " . htmlspecialchars($input['title']) . "<br>" .
                    "<b>Artist:</b> " . htmlspecialchars($input['artist_name']) . "<br>" .
                    "<b>Type:</b> {$type}<br><br>" .
                    "Review it in the <a href='" . web_address . "/distribution/admin'>distribution admin</a>."
                );
            }

            $loader->api->set_message( "ok", [
                'message' => 'Submission created successfully',
                'submission_id' => $submission_id,
                'isrc' => $isrc,
                'upc' => $upc,
                'status' => 'submitted',
                'next_steps' => 'Your submission is under review. Our team will verify your music and metadata before distributing to platforms.'
            ]);
        } else {
            return $loader->api->set_error("failed", array('code' => 'create_failed'));
        }
    }

/**
 * Return a normalized file array for tracks[i][audio] from $_FILES['tracks']
 */
function dist_getTrackFile( $index ) {
    if ( empty($_FILES['tracks']) || empty($_FILES['tracks']['tmp_name'][$index]['audio']) )
        return null;
    if ( !empty($_FILES['tracks']['error'][$index]['audio']) )
        return null;
    return array(
        'name'     => $_FILES['tracks']['name'][$index]['audio'],
        'tmp_name' => $_FILES['tracks']['tmp_name'][$index]['audio'],
        'error'    => $_FILES['tracks']['error'][$index]['audio'],
        'size'     => $_FILES['tracks']['size'][$index]['audio'],
        'type'     => $_FILES['tracks']['type'][$index]['audio'],
    );
}

function dist_handleFileUpload($loader, $file, $type, $user_id) {
        if ( empty($file['tmp_name']) || !empty($file['error']) || !is_uploaded_file($file['tmp_name']) )
            return null;

        $upload_dir = base_root . "/files/dist/{$type}/" . date('Y/m');

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $ext = strtolower( pathinfo($file['name'], PATHINFO_EXTENSION) );
        if ( $type === 'covers' && !in_array($ext, array('jpg','jpeg','png','webp'), true) )
            return null;
        if ( $type === 'audio' && !in_array($ext, array('mp3','wav','flac','aac','ogg','m4a'), true) )
            return null;

        $filename = $user_id . '_' . time() . '_' . uniqid() . '.' . $ext;
        $filepath = $upload_dir . '/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return "files/dist/{$type}/" . date('Y/m') . '/' . $filename;
        }

        return null;
    }

function dist_parseDuration( $duration ) {
    if ( is_numeric($duration) ) return (int)$duration;
    if ( !is_string($duration) ) return null;
    $parts = explode(':', trim($duration));
    if ( count($parts) === 2 ) return (int)$parts[0] * 60 + (int)$parts[1];
    if ( count($parts) === 3 ) return (int)$parts[0] * 3600 + (int)$parts[1] * 60 + (int)$parts[2];
    return null;
}

function dist_saveAlbumTracks($loader, $submission_id, $tracks, $user_id, $main_audio_path = null, $main_audio_track_index = null, $default_ai_pct = 0) {
    $db = $loader->db;
        $track_number = 1;

        foreach ($tracks as $ti => $track) {
            if (!is_array($track) || empty($track['title'])) continue;

            $title = $db->real_escape_string($track['title']);
            $artist_name = $db->real_escape_string($track['artist_name'] ?? '');
            $isrc = !empty($track['isrc']) ? $db->real_escape_string( strtoupper(trim($track['isrc'])) ) : null;
            // plan may disallow artist-supplied ISRCs
            if ( $isrc && function_exists("dist_user_plan") ){
                $tp = dist_user_plan( $db, $user_id );
                if ( $tp && !empty($tp['features']) && empty($tp['features']['own_isrc']) ) $isrc = null;
            }
            if ( !$isrc && function_exists("dist_gen_isrc") )
                $isrc = $db->real_escape_string( dist_gen_isrc( $db ) );
            $duration = !empty($track['duration']) ? dist_parseDuration($track['duration']) : null;
            $track_ai_pct = isset($track['ai_pct']) ? max(0, min(100, (int)$track['ai_pct'])) : (int)$default_ai_pct;

            $audio_path = null;
            if ( $ti === $main_audio_track_index && $main_audio_path ) {
                // First track's file was already uploaded into audio_file_path
                $audio_path = $main_audio_path;
            } else {
                $track_file = dist_getTrackFile( $ti );
                if ( !$track_file && !empty($_FILES['track_audio_' . $track_number]) && $_FILES['track_audio_' . $track_number]['error'] === 0 )
                    $track_file = $_FILES['track_audio_' . $track_number];
                if ( $track_file )
                    $audio_path = dist_handleFileUpload($loader, $track_file, 'audio', $user_id);
            }

            // auto-detect duration from the file when not provided
            if ( !$duration && $audio_path && function_exists("dist_probe_duration") )
                $duration = dist_probe_duration( $audio_path );

            // §3 review-queue fingerprint
            $fp = $audio_path && function_exists("dist_track_fingerprint") ? dist_track_fingerprint( $audio_path ) : null;

            $sql = "INSERT INTO `_dist_tracks` 
                (submission_id, track_number, title, artist_name, duration, isrc, audio_file_path, ai_pct, fingerprint)
                VALUES ({$submission_id}, {$track_number}, '{$title}', '{$artist_name}', 
                " . ($duration ? $duration : "NULL") . ",
                " . ($isrc ? "'{$isrc}'" : "NULL") . ",
                " . ($audio_path ? "'{$audio_path}'" : "NULL") . ",
                " . (int)$track_ai_pct . ",
                " . ($fp ? "'{$fp}'" : "NULL") . ")";
            
            $db->query($sql);
            $track_number++;
        }
    }

function dist_logActivity($loader, $user_id, $submission_id, $action, $old_status, $new_status, $details) {
    $db = $loader->db;
    $details = $db->real_escape_string($details);
    $sql = "INSERT INTO `_dist_logs` (user_id, submission_id, action, old_status, new_status, details, performed_by)
        VALUES ({$user_id}, {$submission_id}, '{$action}', " . ($old_status ? "'{$old_status}'" : "NULL") . ", 
        " . ($new_status ? "'{$new_status}'" : "NULL") . ", '{$details}', {$user_id})";
    $db->query($sql);
}

function dist_getInput() {
    $input = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!empty($_POST)) {
            $input = $_POST;
        } else {
            $json = file_get_contents('php://input');
            if ($json) {
                $input = json_decode($json, true);
            }
        }
    }
    return $input;
}
?>
