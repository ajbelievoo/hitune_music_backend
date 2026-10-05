<?php
/**
 * Music Distribution - Submit Music Form
 */

if (!defined('bof_root')) die('Direct access not allowed');

class page_dist_submit extends bof_page {

    public function load() {
        
        $page = [
            'name' => 'dist_submit',
            'title' => 'Submit Your Music for Distribution',
            'slug' => 'distribution/submit',
            'require_login' => true,
            'layout' => 'fullwidth',
            'seo' => [
                'description' => 'Submit your music for distribution to Spotify, Apple Music, YouTube and 100+ platforms'
            ]
        ];

        return $page;
    }

    public function body() {
        
        $endpoint = $this->loader->endpoint_address;
        $user = $this->loader->user;
        
        ob_start();
        ?>

<style>
.dist-submit {
    max-width: 800px;
    margin: 0 auto;
    padding: 40px 20px;
}

.form-header {
    text-align: center;
    margin-bottom: 40px;
}
.form-header h1 {
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 10px;
}
.form-header p {
    opacity: 0.7;
    font-size: 16px;
}

.form-section {
    background: rgba(var(--bg_color), 1);
    border: 1px solid rgba(var(--font_color), 0.1);
    border-radius: 16px;
    padding: 30px;
    margin-bottom: 30px;
}
.form-section h3 {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 1px solid rgba(var(--font_color), 0.1);
}

.form-group {
    margin-bottom: 25px;
}
.form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 10px;
    font-size: 14px;
}
.form-group label .required {
    color: #f44336;
}
.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 14px 18px;
    background: rgba(var(--bg_color2), 0.3);
    border: 1px solid rgba(var(--font_color), 0.15);
    border-radius: 10px;
    font-size: 15px;
    color: inherit;
    transition: all 0.3s;
}
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: rgba(var(--theme_color), 0.5);
}
.form-group input::placeholder {
    opacity: 0.4;
}
.form-group small {
    display: block;
    margin-top: 8px;
    opacity: 0.6;
    font-size: 13px;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.release-type-selector {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
}
.release-type-option {
    flex: 1;
    padding: 20px;
    border: 2px solid rgba(var(--font_color), 0.15);
    border-radius: 12px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
}
.release-type-option:hover {
    border-color: rgba(var(--theme_color), 0.5);
}
.release-type-option.selected {
    border-color: rgba(var(--theme_color), 1);
    background: rgba(var(--theme_color), 0.1);
}
.release-type-option .mdi {
    font-size: 32px;
    margin-bottom: 10px;
    display: block;
}
.release-type-option h4 {
    font-size: 16px;
    margin-bottom: 5px;
}
.release-type-option p {
    font-size: 13px;
    opacity: 0.6;
}

.file-upload {
    border: 2px dashed rgba(var(--font_color), 0.2);
    border-radius: 12px;
    padding: 40px 30px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
    position: relative;
}
.file-upload:hover {
    border-color: rgba(var(--theme_color), 0.5);
}
.file-upload.has-file {
    border-color: rgba(var(--theme_color), 1);
    background: rgba(var(--theme_color), 0.05);
}
.file-upload input {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
}
.file-upload .mdi {
    font-size: 48px;
    opacity: 0.4;
    margin-bottom: 15px;
}
.file-upload h4 {
    font-size: 16px;
    margin-bottom: 8px;
}
.file-upload p {
    font-size: 14px;
    opacity: 0.6;
}
.file-upload .file-preview {
    margin-top: 15px;
}
.file-upload .file-preview img {
    max-width: 150px;
    max-height: 150px;
    border-radius: 8px;
}
.file-upload .file-name {
    margin-top: 10px;
    font-weight: 600;
}

.platforms-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 12px;
}
.platform-option {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 15px;
    border: 1px solid rgba(var(--font_color), 0.15);
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
}
.platform-option:hover {
    border-color: rgba(var(--theme_color), 0.3);
}
.platform-option.selected {
    border-color: rgba(var(--theme_color), 1);
    background: rgba(var(--theme_color), 0.1);
}
.platform-option input {
    display: none;
}
.platform-option .check {
    width: 20px;
    height: 20px;
    border: 2px solid rgba(var(--font_color), 0.3);
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
}
.platform-option.selected .check {
    background: rgba(var(--theme_color), 1);
    border-color: rgba(var(--theme_color), 1);
}
.platform-option .check .mdi {
    font-size: 16px;
    color: #fff;
    opacity: 0;
}
.platform-option.selected .check .mdi {
    opacity: 1;
}

