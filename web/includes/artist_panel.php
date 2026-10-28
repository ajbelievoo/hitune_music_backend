<?php
/**
 * HiTune Artist Panel — library
 *
 * One place for an artist to (1) get verified on HiTune Music, Spotify and
 * Apple Music and (2) read real analytics. HiTune Music data (plays, cities,
 * countries, tips, artist verification) lives on music.hitune.in and is
 * pulled through the signed bridge endpoint POST /api/v1/dist/artist.
 *
 * PHP 7.4 compatible (this vhost runs php-fpm 7.4).
 */

if (!defined('AP_BRIDGE_URL')) {
    define('AP_BRIDGE_URL', 'https://music.hitune.in/api/v1/dist/artist');
}

require_once __DIR__ . '/email_helper.php';

// ── Schema ───────────────────────────────────────────────────────────────────

function ap_ensure_tables($conn)
{
    static $done = false;
    if ($done) return;
    $conn->query("CREATE TABLE IF NOT EXISTS artist_verifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        platform ENUM('hitune','spotify','apple') NOT NULL,
        artist_name VARCHAR(255) NOT NULL,
        real_name VARCHAR(255) NULL,
        profile_url VARCHAR(500) NULL,
        external_id VARCHAR(100) NULL,
        doc_path VARCHAR(500) NULL,
        proof_note TEXT NULL,
        status ENUM('pending','verified','rejected','revoked') NOT NULL DEFAULT 'pending',
        method VARCHAR(20) NULL,
        music_request_id INT NULL,
        meta LONGTEXT NULL,
        admin_notes TEXT NULL,
        requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        reviewed_at TIMESTAMP NULL DEFAULT NULL,
        UNIQUE KEY uq_user_platform_artist (user_id, platform, artist_name),
        KEY idx_status (status, platform)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $conn->query("CREATE TABLE IF NOT EXISTS artist_profiles (
        user_id INT NOT NULL PRIMARY KEY,
        stage_name VARCHAR(255) NULL,
        slug VARCHAR(120) NULL,
        bio TEXT NULL,
        genre VARCHAR(100) NULL,
        country VARCHAR(100) NULL,
        city VARCHAR(100) NULL,
        website VARCHAR(255) NULL,
        instagram VARCHAR(255) NULL,
        youtube VARCHAR(255) NULL,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_slug (slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $done = true;
}

function ap_setting($conn, $key, $default = '')
{
    $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1");
    if (!$stmt) return $default;
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return ($row && $row['setting_value'] !== null && $row['setting_value'] !== '') ? $row['setting_value'] : $default;
}

// ── Music-side bridge ────────────────────────────────────────────────────────

/** Signed POST to /api/v1/dist/artist. Same HMAC contract as the ecosystem bridge. */
function ap_bridge($conn, array $payload)
{
    $secret = trim((string) ap_setting($conn, 'ecosystem_bridge_secret', ''));
    if ($secret === '') return ['ok' => false, 'error' => 'bridge_secret_missing'];

    $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $ts   = time();
    $sig  = hash_hmac('sha256', "{$ts}.{$body}", $secret);

    $ch = curl_init(AP_BRIDGE_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_CONNECTTIMEOUT => 6,
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
    $data = json_decode((string) $resp, true);
    if (!is_array($data)) return ['ok' => false, 'error' => 'bad_response', '_http_code' => $code];
    $data['_http_code'] = $code;
    return $data;
}

/**
 * Make sure the artist also has a HiTune Music account (same email + password
 * hash as here — the unified login bridge) so artist verification has a
 * music-side user to attach to.
 */
function ap_ensure_music_account($conn, $userId, array $user)
{
    require_once __DIR__ . '/sso_sync.php';
    if (!function_exists('sso_music_find') || !function_exists('sso_music_create')) return;
    if (sso_music_find($conn, $user['email'])) return;
    $stmt = $conn->prepare("SELECT password, email_verified FROM users WHERE id = ? LIMIT 1");
    if (!$stmt) return;
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) return;
    $hash = (string) $row['password'];
    if ($hash === '') $hash = password_hash(bin2hex(random_bytes(12)), PASSWORD_BCRYPT);
    sso_music_create($conn, (string) $user['name'], $user['email'], $hash, !empty($row['email_verified']));
}

// ── Data about the artist on THIS side ───────────────────────────────────────

/** Distinct artist names the user has released under (+ profile stage name). */
function ap_user_stage_names($conn, $userId, $fallbackName = '')
{
    $names = [];
    $stmt = $conn->prepare("SELECT stage_name FROM artist_profiles WHERE user_id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row && trim((string) $row['stage_name']) !== '') $names[] = trim($row['stage_name']);
    }
    $stmt = $conn->prepare("SELECT DISTINCT primary_artist FROM releases WHERE user_id = ? AND primary_artist IS NOT NULL AND primary_artist <> '' ORDER BY id DESC LIMIT 20");
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) $names[] = trim($r['primary_artist']);
        $stmt->close();
    }
    if (!$names && trim($fallbackName) !== '') $names[] = trim($fallbackName);
    $out = [];
    foreach ($names as $n) {
        $k = strtolower($n);
        if ($n !== '' && !isset($out[$k])) $out[$k] = $n;
    }
    return array_values($out);
}

function ap_user_isrcs($conn, $userId, $limit = 25)
{
    $isrcs = [];
    $stmt = $conn->prepare("SELECT DISTINCT rt.isrc_code FROM release_tracks rt JOIN releases r ON r.id = rt.release_id
        WHERE r.user_id = ? AND rt.isrc_code IS NOT NULL AND rt.isrc_code <> '' ORDER BY rt.id DESC LIMIT " . (int) $limit);
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) $isrcs[] = strtoupper(trim($r['isrc_code']));
        $stmt->close();
    }
    return $isrcs;
}

