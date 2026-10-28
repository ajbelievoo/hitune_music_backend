<?php
/**
 * music.hitune.in — per-track loudness measurement
 *
 * Measures integrated loudness (LUFS) and true peak (dBTP) for every track
 * that has a local audio source, using `ffmpeg -af ebur128=peak=true`.
 * Results are stored in _c_m_tracks.lufs / _c_m_tracks.peak_db and served
 * to the app via muse_request_source ("loudness" field) for Spotify-style
 * volume normalization (target -14 LUFS).
 *
 * Run from CLI:
 *   /usr/bin/php82 scripts/measure_loudness.php            # 50 tracks/run
 *   /usr/bin/php82 scripts/measure_loudness.php --limit=200 --remeasure
 */

require_once dirname(__DIR__) . '/api/app/config.php';

$options = getopt('', ['limit::', 'remeasure', 'help']);
if (isset($options['help'])) {
    echo "Usage: php82 scripts/measure_loudness.php [--limit=N] [--remeasure]\n";
    exit(0);
}

$limit = isset($options['limit']) ? max(1, (int)$options['limit']) : 50;
$remeasure = isset($options['remeasure']);

$ffmpeg = trim((string)@shell_exec('which ffmpeg'));
if (!$ffmpeg || !is_executable($ffmpeg)) {
    fwrite(STDERR, "ffmpeg not found in PATH\n");
    exit(1);
}

$dsn = 'mysql:host=' . db_host . ';dbname=' . db_name . ';charset=utf8mb4';
$pdo = new PDO($dsn, db_user, db_pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$where = $remeasure ? '1' : 'lufs IS NULL';
$tracks = $pdo->query(
    "SELECT ID, title FROM `_c_m_tracks` WHERE {$where} ORDER BY ID ASC LIMIT {$limit}"
)->fetchAll(PDO::FETCH_ASSOC);

if (!$tracks) {
    echo "Nothing to measure.\n";
    exit(0);
}

$root = dirname(__DIR__);
$done = 0;
$skipped = 0;
$failed = 0;

$upd = $pdo->prepare("UPDATE `_c_m_tracks` SET `lufs` = :lufs, `peak_db` = :peak WHERE `ID` = :id");

foreach ($tracks as $track) {

    // Find the first local audio source for this track
    $sources = $pdo->prepare(
        "SELECT data FROM `_c_m_tracks_sources` WHERE `target_id` = :id AND `type` = 'audio' ORDER BY ID ASC"
    );
    $sources->execute(['id' => $track['ID']]);

    $file = null;
    foreach ($sources->fetchAll(PDO::FETCH_ASSOC) as $source) {
        $data = json_decode($source['data'], true);
        if (!empty($data['file_type']) && $data['file_type'] === 'local' && !empty($data['local_file'])) {
            $f = $pdo->prepare("SELECT path FROM `_bof_files` WHERE `ID` = :fid");
            $f->execute(['fid' => $data['local_file']]);
            $row = $f->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $abs = $root . '/' . ltrim($row['path'], '/');
                if (is_file($abs)) { $file = $abs; break; }
            }
        }
    }

    if (!$file) {
        $skipped++;
        continue;
    }

    $cmd = sprintf(
        '%s -hide_banner -nostats -i %s -af ebur128=peak=true -f null - 2>&1',
        escapeshellarg($ffmpeg),
        escapeshellarg($file)
    );
    $output = shell_exec($cmd);

    $lufs = null;
    $peak = null;

    // Summary block: "I:         -9.4 LUFS" and "Peak:       -0.3 dBFS"
    if (preg_match('/^\s*I:\s*(-?[\d\.]+)\s*LUFS/m', $output, $m)) {
        $lufs = (float)$m[1];
    } elseif (preg_match('/Integrated loudness:\s*I:\s*(-?[\d\.]+)/s', $output, $m)) {
        $lufs = (float)$m[1];
    }
    if (preg_match_all('/^\s*Peak:\s*(-?[\d\.]+)\s*dBFS/m', $output, $m)) {
        // ebur128 reports both sample peak and true peak; the last block is true peak
        $peak = (float)end($m[1]);
    }

    if ($lufs === null) {
        $failed++;
        fwrite(STDERR, "FAILED  #{$track['ID']} {$track['title']}\n");
        continue;
    }

    $upd->execute(['lufs' => $lufs, 'peak' => $peak, 'id' => $track['ID']]);
    $done++;
    echo "OK      #{$track['ID']} {$track['title']}  I={$lufs} LUFS  Peak=" . ($peak === null ? 'n/a' : $peak . ' dBTP') . "\n";

}

echo "\nDone: {$done} measured, {$skipped} no-local-source, {$failed} failed.\n";
