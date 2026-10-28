<?php
/**
 * Music Distribution - Admin Panel (Standalone)
 * Review queue, status management, royalty reports
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Canonical host is the distribution subdomain; bounce legacy music.hitune.in URLs.
if (($_SERVER['HTTP_HOST'] ?? '') === 'music.hitune.in') {
    $q = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
    header('Location: https://distribution.hitune.in/admin' . $q, true, 301);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Distribution Admin | Hitune Music</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@6.5.95/css/materialdesignicons.min.css">
    <script src="/dist-auth.js"></script>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/dist-style.css">
    <style>
        .stats-grid { margin-top: 24px; }
        .tabs { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 18px; align-items: center; }
        .tab { padding: 7px 15px; border-radius: 999px; font-size: 13px; font-weight: 700; border: 1px solid var(--d-border); background: #fff; color: var(--d-slate); cursor: pointer; transition: all .15s; }
        .tab:hover { border-color: var(--d-cyan); color: var(--d-cyan-dark); }
        .tab.active { background: var(--d-cyan-soft); border-color: var(--d-cyan); color: var(--d-cyan-dark); }
        .search-input { width: 240px; margin-left: auto; }
        tr.sub-row { cursor: pointer; }
        .file-links a { display: inline-block; margin: 5px 10px 5px 0; padding: 6px 14px; background: var(--d-cyan-soft); color: var(--d-cyan-dark); border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 600; }
        .track-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid var(--d-border); font-size: 13px; color: var(--d-slate); }
        .empty { text-align: center; padding: 34px; color: var(--d-faint); font-size: 14px; }
    </style>
</head>
<body>
    <nav class="topnav">
        <div class="topnav-inner">
            <a class="brand" href="/"><span class="brand-dot"><span class="mdi mdi-music-note"></span></span>Hitune <small>Distribution</small></a>
            <div class="nav-links">
                <a href="/">Plans</a>
                <a href="/dashboard">Dashboard</a>
                <a href="/submit">Submit Music</a>
                <a href="https://music.hitune.in/">Hitune Music</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div id="app">
            <div class="loading"><div class="spinner"></div><p>Loading admin panel...</p></div>
        </div>
    </div>

    <div class="modal-bg" id="modal-bg">
        <div class="modal" id="modal-content"></div>
    </div>

    <script>
    const apiHeaders = {
        'x-bof-request-code': 'BusyOwlFrameWorkVersion201',
        'x-bof-platform': 'web',
        'x-bof-version': '2074'
    };
    distAuth.headers(apiHeaders);

    const app = document.getElementById('app');
    let currentStatus = '';
    let currentSearch = '';

    function esc(s) { const d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }
    function api(path, opts) {
        opts = opts || {};
        opts.headers = apiHeaders;
        return fetch(path, opts).then(r => r.json());
    }
    function denied(data) {
        return !data.success && (data.message === '403' || (data.messages||[]).join(' ').match(/403|access|denied/i) || data.code === 'admin_required');
    }

    function renderDenied() {
        app.innerHTML = `<div class="section"><div class="alert alert-error">
            <strong>Access denied.</strong> This page is for admins only.
            <br><a href="${distAuth.loginUrl()}" class="link-btn">Login with an admin account</a>
        </div></div>`;
    }

    function loadStats() {
        api('/api/dist/admin?action=stats').then(d => {
            if (!d.success) return;
            const s = d.stats;
            document.getElementById('stats-grid').innerHTML = `
                <div class="stat-card"><div class="value">${s.total}</div><div class="label">Total</div></div>
                <div class="stat-card"><div class="value">${s.submitted + s.in_review}</div><div class="label">Awaiting Review</div></div>
                <div class="stat-card"><div class="value">${s.in_progress}</div><div class="label">In Progress</div></div>
                <div class="stat-card"><div class="value">${s.launched}</div><div class="label">Launched</div></div>
                <div class="stat-card"><div class="value">${s.active_subscriptions}</div><div class="label">Active Subs</div></div>
                <div class="stat-card"><div class="value">₹${s.revenue_total}</div><div class="label">Revenue</div></div>`;
        });
    }

    function loadList() {
        const tbody = document.getElementById('subs-tbody');
        tbody.innerHTML = '<tr><td colspan="6" class="empty">Loading...</td></tr>';
        let url = '/api/dist/admin?action=list';
        if (currentStatus) url += '&status=' + currentStatus;
        if (currentSearch) url += '&q=' + encodeURIComponent(currentSearch);
        api(url).then(d => {
            if (denied(d)) { renderDenied(); return; }
            if (!d.success || !d.submissions.length) {
                tbody.innerHTML = '<tr><td colspan="6" class="empty">No submissions found</td></tr>';
                return;
            }
            tbody.innerHTML = d.submissions.map(s => `
                <tr class="sub-row" onclick="viewSub(${s.id})">
                    <td><strong>${esc(s.title)}</strong><br><small style="opacity:0.6">${esc(s.artist_name)}</small></td>
                    <td>${esc(s.type)}</td>
                    <td>${esc(s.user || '')}<br><small style="opacity:0.6">${esc(s.user_email || '')}</small></td>
                    <td><span class="status ${s.status}">${s.status.replace('_',' ')}</span>${s.takedown_requested ? ' <span class="status rejected">takedown req</span>' : ''}</td>
                    <td>${esc(s.submitted_at || '')}</td>
                    <td><button class="btn btn-secondary btn-sm" onclick="event.stopPropagation();viewSub(${s.id})">Review</button></td>
                </tr>`).join('');
        });
    }

    function renderMain() {
        app.innerHTML = `
            <div class="stats-grid stat-grid" style="margin-top:24px" id="stats-grid"></div>
            <div class="section">
                <h2>Submissions Queue</h2>
                <div class="tabs">
                    ${['','submitted','in_review','in_progress','approved','launched','rejected','taken_down'].map(st =>
                        `<div class="tab ${currentStatus===st?'active':''}" data-st="${st}" onclick="setStatus('${st}')">${st ? st.replace('_',' ') : 'All'}</div>`).join('')}
                    <input class="search-input" id="search" placeholder="Search title/artist..." onkeyup="if(event.key==='Enter'){currentSearch=this.value;loadList();}">
                </div>
                <table class="data-table">
                    <thead><tr><th>Release</th><th>Type</th><th>User</th><th>Status</th><th>Submitted</th><th></th></tr></thead>
                    <tbody id="subs-tbody"></tbody>
                </table>
            </div>
            <div class="section">
                <h2>Royalty Reports</h2>
                <div class="form-group" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
                    <div><label>Submission ID</label><input id="ry_sub" type="number" style="width:110px"></div>
                    <div><label>Month</label><input id="ry_month" placeholder="2026-09" style="width:110px"></div>
                    <div><label>Platform</label><input id="ry_platform" placeholder="Spotify" style="width:140px"></div>
                    <div><label>Streams</label><input id="ry_streams" type="number" style="width:100px"></div>
                    <div><label>Revenue</label><input id="ry_revenue" type="number" step="0.01" style="width:100px"></div>
                    <div><label>Currency</label><input id="ry_currency" value="USD" style="width:80px"></div>
                    <button class="btn btn-primary btn-sm" onclick="addRoyalty()">Add Report</button>
                </div>
                <details class="dist-details" style="margin:15px 0">
                    <summary>Bulk import from CSV (submission_id, month, platform, streams, downloads, revenue, currency)</summary>
                    <div class="form-group" style="margin-top:10px">
                        <textarea id="csv_import" class="csv-box" rows="5" placeholder="1,2026-08,Spotify,5000,0,45.50,USD&#10;1,2026-08,Apple Music,3200,0,38.20,USD"></textarea>
                    </div>
                    <button class="btn btn-secondary btn-sm" onclick="importRoyalties(this)">Import CSV</button>
                    <div id="csv_result" style="margin-top:10px;font-size:13px"></div>
                </details>
                <div id="royalty-list"></div>
            </div>
            <div class="section">
                <h2>Payout Requests</h2>
                <div id="payout-list"></div>
            </div>
            <div class="section">
                <h2>Subscriptions</h2>
                <div id="sub-list"></div>
            </div>`;
        loadStats();
        loadList();
        loadRoyalties();
        loadPayouts();
        loadSubscriptions();
    }

    function loadSubscriptions() {
        api('/api/dist/admin?action=subscriptions').then(d => {
            const el = document.getElementById('sub-list');
            if (!el) return;
            if (!d.success || !d.subscriptions.length) { el.innerHTML = '<div class="empty">No subscriptions</div>'; return; }
            el.innerHTML = `<table class="data-table"><thead><tr><th>ID</th><th>User</th><th>Plan</th><th>Paid</th><th>Releases</th><th>Status</th><th>Expires</th></tr></thead>
                <tbody>${d.subscriptions.map(s => `<tr>
                    <td>#${s.id}</td>
                    <td>${esc(s.user||('#'+s.user_id))}<br><small style="opacity:0.6">${esc(s.user_email||'')}</small></td>
                    <td>${esc(s.plan)}</td>
                    <td>₹${Number(s.amount_paid).toLocaleString()}<br><small style="opacity:0.6">${esc(s.payment_status)}${s.transaction_id?' · '+esc(s.transaction_id):''}</small></td>
                    <td>${s.releases_used}</td>
                    <td><span class="status ${s.status==='active'?'launched':(s.status==='pending'?'submitted':'taken_down')}">${esc(s.status)}</span></td>
                    <td>${esc(s.end_date||'-')}</td>
                </tr>`).join('')}</tbody></table>`;
        });
    }

    function exportPackage(id, btn) {
        const orig = btn.textContent;
        btn.disabled = true; btn.textContent = 'Exporting...';
        api('/api/dist/admin?action=export&id=' + id).then(d => {
            btn.disabled = false; btn.textContent = orig;
            const el = document.getElementById('export-result');
            if (d.success && d.download_url) {
                el.innerHTML = `<a href="${d.download_url}" target="_blank" class="link-btn">Download release package (${d.files_added} files)</a>` +
                    (d.missing_files && d.missing_files.length ? `<br><span style="color:var(--d-red)">Missing: ${d.missing_files.map(esc).join(', ')}</span>` : '');
            } else {
                el.innerHTML = '<span style="color:var(--d-red)">Export failed: ' + esc((d.messages||[]).join(', ')) + '</span>';
            }
        });
    }

    function loadPayouts() {
        api('/api/dist/admin?action=payouts').then(d => {
            const el = document.getElementById('payout-list');
            if (!el) return;
            if (!d.success || !d.payouts.length) { el.innerHTML = '<div class="empty">No payout requests</div>'; return; }
            el.innerHTML = `<table class="data-table"><thead><tr><th>ID</th><th>User</th><th>Amount</th><th>Method</th><th>Details</th><th>Status</th><th>Requested</th><th></th></tr></thead>
                <tbody>${d.payouts.map(p => `<tr>
                    <td>#${p.id}</td>
                    <td>${esc(p.username||('#'+p.user_id))}<br><small style="opacity:0.6">${esc(p.email||'')}</small></td>
                    <td>₹${Number(p.amount).toLocaleString()}</td>
                    <td>${esc(p.method||'')}</td>
                    <td style="max-width:220px;font-size:12px;opacity:.8">${esc(p.details||'')}</td>
                    <td><span class="status ${p.status==='requested'?'submitted':(p.status==='paid'?'launched':(p.status==='processing'?'in_review':'rejected'))}">${esc(p.status)}</span></td>
                    <td>${esc(p.time_add||'')}</td>
                    <td>${p.status==='requested'||p.status==='processing' ?
                        `<button class="btn btn-primary btn-sm" onclick="payoutAction(${p.id},'paid')">Mark Paid</button>
                         <button class="btn btn-secondary btn-sm" onclick="payoutAction(${p.id},'processing')">Processing</button>
                         <button class="btn btn-sm btn-danger" onclick="payoutAction(${p.id},'rejected')">Reject</button>` : ''}</td>
                </tr>`).join('')}</tbody></table>`;
        });
    }

    function payoutAction(id, status) {
        let ref = '';
        if (status === 'paid') ref = prompt('Payment reference (UPI/transaction ID):', '') || '';
        if (status === 'rejected' && !confirm('Reject payout request #' + id + '?')) return;
        const fd = new FormData();
        fd.append('action', 'payout_update');
        fd.append('id', id);
        fd.append('status', status);
        if (ref) fd.append('payment_reference', ref);
        api('/api/dist/admin', { method: 'POST', body: fd }).then(d => {
            if (d.success) loadPayouts();
            else alert('Error: ' + ((d.messages||[]).join(', ') || 'failed'));
        });
    }

    function setStatus(st) { currentStatus = st; document.querySelectorAll('.tab').forEach(t => t.classList.toggle('active', t.dataset.st === st)); loadList(); }

    function viewSub(id) {
        api('/api/dist/admin?action=view&id=' + id).then(d => {
            if (!d.success) { alert('Error loading submission'); return; }
            const s = d.submission;
            const tracks = d.tracks || [];
            const statuses = ['submitted','in_review','in_progress','approved','rejected','launched','taken_down'];
            document.getElementById('modal-content').innerHTML = `
                <span class="modal-close" onclick="closeModal()">&times;</span>
                <h3>${esc(s.title)} <span class="status ${s.status}">${s.status.replace('_',' ')}</span></h3>
                <div class="muted" style="font-size:13px;">by ${esc(s.artist_name)} &middot; ${esc(s.user||'')} (${esc(s.user_email||'')}) &middot; ${esc(s.plan_name||'')} plan</div>
                <div class="detail-grid">
                    <div><div class="lbl">Type</div>${esc(s.type)}</div>
                    <div><div class="lbl">Genre</div>${esc(s.genre||'-')}</div>
                    <div><div class="lbl">Release Date</div>${esc(s.release_date||'-')}</div>
                    <div><div class="lbl">Language</div>${esc(s.language||'-')}</div>
                    <div><div class="lbl">ISRC</div>${esc(s.isrc||'-')}</div>
                    <div><div class="lbl">UPC</div>${esc(s.upc||'-')}</div>
                    <div><div class="lbl">Label</div>${esc(s.label_name||'-')}</div>
                    <div><div class="lbl">Platforms</div>${esc((s.platforms||[]).join(', ')||'-')}</div>
                </div>
                <div class="file-links">
                    ${s.cover_art ? `<a href="/${esc(s.cover_art)}" target="_blank">Cover Art</a>` : ''}
                    ${s.audio_file ? `<a href="/${esc(s.audio_file)}" target="_blank">Main Audio</a>` : ''}
                </div>
                ${tracks.length ? '<h4 style="margin:15px 0 8px">Tracks</h4>' + tracks.map(t =>
                    `<div class="track-row"><span>#${t.track_number} ${esc(t.title)} ${t.isrc?('· '+esc(t.isrc)):''}</span>
                    <span>${t.duration?Math.floor(t.duration/60)+':'+String(t.duration%60).padStart(2,'0'):''}
                    ${t.audio_file_path?`<a href="/${esc(t.audio_file_path)}" target="_blank" class="link-btn" style="margin-left:10px">audio</a>`:''}</span></div>`).join('') : ''}
                <h4 style="margin:20px 0 10px">Update Status</h4>
                <div class="detail-grid">
                    <div class="form-group"><label>Status</label>
                        <select id="up_status">${statuses.map(st => `<option value="${st}" ${s.status===st?'selected':''}>${st.replace('_',' ')}</option>`).join('')}</select></div>
                    <div class="form-group"><label>Launch Date</label><input id="up_launch" type="date" value="${esc(s.launch_date||'')}"></div>
                    <div class="form-group"><label>TuneCore/Aggregator URL</label><input id="up_tc_url" value="${esc(s.tunecore_url||'')}"></div>
                    <div class="form-group"><label>Aggregator Status</label><input id="up_tc_status" value="${esc(s.tunecore_status||'')}"></div>
                </div>
                ${s.takedown_requested ? `<div class="alert alert-error" style="margin:15px 0">
                    <b>Takedown requested</b> ${s.takedown_requested_at ? 'on ' + esc(s.takedown_requested_at) : ''}
                    ${s.takedown_reason ? `<div style="margin-top:6px;font-size:13px;opacity:.8">Reason: ${esc(s.takedown_reason)}</div>` : ''}
                    <div style="margin-top:10px;font-size:12px;opacity:.7">Pull the release from the aggregator, then set status to "taken down". Or dismiss the request:</div>
                    <button class="btn btn-secondary btn-sm" style="margin-top:8px" onclick="clearTakedown(${s.id})">Dismiss Request</button>
                </div>` : ''}
                <div class="form-group"><label>Admin Notes</label><textarea id="up_notes" rows="3">${esc(s.admin_notes||'')}</textarea></div>
                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                    <button class="btn btn-primary" onclick="saveStatus(${s.id})">Save Changes</button>
                    <button class="btn btn-secondary" onclick="exportPackage(${s.id}, this)">Export Package</button>
                    ${s.catalog_published ? `<span class="status launched" style="align-self:center">in catalog (${(s.catalog_track_ids||[]).length} tracks)</span>` :
                        `<button class="btn btn-secondary" onclick="publishCatalog(${s.id}, this)">Publish to Catalog</button>`}
                    <button class="btn btn-danger" onclick="deleteSub(${s.id})">Delete</button>
                </div>
                <div id="export-result" style="margin-top:12px;font-size:13px"></div>`;
            document.getElementById('modal-bg').classList.add('open');
        });
    }

    function closeModal() { document.getElementById('modal-bg').classList.remove('open'); }
    document.getElementById('modal-bg').addEventListener('click', e => { if (e.target.id === 'modal-bg') closeModal(); });

    function saveStatus(id) {
        const fd = new FormData();
        fd.append('action', 'update_status');
        fd.append('id', id);
        fd.append('status', document.getElementById('up_status').value);
        fd.append('admin_notes', document.getElementById('up_notes').value);
        fd.append('tunecore_url', document.getElementById('up_tc_url').value);
        fd.append('tunecore_status', document.getElementById('up_tc_status').value);
        fd.append('launch_date', document.getElementById('up_launch').value);
        api('/api/dist/admin', { method: 'POST', body: fd }).then(d => {
            if (d.success) { closeModal(); loadList(); loadStats(); }
            else alert('Error: ' + ((d.messages||[]).join(', ') || 'failed'));
        });
    }

    function publishCatalog(id, btn) {
        btn.disabled = true; btn.textContent = 'Publishing...';
        const fd = new FormData();
        fd.append('action', 'publish');
        fd.append('id', id);
        api('/api/dist/admin', { method: 'POST', body: fd }).then(d => {
            btn.disabled = false; btn.textContent = 'Publish to Catalog';
            if (d.success) { closeModal(); loadList(); alert('Published ' + (d.track_ids||[]).length + ' track(s) to the streaming catalog.'); }
            else alert('Publish failed: ' + ((d.messages||[]).join(', ') || d.code || 'error'));
        });
    }

    function clearTakedown(id) {
        if (!confirm('Dismiss this takedown request? The release stays live.')) return;
        const fd = new FormData();
        fd.append('action', 'clear_takedown');
        fd.append('id', id);
        api('/api/dist/admin', { method: 'POST', body: fd }).then(d => {
            if (d.success) { closeModal(); loadList(); }
            else alert('Error: ' + ((d.messages||[]).join(', ') || 'failed'));
        });
    }

    function deleteSub(id) {
        if (!confirm('Delete submission #' + id + ' and its tracks?')) return;
        const fd = new FormData();
        fd.append('action', 'delete');
        fd.append('id', id);
        api('/api/dist/admin', { method: 'POST', body: fd }).then(d => {
            if (d.success) { closeModal(); loadList(); loadStats(); }
            else alert('Error: ' + ((d.messages||[]).join(', ') || 'failed'));
        });
    }

    function addRoyalty() {
        const fd = new FormData();
        fd.append('action', 'add_royalty');
        fd.append('submission_id', document.getElementById('ry_sub').value);
        fd.append('report_month', document.getElementById('ry_month').value);
        fd.append('platform', document.getElementById('ry_platform').value);
        fd.append('streams', document.getElementById('ry_streams').value);
        fd.append('revenue', document.getElementById('ry_revenue').value);
        fd.append('currency', document.getElementById('ry_currency').value);
        api('/api/dist/admin', { method: 'POST', body: fd }).then(d => {
            if (d.success) { loadRoyalties(); loadStats(); }
            else alert('Error: ' + ((d.messages||[]).join(', ') || 'failed'));
        });
    }

    function importRoyalties(btn) {
        const csv = document.getElementById('csv_import').value.trim();
        if (!csv) { alert('Paste CSV rows first'); return; }
        btn.disabled = true; btn.textContent = 'Importing...';
        const fd = new FormData();
        fd.append('action', 'import_royalties');
        fd.append('csv', csv);
        api('/api/dist/admin', { method: 'POST', body: fd }).then(d => {
            btn.disabled = false; btn.textContent = 'Import CSV';
            const el = document.getElementById('csv_result');
            if (d.success) {
                let h = `<span style="color:var(--d-green)">${d.created} reports imported.</span>`;
                if (d.skipped && d.skipped.length)
                    h += '<br><span style="color:var(--d-red)">Skipped: ' + d.skipped.map(s => 'line ' + s.line + ' (' + esc(s.reason) + ')').join(', ') + '</span>';
                el.innerHTML = h;
                document.getElementById('csv_import').value = '';
                loadRoyalties(); loadStats();
            } else {
                el.innerHTML = '<span style="color:var(--d-red)">' + esc((d.messages||[]).join(', ') || 'Import failed') + '</span>';
            }
        });
    }

    function loadRoyalties() {
        api('/api/dist/admin?action=royalties').then(d => {
            const el = document.getElementById('royalty-list');
            if (!el) return;
            if (!d.success || !d.reports.length) { el.innerHTML = '<div class="empty">No royalty reports yet</div>'; return; }
            el.innerHTML = `<table class="data-table"><thead><tr><th>Month</th><th>Release</th><th>User</th><th>Platform</th><th>Streams</th><th>Revenue</th></tr></thead>
                <tbody>${d.reports.map(r => `<tr><td>${esc(r.report_month)}</td><td>${esc(r.submission_title||'#')}</td>
                <td>${esc(r.username||'')}</td><td>${esc(r.platform)}</td><td>${r.streams}</td>
                <td>${esc(r.currency)} ${r.revenue}</td></tr>`).join('')}</tbody></table>`;
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        api('/api/dist/admin?action=stats').then(d => {
            if (denied(d)) { renderDenied(); return; }
            renderMain();
        }).catch(() => renderDenied());
    });
    </script>
</body>
</html>
