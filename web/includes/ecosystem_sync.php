<?php
/**
 * HiTune Ecosystem bridge — pushes approved releases into the HiTune Music
 * streaming catalog + IyolMe sound registry via the internal HMAC endpoint
 * on music.hitune.in (strategy doc: "1-Click Direct Ecosystem Publish").
 *
 * Shared secret lives in settings.ecosystem_bridge_secret and mirrors
 * _bof_setting.dist_bridge_secret on the music app.
 *
 * Signature contract (same as the IyolMe one):
 *   X-HT-Client:    hitune_dist_portal
 *   X-HT-Timestamp: unix seconds
 *   X-HT-Signature: hex hmac_sha256( "{ts}.{raw_json_body}", secret )
 */

if (!defined('ECO_BRIDGE_URL'))
    define('ECO_BRIDGE_URL', 'https://music.hitune.in/api/v1/dist/ecosystem');

require_once __DIR__ . '/referral.php';

/** Internal platform names surfaced in the release's platform list. */
define('ECO_PLATFORM_HITUNE', 'HiTune Music');
define('ECO_PLATFORM_IYOLME', 'IyolMe');

function eco_bridge_secret($conn) {
    static $cached = null;
    if ($cached !== null) return $cached;
    $r = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'ecosystem_bridge_secret' LIMIT 1");
    $cached = ($r && $r->num_rows) ? trim((string)$r->fetch_assoc()['setting_value']) : '';
    return $cached;
}

/** Signed POST to the music-side ecosystem endpoint. Returns decoded body. */
function eco_bridge_call($conn, $payload) {
    $secret = eco_bridge_secret($conn);
    if (!$secret) return ['ok' => false, 'error' => 'bridge_secret_missing'];

    $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $ts   = time();
    $sig  = hash_hmac('sha256', "{$ts}.{$body}", $secret);

    $ch = curl_init(ECO_BRIDGE_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Accept: application/json',
            'X-HT-Client: hitune_dist_portal',
            "X-HT-Timestamp: {$ts}",
            "X-HT-Signature: {$sig}",
        ],
    ]);
    $resp = curl_exec($ch);
    $err  = curl_error($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($err) return ['ok' => false, 'error' => 'connection_failed', 'detail' => $err];
    $data = json_decode((string)$resp, true);
    if (!is_array($data)) return ['ok' => false, 'error' => 'bad_response', 'http_code' => $code];
    $data['_http_code'] = $code;
    return $data;
}

/**
 * Make sure the internal platforms exist on a release (always selected —
 * ecosystem placement is automatic, not opt-in).
 */
function eco_ensure_platforms($conn, $release_id) {
    $release_id = (int)$release_id;
    $types = [ECO_PLATFORM_HITUNE => 'streaming', ECO_PLATFORM_IYOLME => 'social'];
    foreach ($types as $name => $type) {
        $stmt = $conn->prepare("SELECT id FROM release_platforms WHERE release_id = ? AND platform_name = ? LIMIT 1");
        $stmt->bind_param("is", $release_id, $name);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        if (!$exists) {
            $stmt = $conn->prepare("INSERT INTO release_platforms (release_id, platform_name, platform_type, is_selected, is_public) VALUES (?, ?, ?, 1, 1)");
            $stmt->bind_param("iss", $release_id, $name, $type);
            $stmt->execute();
            $stmt->close();
        }
    }
}

/** Update a platform row's delivery status (+ optional store url). */
function eco_mark_platform($conn, $release_id, $platform_name, $status, $store_url = null) {
    $release_id = (int)$release_id;
    if ($status === 'live' && $store_url) {
        $stmt = $conn->prepare("UPDATE release_platforms SET delivery_status = ?, store_url = ?, delivered_at = COALESCE(delivered_at, NOW()) WHERE release_id = ? AND platform_name = ?");
        $stmt->bind_param("ssis", $status, $store_url, $release_id, $platform_name);
    } else {
        $stmt = $conn->prepare("UPDATE release_platforms SET delivery_status = ? WHERE release_id = ? AND platform_name = ?");
        $stmt->bind_param("sis", $status, $release_id, $platform_name);
    }
    $stmt->execute();
    $stmt->close();
}

/**
 * Publish a release to the HiTune catalog + IyolMe sound registry.
 * Marks the internal platform rows live/failed accordingly.
 */
