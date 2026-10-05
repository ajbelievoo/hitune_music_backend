<?php
/**
 * music.hitune.in database backup script
 *
 * Creates a gzipped SQL dump in /www/backup/music/.
 * Run from CLI as the web user or via cron.
 */

require_once dirname(__DIR__) . '/api/app/config.php';

$backupDir = '/www/wwwroot/backups/music';
if (!is_dir($backupDir)) {
    @mkdir($backupDir, 0750, true);
}

$date = date('Y-m-d_H-i-s');
$file = $backupDir . '/music_' . $date . '.sql.gz';

$cmd = sprintf(
    'mysqldump --host=%s --user=%s --password=%s --single-transaction --routines --triggers %s | gzip > %s',
    escapeshellarg(db_host),
    escapeshellarg(db_user),
    escapeshellarg(db_pass),
    escapeshellarg(db_name),
    escapeshellarg($file)
);

exec($cmd, $output, $code);

if ($code !== 0) {
    fwrite(STDERR, "Backup failed with exit code {$code}\n");
    exit(1);
}

// Retention: keep last 14 days
$cutoff = strtotime('-14 days');
foreach (glob($backupDir . '/music_*.sql.gz') as $f) {
    if (filemtime($f) < $cutoff) {
        @unlink($f);
    }
}

echo "Backup created: {$file}\n";
