<?php
/**
 * HiTune Music Distribution - Admin Royalties Management
 * Manual royalty entry for published tracks
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/config.php';

session_start();
requireAdmin();

// Handle royalty save
if (isset($_POST['save_royalty'])) {
    $track_id = intval($_POST['track_id']);
    $release_id = intval($_POST['release_id']);
    $plays = intval($_POST['plays'] ?? 0);
    $downloads = intval($_POST['downloads'] ?? 0);
    $revenue = floatval($_POST['revenue'] ?? 0);
    $royalty_amount = floatval($_POST['royalty_amount'] ?? 0);
    $platform = $_POST['platform'] ?? 'All Platforms';
    $reporting_period = $_POST['reporting_period'] ?? date('F Y');
    $notes = $_POST['notes'] ?? '';
    
    $stmt = $conn->prepare("INSERT INTO track_royalties 
        (track_id, release_id, plays, downloads, revenue, royalty_amount, platform, reporting_period, admin_notes, created_by) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'admin')");
    $stmt->bind_param("iiiiddsss", $track_id, $release_id, $plays, $downloads, $revenue, $royalty_amount, $platform, $reporting_period, $notes);
    $stmt->execute();
    
    header('Location: royalties.php?saved=1');
    exit;
}

// Handle CSV royalty import
$importResult = null;
if (isset($_POST['import_csv']) && isset($_FILES['royalty_csv']) && $_FILES['royalty_csv']['error'] === UPLOAD_ERR_OK) {
    $tmpPath = $_FILES['royalty_csv']['tmp_name'];
    $fileHash = hash_file('sha256', $tmpPath);

    // Duplicate file protection
    $stmt = $conn->prepare("SELECT id FROM royalty_imports WHERE file_hash = ?");
    $stmt->bind_param("s", $fileHash);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $importResult = ['error' => 'This exact file was already imported. Duplicate import blocked.'];
    } else {
        $handle = fopen($tmpPath, 'r');
        if ($handle) {
            // Read header row, normalize
            $headers = fgetcsv($handle);
            $headers = array_map(function ($h) { return strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', $h))); }, $headers ?: []);

            $col = function ($row, ...$names) use ($headers) {
                foreach ($names as $n) {
                    $idx = array_search($n, $headers, true);
                    if ($idx !== false && isset($row[$idx])) return trim($row[$idx]);
                }
                return '';
            };

            $total = 0; $imported = 0; $skipped = 0; $failed = 0;
            $errors = [];

            // Create import batch row first
            $stmt = $conn->prepare("INSERT INTO royalty_imports (filename, file_hash, imported_by) VALUES (?, ?, ?)");
            $adminName = $_SESSION['admin_username'] ?? 'admin';
            $fname = $_FILES['royalty_csv']['name'];
            $stmt->bind_param("sss", $fname, $fileHash, $adminName);
            $stmt->execute();
            $importId = $conn->insert_id;

            while (($row = fgetcsv($handle)) !== false) {
                if (count(array_filter($row)) === 0) continue; // skip blank lines
                $total++;

                $isrc     = $col($row, 'isrc', 'isrc_code');
                $title    = $col($row, 'track', 'song_title', 'track_title', 'title');
                $platform = $col($row, 'platform', 'store', 'dsp') ?: 'All Platforms';
                $period   = $col($row, 'period', 'reporting_period', 'month') ?: date('F Y');
                $plays    = (int) preg_replace('/[^0-9]/', '', $col($row, 'plays', 'streams', 'quantity'));
                $downloads= (int) preg_replace('/[^0-9]/', '', $col($row, 'downloads'));
                $revenue  = (float) preg_replace('/[^0-9.\-]/', '', $col($row, 'revenue', 'earnings', 'gross'));
                $royalty  = (float) preg_replace('/[^0-9.\-]/', '', $col($row, 'royalty', 'royalty_amount', 'amount', 'net'));

                // Match track by ISRC or title
                $track = null;
                if ($isrc !== '') {
                    $t = $conn->prepare("SELECT id, release_id FROM release_tracks WHERE isrc_code = ? LIMIT 1");
                    $t->bind_param("s", $isrc);
                    $t->execute();
                    $track = $t->get_result()->fetch_assoc();
                }
                if (!$track && $title !== '') {
                    $t = $conn->prepare("SELECT id, release_id FROM release_tracks WHERE song_title = ? LIMIT 1");
                    $t->bind_param("s", $title);
                    $t->execute();
                    $track = $t->get_result()->fetch_assoc();
                }

                if (!$track) {
                    $failed++;
                    $errors[] = "Row $total: no matching track (isrc='$isrc' title='$title')";
                    continue;
                }

                // Skip exact duplicate rows
                $d = $conn->prepare("SELECT id FROM track_royalties WHERE track_id = ? AND platform = ? AND reporting_period = ? AND royalty_amount = ? LIMIT 1");
                $d->bind_param("issd", $track['id'], $platform, $period, $royalty);
                $d->execute();
                if ($d->get_result()->num_rows > 0) { $skipped++; continue; }

                $ins = $conn->prepare("INSERT INTO track_royalties (track_id, release_id, plays, downloads, revenue, royalty_amount, platform, reporting_period, admin_notes, created_by, import_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'csv_import', ?)");
                $note = "CSV import #$importId";
                $ins->bind_param("iiiiddsssi", $track['id'], $track['release_id'], $plays, $downloads, $revenue, $royalty, $platform, $period, $note, $importId);
                if ($ins->execute()) $imported++; else { $failed++; $errors[] = "Row $total: DB insert failed"; }
            }
            fclose($handle);

            $errText = implode("\n", $errors);
            $stmt = $conn->prepare("UPDATE royalty_imports SET rows_total = ?, rows_imported = ?, rows_skipped = ?, rows_failed = ?, error_log = ? WHERE id = ?");
            $stmt->bind_param("iiiisi", $total, $imported, $skipped, $failed, $errText, $importId);
            $stmt->execute();

            $importResult = ['success' => "Import complete: $imported imported, $skipped duplicates skipped, $failed failed (of $total rows).", 'errors' => $errors];
        } else {
            $importResult = ['error' => 'Could not read uploaded file.'];
        }
    }
}

// Get published releases with tracks
$releases = $conn->query("SELECT r.*, u.name as user_name 
    FROM releases r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.status IN ('ready', 'live')
    ORDER BY r.ready_at DESC");

// Get all royalties summary
$royalties_summary = $conn->query("SELECT 
    SUM(plays) as total_plays,
    SUM(downloads) as total_downloads,
    SUM(revenue) as total_revenue,
    SUM(royalty_amount) as total_royalties
    FROM track_royalties");
$summary = $royalties_summary->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Royalties Management - Admin Panel</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@6.5.95/css/materialdesignicons.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: #0a0a0a;
            color: #fff;
            min-height: 100vh;
        }
        .admin-container { display: flex; min-height: 100vh; }
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            position: fixed;
            top: 0; left: 0; bottom: 0;
            overflow-y: auto;
        }
        .sidebar-header {
            padding: 30px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }
        .sidebar-header .logo {
            width: 60px; height: 60px;
            background: linear-gradient(135deg, #00b7ff, #8b5cf6);
            border-radius: 15px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 15px;
            font-size: 28px;
        }
        .sidebar-header h2 { font-size: 18px; font-weight: 700; }
        .nav-menu { padding: 20px 0; }
        .nav-item {
            display: flex; align-items: center; gap: 15px;
            padding: 15px 30px;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        .nav-item:hover, .nav-item.active {
            background: rgba(0, 183, 255, 0.1);
            color: #00b7ff;
            border-left-color: #00b7ff;
        }
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 30px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .header h1 { font-size: 28px; font-weight: 700; }
        .btn {
            padding: 12px 25px;
            background: linear-gradient(135deg, #00b7ff, #8b5cf6);
            border: none;
            border-radius: 10px;
            color: #fff;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        /* Stats */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
        }
        .stat-card h3 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .stat-card p {
            font-size: 14px;
            color: rgba(255,255,255,0.6);
        }
        
        /* Releases List */
        .release-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
        }
        .release-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .release-info h3 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .release-info p {
            font-size: 13px;
            color: rgba(255,255,255,0.5);
        }
        .btn-add {
            padding: 10px 20px;
            background: linear-gradient(135deg, #00d4aa, #00c853);
            border: none;
            border-radius: 8px;
            color: #000;
            font-weight: 600;
            cursor: pointer;
            font-size: 13px;
        }
        
        /* Tracks Table */
        .tracks-table {
            width: 100%;
            border-collapse: collapse;
        }
        .tracks-table th {
            text-align: left;
            padding: 12px;
            font-size: 12px;
            color: rgba(255,255,255,0.5);
            text-transform: uppercase;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .tracks-table td {
            padding: 12px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            font-size: 14px;
        }
        .royalty-stats {
            display: flex;
            gap: 15px;
        }
        .royalty-stat {
            text-align: center;
            padding: 10px 15px;
            background: rgba(0,212,170,0.1);
            border-radius: 8px;
        }
        .royalty-stat strong {
            display: block;
            font-size: 16px;
            color: #00d4aa;
        }
        .royalty-stat span {
            font-size: 11px;
            color: rgba(255,255,255,0.5);
        }
        
        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.8);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal-overlay.active {
            display: flex;
        }
        .modal {
            background: #1a1a2e;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 30px;
            width: 90%;
            max-width: 500px;
        }
        .modal h2 {
            font-size: 20px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-size: 13px;
            margin-bottom: 8px;
            color: rgba(255,255,255,0.7);
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 12px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 8px;
            color: #fff;
            font-size: 14px;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 25px;
        }
        .btn-secondary {
            background: rgba(255,255,255,0.1);
            color: #fff;
        }
        .btn-primary {
            background: linear-gradient(135deg, #00d4aa, #00c853);
            color: #000;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
        }
        .alert-success {
            background: rgba(0,200,83,0.2);
            border: 1px solid #00c853;
            color: #00c853;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo"><span class="mdi mdi-shield-account"></span></div>
                <h2>Admin Panel</h2>
            </div>
            <nav class="nav-menu">
                <a href="index.php" class="nav-item"><span class="mdi mdi-view-dashboard"></span>Dashboard</a>
                <a href="users.php" class="nav-item"><span class="mdi mdi-account-group"></span>Users</a>
                <a href="submissions.php" class="nav-item"><span class="mdi mdi-music"></span>Releases</a>
                <a href="artist_verifications.php" class="nav-item"><span class="mdi mdi-check-decagram"></span>Artist Verifications</a>
                <a href="revenue.php" class="nav-item"><span class="mdi mdi-currency-usd"></span>Revenue</a>
                <a href="payouts.php" class="nav-item"><span class="mdi mdi-cash-multiple"></span>Payouts</a>
                <a href="payments.php" class="nav-item"><span class="mdi mdi-credit-card"></span>Payments</a>
                <a href="royalties.php" class="nav-item active"><span class="mdi mdi-chart-line"></span>Royalties</a>
                <a href="settings.php" class="nav-item"><span class="mdi mdi-cog"></span>Settings</a>
                <a href="logout.php" class="nav-item"><span class="mdi mdi-logout"></span>Logout</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="header">
                <h1><span class="mdi mdi-chart-line"></span> Royalties Management</h1>
                <a href="index.php" class="btn">Back to Dashboard</a>
            </div>
            
            <?php if (isset($_GET['saved'])): ?>
            <div class="alert alert-success">
                <span class="mdi mdi-check-circle"></span> Royalty data saved successfully!
            </div>
            <?php endif; ?>

            <?php if ($importResult): ?>
                <?php if (isset($importResult['success'])): ?>
                <div class="alert alert-success">
                    <span class="mdi mdi-check-circle"></span> <?php echo htmlspecialchars($importResult['success']); ?>
                    <?php if (!empty($importResult['errors'])): ?>
                    <details style="margin-top:10px;"><summary style="cursor:pointer;">Show row errors</summary>
                        <pre style="white-space:pre-wrap;font-size:12px;margin-top:8px;color:#ffb74d;"><?php echo htmlspecialchars(implode("\n", array_slice($importResult['errors'], 0, 50))); ?></pre>
                    </details>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="alert" style="background:rgba(255,82,82,0.15);border:1px solid #ff5252;color:#ff5252;">
                    <span class="mdi mdi-alert-circle"></span> <?php echo htmlspecialchars($importResult['error']); ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- CSV Import -->
            <div style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.1);border-radius:15px;padding:25px;margin-bottom:30px;">
                <h2 style="font-size:18px;margin-bottom:8px;"><span class="mdi mdi-file-upload"></span> Import Royalty Statement (CSV)</h2>
                <p style="font-size:13px;color:rgba(255,255,255,0.5);margin-bottom:18px;">
                    Upload a DSP royalty CSV. Tracks are matched by <b>isrc</b> column (or <b>track</b>/<b>song_title</b>).
                    Recognized columns: <code>isrc, track, platform, plays/streams, downloads, revenue, royalty/amount, period</code>.
                    The same file cannot be imported twice; identical rows are skipped.
                </p>
                <form method="POST" enctype="multipart/form-data" style="display:flex;gap:15px;align-items:center;flex-wrap:wrap;">
                    <input type="file" name="royalty_csv" accept=".csv,text/csv" required
                           style="padding:12px;background:rgba(255,255,255,0.08);border:1px dashed rgba(255,255,255,0.25);border-radius:10px;color:#fff;">
                    <button type="submit" name="import_csv" class="btn" style="background:linear-gradient(135deg,#4facfe,#00d4aa);color:#000;">
                        <span class="mdi mdi-upload"></span> Import CSV
                    </button>
                </form>
                <?php
                $imports = $conn->query("SELECT * FROM royalty_imports ORDER BY id DESC LIMIT 5");
                if ($imports && $imports->num_rows > 0):
                ?>
                <table class="tracks-table" style="margin-top:20px;">
                    <thead><tr><th>#</th><th>File</th><th>Total</th><th>Imported</th><th>Skipped</th><th>Failed</th><th>Date</th></tr></thead>
                    <tbody>
                    <?php while ($imp = $imports->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $imp['id']; ?></td>
                            <td><?php echo htmlspecialchars($imp['filename']); ?></td>
                            <td><?php echo $imp['rows_total']; ?></td>
                            <td style="color:#00c853;"><?php echo $imp['rows_imported']; ?></td>
                            <td style="color:#ffc107;"><?php echo $imp['rows_skipped']; ?></td>
                            <td style="color:#ff5252;"><?php echo $imp['rows_failed']; ?></td>
                            <td><?php echo date('M j, Y H:i', strtotime($imp['created_at'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3 style="color: #00d4aa;"><?php echo number_format($summary['total_plays'] ?? 0); ?></h3>
                    <p>Total Plays/Streams</p>
                </div>
                <div class="stat-card">
                    <h3 style="color: #4facfe;"><?php echo number_format($summary['total_downloads'] ?? 0); ?></h3>
                    <p>Total Downloads</p>
                </div>
                <div class="stat-card">
                    <h3 style="color: #ffc107;">$<?php echo number_format($summary['total_revenue'] ?? 0, 2); ?></h3>
                    <p>Total Revenue</p>
                </div>
                <div class="stat-card">
                    <h3 style="color: #00c853;">$<?php echo number_format($summary['total_royalties'] ?? 0, 2); ?></h3>
                    <p>Total Royalties Paid</p>
                </div>
            </div>

            <!-- Published Releases -->
            <h2 style="font-size: 20px; margin-bottom: 20px;">Published Releases</h2>
            
            <?php while ($release = $releases->fetch_assoc()): 
                // Get tracks with royalty data
                $tracks_result = $conn->query("SELECT rt.*, 
                    (SELECT SUM(plays) FROM track_royalties WHERE track_id = rt.id) as total_plays,
                    (SELECT SUM(royalty_amount) FROM track_royalties WHERE track_id = rt.id) as total_royalty
                    FROM release_tracks rt 
                    WHERE rt.release_id = {$release['id']} 
                    ORDER BY rt.track_number");
            ?>
            <div class="release-card">
                <div class="release-header">
                    <div class="release-info">
                        <h3><?php echo htmlspecialchars($release['title']); ?></h3>
                        <p>by <?php echo htmlspecialchars($release['primary_artist']); ?> • Published <?php echo date('M j, Y', strtotime($release['ready_at'])); ?></p>
                    </div>
                </div>
                
                <table class="tracks-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Track</th>
                            <th>Total Plays</th>
                            <th>Total Royalties</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($track = $tracks_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $track['track_number']; ?></td>
                            <td><?php echo htmlspecialchars($track['song_title']); ?></td>
                            <td><?php echo number_format($track['total_plays'] ?? 0); ?></td>
                            <td>$<?php echo number_format($track['total_royalty'] ?? 0, 2); ?></td>
                            <td>
                                <button class="btn-add" onclick="openRoyaltyModal(<?php echo $track['id']; ?>, <?php echo $release['id']; ?>, '<?php echo htmlspecialchars(addslashes($track['song_title'])); ?>')">
                                    <span class="mdi mdi-plus"></span> Add Stats
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php endwhile; ?>
        </main>
    </div>

    <!-- Add Royalty Modal -->
    <div class="modal-overlay" id="royaltyModal">
        <div class="modal">
            <h2><span class="mdi mdi-chart-bar"></span> Add Track Statistics</h2>
            <form method="POST">
                <input type="hidden" name="track_id" id="modal_track_id">
                <input type="hidden" name="release_id" id="modal_release_id">
                
                <div class="form-group">
                    <label>Track</label>
                    <input type="text" id="modal_track_name" readonly style="opacity: 0.7;">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Plays/Streams</label>
                        <input type="number" name="plays" min="0" value="0" required>
                    </div>
                    <div class="form-group">
                        <label>Downloads</label>
                        <input type="number" name="downloads" min="0" value="0">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Revenue Generated ($)</label>
                        <input type="number" name="revenue" step="0.01" min="0" value="0.00">
                    </div>
                    <div class="form-group">
                        <label>Royalty Amount ($)</label>
                        <input type="number" name="royalty_amount" step="0.01" min="0" value="0.00" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Platform</label>
                        <select name="platform">
                            <option value="All Platforms">All Platforms</option>
                            <option value="Spotify">Spotify</option>
                            <option value="Apple Music">Apple Music</option>
                            <option value="YouTube Music">YouTube Music</option>
                            <option value="Amazon Music">Amazon Music</option>
                            <option value="JioSaavn">JioSaavn</option>
                            <option value="Gaana">Gaana</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Reporting Period</label>
                        <input type="text" name="reporting_period" value="<?php echo date('F Y'); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" rows="3" placeholder="Any additional notes..."></textarea>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeRoyaltyModal()">Cancel</button>
                    <button type="submit" name="save_royalty" class="btn btn-primary">
                        <span class="mdi mdi-content-save"></span> Save
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openRoyaltyModal(trackId, releaseId, trackName) {
            document.getElementById('modal_track_id').value = trackId;
            document.getElementById('modal_release_id').value = releaseId;
            document.getElementById('modal_track_name').value = trackName;
            document.getElementById('royaltyModal').classList.add('active');
        }
        
        function closeRoyaltyModal() {
            document.getElementById('royaltyModal').classList.remove('active');
        }
        
        // Close modal on outside click
        document.getElementById('royaltyModal').addEventListener('click', function(e) {
            if (e.target === this) closeRoyaltyModal();
        });
    </script>
</body>
</html>
