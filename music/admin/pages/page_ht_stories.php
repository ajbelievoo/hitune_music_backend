<?php
/**
 * HiTune Stories - Admin Panel
 * Post official stories that show in the HiTune app rail AND inside IyolMe.
 * Stories expire after 24h unless "Repeat" is on (then they keep playing).
 */

if (!defined('bof_base')) die('Direct access not allowed');

class page_ht_stories extends bof_admin_page {

    public function load() {
        return [
            'name' => 'ht_stories',
            'title' => 'HiTune Stories',
            'slug' => 'hitune-stories',
            'icon' => 'mdi-history',
            'position' => 31,
            'capabilities' => ['admin']
        ];
    }

    public function body() {
        $endpoint = $this->loader->endpoint_address;
        ob_start();
        ?>
<style>
.hts { padding: 20px; max-width: 1100px; margin: 0 auto; }
.hts h1 { font-size: 24px; font-weight: 700; margin-bottom: 6px; }
.hts .sub { color: #777; margin-bottom: 24px; font-size: 14px; }
.hts-card { background: #fff; border-radius: 14px; padding: 22px; box-shadow: 0 2px 10px rgba(0,0,0,.07); margin-bottom: 24px; }
.hts-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.hts label { display: block; font-size: 12px; font-weight: 600; color: #555; margin-bottom: 5px; text-transform: uppercase; letter-spacing: .4px; }
.hts input[type=text], .hts input[type=url], .hts input[type=number], .hts select, .hts textarea {
    width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 9px; font-size: 14px; box-sizing: border-box;
}
.hts textarea { resize: vertical; min-height: 60px; }
.hts .row { display: flex; align-items: center; gap: 10px; margin-top: 16px; flex-wrap: wrap; }
.hts .switch { display: inline-flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 600; }
.hts .switch input { width: 18px; height: 18px; accent-color: #7c3aed; }
.hts button { background: #7c3aed; color: #fff; border: 0; border-radius: 9px; padding: 11px 22px; font-size: 14px; font-weight: 700; cursor: pointer; }
.hts button.ghost { background: #eee; color: #444; }
.hts button.danger { background: #dc2626; }
.hts button:disabled { opacity: .55; cursor: default; }
.hts-msg { margin-top: 12px; font-size: 14px; font-weight: 600; }
.hts-msg.ok { color: #059669; } .hts-msg.err { color: #dc2626; }
.hts-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: 16px; }
.hts-item { background: #fff; border-radius: 14px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,.07); }
.hts-item .media { height: 240px; background: #111; display: flex; align-items: center; justify-content: center; }
.hts-item .media img, .hts-item .media video { width: 100%; height: 100%; object-fit: cover; }
.hts-item .body { padding: 12px 14px; }
.hts-item .cap { font-size: 13px; color: #333; margin-bottom: 8px; min-height: 18px; }
.hts-item .meta { font-size: 11px; color: #999; margin-bottom: 10px; }
.hts-item .badges { display: flex; gap: 6px; margin-bottom: 10px; flex-wrap: wrap; }
.hts-badge { font-size: 10px; font-weight: 700; padding: 3px 8px; border-radius: 20px; }
.hts-badge.live { background: #dcfce7; color: #15803d; }
.hts-badge.expired { background: #fee2e2; color: #b91c1c; }
.hts-badge.repeat { background: #ede9fe; color: #6d28d9; }
.hts-item .actions { display: flex; gap: 6px; }
.hts-item .actions button { flex: 1; padding: 8px 6px; font-size: 12px; }
#hts-preview { max-height: 220px; border-radius: 10px; display: none; margin-top: 10px; }
</style>

<div class="hts">
    <h1>HiTune Stories</h1>
    <div class="sub">Official stories appear at the top of the app's Stories rail <b>and inside IyolMe</b> (posted as @blitzomnii). They vanish after 24 hours unless <b>Repeat</b> is on.</div>

    <div class="hts-card">
        <div class="hts-grid">
            <div>
                <label>Media file (image or video) — or paste a URL below</label>
                <input type="file" id="f-media" accept="image/*,video/*">
                <img id="hts-preview" src="" alt="">
            </div>
            <div>
                <label>…or media URL</label>
                <input type="url" id="f-url" placeholder="https://…jpg / .mp4">
                <div style="height:12px"></div>
                <label>Type</label>
                <select id="f-type"><option value="0">Image</option><option value="1">Video</option></select>
            </div>
            <div>
                <label>Caption</label>
                <textarea id="f-caption" placeholder="Story text…"></textarea>
            </div>
            <div>
                <label>Link (swipe-up / tap target)</label>
                <input type="url" id="f-link" placeholder="https://hitune.in/…">
                <div style="height:12px"></div>
                <label>Duration (seconds)</label>
                <input type="number" id="f-duration" value="5" min="1" max="60">
                <div style="height:12px"></div>
                <label>Display name</label>
                <input type="text" id="f-name" value="HiTune">
            </div>
        </div>
        <div class="row">
            <label class="switch"><input type="checkbox" id="f-repeat"> Repeat after 24h (keep playing)</label>
            <button id="btn-save" onclick="htsSave()">Post Story</button>
            <button class="ghost" onclick="htsReset()">Reset</button>
            <span class="hts-msg" id="hts-msg"></span>
        </div>
    </div>

    <h2 style="font-size:17px;margin-bottom:12px;">Posted stories</h2>
    <div class="hts-list" id="hts-list"><div style="color:#999">Loading…</div></div>
</div>

<script>
const HTS_EP = '<?php echo $endpoint; ?>be/ht_stories';
const HTS_AUTH = { 'Authorization': 'Bearer ' + (window.app?.user?.token || '') };

document.getElementById('f-media').addEventListener('change', e => {
    const f = e.target.files[0]; const p = document.getElementById('hts-preview');
    if (f && f.type.startsWith('image/')) { p.src = URL.createObjectURL(f); p.style.display = 'block'; }
    else p.style.display = 'none';
});

function htsReset() {
    ['f-media','f-url','f-caption','f-link','f-name'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('f-name').value = 'HiTune';
    document.getElementById('f-duration').value = 5;
    document.getElementById('f-repeat').checked = false;
    document.getElementById('hts-preview').style.display = 'none';
    htsMsg('');
}

function htsMsg(t, ok) {
    const m = document.getElementById('hts-msg');
    m.textContent = t; m.className = 'hts-msg ' + (ok === undefined ? '' : (ok ? 'ok' : 'err'));
}

async function htsSave() {
    const file = document.getElementById('f-media').files[0];
    const url  = document.getElementById('f-url').value.trim();
    if (!file && !url) { htsMsg('Pick a media file or paste a URL', false); return; }

    const fd = new FormData();
    fd.append('action', 'save');
    if (file) fd.append('media', file);
    if (url)  fd.append('content', url);
    fd.append('type', document.getElementById('f-type').value);
    fd.append('duration', document.getElementById('f-duration').value || '5');
    fd.append('caption', document.getElementById('f-caption').value);
    fd.append('link_url', document.getElementById('f-link').value);
    fd.append('display_name', document.getElementById('f-name').value || 'HiTune');
    fd.append('repeat', document.getElementById('f-repeat').checked ? '1' : '0');

    const btn = document.getElementById('btn-save');
    btn.disabled = true; htsMsg('Posting…');
    try {
        const r = await fetch(HTS_EP, { method: 'POST', headers: HTS_AUTH, body: fd });
        const d = await r.json();
        if (d.success || d.message === 'story_saved') {
            htsMsg('Story posted' + (d.data?.iyol_story_id || d.iyol_story_id ? ' · synced to IyolMe' : ''), true);
            htsReset(); htsLoad();
        } else htsMsg('Failed: ' + (d.message || JSON.stringify(d)), false);
    } catch (e) { htsMsg('Error: ' + e, false); }
    btn.disabled = false;
}

async function htsToggle(id, field) {
    const fd = new FormData();
    fd.append('action', 'toggle'); fd.append('id', id); fd.append('field', field);
    await fetch(HTS_EP, { method: 'POST', headers: HTS_AUTH, body: fd });
    htsLoad();
}

async function htsDelete(id) {
    if (!confirm('Delete this story? (also removed from IyolMe)')) return;
    const fd = new FormData();
    fd.append('action', 'delete'); fd.append('id', id);
    await fetch(HTS_EP, { method: 'POST', headers: HTS_AUTH, body: fd });
    htsLoad();
}

function htsBadge(s) {
    const day = 24 * 3600 * 1000;
    const age = Date.now() - new Date(s.time_add.replace(' ', 'T') + 'Z').getTime();
    let b = '';
    if (s.repeat_forever == 1) b += '<span class="hts-badge repeat">REPEAT</span>';
    b += (s.repeat_forever == 1 || age < day)
        ? '<span class="hts-badge live">LIVE</span>'
        : '<span class="hts-badge expired">EXPIRED</span>';
    if (s.active != 1) b += '<span class="hts-badge expired">DISABLED</span>';
    if (s.iyol_story_id) b += '<span class="hts-badge repeat">IYOLME ✓</span>';
    return b;
}

async function htsLoad() {
    const fd = new FormData(); fd.append('action', 'list');
    try {
        const r = await fetch(HTS_EP, { method: 'POST', headers: HTS_AUTH, body: fd });
        const d = await r.json();
        const list = (d.data && d.data.stories) || d.stories || [];
        const el = document.getElementById('hts-list');
        if (!list.length) { el.innerHTML = '<div style="color:#999">No stories yet</div>'; return; }
        el.innerHTML = list.map(s => `
            <div class="hts-item">
                <div class="media">${s.type == 1
                    ? `<video src="${s.content}" muted playsinline controls></video>`
                    : `<img src="${s.content}" loading="lazy" alt="">`}</div>
                <div class="body">
                    <div class="badges">${htsBadge(s)}</div>
                    <div class="cap">${(s.caption || '').replace(/</g,'&lt;')}</div>
                    <div class="meta">${s.time_add} · ${s.s_views} views · ${s.duration}s</div>
                    <div class="actions">
                        <button class="ghost" onclick="htsToggle(${s.ID},'repeat_forever')">${s.repeat_forever == 1 ? 'Repeat ON' : 'Repeat OFF'}</button>
                        <button class="ghost" onclick="htsToggle(${s.ID},'active')">${s.active == 1 ? 'Disable' : 'Enable'}</button>
                        <button class="danger" onclick="htsDelete(${s.ID})">Delete</button>
                    </div>
                </div>
            </div>`).join('');
    } catch (e) {
        document.getElementById('hts-list').innerHTML = '<div style="color:#dc2626">Load failed: ' + e + '</div>';
    }
}

htsLoad();
</script>
        <?php
        return ob_get_clean();
    }
}
?>