.tracks-section {
    margin-top: 20px;
}
.track-item {
    background: rgba(var(--bg_color2), 0.3);
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 15px;
}
.track-item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}
.track-number {
    font-weight: 600;
    color: rgba(var(--theme_color), 1);
}
.btn-remove {
    background: transparent;
    border: none;
    color: #f44336;
    cursor: pointer;
    font-size: 20px;
}
.btn-add-track {
    width: 100%;
    padding: 15px;
    background: transparent;
    border: 2px dashed rgba(var(--font_color), 0.3);
    border-radius: 10px;
    cursor: pointer;
    font-size: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.3s;
}
.btn-add-track:hover {
    border-color: rgba(var(--theme_color), 1);
    color: rgba(var(--theme_color), 1);
}

.form-actions {
    display: flex;
    gap: 15px;
    justify-content: flex-end;
    padding-top: 20px;
}
.btn-cancel {
    padding: 14px 28px;
    background: transparent;
    border: 1px solid rgba(var(--font_color), 0.3);
    border-radius: 10px;
    cursor: pointer;
    font-size: 15px;
    text-decoration: none;
    color: inherit;
}
.btn-submit {
    padding: 14px 32px;
    background: rgba(var(--theme_color), 1);
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s;
}
.btn-submit:hover {
    opacity: 0.9;
}
.btn-submit:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.submitting-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}
.submitting-content {
    text-align: center;
    color: #fff;
}
.submitting-content .spinner {
    width: 60px;
    height: 60px;
    border: 4px solid rgba(255,255,255,0.3);
    border-top-color: rgba(var(--theme_color), 1);
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 20px;
}
@keyframes spin {
    to { transform: rotate(360deg); }
}

.no-subscription {
    text-align: center;
    padding: 80px 20px;
}
.no-subscription .mdi {
    font-size: 64px;
    opacity: 0.3;
    margin-bottom: 20px;
}
.no-subscription h2 {
    font-size: 24px;
    margin-bottom: 15px;
}
.no-subscription p {
    opacity: 0.7;
    margin-bottom: 25px;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    .release-type-selector {
        flex-direction: column;
    }
    .platforms-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .form-actions {
        flex-direction: column-reverse;
    }
    .btn-cancel,
    .btn-submit {
        width: 100%;
        justify-content: center;
    }
}
</style>

<div class="dist-submit">
    <div id="form-container">
        <div class="loading" style="text-align: center; padding: 60px;">Loading...</div>
    </div>
</div>

<script>
let subscription = null;
let selectedType = 'single';
let trackCount = 1;

async function checkSubscription() {
    try {
        const response = await fetch('<?php echo $endpoint; ?>dist/plans', {
            headers: {
                'Authorization': 'Bearer ' + (window.app?.user?.token || '')
            }
        });
        const data = await response.json();
        
        if (data.success) {
            subscription = data.data.user_subscription;
            if (subscription && subscription.status === 'active') {
                renderForm();
            } else {
                renderNoSubscription();
            }
        }
    } catch (error) {
        console.error('Error:', error);
        renderNoSubscription();
    }
}

function renderNoSubscription() {
    document.getElementById('form-container').innerHTML = `
        <div class="no-subscription">
            <span class="mdi mdi-crown"></span>
            <h2>Subscription Required</h2>
            <p>You need an active subscription to submit music for distribution.</p>
            <a href="/distribution" class="btn-primary">View Plans</a>
        </div>
    `;
}

