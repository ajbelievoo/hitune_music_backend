<?php
/**
 * HiTune Music Distribution - Multi-Step Release Creation
 * Step 4: Artwork Upload
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$user = getCurrentUser();
$release_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Verify release exists
if ($release_id === 0) {
    header('Location: /index.php?q=releases');
    exit;
}

$stmt = $conn->prepare("SELECT * FROM releases WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $release_id, $user['id']);
$stmt->execute();
$result = $stmt->get_result();
$release = $result->fetch_assoc();

if (!$release) {
    header('Location: /index.php?q=releases');
    exit;
}

// Check if can edit
if ($release['status'] !== 'draft' && $release['status'] !== 'rejected') {
    header("Location: /index.php?q=release-view&id=$release_id");
    exit;
}

// Handle template selection
if (isset($_POST['use_template']) && isset($_POST['template_id'])) {
    $template_id = intval($_POST['template_id']);
    $stmt = $conn->prepare("SELECT * FROM cover_art_templates WHERE id = ? AND is_active = 1");
    $stmt->bind_param("i", $template_id);
    $stmt->execute();
    $template = $stmt->get_result()->fetch_assoc();
    
    if ($template) {
        // Copy template to release artwork
        $source_path = '/www/wwwroot/web' . $template['file_path'];
        $new_file_name = 'release_' . $release_id . '_template_' . time() . '.jpg';
        $dest_path = '/www/wwwroot/web/uploads/artwork/' . $new_file_name;
        
        if (copy($source_path, $dest_path)) {
            $relative_path = '/uploads/artwork/' . $new_file_name;
            $stmt = $conn->prepare("UPDATE releases SET cover_art = ?, cover_art_path = ? WHERE id = ?");
            $stmt->bind_param("ssi", $new_file_name, $relative_path, $release_id);
            $stmt->execute();
            
            header("Location: /index.php?q=release-create&id=$release_id&step=4");
            exit;
        }
    }
}

// Get all active admin templates
$admin_templates = [];
$result = $conn->query("SELECT * FROM cover_art_templates WHERE is_active = 1 ORDER BY category, created_at DESC");
while ($row = $result->fetch_assoc()) {
    $admin_templates[] = $row;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';
    
    // Handle file upload if provided
    error_log("DEBUG UPLOAD: FILES data=" . json_encode($_FILES['artwork'] ?? 'not set'));
    if (isset($_FILES['artwork']) && $_FILES['artwork']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '/www/wwwroot/web/uploads/artwork/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_name = 'release_' . $release_id . '_' . time() . '.jpg';
        $file_path = $upload_dir . $file_name;
        
        error_log("DEBUG UPLOAD: Moving file from " . $_FILES['artwork']['tmp_name'] . " to " . $file_path);
        if (move_uploaded_file($_FILES['artwork']['tmp_name'], $file_path)) {
            $relative_path = '/uploads/artwork/' . $file_name;
            $stmt = $conn->prepare("UPDATE releases SET cover_art = ?, cover_art_path = ? WHERE id = ?");
            $stmt->bind_param("ssi", $file_name, $relative_path, $release_id);
            $result = $stmt->execute();
            error_log("DEBUG UPLOAD: Saved to DB result=" . ($result ? 'success' : 'failed'));
        } else {
            error_log("DEBUG UPLOAD: move_uploaded_file FAILED");
        }
    } elseif (isset($_FILES['artwork'])) {
        error_log("DEBUG UPLOAD: Upload error code=" . $_FILES['artwork']['error']);
    }
    
    // Update progress and status
    if ($action === 'submit_release') {
        // Get current status for logging
        $old_status = $release['status'];

        // Freemium gate at the real "release" moment — a draft created
        // while quota remained must not bypass the monthly free limit.
        // Resubmission of a rejected release does not consume quota
        // (rejected rows are excluded from the count).
        if ($old_status !== 'rejected' && function_exists('freeReleaseQuota')) {
            $__q = freeReleaseQuota();
            if (!$__q['subscribed'] && $__q['enabled'] && $__q['remaining'] <= 0) {
                header("Location: /index.php?q=release-create&quota=exceeded");
                exit;
            }
        }
        
        // DEBUG: Log current state before update
        error_log("DEBUG SUBMIT: Release $release_id, old_status=$old_status, cover_art=" . ($release['cover_art'] ?? 'NULL') . ", path=" . ($release['cover_art_path'] ?? 'NULL'));
        
        // Submit for admin approval (handles both new submissions and resubmissions)
        // submitted_at is only set on first submission, not on resubmission
        $stmt = $conn->prepare("UPDATE releases SET step_completed = 4, progress_percent = 100, status = 'submitted', submitted_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $release_id);
        $result = $stmt->execute();
        
        // DEBUG: Log update result
        error_log("DEBUG SUBMIT: Update result=" . ($result ? 'success' : 'failed') . ", affected=" . $stmt->affected_rows);
        
        // Log activity with correct old status
        $log_action = ($old_status === 'rejected') ? 'Release Resubmitted' : 'Release Submitted';
        $stmt = $conn->prepare("INSERT INTO activity_log (release_id, user_id, action, old_status, new_status, created_by) VALUES (?, ?, ?, ?, 'submitted', 'user')");
        $stmt->bind_param("iiss", $release_id, $user['id'], $log_action, $old_status);
        $stmt->execute();
        
        header("Location: /index.php?q=releases");
        exit;
    } else {
        $stmt = $conn->prepare("UPDATE releases SET step_completed = GREATEST(step_completed, 4), progress_percent = GREATEST(progress_percent, 100) WHERE id = ?");
        $stmt->bind_param("i", $release_id);
        $stmt->execute();
        header("Location: /index.php?q=releases");
        exit;
    }
}

$pageTitle = 'Create Release - Step 4: Artwork';
include __DIR__ . '/../includes/header_premium.php';
?>

<style>
    .release-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 100px 20px 60px;
    }
    .progress-header {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        border-radius: 15px;
        padding: 25px 30px;
        margin-bottom: 30px;
        border: 1px solid rgba(255,255,255,0.1);
    }
    .progress-header h1 {
        font-size: 24px;
        font-weight: 700;
        margin-bottom: 20px;
    }
    .progress-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: relative;
    }
    .progress-bar::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 0;
        right: 0;
        height: 2px;
        background: rgba(255,255,255,0.1);
        transform: translateY(-50%);
        z-index: 0;
    }
    .progress-line {
        position: absolute;
        top: 50%;
        left: 0;
        height: 2px;
        background: linear-gradient(135deg, #00d4aa, #00c853);
        transform: translateY(-50%);
        z-index: 0;
        transition: width 0.3s;
    }
    .progress-step {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        position: relative;
        z-index: 1;
    }
    .step-number {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 14px;
        background: rgba(255,255,255,0.1);
        border: 2px solid rgba(255,255,255,0.2);
        transition: all 0.3s;
    }
    .progress-step.active .step-number {
        background: linear-gradient(135deg, #00d4aa, #00c853);
        border-color: #00d4aa;
        color: #000;
    }
    .progress-step.completed .step-number {
        background: #00c853;
        border-color: #00c853;
        color: #000;
    }
    .step-label {
        font-size: 12px;
        color: rgba(255,255,255,0.5);
        font-weight: 500;
    }
    .progress-step.active .step-label {
        color: #00d4aa;
    }
    
    .form-card {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 20px;
        padding: 40px;
    }
    .form-section-title {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .form-section-title i {
        color: #00d4aa;
    }
    
    .guidelines-box {
        background: rgba(0,0,0,0.2);
        border-radius: 12px;
        padding: 25px;
        margin-bottom: 30px;
    }
    .guidelines-box h4 {
        font-size: 14px;
        margin-bottom: 15px;
        color: rgba(255,255,255,0.8);
    }
    .guidelines-box ul {
        list-style: none;
        font-size: 13px;
        color: rgba(255,255,255,0.6);
    }
    .guidelines-box li {
        padding: 6px 0;
        padding-left: 25px;
        position: relative;
    }
    .guidelines-box li::before {
        content: '✓';
        position: absolute;
        left: 0;
        color: #00d4aa;
    }
    .guidelines-box .warning {
        color: #00b7ff;
    }
    .guidelines-box .warning::before {
        content: '✗';
        color: #00b7ff;
    }
    
    .artwork-upload {
        border: 2px dashed rgba(255,255,255,0.3);
        border-radius: 20px;
        padding: 60px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
        margin-bottom: 30px;
        position: relative;
        overflow: hidden;
    }
    .artwork-upload:hover {
        border-color: #00d4aa;
        background: rgba(0, 212, 170, 0.05);
    }
    .artwork-upload.has-image {
        padding: 0;
        border-style: solid;
        border-color: #00d4aa;
    }
    .artwork-upload i {
        font-size: 60px;
        color: rgba(255,255,255,0.3);
        margin-bottom: 20px;
    }
    .artwork-upload h3 {
        font-size: 18px;
        margin-bottom: 10px;
    }
    .artwork-upload p {
        color: rgba(255,255,255,0.5);
        font-size: 14px;
        margin-bottom: 5px;
    }
    .artwork-upload input {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
    }
    .preview-image {
        max-width: 100%;
        max-height: 400px;
        border-radius: 12px;
    }
    .change-image-btn {
        position: absolute;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(0,0,0,0.8);
        color: #fff;
        padding: 10px 25px;
        border-radius: 25px;
        font-size: 14px;
        pointer-events: none;
    }
    
    /* Templates */
    .templates-section {
        margin-top: 40px;
    }
    .templates-section h3 {
        font-size: 16px;
        margin-bottom: 20px;
        color: rgba(255,255,255,0.8);
    }
    .templates-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 15px;
    }
    @media (max-width: 768px) {
        .templates-grid { grid-template-columns: repeat(2, 1fr); }
    }
    .template-card {
        aspect-ratio: 1;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s;
        position: relative;
        overflow: hidden;
        border: 3px solid transparent;
    }
    .template-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        border-color: #00d4aa;
    }
    .template-card.selected {
        border-color: #00d4aa;
        box-shadow: 0 0 0 3px rgba(0, 212, 170, 0.3);
    }
    .template-card img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .template-card span {
        font-size: 12px;
        text-align: center;
        padding: 10px;
        opacity: 0.7;
    }
    .template-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.6);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: all 0.3s;
    }
    .template-card:hover .template-overlay {
        opacity: 1;
    }
    .template-overlay button {
        background: linear-gradient(135deg, #00d4aa, #00c853);
        color: #000;
        border: none;
        padding: 10px 20px;
        border-radius: 25px;
        font-weight: 600;
        cursor: pointer;
    }
    .template-category {
        position: absolute;
        top: 8px;
        left: 8px;
        background: rgba(0,0,0,0.6);
        padding: 4px 10px;
        border-radius: 15px;
        font-size: 10px;
        text-transform: uppercase;
    }
    
    /* Text Overlay on Templates */
    .template-text-overlay {
        position: absolute;
        left: 0;
        right: 0;
        padding: 15px;
        text-align: center;
        pointer-events: none;
        z-index: 5;
    }
    .template-text-overlay.top { top: 20px; }
    .template-text-overlay.top-left { top: 20px; left: 15px; right: auto; text-align: left; }
    .template-text-overlay.top-right { top: 20px; left: auto; right: 15px; text-align: right; }
    .template-text-overlay.center { 
        top: 50%; 
        transform: translateY(-50%);
        background: rgba(0,0,0,0.4);
        padding: 20px;
    }
    .template-text-overlay.bottom { bottom: 20px; }
    .template-text-overlay.bottom-left { bottom: 20px; left: 15px; right: auto; text-align: left; }
    .template-text-overlay.bottom-right { bottom: 20px; left: auto; right: 15px; text-align: right; }
    
    .template-text-overlay .overlay-title {
        font-size: 16px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.8);
        margin-bottom: 4px;
        line-height: 1.2;
    }
    .template-text-overlay .overlay-artist {
        font-size: 12px;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 2px;
        text-shadow: 1px 1px 3px rgba(0,0,0,0.8);
        opacity: 0.9;
    }
    
    /* Text Styles */
    .template-text-overlay.style-modern .overlay-title {
        font-family: 'Segoe UI', system-ui, sans-serif;
        font-weight: 900;
    }
    .template-text-overlay.style-elegant .overlay-title {
        font-family: Georgia, serif;
        font-style: italic;
        font-weight: 400;
        text-transform: capitalize;
    }
    .template-text-overlay.style-elegant .overlay-artist {
        font-style: italic;
        text-transform: capitalize;
    }
    .template-text-overlay.style-minimal .overlay-title {
        font-weight: 300;
        letter-spacing: 4px;
    }
    .template-text-overlay.style-minimal .overlay-artist {
        letter-spacing: 3px;
        font-weight: 300;
    }
    .template-text-overlay.style-grunge .overlay-title {
        font-weight: 900;
        letter-spacing: -1px;
        text-shadow: 3px 3px 0 rgba(0,0,0,0.5);
    }
    .template-text-overlay.style-neon .overlay-title {
        font-weight: 700;
        text-shadow: 0 0 10px currentColor, 0 0 20px currentColor, 2px 2px 4px rgba(0,0,0,0.8);
    }
    .template-text-overlay.style-neon .overlay-artist {
        text-shadow: 0 0 5px currentColor, 2px 2px 4px rgba(0,0,0,0.8);
    }
    .template-text-overlay.style-retro .overlay-title {
        font-family: 'Courier New', monospace;
        letter-spacing: 3px;
        text-shadow: 2px 2px 0 rgba(0,0,0,0.5);
    }
    
    /* AI Generation Section */
    .ai-section {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
        border: 1px solid rgba(102, 126, 234, 0.3);
        border-radius: 20px;
        padding: 30px;
        margin-top: 30px;
    }
    .ai-section h3 {
        font-size: 18px;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .ai-section h3 i {
        color: #667eea;
    }
    .ai-section p {
        color: rgba(255,255,255,0.6);
        font-size: 14px;
        margin-bottom: 20px;
    }
    .ai-input-group {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
    }
    .ai-input-group input {
        flex: 1;
        padding: 14px 18px;
        background: rgba(0,0,0,0.3);
        border: 1px solid rgba(255,255,255,0.15);
        border-radius: 10px;
        color: #fff;
        font-size: 15px;
    }
    .ai-input-group input:focus {
        outline: none;
        border-color: #667eea;
    }
    .ai-input-group button {
        padding: 14px 25px;
        background: linear-gradient(135deg, #667eea, #764ba2);
        border: none;
        border-radius: 10px;
        color: #fff;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .ai-input-group button:hover {
        opacity: 0.9;
    }
    .ai-input-group button:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    .ai-examples {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }
    .ai-example-btn {
        padding: 8px 15px;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 20px;
        color: rgba(255,255,255,0.7);
        font-size: 12px;
        cursor: pointer;
        transition: all 0.3s;
    }
    .ai-example-btn:hover {
        background: rgba(102, 126, 234, 0.2);
        border-color: #667eea;
        color: #fff;
    }
    .ai-loading {
        display: none;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 30px;
        color: rgba(255,255,255,0.7);
    }
    .ai-loading.active {
        display: flex;
    }
    .ai-loading i {
        font-size: 30px;
        animation: spin 1s linear infinite;
    }
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    .generated-preview {
        margin-top: 20px;
        text-align: center;
    }
    .generated-preview img {
        max-width: 300px;
        max-height: 300px;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    }
    .generated-preview .btn-use-ai {
        margin-top: 15px;
        padding: 12px 30px;
        background: linear-gradient(135deg, #00d4aa, #00c853);
        border: none;
        border-radius: 10px;
        color: #000;
        font-weight: 600;
        cursor: pointer;
    }
    
    /* Form actions */
    .form-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 40px;
        padding-top: 30px;
        border-top: 1px solid rgba(255,255,255,0.1);
    }
    .btn {
        padding: 14px 30px;
        border-radius: 10px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        border: none;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .btn-secondary {
        background: rgba(255,255,255,0.1);
        color: #fff;
    }
    .btn-secondary:hover {
        background: rgba(255,255,255,0.15);
    }
    .btn-success {
        background: linear-gradient(135deg, #00d4aa, #00c853);
        color: #000;
    }
    .btn-success:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 30px rgba(0, 200, 83, 0.3);
    }
    
    .submit-section {
        background: linear-gradient(135deg, rgba(0, 212, 170, 0.1), rgba(0, 200, 83, 0.1));
        border: 1px solid rgba(0, 212, 170, 0.3);
        border-radius: 15px;
        padding: 25px;
        margin-top: 30px;
        text-align: center;
    }
    .submit-section h3 {
        font-size: 18px;
        margin-bottom: 10px;
    }
    .submit-section p {
        color: rgba(255,255,255,0.6);
        font-size: 14px;
        margin-bottom: 20px;
    }
</style>

<div class="release-container">
    <!-- Progress Header -->
    <div class="progress-header">
        <h1>Upload Cover Art</h1>
        <div class="progress-bar">
            <div class="progress-line" style="width: 87.5%;"></div>
            <div class="progress-step completed">
                <div class="step-number"><i class="mdi mdi-check"></i></div>
                <span class="step-label">Release Details</span>
            </div>
            <div class="progress-step completed">
                <div class="step-number"><i class="mdi mdi-check"></i></div>
                <span class="step-label">Stores</span>
            </div>
            <div class="progress-step completed">
                <div class="step-number"><i class="mdi mdi-check"></i></div>
                <span class="step-label">Tracks</span>
            </div>
            <div class="progress-step active">
                <div class="step-number">4</div>
                <span class="step-label">Artwork</span>
            </div>
        </div>
    </div>

    <!-- Separate forms for template and AI (outside main form to avoid nesting) -->
    <form method="POST" id="templateForm" style="display: none;">
        <input type="hidden" name="use_template" value="1">
        <input type="hidden" name="template_id" id="selectedTemplateId">
    </form>
    
    <form method="POST" id="aiForm" style="display: none;" enctype="multipart/form-data">
        <input type="file" name="artwork" id="aiArtworkInput">
    </form>
    
    <form method="POST" enctype="multipart/form-data" class="form-card" id="releaseForm">
        <input type="hidden" name="action" id="formAction" value="save">
        
        <!-- Guidelines -->
        <div class="guidelines-box">
            <h4><i class="mdi mdi-information" style="color: #00d4aa; margin-right: 8px;"></i>Artwork Guidelines</h4>
            <ul>
                <li>JPG or PNG image file smaller than 10MB</li>
                <li>File must be in RGB mode, even if your image is black and white</li>
                <li>At least 1600 x 1600 pixels in size</li>
                <li>No trademarked text. Only the release title and artist names are acceptable</li>
                <li>No blur lines, pixelation, or white space</li>
                <li class="warning">No social media links, contact information, store names or logos, pricing information, release dates, "New" stickers, etc.</li>
            </ul>
        </div>
        
        <!-- Upload Box -->
        <div class="form-section-title">
            <i class="mdi mdi-cloud-upload"></i>
            Upload Artwork
        </div>
        
        <div class="artwork-upload <?php echo $release && $release['cover_art'] ? 'has-image' : ''; ?>" id="uploadBox">
            <?php if ($release && $release['cover_art']): ?>
                <img src="<?php echo htmlspecialchars($release['cover_art_path']); ?>" alt="Cover Art" class="preview-image" id="previewImage">
                <div class="change-image-btn" onclick="document.getElementById('artworkInput').click()">Click to change</div>
            <?php else: ?>
                <i class="mdi mdi-image"></i>
                <h3>Click or Drag to Upload</h3>
                <p>Upload your artwork or design one using our creator tool</p>
                <p><small>3000 x 3000px recommended • JPG or PNG • Max 10MB</small></p>
            <?php endif; ?>
            <input type="file" name="artwork" id="artworkInput" accept="image/jpeg,image/png" onchange="previewFile(this)">
        </div>
        
        <!-- Admin Templates Gallery -->
        <?php if (!empty($admin_templates)): ?>
        <div class="templates-section">
            <h3><i class="mdi mdi-image-multiple" style="color: #00d4aa; margin-right: 8px;"></i>Choose from Admin Gallery:</h3>
            <div class="templates-grid">
                <?php foreach ($admin_templates as $template):
                    // Get text overlay settings
                    $hasOverlay = !empty($template['has_text_overlay']);
                    $textPosition = $template['text_position'] ?? 'bottom';
                    $textStyle = $template['text_style'] ?? 'modern';
                    $textColor = $template['text_color'] ?? '#ffffff';
                    // Sample text for preview
                    $sampleTitle = $release['title'] ?: 'SONG TITLE';
                    $sampleArtist = $release['primary_artist'] ?: 'ARTIST NAME';
                ?>
                <div class="template-card" onclick="selectTemplate(<?php echo $template['id']; ?>, '<?php echo htmlspecialchars($template['file_path']); ?>')">
                    <span class="template-category"><?php echo ucfirst($template['category']); ?></span>
                    <img src="<?php echo htmlspecialchars($template['file_path']); ?>" alt="<?php echo htmlspecialchars($template['name']); ?>">
                    <?php if ($hasOverlay): ?>
                    <div class="template-text-overlay <?php echo $textPosition; ?> style-<?php echo $textStyle; ?>" style="color: <?php echo htmlspecialchars($textColor); ?>">
                        <div class="overlay-title"><?php echo htmlspecialchars(strtoupper($sampleTitle)); ?></div>
                        <div class="overlay-artist"><?php echo htmlspecialchars(strtoupper($sampleArtist)); ?></div>
                    </div>
                    <?php endif; ?>
                    <div class="template-overlay">
                        <button type="button">Use Template</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- AI Cover Art Generator -->
        <div class="ai-section">
            <h3><i class="mdi mdi-robot"></i>Generate AI Cover Art</h3>
            <p>Describe your vision and let AI create a unique cover art for your release.</p>
            <div class="ai-input-group">
                <input type="text" id="aiPrompt" placeholder="e.g., Abstract colorful waves with neon lights, dark background, modern style...">
                <button type="button" id="generateBtn" onclick="generateAICover()">
                    <i class="mdi mdi-magic-staff"></i>
                    Generate
                </button>
            </div>
            <div class="ai-examples">
                <span style="font-size: 12px; color: rgba(255,255,255,0.5); margin-right: 10px;">Try:</span>
                <button type="button" class="ai-example-btn" onclick="setPrompt('Abstract geometric shapes with gradient colors')">Abstract geometric</button>
                <button type="button" class="ai-example-btn" onclick="setPrompt('Cosmic galaxy with stars and nebula')">Cosmic galaxy</button>
                <button type="button" class="ai-example-btn" onclick="setPrompt('Minimalist black and white waves')">Minimalist waves</button>
                <button type="button" class="ai-example-btn" onclick="setPrompt('Vibrant tropical sunset with palm trees')">Tropical sunset</button>
                <button type="button" class="ai-example-btn" onclick="setPrompt('Cyberpunk city with neon lights')">Cyberpunk city</button>
            </div>
            <div class="ai-loading" id="aiLoading">
                <i class="mdi mdi-loading"></i>
                <span>Creating your artwork... This may take a moment.</span>
            </div>
            <div class="generated-preview" id="generatedPreview" style="display: none;">
                <img id="generatedImage" src="" alt="AI Generated Cover">
                <br>
                <button type="button" class="btn-use-ai" onclick="useGeneratedCover()">
                    <i class="mdi mdi-check"></i> Use This Cover Art
                </button>
            </div>
        </div>
        
        <!-- Cover Art Creator Button -->
        <div class="creator-section" style="text-align: center; margin-top: 30px;">
            <button type="button" class="btn btn-creator" onclick="openCoverCreator()">
                <i class="mdi mdi-brush"></i>
                Cover Art Creator
            </button>
            <p style="color: rgba(255,255,255,0.5); font-size: 14px; margin-top: 10px;">Create custom cover art with text, fonts & styles</p>
        </div>
        
        <!-- Submit Section -->
        <div class="submit-section">
            <?php if ($release['status'] === 'rejected'): ?>
            <h3><i class="mdi mdi-refresh" style="color: #ff5252;"></i> Resubmit Your Release</h3>
            <p style="color: #00b7ff;"><i class="mdi mdi-alert-circle"></i> Your release was rejected. Please review the admin feedback, make necessary changes, and resubmit.</p>
            <?php if ($release['admin_notes']): ?>
            <div style="background: rgba(255, 82, 82, 0.1); border: 1px solid rgba(255, 82, 82, 0.3); border-radius: 10px; padding: 15px; margin-top: 15px; text-align: left;">
                <strong style="color: #ff5252;"><i class="mdi mdi-message-text"></i> Admin Feedback:</strong>
                <p style="margin-top: 8px; color: rgba(255,255,255,0.8);"><?php echo nl2br(htmlspecialchars($release['admin_notes'])); ?></p>
            </div>
            <?php endif; ?>
            <?php else: ?>
            <h3><i class="mdi mdi-check-circle" style="color: #00d4aa;"></i> Ready to Submit?</h3>
            <p>Once you submit, your release will be sent to our team for review. You'll be able to track the status on your releases page.</p>
            <?php endif; ?>
        </div>

        <!-- Form Actions -->
        <div class="form-actions">
            <a href="/index.php?q=release-create&id=<?php echo $release_id; ?>&step=3" class="btn btn-secondary">
                <i class="mdi mdi-arrow-left"></i>
                Back
            </a>
            <div style="display: flex; gap: 15px;">
                <button type="button" class="btn btn-secondary" id="saveDraftBtn" onclick="handleSaveDraft(event)">
                    <i class="mdi mdi-content-save"></i>
                    <span>Save Draft</span>
                </button>
                <button type="button" class="btn btn-success" id="submitBtn" onclick="handleSubmit(event)">
                    <i class="mdi mdi-send"></i>
                    <span><?php echo $release['status'] === 'rejected' ? 'Resubmit Release' : 'Submit Release'; ?></span>
                </button>
            </div>
        </div>
    </form>
</div>

<script>
    let generatedImageData = null;
    
    function previewFile(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const uploadBox = document.getElementById('uploadBox');
                uploadBox.classList.add('has-image');
                
                // Remove any existing preview image and change button
                const oldPreview = document.getElementById('previewImage');
                const oldBtn = uploadBox.querySelector('.change-image-btn');
                if (oldPreview) oldPreview.remove();
                if (oldBtn) oldBtn.remove();
                
                // Hide the upload icon/text but KEEP the file input
                const icons = uploadBox.querySelectorAll('i, h3, p');
                icons.forEach(el => el.style.display = 'none');
                
                // Add preview image and change button
                const previewHtml = `
                    <img src="${e.target.result}" alt="Cover Art Preview" class="preview-image" id="previewImage">
                    <div class="change-image-btn" onclick="document.getElementById('artworkInput').click()">Click to change</div>
                `;
                uploadBox.insertAdjacentHTML('afterbegin', previewHtml);
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    function selectTemplate(templateId, filePath) {
        if (confirm('Use this template as your cover art?')) {
            document.getElementById('selectedTemplateId').value = templateId;
            document.getElementById('templateForm').submit();
        }
    }
    
    function setPrompt(prompt) {
        document.getElementById('aiPrompt').value = prompt;
    }
    
    async function generateAICover() {
        const prompt = document.getElementById('aiPrompt').value.trim();
        const btn = document.getElementById('generateBtn');
        const loading = document.getElementById('aiLoading');
        const preview = document.getElementById('generatedPreview');
        
        if (!prompt) {
            alert('Please enter a description for your cover art');
            return;
        }
        
        // Show loading
        btn.disabled = true;
        loading.classList.add('active');
        preview.style.display = 'none';
        
        try {
            // Note: This uses Pollinations.ai free image generation API
            // In production, you might want to use Gemini API or other AI services
            const enhancedPrompt = `Album cover art: ${prompt}. Professional music album artwork, square format, high quality, suitable for streaming platforms.`;
            const imageUrl = `https://image.pollinations.ai/prompt/${encodeURIComponent(enhancedPrompt)}?width=1024&height=1024&nologo=true&seed=${Math.floor(Math.random() * 1000000)}`;
            
            // Load and display the generated image
            const img = new Image();
            img.onload = function() {
                document.getElementById('generatedImage').src = imageUrl;
                generatedImageData = imageUrl;
                preview.style.display = 'block';
                loading.classList.remove('active');
                btn.disabled = false;
            };
            img.onerror = function() {
                alert('Failed to generate image. Please try again.');
                loading.classList.remove('active');
                btn.disabled = false;
            };
            img.src = imageUrl;
            
        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
            loading.classList.remove('active');
            btn.disabled = false;
        }
    }
    
    async function useGeneratedCover() {
        if (!generatedImageData) return;
        
        try {
            // Fetch the image and convert to blob
            const response = await fetch(generatedImageData);
            const blob = await response.blob();
            
            // Create a File object
            const file = new File([blob], 'ai_generated_cover.jpg', { type: 'image/jpeg' });
            
            // Create a DataTransfer object and add the file
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            
            // Set the file to the file input
            const fileInput = document.getElementById('artworkInput');
            fileInput.files = dataTransfer.files;
            
            // Trigger the preview
            previewFile(fileInput);
            
            // Scroll to upload section
            document.getElementById('uploadBox').scrollIntoView({ behavior: 'smooth' });
            
            alert('AI generated cover art has been selected! Click "Submit Release" to save it.');
            
        } catch (error) {
            console.error('Error:', error);
            alert('Failed to use generated cover. Please try again.');
        }
    }
    
    // Cover Art Creator Functions
    let creatorBackgroundImage = null;
    let currentTemplatePath = '';
    let currentGradient = 'purple-blue';
    let currentFilter = 'none';
    
    const gradientPresets = {
        'purple-blue': ['#667eea', '#764ba2'],
        'dark-night': ['#1a1a2e', '#16213e'],
        'green-nature': ['#11998e', '#38ef7d'],
        'sunset-orange': ['#00b7ff', '#8b5cf6'],
        'pink-rose': ['#f093fb', '#f5576c'],
        'blue-ocean': ['#4facfe', '#00f2fe'],
        'mint-fresh': ['#43e97b', '#38f9d7'],
        'gold-sunset': ['#fa709a', '#fee140'],
        'fire-red': ['#f12711', '#f5af19'],
        'midnight-blue': ['#0f2027', '#203a43', '#2c5364'],
        'berry-purple': ['#8e2de2', '#4a00e0'],
        'coral-pink': ['#ff9a9e', '#fecfef'],
        'aqua-marine': ['#30cfd0', '#330867'],
        'lemon-lime': ['#f6d365', '#fda085'],
        'royal-purple': ['#654ea3', '#eaafc8'],
        'deep-space': ['#000000', '#434343'],
        'cherry-red': ['#eb3349', '#f45c43'],
        'sky-blue': ['#89f7fe', '#66a6ff'],
        'magic-magenta': ['#ee0979', '#ff6a00'],
        'forest-green': ['#134e5e', '#71b280'],
        'peach-orange': ['#ffecd2', '#fcb69f'],
        'lavender': ['#e0c3fc', '#8ec5fc'],
        'dark-velvet': ['#141e30', '#243b55'],
        'tropical': ['#fccb90', '#d57eeb'],
        'electric': ['#00f260', '#0575e6']
    };
    
    function openCoverCreator() {
        document.getElementById('coverCreatorModal').style.display = 'flex';
        // Select first gradient by default
        setTimeout(() => {
            selectGradient('purple-blue');
        }, 100);
    }
    
    function selectGradient(gradientName) {
        currentGradient = gradientName;
        currentTemplatePath = 'gradient://' + gradientName;
        
        // Update active state
        document.querySelectorAll('.creator-thumb').forEach(thumb => {
            thumb.classList.remove('active');
        });
        if (event && event.currentTarget) {
            event.currentTarget.classList.add('active');
        }
        
        updateCreatorPreview();
    }
    
    function setFilter(filterName) {
        currentFilter = filterName;
        
        // Update active filter button
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        if (event && event.currentTarget) {
            event.currentTarget.classList.add('active');
        }
        
        updateCreatorPreview();
    }
    
    function closeCoverCreator() {
        document.getElementById('coverCreatorModal').style.display = 'none';
    }
    
    function selectCreatorTemplate(imagePath, isGradient = false) {
        // Update active state
        document.querySelectorAll('.creator-thumb').forEach(thumb => {
            thumb.classList.remove('active');
        });
        event.currentTarget.classList.add('active');
        
        currentTemplatePath = imagePath;
        
        // Load image
        creatorBackgroundImage = new Image();
        creatorBackgroundImage.crossOrigin = 'anonymous';
        creatorBackgroundImage.onload = function() {
            updateCreatorPreview();
        };
        
        if (isGradient) {
            // For gradient backgrounds, create a canvas gradient
            updateCreatorPreview();
        } else {
            creatorBackgroundImage.src = imagePath;
        }
    }
    
    function updateCreatorPreview() {
        const canvas = document.getElementById('creatorCanvas');
        const ctx = canvas.getContext('2d');
        
        // Clear canvas
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        // Draw background
        if (currentTemplatePath.startsWith('gradient://')) {
            const colors = gradientPresets[currentGradient] || ['#1a1a2e', '#16213e'];
            const gradient = ctx.createLinearGradient(0, 0, canvas.width, canvas.height);
            colors.forEach((color, index) => {
                gradient.addColorStop(index / (colors.length - 1), color);
            });
            ctx.fillStyle = gradient;
            ctx.fillRect(0, 0, canvas.width, canvas.height);
        } else if (creatorBackgroundImage && creatorBackgroundImage.complete) {
            ctx.drawImage(creatorBackgroundImage, 0, 0, canvas.width, canvas.height);
        } else {
            // Default background
            const colors = gradientPresets[currentGradient] || ['#1a1a2e', '#16213e'];
            const gradient = ctx.createLinearGradient(0, 0, canvas.width, canvas.height);
            gradient.addColorStop(0, colors[0]);
            gradient.addColorStop(1, colors[1]);
            ctx.fillStyle = gradient;
            ctx.fillRect(0, 0, canvas.width, canvas.height);
        }
        
        // Apply opacity
        const opacity = document.getElementById('bgOpacity').value / 100;
        if (opacity < 1) {
            ctx.fillStyle = `rgba(0,0,0,${1 - opacity})`;
            ctx.fillRect(0, 0, canvas.width, canvas.height);
        }
        
        // Apply filters
        applyCanvasFilter(ctx, canvas, currentFilter);
        
        // Get text options
        let title = document.getElementById('creatorTitle').value || 'SONG TITLE';
        let artist = document.getElementById('creatorArtist').value || 'ARTIST NAME';
        const position = document.getElementById('creatorPosition').value;
        const alignment = document.getElementById('creatorAlignment').value;
        const style = document.getElementById('creatorStyle').value;
        const transform = document.getElementById('creatorTransform').value;
        const titleColor = document.getElementById('creatorTitleColor').value;
        const artistColor = document.getElementById('creatorArtistColor').value;
        const titleOutline = document.getElementById('creatorTitleOutline').value;
        const artistOutline = document.getElementById('creatorArtistOutline').value;
        const titleSize = parseInt(document.getElementById('creatorTitleSize').value);
        const artistSize = parseInt(document.getElementById('creatorArtistSize').value);
        const letterSpacing = parseInt(document.getElementById('creatorLetterSpacing').value);
        const lineHeight = parseFloat(document.getElementById('creatorLineHeight').value);
        const titleFont = document.getElementById('creatorTitleFont').value;
        const artistFont = document.getElementById('creatorArtistFont').value;
        
        // Apply text transform
        switch(transform) {
            case 'uppercase':
                title = title.toUpperCase();
                artist = artist.toUpperCase();
                break;
            case 'lowercase':
                title = title.toLowerCase();
                artist = artist.toLowerCase();
                break;
            case 'capitalize':
                title = title.replace(/\b\w/g, l => l.toUpperCase());
                artist = artist.replace(/\b\w/g, l => l.toUpperCase());
                break;
        }
        
        // Calculate positions
        let titleX, titleY, artistX, artistY;
        const padding = 60;
        
        // X position based on alignment
        switch(alignment) {
            case 'left':
                titleX = padding;
                artistX = padding;
                ctx.textAlign = 'left';
                break;
            case 'right':
                titleX = canvas.width - padding;
                artistX = canvas.width - padding;
                ctx.textAlign = 'right';
                break;
            case 'center':
            default:
                titleX = canvas.width / 2;
                artistX = canvas.width / 2;
                ctx.textAlign = 'center';
                break;
        }
        
        // Y position based on position
        switch(position) {
            case 'top':
                titleY = 120;
                artistY = titleY + (titleSize * lineHeight);
                break;
            case 'top-left':
                titleY = 120;
                artistY = titleY + (titleSize * lineHeight);
                break;
            case 'top-right':
                titleY = 120;
                artistY = titleY + (titleSize * lineHeight);
                break;
            case 'center':
                titleY = canvas.height / 2 - (artistSize * lineHeight / 2);
                artistY = canvas.height / 2 + (titleSize * lineHeight / 2);
                ctx.fillStyle = 'rgba(0,0,0,0.3)';
                ctx.fillRect(0, canvas.height/2 - 100, canvas.width, 200);
                break;
            case 'center-left':
                titleY = canvas.height / 2 - (artistSize * lineHeight / 2);
                artistY = canvas.height / 2 + (titleSize * lineHeight / 2);
                break;
            case 'center-right':
                titleY = canvas.height / 2 - (artistSize * lineHeight / 2);
                artistY = canvas.height / 2 + (titleSize * lineHeight / 2);
                break;
            case 'bottom-left':
                titleY = canvas.height - 120 - (artistSize * lineHeight);
                artistY = canvas.height - 120;
                break;
            case 'bottom-right':
                titleY = canvas.height - 120 - (artistSize * lineHeight);
                artistY = canvas.height - 120;
                break;
            case 'bottom':
            default:
                titleY = canvas.height - 120 - (artistSize * lineHeight);
                artistY = canvas.height - 120;
                break;
        }
        
        // Build font strings
        let titleWeight = '900';
        let artistWeight = '600';
        
        switch(style) {
            case 'elegant':
                titleWeight = '400';
                artistWeight = '400';
                break;
            case 'minimal':
                titleWeight = '300';
                artistWeight = '300';
                break;
            case 'grunge':
                titleWeight = '900';
                artistWeight = '700';
                break;
        }
        
        const titleFontStr = `${titleWeight} ${titleSize}px ${titleFont}`;
        const artistFontStr = `${artistWeight} ${artistSize}px ${artistFont}`;
        
        // Apply letter spacing by adding spaces
        const applyLetterSpacing = (text, spacing) => {
            if (spacing <= 0) return text;
            return text.split('').join(' '.repeat(spacing / 2));
        };
        
        const spacedTitle = applyLetterSpacing(title, letterSpacing);
        const spacedArtist = applyLetterSpacing(artist, letterSpacing);
        
        // Draw functions
        const drawTextWithOutline = (text, x, y, fillColor, outlineColor, font, isTitle) => {
            ctx.font = font;
            
            // Apply style effects
            switch(style) {
                case 'outline':
                    ctx.strokeStyle = outlineColor;
                    ctx.lineWidth = isTitle ? 3 : 2;
                    ctx.strokeText(text, x, y);
                    break;
                case '3d':
                    ctx.fillStyle = outlineColor;
                    ctx.fillText(text, x + 4, y + 4);
                    break;
                case 'shadow':
                    ctx.shadowColor = outlineColor;
                    ctx.shadowBlur = 15;
                    ctx.shadowOffsetX = 4;
                    ctx.shadowOffsetY = 4;
                    break;
                case 'neon':
                    ctx.shadowColor = fillColor;
                    ctx.shadowBlur = 25;
                    ctx.shadowOffsetX = 0;
                    ctx.shadowOffsetY = 0;
                    break;
                case 'emboss':
                    ctx.fillStyle = outlineColor;
                    ctx.fillText(text, x - 2, y - 2);
                    ctx.shadowColor = 'rgba(0,0,0,0.5)';
                    ctx.shadowBlur = 5;
                    ctx.shadowOffsetX = 2;
                    ctx.shadowOffsetY = 2;
                    break;
                default:
                    ctx.shadowColor = 'rgba(0,0,0,0.8)';
                    ctx.shadowBlur = 8;
                    ctx.shadowOffsetX = 2;
                    ctx.shadowOffsetY = 2;
            }
            
            ctx.fillStyle = fillColor;
            ctx.fillText(text, x, y);
            
            // Reset shadow
            ctx.shadowColor = 'transparent';
            ctx.shadowBlur = 0;
            ctx.shadowOffsetX = 0;
            ctx.shadowOffsetY = 0;
        };
        
        // Draw Title
        drawTextWithOutline(spacedTitle, titleX, titleY, titleColor, titleOutline, titleFontStr, true);
        
        // Draw Artist
        drawTextWithOutline(spacedArtist, artistX, artistY, artistColor, artistOutline, artistFontStr, false);
    }
    
    // Apply preset styles
    function applyPreset(preset) {
        switch(preset) {
            case 'bollywood':
                document.getElementById('creatorTitleFont').value = "'Mangal', 'Kokila', 'Kalam', sans-serif";
                document.getElementById('creatorArtistFont').value = "'Mangal', 'Kokila', sans-serif";
                document.getElementById('creatorStyle').value = 'shadow';
                document.getElementById('creatorTitleColor').value = '#ffcc00';
                document.getElementById('creatorArtistColor').value = '#ffffff';
                document.getElementById('creatorTitleOutline').value = '#ff0000';
                document.getElementById('creatorPosition').value = 'center';
                selectGradient('purple-blue');
                break;
            case 'punjabi':
                document.getElementById('creatorTitleFont').value = "Impact, sans-serif";
                document.getElementById('creatorArtistFont').value = "Impact, sans-serif";
                document.getElementById('creatorStyle').value = 'outline';
                document.getElementById('creatorTitleColor').value = '#ffffff';
                document.getElementById('creatorArtistColor').value = '#ffcc00';
                document.getElementById('creatorTitleOutline').value = '#000000';
                document.getElementById('creatorPosition').value = 'top';
                selectGradient('gold-sunset');
                break;
            case 'hiphop':
                document.getElementById('creatorTitleFont').value = "'Comic Sans MS', cursive";
                document.getElementById('creatorArtistFont').value = "'Comic Sans MS', cursive";
                document.getElementById('creatorStyle').value = 'neon';
                document.getElementById('creatorTitleColor').value = '#00ff00';
                document.getElementById('creatorArtistColor').value = '#ff00ff';
                document.getElementById('creatorTitleOutline').value = '#000000';
                document.getElementById('creatorPosition').value = 'bottom';
                selectGradient('deep-space');
                break;
            case 'romantic':
                document.getElementById('creatorTitleFont').value = "'Brush Script MT', cursive";
                document.getElementById('creatorArtistFont').value = "'Brush Script MT', cursive";
                document.getElementById('creatorStyle').value = 'elegant';
                document.getElementById('creatorTitleColor').value = '#ff69b4';
                document.getElementById('creatorArtistColor').value = '#ffffff';
                document.getElementById('creatorTitleOutline').value = '#8b008b';
                document.getElementById('creatorPosition').value = 'center';
                selectGradient('pink-rose');
                break;
            case 'edm':
                document.getElementById('creatorTitleFont').value = "'Courier New', monospace";
                document.getElementById('creatorArtistFont').value = "'Courier New', monospace";
                document.getElementById('creatorStyle').value = 'neon';
                document.getElementById('creatorTitleColor').value = '#00ffff';
                document.getElementById('creatorArtistColor').value = '#ff00ff';
                document.getElementById('creatorTitleOutline').value = '#000000';
                document.getElementById('creatorPosition').value = 'top';
                selectGradient('electric');
                break;
            case 'classical':
                document.getElementById('creatorTitleFont').value = "'Times New Roman', serif";
                document.getElementById('creatorArtistFont').value = "Georgia, serif";
                document.getElementById('creatorStyle').value = 'elegant';
                document.getElementById('creatorTitleColor').value = '#ffd700';
                document.getElementById('creatorArtistColor').value = '#ffffff';
                document.getElementById('creatorTitleOutline').value = '#8b4513';
                document.getElementById('creatorPosition').value = 'center';
                selectGradient('royal-purple');
                break;
        }
        updateCreatorPreview();
    }
    
    function applyCanvasFilter(ctx, canvas, filter) {
        if (filter === 'none') return;
        
        // Get image data
        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const data = imageData.data;
        
        for (let i = 0; i < data.length; i += 4) {
            const r = data[i];
            const g = data[i + 1];
            const b = data[i + 2];
            
            switch(filter) {
                case 'bw':
                    const gray = 0.299 * r + 0.587 * g + 0.114 * b;
                    data[i] = gray;
                    data[i + 1] = gray;
                    data[i + 2] = gray;
                    break;
                case 'sepia':
                    data[i] = (r * 0.393) + (g * 0.769) + (b * 0.189);
                    data[i + 1] = (r * 0.349) + (g * 0.686) + (b * 0.168);
                    data[i + 2] = (r * 0.272) + (g * 0.534) + (b * 0.131);
                    break;
                case 'vintage':
                    data[i] = Math.min(255, r * 1.2 + 30);
                    data[i + 1] = Math.min(255, g * 1.1 + 10);
                    data[i + 2] = Math.min(255, b * 0.9);
                    break;
                case 'cool':
                    data[i] = r * 0.9;
                    data[i + 1] = g;
                    data[i + 2] = Math.min(255, b * 1.1 + 20);
                    break;
                case 'warm':
                    data[i] = Math.min(255, r * 1.1 + 20);
                    data[i + 1] = g * 0.95;
                    data[i + 2] = b * 0.8;
                    break;
                case 'neon':
                    data[i] = r > 128 ? Math.min(255, r * 1.3) : r * 0.7;
                    data[i + 1] = g > 128 ? Math.min(255, g * 1.2) : g * 0.7;
                    data[i + 2] = b > 128 ? Math.min(255, b * 1.4) : b * 0.7;
                    break;
                case 'dramatic':
                    const avg = (r + g + b) / 3;
                    const contrast = 1.5;
                    data[i] = Math.min(255, Math.max(0, (r - avg) * contrast + avg));
                    data[i + 1] = Math.min(255, Math.max(0, (g - avg) * contrast + avg));
                    data[i + 2] = Math.min(255, Math.max(0, (b - avg) * contrast + avg));
                    break;
            }
        }
        
        ctx.putImageData(imageData, 0, 0);
    }
    
    function saveCreatorArtwork() {
        const canvas = document.getElementById('creatorCanvas');
        
        // Convert canvas to blob
        canvas.toBlob(function(blob) {
            // Create a File object
            const file = new File([blob], 'cover_art_creator.jpg', { type: 'image/jpeg' });
            
            // Create a DataTransfer object and add the file
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            
            // Set the file to the file input
            const fileInput = document.getElementById('artworkInput');
            fileInput.files = dataTransfer.files;
            
            // Trigger the preview
            previewFile(fileInput);
            
            // Close the creator
            closeCoverCreator();
            
            // Scroll to upload section
            document.getElementById('uploadBox').scrollIntoView({ behavior: 'smooth' });
            
            alert('Cover art created successfully! Click "Submit Release" to save it.');
        }, 'image/jpeg', 0.95);
    }
    
    // Close modal on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeCoverCreator();
        }
    });
    
    // Form submission handlers with loading states
    function setButtonLoading(btnId, loadingText) {
        const btn = document.getElementById(btnId);
        const originalContent = btn.innerHTML;
        btn.dataset.originalContent = originalContent;
        btn.disabled = true;
        btn.style.opacity = '0.7';
        btn.style.cursor = 'not-allowed';
        btn.innerHTML = `<i class="mdi mdi-loading mdi-spin"></i> <span>${loadingText}</span>`;
        return originalContent;
    }
    
    function handleSaveDraft(event) {
        event.preventDefault();
        document.getElementById('formAction').value = 'save';
        setButtonLoading('saveDraftBtn', 'Saving...');
        setButtonLoading('submitBtn', 'Please wait...');
        document.getElementById('releaseForm').submit();
    }
    
    function handleSubmit(event) {
        event.preventDefault();
        document.getElementById('formAction').value = 'submit_release';
        setButtonLoading('submitBtn', 'Submitting...');
        setButtonLoading('saveDraftBtn', 'Please wait...');
        document.getElementById('releaseForm').submit();
    }
</script>

<?php include __DIR__ . '/../includes/footer_premium.php'; ?>

<!-- Cover Art Creator Modal -->
<div id="coverCreatorModal" class="creator-modal" style="display: none;">
    <div class="creator-container">
        <div class="creator-header">
            <h2><i class="mdi mdi-brush"></i> Cover Art Creator</h2>
            <button type="button" class="creator-close" onclick="closeCoverCreator()">
                <i class="mdi mdi-close"></i>
            </button>
        </div>
        
        <div class="creator-body">
            <!-- Template Gallery -->
            <div class="creator-sidebar">
                <h3>Select Template</h3>
                <div class="creator-templates">
                    <?php foreach ($admin_templates as $template): ?>
                    <div class="creator-thumb" onclick="selectCreatorTemplate('<?php echo htmlspecialchars($template['file_path']); ?>')">
                        <img src="<?php echo htmlspecialchars($template['file_path']); ?>" alt="<?php echo htmlspecialchars($template['name']); ?>">
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <h3 style="margin-top: 20px;">Gradient Backgrounds</h3>
                <div class="creator-templates">
                    <!-- 25+ Gradient Templates -->
                    <div class="creator-thumb" onclick="selectGradient('purple-blue')">
                        <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 8px;"></div>
                    </div>
                    <div class="creator-thumb" onclick="selectGradient('dark-night')">
                        <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #1a1a2e, #16213e); border-radius: 8px;"></div>
                    </div>
                    <div class="creator-thumb" onclick="selectGradient('green-nature')">
                        <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #11998e, #38ef7d); border-radius: 8px;"></div>
                    </div>
                    <div class="creator-thumb" onclick="selectGradient('sunset-orange')">
                        <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #00b7ff, #8b5cf6); border-radius: 8px;"></div>
                    </div>
                    <div class="creator-thumb" onclick="selectGradient('pink-rose')">
                        <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #f093fb, #f5576c); border-radius: 8px;"></div>
                    </div>
                    <div class="creator-thumb" onclick="selectGradient('blue-ocean')">
                        <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #4facfe, #00f2fe); border-radius: 8px;"></div>
                    </div>
                    <div class="creator-thumb" onclick="selectGradient('mint-fresh')">
                        <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #43e97b, #38f9d7); border-radius: 8px;"></div>
                    </div>
                    <div class="creator-thumb" onclick="selectGradient('gold-sunset')">
                        <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #fa709a, #fee140); border-radius: 8px;"></div>
                    </div>
                    <div class="creator-thumb" onclick="selectGradient('fire-red')">
                        <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #f12711, #f5af19); border-radius: 8px;"></div>
                    </div>
                    <div class="creator-thumb" onclick="selectGradient('midnight-blue')">
                        <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #0f2027, #203a43, #2c5364); border-radius: 8px;"></div>
                    </div>
                    <div class="creator-thumb" onclick="selectGradient('berry-purple')">
                        <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #8e2de2, #4a00e0); border-radius: 8px;"></div>
                    </div>
                    <div class="creator-thumb" onclick="selectGradient('coral-pink')">
                        <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #ff9a9e, #fecfef); border-radius: 8px;"></div>
                    </div>
                    <div class="creator-thumb" onclick="selectGradient('aqua-marine')">
                        <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #30cfd0, #330867); border-radius: 8px;"></div>
                    </div>
                    <div class="creator-thumb" onclick="selectGradient('lemon-lime')">
                        <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #f6d365, #fda085); border-radius: 8px;"></div>
                    </div>
                    <div class="creator-thumb" onclick="selectGradient('royal-purple')">
                        <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #654ea3, #eaafc8); border-radius: 8px;"></div>
                    </div>
                    <div class="creator-thumb" onclick="selectGradient('deep-space')">
                        <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #000000, #434343); border-radius: 8px;"></div>
                    </div>
                    <div class="creator-thumb" onclick="selectGradient('cherry-red')">
                        <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #eb3349, #f45c43); border-radius: 8px;"></div>
                    </div>
                    <div class="creator-thumb" onclick="selectGradient('sky-blue')">
                        <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #89f7fe, #66a6ff); border-radius: 8px;"></div>
                    </div>
                    <div class="creator-thumb" onclick="selectGradient('magic-magenta')">
                        <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #ee0979, #ff6a00); border-radius: 8px;"></div>
                    </div>
                    <div class="creator-thumb" onclick="selectGradient('forest-green')">
                        <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #134e5e, #71b280); border-radius: 8px;"></div>
                    </div>
                    <div class="creator-thumb" onclick="selectGradient('peach-orange')">
                        <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #ffecd2, #fcb69f); border-radius: 8px;"></div>
                    </div>
                    <div class="creator-thumb" onclick="selectGradient('lavender')">
                        <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #e0c3fc, #8ec5fc); border-radius: 8px;"></div>
                    </div>
                    <div class="creator-thumb" onclick="selectGradient('dark-velvet')">
                        <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #141e30, #243b55); border-radius: 8px;"></div>
                    </div>
                    <div class="creator-thumb" onclick="selectGradient('tropical')">
                        <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #fccb90, #d57eeb); border-radius: 8px;"></div>
                    </div>
                    <div class="creator-thumb" onclick="selectGradient('electric')">
                        <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #00f260, #0575e6); border-radius: 8px;"></div>
                    </div>
                </div>
                
                <h3 style="margin-top: 20px;">Filters</h3>
                <div class="filter-buttons">
                    <button type="button" class="filter-btn" onclick="setFilter('none')">None</button>
                    <button type="button" class="filter-btn" onclick="setFilter('vintage')">Vintage</button>
                    <button type="button" class="filter-btn" onclick="setFilter('bw')">B&W</button>
                    <button type="button" class="filter-btn" onclick="setFilter('sepia')">Sepia</button>
                    <button type="button" class="filter-btn" onclick="setFilter('cool')">Cool</button>
                    <button type="button" class="filter-btn" onclick="setFilter('warm')">Warm</button>
                    <button type="button" class="filter-btn" onclick="setFilter('neon')">Neon</button>
                    <button type="button" class="filter-btn" onclick="setFilter('dramatic')">Dramatic</button>
                </div>
                
                <h3 style="margin-top: 20px;">Text Options</h3>
                <div class="creator-options">
                    <div class="option-group">
                        <label>Song Title</label>
                        <input type="text" id="creatorTitle" value="<?php echo htmlspecialchars($release['title'] ?: 'SONG TITLE'); ?>" oninput="updateCreatorPreview()" placeholder="Enter song title (Hindi or English)">
                    </div>
                    <div class="option-group">
                        <label>Artist Name</label>
                        <input type="text" id="creatorArtist" value="<?php echo htmlspecialchars($release['primary_artist'] ?: 'ARTIST NAME'); ?>" oninput="updateCreatorPreview()" placeholder="Enter artist name (Hindi or English)">
                    </div>
                    
                    <!-- Font Selection -->
                    <div class="option-group">
                        <label>Title Font</label>
                        <select id="creatorTitleFont" onchange="updateCreatorPreview()">
                            <optgroup label="English Fonts">
                                <option value="'Segoe UI', sans-serif">Segoe UI (Modern)</option>
                                <option value="Georgia, serif">Georgia (Elegant)</option>
                                <option value="Impact, sans-serif">Impact (Bold)</option>
                                <option value="'Courier New', monospace">Courier (Retro)</option>
                                <option value="Arial, sans-serif">Arial (Clean)</option>
                                <option value="'Times New Roman', serif">Times (Classic)</option>
                                <option value="Verdana, sans-serif">Verdana (Readable)</option>
                                <option value="'Comic Sans MS', cursive">Comic (Fun)</option>
                                <option value="'Brush Script MT', cursive">Brush Script (Handwritten)</option>
                                <option value="Papyrus, fantasy">Papyrus (Artistic)</option>
                            </optgroup>
                            <optgroup label="Hindi/Desi Fonts (System)">
                                <option value="'Mangal', 'Kokila', 'Kalam', sans-serif">Mangal / Kokila (Hindi)</option>
                                <option value="'Devanagari MT', 'Nirmala UI', sans-serif">Devanagari / Nirmala (Hindi)</option>
                                <option value="'Kalam', 'Mangal', cursive">Kalam (Desi Style)</option>
                                <option value="'Noto Sans Devanagari', sans-serif">Noto Sans (Modern Hindi)</option>
                                <option value="'Samarkan', 'Mangal', fantasy">Samarkan (Desi Look)</option>
                            </optgroup>
                            <optgroup label="Indian Style Fonts">
                                <option value="'Tiro Devanagari Hindi', serif">Tiro Devanagari (Hindi)</option>
                                <option value="'Sahitya', serif">Sahitya (Hindi Book)</option>
                                <option value="'Yatra One', cursive">Yatra One (Desi Bold)</option>
                                <option value="'Modak', cursive">Modak (Round Desi)</option>
                                <option value="'Bungee Shade', cursive">Bungee Shade (Stylish)</option>
                                <option value="'Rozha One', serif">Rozha One (Desi Serif)</option>
                            </optgroup>
                        </select>
                    </div>
                    
                    <div class="option-group">
                        <label>Artist Font</label>
                        <select id="creatorArtistFont" onchange="updateCreatorPreview()">
                            <optgroup label="English Fonts">
                                <option value="'Segoe UI', sans-serif">Segoe UI (Modern)</option>
                                <option value="Georgia, serif">Georgia (Elegant)</option>
                                <option value="Impact, sans-serif">Impact (Bold)</option>
                                <option value="Arial, sans-serif">Arial (Clean)</option>
                                <option value="'Brush Script MT', cursive">Brush Script (Handwritten)</option>
                            </optgroup>
                            <optgroup label="Hindi/Desi Fonts">
                                <option value="'Mangal', 'Kokila', sans-serif">Mangal / Kokila (Hindi)</option>
                                <option value="'Nirmala UI', 'Mangal', sans-serif">Nirmala UI (Hindi)</option>
                                <option value="'Kalam', cursive">Kalam (Desi Style)</option>
                                <option value="'Noto Sans Devanagari', sans-serif">Noto Sans (Modern Hindi)</option>
                            </optgroup>
                        </select>
                    </div>
                    
                    <div class="option-row">
                        <div class="option-group">
                            <label>Position</label>
                            <select id="creatorPosition" onchange="updateCreatorPreview()">
                                <option value="top">Top</option>
                                <option value="top-left">Top Left</option>
                                <option value="top-right">Top Right</option>
                                <option value="center">Center</option>
                                <option value="center-left">Center Left</option>
                                <option value="center-right">Center Right</option>
                                <option value="bottom" selected>Bottom</option>
                                <option value="bottom-left">Bottom Left</option>
                                <option value="bottom-right">Bottom Right</option>
                            </select>
                        </div>
                        <div class="option-group">
                            <label>Alignment</label>
                            <select id="creatorAlignment" onchange="updateCreatorPreview()">
                                <option value="center">Center</option>
                                <option value="left">Left</option>
                                <option value="right">Right</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="option-row">
                        <div class="option-group">
                            <label>Style Effect</label>
                            <select id="creatorStyle" onchange="updateCreatorPreview()">
                                <option value="modern">Modern Bold</option>
                                <option value="elegant">Elegant Script</option>
                                <option value="minimal">Minimal Clean</option>
                                <option value="grunge">Grunge Rock</option>
                                <option value="neon">Neon Glow</option>
                                <option value="retro">Retro Pixel</option>
                                <option value="shadow">Drop Shadow</option>
                                <option value="outline">Outline Border</option>
                                <option value="3d">3D Effect</option>
                                <option value="emboss">Embossed</option>
                            </select>
                        </div>
                        <div class="option-group">
                            <label>Text Transform</label>
                            <select id="creatorTransform" onchange="updateCreatorPreview()">
                                <option value="uppercase">UPPERCASE</option>
                                <option value="lowercase">lowercase</option>
                                <option value="capitalize">Capitalize</option>
                                <option value="none">As Typed</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="option-row">
                        <div class="option-group">
                            <label>Title Color</label>
                            <input type="color" id="creatorTitleColor" value="#ffffff" onchange="updateCreatorPreview()">
                        </div>
                        <div class="option-group">
                            <label>Title Outline</label>
                            <input type="color" id="creatorTitleOutline" value="#000000" onchange="updateCreatorPreview()">
                        </div>
                    </div>
                    
                    <div class="option-row">
                        <div class="option-group">
                            <label>Artist Color</label>
                            <input type="color" id="creatorArtistColor" value="#cccccc" onchange="updateCreatorPreview()">
                        </div>
                        <div class="option-group">
                            <label>Artist Outline</label>
                            <input type="color" id="creatorArtistOutline" value="#000000" onchange="updateCreatorPreview()">
                        </div>
                    </div>
                    
                    <div class="option-row">
                        <div class="option-group">
                            <label>Title Size</label>
                            <input type="range" id="creatorTitleSize" min="20" max="100" value="48" oninput="updateCreatorPreview()">
                        </div>
                        <div class="option-group">
                            <label>Artist Size</label>
                            <input type="range" id="creatorArtistSize" min="15" max="60" value="28" oninput="updateCreatorPreview()">
                        </div>
                    </div>
                    
                    <div class="option-row">
                        <div class="option-group">
                            <label>Letter Spacing</label>
                            <input type="range" id="creatorLetterSpacing" min="-5" max="20" value="2" oninput="updateCreatorPreview()">
                        </div>
                        <div class="option-group">
                            <label>Line Height</label>
                            <input type="range" id="creatorLineHeight" min="0.8" max="2" step="0.1" value="1.2" oninput="updateCreatorPreview()">
                        </div>
                    </div>
                    
                    <div class="option-group">
                        <label>Background Opacity</label>
                        <input type="range" id="bgOpacity" min="0" max="100" value="100" oninput="updateCreatorPreview()">
                    </div>
                    
                    <!-- Quick Presets -->
                    <h4 style="margin-top: 20px; color: rgba(255,255,255,0.6);">Quick Presets</h4>
                    <div class="preset-buttons">
                        <button type="button" class="preset-btn" onclick="applyPreset('bollywood')">🎬 Bollywood</button>
                        <button type="button" class="preset-btn" onclick="applyPreset('punjabi')">🎵 Punjabi</button>
                        <button type="button" class="preset-btn" onclick="applyPreset('hiphop')">🎤 Hip Hop</button>
                        <button type="button" class="preset-btn" onclick="applyPreset('romantic')">💕 Romantic</button>
                        <button type="button" class="preset-btn" onclick="applyPreset('edm')">🎧 EDM</button>
                        <button type="button" class="preset-btn" onclick="applyPreset('classical')">🎻 Classical</button>
                    </div>
                </div>
            </div>
            
            <!-- Preview Area -->
            <div class="creator-preview-area">
                <div id="creatorCanvasWrapper" class="creator-canvas-wrapper">
                    <canvas id="creatorCanvas" width="1024" height="1024"></canvas>
                </div>
                <div class="creator-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeCoverCreator()">Cancel</button>
                    <button type="button" class="btn btn-success" onclick="saveCreatorArtwork()">
                        <i class="mdi mdi-check"></i> Use This Cover Art
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Cover Creator Modal */
.creator-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.9);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
}
.creator-container {
    width: 95%;
    max-width: 1200px;
    height: 90vh;
    background: #1a1a2e;
    border-radius: 20px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}
