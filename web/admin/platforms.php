<?php
/**
 * Admin Platform Management
 * Customize platform icons and colors
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';

// Check admin access
if (!isAdmin()) {
    header('Location: /admin/login.php');
    exit;
}

$pageTitle = 'Manage Platforms - Admin';

// Handle save
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_platforms'])) {
    $updated = 0;
    foreach ($_POST['platforms'] as $name => $data) {
        $icon = $data['icon'] ?? 'mdi-music';
        $color = $data['color'] ?? 'linear-gradient(135deg, #00b7ff, #8b5cf6)';
        
        $stmt = $conn->prepare("INSERT INTO platform_settings (platform_name, icon_class, gradient_color, updated_at) 
                               VALUES (?, ?, ?, NOW()) 
                               ON DUPLICATE KEY UPDATE 
                               icon_class = VALUES(icon_class), 
                               gradient_color = VALUES(gradient_color), 
                               updated_at = NOW()");
        $stmt->bind_param("sss", $name, $icon, $color);
        $stmt->execute();
        $updated++;
    }
    $message = "Updated $updated platforms successfully!";
}

// All platform definitions
$platform_definitions = [
    ['name' => 'Spotify', 'icon' => 'mdi-spotify', 'color' => 'linear-gradient(135deg, #1DB954, #191414)'],
    ['name' => 'Apple Music', 'icon' => 'mdi-apple', 'color' => 'linear-gradient(135deg, #FA243C, #000000)'],
    ['name' => 'YouTube Music', 'icon' => 'mdi-youtube', 'color' => 'linear-gradient(135deg, #FF0000, #282828)'],
    ['name' => 'Amazon Music', 'icon' => 'mdi-amazon', 'color' => 'linear-gradient(135deg, #00C6FF, #0072FF)'],
    ['name' => 'Tidal', 'icon' => 'mdi-wave', 'color' => 'linear-gradient(135deg, #000000, #FFFFFF)'],
    ['name' => 'Deezer', 'icon' => 'mdi-music-box', 'color' => 'linear-gradient(135deg, #00b7ff, #8b5cf6)'],
    ['name' => 'Pandora', 'icon' => 'mdi-radio', 'color' => 'linear-gradient(135deg, #005483, #3668FF)'],
    ['name' => 'SoundCloud', 'icon' => 'mdi-soundcloud', 'color' => 'linear-gradient(135deg, #FF5500, #FF8800)'],
    ['name' => 'Napster', 'icon' => 'mdi-music-box', 'color' => 'linear-gradient(135deg, #FF9900, #FF5500)'],
    ['name' => 'iHeartRadio', 'icon' => 'mdi-heart', 'color' => 'linear-gradient(135deg, #C10000, #FF0000)'],
    ['name' => 'Shazam', 'icon' => 'mdi-magnify', 'color' => 'linear-gradient(135deg, #00CC99, #0088FF)'],
    ['name' => 'Qobuz', 'icon' => 'mdi-music', 'color' => 'linear-gradient(135deg, #1A1A1A, #FF5500)'],
    ['name' => 'JioSaavn', 'icon' => 'mdi-music', 'color' => 'linear-gradient(135deg, #2BC47C, #1E8C5A)'],
    ['name' => 'Gaana', 'icon' => 'mdi-music-circle', 'color' => 'linear-gradient(135deg, #E91E63, #C2185B)'],
    ['name' => 'Wynk Music', 'icon' => 'mdi-music-note', 'color' => 'linear-gradient(135deg, #FF6B00, #FF8E00)'],
    ['name' => 'Hungama', 'icon' => 'mdi-play-circle', 'color' => 'linear-gradient(135deg, #FF4081, #C51162)'],
    ['name' => 'Resso', 'icon' => 'mdi-music', 'color' => 'linear-gradient(135deg, #FF0050, #00E0FF)'],
    ['name' => 'Times Music', 'icon' => 'mdi-music', 'color' => 'linear-gradient(135deg, #FF0000, #990000)'],
    ['name' => 'QQ Music', 'icon' => 'mdi-music', 'color' => 'linear-gradient(135deg, #31C27C, #00A651)'],
    ['name' => 'NetEase Cloud', 'icon' => 'mdi-cloud-music', 'color' => 'linear-gradient(135deg, #C20C0C, #8B0000)'],
    ['name' => 'KuGou', 'icon' => 'mdi-music', 'color' => 'linear-gradient(135deg, #00BFFF, #1E90FF)'],
    ['name' => 'Joox', 'icon' => 'mdi-music-box-outline', 'color' => 'linear-gradient(135deg, #00B894, #00CEC9)'],
    ['name' => 'Line Music', 'icon' => 'mdi-chat', 'color' => 'linear-gradient(135deg, #00B900, #00E676)'],
    ['name' => 'KKBox', 'icon' => 'mdi-music', 'color' => 'linear-gradient(135deg, #0099FF, #0066CC)'],
    ['name' => 'AWA', 'icon' => 'mdi-waves', 'color' => 'linear-gradient(135deg, #00BCD4, #0097A7)'],
    ['name' => 'Genie', 'icon' => 'mdi-music', 'color' => 'linear-gradient(135deg, #00C853, #00E676)'],
    ['name' => 'Melon', 'icon' => 'mdi-music', 'color' => 'linear-gradient(135deg, #00C853, #00E676)'],
    ['name' => 'Bugs', 'icon' => 'mdi-music', 'color' => 'linear-gradient(135deg, #FF1744, #D50000)'],
    ['name' => 'Yandex Music', 'icon' => 'mdi-music', 'color' => 'linear-gradient(135deg, #FF0000, #FC0)'],
    ['name' => 'Zvooq', 'icon' => 'mdi-music', 'color' => 'linear-gradient(135deg, #00b7ff, #556270)'],
    ['name' => 'Anghami', 'icon' => 'mdi-music', 'color' => 'linear-gradient(135deg, #00b7ff, #556270)'],
    ['name' => 'Musixmatch', 'icon' => 'mdi-music', 'color' => 'linear-gradient(135deg, #FF5500, #FF9900)'],
    ['name' => 'Genius', 'icon' => 'mdi-music', 'color' => 'linear-gradient(135deg, #FFFF64, #000000)'],
    ['name' => 'Boomplay', 'icon' => 'mdi-music', 'color' => 'linear-gradient(135deg, #FFD700, #FFAA00)'],
    ['name' => 'Mdundo', 'icon' => 'mdi-music', 'color' => 'linear-gradient(135deg, #3399FF, #0066CC)'],
    ['name' => 'Rotana', 'icon' => 'mdi-music', 'color' => 'linear-gradient(135deg, #FF1744, #D50000)'],
    ['name' => 'Beatport', 'icon' => 'mdi-music', 'color' => 'linear-gradient(135deg, #9E00FF, #FF00FF)'],
    ['name' => 'Traxsource', 'icon' => 'mdi-music', 'color' => 'linear-gradient(135deg, #FF5500, #FF9900)'],
    ['name' => 'JunoDownload', 'icon' => 'mdi-music', 'color' => 'linear-gradient(135deg, #00C853, #00E676)'],
    ['name' => '7digital', 'icon' => 'mdi-music', 'color' => 'linear-gradient(135deg, #0066CC, #0099FF)'],
    ['name' => 'Peloton', 'icon' => 'mdi-bike', 'color' => 'linear-gradient(135deg, #FF0000, #990000)'],
    ['name' => 'Waze', 'icon' => 'mdi-map-marker', 'color' => 'linear-gradient(135deg, #00C853, #00E676)'],
    ['name' => 'Uber', 'icon' => 'mdi-car', 'color' => 'linear-gradient(135deg, #000000, #333333)'],
    ['name' => 'Lyft', 'icon' => 'mdi-car', 'color' => 'linear-gradient(135deg, #FF00BF, #FF66CC)'],
    ['name' => 'Twitch', 'icon' => 'mdi-twitch', 'color' => 'linear-gradient(135deg, #9146FF, #6441A5)'],
    ['name' => 'TikTok', 'icon' => 'mdi-music-note', 'color' => 'linear-gradient(135deg, #000000, #FF0050)'],
    ['name' => 'Instagram', 'icon' => 'mdi-instagram', 'color' => 'linear-gradient(45deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888)'],
    ['name' => 'Facebook', 'icon' => 'mdi-facebook', 'color' => 'linear-gradient(135deg, #1877F2, #4267B2)'],
    ['name' => 'YouTube Shorts', 'icon' => 'mdi-youtube', 'color' => 'linear-gradient(135deg, #FF0000, #282828)'],
    ['name' => 'Snapchat', 'icon' => 'mdi-snapchat', 'color' => 'linear-gradient(135deg, #FFFC00, #000000)'],
    ['name' => 'Triller', 'icon' => 'mdi-video', 'color' => 'linear-gradient(135deg, #FF0000, #000000)'],
    ['name' => 'Likee', 'icon' => 'mdi-heart', 'color' => 'linear-gradient(135deg, #FF0050, #00D4FF)'],
    ['name' => 'Trebel', 'icon' => 'mdi-download', 'color' => 'linear-gradient(135deg, #00C853, #00D4AA)'],
    ['name' => 'Twitter / X', 'icon' => 'mdi-twitter', 'color' => 'linear-gradient(135deg, #1DA1F2, #14171A)'],
];

// Load saved settings
$saved = [];
$result = $conn->query("SELECT * FROM platform_settings");
while ($row = $result->fetch_assoc()) {
    $saved[$row['platform_name']] = $row;
}

// Merge with defaults
foreach ($platform_definitions as &$platform) {
    if (isset($saved[$platform['name']])) {
        $platform['icon'] = $saved[$platform['name']]['icon_class'];
        $platform['color'] = $saved[$platform['name']]['gradient_color'];
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.materialdesignicons.com/6.5.95/css/materialdesignicons.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #0f0f1a; color: #fff; }
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
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            transition: all 0.3s;
        }
        .nav-item:hover, .nav-item.active {
            background: rgba(255,255,255,0.05);
            color: #fff;
            border-left: 3px solid #00b7ff;
        }
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 30px;
        }
        .platforms-container {
            max-width: 1400px;
        }
        .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    .page-title {
        font-size: 28px;
        font-weight: 700;
    }
    .alert {
        padding: 15px 20px;
        border-radius: 10px;
        margin-bottom: 20px;
    }
    .alert-success {
        background: rgba(0, 212, 170, 0.2);
        border: 1px solid #00d4aa;
        color: #00d4aa;
    }
    .platform-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    .platform-card {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        padding: 20px;
    }
    .platform-preview {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 15px;
    }
    .platform-icon {
        width: 60px;
        height: 60px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        color: #fff;
    }
    .platform-name {
        font-size: 16px;
        font-weight: 600;
    }
    .form-group {
        margin-bottom: 12px;
    }
    .form-group label {
        display: block;
        font-size: 12px;
        color: rgba(255,255,255,0.6);
        margin-bottom: 5px;
    }
    .form-control {
        width: 100%;
        padding: 10px 12px;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 8px;
        color: #fff;
        font-size: 14px;
    }
    .form-control:focus {
        outline: none;
        border-color: #00d4aa;
    }
    .btn-save {
        background: linear-gradient(135deg, #00d4aa, #00b894);
        color: #000;
        border: none;
        padding: 15px 40px;
        border-radius: 10px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .btn-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 212, 170, 0.3);
    }
    .icon-help {
        font-size: 11px;
        color: rgba(255,255,255,0.5);
        margin-top: 5px;
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
                <a href="submissions.php" class="nav-item"><span class="mdi mdi-music"></span>Submissions</a>
                <a href="artist_verifications.php" class="nav-item"><span class="mdi mdi-check-decagram"></span>Artist Verifications</a>
                <a href="cover_art.php" class="nav-item"><span class="mdi mdi-image"></span>Cover Art</a>
                <a href="platforms.php" class="nav-item active"><span class="mdi mdi-store-cog"></span>Platforms</a>
                <a href="revenue.php" class="nav-item"><span class="mdi mdi-currency-usd"></span>Revenue</a>
                <a href="payouts.php" class="nav-item"><span class="mdi mdi-cash-multiple"></span>Payouts</a>
                <a href="payments.php" class="nav-item"><span class="mdi mdi-credit-card"></span>Payments</a>
                <a href="settings.php" class="nav-item"><span class="mdi mdi-cog"></span>Settings</a>
                <a href="logout.php" class="nav-item"><span class="mdi mdi-logout"></span>Logout</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title"><i class="mdi mdi-store-cog"></i> Manage Platform Icons</h1>
                <a href="index.php" class="btn btn-secondary"><i class="mdi mdi-arrow-left"></i> Back</a>
            </div>
            
            <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="mdi mdi-check-circle"></i> <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="platform-grid">
                    <?php foreach ($platform_definitions as $platform): ?>
                    <div class="platform-card">
                        <div class="platform-preview">
                            <div class="platform-icon" style="background: <?php echo $platform['color']; ?>">
                                <i class="mdi <?php echo $platform['icon']; ?>"></i>
                            </div>
                            <div class="platform-name"><?php echo htmlspecialchars($platform['name']); ?></div>
                        </div>
                        
                        <input type="hidden" name="platforms[<?php echo htmlspecialchars($platform['name']); ?>][name]" value="<?php echo htmlspecialchars($platform['name']); ?>">
                        
                        <div class="form-group">
                            <label>Icon Class (Material Design)</label>
                            <input type="text" class="form-control" name="platforms[<?php echo htmlspecialchars($platform['name']); ?>][icon]" 
                                   value="<?php echo htmlspecialchars($platform['icon']); ?>">
                            <div class="icon-help">Use mdi-* classes from Material Design Icons</div>
                        </div>
                        
                        <div class="form-group">
                            <label>Gradient Color</label>
                            <input type="text" class="form-control" name="platforms[<?php echo htmlspecialchars($platform['name']); ?>][color]" 
                                   value="<?php echo htmlspecialchars($platform['color']); ?>">
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
        
        <div style="text-align: center;">
            <button type="submit" name="save_platforms" class="btn-save">
                <i class="mdi mdi-content-save"></i> Save All Changes
            </button>
        </div>
    </form>
</div>
        </main>
    </div>
</body>
</html>