function ap_user_track_titles($conn, $userId, $limit = 100)
{
    $titles = [];
    $stmt = $conn->prepare("SELECT rt.song_title AS t FROM release_tracks rt JOIN releases r ON r.id = rt.release_id
        WHERE r.user_id = ? AND rt.song_title IS NOT NULL AND rt.song_title <> '' ORDER BY rt.id DESC LIMIT " . (int) $limit);
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) $titles[] = trim($r['t']);
        $stmt->close();
    }
    $stmt = $conn->prepare("SELECT title AS t FROM releases WHERE user_id = ? AND title <> '' ORDER BY id DESC LIMIT 50");
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) $titles[] = trim($r['t']);
        $stmt->close();
    }
    return array_values(array_unique($titles));
}

/** verification rows for one user: platform => [rows newest first] */
function ap_verifications_for_user($conn, $userId)
{
    ap_ensure_tables($conn);
    $out = ['hitune' => [], 'spotify' => [], 'apple' => []];
    $stmt = $conn->prepare("SELECT * FROM artist_verifications WHERE user_id = ? ORDER BY id DESC");
    if (!$stmt) return $out;
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $r['meta_arr'] = json_decode((string) $r['meta'], true) ?: [];
        $out[$r['platform']][] = $r;
    }
    $stmt->close();
    return $out;
}

/** Best status for a platform: verified > pending > rejected/revoked > none */
function ap_platform_state(array $rows)
{
    $rank = ['verified' => 3, 'pending' => 2, 'rejected' => 1, 'revoked' => 1];
    $best = null;
    foreach ($rows as $r) {
        if ($best === null || ($rank[$r['status']] ?? 0) > ($rank[$best['status']] ?? 0)) $best = $r;
    }
    return $best;
}

// ── HTTP + parsing helpers ───────────────────────────────────────────────────

function ap_http_get_json($url, array $headers = [], $timeout = 10)
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 3,
        CURLOPT_HTTPHEADER     => array_merge(['Accept: application/json', 'User-Agent: HiTuneDistribution/1.0'], $headers),
    ]);
    $resp = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($resp === false || $code >= 400 || $code === 0) return null;
    $j = json_decode((string) $resp, true);
    return is_array($j) ? $j : null;
}

function ap_parse_spotify_id($in)
{
    $in = trim((string) $in);
    if (preg_match('~open\.spotify\.com/(?:intl-[a-z]+/)?artist/([A-Za-z0-9]{22})~', $in, $m)) return $m[1];
    if (preg_match('~^spotify:artist:([A-Za-z0-9]{22})$~', $in, $m)) return $m[1];
    if (preg_match('~^[A-Za-z0-9]{22}$~', $in)) return $in;
    return null;
}