function renderForm() {
    document.getElementById('form-container').innerHTML = `
        <div class="form-header">
            <h1>Submit Your Music</h1>
            <p>Fill in all the details below to distribute your music worldwide</p>
        </div>
        
        <form id="submit-form" enctype="multipart/form-data">
            <div class="form-section">
                <h3>Release Type</h3>
                <div class="release-type-selector">
                    <div class="release-type-option selected" data-type="single" onclick="selectType('single')">
                        <span class="mdi mdi-music-note"></span>
                        <h4>Single</h4>
                        <p>1 song release</p>
                    </div>
                    <div class="release-type-option" data-type="ep" onclick="selectType('ep')">
                        <span class="mdi mdi-album"></span>
                        <h4>EP</h4>
                        <p>2-6 songs</p>
                    </div>
                    <div class="release-type-option" data-type="album" onclick="selectType('album')">
                        <span class="mdi mdi-library-music"></span>
                        <h4>Album</h4>
                        <p>7+ songs</p>
                    </div>
                </div>
                <input type="hidden" name="type" id="release-type" value="single">
            </div>

            <div class="form-section">
                <h3>Music Details</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>Title <span class="required">*</span></label>
                        <input type="text" name="title" placeholder="Song or Album Title" required>
                    </div>
                    <div class="form-group">
                        <label>Artist Name <span class="required">*</span></label>
                        <input type="text" name="artist_name" placeholder="Primary Artist" required>
                    </div>
                </div>
                <div class="form-group" id="album-name-group" style="display: none;">
                    <label>Album Name <span class="required">*</span></label>
                    <input type="text" name="album_name" placeholder="Album Name">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Genre</label>
                        <select name="genre">
                            <option value="">Select Genre</option>
                            <option value="Pop">Pop</option>
                            <option value="Hip Hop">Hip Hop</option>
                            <option value="Rock">Rock</option>
                            <option value="Electronic">Electronic</option>
                            <option value="R&B">R&B</option>
                            <option value="Country">Country</option>
                            <option value="Jazz">Jazz</option>
                            <option value="Classical">Classical</option>
                            <option value="Latin">Latin</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Release Date</label>
                        <input type="date" name="release_date" min="${new Date().toISOString().split('T')[0]}">
                        <small>Leave blank for immediate release after approval</small>
                    </div>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3" placeholder="Tell us about this release..."></textarea>
                </div>
            </div>

            <div class="form-section" id="tracks-section" style="display: none;">
                <h3>Tracklist</h3>
                <div id="tracks-container"></div>
                <button type="button" class="btn-add-track" onclick="addTrack()">
                    <span class="mdi mdi-plus"></span> Add Track
                </button>
            </div>

            <div class="form-section">
                <h3>Upload Files</h3>
                <div class="form-group">
                    <label>Cover Art <span class="required">*</span> (3000x3000px, JPG/PNG)</label>
                    <div class="file-upload" id="cover-upload">
                        <input type="file" name="cover_art" accept="image/jpeg,image/png" required onchange="handleFileSelect(this, 'cover')">
                        <span class="mdi mdi-image"></span>
                        <h4>Upload Cover Art</h4>
                        <p>Drag & drop or click to browse</p>
                        <div class="file-preview" style="display: none;"></div>
                        <div class="file-name" style="display: none;"></div>
                    </div>
                </div>
                
                <div class="form-group" id="audio-upload-group">
                    <label>Audio File <span class="required">*</span> (WAV or MP3, high quality)</label>
                    <div class="file-upload" id="audio-upload">
                        <input type="file" name="audio_file" accept="audio/wav,audio/mpeg,audio/mp3" required onchange="handleFileSelect(this, 'audio')">
                        <span class="mdi mdi-music-note"></span>
                        <h4>Upload Audio</h4>
                        <p>Drag & drop or click to browse</p>
                        <div class="file-name" style="display: none;"></div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Additional Information</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>ISRC Code</label>
                        <input type="text" name="isrc" placeholder="XX-XXX-XX-XXXXX">
                        <small>Leave blank if you don't have one</small>
                    </div>
                    <div class="form-group">
                        <label>UPC Code</label>
                        <input type="text" name="upc" placeholder="XXXXXXXXXXXX">
                        <small>Leave blank if you don't have one</small>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Label Name</label>
                        <input type="text" name="label_name" placeholder="Your Label Name (optional)">
                    </div>
                    <div class="form-group">
                        <label>Language</label>
                        <select name="language">
                            <option value="">Select Language</option>
                            <option value="Hindi">Hindi</option>
                            <option value="English">English</option>
                            <option value="Punjabi">Punjabi</option>
                            <option value="Tamil">Tamil</option>
                            <option value="Telugu">Telugu</option>
                            <option value="Bengali">Bengali</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Recording Location</label>
                    <input type="text" name="recording_location" placeholder="City, Country (optional)">
                </div>
            </div>

            <div class="form-section">
                <h3>Distribution Platforms</h3>
                <div class="form-group">
                    <label>Select platforms to distribute to:</label>
                    <div class="platforms-grid">
                        ${['Spotify', 'Apple Music', 'YouTube Music', 'Amazon Music', 'Tidal', 'Deezer', 'Pandora', 'iTunes', 'TikTok', 'Facebook', 'Instagram'].map(p => `
                            <label class="platform-option selected" onclick="togglePlatform(this)">
                                <input type="checkbox" name="platforms[]" value="${p}" checked>
                                <div class="check"><span class="mdi mdi-check"></span></div>
                                <span>${p}</span>
                            </label>
                        `).join('')}
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <a href="/distribution/dashboard" class="btn-cancel">Cancel</a>
                <button type="submit" class="btn-submit">
                    <span class="mdi mdi-send"></span>
                    Submit for Distribution
                </button>
            </div>
        </form>

        <div class="submitting-overlay" id="submitting-overlay" style="display: none;">
            <div class="submitting-content">
                <div class="spinner"></div>
                <h3>Submitting your music...</h3>
                <p>Please don't close this window</p>
            </div>
        </div>
    `;
    
    initFormHandlers();
}