function ecosystem_publish_release($conn, $release_id) {
    $release_id = (int)$release_id;
    eco_ensure_platforms($conn, $release_id);

    $stmt = $conn->prepare("SELECT r.*, u.email AS user_email FROM releases r LEFT JOIN users u ON u.id = r.user_id WHERE r.id = ? LIMIT 1");
    $stmt->bind_param("i", $release_id);
    $stmt->execute();
    $rel = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$rel) return ['ok' => false, 'error' => 'release_not_found'];

    $tracks = [];
    $stmt = $conn->prepare("SELECT song_title, isrc_code, audio_file_path, audio_duration, ai_pct
        FROM release_tracks WHERE release_id = ? ORDER BY track_number ASC");
    $stmt->bind_param("i", $release_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($t = $res->fetch_assoc()) {
        if (empty($t['audio_file_path'])) continue;
        $dur = null;
        if (!empty($t['audio_duration'])) {
            if (is_numeric($t['audio_duration'])) $dur = (int)$t['audio_duration'];
            else {
                $p = explode(':', $t['audio_duration']);
                if (count($p) === 2) $dur = (int)$p[0] * 60 + (int)$p[1];
            }
        }
        $tracks[] = [
            'title'       => $t['song_title'],
            'artist_name' => $rel['primary_artist'],
            'isrc'        => $t['isrc_code'],
            'duration'    => $dur,
            'audio_path'  => $t['audio_file_path'],
            'ai_pct'      => (int)($t['ai_pct'] ?? 0),
        ];
    }
    $stmt->close();

    if (!$tracks) {
        eco_mark_platform($conn, $release_id, ECO_PLATFORM_HITUNE, 'failed');
        eco_mark_platform($conn, $release_id, ECO_PLATFORM_IYOLME, 'failed');
        return ['ok' => false, 'error' => 'no_tracks'];
    }

    $res = eco_bridge_call($conn, [
        'action'         => 'publish',
        'web_release_id' => $release_id,
        'release'        => [
            'title'        => $rel['title'],
            'artist_name'  => $rel['primary_artist'],
            'type'         => $rel['release_type'],
            'album_name'   => $rel['release_type'] !== 'single' ? $rel['title'] : null,
            'genre'        => $rel['primary_genre'],
            'language'     => $rel['language'],
            'release_date' => $rel['release_date'],
            'upc'          => $rel['upc_code'],
            'label_name'   => $rel['label_name'],
            'description'  => null,
            'ai_pct'       => (int)($rel['ai_pct'] ?? 0),
            'ai_tools'     => $rel['ai_tools'] ?? null,
            'ai_declared'  => !empty($rel['ai_declared']) ? 1 : 0,
            'cover_path'   => $rel['cover_art_path'] ?: $rel['cover_art'],
            'user_email'   => $rel['user_email'],
            'tracks'       => $tracks,
        ],
    ]);

    if (!empty($res['ok'])) {
        $url = !empty($res['track_urls'][0]) ? $res['track_urls'][0] : null;
        eco_mark_platform($conn, $release_id, ECO_PLATFORM_HITUNE, 'live', $url);
        eco_mark_platform($conn, $release_id, ECO_PLATFORM_IYOLME, 'live', $url);
        // remember the bridge submission for later takedown
        $conn->query("UPDATE releases SET admin_notes = CONCAT(COALESCE(admin_notes,''), '\n[ecosystem] catalog submission #" . (int)$res['submission_id'] . "') WHERE id = {$release_id} AND admin_notes NOT LIKE '%catalog submission #%'");

        // §8 referral bonus — credit the referrer on the referee's first live release
        if (function_exists('ref_credit_first_release') && !empty($rel['user_id'])) {
            @ref_credit_first_release($conn, (int) $rel['user_id']);
        }
    } else {
        eco_mark_platform($conn, $release_id, ECO_PLATFORM_HITUNE, 'failed');
        eco_mark_platform($conn, $release_id, ECO_PLATFORM_IYOLME, 'failed');
    }
    return $res;
}

/** Pull a release off HiTune + IyolMe (full rejection / takedown). */
function ecosystem_takedown_release($conn, $release_id) {
    $release_id = (int)$release_id;
    $res = eco_bridge_call($conn, [
        'action'         => 'takedown',
        'web_release_id' => $release_id,
    ]);
    eco_mark_platform($conn, $release_id, ECO_PLATFORM_HITUNE, 'taken_down');
    eco_mark_platform($conn, $release_id, ECO_PLATFORM_IYOLME, 'taken_down');
    return $res;
}

/**
 * Strategy-doc dashboard label (§7) from release status + platform delivery.
 * $platform_rows: release_platforms rows for the release.
 */
function eco_dashboard_status($status, $platform_rows = []) {
    if (in_array($status, ['draft'], true)) return 'Draft';
    if (in_array($status, ['submitted', 'in_progress'], true)) return 'Pending Admin Review';
    if ($status === 'ready') return 'Ready for Release';
    if ($status === 'rejected') return 'Rejected for All Platforms (Including HiTune Music)';
    if (in_array($status, ['takedown_requested'], true)) return 'Takedown Requested';
    if ($status === 'taken_down') return 'Taken Down';

    if ($status === 'live') {
        $hitune = null; $global_live = 0; $global_failed = 0; $global_total = 0;
        foreach ($platform_rows as $p) {
            $name = $p['platform_name'] ?? '';
            $ds = $p['delivery_status'] ?? 'pending';
            if ($name === ECO_PLATFORM_HITUNE || $name === ECO_PLATFORM_IYOLME) {
                if ($ds === 'live') $hitune = true;
            } elseif (!empty($p['is_selected'])) {
                $global_total++;
                if ($ds === 'live') $global_live++;
                if (in_array($ds, ['failed', 'taken_down'], true)) $global_failed++;
            }
        }
        if ($hitune) {
            if ($global_live > 0) return 'Globally Distributed';
            if ($global_total > 0 && $global_failed === $global_total)
                return 'Live on HiTune Music | Not Distributed Globally';
            return 'Live on HiTune';
        }
        return 'Live';
    }
    return ucfirst(str_replace('_', ' ', (string)$status));
}