function ap_parse_apple_id($in)
{
    $in = trim((string) $in);
    if (preg_match('~(?:music|itunes)\.apple\.com/[a-z]{2}/artist/(?:[^/?#]+/)?(\d{4,})~i', $in, $m)) return $m[1];
    if (preg_match('~(?:music|itunes)\.apple\.com/artist/(?:[^/?#]+/)?(\d{4,})~i', $in, $m)) return $m[1];
    if (preg_match('~^\d{4,}$~', $in)) return $in;
    return null;
}

/** 0-100 similarity between two artist names (ignores case/punctuation). */
function ap_name_similarity($a, $b)
{
    $n = function ($s) { return preg_replace('/[^\p{L}0-9]+/u', '', mb_strtolower((string) $s)); };
    $a = $n($a); $b = $n($b);
    if ($a === '' || $b === '') return 0.0;
    if ($a === $b) return 100.0;
    similar_text($a, $b, $pct);
    return round($pct, 1);
}

function ap_norm_title($s)
{
    return preg_replace('/[^\p{L}0-9]+/u', '', mb_strtolower((string) $s));
}

// ── Spotify ──────────────────────────────────────────────────────────────────

/** Client-credentials token (needs settings.spotify_client_id / spotify_client_secret). */
function ap_spotify_token($conn)
{
    $id = trim((string) ap_setting($conn, 'spotify_client_id', ''));
    $secret = trim((string) ap_setting($conn, 'spotify_client_secret', ''));
    if ($id === '' || $secret === '') return null;

    $cache = sys_get_temp_dir() . '/ap_spotify_' . md5($id) . '.json';
    if (is_file($cache)) {
        $c = json_decode((string) @file_get_contents($cache), true);
        if (is_array($c) && !empty($c['token']) && ($c['exp'] ?? 0) > time() + 30) return $c['token'];
    }

    $ch = curl_init('https://accounts.spotify.com/api/token');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => 'grant_type=client_credentials',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => ['Authorization: Basic ' . base64_encode($id . ':' . $secret), 'Content-Type: application/x-www-form-urlencoded'],
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);
    $j = json_decode((string) $resp, true);
    if (!is_array($j) || empty($j['access_token'])) return null;
    @file_put_contents($cache, json_encode(['token' => $j['access_token'], 'exp' => time() + (int) ($j['expires_in'] ?? 3000)]));
    return $j['access_token'];
}

/**
 * Look up a Spotify artist. With API credentials: name, image, followers,
 * popularity, genres and an ISRC ownership check against the artist's catalogue.
 * Without credentials: public oEmbed (name + thumbnail only).
 */
function ap_spotify_lookup($conn, $spotifyId, array $isrcs = [])
{
    $url = 'https://open.spotify.com/artist/' . $spotifyId;
    $out = ['ok' => false, 'id' => $spotifyId, 'url' => $url, 'api' => 'none', 'isrc_matches' => [], 'isrc_checked' => 0];

    $token = ap_spotify_token($conn);
    if ($token) {
        $auth = ['Authorization: Bearer ' . $token];
        $a = ap_http_get_json('https://api.spotify.com/v1/artists/' . rawurlencode($spotifyId), $auth);
        if ($a && !empty($a['name'])) {
            $out['ok'] = true;
            $out['api'] = 'web_api';
            $out['name'] = $a['name'];
            $out['image'] = $a['images'][0]['url'] ?? null;
            $out['followers'] = (int) ($a['followers']['total'] ?? 0);
            $out['popularity'] = (int) ($a['popularity'] ?? 0);
            $out['genres'] = array_slice((array) ($a['genres'] ?? []), 0, 5);
            foreach (array_slice($isrcs, 0, 20) as $isrc) {
                $out['isrc_checked']++;
                $s = ap_http_get_json('https://api.spotify.com/v1/search?type=track&limit=5&q=' . rawurlencode('isrc:' . $isrc), $auth);
                foreach ((array) ($s['tracks']['items'] ?? []) as $t) {
                    foreach ((array) ($t['artists'] ?? []) as $ar) {
                        if (($ar['id'] ?? '') === $spotifyId) { $out['isrc_matches'][] = $isrc; break 2; }
                    }
                }
            }
            return $out;
        }
    }

    $o = ap_http_get_json('https://open.spotify.com/oembed?url=' . rawurlencode($url));
    if ($o && !empty($o['title'])) {
        $out['ok'] = true;
        $out['api'] = 'oembed';
        $out['name'] = $o['title'];
        $out['image'] = $o['thumbnail_url'] ?? null;
    }
    return $out;
}

