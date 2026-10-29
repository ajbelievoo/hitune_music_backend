<?php
/**
 * /story/<slug> — smart share link for HiTune stories.
 *
 * Deep-links into the app (Android intent + hitune:// fallback). When the
 * app isn't installed it shows the story media + a Get-the-app button.
 * Open-Graph tags give WhatsApp/Instagram a rich preview.
 */

$slug = preg_replace('/[^A-Za-z0-9_-]/', '', $_GET['ref'] ?? ($_GET['q'] ?? ''));
$slug = trim($slug, '/');
$ref  = preg_replace('/_/', ':', $slug, 1);

$title = 'HiTune Story';
$caption = '';
$media = '';
$thumb = '';
$isVideo = false;

// Look up admin stories (a:<id>) — these live in the HiTune app DB.
if (preg_match('/^a:(\d+)$/', $ref, $m)) {
    $env = @parse_ini_file(__DIR__ . '/.env', false, INI_SCANNER_RAW) ?: [];
    try {
        $pdo = new PDO(
            'mysql:host=' . ($env['DB_HOST'] ?? 'localhost') . ';dbname=' . ($env['DB_NAME'] ?? 'musicpro') . ';charset=utf8mb4',
            $env['DB_USER'] ?? 'musicpro',
            $env['DB_PASS'] ?? '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
        $st = $pdo->prepare("SELECT * FROM `_ht_admin_stories` WHERE ID=? AND active=1 AND (repeat_forever=1 OR time_add >= NOW() - INTERVAL 24 HOUR) LIMIT 1");
        $st->execute([(int) $m[1]]);
        $row = $st->fetch();
        if ($row) {
            $isVideo = ((int) $row['type']) === 1;
            $media = $row['content'];
            $thumb = $row['thumbnail'] ?: ($isVideo ? '' : $row['content']);
            $caption = $row['caption'] ?? '';
            $title = ($row['display_name'] ?: 'HiTune') . ' Story';
        }
    } catch (Throwable $e) {}
}

$deepLink = 'hitune://story/' . rawurlencode($slug);
$intentUrl = 'intent://story/' . rawurlencode($slug)
    . '#Intent;scheme=hitune;package=com.hitune.app;'
    . 'S.browser_fallback_url=' . rawurlencode('https://hitune.in/') . ';end';
$apkUrl = 'https://hitune.in/hitune-music.apk';
$esc = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $esc($title) ?> — HiTune</title>
<meta property="og:type" content="<?= $isVideo ? 'video.other' : 'article' ?>">
<meta property="og:title" content="<?= $esc($title) ?>">
<meta property="og:description" content="<?= $esc($caption ?: 'Watch this story on HiTune') ?>">
<?php if ($thumb): ?><meta property="og:image" content="<?= $esc($thumb) ?>"><?php endif; ?>
<meta name="twitter:card" content="<?= $thumb ? 'summary_large_image' : 'summary' ?>">
<style>
*{margin:0;box-sizing:border-box;font-family:-apple-system,'Segoe UI',Roboto,sans-serif}
body{background:#000;color:#fff;min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:24px}
.media{max-width:340px;width:100%;border-radius:18px;overflow:hidden;background:#111;margin-bottom:22px}
.media img,.media video{width:100%;display:block}
h1{font-size:1.3rem;margin-bottom:6px;text-align:center}
p{color:#9ca3af;font-size:.9rem;text-align:center;max-width:340px;margin-bottom:20px}
.btn{display:inline-block;padding:14px 28px;border-radius:30px;font-weight:700;text-decoration:none;margin:5px}
.btn-p{background:linear-gradient(135deg,#0fa8d4,#e56bd8);color:#fff}
.btn-o{border:1px solid #4b5563;color:#d1d5db}
.logo{font-size:1.6rem;font-weight:800;margin-bottom:14px;background:linear-gradient(135deg,#0fa8d4,#e56bd8);-webkit-background-clip:text;background-clip:text;color:transparent}
</style>
<script>
// Try the app first; if nothing takes over in 1.8s stay on this page.
(function(){
  var slug = <?= json_encode($slug) ?>;
  var t = setTimeout(function(){ /* stay — page is the fallback */ }, 1800);
  window.addEventListener('pagehide', function(){ clearTimeout(t); });
  document.addEventListener('visibilitychange', function(){
    if (document.hidden) clearTimeout(t);
  });
  var isAndroid = /Android/i.test(navigator.userAgent);
  window.location.href = isAndroid
    ? <?= json_encode($intentUrl) ?>
    : <?= json_encode($deepLink) ?>;
})();
</script>
</head>
<body>
<div class="logo">HiTune</div>
<?php if ($media): ?>
<div class="media">
  <?php if ($isVideo): ?>
    <video src="<?= $esc($media) ?>" controls playsinline <?= $thumb ? 'poster="' . $esc($thumb) . '"' : '' ?>></video>
  <?php else: ?>
    <img src="<?= $esc($media) ?>" alt="">
  <?php endif; ?>
</div>
<?php endif; ?>
<h1><?= $esc($title) ?></h1>
<?php if ($caption): ?><p><?= $esc($caption) ?></p><?php endif; ?>
<p>This story opens in the HiTune app.</p>
<a class="btn btn-p" href="<?= $esc($intentUrl) ?>">Open in HiTune</a>
<a class="btn btn-o" href="<?= $esc($apkUrl) ?>">Get the app</a>
</body>
</html>
