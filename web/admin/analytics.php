<?php
/**
 * HiTune Music Distribution - Admin Analytics Management
 * Manual streaming stats and revenue updates per platform
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/config.php';

session_start();
requireAdmin();

// Handle analytics update
if (isset($_POST['update_analytics'])) {
    $release_id = intval($_POST['release_id']);
    $platform_id = intval($_POST['platform_id']);
    $streams = intval($_POST['streams'] ?? 0);
    $revenue = floatval($_POST['revenue'] ?? 0);
    $period = $_POST['period'] ?? date('Y-m');
    
    // Check if record exists
    $check = $conn->prepare("SELECT id FROM release_analytics WHERE release_id = ? AND platform_id = ? AND period = ?");
    $check->bind_param("iis", $release_id, $platform_id, $period);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing
        $stmt = $conn->prepare("UPDATE release_analytics SET streams = ?, revenue = ?, updated_at = NOW() WHERE release_id = ? AND platform_id = ? AND period = ?");
        $stmt->bind_param("idiis", $streams, $revenue, $release_id, $platform_id, $period);
    } else {
        // Insert new
        $stmt = $conn->prepare("INSERT INTO release_analytics (release_id, platform_id, period, streams, revenue) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisid", $release_id, $platform_id, $period, $streams, $revenue);
    }
    $stmt->execute();
    
    header('Location: analytics.php?updated=1');
    exit;
}

// Handle track-level analytics update
if (isset($_POST['update_track_analytics'])) {
    $track_id = intval($_POST['track_id']);
    $platform_id = intval($_POST['platform_id']);
    $streams = intval($_POST['streams'] ?? 0);
    $revenue = floatval($_POST['revenue'] ?? 0);
    $period = $_POST['period'] ?? date('Y-m');
    
    // Check if record exists
    $check = $conn->prepare("SELECT id FROM track_analytics WHERE track_id = ? AND platform_id = ? AND period = ?");
    $check->bind_param("iis", $track_id, $platform_id, $period);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows > 0) {
        $stmt = $conn->prepare("UPDATE track_analytics SET streams = ?, revenue = ?, updated_at = NOW() WHERE track_id = ? AND platform_id = ? AND period = ?");
        $stmt->bind_param("idiis", $streams, $revenue, $track_id, $platform_id, $period);
    } else {
        $stmt = $conn->prepare("INSERT INTO track_analytics (track_id, platform_id, period, streams, revenue) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisid", $track_id, $platform_id, $period, $streams, $revenue);
    }
    $stmt->execute();
    
    header('Location: analytics.php?track_updated=1');
    exit;
}

// Get filter parameters
$filter_release = isset($_GET['release_id']) ? intval($_GET['release_id']) : 0;
$filter_platform = isset($_GET['platform_id']) ? intval($_GET['platform_id']) : 0;
$filter_period = isset($_GET['period']) ? $_GET['period'] : date('Y-m');

// Get all releases for dropdown
$releases = $conn->query("SELECT r.id, r.title, r.primary_artist, r.status, u.name as user_name 
    FROM releases r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.status = 'ready'
    ORDER BY r.title");

// Get all platforms
$platforms = $conn->query("SELECT id, platform_name 
    FROM release_platforms 
    WHERE is_selected = 1 
    GROUP BY platform_name 
    ORDER BY platform_name");

// Get analytics data with filters
$where_clauses = ["1=1"];
$params = [];
$types = "";

if ($filter_release) {
    $where_clauses[] = "ra.release_id = ?";
    $params[] = $filter_release;
    $types .= "i";
}
if ($filter_platform) {
    $where_clauses[] = "ra.platform_id = ?";
    $params[] = $filter_platform;
    $types .= "i";
}
if ($filter_period) {
    $where_clauses[] = "ra.period = ?";
    $params[] = $filter_period;
    $types .= "s";
}

$where_sql = implode(" AND ", $where_clauses);

// Check if analytics table exists, create if not
$conn->query("CREATE TABLE IF NOT EXISTS release_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    release_id INT NOT NULL,
    platform_id INT NOT NULL,
    period VARCHAR(7) NOT NULL,
    streams INT DEFAULT 0,
    revenue DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_analytics (release_id, platform_id, period)
)");

$conn->query("CREATE TABLE IF NOT EXISTS track_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    track_id INT NOT NULL,
    platform_id INT NOT NULL,
    period VARCHAR(7) NOT NULL,
    streams INT DEFAULT 0,
    revenue DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_track_analytics (track_id, platform_id, period)
)");

// Get summary stats
$summary_sql = "SELECT 
    SUM(ra.streams) as total_streams,
    SUM(ra.revenue) as total_revenue,
    COUNT(DISTINCT ra.release_id) as releases_with_data
    FROM release_analytics ra
    WHERE " . $where_sql;

$summary_stmt = $conn->prepare($summary_sql);
if (!empty($params)) {
    $summary_stmt->bind_param($types, ...$params);
}
$summary_stmt->execute();
$summary = $summary_stmt->get_result()->fetch_assoc();

// Get platform-wise breakdown
$platform_stats = $conn->query("SELECT 
    rp.id,
    rp.platform_name,
    COALESCE(SUM(ra.streams), 0) as streams,
    COALESCE(SUM(ra.revenue), 0) as revenue
    FROM release_platforms rp
    LEFT JOIN release_analytics ra ON rp.id = ra.platform_id
    WHERE rp.is_selected = 1
    GROUP BY rp.id, rp.platform_name
    ORDER BY streams DESC");

// Get release details with tracks if filtered
$release_details = null;
$tracks = [];
if ($filter_release) {
    $stmt = $conn->prepare("SELECT r.*, u.name as user_name FROM releases r JOIN users u ON r.user_id = u.id WHERE r.id = ?");
    $stmt->bind_param("i", $filter_release);
    $stmt->execute();
    $release_details = $stmt->get_result()->fetch_assoc();
    
    // Get tracks for this release
    $tracks_result = $conn->query("SELECT * FROM release_tracks WHERE release_id = $filter_release ORDER BY track_number");
    while ($row = $tracks_result->fetch_assoc()) {
        $tracks[] = $row;
    }
}

// Generate period options (last 12 months)
$periods = [];
for ($i = 0; $i < 12; $i++) {
    $periods[] = date('Y-m', strtotime("-$i months"));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Management - Admin Panel</title>
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
        .btn-secondary {
            background: rgba(255,255,255,0.1);
        }
        .btn-success {
            background: linear-gradient(135deg, #00d4aa, #00c853);
            color: #000;
        }
        
        /* Stats Cards - Improved */
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: linear-gradient(135deg, rgba(255,255,255,0.08) 0%, rgba(255,255,255,0.02) 100%);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 25px;
            text-align: center;
            transition: all 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            border-color: rgba(255,255,255,0.2);
        }
        .stat-card h3 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .stat-card p {
            font-size: 13px;
            color: rgba(255,255,255,0.5);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .stat-card.streams h3 { color: #4facfe; }
        .stat-card.revenue h3 { color: #00c853; }
        .stat-card.releases h3 { color: #00b7ff; }
        .stat-card.platforms h3 { color: #ffc107; }
        
        /* Filter Section - Improved */
        .filter-section {
            background: linear-gradient(135deg, rgba(0,183,255,0.05) 0%, rgba(0,212,170,0.05) 100%);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 30px;
        }
        .filter-section h3 {
            font-size: 18px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #00b7ff;
        }
        .filter-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 15px;
            align-items: end;
        }
        .filter-group label {
            display: block;
            font-size: 11px;
            color: rgba(255,255,255,0.6);
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 500;
        }
        .filter-group select, .filter-group input {
            width: 100%;
            padding: 12px 15px;
            background: rgba(0,0,0,0.2);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 10px;
            color: #fff;
            font-size: 14px;
            transition: all 0.3s;
        }
        .filter-group select:hover, .filter-group input:hover {
            border-color: rgba(0,183,255,0.4);
        }
        .filter-group select:focus, .filter-group input:focus {
            border-color: #00b7ff;
            outline: none;
        }
        .filter-group select option {
            background: #1a1a2e;
            color: #fff;
        }
        
        /* Platform Stats - Improved Layout */
        .platform-stats {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 30px;
        }
        .platform-stats h3 {
            font-size: 18px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .platform-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 12px;
        }
        .platform-card {
            background: linear-gradient(135deg, rgba(0,0,0,0.3) 0%, rgba(255,255,255,0.05) 100%);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 15px 10px;
            text-align: center;
            transition: all 0.3s ease;
            min-height: 90px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .platform-card:hover {
            border-color: rgba(0,183,255,0.5);
            transform: translateY(-2px);
        }
        .platform-card h4 {
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 8px;
            color: rgba(255,255,255,0.9);
            text-transform: capitalize;
        }
        .platform-card .streams {
            font-size: 20px;
            font-weight: 700;
            color: #4facfe;
            line-height: 1;
        }
        .platform-card .revenue {
            font-size: 12px;
            color: #00c853;
            margin-top: 4px;
            font-weight: 500;
        }
        
        /* Update Form - Improved */
        .update-section {
            background: linear-gradient(135deg, rgba(0,212,170,0.08) 0%, rgba(79,172,254,0.05) 100%);
            border: 1px solid rgba(0,212,170,0.3);
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 30px;
        }
        .update-section h3 {
            font-size: 18px;
            margin-bottom: 20px;
            color: #00d4aa;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr 1fr auto;
            gap: 15px;
            align-items: end;
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-size: 11px;
            color: rgba(255,255,255,0.7);
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 500;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px 15px;
            background: rgba(0,0,0,0.2);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 10px;
            color: #fff;
            font-size: 14px;
            transition: all 0.3s;
        }
        .form-group input:focus, .form-group select:focus {
            border-color: #00d4aa;
            outline: none;
        }
        .form-group input::placeholder {
            color: rgba(255,255,255,0.3);
        }
        
        /* Track Section - Improved */
        .track-section {
            background: linear-gradient(135deg, rgba(0,183,255,0.05) 0%, rgba(139,92,246,0.05) 100%);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 25px;
        }
        .track-section h3 {
            font-size: 18px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #8b5cf6;
        }
        .track-item {
            background: rgba(0,0,0,0.25);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }
        .track-item:hover {
            border-color: rgba(0,183,255,0.3);
        }
        .track-item h4 {
            font-size: 15px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: rgba(255,255,255,0.95);
        }
        .track-item h4 span {
            background: linear-gradient(135deg, #00b7ff, #8b5cf6);
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
            flex-shrink: 0;
        }
        .track-item .form-grid {
            grid-template-columns: 1.5fr 1fr 1fr auto;
            margin-bottom: 0;
        }
        .track-item .btn {
            padding: 10px 20px;
            font-size: 13px;
        }
        
        /* Alerts */
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
        
        @media (max-width: 1400px) {
            .platform-grid { grid-template-columns: repeat(5, 1fr); }
        }
        @media (max-width: 1200px) {
            .platform-grid { grid-template-columns: repeat(4, 1fr); }
            .filter-grid { grid-template-columns: repeat(2, 1fr); }
            .stats-cards { grid-template-columns: repeat(2, 1fr); }
            .form-grid { grid-template-columns: repeat(2, 1fr); }
            .track-item .form-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 900px) {
            .platform-grid { grid-template-columns: repeat(3, 1fr); }
            .filter-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .platform-grid { grid-template-columns: repeat(2, 1fr); }
            .stats-cards { grid-template-columns: 1fr; }
            .form-grid { grid-template-columns: 1fr; }
            .track-item .form-grid { grid-template-columns: 1fr; }
            .main-content { margin-left: 0; padding: 20px; }
            .sidebar { display: none; }
            .platform-card { padding: 12px 8px; min-height: 80px; }
            .platform-card h4 { font-size: 12px; }
            .platform-card .streams { font-size: 18px; }
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
                <a href="analytics.php" class="nav-item active"><span class="mdi mdi-chart-line"></span>Analytics</a>
                <a href="revenue.php" class="nav-item"><span class="mdi mdi-currency-usd"></span>Revenue</a>
                <a href="payouts.php" class="nav-item"><span class="mdi mdi-cash-multiple"></span>Payouts</a>
                <a href="payments.php" class="nav-item"><span class="mdi mdi-credit-card"></span>Payments</a>
                <a href="settings.php" class="nav-item"><span class="mdi mdi-cog"></span>Settings</a>
                <a href="logout.php" class="nav-item"><span class="mdi mdi-logout"></span>Logout</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="header">
                <h1><span class="mdi mdi-chart-line"></span> Analytics Management</h1>
                <a href="index.php" class="btn">Back to Dashboard</a>
            </div>
            
            <?php if (isset($_GET['updated'])): ?>
            <div class="alert alert-success">
                <span class="mdi mdi-check-circle"></span> Release analytics updated successfully!
            </div>
            <?php endif; ?>
            <?php if (isset($_GET['track_updated'])): ?>
            <div class="alert alert-success">
                <span class="mdi mdi-check-circle"></span> Track analytics updated successfully!
            </div>
            <?php endif; ?>

            <!-- Summary Stats -->
            <div class="stats-cards">
                <div class="stat-card streams">
                    <h3><?php echo number_format($summary['total_streams'] ?? 0); ?></h3>
                    <p>Total Streams</p>
                </div>
                <div class="stat-card revenue">
                    <h3>₹<?php echo number_format($summary['total_revenue'] ?? 0, 2); ?></h3>
                    <p>Total Revenue</p>
                </div>
                <div class="stat-card releases">
                    <h3><?php echo $summary['releases_with_data'] ?? 0; ?></h3>
                    <p>Releases with Data</p>
                </div>
                <div class="stat-card platforms">
                    <h3><?php echo $platforms->num_rows; ?></h3>
                    <p>Active Platforms</p>
                </div>
            </div>

            <!-- Platform Breakdown -->
            <div class="platform-stats">
                <h3><span class="mdi mdi-store"></span> Platform-wise Performance</h3>
                <div class="platform-grid">
                    <?php while ($platform = $platform_stats->fetch_assoc()): ?>
                    <div class="platform-card">
                        <h4><?php echo htmlspecialchars($platform['platform_name']); ?></h4>
                        <div class="streams"><?php echo number_format($platform['streams']); ?></div>
                        <div class="revenue">₹<?php echo number_format($platform['revenue'], 2); ?></div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <h3><span class="mdi mdi-filter"></span> Filter & Update Analytics</h3>
                <form method="GET" class="filter-grid">
                    <div class="filter-group">
                        <label>Select Release</label>
                        <select name="release_id" onchange="this.form.submit()">
                            <option value="">All Releases</option>
                            <?php 
                            $releases->data_seek(0);
                            while ($rel = $releases->fetch_assoc()): 
                            ?>
                            <option value="<?php echo $rel['id']; ?>" <?php echo $filter_release == $rel['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($rel['title'] . ' - ' . $rel['primary_artist']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Select Platform</label>
                        <select name="platform_id" onchange="this.form.submit()">
                            <option value="">All Platforms</option>
                            <?php 
                            $platforms->data_seek(0);
                            while ($plat = $platforms->fetch_assoc()): 
                            ?>
                            <option value="<?php echo $plat['id']; ?>" <?php echo $filter_platform == $plat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($plat['platform_name']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Period (Month)</label>
                        <select name="period" onchange="this.form.submit()">
                            <?php foreach ($periods as $per): ?>
                            <option value="<?php echo $per; ?>" <?php echo $filter_period == $per ? 'selected' : ''; ?>>
                                <?php echo date('F Y', strtotime($per . '-01')); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <a href="analytics.php" class="btn btn-secondary" style="text-align:center;justify-content:center;">
                            <span class="mdi mdi-refresh"></span> Reset
                        </a>
                    </div>
                </form>
            </div>

            <!-- Release Analytics Update Form -->
            <?php if ($filter_release && $release_details): ?>
            <div class="update-section">
                <h3><span class="mdi mdi-pencil"></span> Update Analytics: <?php echo htmlspecialchars($release_details['title']); ?></h3>
                <form method="POST">
                    <input type="hidden" name="release_id" value="<?php echo $filter_release; ?>">
                    <input type="hidden" name="period" value="<?php echo $filter_period; ?>">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Platform</label>
                            <select name="platform_id" required>
                                <option value="">Select Platform</option>
                                <?php
                                $platforms->data_seek(0);
                                while ($plat = $platforms->fetch_assoc()):
                                ?>
                                <option value="<?php echo $plat['id']; ?>">
                                    <?php echo htmlspecialchars($plat['platform_name']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Streams Count</label>
                            <input type="number" name="streams" placeholder="e.g. 50000" min="0" required>
                        </div>
                        <div class="form-group">
                            <label>Revenue (₹)</label>
                            <input type="number" name="revenue" placeholder="e.g. 1250.50" step="0.01" min="0" required>
                        </div>
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" name="update_analytics" class="btn btn-success" style="width: 100%; justify-content: center;">
                                <span class="mdi mdi-content-save"></span> Save
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Track-wise Analytics -->
            <?php if (!empty($tracks)): ?>
            <div class="track-section">
                <h3><span class="mdi mdi-music"></span> Track-wise Analytics</h3>
                <?php foreach ($tracks as $track): ?>
                <div class="track-item">
                    <h4><span><?php echo $track['track_number']; ?></span> <?php echo htmlspecialchars($track['song_title']); ?></h4>
                    <form method="POST">
                        <input type="hidden" name="track_id" value="<?php echo $track['id']; ?>">
                        <input type="hidden" name="period" value="<?php echo $filter_period; ?>">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Platform</label>
                                <select name="platform_id" required>
                                    <option value="">Select Platform</option>
                                    <?php
                                    $platforms->data_seek(0);
                                    while ($plat = $platforms->fetch_assoc()):
                                    ?>
                                    <option value="<?php echo $plat['id'] ?? rand(1000,9999); ?>">
                                        <?php echo htmlspecialchars($plat['platform_name_display'] ?? $plat['platform_name']); ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Streams Count</label>
                                <input type="number" name="streams" placeholder="e.g. 25000" min="0" required>
                            </div>
                            <div class="form-group">
                                <label>Revenue (₹)</label>
                                <input type="number" name="revenue" placeholder="e.g. 625.25" step="0.01" min="0" required>
                            </div>
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="submit" name="update_track_analytics" class="btn btn-success" style="width: 100%; justify-content: center;">
                                    <span class="mdi mdi-content-save"></span> Save
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
