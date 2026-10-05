<?php
/**
 * Admin Panel - Artist Accounts Preview Images Settings
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/config.php';

session_start();
requireAdmin();

$success = '';
$error = '';

// Handle image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_image'])) {
    $platform = $_POST['platform'] ?? '';
    
    if (!in_array($platform, ['spotify', 'apple_music', 'youtube'])) {
        $error = 'Invalid platform';
    } elseif (!isset($_FILES['preview_image']) || $_FILES['preview_image']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Please select an image to upload';
    } else {
        $file = $_FILES['preview_image'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        
        if (!in_array($file['type'], $allowedTypes)) {
            $error = 'Only JPG, PNG, and WebP images are allowed';
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $error = 'Image size must be less than 5MB';
        } else {
            $uploadDir = __DIR__ . '/../uploads/artist_previews/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = $platform . '_preview_' . time() . '.' . $ext;
            $filepath = $uploadDir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                $webPath = '/uploads/artist_previews/' . $filename;
                $settingKey = $platform . '_preview_image';
                $description = $platform . ' preview image';
                
                // Save to settings table
                $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value, setting_group, description) VALUES (?, ?, 'artist_previews', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->bind_param("ssss", $settingKey, $webPath, $description, $webPath);
                
                if ($stmt->execute()) {
                    $success = ucfirst($platform) . ' preview image updated successfully!';
                } else {
                    $error = 'Failed to save image path to database';
                }
                $stmt->close();
            } else {
                $error = 'Failed to upload image';
            }
        }
    }
}

// Get current preview images
$previews = [
    'spotify' => '',
    'apple_music' => '',
    'youtube' => ''
];

$stmt = $conn->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_group = 'artist_previews'");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $key = str_replace('_preview_image', '', $row['setting_key']);
    $previews[$key] = $row['setting_value'];
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artist Accounts Preview Settings - Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@6.5.95/css/materialdesignicons.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: #0a0a0a;
            color: #fff;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #333;
        }
        .header h1 {
            font-size: 28px;
        }
        .back-link {
            color: #1db954;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .preview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
        }
        .preview-card {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 12px;
            padding: 25px;
        }
        .preview-card h3 {
            font-size: 18px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .preview-card.spotify h3 { color: #1db954; }
        .preview-card.apple h3 { color: #fa243c; }
        .preview-card.youtube h3 { color: #ff0000; }
        .current-preview {
            background: #0a0a0a;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            min-height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .current-preview img {
            max-width: 100%;
            max-height: 200px;
            object-fit: contain;
        }
        .current-preview span {
            color: #666;
            font-size: 14px;
        }
        .upload-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
        }
        .file-input-wrapper input[type=file] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        .file-input-btn {
            background: #333;
            color: #fff;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            display: inline-block;
            text-align: center;
            border: 1px solid #444;
        }
        .file-input-btn:hover {
            background: #444;
        }
        .upload-btn {
            background: #1db954;
            color: #000;
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .upload-btn:hover {
            background: #1ed760;
        }
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: rgba(29,185,84,0.2);
            border: 1px solid #1db954;
            color: #1db954;
        }
        .alert-error {
            background: rgba(255,82,82,0.2);
            border: 1px solid #ff5252;
            color: #ff5252;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="mdi mdi-image-multiple"></i> Artist Accounts Preview Settings</h1>
            <a href="index.php" class="back-link"><i class="mdi mdi-arrow-left"></i> Back to Dashboard</a>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="preview-grid">
            <!-- Spotify -->
            <div class="preview-card spotify">
                <h3><i class="mdi mdi-spotify"></i> Spotify for Artists</h3>
                <div class="current-preview">
                    <?php if ($previews['spotify']): ?>
                        <img src="<?php echo htmlspecialchars($previews['spotify']); ?>" alt="Spotify Preview">
                    <?php else: ?>
                        <span>No preview image uploaded</span>
                    <?php endif; ?>
                </div>
                <form method="POST" enctype="multipart/form-data" class="upload-form">
                    <input type="hidden" name="platform" value="spotify">
                    <input type="hidden" name="upload_image" value="1">
                    <div class="file-input-wrapper">
                        <div class="file-input-btn">
                            <i class="mdi mdi-upload"></i> Choose Image
                        </div>
                        <input type="file" name="preview_image" accept="image/jpeg,image/png,image/webp" required>
                    </div>
                    <button type="submit" class="upload-btn">Upload Preview</button>
                </form>
            </div>

            <!-- Apple Music -->
            <div class="preview-card apple">
                <h3><i class="mdi mdi-apple"></i> Apple Music for Artists</h3>
                <div class="current-preview">
                    <?php if ($previews['apple_music']): ?>
                        <img src="<?php echo htmlspecialchars($previews['apple_music']); ?>" alt="Apple Music Preview">
                    <?php else: ?>
                        <span>No preview image uploaded</span>
                    <?php endif; ?>
                </div>
                <form method="POST" enctype="multipart/form-data" class="upload-form">
                    <input type="hidden" name="platform" value="apple_music">
                    <input type="hidden" name="upload_image" value="1">
                    <div class="file-input-wrapper">
                        <div class="file-input-btn">
                            <i class="mdi mdi-upload"></i> Choose Image
                        </div>
                        <input type="file" name="preview_image" accept="image/jpeg,image/png,image/webp" required>
                    </div>
                    <button type="submit" class="upload-btn" style="background: #fa243c; color: #fff;">Upload Preview</button>
                </form>
            </div>

            <!-- YouTube -->
            <div class="preview-card youtube">
                <h3><i class="mdi mdi-youtube"></i> YouTube OAC</h3>
                <div class="current-preview">
                    <?php if ($previews['youtube']): ?>
                        <img src="<?php echo htmlspecialchars($previews['youtube']); ?>" alt="YouTube Preview">
                    <?php else: ?>
                        <span>No preview image uploaded</span>
                    <?php endif; ?>
                </div>
                <form method="POST" enctype="multipart/form-data" class="upload-form">
                    <input type="hidden" name="platform" value="youtube">
                    <input type="hidden" name="upload_image" value="1">
                    <div class="file-input-wrapper">
                        <div class="file-input-btn">
                            <i class="mdi mdi-upload"></i> Choose Image
                        </div>
                        <input type="file" name="preview_image" accept="image/jpeg,image/png,image/webp" required>
                    </div>
                    <button type="submit" class="upload-btn" style="background: #ff0000; color: #fff;">Upload Preview</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
