<?php
/**
 * Music Distribution - User Dashboard
 */

if (!defined('bof_root')) die('Direct access not allowed');

class page_dist_dashboard extends bof_page {

    public function load() {
        
        $page = [
            'name' => 'dist_dashboard',
            'title' => 'My Distribution Dashboard',
            'slug' => 'distribution/dashboard',
            'require_login' => true,
            'layout' => 'fullwidth',
            'seo' => [
                'description' => 'Manage your music distribution submissions and track earnings'
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
.dist-dashboard {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    flex-wrap: wrap;
    gap: 20px;
}
.dashboard-header h1 {
    font-size: 28px;
    font-weight: 700;
}

.btn-primary {
    background: rgba(var(--theme_color), 1);
    color: #fff;
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
}
.btn-primary:hover {
    opacity: 0.9;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}
.stat-card {
    background: rgba(var(--bg_color), 1);
    border: 1px solid rgba(var(--font_color), 0.1);
    border-radius: 12px;
    padding: 20px;
    text-align: center;
}
.stat-card h3 {
    font-size: 32px;
    font-weight: 700;
    color: rgba(var(--theme_color), 1);
    margin-bottom: 8px;
}
.stat-card p {
    opacity: 0.7;
    font-size: 14px;
}
.stat-card.earnings h3 {
    color: #4CAF50;
}

.subscription-banner {
    background: linear-gradient(135deg, rgba(var(--theme_color), 0.1), rgba(var(--theme_color2), 0.1));
    border: 1px solid rgba(var(--theme_color), 0.3);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}
.subscription-banner.inactive {
    background: rgba(244, 67, 54, 0.1);
    border-color: rgba(244, 67, 54, 0.3);
}
.subscription-info h3 {
    font-size: 18px;
    margin-bottom: 5px;
}
.subscription-info p {
    opacity: 0.7;
    font-size: 14px;
}
.subscription-days {
    background: rgba(var(--theme_color), 1);
    color: #fff;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 600;
}

.section-title {
    font-size: 20px;
    font-weight: 600;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.submissions-list {
    background: rgba(var(--bg_color), 1);
    border: 1px solid rgba(var(--font_color), 0.1);
    border-radius: 12px;
    overflow: hidden;
}
.submission-item {
    display: flex;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid rgba(var(--font_color), 0.05);
    gap: 20px;
}
.submission-item:last-child {
    border-bottom: none;
}
.submission-cover {
    width: 80px;
    height: 80px;
    background: rgba(var(--bg_color2), 0.5);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}
.submission-cover img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.submission-cover .mdi {
    font-size: 32px;
    opacity: 0.3;
}
.submission-info {
    flex: 1;
}
.submission-info h4 {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 5px;
}
.submission-info p {
    font-size: 14px;
    opacity: 0.7;
    margin-bottom: 3px;
}
.status-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}
.status-submitted { background: rgba(255, 152, 0, 0.2); color: #FF9800; }
.status-in_review { background: rgba(33, 150, 243, 0.2); color: #2196F3; }
.status-in_progress { background: rgba(156, 39, 176, 0.2); color: #9C27B0; }
.status-approved { background: rgba(76, 175, 80, 0.2); color: #4CAF50; }
.status-launched { background: rgba(0, 150, 136, 0.2); color: #009688; }
.status-rejected { background: rgba(244, 67, 54, 0.2); color: #F44336; }

.tunecore-link {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    color: rgba(var(--theme_color), 1);
    font-size: 13px;
    text-decoration: none;
    margin-top: 5px;
}
.tunecore-link:hover {
    text-decoration: underline;
}

.filter-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}
.filter-tab {
    padding: 10px 20px;
    background: transparent;
    border: 1px solid rgba(var(--font_color), 0.2);
    border-radius: 25px;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.3s;
}
.filter-tab:hover,
.filter-tab.active {
    background: rgba(var(--theme_color), 1);
    border-color: rgba(var(--theme_color), 1);
    color: #fff;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
}
.empty-state .mdi {
    font-size: 64px;
    opacity: 0.2;
    margin-bottom: 20px;
}
.empty-state h3 {
    font-size: 20px;
    margin-bottom: 10px;
}
.empty-state p {
    opacity: 0.7;
    margin-bottom: 20px;
}

.royalty-section {
    margin-top: 40px;
}
.royalty-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}
.royalty-card {
    background: rgba(var(--bg_color), 1);
    border: 1px solid rgba(var(--font_color), 0.1);
    border-radius: 12px;
    padding: 20px;
}
.royalty-card h4 {
    font-size: 14px;
    opacity: 0.7;
    margin-bottom: 10px;
}
.royalty-card .amount {
    font-size: 28px;
    font-weight: 700;
    color: #4CAF50;
}
.royalty-card.streams .amount {
    color: rgba(var(--theme_color), 1);
}

.loading {
    text-align: center;
    padding: 40px;
    opacity: 0.5;
}

@media (max-width: 768px) {
    .dashboard-header {
        flex-direction: column;
        align-items: flex-start;
    }
    .submission-item {
        flex-wrap: wrap;
    }
    .submission-cover {
        width: 60px;
        height: 60px;
    }
    .subscription-banner {
        flex-direction: column;
        text-align: center;
    }
}
</style>

<div class="dist-dashboard">
    <div class="dashboard-header">
        <h1>My Distribution Dashboard</h1>
        <a href="/distribution/submit" class="btn-primary">
            <span class="mdi mdi-plus"></span>
            New Release
        </a>
    </div>

    <div id="subscription-banner" class="subscription-banner">
        <div class="loading">Loading subscription...</div>
    </div>

    <div class="stats-grid" id="stats-grid">
        <div class="stat-card">
            <h3 id="stat-total">-</h3>
            <p>Total Submissions</p>
        </div>
        <div class="stat-card">
            <h3 id="stat-published">-</h3>
            <p>Published</p>
        </div>
        <div class="stat-card">
            <h3 id="stat-review">-</h3>
            <p>In Review</p>
        </div>
        <div class="stat-card earnings">
            <h3 id="stat-earnings">$0.00</h3>
            <p>Total Earnings</p>
        </div>
    </div>

    <div class="section-title">
        <span>My Submissions</span>
    </div>

    <div class="filter-tabs">
        <button class="filter-tab active" data-filter="all">All</button>
        <button class="filter-tab" data-filter="submitted,in_review,in_progress">In Review</button>
        <button class="filter-tab" data-filter="approved,launched">Published</button>
        <button class="filter-tab" data-filter="rejected">Rejected</button>
    </div>

    <div class="submissions-list" id="submissions-list">
        <div class="loading">Loading submissions...</div>
    </div>

    <div class="royalty-section">
        <div class="section-title">
            <span>Royalty Reports</span>
        </div>
        <div class="royalty-summary" id="royalty-summary">
            <div class="royalty-card">
                <h4>Total Earnings</h4>
                <div class="amount" id="total-earnings">$0.00</div>
            </div>
            <div class="royalty-card streams">
                <h4>Total Streams</h4>
                <div class="amount" id="total-streams">0</div>
            </div>
            <div class="royalty-card">
                <h4>Last Report</h4>
                <div class="amount" id="last-report">-</div>
            </div>
        </div>
    </div>
</div>

<script>
let allSubmissions = [];
let currentFilter = 'all';

async function loadDashboard() {
    try {
        const response = await fetch('<?php echo $endpoint; ?>dist/dashboard', {
            headers: {
                'Authorization': 'Bearer ' + (window.app?.user?.token || '')
            }
        });
        const data = await response.json();
        
        if (data.success) {
            renderSubscription(data.data.subscription);
            renderStats(data.data.stats);
            allSubmissions = data.data.submissions;
            renderSubmissions();
            renderRoyalties(data.data.royalties);
        }
    } catch (error) {
        console.error('Error loading dashboard:', error);
    }
}

function renderSubscription(sub) {
    const banner = document.getElementById('subscription-banner');
    
    if (!sub || !sub.is_active) {
        banner.className = 'subscription-banner inactive';
        banner.innerHTML = `
            <div class="subscription-info">
                <h3>No Active Plan</h3>
                <p>Subscribe to a plan to start distributing your music</p>
            </div>
            <a href="/distribution" class="btn-primary">View Plans</a>
        `;
        return;
    }
    
    banner.innerHTML = `
        <div class="subscription-info">
            <h3>${sub.plan}</h3>
            <p>Active until ${new Date(sub.end_date).toLocaleDateString()}</p>
        </div>
        <div class="subscription-days">${sub.days_remaining} days left</div>
    `;
}

function renderStats(stats) {
    document.getElementById('stat-total').textContent = stats.total_submissions;
    document.getElementById('stat-published').textContent = stats.published;
    document.getElementById('stat-review').textContent = stats.in_review;
}

function renderSubmissions() {
    const container = document.getElementById('submissions-list');
    
    let filtered = allSubmissions;
    if (currentFilter !== 'all') {
        const statuses = currentFilter.split(',');
        filtered = allSubmissions.filter(s => statuses.includes(s.status));
    }
    
    if (filtered.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <span class="mdi mdi-music-off"></span>
                <h3>No submissions yet</h3>
                <p>Start your music distribution journey today</p>
                <a href="/distribution/submit" class="btn-primary">
                    <span class="mdi mdi-plus"></span> Submit Music
                </a>
            </div>
        `;
        return;
    }
    
    container.innerHTML = filtered.map(sub => `
        <div class="submission-item">
            <div class="submission-cover">
                ${sub.cover_art ? `<img src="${sub.cover_art}" alt="${sub.title}">` : '<span class="mdi mdi-music"></span>'}
            </div>
            <div class="submission-info">
                <h4>${sub.title}</h4>
                <p>${sub.artist_name} ${sub.album_name ? '• ' + sub.album_name : ''}</p>
                <p>${sub.type.toUpperCase()} • ${sub.genre || 'No Genre'}</p>
                ${sub.tunecore_url ? `
                    <a href="${sub.tunecore_url}" target="_blank" class="tunecore-link">
                        <span class="mdi mdi-link-variant"></span> View on TuneCore
                    </a>
                ` : ''}
            </div>
            <div>
                <span class="status-badge status-${sub.status}">${sub.status_label}</span>
                <p style="font-size: 12px; opacity: 0.5; margin-top: 8px;">
                    ${new Date(sub.submitted_at).toLocaleDateString()}
                </p>
            </div>
        </div>
    `).join('');
}

function renderRoyalties(royalties) {
    document.getElementById('stat-earnings').textContent = '$' + royalties.total_earned.toFixed(2);
    document.getElementById('total-earnings').textContent = '$' + royalties.total_earned.toFixed(2);
    document.getElementById('total-streams').textContent = royalties.total_streams.toLocaleString();
    
    const lastReport = royalties.reports[0];
    document.getElementById('last-report').textContent = lastReport ? lastReport.month : '-';
}

// Filter tabs
document.querySelectorAll('.filter-tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        currentFilter = tab.dataset.filter;
        renderSubmissions();
    });
});

// Load on page load
document.addEventListener('DOMContentLoaded', loadDashboard);
</script>

        <?php
        return ob_get_clean();
    }

}
