<?php
/**
 * Music Distribution - Admin Panel
 * Manage submissions, royalties, and distribution workflow
 */

if (!defined('bof_base')) die('Direct access not allowed');

class page_dist_admin extends bof_admin_page {

    public function load() {
        
        $page = [
            'name' => 'dist_admin',
            'title' => 'Music Distribution Admin',
            'slug' => 'distribution-admin',
            'icon' => 'mdi-music-circle',
            'position' => 30,
            'capabilities' => ['admin']
        ];

        return $page;
    }

    public function body() {
        
        $endpoint = $this->loader->endpoint_address;
        
        ob_start();
        ?>

<style>
.dist-admin {
    padding: 20px;
}

.admin-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}
.admin-header h1 {
    font-size: 24px;
    font-weight: 600;
}

.stats-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}
.stat-box {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
.stat-box h3 {
    font-size: 28px;
    font-weight: 700;
    color: #667eea;
    margin-bottom: 5px;
}
.stat-box p {
    color: #666;
    font-size: 14px;
}
.stat-box.submitted { border-left: 4px solid #FF9800; }
.stat-box.review { border-left: 4px solid #2196F3; }
.stat-box.progress { border-left: 4px solid #9C27B0; }
.stat-box.launched { border-left: 4px solid #4CAF50; }

.admin-tabs {
    display: flex;
    gap: 5px;
    margin-bottom: 20px;
    border-bottom: 2px solid #eee;
}
.admin-tab {
    padding: 12px 24px;
    background: transparent;
    border: none;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    color: #666;
    position: relative;
}
.admin-tab:hover {
    color: #667eea;
}
.admin-tab.active {
    color: #667eea;
}
.admin-tab.active::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    right: 0;
    height: 2px;
    background: #667eea;
}

.filter-bar {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}
.filter-bar input,
.filter-bar select {
    padding: 10px 15px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 14px;
}
.filter-bar input {
    min-width: 250px;
}
.btn-refresh {
    padding: 10px 20px;
    background: #667eea;
    color: #fff;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 5px;
}

.submissions-table {
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
.submissions-table table {
    width: 100%;
    border-collapse: collapse;
}
.submissions-table th {
    background: #f8f9fa;
    padding: 15px;
    text-align: left;
    font-weight: 600;
    font-size: 13px;
    text-transform: uppercase;
    color: #666;
}
.submissions-table td {
    padding: 15px;
    border-bottom: 1px solid #eee;
    vertical-align: top;
}
.submissions-table tr:hover {
    background: #f8f9fa;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 10px;
}
.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #667eea;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 600;
}
.user-details {
    line-height: 1.4;
}
.user-details strong {
    display: block;
}
.user-details small {
    color: #666;
}

.release-info h4 {
    font-weight: 600;
    margin-bottom: 4px;
}
.release-info p {
    font-size: 13px;
    color: #666;
}

.status-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}
.status-submitted { background: #FFF3E0; color: #E65100; }
.status-in_review { background: #E3F2FD; color: #1565C0; }
.status-in_progress { background: #F3E5F5; color: #7B1FA2; }
.status-approved { background: #E8F5E9; color: #2E7D32; }
.status-launched { background: #E0F2F1; color: #00695C; }
.status-rejected { background: #FFEBEE; color: #C62828; }

.btn-action {
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 500;
}
.btn-view {
    background: #667eea;
    color: #fff;
}

/* Modal */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.6);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}
.modal-overlay.active {
    display: flex;
}
.modal-content {
    background: #fff;
    border-radius: 16px;
    width: 90%;
    max-width: 700px;
    max-height: 90vh;
    overflow: auto;
}
.modal-header {
    padding: 20px 25px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.modal-header h3 {
    font-size: 18px;
    font-weight: 600;
}
.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
}
.modal-body {
    padding: 25px;
}
.modal-footer {
    padding: 20px 25px;
    border-top: 1px solid #eee;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    margin-bottom: 20px;
}
.form-group {
    margin-bottom: 20px;
}
.form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
    font-size: 14px;
}
.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 14px;
}
.form-group textarea {
    resize: vertical;
    min-height: 100px;
}

.status-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}
.status-btn {
    padding: 10px 20px;
    border: 2px solid #ddd;
    background: #fff;
    border-radius: 8px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 500;
    transition: all 0.3s;
}
.status-btn:hover,
.status-btn.active {
    border-color: #667eea;
    background: #667eea;
    color: #fff;
}

.preview-section {
    display: grid;
    grid-template-columns: 150px 1fr;
    gap: 20px;
    margin-bottom: 25px;
}
.cover-preview {
    width: 150px;
    height: 150px;
    background: #f0f0f0;
    border-radius: 8px;
    overflow: hidden;
}
.cover-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.audio-player {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
}
.audio-player audio {
    width: 100%;
}

.tracks-list {
    margin-top: 15px;
}
.track-row {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 10px 0;
    border-bottom: 1px solid #eee;
}
.track-row:last-child {
    border-bottom: none;
}
.track-num {
    width: 30px;
    height: 30px;
    background: #667eea;
    color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 600;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 30px;
}
.pagination button {
    padding: 10px 20px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    cursor: pointer;
}
.pagination button:hover {
    background: #667eea;
    color: #fff;
    border-color: #667eea;
}
.pagination button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

@media (max-width: 768px) {
    .filter-bar {
        flex-direction: column;
    }
    .submissions-table {
        overflow-x: auto;
    }
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="dist-admin">
    <div class="admin-header">
        <h1>Music Distribution Admin</h1>
    </div>

    <div class="stats-cards" id="stats-cards">
        <div class="stat-box submitted">
            <h3 id="stat-submitted">0</h3>
            <p>New Submissions</p>
        </div>
        <div class="stat-box review">
            <h3 id="stat-review">0</h3>
            <p>In Review</p>
        </div>
        <div class="stat-box progress">
            <h3 id="stat-progress">0</h3>
            <p>In Progress</p>
        </div>
        <div class="stat-box launched">
            <h3 id="stat-launched">0</h3>
            <p>Launched</p>
        </div>
    </div>

    <div class="admin-tabs">
        <button class="admin-tab active" data-tab="submissions" onclick="switchTab('submissions')">Submissions</button>
        <button class="admin-tab" data-tab="royalties" onclick="switchTab('royalties')">Royalties</button>
        <button class="admin-tab" data-tab="logs" onclick="switchTab('logs')">Activity Logs</button>
    </div>

    <div id="submissions-tab">
        <div class="filter-bar">
            <select id="status-filter" onchange="loadSubmissions()">
                <option value="">All Status</option>
                <option value="submitted">Submitted</option>
                <option value="in_review">In Review</option>
                <option value="in_progress">In Progress</option>
                <option value="approved">Approved</option>
                <option value="launched">Launched</option>
                <option value="rejected">Rejected</option>
            </select>
            <select id="type-filter" onchange="loadSubmissions()">
                <option value="">All Types</option>
                <option value="single">Single</option>
                <option value="ep">EP</option>
                <option value="album">Album</option>
            </select>
            <input type="text" id="search-input" placeholder="Search by title, artist, or user..." onkeyup="if(event.key==='Enter')loadSubmissions()">
            <button class="btn-refresh" onclick="loadSubmissions()">
                <span class="mdi mdi-refresh"></span> Refresh
            </button>
        </div>

        <div class="submissions-table">
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Release</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="submissions-list">
                    <tr><td colspan="6" style="text-align: center; padding: 40px;">Loading...</td></tr>
                </tbody>
            </table>
        </div>

        <div class="pagination" id="pagination"></div>
    </div>

    <div id="royalties-tab" style="display: none;">
        <div class="filter-bar">
            <input type="month" id="royalty-month" onchange="loadRoyalties()">
            <button class="btn-refresh" onclick="loadRoyalties()">
                <span class="mdi mdi-refresh"></span> Refresh
            </button>
            <button class="btn-refresh" onclick="showAddRoyaltyModal()" style="background: #4CAF50;">
                <span class="mdi mdi-plus"></span> Add Royalty
            </button>
        </div>
        
        <div class="submissions-table">
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Song</th>
                        <th>Month</th>
                        <th>Platform</th>
                        <th>Streams</th>
                        <th>Revenue</th>
                        <th>Paid</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="royalties-list">
                    <tr><td colspan="8" style="text-align: center; padding: 40px;">Select a month to view royalties</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div id="logs-tab" style="display: none;">
        <div class="submissions-table">
            <table>
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody id="logs-list">
                    <tr><td colspan="4" style="text-align: center; padding: 40px;">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Submission Detail Modal -->
<div class="modal-overlay" id="submission-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Submission Details</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body" id="modal-body">
            Loading...
        </div>
        <div class="modal-footer">
            <button class="btn-action" onclick="closeModal()">Close</button>
            <button class="btn-action btn-view" onclick="saveSubmission()">Save Changes</button>
        </div>
    </div>
</div>

<script>
let currentSubmission = null;
let currentPage = 1;

async function loadSubmissions(page = 1) {
    currentPage = page;
    const status = document.getElementById('status-filter').value;
    const type = document.getElementById('type-filter').value;
    const search = document.getElementById('search-input').value;
    
    let url = '<?php echo $endpoint; ?>be/dist/admin/submissions?page=' + page;
    if (status) url += '&status=' + status;
    if (type) url += '&type=' + type;
    if (search) url += '&search=' + encodeURIComponent(search);
    
    try {
        const response = await fetch(url, {
            headers: {
                'Authorization': 'Bearer ' + (window.app?.user?.token || '')
            }
        });
        const data = await response.json();
        
        if (data.success) {
            renderSubmissions(data.data.submissions);
            renderPagination(data.data.pagination);
            updateStats(data.data.status_counts);
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

function renderSubmissions(submissions) {
    const tbody = document.getElementById('submissions-list');
    
    if (submissions.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 40px;">No submissions found</td></tr>';
        return;
    }
    
    tbody.innerHTML = submissions.map(sub => `
        <tr>
            <td>
                <div class="user-info">
                    <div class="user-avatar">${sub.user.username?.[0]?.toUpperCase() || '?'}</div>
                    <div class="user-details">
                        <strong>${sub.user.username}</strong>
                        <small>${sub.user.email}</small>
                    </div>
                </div>
            </td>
            <td>
                <div class="release-info">
                    <h4>${sub.title}</h4>
                    <p>${sub.artist_name}</p>
                </div>
            </td>
            <td>${sub.type.toUpperCase()}</td>
            <td><span class="status-badge status-${sub.status}">${sub.status.replace('_', ' ')}</span></td>
            <td>${new Date(sub.submitted_at).toLocaleDateString()}</td>
            <td>
                <button class="btn-action btn-view" onclick="viewSubmission(${sub.id})">View</button>
            </td>
        </tr>
    `).join('');
}

function renderPagination(pagination) {
    const container = document.getElementById('pagination');
    let html = '';
    
    if (pagination.current_page > 1) {
        html += `<button onclick="loadSubmissions(${pagination.current_page - 1})">Previous</button>`;
    }
    
    html += `<span style="padding: 10px;">Page ${pagination.current_page} of ${pagination.total_pages}</span>`;
    
    if (pagination.current_page < pagination.total_pages) {
        html += `<button onclick="loadSubmissions(${pagination.current_page + 1})">Next</button>`;
    }
    
    container.innerHTML = html;
}

function updateStats(counts) {
    document.getElementById('stat-submitted').textContent = counts.submitted || 0;
    document.getElementById('stat-review').textContent = (counts.in_review || 0) + (counts.submitted || 0);
    document.getElementById('stat-progress').textContent = counts.in_progress || 0;
    document.getElementById('stat-launched').textContent = counts.launched || 0;
}

function viewSubmission(id) {
    const row = document.querySelector(`button[onclick="viewSubmission(${id})"]`).closest('tr');
    const submission = {
        id: id,
        user: {
            username: row.querySelector('.user-details strong').textContent,
            email: row.querySelector('.user-details small').textContent
        },
        title: row.querySelector('.release-info h4').textContent,
        artist_name: row.querySelector('.release-info p').textContent,
        status: row.querySelector('.status-badge').textContent.replace(' ', '_')
    };
    
    currentSubmission = submission;
    
    document.getElementById('modal-body').innerHTML = `
        <div class="preview-section">
            <div class="cover-preview">
                <span class="mdi mdi-image" style="font-size: 48px; opacity: 0.3; display: flex; height: 100%; align-items: center; justify-content: center;"></span>
            </div>
            <div>
                <h4>${submission.title}</h4>
                <p>${submission.artist_name}</p>
                <p style="margin-top: 10px;">Submitted by: ${submission.user.username} (${submission.user.email})</p>
            </div>
        </div>
        
        <div class="form-group">
            <label>Update Status</label>
            <div class="status-buttons">
                ${['submitted', 'in_review', 'in_progress', 'approved', 'launched', 'rejected'].map(s => `
                    <button type="button" class="status-btn ${submission.status === s ? 'active' : ''}" 
                        onclick="selectStatus('${s}')">${s.replace('_', ' ')}</button>
                `).join('')}
            </div>
        </div>
        
        <div class="form-group">
            <label>TuneCore URL</label>
            <input type="url" id="tunecore-url" placeholder="https://...">
        </div>
        
        <div class="form-group">
            <label>Admin Notes</label>
            <textarea id="admin-notes" placeholder="Internal notes about this submission..."></textarea>
        </div>
        
        <input type="hidden" id="selected-status" value="${submission.status}">
    `;
    
    document.getElementById('submission-modal').classList.add('active');
}

function selectStatus(status) {
    document.querySelectorAll('.status-btn').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    document.getElementById('selected-status').value = status;
}

function closeModal() {
    document.getElementById('submission-modal').classList.remove('active');
    currentSubmission = null;
}

async function saveSubmission() {
    if (!currentSubmission) return;
    
    const status = document.getElementById('selected-status').value;
    const tunecoreUrl = document.getElementById('tunecore-url')?.value;
    const adminNotes = document.getElementById('admin-notes')?.value;
    
    try {
        const response = await fetch('<?php echo $endpoint; ?>be/dist/admin/submissions', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + (window.app?.user?.token || '')
            },
            body: JSON.stringify({
                submission_id: currentSubmission.id,
                status: status,
                tunecore_url: tunecoreUrl,
                admin_notes: adminNotes
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Submission updated successfully');
            closeModal();
            loadSubmissions(currentPage);
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to update submission');
    }
}

function switchTab(tab) {
    document.querySelectorAll('.admin-tab').forEach(t => t.classList.remove('active'));
    document.querySelector(`[data-tab="${tab}"]`).classList.add('active');
    
    document.getElementById('submissions-tab').style.display = tab === 'submissions' ? 'block' : 'none';
    document.getElementById('royalties-tab').style.display = tab === 'royalties' ? 'block' : 'none';
    document.getElementById('logs-tab').style.display = tab === 'logs' ? 'block' : 'none';
}

// Load on page load
document.addEventListener('DOMContentLoaded', loadSubmissions);
</script>

        <?php
        return ob_get_clean();
    }

}
