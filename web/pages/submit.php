<?php
/**
 * HiTune Music Distribution - Submit Page
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$pageTitle = 'Submit Music - HiTune Music Distribution';
include __DIR__ . '/../includes/header_premium.php';
?>
<style>
    .submit-page { max-width: 800px; margin: 0 auto; padding: 120px 40px 80px; }
    .submit-page::before {
        content: '';
        position: fixed;
        width: 600px;
        height: 600px;
        background: radial-gradient(circle, rgba(0, 183, 255, 0.12) 0%, transparent 60%);
        top: -200px;
        right: -200px;
        animation: bgGlow 8s ease-in-out infinite;
        z-index: -1;
        pointer-events: none;
    }
    @keyframes bgGlow {
        0%, 100% { transform: scale(1); opacity: 0.5; }
        50% { transform: scale(1.2); opacity: 0.8; }
    }
    .submit-header { text-align: center; margin-bottom: 50px; }
    .submit-header h1 { font-size: 36px; font-weight: 700; margin-bottom: 15px; }
    .submit-header p { color: rgba(255,255,255,0.6); }
    .submit-form { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 20px; padding: 40px; }
    .form-section { margin-bottom: 40px; }
    .form-section h3 { font-size: 18px; font-weight: 600; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid rgba(255,255,255,0.1); }
    .form-group { margin-bottom: 25px; }
    .form-group label { display: block; margin-bottom: 10px; font-size: 14px; font-weight: 500; }
    .form-group label .required { color: #00b7ff; }
    .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 14px 18px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.2); border-radius: 10px; color: #fff; font-size: 15px; transition: all 0.3s; }
    .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #00b7ff; background: rgba(255,255,255,0.08); }
    .form-group input::placeholder { color: rgba(255,255,255,0.4); }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .type-selector { display: flex; gap: 20px; margin-bottom: 30px; }
    .type-option { flex: 1; padding: 30px; background: rgba(255,255,255,0.03); border: 2px solid rgba(255,255,255,0.1); border-radius: 15px; text-align: center; cursor: pointer; transition: all 0.3s; }
    .type-option:hover { border-color: rgba(255,255,255,0.3); }
    .type-option.selected { border-color: #00b7ff; background: rgba(0,183,255,0.1); }
    .type-option i { font-size: 36px; margin-bottom: 15px; color: #00b7ff; }
    .type-option h4 { font-size: 16px; font-weight: 600; margin-bottom: 5px; }
    .type-option p { font-size: 13px; color: rgba(255,255,255,0.5); }
    .file-upload { border: 2px dashed rgba(255,255,255,0.2); border-radius: 15px; padding: 50px; text-align: center; cursor: pointer; transition: all 0.3s; position: relative; }
    .file-upload:hover { border-color: #00b7ff; background: rgba(0,183,255,0.05); }
    .file-upload i { font-size: 50px; color: rgba(255,255,255,0.3); margin-bottom: 20px; }
    .file-upload p { color: rgba(255,255,255,0.6); margin-bottom: 10px; }
    .file-upload small { color: rgba(255,255,255,0.4); font-size: 13px; }
    .file-upload input { position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; }
    .platforms-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; }
    .platform-option { display: flex; align-items: center; gap: 10px; padding: 15px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; cursor: pointer; transition: all 0.3s; }
    .platform-option:hover { background: rgba(255,255,255,0.08); }
    .platform-option.selected { border-color: #00c853; background: rgba(0,200,83,0.1); }
    .platform-option i { font-size: 20px; }
    .submit-btn { width: 100%; padding: 18px; background: linear-gradient(135deg, #00c853, #00e676); color: #fff; border: none; border-radius: 12px; font-size: 16px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; }
    .submit-btn:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(0,200,83,0.3); }
    @media (max-width: 600px) { .form-row { grid-template-columns: 1fr; } .type-selector { flex-direction: column; } }
</style>

<div class="submit-page">
    <div class="submit-header">
        <h1>Submit Your Music</h1>
        <p>Upload your tracks and get them distributed to 150+ platforms worldwide</p>
    </div>

    <form class="submit-form">
        <!-- Release Type -->
        <div class="form-section">
            <h3><i class="mdi mdi-album" style="color: #00b7ff; margin-right: 10px;"></i>Release Type</h3>
            <div class="type-selector">
                <div class="type-option selected" onclick="selectType(this)">
                    <i class="mdi mdi-music-note"></i>
                    <h4>Single</h4>
                    <p>1 track</p>
                </div>
                <div class="type-option" onclick="selectType(this)">
                    <i class="mdi mdi-album"></i>
                    <h4>EP</h4>
                    <p>2-6 tracks</p>
                </div>
                <div class="type-option" onclick="selectType(this)">
                    <i class="mdi mdi-disc"></i>
                    <h4>Album</h4>
                    <p>7+ tracks</p>
                </div>
            </div>
        </div>

        <!-- Basic Info -->
        <div class="form-section">
            <h3><i class="mdi mdi-information" style="color: #00b7ff; margin-right: 10px;"></i>Basic Information</h3>
            <div class="form-group">
                <label>Release Title <span class="required">*</span></label>
                <input type="text" placeholder="Enter your release title" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Primary Artist <span class="required">*</span></label>
                    <input type="text" placeholder="Artist name" required>
                </div>
                <div class="form-group">
                    <label>Genre <span class="required">*</span></label>
                    <select required>
                        <option value="">Select Genre</option>
                        <option>Pop</option>
                        <option>Hip-Hop</option>
                        <option>Rock</option>
                        <option>R&B</option>
                        <option>Electronic</option>
                        <option>Classical</option>
                        <option>Jazz</option>
                        <option>Country</option>
                        <option>Latin</option>
                        <option>Indian</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Release Date <span class="required">*</span></label>
                    <input type="date" required>
                </div>
                <div class="form-group">
                    <label>Language</label>
                    <select>
                        <option>English</option>
                        <option>Hindi</option>
                        <option>Punjabi</option>
                        <option>Tamil</option>
                        <option>Telugu</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Cover Art -->
        <div class="form-section">
            <h3><i class="mdi mdi-image" style="color: #00b7ff; margin-right: 10px;"></i>Cover Art</h3>
            <div class="file-upload">
                <i class="mdi mdi-cloud-upload"></i>
                <p>Click or drag to upload cover art</p>
                <small>3000x3000px, JPG or PNG, max 10MB</small>
                <input type="file" accept="image/jpeg,image/png">
            </div>
        </div>

        <!-- Audio File -->
        <div class="form-section">
            <h3><i class="mdi mdi-music" style="color: #00b7ff; margin-right: 10px;"></i>Audio File</h3>
            <div class="file-upload">
                <i class="mdi mdi-music-box"></i>
                <p>Upload your audio file</p>
                <small>WAV, FLAC, or MP3 320kbps</small>
                <input type="file" accept="audio/*">
            </div>
        </div>

        <!-- Platforms -->
        <div class="form-section">
            <h3><i class="mdi mdi-share-variant" style="color: #00b7ff; margin-right: 10px;"></i>Select Platforms</h3>
            <div class="platforms-grid">
                <div class="platform-option selected" onclick="togglePlatform(this)">
                    <i class="mdi mdi-spotify" style="color: #1DB954;"></i>
                    <span>Spotify</span>
                </div>
                <div class="platform-option selected" onclick="togglePlatform(this)">
                    <i class="mdi mdi-apple" style="color: #FA243C;"></i>
                    <span>Apple Music</span>
                </div>
                <div class="platform-option selected" onclick="togglePlatform(this)">
                    <i class="mdi mdi-youtube" style="color: #FF0000;"></i>
                    <span>YouTube Music</span>
                </div>
                <div class="platform-option selected" onclick="togglePlatform(this)">
                    <i class="mdi mdi-amazon" style="color: #00A8E1;"></i>
                    <span>Amazon Music</span>
                </div>
                <div class="platform-option" onclick="togglePlatform(this)">
                    <i class="mdi mdi-music" style="color: #00FFFF;"></i>
                    <span>Tidal</span>
                </div>
                <div class="platform-option" onclick="togglePlatform(this)">
                    <i class="mdi mdi-music-circle" style="color: #FF0099;"></i>
                    <span>Deezer</span>
                </div>
                <div class="platform-option" onclick="togglePlatform(this)">
                    <i class="mdi mdi-music-note" style="color: #0090d9;"></i>
                    <span>Pandora</span>
                </div>
                <div class="platform-option" onclick="togglePlatform(this)">
                    <i class="mdi mdi-music-box" style="color: #FBBC05;"></i>
                    <span>iTunes</span>
                </div>
            </div>
        </div>

        <button type="submit" class="submit-btn">
            <i class="mdi mdi-send"></i>
            Submit Release
        </button>
    </form>
</div>

<script>
    function selectType(el) {
        document.querySelectorAll('.type-option').forEach(opt => opt.classList.remove('selected'));
        el.classList.add('selected');
    }
    function togglePlatform(el) {
        el.classList.toggle('selected');
    }
</script>

<?php include __DIR__ . '/../includes/footer_premium.php'; ?>
