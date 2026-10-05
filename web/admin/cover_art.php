<?php
/**
 * HiTune Music Distribution - Admin Cover Art Templates Management
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/config.php';

session_start();
requireAdmin();

$success_message = '';
$error_message = '';

// Handle template upload
if (isset($_POST['upload_template'])) {
    $name = $_POST['template_name'] ?? '';
    $category = $_POST['category'] ?? 'general';
    $has_text_overlay = isset($_POST['has_text_overlay']) ? 1 : 0;
    $text_position = $_POST['text_position'] ?? 'bottom';
    $text_style = $_POST['text_style'] ?? 'modern';
    $text_color = $_POST['text_color'] ?? '#ffffff';
    
    if (empty($name)) {
        $error_message = 'Please enter a template name';
    } elseif (!isset($_FILES['template_image']) || $_FILES['template_image']['error'] !== UPLOAD_ERR_OK) {
        $error_message = 'Please select an image to upload';
    } else {
        $upload_dir = '/www/wwwroot/web/uploads/artwork/templates/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_ext = pathinfo($_FILES['template_image']['name'], PATHINFO_EXTENSION);
        $file_name = 'template_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
        $file_path = $upload_dir . $file_name;
        
        // Validate image
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $file_type = $_FILES['template_image']['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            $error_message = 'Only JPG and PNG images are allowed';
        } elseif ($_FILES['template_image']['size'] > 10 * 1024 * 1024) {
            $error_message = 'Image size must be less than 10MB';
        } else {
            if (move_uploaded_file($_FILES['template_image']['tmp_name'], $file_path)) {
                $relative_path = '/uploads/artwork/templates/' . $file_name;
                
                $stmt = $conn->prepare("INSERT INTO cover_art_templates (name, file_name, file_path, category, has_text_overlay, text_position, text_style, text_color, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssisssi", $name, $file_name, $relative_path, $category, $has_text_overlay, $text_position, $text_style, $text_color, $_SESSION['admin_id']);
                
                if ($stmt->execute()) {
                    $success_message = 'Template uploaded successfully!';
                } else {
                    $error_message = 'Failed to save template to database';
                    unlink($file_path);
                }
            } else {
                $error_message = 'Failed to upload image';
            }
        }
    }
}

// Handle template deletion
if (isset($_POST['delete_template'])) {
    $template_id = intval($_POST['template_id']);
    
    // Get file path before deleting
    $stmt = $conn->prepare("SELECT file_path FROM cover_art_templates WHERE id = ?");
    $stmt->bind_param("i", $template_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $template = $result->fetch_assoc();
    
    if ($template) {
        // Delete from database
        $stmt = $conn->prepare("DELETE FROM cover_art_templates WHERE id = ?");
        $stmt->bind_param("i", $template_id);
        
        if ($stmt->execute()) {
            // Delete file
            $full_path = '/www/wwwroot/web' . $template['file_path'];
            if (file_exists($full_path)) {
                unlink($full_path);
            }
            $success_message = 'Template deleted successfully!';
        } else {
            $error_message = 'Failed to delete template';
        }
    }
}

// Handle toggle active status
if (isset($_POST['toggle_status'])) {
    $template_id = intval($_POST['template_id']);
    $is_active = intval($_POST['is_active']);
    $new_status = $is_active ? 0 : 1;
    
    $stmt = $conn->prepare("UPDATE cover_art_templates SET is_active = ? WHERE id = ?");
    $stmt->bind_param("ii", $new_status, $template_id);
    
    if ($stmt->execute()) {
        $success_message = 'Template status updated!';
    } else {
        $error_message = 'Failed to update template status';
    }
}

// Get all templates
$templates = [];
$result = $conn->query("SELECT * FROM cover_art_templates ORDER BY created_at DESC");
while ($row = $result->fetch_assoc()) {
    $templates[] = $row;
}

// Get categories
$categories = ['general', 'abstract', 'nature', 'urban', 'minimal', 'vibrant', 'dark'];
$text_positions = ['top' => 'Top Center', 'top-left' => 'Top Left', 'top-right' => 'Top Right', 'center' => 'Center', 'bottom' => 'Bottom Center', 'bottom-left' => 'Bottom Left', 'bottom-right' => 'Bottom Right'];
$text_styles = ['modern' => 'Modern Bold', 'elegant' => 'Elegant Script', 'minimal' => 'Minimal Clean', 'grunge' => 'Grunge/Rock', 'neon' => 'Neon Glow', 'retro' => 'Retro Vintage'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cover Art Templates - Admin Panel</title>
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
        .btn-secondary:hover {
            background: rgba(255,255,255,0.15);
        }
        .btn-success {
            background: linear-gradient(135deg, #00d4aa, #00c853);
            color: #000;
        }
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-success {
            background: rgba(0, 200, 83, 0.2);
            border: 1px solid #00c853;
            color: #00c853;
        }
        .alert-error {
            background: rgba(255, 82, 82, 0.2);
            border: 1px solid #ff5252;
            color: #ff5252;
        }
        
        /* Upload Section */
        .upload-section {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
        }
        .upload-section h2 {
            font-size: 18px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .upload-section h2 i { color: #00d4aa; }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            color: rgba(255,255,255,0.8);
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            background: rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 10px;
            color: #fff;
            font-size: 14px;
        }
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #00d4aa;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        @media (max-width: 768px) {
            .form-row { grid-template-columns: 1fr; }
        }
        .file-input-wrapper {
            position: relative;
            border: 2px dashed rgba(255,255,255,0.2);
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        .file-input-wrapper:hover {
            border-color: #00d4aa;
            background: rgba(0, 212, 170, 0.05);
        }
        .file-input-wrapper input[type="file"] {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            opacity: 0;
            cursor: pointer;
        }
        .file-input-wrapper i {
            font-size: 40px;
            color: rgba(255,255,255,0.3);
            margin-bottom: 10px;
        }
        
        /* Templates Grid */
        .templates-section h2 {
            font-size: 18px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .templates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }
        .template-card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 15px;
            overflow: hidden;
            position: relative;
        }
        .template-card.inactive {
            opacity: 0.5;
        }
        .template-image {
            aspect-ratio: 1;
            overflow: hidden;
        }
        .template-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .template-info {
            padding: 15px;
        }
        .template-info h4 {
            font-size: 14px;
            margin-bottom: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .template-info p {
            font-size: 12px;
            color: rgba(255,255,255,0.5);
            margin-bottom: 10px;
        }
        .template-actions {
            display: flex;
            gap: 8px;
        }
        .template-actions form {
            flex: 1;
        }
        .btn-small {
            width: 100%;
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }
        .btn-toggle {
            background: rgba(0, 212, 170, 0.2);
            color: #00d4aa;
        }
        .btn-toggle:hover {
            background: rgba(0, 212, 170, 0.3);
        }
        .btn-delete {
            background: rgba(255, 82, 82, 0.2);
            color: #ff5252;
        }
        .btn-delete:hover {
            background: rgba(255, 82, 82, 0.3);
        }
        .status-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        .status-active {
            background: rgba(0, 200, 83, 0.3);
            color: #00c853;
        }
        .status-inactive {
            background: rgba(255, 255, 255, 0.2);
            color: rgba(255,255,255,0.7);
        }
        .overlay-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            background: rgba(102, 126, 234, 0.3);
            color: #667eea;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: rgba(255,255,255,0.5);
        }
        .empty-state i {
            font-size: 60px;
            margin-bottom: 20px;
            color: rgba(255,255,255,0.2);
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo"><span class="mdi mdi-shield-account"></span></div>
                <h2>Admin Panel</h2>
                <p>HiTune Distribution</p>
            </div>
            <nav class="nav-menu">
                <a href="index.php" class="nav-item"><span class="mdi mdi-view-dashboard"></span>Dashboard</a>
                <a href="users.php" class="nav-item"><span class="mdi mdi-account-group"></span>Users</a>
                <a href="submissions.php" class="nav-item"><span class="mdi mdi-music"></span>Releases</a>
                <a href="cover_art.php" class="nav-item active"><span class="mdi mdi-image"></span>Cover Art</a>
                <a href="manage_artists.php" class="nav-item"><span class="mdi mdi-account-music"></span>Manage Artists</a>
                <a href="user_artists.php" class="nav-item"><span class="mdi mdi-account-question"></span>Artist Requests</a>
                <a href="artist_verifications.php" class="nav-item"><span class="mdi mdi-check-decagram"></span>Artist Verifications</a>
                <a href="revenue.php" class="nav-item"><span class="mdi mdi-currency-usd"></span>Revenue</a>
                <a href="payouts.php" class="nav-item"><span class="mdi mdi-cash-multiple"></span>Payouts</a>
                <a href="payments.php" class="nav-item"><span class="mdi mdi-credit-card"></span>Payments</a>
                <a href="settings.php" class="nav-item"><span class="mdi mdi-cog"></span>Settings</a>
                <a href="logout.php" class="nav-item"><span class="mdi mdi-logout"></span>Logout</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="header">
                <h1>Cover Art Templates</h1>
                <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
            
            <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="mdi mdi-check-circle"></i>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="mdi mdi-alert-circle"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php endif; ?>
            
            <!-- Upload Section -->
            <div class="upload-section">
                <h2><i class="mdi mdi-cloud-upload"></i>Upload New Template</h2>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Template Name</label>
                            <input type="text" name="template_name" placeholder="e.g., Abstract Waves" required>
                        </div>
                        <div class="form-group">
                            <label>Category</label>
                            <select name="category" required>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat; ?>"><?php echo ucfirst($cat); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Template Image</label>
                        <div class="file-input-wrapper">
                            <input type="file" name="template_image" accept="image/jpeg,image/png,image/jpg" required>
                            <i class="mdi mdi-image"></i>
                            <p>Click or drag to upload image</p>
                            <small>JPG or PNG, Max 10MB, 3000x3000px recommended</small>
                        </div>
                    </div>
                    
                    <!-- Text Overlay Options -->
                    <div class="form-group" style="margin-top: 25px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1);">
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                            <input type="checkbox" name="has_text_overlay" value="1" style="width: auto;">
                            <span><i class="mdi mdi-format-text" style="color: #00d4aa;"></i> Enable Text Overlay (Auto-show Title & Artist Name)</span>
                        </label>
                    </div>
                    
                    <div id="textOverlayOptions" style="display: none; margin-top: 15px;">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Text Position</label>
                                <select name="text_position">
                                    <?php foreach ($text_positions as $key => $label): ?>
                                    <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Text Style</label>
                                <select name="text_style">
                                    <?php foreach ($text_styles as $key => $label): ?>
                                    <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Text Color</label>
                            <input type="color" name="text_color" value="#ffffff" style="width: 60px; height: 40px; padding: 0; border: none; cursor: pointer;">
                        </div>
                    </div>
                    
                    <script>
                        document.querySelector('input[name="has_text_overlay"]').addEventListener('change', function() {
                            document.getElementById('textOverlayOptions').style.display = this.checked ? 'block' : 'none';
                        });
                    </script>
                    
                    <button type="submit" name="upload_template" class="btn btn-success">
                        <i class="mdi mdi-upload"></i>Upload Template
                    </button>
                </form>
            </div>
            
            <!-- Templates Grid -->
            <div class="templates-section">
                <h2><i class="mdi mdi-image-multiple"></i>All Templates (<?php echo count($templates); ?>)</h2>
                
                <?php if (empty($templates)): ?>
                <div class="empty-state">
                    <i class="mdi mdi-image-off"></i>
                    <p>No templates uploaded yet</p>
                </div>
                <?php else: ?>
                <div class="templates-grid">
                    <?php foreach ($templates as $template): 
                    $hasOverlay = !empty($template['has_text_overlay']);
                ?>
                    <div class="template-card <?php echo $template['is_active'] ? '' : 'inactive'; ?>">
                        <span class="status-badge <?php echo $template['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                            <?php echo $template['is_active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                        <?php if ($hasOverlay): ?>
                        <span class="overlay-badge">
                            <i class="mdi mdi-format-text"></i> Text
                        </span>
                        <?php endif; ?>
                        <div class="template-image">
                            <img src="<?php echo htmlspecialchars($template['file_path']); ?>" alt="<?php echo htmlspecialchars($template['name']); ?>">
                        </div>
                        <div class="template-info">
                            <h4><?php echo htmlspecialchars($template['name']); ?></h4>
                            <p><?php echo ucfirst($template['category']); ?> • <?php echo date('M j, Y', strtotime($template['created_at'])); ?><?php echo $hasOverlay ? ' • <span style="color: #00d4aa;"><i class="mdi mdi-format-text"></i> ' . ucfirst($template['text_style']) . ' Style</span>' : ''; ?></p>
                            <div class="template-actions">
                                <form method="POST">
                                    <input type="hidden" name="template_id" value="<?php echo $template['id']; ?>">
                                    <input type="hidden" name="is_active" value="<?php echo $template['is_active']; ?>">
                                    <button type="submit" name="toggle_status" class="btn-small btn-toggle">
                                        <i class="mdi mdi-toggle-switch"></i>
                                        <?php echo $template['is_active'] ? 'Disable' : 'Enable'; ?>
                                    </button>
                                </form>
                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this template?');">
                                    <input type="hidden" name="template_id" value="<?php echo $template['id']; ?>">
                                    <button type="submit" name="delete_template" class="btn-small btn-delete">
                                        <i class="mdi mdi-delete"></i>Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
