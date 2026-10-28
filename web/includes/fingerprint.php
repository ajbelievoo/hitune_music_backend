<?php
// Audio content fingerprinting for the §3 review queue.
// sha256 of file bytes; on any collision with another release's track we
// append a review flag to releases.admin_notes (manual copyright check).

if (!function_exists('web_audio_fingerprint')) {
    function web_audio_fingerprint($rel_path) {
        if (empty($rel_path)) return null;
        $full = '/www/wwwroot/web/' . ltrim($rel_path, '/');
        if (!is_file($full)) return null;
        $h = @hash_file('sha256', $full);
        if ($h) return $h;
        return md5(filesize($full) . ':' . basename($rel_path));
    }
}

// Call after storing a track's audio_file_path. Returns true when flagged.
if (!function_exists('web_flag_duplicate_audio')) {
    function web_flag_duplicate_audio($conn, $track_id) {
        $track_id = (int) $track_id;
        $r = $conn->query("SELECT t.release_id, t.fingerprint,
                GROUP_CONCAT(DISTINCT o.release_id) others
            FROM release_tracks t
            JOIN release_tracks o ON o.fingerprint = t.fingerprint AND o.release_id != t.release_id
            WHERE t.id = {$track_id} AND t.fingerprint IS NOT NULL AND t.fingerprint != ''
            GROUP BY t.fingerprint LIMIT 1");
        if (!$r || !$r->num_rows) return false;
        $row = $r->fetch_assoc();
        $release_id = (int) $row['release_id'];
        $others = $conn->real_escape_string($row['others']);
        $conn->query("UPDATE releases SET admin_notes = CONCAT(COALESCE(admin_notes,''),
            '\n[review] Duplicate audio fingerprint matches release #{$others} — manual copyright/reupload review required')
            WHERE id = {$release_id}");
        return true;
    }
}