// ── Apple Music ──────────────────────────────────────────────────────────────

/** Look up an Apple Music artist through the public iTunes lookup API. */
function ap_apple_lookup($appleId, array $titles = [])
{
    $out = ['ok' => false, 'id' => $appleId, 'title_matches' => [], 'songs_seen' => 0];
    $j = ap_http_get_json('https://itunes.apple.com/lookup?id=' . rawurlencode($appleId) . '&entity=album&limit=25&country=in');
    if (!$j || empty($j['results'])) {
        $j = ap_http_get_json('https://itunes.apple.com/lookup?id=' . rawurlencode($appleId) . '&entity=album&limit=25&country=us');
    }
    if (!$j || empty($j['results'])) return $out;

    $albums = 0; $albumNames = [];
    foreach ($j['results'] as $r) {
        if (($r['wrapperType'] ?? '') === 'artist') {
            $out['ok'] = true;
            $out['name'] = $r['artistName'] ?? '';
            $out['genre'] = $r['primaryGenreName'] ?? '';
            $out['url'] = preg_replace('/\?.*$/', '', (string) ($r['artistLinkUrl'] ?? ''));
        } elseif (($r['wrapperType'] ?? '') === 'collection') {
            $albums++;
            $albumNames[] = $r['collectionName'] ?? '';
            if (empty($out['image']) && !empty($r['artworkUrl100'])) $out['image'] = str_replace('100x100', '300x300', $r['artworkUrl100']);
        }
    }
    $out['albums'] = $albums;
    if (!$out['ok']) return $out;

    // catalogue titles (songs + albums) vs the artist's HiTune releases
    $songs = ap_http_get_json('https://itunes.apple.com/lookup?id=' . rawurlencode($appleId) . '&entity=song&limit=200&country=in');
    $catalog = $albumNames;
    foreach ((array) ($songs['results'] ?? []) as $r) {
        if (($r['wrapperType'] ?? '') === 'track') { $catalog[] = $r['trackName'] ?? ''; $out['songs_seen']++; }
    }
    $set = [];
    foreach ($catalog as $c) $set[ap_norm_title(preg_replace('/\s*[\(\[].*?[\)\]]\s*/u', ' ', (string) $c))] = $c;
    foreach ($titles as $t) {
        $k = ap_norm_title(preg_replace('/\s*[\(\[].*?[\)\]]\s*/u', ' ', $t));
        if ($k !== '' && isset($set[$k])) $out['title_matches'][] = $t;
    }
    return $out;
}

// ── Submitting + reviewing verifications ─────────────────────────────────────

/** Store an uploaded proof document under uploads/verification_docs/ (random name). */
function ap_store_proof_upload(array $file)
{
    if (empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) return null;
    if ($file['size'] > 5 * 1024 * 1024) return false;
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'application/pdf' => 'pdf'][$mime] ?? null;
    if (!$ext) return false;
    $dir = __DIR__ . '/../uploads/verification_docs';
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) return false;
    if (!is_file($dir . '/index.html')) @file_put_contents($dir . '/index.html', '');
    $name = bin2hex(random_bytes(16)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) return false;
    return 'uploads/verification_docs/' . $name;
}

/**
 * Create / refresh a verification request.
 * $platform: hitune | spotify | apple
 * $in: artist_name, real_name, profile_url, note, doc_path
 * Returns ['ok'=>bool, 'status'=>string, 'message'=>string]
 */
