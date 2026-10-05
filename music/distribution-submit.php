<?php
/**
 * Music Distribution - Submit Music Page (Standalone)
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Canonical host is the distribution subdomain; bounce legacy music.hitune.in URLs.
if (($_SERVER['HTTP_HOST'] ?? '') === 'music.hitune.in') {
    $q = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
    header('Location: https://distribution.hitune.in/submit' . $q, true, 301);
    exit;
}

// Let the page load - API will handle authentication
$is_logged = false;
$user_id = 0;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Music | Hitune Music</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@6.5.95/css/materialdesignicons.min.css">
    <script src="/dist-auth.js"></script>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/dist-style.css">
    <style>
        .type-selector { gap: 14px; margin-bottom: 4px; }
        .type-option {
            flex: 1; min-width: 120px; padding: 22px 14px;
            border-radius: 14px; text-align: center;
        }
        .type-option .mdi { font-size: 30px; display: block; margin-bottom: 8px; color: var(--d-faint); }
        .type-option.selected .mdi { color: var(--d-cyan); }
        .type-option .name { font-weight: 700; }
        .track-item { display: block; }
        .track-item h3 { font-size: 14px; font-weight: 800; margin-bottom: 14px; color: var(--d-navy); }
        .track-item .remove-track { font-size: 13px; font-weight: 700; }
        .file-upload input { display: none; }
    </style>
</head>
<body>
    <nav class="topnav">
        <div class="topnav-inner">
            <a class="brand" href="/"><span class="brand-dot"><span class="mdi mdi-music-note"></span></span>Hitune <small>Distribution</small></a>
            <div class="nav-links">
                <a href="/">Plans</a>
                <a href="/dashboard">Dashboard</a>
                <a href="/admin">Admin</a>
                <a href="https://music.hitune.in/">Hitune Music</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="section" style="margin-top:24px"><h2><span class="mdi mdi-rocket-launch"></span>Submit a Release</h2>
        <p class="muted" style="margin-top:-8px">Upload your release for review — it will be delivered to your selected platforms.</p></div>

        <div id="subscription-check" class="form-section">
            <div class="loading active">
                <div class="spinner"></div>
                <p>Checking subscription status...</p>
            </div>
            <div id="subscription-content"></div>
        </div>

        <form id="submission-form" style="display: none;">
            <div class="form-section">
                <h2>Release Type</h2>
                <div class="type-selector">
                    <div class="type-option selected" data-type="single">
                        <i class="mdi mdi-music-note"></i>
                        <div class="name">Single</div>
                    </div>
                    <div class="type-option" data-type="ep">
                        <i class="mdi mdi-album"></i>
                        <div class="name">EP</div>
                    </div>
                    <div class="type-option" data-type="album">
                        <i class="mdi mdi-disc"></i>
                        <div class="name">Album</div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h2>Basic Information</h2>
                <div class="form-group">
                    <label>Release Title *</label>
                    <input type="text" name="title" required placeholder="Enter your release title">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Artist Name *</label>
                        <input type="text" name="artist_name" required placeholder="Artist or band name">
                    </div>
                    <div class="form-group">
                        <label>Genre *</label>
                        <select name="genre" required>
                            <option value="">Select Genre</option>
                            <option value="pop">Pop</option>
                            <option value="rock">Rock</option>
                            <option value="hip-hop">Hip-Hop</option>
                            <option value="rnb">R&B</option>
                            <option value="electronic">Electronic</option>
                            <option value="classical">Classical</option>
                            <option value="jazz">Jazz</option>
                            <option value="folk">Folk</option>
                            <option value="reggae">Reggae</option>
                            <option value="latin">Latin</option>
                            <option value="country">Country</option>
                            <option value="indian">Indian</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Release Date *</label>
                        <input type="date" name="release_date" required>
                    </div>
                    <div class="form-group">
                        <label>Language</label>
                        <select name="language">
                            <option value="english">English</option>
                            <option value="hindi">Hindi</option>
                            <option value="punjabi">Punjabi</option>
                            <option value="tamil">Tamil</option>
                            <option value="telugu">Telugu</option>
                            <option value="marathi">Marathi</option>
                            <option value="bengali">Bengali</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3" placeholder="Describe your release (optional)"></textarea>
                </div>
            </div>

            <div class="form-section">
                <h2>Cover Art</h2>
                <div class="file-upload" id="cover-upload">
                    <i class="mdi mdi-image"></i>
                    <p>Click to upload cover art (3000x3000px, JPG/PNG)</p>
                    <input type="file" name="cover_art" accept="image/jpeg,image/png">
                </div>
            </div>

            <div class="form-section">
                <h2>Tracks</h2>
                <div id="tracks-container">
                    <div class="track-item">
                        <h3>Track 1</h3>
                        <div class="form-group">
                            <label>Track Title *</label>
                            <input type="text" name="track_title[]" required placeholder="Track title">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Duration (mm:ss)</label>
                                <input type="text" name="track_duration[]" placeholder="3:45">
                            </div>
                            <div class="form-group">
                                <label>ISRC (optional)</label>
                                <input type="text" name="track_isrc[]" placeholder="ISRC code">
                            </div>
                        </div>
                        <div class="file-upload" id="audio-upload-0">
                            <i class="mdi mdi-music"></i>
                            <p>Click to upload audio file (WAV, FLAC, MP3)</p>
                            <input type="file" name="track_audio[]" accept="audio/*">
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-secondary btn-sm add-track-btn" id="add-track-btn">+ Add Another Track</button>
            </div>

            <div class="form-section">
                <h2>Distribution Platforms</h2>
                <p class="muted" style="margin-bottom: 15px;">Select platforms where you want your music distributed</p>
                <div class="platform-selector">
                    <div class="platform-option selected">Spotify</div>
                    <div class="platform-option selected">Apple Music</div>
                    <div class="platform-option selected">YouTube Music</div>
                    <div class="platform-option selected">Amazon Music</div>
                    <div class="platform-option">Deezer</div>
                    <div class="platform-option">Tidal</div>
                    <div class="platform-option">Pandora</div>
                    <div class="platform-option">Instagram</div>
                    <div class="platform-option">TikTok</div>
                    <div class="platform-option">Snapchat</div>
                </div>
            </div>

            <div class="form-section">
                <h2>Additional Information</h2>
                <div class="form-group">
                    <label>Label Name (optional)</label>
                    <input type="text" name="label_name" placeholder="Your label name">
                </div>
                <div class="form-group">
                    <label>Recording Location</label>
                    <input type="text" name="recording_location" placeholder="City, Country">
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block submit-btn" style="padding:15px;font-size:16px">Submit for Review</button>
        </form>
    </div>

    <script>
    let trackCount = 1;
    let selectedType = 'single';
    let selectedPlatforms = ['spotify', 'apple-music', 'youtube-music', 'amazon-music'];

    // BOF session headers (stored in localStorage by the app on login)
    const apiHeaders = {
        'x-bof-request-code': 'BusyOwlFrameWorkVersion201',
        'x-bof-platform': 'web',
        'x-bof-version': '2074'
    };
    distAuth.headers(apiHeaders);

    // Check subscription status
    document.addEventListener('DOMContentLoaded', function() {
        fetch('/api/dist/plans', {
            headers: apiHeaders
        })
        .then(response => response.json())
        .then(data => {
            document.querySelector('.loading').classList.remove('active');
            if (data.success && data.user_subscription) {
                document.getElementById('subscription-content').innerHTML = `
                    <p class="alert alert-success" style="margin:0">
                        <i class="mdi mdi-check-circle"></i>
                        Active Subscription: <strong>${data.user_subscription.plan_name}</strong>
                    </p>
                `;
                document.getElementById('submission-form').style.display = 'block';
            } else if (data.success && !data.logged_in) {
                document.getElementById('subscription-content').innerHTML = `
                    <div style="text-align: center; padding: 30px;">
                        <h3 style="margin-bottom: 15px;">Login Required</h3>
                        <p class="muted" style="margin-bottom: 20px;">Please login to submit music for distribution.</p>
                        <a href="${distAuth.loginUrl()}" class="btn btn-primary">Login</a>
                    </div>
                `;
            } else {
                document.getElementById('subscription-content').innerHTML = `
                    <div style="text-align: center; padding: 30px;">
                        <h3 style="margin-bottom: 15px;">No Active Subscription</h3>
                        <p class="muted" style="margin-bottom: 20px;">You need an active subscription to submit music for distribution.</p>
                        <a href="/" class="btn btn-primary">View Plans</a>
                    </div>
                `;
            }
        })
        .catch(error => {
            document.querySelector('.loading').classList.remove('active');
            document.getElementById('subscription-content').innerHTML = '<p class="alert alert-error">Error checking subscription. Please try again.</p>';
        });
    });

    // Type selector
    document.querySelectorAll('.type-option').forEach(option => {
        option.addEventListener('click', function() {
            document.querySelectorAll('.type-option').forEach(o => o.classList.remove('selected'));
            this.classList.add('selected');
            selectedType = this.dataset.type;
        });
    });

    // Platform selector
    document.querySelectorAll('.platform-option').forEach(option => {
        option.addEventListener('click', function() {
            this.classList.toggle('selected');
        });
    });

    // Add track
    document.getElementById('add-track-btn').addEventListener('click', function() {
        trackCount++;
        const trackHtml = `
            <div class="track-item">
                <h3>Track ${trackCount}</h3>
                <div class="form-group">
                    <label>Track Title *</label>
                    <input type="text" name="track_title[]" required placeholder="Track title">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Duration (mm:ss)</label>
                        <input type="text" name="track_duration[]" placeholder="3:45">
                    </div>
                    <div class="form-group">
                        <label>ISRC (optional)</label>
                        <input type="text" name="track_isrc[]" placeholder="ISRC code">
                    </div>
                </div>
                <div class="file-upload">
                    <i class="mdi mdi-music"></i>
                    <p>Click to upload audio file (WAV, FLAC, MP3)</p>
                    <input type="file" name="track_audio[]" accept="audio/*">
                </div>
                <div class="remove-track" onclick="this.parentElement.remove()">Remove Track</div>
            </div>
        `;
        document.getElementById('tracks-container').insertAdjacentHTML('beforeend', trackHtml);
    });

    // File upload handlers (delegated so dynamically added tracks work too)
    document.addEventListener('click', function(e) {
        if (e.target.matches('input[type="file"]')) return;
        const upload = e.target.closest('.file-upload');
        if (!upload) return;
        const input = upload.querySelector('input[type="file"]');
        if (input) input.click();
    });
    document.addEventListener('change', function(e) {
        const input = e.target.closest('.file-upload input[type="file"]');
        if (!input || !input.files.length) return;
        const upload = input.closest('.file-upload');
        upload.querySelector('p').textContent = input.files[0].name;
        upload.style.borderColor = '#4caf50';
    });

    // Form submission
    document.getElementById('submission-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData();
        formData.append('type', selectedType);
        formData.append('title', this.querySelector('[name="title"]').value);
        formData.append('artist_name', this.querySelector('[name="artist_name"]').value);
        formData.append('genre', this.querySelector('[name="genre"]').value);
        formData.append('release_date', this.querySelector('[name="release_date"]').value);
        formData.append('language', this.querySelector('[name="language"]').value);
        formData.append('description', this.querySelector('[name="description"]').value);
        formData.append('label_name', this.querySelector('[name="label_name"]').value);
        formData.append('recording_location', this.querySelector('[name="recording_location"]').value);
        
        const coverFile = this.querySelector('[name="cover_art"]').files[0];
        if (coverFile) formData.append('cover_art', coverFile);
        
        const trackTitles = this.querySelectorAll('[name="track_title[]"]');
        trackTitles.forEach((title, index) => {
            formData.append('tracks[' + index + '][title]', title.value);
            formData.append('tracks[' + index + '][duration]', this.querySelectorAll('[name="track_duration[]"]')[index].value);
            formData.append('tracks[' + index + '][isrc]', this.querySelectorAll('[name="track_isrc[]"]')[index].value);
            const audioFile = this.querySelectorAll('[name="track_audio[]"]')[index].files[0];
            if (audioFile) formData.append('tracks[' + index + '][audio]', audioFile);
        });

        const platforms = [];
        document.querySelectorAll('.platform-option.selected').forEach(p => {
            platforms.push(p.textContent.toLowerCase().replace(/ /g, '-'));
        });
        formData.append('platforms', JSON.stringify(platforms));

        const submitBtn = this.querySelector('.submit-btn');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting...';

        fetch('/api/dist/submit', {
            method: 'POST',
            headers: apiHeaders,
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Submit for Review';
            if (data.success || data.message === 'ok') {
                alert('Submission successful! Your music has been submitted for review.');
                window.location.href = '/dashboard';
            } else {
                let errMsg = (data.messages && data.messages.join) ? data.messages.join(', ') : (data.message || 'Submission failed');
                if (data.code === 'release_limit_reached')
                    errMsg = `Release limit reached: your plan allows ${data.limit} releases (${data.used} used). Upgrade your plan to submit more.`;
                else if (data.code === 'no_subscription')
                    errMsg = 'You need an active distribution plan. Redirecting to plans...';
                if (errMsg === '403' || errMsg.indexOf('Unauthorized') !== -1) {
                    window.location.href = distAuth.loginUrl();
                    return;
                }
                alert('Error: ' + errMsg);
                if (data.code === 'no_subscription') window.location.href = '/';
            }
        })
        .catch(error => {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Submit for Review';
            alert('Error submitting. Please try again.');
        });
    });
    </script>
</body>
</html>
