<?php
/**
 * Music Distribution - Dashboard Page (Standalone)
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Canonical host is the distribution subdomain; bounce legacy music.hitune.in URLs.
if (($_SERVER['HTTP_HOST'] ?? '') === 'music.hitune.in') {
    $q = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
    header('Location: https://distribution.hitune.in/dashboard' . $q, true, 301);
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
    <title>Distribution Dashboard | Hitune Music</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@6.5.95/css/materialdesignicons.min.css">
    <script src="/dist-auth.js"></script>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/dist-style.css">
    <style>
        .stats-grid { margin-top: 24px; }
        .empty-state h3 { font-size: 20px; margin-bottom: 8px; }
        .empty-state p { margin-bottom: 18px; }
        .section h2 { justify-content: space-between; }
    </style>
</head>
<body>
    <nav class="topnav">
        <div class="topnav-inner">
            <a class="brand" href="/"><span class="brand-dot"><span class="mdi mdi-music-note"></span></span>Hitune <small>Distribution</small></a>
            <div class="nav-links">
                <a href="/">Plans</a>
                <a href="/submit">Submit Music</a>
                <a href="/admin">Admin</a>
                <a href="https://music.hitune.in/">Hitune Music</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="section" style="margin-top:24px"><h2><span class="mdi mdi-view-dashboard"></span>Distribution Dashboard</h2>
        <p class="muted" style="margin-top:-8px">Your releases, earnings and subscription at a glance.</p></div>

        <div class="stats-grid stat-grid">
            <div class="stat-card">
                <i class="mdi mdi-music"></i>
                <div class="value">0</div>
                <div class="label">Total Releases</div>
            </div>
            <div class="stat-card">
                <i class="mdi mdi-clock"></i>
                <div class="value">0</div>
                <div class="label">Pending Review</div>
            </div>
            <div class="stat-card">
                <i class="mdi mdi-check-circle"></i>
                <div class="value">0</div>
                <div class="label">Approved</div>
            </div>
            <div class="stat-card">
                <i class="mdi mdi-currency-inr"></i>
                <div class="value">₹0</div>
                <div class="label">Total Royalties</div>
            </div>
        </div>

        <div class="section">
            <h2>Active Subscription</h2>
            <div id="subscription-content">
                <div class="empty-state">
                    <i class="mdi mdi-account-cash"></i>
                    <h3>No Active Subscription</h3>
                    <p>You need an active subscription to submit music for distribution.</p>
                    <a href="/" class="btn btn-primary">View Plans</a>
                </div>
            </div>
        </div>

        <div class="section">
            <h2>
                Recent Submissions
                <a href="/submit" class="btn btn-primary btn-sm">New Submission</a>
            </h2>
            <div id="submissions-content">
                <div class="empty-state">
                    <i class="mdi mdi-upload"></i>
                    <h3>No Submissions Yet</h3>
                    <p>Start by submitting your first music release.</p>
                    <a href="/submit" class="btn btn-primary">Submit Music</a>
                </div>
            </div>
        </div>

        <div class="section">
            <h2>Earnings &amp; Payouts</h2>
            <div class="balance-grid">
                <div class="balance-card avail"><div class="bv" id="bal-available">₹0</div><div class="bl">Available</div></div>
                <div class="balance-card"><div class="bv" id="bal-pending">₹0</div><div class="bl">Pending Payouts</div></div>
                <div class="balance-card"><div class="bv" id="bal-paid">₹0</div><div class="bl">Paid Out</div></div>
                <div class="balance-card"><div class="bv" id="bal-earned">₹0</div><div class="bl">Total Earned</div></div>
            </div>
            <button class="btn btn-primary" id="btn-request-payout">Request Payout</button>
            <details class="dist-details" style="margin-top:15px">
                <summary>Payout Settings (saved details)</summary>
                <div class="detail-grid" style="margin-top:12px">
                    <div class="form-group"><label>Stage / Artist Name</label><input id="pf-stage_name"></div>
                    <div class="form-group"><label>UPI ID</label><input id="pf-upi_id" placeholder="name@upi"></div>
                    <div class="form-group"><label>Account Holder</label><input id="pf-account_holder"></div>
                    <div class="form-group"><label>Account Number</label><input id="pf-account_number"></div>
                    <div class="form-group"><label>IFSC</label><input id="pf-ifsc" placeholder="SBIN0123456"></div>
                    <div class="form-group"><label>PayPal Email</label><input id="pf-paypal_email"></div>
                </div>
                <button class="btn btn-secondary btn-sm" id="pf-save" onclick="savePayoutProfile()">Save Payout Details</button>
                <div class="msg" id="pf-msg" style="margin-top:10px"></div>
            </details>
            <div id="payout-history" style="margin-top:20px"></div>
        </div>

        <div class="section">
            <h2>Monthly Royalty Reports</h2>
            <div id="royalty-reports">
                <div class="empty-state"><i class="mdi mdi-chart-line"></i><p>No royalty reports yet. Reports appear here after your music goes live and earns streams.</p></div>
            </div>
        </div>
    </div>

    <!-- Release detail modal -->
    <div class="modal-overlay" id="release-modal">
        <div class="modal">
            <span class="mclose modal-close" onclick="closeModal('release-modal')">&times;</span>
            <h3 id="rm-title">Release</h3>
            <div id="rm-body">Loading...</div>
        </div>
    </div>

    <!-- Payout request modal -->
    <div class="modal-overlay" id="payout-modal">
        <div class="modal">
            <span class="mclose modal-close" onclick="closeModal('payout-modal')">&times;</span>
            <h3>Request Payout</h3>
            <p class="muted" style="font-size:14px;margin-bottom:15px">Available balance: <b id="pm-amount">₹0</b>. The full available balance will be withdrawn. Minimum withdrawal ₹500.</p>
            <div class="msg" id="pm-msg"></div>
            <div class="form-group">
                <label>Payout Method</label>
                <select id="pm-method" onchange="prefillPayoutDetails()">
                    <option value="upi">UPI</option>
                    <option value="bank">Bank Transfer (IMPS/NEFT)</option>
                    <option value="paypal">PayPal</option>
                </select>
            </div>
            <div class="form-group">
                <label>Payment Details</label>
                <textarea id="pm-details" placeholder="UPI: yourname@upi&#10;Bank: Account no, IFSC, Account holder name&#10;PayPal: email address"></textarea>
            </div>
            <button class="btn btn-primary" id="pm-submit" onclick="submitPayout()">Submit Request</button>
        </div>
    </div>

    <!-- Edit release modal -->
    <div class="modal-overlay" id="edit-modal">
        <div class="modal">
            <span class="mclose modal-close" onclick="closeModal('edit-modal')">&times;</span>
            <h3>Edit Release Metadata</h3>
            <div class="msg" id="em-msg"></div>
            <input type="hidden" id="em-id">
            <div class="form-group"><label>Release Title *</label><input id="em-title"></div>
            <div class="form-group"><label>Artist Name *</label><input id="em-artist_name"></div>
            <div class="form-group"><label>Album Name</label><input id="em-album_name"></div>
            <div class="form-group"><label>Genre</label><input id="em-genre"></div>
            <div class="form-group"><label>Release Date</label><input id="em-release_date" type="date"></div>
            <div class="form-group"><label>Language</label><input id="em-language"></div>
            <div class="form-group"><label>ISRC</label><input id="em-isrc"></div>
            <div class="form-group"><label>UPC</label><input id="em-upc"></div>
            <div class="form-group"><label>Label Name</label><input id="em-label_name"></div>
            <div class="form-group"><label>Description</label><textarea id="em-description"></textarea></div>
            <button class="btn btn-primary" id="em-submit" onclick="submitEdit()">Save Changes</button>
        </div>
    </div>

    <script>
    // BOF session headers (stored in localStorage by the app on login)
    const apiHeaders = {
        'x-bof-request-code': 'BusyOwlFrameWorkVersion201',
        'x-bof-platform': 'web',
        'x-bof-version': '2074'
    };
    distAuth.headers(apiHeaders);

    // Load dashboard data from API
    document.addEventListener('DOMContentLoaded', function() {
        fetch('/api/dist/dashboard', {
            headers: apiHeaders
        })
        .then(response => response.json())
        .then(data => {
            const msgs = (data.messages || []).join(' ');
            if (data.message === '403' || msgs.indexOf('403') !== -1 || msgs.indexOf('Unauthorized') !== -1 || data.message === 'Unauthorized') {
                window.location.href = distAuth.loginUrl();
                return;
            }
            if (data.success || data.message === 'ok') {
                updateDashboard(data);
            }
        })
        .catch(error => console.error('Error loading dashboard:', error));
    });

    function updateDashboard(data) {
        // Update stats
        if (data.stats) {
            document.querySelector('.stat-card:nth-child(1) .value').textContent = data.stats.total_submissions || 0;
            document.querySelector('.stat-card:nth-child(2) .value').textContent = data.stats.in_review || 0;
            document.querySelector('.stat-card:nth-child(3) .value').textContent = data.stats.approved || 0;
            document.querySelector('.stat-card:nth-child(4) .value').textContent = '₹' + ((data.royalties && data.royalties.total_earned) || 0);
        }

        // Update subscription
        if (data.subscription) {
            if (data.subscription.is_active) {
                const sub = data.subscription;
                const usage = sub.releases_limit
                    ? `${sub.releases_used} / ${sub.releases_limit} releases used`
                    : `${sub.releases_used} releases (unlimited)`;
                document.getElementById('subscription-content').innerHTML = `
                    <div class="subscription-card">
                        <div class="plan-name">${sub.plan}</div>
                        <div class="plan-details">Active distribution plan · ${esc(usage)}</div>
                        <div class="expiry">Expires: ${sub.end_date || 'N/A'} (${sub.days_remaining || 0} days left)</div>
                        ${sub.can_renew ? `<a href="/subscribe/${sub.plan_slug}" class="btn btn-primary btn-sm" style="margin-top:12px">Renew Plan</a>` : ''}
                    </div>
                `;
            } else {
                document.getElementById('subscription-content').innerHTML = `
                    <div class="empty-state">
                        <i class="mdi mdi-account-cash"></i>
                        <h3>Subscription ${data.subscription.status}</h3>
                        <p>Your ${data.subscription.plan} subscription is ${data.subscription.status} (payment: ${data.subscription.payment_status}).</p>
                        <a href="/" class="btn btn-primary">View Plans</a>
                    </div>
                `;
            }
        }

        // Update submissions
        if (data.submissions && data.submissions.length > 0) {
            let tableHtml = `
                <table class="data-table submissions-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            data.submissions.forEach(sub => {
                tableHtml += `
                    <tr onclick="viewRelease(${sub.id})" title="View details">
                        <td>${sub.title}</td>
                        <td>${sub.type}</td>
                        <td><span class="status ${sub.status}">${sub.status.replace('_', ' ')}</span></td>
                        <td>${new Date(sub.submitted_at).toLocaleDateString()}</td>
                    </tr>
                `;
            });
            tableHtml += '</tbody></table>';
            document.getElementById('submissions-content').innerHTML = tableHtml;
        }

        // Payout balance
        if (data.payout_balance) {
            const b = data.payout_balance;
            document.getElementById('bal-available').textContent = '₹' + b.available;
            document.getElementById('bal-pending').textContent = '₹' + b.pending_payouts;
            document.getElementById('bal-paid').textContent = '₹' + b.total_paid;
            document.getElementById('bal-earned').textContent = '₹' + b.total_earned;
            document.getElementById('pm-amount').textContent = '₹' + b.available;
            document.getElementById('btn-request-payout').disabled = b.available < b.min_withdrawal;
            document.getElementById('btn-request-payout').textContent =
                b.available < b.min_withdrawal ? 'Request Payout (min ₹500)' : 'Request Payout';
        }

        // Royalty monthly reports
        if (data.royalties && data.royalties.reports && data.royalties.reports.length > 0) {
            let rh = '<table class="data-table submissions-table"><thead><tr><th>Month</th><th>Streams</th><th>Revenue</th></tr></thead><tbody>';
            data.royalties.reports.forEach(r => {
                rh += `<tr><td>${r.month}</td><td>${Number(r.streams).toLocaleString()}</td><td>₹${Number(r.revenue).toLocaleString()}</td></tr>`;
            });
            rh += '</tbody></table>';
            document.getElementById('royalty-reports').innerHTML = rh;
        }

        loadPayoutHistory();
    }

    function closeModal(id) { document.getElementById(id).classList.remove('show'); }

    function esc(s) { const d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }

    function viewRelease(id) {
        document.getElementById('rm-body').innerHTML = 'Loading...';
        document.getElementById('release-modal').classList.add('show');
        fetch('/api/dist/submission?id=' + id, { headers: apiHeaders })
            .then(r => r.json())
            .then(data => {
                if (!data.success) { document.getElementById('rm-body').innerHTML = 'Could not load release.'; return; }
                const s = data.submission;
                document.getElementById('rm-title').textContent = s.title;
                let h = '';
                h += `<div class="detail-row"><span class="k">Status</span><span><span class="status ${s.status}">${esc(s.status_label || s.status)}</span></span></div>`;
                h += `<div class="detail-row"><span class="k">Type</span><span>${esc(s.type)}</span></div>`;
                h += `<div class="detail-row"><span class="k">Artist</span><span>${esc(s.artist_name)}</span></div>`;
                if (s.album_name) h += `<div class="detail-row"><span class="k">Album</span><span>${esc(s.album_name)}</span></div>`;
                if (s.genre) h += `<div class="detail-row"><span class="k">Genre</span><span>${esc(s.genre)}</span></div>`;
                if (s.release_date) h += `<div class="detail-row"><span class="k">Release Date</span><span>${esc(s.release_date)}</span></div>`;
                if (s.upc) h += `<div class="detail-row"><span class="k">UPC</span><span>${esc(s.upc)}</span></div>`;
                if (s.label_name) h += `<div class="detail-row"><span class="k">Label</span><span>${esc(s.label_name)}</span></div>`;
                if (s.tunecore_status) h += `<div class="detail-row"><span class="k">Aggregator Status</span><span>${esc(s.tunecore_status)}</span></div>`;
                if (s.launch_date) h += `<div class="detail-row"><span class="k">Launch Date</span><span>${esc(s.launch_date)}</span></div>`;
                if (s.admin_notes) h += `<div class="detail-row"><span class="k">Admin Notes</span><span>${esc(s.admin_notes)}</span></div>`;
                if (s.platforms && s.platforms.length) h += `<div class="detail-row"><span class="k">Platforms</span><span>${s.platforms.map(esc).join(', ')}</span></div>`;

                if (data.tracks && data.tracks.length) {
                    h += '<h4 style="margin:18px 0 8px">Tracks</h4>';
                    data.tracks.forEach(t => {
                        h += `<div class="detail-row"><span class="k">#${t.track_number} ${esc(t.title)}</span><span>${t.isrc ? 'ISRC ' + esc(t.isrc) : ''}</span></div>`;
                    });
                }

                if (data.royalties && data.royalties.length) {
                    h += '<h4 style="margin:18px 0 8px">Royalties</h4>';
                    data.royalties.forEach(r => {
                        h += `<div class="detail-row"><span class="k">${esc(r.report_month)} · ${esc(r.platform)}</span><span>₹${Number(r.revenue).toLocaleString()} ${r.is_paid ? '(paid)' : ''}</span></div>`;
                    });
                }

                if (data.timeline && data.timeline.length) {
                    h += '<h4 style="margin:18px 0 8px">Status Timeline</h4><div class="timeline">';
                    data.timeline.forEach(t => {
                        h += `<div class="titem">${esc(t.details || t.action)}<div class="tt">${esc(t.time)}</div></div>`;
                    });
                    h += '</div>';
                }

                if (data.catalog_published)
                    h += `<div class="alert alert-success" style="margin-top:16px">Live on Hitune Music catalog (${(data.catalog_track_ids||[]).length} track(s) streamable)</div>`;

                if (data.takedown_requested)
                    h += `<div class="alert alert-error" style="margin-top:16px">Takedown requested${data.takedown_requested_at ? ' on ' + esc(data.takedown_requested_at) : ''} — pending admin action.</div>`;

                h += '<div style="display:flex;gap:10px;margin-top:20px">';
                if (data.can_edit) h += `<button class="btn btn-secondary" onclick="editRelease(${s.id})">Edit Metadata</button>`;
                if (data.can_resubmit) h += `<button class="btn btn-primary" onclick="resubmitRelease(${s.id}, this)">Resubmit for Review</button>`;
                if (data.can_takedown) h += `<button class="btn btn-danger" onclick="requestTakedown(${s.id}, this)">Request Takedown</button>`;
                h += '</div>';

                document.getElementById('rm-body').innerHTML = h;
            })
            .catch(() => { document.getElementById('rm-body').innerHTML = 'Error loading release.'; });
    }

    function editRelease(id) {
        fetch('/api/dist/submission?id=' + id, { headers: apiHeaders })
            .then(r => r.json())
            .then(data => {
                if (!data.success) return;
                const s = data.submission;
                document.getElementById('em-id').value = s.id;
                ['title','artist_name','album_name','genre','release_date','language','isrc','upc','label_name','description'].forEach(f => {
                    const el = document.getElementById('em-' + f);
                    if (el) el.value = s[f] || '';
                });
                document.getElementById('em-msg').className = 'msg';
                closeModal('release-modal');
                document.getElementById('edit-modal').classList.add('show');
            });
    }

    function submitEdit() {
        const btn = document.getElementById('em-submit');
        const msg = document.getElementById('em-msg');
        const fd = new FormData();
        fd.append('action', 'update');
        fd.append('id', document.getElementById('em-id').value);
        ['title','artist_name','album_name','genre','release_date','language','isrc','upc','label_name','description'].forEach(f => {
            const el = document.getElementById('em-' + f);
            if (el) fd.append(f, el.value);
        });
        btn.disabled = true; btn.textContent = 'Saving...';
        fetch('/api/dist/submission', { method: 'POST', headers: apiHeaders, body: fd })
            .then(r => r.json())
            .then(data => {
                btn.disabled = false; btn.textContent = 'Save Changes';
                if (data.success) {
                    msg.className = 'msg ok'; msg.textContent = 'Saved.';
                    setTimeout(() => { closeModal('edit-modal'); location.reload(); }, 800);
                } else {
                    msg.className = 'msg err'; msg.textContent = (data.messages || []).join(', ') || 'Save failed';
                }
            })
            .catch(() => { btn.disabled = false; btn.textContent = 'Save Changes'; msg.className = 'msg err'; msg.textContent = 'Network error.'; });
    }

    function requestTakedown(id, btn) {
        const reason = prompt('Reason for takedown (optional):');
        if (reason === null) return;
        if (!confirm('Request takedown of this release? Admin will pull it from platforms.')) return;
        btn.disabled = true; btn.textContent = 'Requesting...';
        const fd = new FormData();
        fd.append('action', 'takedown');
        fd.append('id', id);
        fd.append('reason', reason);
        fetch('/api/dist/submission', { method: 'POST', headers: apiHeaders, body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) { closeModal('release-modal'); location.reload(); }
                else { btn.disabled = false; btn.textContent = 'Request Takedown'; alert((data.messages || []).join(', ') || 'Failed'); }
            })
            .catch(() => { btn.disabled = false; btn.textContent = 'Request Takedown'; });
    }

    function resubmitRelease(id, btn) {
        if (!confirm('Resubmit this release for review?')) return;
        btn.disabled = true; btn.textContent = 'Resubmitting...';
        const fd = new FormData();
        fd.append('action', 'resubmit');
        fd.append('id', id);
        fetch('/api/dist/submission', { method: 'POST', headers: apiHeaders, body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) { closeModal('release-modal'); location.reload(); }
                else { btn.disabled = false; btn.textContent = 'Resubmit for Review'; alert((data.messages || []).join(', ') || 'Failed'); }
            })
            .catch(() => { btn.disabled = false; btn.textContent = 'Resubmit for Review'; });
    }

    function loadPayoutHistory() {
        fetch('/api/dist/payout', { headers: apiHeaders })
            .then(r => r.json())
            .then(data => {
                if (!data.success) return;
                if (data.balance) {
                    document.getElementById('bal-available').textContent = '₹' + data.balance.available;
                    document.getElementById('bal-pending').textContent = '₹' + data.balance.pending_payouts;
                    document.getElementById('bal-paid').textContent = '₹' + data.balance.total_paid;
                    document.getElementById('bal-earned').textContent = '₹' + data.balance.total_earned;
                    document.getElementById('pm-amount').textContent = '₹' + data.balance.available;
                }
                if (data.profile) {
                    window._payoutProfile = data.profile;
                    ['stage_name','upi_id','account_holder','account_number','ifsc','paypal_email'].forEach(f => {
                        const el = document.getElementById('pf-' + f);
                        if (el && data.profile[f]) el.value = data.profile[f];
                    });
                }
                if (data.history && data.history.length) {
                    let h = '<table class="data-table payout-table"><thead><tr><th>ID</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th></tr></thead><tbody>';
                    data.history.forEach(p => {
                        h += `<tr><td>#${p.id}</td><td>₹${Number(p.amount).toLocaleString()}</td><td>${esc(p.method)}</td><td><span class="status ${p.status}">${esc(p.status)}</span></td><td>${new Date(p.time_add).toLocaleDateString()}</td></tr>`;
                    });
                    h += '</tbody></table>';
                    document.getElementById('payout-history').innerHTML = h;
                }
            })
            .catch(() => {});
    }

    document.getElementById('btn-request-payout').addEventListener('click', function() {
        document.getElementById('pm-msg').className = 'msg';
        document.getElementById('payout-modal').classList.add('show');
        prefillPayoutDetails();
    });

    function prefillPayoutDetails() {
        const p = window._payoutProfile;
        if (!p) return;
        const method = document.getElementById('pm-method').value;
        let d = '';
        if (method === 'upi' && p.upi_id) d = 'UPI: ' + p.upi_id;
        else if (method === 'paypal' && p.paypal_email) d = 'PayPal: ' + p.paypal_email;
        else if (method === 'bank' && p.account_number)
            d = 'Bank: ' + (p.account_holder ? p.account_holder + ' / ' : '') + 'A/C ' + p.account_number + (p.ifsc ? ' / IFSC ' + p.ifsc : '');
        if (d) document.getElementById('pm-details').value = d;
    }

    function savePayoutProfile() {
        const btn = document.getElementById('pf-save');
        const msg = document.getElementById('pf-msg');
        const fd = new FormData();
        fd.append('action', 'profile');
        ['stage_name','upi_id','account_holder','account_number','ifsc','paypal_email'].forEach(f => {
            fd.append(f, document.getElementById('pf-' + f).value);
        });
        btn.disabled = true; btn.textContent = 'Saving...';
        fetch('/api/dist/payout', { method: 'POST', headers: apiHeaders, body: fd })
            .then(r => r.json())
            .then(data => {
                btn.disabled = false; btn.textContent = 'Save Payout Details';
                if (data.success) { msg.className = 'msg ok'; msg.textContent = 'Saved.'; }
                else { msg.className = 'msg err'; msg.textContent = (data.messages || []).join(', ') || 'Save failed'; }
            })
            .catch(() => { btn.disabled = false; btn.textContent = 'Save Payout Details'; msg.className = 'msg err'; msg.textContent = 'Network error.'; });
    }

    function submitPayout() {
        const btn = document.getElementById('pm-submit');
        const msg = document.getElementById('pm-msg');
        const method = document.getElementById('pm-method').value;
        const details = document.getElementById('pm-details').value.trim();
        if (!details) { msg.className = 'msg err'; msg.textContent = 'Please enter your payment details.'; return; }
        btn.disabled = true; btn.textContent = 'Submitting...';
        const fd = new FormData();
        fd.append('method', method);
        fd.append('details', details);
        fetch('/api/dist/payout', { method: 'POST', headers: apiHeaders, body: fd })
            .then(r => r.json())
            .then(data => {
                btn.disabled = false; btn.textContent = 'Submit Request';
                if (data.success) {
                    msg.className = 'msg ok';
                    msg.textContent = 'Payout request #' + data.id + ' submitted for ₹' + data.amount + '. Our team will process it shortly.';
                    loadPayoutHistory();
                    setTimeout(() => closeModal('payout-modal'), 2000);
                } else {
                    msg.className = 'msg err';
                    msg.textContent = (data.messages || []).join(', ') || 'Request failed';
                }
            })
            .catch(() => { btn.disabled = false; btn.textContent = 'Submit Request'; msg.className = 'msg err'; msg.textContent = 'Network error.'; });
    }

    // close modals on overlay click
    document.querySelectorAll('.modal-overlay').forEach(m => {
        m.addEventListener('click', function(e) { if (e.target === this) this.classList.remove('show'); });
    });
    </script>
</body>
</html>