function ap_submit_verification($conn, $userId, array $user, $platform, array $in)
{
    ap_ensure_tables($conn);
    $artist = trim((string) ($in['artist_name'] ?? ''));
    if ($artist === '' || mb_strlen($artist) > 120) return ['ok' => false, 'message' => 'Please choose or enter your artist / stage name.'];

    // one live row per user+platform+artist; rejected/revoked ones can be re-submitted
    $stmt = $conn->prepare("SELECT id, status FROM artist_verifications WHERE user_id = ? AND platform = ? AND artist_name = ? LIMIT 1");
    $stmt->bind_param("iss", $userId, $platform, $artist);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($existing && in_array($existing['status'], ['verified', 'pending'], true)) {
        return ['ok' => false, 'message' => $existing['status'] === 'verified'
            ? 'This artist is already verified on ' . ucfirst($platform) . '.'
            : 'A verification request for this artist is already pending review.'];
    }

    $meta = []; $status = 'pending'; $method = 'manual'; $externalId = null; $profileUrl = trim((string) ($in['profile_url'] ?? ''));
    $real = trim((string) ($in['real_name'] ?? ''));
    $note = trim((string) ($in['note'] ?? ''));
    $doc  = $in['doc_path'] ?? null;
    $musicRequestId = null;

    if ($platform === 'hitune') {
        if ($real === '') return ['ok' => false, 'message' => 'Please enter your legal name.'];
        ap_ensure_music_account($conn, $userId, $user);
        $res = ap_bridge($conn, [
            'action' => 'verify_request', 'email' => $user['email'],
            'stage_name' => $artist, 'real_name' => $real,
            'note' => trim($note . ($profileUrl !== '' ? " | Proof link: {$profileUrl}" : '')),
        ]);
        if (empty($res['ok'])) {
            $msg = $res['hint'] ?? ($res['error'] ?? 'Could not reach HiTune Music.');
            return ['ok' => false, 'message' => 'HiTune Music says: ' . $msg];
        }
        if (($res['status'] ?? '') === 'already_verified') {
            $status = 'verified'; $method = 'music';
            $meta['artist'] = $res['artist'] ?? null;
        } else {
            $musicRequestId = (int) ($res['request_id'] ?? 0) ?: null;
        }
    } elseif ($platform === 'spotify') {
        $externalId = ap_parse_spotify_id($profileUrl);
        if (!$externalId) return ['ok' => false, 'message' => 'Paste your Spotify artist link, e.g. https://open.spotify.com/artist/…'];
        $lk = ap_spotify_lookup($conn, $externalId, ap_user_isrcs($conn, $userId));
        if (!$lk['ok']) return ['ok' => false, 'message' => 'We could not find that Spotify artist. Check the link and try again.'];
        $meta = $lk;
        $meta['name_similarity'] = ap_name_similarity($artist, $lk['name'] ?? '');
        $profileUrl = $lk['url'];
        if ($lk['api'] === 'web_api' && !empty($lk['isrc_matches']) && $meta['name_similarity'] >= 60) {
            $status = 'verified'; $method = 'auto_isrc';
        }
    } elseif ($platform === 'apple') {
        $externalId = ap_parse_apple_id($profileUrl);
        if (!$externalId) return ['ok' => false, 'message' => 'Paste your Apple Music artist link, e.g. https://music.apple.com/in/artist/…/123456'];
        $lk = ap_apple_lookup($externalId, ap_user_track_titles($conn, $userId));
        if (!$lk['ok']) return ['ok' => false, 'message' => 'We could not find that Apple Music artist. Check the link and try again.'];
        $meta = $lk;
        $meta['name_similarity'] = ap_name_similarity($artist, $lk['name'] ?? '');
        $profileUrl = $lk['url'] ?: $profileUrl;
    } else {
        return ['ok' => false, 'message' => 'Unknown platform.'];
    }

    $metaJson = json_encode($meta, JSON_UNESCAPED_UNICODE);
    if ($existing) {
        $stmt = $conn->prepare("UPDATE artist_verifications SET real_name=?, profile_url=?, external_id=?, doc_path=COALESCE(?, doc_path), proof_note=?,
            status=?, method=?, music_request_id=?, meta=?, admin_notes=NULL, requested_at=NOW(), reviewed_at=" . ($status === 'verified' ? 'NOW()' : 'NULL') . " WHERE id=?");
        $stmt->bind_param("sssssssisi", $real, $profileUrl, $externalId, $doc, $note, $status, $method, $musicRequestId, $metaJson, $existing['id']);
    } else {
        $stmt = $conn->prepare("INSERT INTO artist_verifications (user_id, platform, artist_name, real_name, profile_url, external_id, doc_path, proof_note, status, method, music_request_id, meta, reviewed_at)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?," . ($status === 'verified' ? 'NOW()' : 'NULL') . ")");
        $stmt->bind_param("isssssssssis", $userId, $platform, $artist, $real, $profileUrl, $externalId, $doc, $note, $status, $method, $musicRequestId, $metaJson);
    }
    $ok = $stmt->execute();
    $stmt->close();
    if (!$ok) return ['ok' => false, 'message' => 'Could not save your request. Please try again.'];

    if ($status === 'verified') {
        return ['ok' => true, 'status' => 'verified', 'message' => ucfirst($platform === 'apple' ? 'Apple Music' : $platform) . ' verified — your ownership was confirmed automatically.'];
    }
    return ['ok' => true, 'status' => 'pending', 'message' => 'Request submitted. Our team reviews verification requests within 1–2 business days.'];
}

/**
 * Admin decision on a verification row.
 * $decision: approve | reject | revoke
 */
function ap_review_verification($conn, $id, $decision, $adminNotes = '', $force = false)
{
    ap_ensure_tables($conn);
    $stmt = $conn->prepare("SELECT v.*, u.email, u.name AS user_name FROM artist_verifications v JOIN users u ON u.id = v.user_id WHERE v.id = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $v = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$v) return ['ok' => false, 'message' => 'Request not found.'];

    $newStatus = $decision === 'approve' ? 'verified' : ($decision === 'revoke' ? 'revoked' : 'rejected');
    $method = $decision === 'approve' ? 'admin' : $v['method'];

    // HiTune Music: drive the native music-side approval so the artist page,
    // roles and notifications all happen exactly like the built-in flow.
    if ($v['platform'] === 'hitune' && !empty($v['music_request_id']) && in_array($decision, ['approve', 'reject', 'revoke'], true)) {
        $res = ap_bridge($conn, [
            'action' => ['approve' => 'verify_approve', 'reject' => 'verify_reject', 'revoke' => 'verify_revoke'][$decision],
            'request_id' => (int) $v['music_request_id'], 'force' => $force ? 1 : 0,
        ]);
        if (empty($res['ok'])) {
            $why = $res['error'] ?? 'bridge_error';
            if ($why === 'artist_managed_by_other') {
                return ['ok' => false, 'message' => 'That artist name is already managed by another HiTune Music account. Use "Approve & transfer" only after checking proof.', 'conflict' => true];
            }
            return ['ok' => false, 'message' => 'HiTune Music rejected the action: ' . $why];
        }
    }

    $stmt = $conn->prepare("UPDATE artist_verifications SET status = ?, method = ?, admin_notes = ?, reviewed_at = NOW() WHERE id = ?");
    $stmt->bind_param("sssi", $newStatus, $method, $adminNotes, $id);
    $stmt->execute();
    $stmt->close();

    $pName = ['hitune' => 'HiTune Music', 'spotify' => 'Spotify', 'apple' => 'Apple Music'][$v['platform']];
    $subject = $newStatus === 'verified' ? "You're verified on {$pName}" : "Your {$pName} verification update";
    $color   = $newStatus === 'verified' ? '#00c853' : '#ff5252';
    $label   = $newStatus === 'verified' ? 'Verified' : ($newStatus === 'revoked' ? 'Verification removed' : 'Needs changes');
    $siteUrl = 'https://distribution.hitune.in';
    $notes   = trim($adminNotes) !== '' ? "<div style='background:#fff8e1;border-left:4px solid #ffc107;padding:14px 18px;border-radius:8px;margin:16px 0;color:#5d4a00;'><b>Note from our team:</b><br>" . nl2br(htmlspecialchars($adminNotes)) . "</div>" : '';
    $body = "<h2 style='margin:0 0 16px;color:#1a1a2e;'>Hi " . htmlspecialchars($v['user_name'] ?: 'artist') . ",</h2>"
          . "<p style='color:#444;line-height:1.6;'>Your <b>" . htmlspecialchars($pName) . "</b> verification for <b>" . htmlspecialchars($v['artist_name']) . "</b> was reviewed.</p>"
          . "<div style='text-align:center;margin:22px 0;'><span style='display:inline-block;background:{$color};color:#fff;font-weight:700;padding:10px 26px;border-radius:30px;'>{$label}</span></div>"
          . $notes
          . "<p style='text-align:center;margin:24px 0 0;'><a href='{$siteUrl}/index.php?q=artist-panel#verification' style='display:inline-block;background:linear-gradient(135deg,#00b7ff,#8b5cf6);color:#fff;text-decoration:none;font-weight:700;padding:14px 32px;border-radius:12px;'>Open Artist Panel</a></p>";
    try { sendEmail($v['email'], $v['user_name'], $subject, buildEmailTemplate($subject, $label, $body)); } catch (Throwable $e) {}

    return ['ok' => true, 'message' => 'Marked as ' . $newStatus . '.', 'status' => $newStatus];
}

/**
 * If HiTune Music already shows the user as a verified manager of an artist
 * (e.g. approved natively in the music admin), reflect that in our rows.
 */
function ap_sync_hitune_status($conn, $userId, array $overview)
{
    if (empty($overview['ok'])) return;
    ap_ensure_tables($conn);
    foreach ((array) ($overview['artists'] ?? []) as $a) {
        if (empty($a['verified'])) continue;
        $meta = json_encode(['artist' => ['id' => $a['id'] ?? null, 'url' => $a['url'] ?? null, 'followers' => $a['followers'] ?? 0]]);
        $name = (string) $a['name'];
        $stmt = $conn->prepare("INSERT INTO artist_verifications (user_id, platform, artist_name, status, method, meta, reviewed_at)
            VALUES (?, 'hitune', ?, 'verified', 'music', ?, NOW())
            ON DUPLICATE KEY UPDATE status = IF(status IN ('pending','rejected'), 'verified', status),
                method = IF(status = 'verified', method, 'music'),
                reviewed_at = IF(reviewed_at IS NULL, NOW(), reviewed_at)");
        if ($stmt) {
            $stmt->bind_param("iss", $userId, $name, $meta);
            $stmt->execute();
            $stmt->close();
        }
    }
    // approved/rejected on the music side but still pending here
    foreach ((array) ($overview['pending_requests'] ?? []) as $q) {
        if (empty($q['id']) || $q['status'] === 'pending') continue;
        $st = $q['status'] === 'approved' ? 'verified' : 'rejected';
        $rid = (int) $q['id'];
        $stmt = $conn->prepare("UPDATE artist_verifications SET status = ?, reviewed_at = NOW() WHERE user_id = ? AND platform = 'hitune' AND music_request_id = ? AND status = 'pending'");
        if ($stmt) {
            $stmt->bind_param("sii", $st, $userId, $rid);
            $stmt->execute();
            $stmt->close();
        }
    }
}

/** Overview from the music side with a short per-session cache. */
function ap_music_overview($conn, array $user, array $stageNames, $days = 30, $force = false)
{
    $key = 'ap_ov_' . (int) $days;
    if (!$force && !empty($_SESSION[$key]) && ($_SESSION[$key]['t'] ?? 0) > time() - 45) return $_SESSION[$key]['d'];
    $res = ap_bridge($conn, ['action' => 'overview', 'email' => $user['email'], 'stage_names' => $stageNames, 'days' => $days]);
    if (!empty($res['ok'])) $_SESSION[$key] = ['t' => time(), 'd' => $res];
    return $res;
}

function ap_slugify($s)
{
    $s = strtolower(trim((string) $s));
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    $s = trim($s, '-');
    return $s !== '' ? substr($s, 0, 60) : 'artist';
}

function ap_fmt_num($n)
{
    $n = (float) $n;
    if ($n >= 1000000) return round($n / 1000000, 1) . 'M';
    if ($n >= 1000) return round($n / 1000, 1) . 'K';
    return (string) (int) $n;
}
