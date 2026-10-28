<?php
/**
 * HiTune Music Distribution — Artist Panel
 *
 * The artist's home base (strategy doc: HiTune Distribution creator portal):
 *   Overview · Analytics · Verification (HiTune Music / Spotify / Apple Music)
 *   · Releases & status · Growth · Profile
 *
 * HiTune Music analytics + artist verification come from music.hitune.in via
 * the signed bridge (includes/artist_panel.php). Spotify / Apple Music data
 * comes from their public APIs and from the DSP royalty reports.
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/artist_panel.php';
require_once __DIR__ . '/../includes/ecosystem_sync.php';
requireLogin();

$user   = getCurrentUser();
$userId = (int) $user['id'];
ap_ensure_tables($conn);

$e = function ($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };

// ── POST actions (PRG) ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken($_POST['csrf_token'] ?? '');
    $act  = $_POST['ap_action'] ?? '';
    $back = 'overview';
    $flash = ['type' => 'error', 'msg' => 'Unknown action.'];

    if ($act === 'save_profile') {
        $back = 'profile';
        $stage = trim((string) ($_POST['stage_name'] ?? ''));
        if ($stage === '' || mb_strlen($stage) > 120) {
            $flash = ['type' => 'error', 'msg' => 'Please enter a stage name (max 120 characters).'];
        } else {
            $bio   = mb_substr(trim((string) ($_POST['bio'] ?? '')), 0, 1500);
            $genre = mb_substr(trim((string) ($_POST['genre'] ?? '')), 0, 100);
            $ctry  = mb_substr(trim((string) ($_POST['country'] ?? '')), 0, 100);
            $city  = mb_substr(trim((string) ($_POST['city'] ?? '')), 0, 100);
            $web   = mb_substr(trim((string) ($_POST['website'] ?? '')), 0, 255);
            $insta = mb_substr(trim((string) ($_POST['instagram'] ?? '')), 0, 255);
            $yt    = mb_substr(trim((string) ($_POST['youtube'] ?? '')), 0, 255);

            $base = ap_slugify($stage); $slug = $base;
            for ($i = 0; $i < 30; $i++) {
                $st = $conn->prepare("SELECT user_id FROM artist_profiles WHERE slug = ? AND user_id <> ? LIMIT 1");
                $st->bind_param("si", $slug, $userId);
                $st->execute();
                $taken = $st->get_result()->num_rows > 0;
                $st->close();
                if (!$taken) break;
                $slug = $base . '-' . random_int(10, 999);
            }
            $st = $conn->prepare("INSERT INTO artist_profiles (user_id, stage_name, slug, bio, genre, country, city, website, instagram, youtube)
                VALUES (?,?,?,?,?,?,?,?,?,?)
                ON DUPLICATE KEY UPDATE stage_name = VALUES(stage_name), bio = VALUES(bio), genre = VALUES(genre), country = VALUES(country),
                    city = VALUES(city), website = VALUES(website), instagram = VALUES(instagram), youtube = VALUES(youtube),
                    slug = COALESCE(slug, VALUES(slug))");
            $st->bind_param("isssssssss", $userId, $stage, $slug, $bio, $genre, $ctry, $city, $web, $insta, $yt);
            $flash = $st->execute()
                ? ['type' => 'ok', 'msg' => 'Profile saved.']
                : ['type' => 'error', 'msg' => 'Could not save your profile.'];
            $st->close();
        }
    } elseif (in_array($act, ['verify_hitune', 'verify_spotify', 'verify_apple'], true)) {
        $back = 'verification';
        $platform = substr($act, 7);
        $in = [
            'artist_name' => $_POST['artist_name'] ?? '',
            'real_name'   => $_POST['real_name'] ?? '',
            'profile_url' => $_POST['profile_url'] ?? '',
            'note'        => mb_substr((string) ($_POST['note'] ?? ''), 0, 1500),
            'doc_path'    => null,
        ];
        $docErr = false;
        if ($platform === 'hitune' && !empty($_FILES['proof_doc']['name'])) {
            $doc = ap_store_proof_upload($_FILES['proof_doc']);
            if ($doc === false) { $docErr = true; } else { $in['doc_path'] = $doc; }
        }
        if ($docErr) {
            $flash = ['type' => 'error', 'msg' => 'Proof file must be a JPG, PNG, WEBP or PDF up to 5 MB.'];
        } else {
            $res = ap_submit_verification($conn, $userId, $user, $platform, $in);
            $flash = ['type' => !empty($res['ok']) ? 'ok' : 'error', 'msg' => $res['message'] ?? 'Done.'];
        }
        unset($_SESSION['ap_ov_7'], $_SESSION['ap_ov_30'], $_SESSION['ap_ov_90']);
    }

    $_SESSION['ap_flash'] = $flash;
    header('Location: /index.php?q=artist-panel#' . $back);
    exit;
}

$flash = $_SESSION['ap_flash'] ?? null;
unset($_SESSION['ap_flash']);

// ── Data ─────────────────────────────────────────────────────────────────────
$days = (int) ($_GET['days'] ?? 30);
if (!in_array($days, [7, 30, 90], true)) $days = 30;

$stageNames = ap_user_stage_names($conn, $userId, $user['name']);
$ov = ap_music_overview($conn, $user, $stageNames, $days, isset($_GET['refresh']));
$musicOk = !empty($ov['ok']);
if ($musicOk) ap_sync_hitune_status($conn, $userId, $ov);
$verifs = ap_verifications_for_user($conn, $userId);

$artists = $musicOk ? (array) ($ov['artists'] ?? []) : [];
$primary = $artists ? $artists[0] : null;
if ($artists && !empty($_GET['artist'])) {
    foreach ($artists as $a) { if ((int) $a['id'] === (int) $_GET['artist']) { $primary = $a; break; } }
}

$profile = null;
$st = $conn->prepare("SELECT * FROM artist_profiles WHERE user_id = ? LIMIT 1");
$st->bind_param("i", $userId);
$st->execute();
$profile = $st->get_result()->fetch_assoc() ?: null;
$st->close();

$primaryName = $primary['name'] ?? ($profile['stage_name'] ?? ($stageNames[0] ?? $user['name']));

// state per platform
$state = [];
foreach (['hitune', 'spotify', 'apple'] as $p) {
    $best = ap_platform_state($verifs[$p]);
    $state[$p] = $best ? $best['status'] : 'none';
}
if ($artists) {
    foreach ($artists as $a) { if (!empty($a['verified'])) { $state['hitune'] = 'verified'; break; } }
}

// DSP royalty reports (this is where Spotify / Apple Music performance really shows up)
$life = ['plays' => 0, 'revenue' => 0.0, 'royalties' => 0.0];
$st = $conn->prepare("SELECT COALESCE(SUM(tr.plays),0) p, COALESCE(SUM(tr.revenue),0) r, COALESCE(SUM(tr.royalty_amount),0) y
    FROM track_royalties tr JOIN release_tracks rt ON tr.track_id = rt.id JOIN releases r ON rt.release_id = r.id WHERE r.user_id = ?");
$st->bind_param("i", $userId);
$st->execute();
$row = $st->get_result()->fetch_assoc();
$st->close();
if ($row) $life = ['plays' => (int) $row['p'], 'revenue' => (float) $row['r'], 'royalties' => (float) $row['y']];

$dsp = [];
$st = $conn->prepare("SELECT tr.platform, COALESCE(SUM(tr.plays),0) s, COALESCE(SUM(tr.revenue),0) r, COALESCE(SUM(tr.royalty_amount),0) y
    FROM track_royalties tr JOIN release_tracks rt ON tr.track_id = rt.id JOIN releases r ON rt.release_id = r.id
    WHERE r.user_id = ? GROUP BY tr.platform ORDER BY s DESC");
$st->bind_param("i", $userId);
$st->execute();
$res = $st->get_result();
while ($r = $res->fetch_assoc()) $dsp[] = $r;
$st->close();

// releases + per-platform delivery for the doc's dashboard statuses
$releases = [];
$st = $conn->prepare("SELECT id, title, release_type, status, release_date, cover_art_path, ai_pct, primary_artist, admin_notes
    FROM releases WHERE user_id = ? ORDER BY id DESC LIMIT 50");
$st->bind_param("i", $userId);
$st->execute();
$res = $st->get_result();
while ($r = $res->fetch_assoc()) $releases[$r['id']] = $r + ['platforms' => []];
$st->close();
if ($releases) {
    $in = implode(',', array_map('intval', array_keys($releases)));
    $res = $conn->query("SELECT release_id, platform_name, delivery_status, is_selected, store_url FROM release_platforms WHERE release_id IN ({$in})");
    while ($res && ($r = $res->fetch_assoc())) $releases[$r['release_id']]['platforms'][] = $r;
}
$liveCount = 0; $aiCount = 0;
foreach ($releases as $r) { if (in_array($r['status'], ['ready', 'live'], true)) $liveCount++; if ((int) $r['ai_pct'] > 0) $aiCount++; }

// growth
$refCode = ref_code_for($conn, $userId);
$refCredit = 0.0; $refCount = 0;
$res = $conn->query("SELECT referral_credit FROM users WHERE id = " . $userId);
if ($res && $res->num_rows) $refCredit = (float) $res->fetch_assoc()['referral_credit'];
$res = $conn->query("SELECT COUNT(*) c FROM referral_events WHERE referrer_id = " . $userId . " AND event = 'signup'");
if ($res) $refCount = (int) $res->fetch_assoc()['c'];
$refLink = 'https://distribution.hitune.in/index.php?q=signup&ref=' . rawurlencode((string) $refCode);

$planSlug = function_exists('getUserPlan') ? getUserPlan() : null;
$planLabel = ['rising_artist' => 'Artist', 'breakout_artist' => 'Artist Pro', 'professional' => 'Label'][$planSlug] ?? 'Free';

$events = $musicOk ? (array) ($ov['events'] ?? []) : [];
$contests = $musicOk ? (array) ($ov['contests'] ?? []) : [];

$totals = $primary['totals'] ?? [];
$series = $primary['series'] ?? ['labels' => [], 'values' => []];
$geo = $primary['geo'] ?? ['available' => false, 'countries' => [], 'cities' => []];

$statusLabel = function ($rel) {
    return eco_dashboard_status($rel['status'], $rel['platforms']);
};
$statusColor = function ($label) {
    if (strpos($label, 'Rejected') === 0) return 'bad';
    if (strpos($label, 'Pending') === 0 || $label === 'Takedown Requested') return 'warn';
    if ($label === 'Draft') return 'mute';
    if ($label === 'Taken Down') return 'mute';
    return 'good';
};

$pageTitle = 'Artist Panel - HiTune Music Distribution';
$metaDescription = 'Your HiTune artist panel: verification on HiTune Music, Spotify and Apple Music, real-time analytics by country and city, releases, royalties and growth tools.';
$path = 'artist-panel';
$loadCharts = true;
include __DIR__ . '/../includes/header_premium.php';

$platIcon = ['hitune' => 'mdi-music-circle', 'spotify' => 'mdi-spotify', 'apple' => 'mdi-apple'];
$platName = ['hitune' => 'HiTune Music', 'spotify' => 'Spotify', 'apple' => 'Apple Music'];
$badge = function ($p) use ($state, $platIcon, $platName, $e) {
    $s = $state[$p];
    $cls = $s === 'verified' ? 'ok' : ($s === 'pending' ? 'wait' : 'none');
    $ico = $s === 'verified' ? 'mdi-check-decagram' : ($s === 'pending' ? 'mdi-clock-outline' : 'mdi-shield-outline');
    $txt = $s === 'verified' ? 'Verified' : ($s === 'pending' ? 'In review' : 'Verify');
    return '<a class="ap-badge ' . $cls . '" href="#verification" data-tab="verification" title="' . $e($platName[$p]) . ': ' . $txt . '">'
         . '<span class="mdi ' . $platIcon[$p] . '"></span> ' . $e($platName[$p]) . ' <span class="mdi ' . $ico . '"></span></a>';
};
?>
<style>
.ap-wrap{max-width:1240px;margin:0 auto;padding:110px 28px 70px}
.ap-flash{padding:14px 18px;border-radius:14px;margin-bottom:20px;font-weight:600;font-size:14px;border:1px solid var(--glass-border)}
.ap-flash.ok{background:rgba(0,200,83,.12);color:#0a9d46;border-color:rgba(0,200,83,.35)}
.ap-flash.error{background:rgba(255,82,82,.1);color:#d93636;border-color:rgba(255,82,82,.35)}
.ap-hero{display:flex;justify-content:space-between;align-items:center;gap:24px;flex-wrap:wrap;padding:28px;border-radius:26px;
  background:linear-gradient(135deg,rgba(0,183,255,.14),rgba(139,92,246,.14));border:1px solid var(--glass-border);backdrop-filter:blur(18px);position:relative;overflow:hidden}
.ap-hero::after{content:'';position:absolute;right:-80px;top:-80px;width:280px;height:280px;background:radial-gradient(circle,rgba(139,92,246,.35),transparent 65%);pointer-events:none}
.ap-id{display:flex;gap:20px;align-items:center;position:relative;z-index:1}
.ap-avatar{width:84px;height:84px;border-radius:24px;background:var(--primary-gradient);display:flex;align-items:center;justify-content:center;font-size:36px;font-weight:800;color:#fff;overflow:hidden;flex-shrink:0;box-shadow:0 12px 34px rgba(0,183,255,.35)}
.ap-avatar img{width:100%;height:100%;object-fit:cover}
.ap-id h1{font-size:clamp(24px,4vw,34px);font-weight:800;margin:0 0 10px;color:var(--text-primary)}
.ap-badges{display:flex;gap:8px;flex-wrap:wrap}
.ap-badge{display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border-radius:30px;font-size:12px;font-weight:700;text-decoration:none;border:1px solid var(--glass-border);background:var(--glass-bg);color:var(--text-secondary)}
.ap-badge.ok{color:#0a9d46;border-color:rgba(0,200,83,.45);background:rgba(0,200,83,.12)}
.ap-badge.wait{color:#c88a00;border-color:rgba(255,193,7,.5);background:rgba(255,193,7,.12)}
.ap-badge.ai{color:#8b5cf6;border-color:rgba(139,92,246,.4);background:rgba(139,92,246,.1)}
.ap-sub{margin-top:10px;font-size:13px;color:var(--text-muted)}
.ap-actions{display:flex;gap:10px;flex-wrap:wrap;position:relative;z-index:1}
.ap-btn{display:inline-flex;align-items:center;gap:8px;padding:12px 20px;border-radius:14px;font-weight:700;font-size:14px;text-decoration:none;border:1px solid var(--glass-border);background:var(--glass-bg);color:var(--text-primary);cursor:pointer;font-family:inherit;transition:.25s}
.ap-btn:hover{transform:translateY(-2px);border-color:var(--primary)}
.ap-btn.primary{background:var(--primary-gradient);color:#fff;border-color:transparent;box-shadow:0 10px 28px rgba(0,183,255,.3)}
.ap-btn.sm{padding:8px 14px;font-size:13px;border-radius:10px}
.ap-tabs{display:flex;gap:6px;margin:26px 0 22px;padding:6px;border-radius:18px;background:var(--glass-bg);border:1px solid var(--glass-border);overflow-x:auto;position:sticky;top:78px;z-index:20;backdrop-filter:blur(14px)}
.ap-tab{padding:11px 18px;border-radius:13px;font-weight:700;font-size:14px;color:var(--text-secondary);text-decoration:none;white-space:nowrap;display:inline-flex;gap:8px;align-items:center;cursor:pointer;border:0;background:transparent;font-family:inherit}
.ap-tab.on{background:var(--primary-gradient);color:#fff;box-shadow:0 8px 22px rgba(0,183,255,.28)}
.ap-pane{display:none;animation:apIn .35s ease}.ap-pane.on{display:block}
@keyframes apIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:none}}
.ap-grid{display:grid;gap:18px}
.g6{grid-template-columns:repeat(auto-fit,minmax(170px,1fr))}
.g2{grid-template-columns:repeat(auto-fit,minmax(340px,1fr))}
.g3{grid-template-columns:repeat(auto-fit,minmax(290px,1fr))}
.ap-card{background:var(--glass-bg);border:1px solid var(--glass-border);border-radius:22px;padding:22px;backdrop-filter:blur(14px);box-shadow:var(--shadow-card)}
.ap-card h3{font-size:16px;font-weight:800;margin:0 0 14px;color:var(--text-primary);display:flex;align-items:center;gap:8px}
.ap-card h3 .mdi{color:var(--primary)}
.ap-kpi{padding:18px 20px}
.ap-kpi .l{font-size:12px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;display:flex;justify-content:space-between}
.ap-kpi .v{font-size:30px;font-weight:800;margin-top:8px;background:var(--primary-gradient);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.ap-kpi .s{font-size:12px;color:var(--text-muted);margin-top:4px}
.ap-muted{color:var(--text-muted);font-size:13px;line-height:1.6}
.ap-bar{display:flex;align-items:center;gap:10px;margin:9px 0;font-size:13px}
.ap-bar .n{width:130px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--text-primary);font-weight:600}
.ap-bar .t{flex:1;height:9px;border-radius:9px;background:rgba(127,127,127,.15);overflow:hidden}
.ap-bar .t i{display:block;height:100%;border-radius:9px;background:var(--primary-gradient)}
.ap-bar .p{width:74px;text-align:right;color:var(--text-muted);font-variant-numeric:tabular-nums}
.ap-tbl{width:100%;border-collapse:collapse;font-size:14px}
.ap-tbl th{text-align:left;padding:10px 12px;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);border-bottom:1px solid var(--glass-border)}
.ap-tbl td{padding:12px;border-bottom:1px solid var(--glass-border);color:var(--text-primary)}
.ap-tbl tr:last-child td{border-bottom:0}
.ap-pill{display:inline-block;padding:4px 11px;border-radius:20px;font-size:11px;font-weight:800}
.ap-pill.good{background:rgba(0,200,83,.14);color:#0a9d46}.ap-pill.warn{background:rgba(255,193,7,.16);color:#b07c00}
.ap-pill.bad{background:rgba(255,82,82,.13);color:#d93636}.ap-pill.mute{background:rgba(127,127,127,.16);color:var(--text-muted)}
.ap-pill.ai{background:rgba(139,92,246,.14);color:#8b5cf6;margin-left:6px}
.ap-empty{text-align:center;padding:34px 16px;color:var(--text-muted);font-size:14px;line-height:1.7}
.ap-empty .mdi{font-size:40px;display:block;margin-bottom:8px;color:var(--primary)}
.ap-form label{display:block;font-size:12px;font-weight:700;color:var(--text-secondary);margin:14px 0 6px}
.ap-form input[type=text],.ap-form input[type=url],.ap-form textarea,.ap-form select,.ap-form input[type=file]{width:100%;padding:12px 14px;border-radius:12px;border:1px solid var(--glass-border);background:var(--bg-card);color:var(--text-primary);font-family:inherit;font-size:14px}
.ap-form textarea{min-height:90px;resize:vertical}
.ap-form input:focus,.ap-form textarea:focus,.ap-form select:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px rgba(0,183,255,.15)}
.ap-vhead{display:flex;align-items:center;gap:14px;margin-bottom:12px}
.ap-vico{width:52px;height:52px;border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:28px;color:#fff;flex-shrink:0}
.ap-vico.hitune{background:var(--primary-gradient)}.ap-vico.spotify{background:#1db954}.ap-vico.apple{background:linear-gradient(135deg,#fa233b,#fb5c74)}
.ap-prev{display:flex;gap:14px;align-items:center;padding:12px;border-radius:14px;border:1px dashed var(--glass-border);margin:12px 0}
.ap-prev img{width:56px;height:56px;border-radius:12px;object-fit:cover}
.ap-note{padding:11px 14px;border-radius:12px;background:rgba(255,193,7,.12);color:#8a6400;font-size:13px;margin-top:12px;line-height:1.5}
.ap-range a{padding:7px 14px;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none;color:var(--text-secondary);border:1px solid var(--glass-border);margin-left:6px}
.ap-range a.on{background:var(--primary-gradient);color:#fff;border-color:transparent}
.ap-row{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
.ap-copy{display:flex;gap:8px}.ap-copy input{flex:1;min-width:0;padding:11px 13px;border-radius:12px;border:1px solid var(--glass-border);background:var(--bg-card);color:var(--text-primary);font-size:13px}
.ap-tiles{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px}
.ap-tile{display:flex;gap:12px;align-items:center;padding:16px;border-radius:16px;border:1px solid var(--glass-border);background:var(--glass-bg);text-decoration:none;color:var(--text-primary);font-weight:700;font-size:14px;transition:.25s}
.ap-tile:hover{transform:translateY(-3px);border-color:var(--primary)}
.ap-tile .mdi{font-size:26px;color:var(--primary)}
.ap-tile small{display:block;font-weight:500;color:var(--text-muted);font-size:12px;margin-top:2px}
.ap-banner{padding:16px 20px;border-radius:18px;background:linear-gradient(135deg,rgba(255,193,7,.18),rgba(255,152,0,.12));border:1px solid rgba(255,193,7,.4);color:var(--text-primary);margin-bottom:18px;font-size:14px;line-height:1.6}
.ap-cv{position:relative;height:280px}
@media(max-width:700px){.ap-wrap{padding:96px 16px 50px}.ap-bar .n{width:90px}.ap-tabs{top:70px}}
</style>

<main class="ap-wrap">
    <?php if ($flash): ?><div class="ap-flash <?php echo $e($flash['type']); ?>"><?php echo $e($flash['msg']); ?></div><?php endif; ?>

    <section class="ap-hero">
        <div class="ap-id">
            <div class="ap-avatar">
                <?php if (!empty($user['profile_image'])): ?><img src="<?php echo $e($user['profile_image']); ?>" alt="">
                <?php else: echo $e(mb_strtoupper(mb_substr($primaryName, 0, 1))); endif; ?>
            </div>
            <div>
                <h1><?php echo $e($primaryName); ?></h1>
                <div class="ap-badges">
                    <?php echo $badge('hitune'), $badge('spotify'), $badge('apple'); ?>
                    <?php if ($aiCount > 0): ?><span class="ap-badge ai"><span class="mdi mdi-robot-outline"></span> <?php echo (int) $aiCount; ?> AI Original</span><?php endif; ?>
                </div>
                <div class="ap-sub">Plan: <b><?php echo $e($planLabel); ?></b> &middot; <?php echo count($releases); ?> release<?php echo count($releases) === 1 ? '' : 's'; ?> &middot; <?php echo (int) $liveCount; ?> live</div>
            </div>
        </div>
        <div class="ap-actions">
            <a class="ap-btn primary" href="/index.php?q=release-create"><span class="mdi mdi-cloud-upload-outline"></span> New Release</a>
            <?php if (!empty($profile['slug'])): ?><a class="ap-btn" href="/index.php?q=artist-page&amp;u=<?php echo $e($profile['slug']); ?>" target="_blank" rel="noopener"><span class="mdi mdi-account-star-outline"></span> Public page</a><?php endif; ?>
            <?php if ($primary): ?><a class="ap-btn" href="<?php echo $e($primary['url']); ?>" target="_blank" rel="noopener"><span class="mdi mdi-open-in-new"></span> On HiTune Music</a><?php endif; ?>
        </div>
    </section>

    <nav class="ap-tabs" id="apTabs" role="tablist">
        <button class="ap-tab on" data-tab="overview"><span class="mdi mdi-view-dashboard-outline"></span> Overview</button>
        <button class="ap-tab" data-tab="analytics"><span class="mdi mdi-chart-areaspline"></span> Analytics</button>
        <button class="ap-tab" data-tab="verification"><span class="mdi mdi-check-decagram-outline"></span> Verification</button>
        <button class="ap-tab" data-tab="releases"><span class="mdi mdi-album"></span> Releases</button>
        <button class="ap-tab" data-tab="growth"><span class="mdi mdi-rocket-launch-outline"></span> Growth</button>
        <button class="ap-tab" data-tab="profile"><span class="mdi mdi-account-cog-outline"></span> Profile</button>
    </nav>

    <?php if (!$musicOk): ?>
        <div class="ap-note" style="margin:0 0 18px">HiTune Music analytics are temporarily unavailable (<?php echo $e($ov['error'] ?? 'no response'); ?>). Everything else on this page still works.</div>
    <?php elseif (!$artists): ?>
        <div class="ap-note" style="margin:0 0 18px"><b>Your HiTune Music analytics unlock after verification.</b> Get your artist profile verified in the <a href="#verification" data-tab="verification" style="color:inherit;text-decoration:underline">Verification</a> tab — plays, countries, cities and fan tips appear here automatically.</div>
    <?php endif; ?>

    <?php if (!empty($events['creator_day_active'])): ?>
        <div class="ap-banner"><b><span class="mdi mdi-party-popper"></span> Creator Day is live!</b> Fans' tips go 100% to artists until <?php echo $e(date('d M, H:i', strtotime($events['tip_event_until']))); ?> — share your HiTune Music profile now.</div>
    <?php endif; ?>

    <!-- ═════════ OVERVIEW ═════════ -->
    <section class="ap-pane on" id="pane-overview">
        <div class="ap-grid g6">
            <div class="ap-card ap-kpi"><div class="l">Plays · <?php echo (int) $days; ?>d <span class="mdi mdi-play-circle-outline"></span></div><div class="v"><?php echo ap_fmt_num($totals['window_plays'] ?? 0); ?></div><div class="s">on HiTune Music</div></div>
            <div class="ap-card ap-kpi"><div class="l">Lifetime plays <span class="mdi mdi-headphones"></span></div><div class="v"><?php echo ap_fmt_num($totals['plays'] ?? 0); ?></div><div class="s"><?php echo ap_fmt_num($totals['unique'] ?? 0); ?> unique listeners</div></div>
            <div class="ap-card ap-kpi"><div class="l">Followers <span class="mdi mdi-account-heart-outline"></span></div><div class="v"><?php echo ap_fmt_num($primary['followers'] ?? 0); ?></div><div class="s"><?php echo ap_fmt_num($totals['likes'] ?? 0); ?> likes</div></div>
            <div class="ap-card ap-kpi"><div class="l">Fan tips <span class="mdi mdi-gift-outline"></span></div><div class="v">₹<?php echo number_format((float) ($totals['tips_amount'] ?? 0), 0); ?></div><div class="s"><?php echo (int) ($totals['tips_count'] ?? 0); ?> tips received</div></div>
            <div class="ap-card ap-kpi"><div class="l">Store royalties <span class="mdi mdi-cash-multiple"></span></div><div class="v">₹<?php echo number_format($life['royalties'], 0); ?></div><div class="s"><?php echo ap_fmt_num($life['plays']); ?> streams reported</div></div>
            <div class="ap-card ap-kpi"><div class="l">Live releases <span class="mdi mdi-album"></span></div><div class="v"><?php echo (int) $liveCount; ?></div><div class="s"><?php echo count($releases); ?> total</div></div>
        </div>

        <div class="ap-grid g2" style="margin-top:18px">
            <div class="ap-card">
                <div class="ap-row"><h3><span class="mdi mdi-chart-line"></span> Plays — last <?php echo (int) $days; ?> days</h3>
                    <span class="ap-range"><?php foreach ([7, 30, 90] as $d): ?><a class="<?php echo $d === $days ? 'on' : ''; ?>" href="/index.php?q=artist-panel&amp;days=<?php echo $d; ?>"><?php echo $d; ?>d</a><?php endforeach; ?></span></div>
                <?php if (array_sum($series['values']) > 0): ?><div class="ap-cv"><canvas id="cvPlays"></canvas></div>
                <?php else: ?><div class="ap-empty"><span class="mdi mdi-chart-timeline-variant"></span>No plays recorded in this window yet.<br>Once your release is live on HiTune Music, every play shows up here in real time.</div><?php endif; ?>
            </div>
            <div class="ap-card">
                <h3><span class="mdi mdi-earth"></span> Where your fans are</h3>
                <?php if (!empty($geo['available'])): foreach (array_slice($geo['countries'], 0, 6) as $c): ?>
                    <div class="ap-bar"><span class="n"><?php echo $e($c['name']); ?></span><span class="t"><i style="width:<?php echo (float) $c['pct']; ?>%"></i></span><span class="p"><?php echo number_format($c['plays']); ?> · <?php echo $c['pct']; ?>%</span></div>
                <?php endforeach; else: ?><div class="ap-empty"><span class="mdi mdi-map-marker-radius-outline"></span>Country &amp; city data starts filling in from the next play onwards.</div><?php endif; ?>
            </div>
        </div>

        <div class="ap-card" style="margin-top:18px">
            <h3><span class="mdi mdi-lightning-bolt-outline"></span> Quick actions</h3>
            <div class="ap-tiles">
                <a class="ap-tile" href="/index.php?q=release-create"><span class="mdi mdi-cloud-upload-outline"></span><span>Upload a release<small>Goes live on HiTune Music &amp; IyolMe first</small></span></a>
                <a class="ap-tile" href="https://music.hitune.in/ai-studio" target="_blank" rel="noopener"><span class="mdi mdi-auto-fix"></span><span>AI Studio<small>Cover art · mastering · lyrics sync · karaoke</small></span></a>
                <a class="ap-tile" href="/index.php?q=splits"><span class="mdi mdi-call-split"></span><span>Royalty splits<small>Share earnings with collaborators</small></span></a>
                <a class="ap-tile" href="/index.php?q=payouts"><span class="mdi mdi-bank-transfer-out"></span><span>Payouts<small>Withdraw to bank / UPI</small></span></a>
                <a class="ap-tile" href="#growth" data-tab="growth"><span class="mdi mdi-account-multiple-plus-outline"></span><span>Refer artists<small>Earn credit for every artist you bring</small></span></a>
            </div>
        </div>
    </section>

    <!-- ═════════ ANALYTICS ═════════ -->
    <section class="ap-pane" id="pane-analytics">
        <div class="ap-row" style="margin-bottom:16px">
            <div><?php if (count($artists) > 1): ?>
                <form method="get" action="/index.php" style="display:inline"><input type="hidden" name="q" value="artist-panel"><input type="hidden" name="days" value="<?php echo (int) $days; ?>">
                <select name="artist" onchange="this.form.submit()" class="ap-btn sm" style="background:var(--bg-card)"><?php foreach ($artists as $a): ?><option value="<?php echo (int) $a['id']; ?>" <?php echo $primary && (int) $primary['id'] === (int) $a['id'] ? 'selected' : ''; ?>><?php echo $e($a['name']); ?></option><?php endforeach; ?></select></form>
            <?php else: ?><span class="ap-muted">HiTune Music · live from the player</span><?php endif; ?></div>
            <span class="ap-range"><?php foreach ([7, 30, 90] as $d): ?><a class="<?php echo $d === $days ? 'on' : ''; ?>" href="/index.php?q=artist-panel&amp;days=<?php echo $d; ?>#analytics"><?php echo $d; ?> days</a><?php endforeach; ?>
                <a href="/index.php?q=artist-panel&amp;days=<?php echo (int) $days; ?>&amp;refresh=1#analytics" title="Refresh now"><span class="mdi mdi-refresh"></span></a></span>
        </div>

        <div class="ap-grid g6">
            <div class="ap-card ap-kpi"><div class="l">Plays</div><div class="v"><?php echo ap_fmt_num($totals['window_plays'] ?? 0); ?></div><div class="s">last <?php echo (int) $days; ?> days</div></div>
            <div class="ap-card ap-kpi"><div class="l">Listeners</div><div class="v"><?php echo ap_fmt_num($totals['listeners_window'] ?? 0); ?></div><div class="s">signed-in, last <?php echo (int) $days; ?>d</div></div>
            <div class="ap-card ap-kpi"><div class="l">Likes</div><div class="v"><?php echo ap_fmt_num($totals['likes'] ?? 0); ?></div><div class="s">lifetime</div></div>
            <div class="ap-card ap-kpi"><div class="l">Playlist adds</div><div class="v"><?php echo ap_fmt_num($totals['playlists'] ?? 0); ?></div><div class="s">lifetime</div></div>
            <div class="ap-card ap-kpi"><div class="l">Shares</div><div class="v"><?php echo ap_fmt_num($totals['shares'] ?? 0); ?></div><div class="s"><?php echo ap_fmt_num($totals['downloads'] ?? 0); ?> downloads</div></div>
            <div class="ap-card ap-kpi"><div class="l">Chart position</div><div class="v"><?php echo !empty($primary['rank']) ? '#' . (int) $primary['rank']['position'] : '—'; ?></div><div class="s"><?php echo !empty($primary['rank']) ? $e($primary['rank']['track']) : 'no plays yet'; ?></div></div>
        </div>

        <div class="ap-grid g2" style="margin-top:18px">
            <div class="ap-card"><h3><span class="mdi mdi-flag-outline"></span> Top countries</h3>
                <?php if (!empty($geo['countries'])): foreach ($geo['countries'] as $c): ?>
                    <div class="ap-bar"><span class="n"><?php echo $e($c['name']); ?></span><span class="t"><i style="width:<?php echo (float) $c['pct']; ?>%"></i></span><span class="p"><?php echo number_format($c['plays']); ?> · <?php echo $c['pct']; ?>%</span></div>
                <?php endforeach; else: ?><div class="ap-empty"><span class="mdi mdi-flag-outline"></span>No located plays in this window.</div><?php endif; ?></div>
            <div class="ap-card"><h3><span class="mdi mdi-city-variant-outline"></span> Top cities</h3>
                <?php if (!empty($geo['cities'])): foreach ($geo['cities'] as $c): ?>
                    <div class="ap-bar"><span class="n"><?php echo $e($c['city']); ?><?php echo $c['country'] ? ', ' . $e($c['cc']) : ''; ?></span><span class="t"><i style="width:<?php echo (float) $c['pct']; ?>%"></i></span><span class="p"><?php echo number_format($c['plays']); ?> · <?php echo $c['pct']; ?>%</span></div>
                <?php endforeach; else: ?><div class="ap-empty"><span class="mdi mdi-city-variant-outline"></span>City data appears as listeners play your tracks.</div><?php endif; ?></div>
        </div>

        <div class="ap-grid g2" style="margin-top:18px">
            <div class="ap-card"><h3><span class="mdi mdi-cellphone-link"></span> Web vs app</h3>
                <?php $pl = $primary['platforms'] ?? []; $plt = array_sum($pl);
                if ($plt > 0): foreach (['web' => 'Website', 'app' => 'HiTune Music app'] as $k => $lbl): $v = (int) ($pl[$k] ?? 0); $pc = round(100 * $v / $plt, 1); ?>
                    <div class="ap-bar"><span class="n"><?php echo $lbl; ?></span><span class="t"><i style="width:<?php echo $pc; ?>%"></i></span><span class="p"><?php echo number_format($v); ?> · <?php echo $pc; ?>%</span></div>
                <?php endforeach; else: ?><div class="ap-empty"><span class="mdi mdi-cellphone-link"></span>Split appears once plays are recorded.</div><?php endif; ?></div>
            <div class="ap-card"><h3><span class="mdi mdi-podium-gold"></span> Top tracks on HiTune Music</h3>
                <?php if (!empty($primary['top_tracks'])): ?><table class="ap-tbl"><tr><th>Track</th><th>Plays</th><th>Likes</th></tr>
                    <?php foreach (array_slice($primary['top_tracks'], 0, 6) as $t): ?>
                        <tr><td><a href="<?php echo $e($t['url']); ?>" target="_blank" rel="noopener" style="color:inherit"><?php echo $e($t['title']); ?></a><?php echo $t['ai_pct'] > 0 ? '<span class="ap-pill ai">AI Original</span>' : ''; ?></td><td><?php echo number_format($t['plays']); ?></td><td><?php echo number_format($t['likes']); ?></td></tr>
                    <?php endforeach; ?></table>
                <?php else: ?><div class="ap-empty"><span class="mdi mdi-music-note-outline"></span>Your tracks appear here after they go live.</div><?php endif; ?></div>
        </div>

        <div class="ap-card" style="margin-top:18px">
            <h3><span class="mdi mdi-store-outline"></span> Streaming platforms — from store reports</h3>
            <?php if ($dsp): ?><table class="ap-tbl"><tr><th>Platform</th><th>Streams</th><th>Revenue</th><th>Your royalties</th></tr>
                <?php foreach ($dsp as $d):
                    $pn = (string) $d['platform']; $ic = stripos($pn, 'spotify') !== false ? 'mdi-spotify' : (stripos($pn, 'apple') !== false ? 'mdi-apple' : (stripos($pn, 'youtube') !== false ? 'mdi-youtube' : 'mdi-music-circle-outline')); ?>
                    <tr><td><span class="mdi <?php echo $ic; ?>"></span> <?php echo $e($pn); ?></td><td><?php echo number_format((int) $d['s']); ?></td><td>₹<?php echo number_format((float) $d['r'], 2); ?></td><td><b>₹<?php echo number_format((float) $d['y'], 2); ?></b></td></tr>
                <?php endforeach; ?></table>
                <p class="ap-muted" style="margin-top:10px">Spotify, Apple Music and other store numbers come from the monthly reports our team imports — this is the official count of streams and earnings.</p>
            <?php else: ?><div class="ap-empty"><span class="mdi mdi-file-chart-outline"></span>Store reports are imported monthly after your release goes live on Spotify, Apple Music and the other stores. They appear here automatically.</div><?php endif; ?>
        </div>

        <?php
        $spMeta = null; $apMeta = null;
        foreach ($verifs['spotify'] as $v) { if ($v['status'] === 'verified' && !empty($v['meta_arr']['ok'])) { $spMeta = $v; break; } }
        foreach ($verifs['apple'] as $v)   { if ($v['status'] === 'verified' && !empty($v['meta_arr']['ok'])) { $apMeta = $v; break; } }
        if ($spMeta || $apMeta): ?>
        <div class="ap-grid g2" style="margin-top:18px">
            <?php if ($spMeta): $m = $spMeta['meta_arr']; ?><div class="ap-card"><h3><span class="mdi mdi-spotify" style="color:#1db954"></span> Spotify profile</h3>
                <div class="ap-prev"><?php if (!empty($m['image'])): ?><img src="<?php echo $e($m['image']); ?>" alt=""><?php endif; ?><div><b><?php echo $e($m['name'] ?? ''); ?></b>
                <div class="ap-muted"><?php echo isset($m['followers']) ? number_format($m['followers']) . ' followers · popularity ' . (int) ($m['popularity'] ?? 0) . '/100' : 'Profile linked'; ?></div>
                <a href="<?php echo $e($spMeta['profile_url']); ?>" target="_blank" rel="noopener" style="font-size:13px">Open on Spotify ↗</a></div></div></div><?php endif; ?>
            <?php if ($apMeta): $m = $apMeta['meta_arr']; ?><div class="ap-card"><h3><span class="mdi mdi-apple" style="color:#fa233b"></span> Apple Music profile</h3>
                <div class="ap-prev"><?php if (!empty($m['image'])): ?><img src="<?php echo $e($m['image']); ?>" alt=""><?php endif; ?><div><b><?php echo $e($m['name'] ?? ''); ?></b>
                <div class="ap-muted"><?php echo $e($m['genre'] ?? ''); ?> · <?php echo (int) ($m['albums'] ?? 0); ?> albums found</div>
                <a href="<?php echo $e($apMeta['profile_url']); ?>" target="_blank" rel="noopener" style="font-size:13px">Open on Apple Music ↗</a></div></div></div><?php endif; ?>
        </div>
        <?php endif; ?>
    </section>

    <!-- ═════════ VERIFICATION ═════════ -->
    <section class="ap-pane" id="pane-verification">
        <p class="ap-muted" style="margin:0 0 18px;max-width:780px">Verified artists get a blue tick on their profile, unlock analytics, and are eligible for playlist pitching and contest prizes. Verify once per platform — most requests are reviewed within 1–2 business days.</p>
        <div class="ap-grid g3">
        <?php foreach (['hitune', 'spotify', 'apple'] as $p):
            $rows = $verifs[$p]; $best = ap_platform_state($rows); $s = $state[$p]; ?>
            <div class="ap-card">
                <div class="ap-vhead"><div class="ap-vico <?php echo $p; ?>"><span class="mdi <?php echo $platIcon[$p]; ?>"></span></div>
                    <div><h3 style="margin:0"><?php echo $e($platName[$p]); ?></h3>
                    <span class="ap-pill <?php echo $s === 'verified' ? 'good' : ($s === 'pending' ? 'warn' : 'mute'); ?>"><?php echo $s === 'verified' ? 'Verified' : ($s === 'pending' ? 'In review' : 'Not verified'); ?></span></div></div>

                <?php if ($p === 'hitune'): ?>
                    <p class="ap-muted">Confirms you manage your artist page on HiTune Music (music.hitune.in). Replaces the old “Artist verification” form.</p>
                    <?php foreach ($artists as $a): if (empty($a['verified'])) continue; ?>
                        <div class="ap-prev"><span class="mdi mdi-check-decagram" style="font-size:30px;color:#00c853"></span><div><b><?php echo $e($a['name']); ?></b><div class="ap-muted"><?php echo number_format($a['followers']); ?> followers</div><a href="<?php echo $e($a['url']); ?>" target="_blank" rel="noopener" style="font-size:13px">Open profile ↗</a></div></div>
                    <?php endforeach; ?>
                <?php elseif ($p === 'spotify'): ?>
                    <p class="ap-muted">Link your Spotify artist profile. It is verified <b>automatically</b> when one of your HiTune releases (same ISRC) is found on that profile; otherwise our team checks it.</p>
                <?php else: ?>
                    <p class="ap-muted">Link your Apple Music artist page. We match its catalogue with your HiTune releases and our team confirms ownership.</p>
                <?php endif; ?>

                <?php foreach ($rows as $v): if ($v['status'] === 'verified' && $p === 'hitune') continue; $m = $v['meta_arr']; ?>
                    <div class="ap-prev">
                        <?php if (!empty($m['image'])): ?><img src="<?php echo $e($m['image']); ?>" alt=""><?php endif; ?>
                        <div style="min-width:0"><b><?php echo $e($v['artist_name']); ?></b>
                        <span class="ap-pill <?php echo $v['status'] === 'verified' ? 'good' : ($v['status'] === 'pending' ? 'warn' : 'bad'); ?>" style="margin-left:6px"><?php echo $e($v['status']); ?></span>
                        <?php if ($v['profile_url']): ?><div class="ap-muted" style="word-break:break-all"><a href="<?php echo $e($v['profile_url']); ?>" target="_blank" rel="noopener"><?php echo $e($v['profile_url']); ?></a></div><?php endif; ?>
                        <?php if (isset($m['name_similarity'])): ?><div class="ap-muted">Name match <?php echo (float) $m['name_similarity']; ?>%<?php if (!empty($m['isrc_matches'])): ?> · ISRC match ✓<?php endif; ?><?php if (!empty($m['title_matches'])): ?> · <?php echo count($m['title_matches']); ?> title match<?php echo count($m['title_matches']) > 1 ? 'es' : ''; ?><?php endif; ?></div><?php endif; ?>
                        <?php if (!empty($v['admin_notes'])): ?><div class="ap-note"><b>Team note:</b> <?php echo nl2br($e($v['admin_notes'])); ?></div><?php endif; ?></div>
                    </div>
                <?php endforeach; ?>

                <?php if ($s !== 'verified' && $s !== 'pending' || ($p === 'hitune' && $s !== 'verified')): ?>
                <form class="ap-form" method="post" action="/index.php?q=artist-panel" <?php echo $p === 'hitune' ? 'enctype="multipart/form-data"' : ''; ?>>
                    <?php echo csrfField(); ?><input type="hidden" name="ap_action" value="verify_<?php echo $p; ?>">
                    <label>Artist / stage name</label>
                    <input type="text" name="artist_name" list="ap-names" maxlength="120" required value="<?php echo $e($stageNames[0] ?? ''); ?>" placeholder="e.g. Rohitak Rock">
                    <?php if ($p === 'hitune'): ?>
                        <label>Legal name</label><input type="text" name="real_name" maxlength="150" required value="<?php echo $e($user['name']); ?>">
                        <label>Proof link (Instagram / YouTube / website)</label><input type="url" name="profile_url" placeholder="https://instagram.com/yourhandle">
                        <label>Proof document (optional · JPG, PNG, PDF · 5 MB)</label><input type="file" name="proof_doc" accept="image/jpeg,image/png,image/webp,application/pdf">
                        <label>Anything else we should know</label><textarea name="note" maxlength="1500"></textarea>
                    <?php elseif ($p === 'spotify'): ?>
                        <label>Spotify artist link</label><input type="text" name="profile_url" required placeholder="https://open.spotify.com/artist/…">
                    <?php else: ?>
                        <label>Apple Music artist link</label><input type="text" name="profile_url" required placeholder="https://music.apple.com/in/artist/…/123456789">
                    <?php endif; ?>
                    <button class="ap-btn primary" style="margin-top:16px;width:100%;justify-content:center" type="submit"><span class="mdi mdi-shield-check-outline"></span> <?php echo $s === 'none' ? 'Request verification' : 'Submit again'; ?></button>
                </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        </div>
        <datalist id="ap-names"><?php foreach ($stageNames as $n): ?><option value="<?php echo $e($n); ?>"><?php endforeach; ?></datalist>

        <?php if ($musicOk && !empty($ov['candidates'])): ?>
            <div class="ap-card" style="margin-top:18px"><h3><span class="mdi mdi-account-search-outline"></span> Your artist names on HiTune Music</h3>
                <table class="ap-tbl"><tr><th>Name</th><th>Status on HiTune Music</th></tr>
                <?php foreach ($ov['candidates'] as $c): ?><tr><td><?php echo $e($c['name']); ?></td><td><?php
                    echo ['not_in_catalog' => 'Not in the catalogue yet — appears when your first release goes live', 'claimable' => 'In the catalogue and unclaimed — you can verify it', 'managed_by_other' => 'Managed by another account — contact support with proof'][$c['status']] ?? $e($c['status']); ?></td></tr><?php endforeach; ?></table></div>
        <?php endif; ?>
    </section>

    <!-- ═════════ RELEASES ═════════ -->
    <section class="ap-pane" id="pane-releases">
        <div class="ap-card">
            <div class="ap-row"><h3><span class="mdi mdi-album"></span> Releases &amp; delivery status</h3><a class="ap-btn primary sm" href="/index.php?q=release-create"><span class="mdi mdi-plus"></span> New release</a></div>
            <?php if ($releases): ?><div style="overflow-x:auto"><table class="ap-tbl"><tr><th></th><th>Release</th><th>Type</th><th>Status</th><th>Release date</th><th></th></tr>
                <?php foreach ($releases as $r): $lbl = $statusLabel($r); ?>
                    <tr><td style="width:56px"><?php if (!empty($r['cover_art_path'])): ?><img src="/<?php echo $e(ltrim($r['cover_art_path'], '/')); ?>" alt="" style="width:44px;height:44px;border-radius:10px;object-fit:cover"><?php else: ?><span class="mdi mdi-album" style="font-size:34px;color:var(--text-muted)"></span><?php endif; ?></td>
                    <td><b><?php echo $e($r['title']); ?></b><?php echo (int) $r['ai_pct'] > 0 ? '<span class="ap-pill ai">AI Original</span>' : ''; ?><div class="ap-muted"><?php echo $e($r['primary_artist']); ?></div></td>
                    <td style="text-transform:capitalize"><?php echo $e($r['release_type']); ?></td>
                    <td><span class="ap-pill <?php echo $statusColor($lbl); ?>"><?php echo $e($lbl); ?></span>
                        <?php if (($r['status'] === 'rejected') && !empty($r['admin_notes'])): ?><div class="ap-muted" style="margin-top:4px"><?php echo $e(mb_strimwidth($r['admin_notes'], 0, 90, '…')); ?></div><?php endif; ?></td>
                    <td><?php echo $r['release_date'] ? $e(date('d M Y', strtotime($r['release_date']))) : '—'; ?></td>
                    <td><a class="ap-btn sm" href="/index.php?q=release-view&amp;id=<?php echo (int) $r['id']; ?>">Open</a></td></tr>
                <?php endforeach; ?></table></div>
            <?php else: ?><div class="ap-empty"><span class="mdi mdi-cloud-upload-outline"></span>No releases yet. Upload your first song — it goes live on HiTune Music &amp; IyolMe first, then on 150+ stores.<br><br><a class="ap-btn primary" href="/index.php?q=release-create">Upload your first release</a></div><?php endif; ?>
        </div>
    </section>

    <!-- ═════════ GROWTH ═════════ -->
    <section class="ap-pane" id="pane-growth">
        <div class="ap-grid g2">
            <div class="ap-card"><h3><span class="mdi mdi-account-multiple-plus-outline"></span> Refer artists, earn credit</h3>
                <p class="ap-muted">Every artist who signs up with your link and publishes their first release earns you credit that you can withdraw with your royalties.</p>
                <div class="ap-copy" style="margin:14px 0"><input type="text" readonly id="apRef" value="<?php echo $e($refLink); ?>"><button class="ap-btn sm" type="button" id="apCopy"><span class="mdi mdi-content-copy"></span> Copy</button></div>
                <div class="ap-row"><span><b><?php echo (int) $refCount; ?></b> <span class="ap-muted">signups</span></span><span><b>₹<?php echo number_format($refCredit, 2); ?></b> <span class="ap-muted">credit earned</span></span></div>
            </div>
            <div class="ap-card"><h3><span class="mdi mdi-party-popper"></span> Creator Day &amp; fan tips</h3>
                <p class="ap-muted">Listeners send you tips from the HiTune Music app and site. You keep <b><?php echo (int) ($events['tip_artist_pct'] ?? 80); ?>%</b> of every tip — and <b>100%</b> during Creator Day windows.</p>
                <?php if (!empty($events['creator_day_active'])): ?><div class="ap-note" style="background:rgba(0,200,83,.12);color:#0a9d46"><b>Live now</b> until <?php echo $e(date('d M, H:i', strtotime($events['tip_event_until']))); ?> — 0% commission.</div>
                <?php else: ?><div class="ap-note">No Creator Day running right now. We announce the next one on the HiTune Music home page.</div><?php endif; ?>
                <p style="margin-top:12px"><b>₹<?php echo number_format((float) ($totals['tips_amount'] ?? 0), 2); ?></b> <span class="ap-muted">received from <?php echo (int) ($totals['tips_count'] ?? 0); ?> tips</span></p>
            </div>
            <div class="ap-card"><h3><span class="mdi mdi-trophy-outline"></span> Collab competitions</h3>
                <?php if ($contests): foreach ($contests as $c): ?>
                    <div class="ap-prev"><span class="mdi mdi-trophy" style="font-size:30px;color:#f5b301"></span><div><b><?php echo $e($c['title']); ?></b><div class="ap-muted"><?php echo $e($c['prize'] ?: 'Free distribution + homepage feature'); ?><?php echo !empty($c['ends_at']) ? ' · ends ' . $e(date('d M', strtotime($c['ends_at']))) : ''; ?></div></div></div>
                <?php endforeach; ?><a class="ap-btn sm" href="https://music.hitune.in/hub" target="_blank" rel="noopener">See leaderboards ↗</a>
                <?php else: ?><div class="ap-empty" style="padding:18px"><span class="mdi mdi-trophy-outline"></span>Monthly “Best AI Song” and “Best Indie Track” contests appear here when they open.</div><?php endif; ?>
            </div>
            <div class="ap-card"><h3><span class="mdi mdi-auto-fix"></span> AI Creator Studio</h3>
                <p class="ap-muted">Make cover art, master your track, sync lyrics, remove vocals for karaoke — or create a song from a prompt. Quotas depend on your plan.</p>
                <a class="ap-btn primary" style="margin-top:12px" href="https://music.hitune.in/ai-studio" target="_blank" rel="noopener"><span class="mdi mdi-open-in-new"></span> Open AI Studio</a>
            </div>
        </div>
    </section>

    <!-- ═════════ PROFILE ═════════ -->
    <section class="ap-pane" id="pane-profile">
        <div class="ap-grid g2">
            <div class="ap-card"><h3><span class="mdi mdi-account-cog-outline"></span> Artist profile</h3>
                <form class="ap-form" method="post" action="/index.php?q=artist-panel"><?php echo csrfField(); ?><input type="hidden" name="ap_action" value="save_profile">
                    <label>Stage name</label><input type="text" name="stage_name" required maxlength="120" value="<?php echo $e($profile['stage_name'] ?? $primaryName); ?>">
                    <label>Bio</label><textarea name="bio" maxlength="1500" placeholder="Tell fans who you are…"><?php echo $e($profile['bio'] ?? ''); ?></textarea>
                    <label>Main genre</label><input type="text" name="genre" maxlength="100" value="<?php echo $e($profile['genre'] ?? ''); ?>" placeholder="Hip-Hop, Pop, Lo-fi…">
                    <div class="ap-grid" style="grid-template-columns:1fr 1fr;gap:12px"><div><label>Country</label><input type="text" name="country" maxlength="100" value="<?php echo $e($profile['country'] ?? ''); ?>"></div><div><label>City</label><input type="text" name="city" maxlength="100" value="<?php echo $e($profile['city'] ?? ''); ?>"></div></div>
                    <label>Website</label><input type="text" name="website" maxlength="255" value="<?php echo $e($profile['website'] ?? ''); ?>" placeholder="https://">
                    <label>Instagram</label><input type="text" name="instagram" maxlength="255" value="<?php echo $e($profile['instagram'] ?? ''); ?>" placeholder="https://instagram.com/…">
                    <label>YouTube</label><input type="text" name="youtube" maxlength="255" value="<?php echo $e($profile['youtube'] ?? ''); ?>" placeholder="https://youtube.com/@…">
                    <button class="ap-btn primary" style="margin-top:18px" type="submit"><span class="mdi mdi-content-save-outline"></span> Save profile</button>
                </form></div>
            <div class="ap-card"><h3><span class="mdi mdi-account-star-outline"></span> Your public artist page</h3>
                <?php if (!empty($profile['slug'])): ?>
                    <p class="ap-muted">Share this link anywhere — it shows your bio, verified badges and live releases.</p>
                    <div class="ap-copy" style="margin:14px 0"><input type="text" readonly value="https://distribution.hitune.in/index.php?q=artist-page&amp;u=<?php echo $e($profile['slug']); ?>"></div>
                    <a class="ap-btn" href="/index.php?q=artist-page&amp;u=<?php echo $e($profile['slug']); ?>" target="_blank" rel="noopener"><span class="mdi mdi-open-in-new"></span> Preview</a>
                <?php else: ?><div class="ap-empty"><span class="mdi mdi-account-star-outline"></span>Save your profile to get a public artist page you can share.</div><?php endif; ?>
            </div>
        </div>
    </section>
</main>

<script>
(function () {
    var tabs = document.querySelectorAll('[data-tab]');
    function show(name) {
        if (!document.getElementById('pane-' + name)) name = 'overview';
        document.querySelectorAll('.ap-pane').forEach(function (p) { p.classList.toggle('on', p.id === 'pane-' + name); });
        document.querySelectorAll('.ap-tab').forEach(function (t) { t.classList.toggle('on', t.getAttribute('data-tab') === name); });
        if (window.apCharts) window.apCharts();
    }
    tabs.forEach(function (t) { t.addEventListener('click', function (ev) { ev.preventDefault(); var n = t.getAttribute('data-tab'); history.replaceState(null, '', '#' + n); show(n); window.scrollTo({ top: document.getElementById('apTabs').offsetTop - 90, behavior: 'smooth' }); }); });
    window.addEventListener('hashchange', function () { show(location.hash.replace('#', '')); });
    show(location.hash.replace('#', '') || 'overview');
    var copy = document.getElementById('apCopy');
    if (copy) copy.addEventListener('click', function () { var i = document.getElementById('apRef'); i.select(); (navigator.clipboard ? navigator.clipboard.writeText(i.value) : Promise.reject()).catch(function () { document.execCommand('copy'); }); copy.innerHTML = '<span class="mdi mdi-check"></span> Copied'; });
})();

document.addEventListener('DOMContentLoaded', function () {
    var labels = <?php echo json_encode(array_map(function ($d) { return date('d M', strtotime($d)); }, $series['labels']), JSON_UNESCAPED_UNICODE); ?>;
    var values = <?php echo json_encode(array_map('intval', $series['values'])); ?>;
    var chart = null;
    function draw() {
        var el = document.getElementById('cvPlays');
        if (!el || typeof Chart === 'undefined' || chart) return;
        var dark = document.documentElement.getAttribute('data-theme') === 'dark';
        var tick = dark ? 'rgba(255,255,255,.6)' : 'rgba(15,23,42,.6)', grid = dark ? 'rgba(255,255,255,.07)' : 'rgba(15,23,42,.07)';
        var g = el.getContext('2d').createLinearGradient(0, 0, 0, 280);
        g.addColorStop(0, 'rgba(0,183,255,.45)'); g.addColorStop(1, 'rgba(139,92,246,.02)');
        chart = new Chart(el.getContext('2d'), { type: 'line', data: { labels: labels, datasets: [{ data: values, borderColor: '#00b7ff', backgroundColor: g, fill: true, tension: .38, pointRadius: 0, pointHoverRadius: 5, borderWidth: 3 }] },
            options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, interaction: { intersect: false, mode: 'index' },
                scales: { x: { ticks: { color: tick, maxTicksLimit: 8 }, grid: { display: false } }, y: { beginAtZero: true, ticks: { color: tick, precision: 0 }, grid: { color: grid } } } } });
    }
    window.apCharts = draw;
    draw();
    new MutationObserver(function () { if (chart) { chart.destroy(); chart = null; draw(); } }).observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
});
</script>

<?php include __DIR__ . '/../includes/footer_premium.php'; ?>