.creator-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 30px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-bottom: 1px solid rgba(255,255,255,0.1);
}
.creator-header h2 {
    font-size: 22px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.creator-close {
    background: none;
    border: none;
    color: #fff;
    font-size: 24px;
    cursor: pointer;
    padding: 5px;
}
.creator-body {
    display: flex;
    flex: 1;
    overflow: hidden;
}
.creator-sidebar {
    width: 320px;
    background: rgba(0,0,0,0.3);
    padding: 20px;
    overflow-y: auto;
}
.creator-sidebar h3 {
    font-size: 16px;
    margin-bottom: 15px;
    color: rgba(255,255,255,0.8);
}
.creator-templates {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
}
.creator-thumb {
    aspect-ratio: 1;
    border-radius: 8px;
    overflow: hidden;
    cursor: pointer;
    border: 2px solid transparent;
    transition: all 0.3s;
}
.creator-thumb:hover, .creator-thumb.active {
    border-color: #00d4aa;
    transform: scale(1.05);
}
.creator-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.creator-options {
    display: flex;
    flex-direction: column;
    gap: 15px;
}
.option-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}
.option-group label {
    font-size: 12px;
    color: rgba(255,255,255,0.6);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.option-group input[type="text"],
.option-group select {
    padding: 10px 12px;
    background: rgba(0,0,0,0.3);
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 8px;
    color: #fff;
    font-size: 14px;
}
.option-group input[type="color"] {
    width: 50px;
    height: 35px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
}
.option-group input[type="range"] {
    width: 100%;
}
.option-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}
.creator-preview-area {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 30px;
    background: #0a0a0a;
}
.creator-canvas-wrapper {
    width: 100%;
    max-width: 500px;
    aspect-ratio: 1;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0,0,0,0.5);
}
#creatorCanvas {
    width: 100%;
    height: 100%;
    object-fit: contain;
}
.creator-actions {
    display: flex;
    gap: 15px;
    margin-top: 30px;
}
.btn-creator {
    padding: 15px 35px;
    font-size: 16px;
    background: linear-gradient(135deg, #00b7ff, #8b5cf6);
}

/* Filter Buttons */
.filter-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 15px;
}
.filter-btn {
    padding: 8px 14px;
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 20px;
    color: rgba(255,255,255,0.8);
    font-size: 12px;
    cursor: pointer;
    transition: all 0.3s;
}
.filter-btn:hover {
    background: rgba(255,255,255,0.2);
    border-color: rgba(255,255,255,0.3);
}
.filter-btn.active {
    background: linear-gradient(135deg, #00d4aa, #00c853);
    color: #000;
    border-color: #00d4aa;
}

/* Preset Buttons */
.preset-buttons {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
}
.preset-btn {
    padding: 10px 12px;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.2), rgba(118, 75, 162, 0.2));
    border: 1px solid rgba(102, 126, 234, 0.4);
    border-radius: 10px;
    color: #fff;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
}
.preset-btn:hover {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.4), rgba(118, 75, 162, 0.4));
    border-color: rgba(102, 126, 234, 0.6);
    transform: translateY(-2px);
}
</style>
