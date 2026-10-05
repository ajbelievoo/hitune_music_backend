<?php
/**
 * HiTune Music Distribution — public artist page
 * /index.php?q=artist-page&u=<slug>
 * Shows bio, verified badges and live releases. No login required.
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/artist_panel.php';

ap_ensure_tables($conn);
$e = function ($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };

$slug = preg_replace('/[^a-z0-9\-]/', '', strtolower((string) ($_GET['u'] ?? '')));
$profile = null;
if ($slug !== '') {
    $st = $conn->prepare("SELECT p.*, u.name AS user_name FROM artist_profiles p JOIN users u ON u.id = p.user_id WHERE p.slug = ? LIMIT 1");
    $st->bind_param("s", $slug);
    $st->execute();
    $profile = $st->get_result()->fetch_assoc() ?: null;
    $st->close();
}

if (!$profile) {
    http_response_code(404);
    $pageTitle = 'Artist not found - HiTune Music Distribution';
    $path = 'artist-page';
    include __DIR__ . '/../includes/header_premium.php';
    echo '<main style="max-width:700px;margin:0 auto;padding:160px 24px;text-align:center"><h1 style="font-size:32px;font-weight:800">Artist not found</h1>'
       . '<p style="opacity:.7;margin:14px 0 26px">This artist page does not exist or was moved.</p><a href="/" class="nav-btn nav-btn-primary" style="display:inline-flex">Back to HiTune</a></main>';
    include __DIR__ . '/../includes/footer_premium.php';
    return;
}

$uid = (int) $profile['user_id'];
$name = $profile['stage_name'] ?: $profile['user_name'];

$verified = [];
$st = $conn->prepare("SELECT platform, profile_url FROM artist_verifications WHERE user_id = ? AND status = 'verified'");
$st->bind_param("i", $uid);
$st->execute();
$res = $st->get_result();
while ($r = $res->fetch_assoc()) $verified[$r['platform']] = $r['profile_url'];
$st->close();

$releases = [];
$st = $conn->prepare("SELECT id, title, release_type, release_date, cover_art_path, ai_pct FROM releases
    WHERE user_id = ? AND status IN ('ready','live') ORDER BY COALESCE(release_date, created_at) DESC LIMIT 24");
$st->bind_param("i", $uid);
$st->execute();
$res = $st->get_result();
while ($r = $res->fetch_assoc()) $releases[$r['id']] = $r + ['links' => []];
$st->close();
if ($releases) {
    $in = implode(',', array_map('intval', array_keys($releases)));
    $res = $conn->query("SELECT release_id, platform_name, store_url FROM release_platforms
        WHERE release_id IN ({$in}) AND delivery_status = 'live' AND store_url IS NOT NULL AND store_url <> ''");
    while ($res && ($r = $res->fetch_assoc())) $releases[$r['release_id']]['links'][] = $r;
}

$isUrl = function ($u) { return (bool) preg_match('~^https?://~i', (string) $u); };
$pageTitle = $name . ' — Artist on HiTune';
$metaDescription = $profile['bio'] ? mb_substr(strip_tags($profile['bio']), 0, 155) : ($name . ' on HiTune Music — listen to releases distributed worldwide.');
$canonicalUrl = 'https://distribution.hitune.in/index.php?q=artist-page&u=' . $slug;
$path = 'artist-page';
$jsonLd = ['@context' => 'https://schema.org', '@type' => 'MusicGroup', 'name' => $name, 'url' => $canonicalUrl,
    'description' => $metaDescription, 'genre' => $profile['genre'] ?: null];
include __DIR__ . '/../includes/header_premium.php';

$platIcon = ['hitune' => 'mdi-music-circle', 'spotify' => 'mdi-spotify', 'apple' => 'mdi-apple'];
$platName = ['hitune' => 'HiTune Music', 'spotify' => 'Spotify', 'apple' => 'Apple Music'];
?>
<style>
.pa-wrap{max-width:1000px;margin:0 auto;padding:120px 24px 70px}
.pa-hero{text-align:center;padding:38px 24px;border-radius:28px;background:linear-gradient(135deg,rgba(0,183,255,.14),rgba(139,92,246,.14));border:1px solid var(--glass-border);backdrop-filter:blur(18px)}
.pa-av{width:110px;height:110px;border-radius:30px;margin:0 auto 16px;background:var(--primary-gradient);color:#fff;font-size:48px;font-weight:800;display:flex;align-items:center;justify-content:center;box-shadow:0 14px 40px rgba(0,183,255,.35)}
.pa-hero h1{font-size:clamp(28px,5vw,42px);font-weight:800;margin:0 0 10px;color:var(--text-primary)}
.pa-meta{color:var(--text-muted);font-size:14px}
.pa-badges{display:flex;justify-content:center;gap:8px;flex-wrap:wrap;margin:16px 0}
.pa-badge{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:30px;font-size:12px;font-weight:700;color:#0a9d46;background:rgba(0,200,83,.12);border:1px solid rgba(0,200,83,.4);text-decoration:none}
.pa-bio{max-width:640px;margin:14px auto 0;color:var(--text-secondary);line-height:1.7;font-size:15px;white-space:pre-line}
.pa-links{display:flex;justify-content:center;gap:10px;margin-top:18px;flex-wrap:wrap}
.pa-links a{padding:9px 16px;border-radius:12px;border:1px solid var(--glass-border);background:var(--glass-bg);color:var(--text-primary);text-decoration:none;font-weight:600;font-size:13px}
.pa-h{font-size:22px;font-weight:800;margin:38px 0 16px;color:var(--text-primary)}
.pa-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:18px}
.pa-card{background:var(--glass-bg);border:1px solid var(--glass-border);border-radius:20px;overflow:hidden;box-shadow:var(--shadow-card);transition:.25s}
.pa-card:hover{transform:translateY(-4px)}
.pa-cover{aspect-ratio:1;background:var(--primary-gradient);display:flex;align-items:center;justify-content:center;color:#fff;font-size:54px}
.pa-cover img{width:100%;height:100%;object-fit:cover}
.pa-info{padding:14px}.pa-info b{display:block;color:var(--text-primary);font-size:15px}.pa-info span{font-size:12px;color:var(--text-muted);text-transform:capitalize}
.pa-info .l{display:flex;gap:8px;margin-top:8px;flex-wrap:wrap}.pa-info .l a{font-size:12px;color:var(--primary);text-decoration:none;font-weight:700;text-transform:none}
</style>
<main class="pa-wrap">
    <section class="pa-hero">
        <div class="pa-av"><?php echo $e(mb_strtoupper(mb_substr($name, 0, 1))); ?></div>
        <h1><?php echo $e($name); ?></h1>
        <div class="pa-meta"><?php echo $e(trim(($profile['genre'] ?: '') . ($profile['genre'] && ($profile['city'] || $profile['country']) ? ' · ' : '') . implode(', ', array_filter([$profile['city'], $profile['country']])))); ?></div>
        <?php if ($verified): ?><div class="pa-badges"><?php foreach ($verified as $p => $url): ?>
            <?php if ($url && $isUrl($url)): ?><a class="pa-badge" href="<?php echo $e($url); ?>" target="_blank" rel="noopener nofollow"><?php else: ?><span class="pa-badge"><?php endif; ?>
            <span class="mdi <?php echo $platIcon[$p]; ?>"></span> Verified on <?php echo $e($platName[$p]); ?> <span class="mdi mdi-check-decagram"></span>
            <?php echo $url && $isUrl($url) ? '</a>' : '</span>'; ?>
        <?php endforeach; ?></div><?php endif; ?>
        <?php if ($profile['bio']): ?><p class="pa-bio"><?php echo $e($profile['bio']); ?></p><?php endif; ?>
        <div class="pa-links">
            <?php foreach (['website' => 'Website', 'instagram' => 'Instagram', 'youtube' => 'YouTube'] as $k => $lbl): if ($profile[$k] && $isUrl($profile[$k])): ?>
                <a href="<?php echo $e($profile[$k]); ?>" target="_blank" rel="noopener nofollow"><?php echo $lbl; ?> ↗</a>
            <?php endif; endforeach; ?>
        </div>
    </section>

    <h2 class="pa-h">Releases</h2>
    <?php if ($releases): ?><div class="pa-grid">
        <?php foreach ($releases as $r): ?>
            <div class="pa-card"><div class="pa-cover"><?php if (!empty($r['cover_art_path'])): ?><img src="/<?php echo $e(ltrim($r['cover_art_path'], '/')); ?>" alt="<?php echo $e($r['title']); ?>" loading="lazy"><?php else: ?><span class="mdi mdi-album"></span><?php endif; ?></div>
            <div class="pa-info"><b><?php echo $e($r['title']); ?></b><span><?php echo $e($r['release_type']); ?><?php echo $r['release_date'] ? ' · ' . $e(date('M Y', strtotime($r['release_date']))) : ''; ?><?php echo (int) $r['ai_pct'] > 0 ? ' · AI Original' : ''; ?></span>
            <?php if ($r['links']): ?><div class="l"><?php foreach ($r['links'] as $l): if ($isUrl($l['store_url'])): ?><a href="<?php echo $e($l['store_url']); ?>" target="_blank" rel="noopener nofollow">▶ <?php echo $e($l['platform_name']); ?></a><?php endif; endforeach; ?></div><?php endif; ?></div></div>
        <?php endforeach; ?></div>
    <?php else: ?><p style="text-align:center;color:var(--text-muted);padding:30px">No public releases yet.</p><?php endif; ?>
</main>
<?php include __DIR__ . '/../includes/footer_premium.php'; ?>