function selectType(type) {
    selectedType = type;
    document.getElementById('release-type').value = type;
    document.querySelectorAll('.release-type-option').forEach(opt => opt.classList.remove('selected'));
    document.querySelector(`[data-type="${type}"]`).classList.add('selected');
    
    const albumGroup = document.getElementById('album-name-group');
    const tracksSection = document.getElementById('tracks-section');
    const audioGroup = document.getElementById('audio-upload-group');
    
    if (type === 'single') {
        albumGroup.style.display = 'none';
        tracksSection.style.display = 'none';
        audioGroup.style.display = 'block';
    } else {
        albumGroup.style.display = 'block';
        albumGroup.querySelector('input').required = true;
        tracksSection.style.display = 'block';
        audioGroup.style.display = 'none';
        if (document.getElementById('tracks-container').children.length === 0) {
            addTrack();
        }
    }
}

function addTrack() {
    const container = document.getElementById('tracks-container');
    const trackNum = ++trackCount;
    
    const trackEl = document.createElement('div');
    trackEl.className = 'track-item';
    trackEl.innerHTML = `
        <div class="track-item-header">
            <span class="track-number">Track ${trackNum}</span>
            ${trackNum > 1 ? `<button type="button" class="btn-remove" onclick="this.closest('.track-item').remove()">&times;</button>` : ''}
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Title <span class="required">*</span></label>
                <input type="text" name="tracks[${trackNum}][title]" placeholder="Track Title" required>
            </div>
            <div class="form-group">
                <label>Artist Name <span class="required">*</span></label>
                <input type="text" name="tracks[${trackNum}][artist_name]" placeholder="Track Artist" required>
            </div>
        </div>
        <div class="form-group">
            <label>Audio File <span class="required">*</span></label>
            <div class="file-upload">
                <input type="file" name="track_audio_${trackNum}" accept="audio/wav,audio/mpeg,audio/mp3" required onchange="handleFileSelect(this, 'track', ${trackNum})">
                <span class="mdi mdi-music-note"></span>
                <h4>Upload Track Audio</h4>
                <p>WAV or MP3 format</p>
                <div class="file-name" style="display: none;"></div>
            </div>
        </div>
    `;
    container.appendChild(trackEl);
}

function togglePlatform(el) {
    el.classList.toggle('selected');
    const checkbox = el.querySelector('input');
    checkbox.checked = el.classList.contains('selected');
}

function handleFileSelect(input, type, trackNum) {
    const file = input.files[0];
    if (!file) return;
    
    const uploadDiv = input.closest('.file-upload');
    uploadDiv.classList.add('has-file');
    uploadDiv.querySelector('.file-name').textContent = file.name;
    uploadDiv.querySelector('.file-name').style.display = 'block';
    
    if (type === 'cover') {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = uploadDiv.querySelector('.file-preview');
            preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
}

function initFormHandlers() {
    const form = document.getElementById('submit-form');
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        document.getElementById('submitting-overlay').style.display = 'flex';
        
        const formData = new FormData(form);
        
        try {
            const response = await fetch('<?php echo $endpoint; ?>dist/submit', {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + (window.app?.user?.token || '')
                },
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert('Submission successful! Your music is now under review.');
                window.location.href = '/distribution/dashboard';
            } else {
                alert('Error: ' + result.message);
                document.getElementById('submitting-overlay').style.display = 'none';
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Something went wrong. Please try again.');
            document.getElementById('submitting-overlay').style.display = 'none';
        }
    });
}

// Load on page load
document.addEventListener('DOMContentLoaded', checkSubscription);
</script>

        <?php
        return ob_get_clean();
    }

}
