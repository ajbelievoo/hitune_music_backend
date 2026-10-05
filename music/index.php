<?php

// Debug: Log all requests to index.php
file_put_contents('/tmp/index_debug.log', date('Y-m-d H:i:s') . ' - index.php called - URI: ' . ($_SERVER['REQUEST_URI'] ?? 'unknown') . ' - Method: ' . ($_SERVER['REQUEST_METHOD'] ?? 'unknown') . "\n", FILE_APPEND);

// Strategy doc §7: direct uploads are disabled on HiTune Music — the upload
// wizard and every "Upload Song" entry point bounce to the Distribution portal.
$_ht_up = strtok($_SERVER["REQUEST_URI"] ?? "", "?");
if ( $_ht_up === "/upload" || strpos( (string) $_ht_up, "/upload/" ) === 0 ) {
    header( "Location: https://distribution.hitune.in/submit", true, 301 );
    exit;
}
// Artist verification now lives in the Distribution "Artist Panel" (HiTune Music +
// Spotify + Apple Music verification and analytics in one place).
if ( $_ht_up === "/become_verified" || strpos( (string) $_ht_up, "/become_verified/" ) === 0 ) {
    header( "Location: https://distribution.hitune.in/index.php?q=artist-panel#verification", true, 302 );
    exit;
}
unset( $_ht_up );

// Share session across subdomains before BOF loads
if (!headers_sent()) {
    ini_set('session.cookie_domain', '.hitune.in');
    session_set_cookie_params([
        'lifetime' => 86400,
        'path' => '/',
        'domain' => '.hitune.in',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

if ( ( !empty($_GET["q"]) && strpos( $_GET["q"], "assetlinks.json" ) !== false ) || ( !empty($_SERVER["REQUEST_URI"]) && strpos( $_SERVER["REQUEST_URI"], "assetlinks.json" ) !== false ) ) {
    http_response_code(200);
    header("HTTP/1.1 200 OK");
    header("Status: 200 OK");
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        [
            "relation" => ["delegate_permission/common.handle_all_urls"],
            "target" => [
                "namespace" => "android_app",
                "package_name" => "com.hitune.app",
                "sha256_cert_fingerprints" => [
                    "EA:28:0C:0B:D5:8A:60:08:AE:99:11:A3:97:F1:9D:87:38:2E:01:E2:E0:E2:DE:1D:18:73:4C:64:FB:67:C1:61"
                ]
            ]
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

$link = false;

require_once( dirname(__FILE__) . "/api/app/config.php" );

if (defined('maintenance') && maintenance) {
    http_response_code(503);
    $maintenanceFile = dirname(__FILE__) . '/maintenance.html';
    if (file_exists($maintenanceFile)) {
        readfile($maintenanceFile);
    } else {
        echo '<h1>Maintenance in progress</h1><p>Music Hitune is currently under maintenance. Please check back shortly.</p>';
    }
    exit;
}

// Handle Developer API v1 requests - bypass BOF endpoint system
// Handled by endpoint registration in dev/loader.php

require_once( bof_root . "/loader.php" );
require_once( root . "/app/client/loader.php" );

$pages = bof()->client_config->get_pages();
$client_config = bof()->client_config->get();
$match = bof()->request->match_page( $pages, true );
$seo = bof()->seo->fetch( !empty( $match[1] ) ? $match[1] : null, true );

$link = false;

if ( !empty( $_SERVER['HTTP_HOST'] ) ){
  $link = ( substr( web_address, 0, strlen("https") ) == "https" ? "https" : "http" ) . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
  if ( substr( $link, 0, strlen( web_address ) ) == web_address ) $link = substr( $link, strlen( web_address ) );
}

$path = !empty( parse_url( $link, PHP_URL_PATH ) ) ? parse_url( $link, PHP_URL_PATH ) : "";

// Skip the marketing landing page — the music app home is the front door.
if ( $path === "" || $path === "/" ){
  header( "Location: " . rtrim( web_address, "/" ) . "/home", true, 301 );
  exit;
}

if ( $path === "/ai-studio" || $path === "ai-studio" ){
  include __DIR__ . "/ai-studio.php";
  return;
}

if ( $path === "/hub" || $path === "hub" || preg_match( "#^/?hub/[a-z]+$#i", $path ) ){
  include __DIR__ . "/hub.php";
  return;
}

if ( $path === "/google40ad904876c94f34.html" || $path === "google40ad904876c94f34.html" ){
  header( "Content-Type: text/html; charset=utf-8" );
  echo "google-site-verification: google40ad904876c94f34.html";
  return;
}

if ( $path === "/robots.txt" || $path === "robots.txt" ){
  header( "Content-Type: text/plain; charset=utf-8" );
  echo "User-agent: *\nAllow: /\n\nSitemap: " . rtrim( web_address, "/" ) . "/sitemap.xml";
  return;
}

if ( preg_match( "/^\\/?files\\/(logo|mobile_logo)\\/.+\\.(png|jpe?g)$/i", $path ) ){
  $abs = realpath( dirname(__FILE__) . "/" . ltrim( $path, "/" ) );
  if ( $abs && file_exists( $abs ) ){
    $webp = preg_replace( "/\\.(png|jpe?g)$/i", ".webp", $abs );
    if ( !file_exists( $webp ) ){
      $src = null;
      $ext = strtolower( pathinfo( $abs, PATHINFO_EXTENSION ) );
      if ( $ext === "png" ) $src = imagecreatefrompng( $abs );
      if ( $ext === "jpg" || $ext === "jpeg" ) $src = imagecreatefromjpeg( $abs );
      if ( $src ){
        $w = imagesx( $src ); $h = imagesy( $src );
        $max = 128;
        $ratio = min( $max / $w, $max / $h, 1 );
        $nw = (int)floor( $w * $ratio ); $nh = (int)floor( $h * $ratio );
        $dst = imagecreatetruecolor( $nw, $nh );
        imagealphablending( $dst, false ); imagesavealpha( $dst, true );
        imagecopyresampled( $dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h );
        imagewebp( $dst, $webp, 80 );
        imagedestroy( $src ); imagedestroy( $dst );
      }
    }
    if ( file_exists( $webp ) ){
      header( "Content-Type: image/webp" );
      header( "Cache-Control: public, max-age=31536000, immutable" );
      readfile( $webp );
      return;
    }
  }
}

if ( $path === "/privacy-policy" || $path === "privacy-policy" ){
  header( "Content-Type: text/html; charset=utf-8" );
  echo "<!DOCTYPE html><html lang='en'><head><meta charset='utf-8'><meta name='viewport' content='width=device-width, initial-scale=1'><title>Privacy Policy</title></head><body style='font-family:system-ui, Roboto, Arial, sans-serif;padding:20px;line-height:1.6;color:#333'><h1>Privacy Policy</h1><p>We respect your privacy. We collect minimal data necessary to provide music streaming features, improve performance, and prevent abuse.</p><h2>Data We Collect</h2><ul><li>Account and authentication information</li><li>Usage analytics and device metadata</li><li>Cookies for preferences and session management</li></ul><h2>How We Use Data</h2><ul><li>To run core features and personalize experience</li><li>To secure the platform and detect fraud</li><li>To comply with legal requirements</li></ul><h2>Contact</h2><p>For privacy requests, contact the site administrator.</p></body></html>";
  return;
}

if ( $path === "/blog" || $path === "blog" ){
  include __DIR__ . "/blog.php";
  return;
}

if ( $path === "/developer" || $path === "developer" ){
  include __DIR__ . "/developer.php";
  return;
}

if ( $path === "/developer-dashboard" || $path === "developer-dashboard" ){
  include __DIR__ . "/developer-dashboard.php";
  return;
}

if ( $path === "/help" || $path === "help" ){
  header( "Content-Type: text/html; charset=utf-8" );
  $sitename = bof()->object->db_setting->get( "sitename" );
  echo "<!DOCTYPE html>
<html lang='en'>
<head>
  <meta charset='utf-8'>
  <meta name='viewport' content='width=device-width, initial-scale=1'>
  <title>Help Center - " . htmlspecialchars($sitename) . "</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: system-ui, -apple-system, Roboto, Arial, sans-serif; line-height: 1.6; color: #333; background: #f5f5f5; }
    .container { max-width: 900px; margin: 0 auto; padding: 20px; }
    .header { text-align: center; padding: 40px 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 10px; margin-bottom: 30px; }
    .header h1 { font-size: 2.5em; margin-bottom: 10px; }
    .header p { opacity: 0.9; font-size: 1.1em; }
    .search-box { margin: 30px 0; text-align: center; }
    .search-box input { width: 100%; max-width: 500px; padding: 15px 20px; border: 2px solid #ddd; border-radius: 30px; font-size: 16px; outline: none; transition: border-color 0.3s; }
    .search-box input:focus { border-color: #667eea; }
    .categories { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 40px; }
    .category { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); transition: transform 0.3s, box-shadow 0.3s; cursor: pointer; }
    .category:hover { transform: translateY(-5px); box-shadow: 0 5px 20px rgba(0,0,0,0.15); }
    .category h3 { color: #667eea; margin-bottom: 10px; font-size: 1.3em; }
    .category p { color: #666; font-size: 0.95em; }
    .faq-section { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    .faq-section h2 { color: #333; margin-bottom: 25px; font-size: 1.8em; }
    .faq-item { margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #eee; }
    .faq-item:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
    .faq-item h3 { color: #667eea; margin-bottom: 10px; font-size: 1.2em; cursor: pointer; }
    .faq-item p { color: #666; }
    .contact-section { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-top: 30px; text-align: center; }
    .contact-section h2 { color: #333; margin-bottom: 15px; }
    .contact-section p { color: #666; margin-bottom: 20px; }
    .contact-btn { display: inline-block; padding: 12px 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 25px; font-weight: 600; transition: transform 0.3s; }
    .contact-btn:hover { transform: scale(1.05); }
    @media (max-width: 600px) { .header h1 { font-size: 1.8em; } .categories { grid-template-columns: 1fr; } }
  </style>
</head>
<body>
  <div class='container'>
    <div class='header'>
      <h1>Help Center</h1>
      <p>How can we help you today?</p>
    </div>
    
    <div class='search-box'>
      <input type='text' placeholder='Search for help topics...' id='searchInput'>
    </div>
    
    <div class='categories'>
      <div class='category' onclick=\"document.getElementById('account').scrollIntoView({behavior: 'smooth'})\">
        <h3>👤 Account & Profile</h3>
        <p>Login, registration, profile settings, and account management</p>
      </div>
      <div class='category' onclick=\"document.getElementById('music').scrollIntoView({behavior: 'smooth'})\">
        <h3>🎵 Music & Streaming</h3>
        <p>Playing music, creating playlists, and discovering content</p>
      </div>
      <div class='category' onclick=\"document.getElementById('payment').scrollIntoView({behavior: 'smooth'})\">
        <h3>💳 Payments & Subscriptions</h3>
        <p>Pricing plans, payment methods, and billing issues</p>
      </div>
      <div class='category' onclick=\"document.getElementById('technical').scrollIntoView({behavior: 'smooth'})\">
        <h3>⚙️ Technical Support</h3>
        <p>App issues, bugs, and technical troubleshooting</p>
      </div>
    </div>
    
    <div class='faq-section' id='account'>
      <h2>Account & Profile</h2>
      <div class='faq-item'>
        <h3>How do I create an account?</h3>
        <p>Click on the Sign Up button on the homepage, fill in your details, and verify your email address to create your account.</p>
      </div>
      <div class='faq-item'>
        <h3>How do I reset my password?</h3>
        <p>Click on 'Forgot Password' on the login page, enter your email address, and follow the instructions sent to your email.</p>
      </div>
      <div class='faq-item'>
        <h3>Can I change my username?</h3>
        <p>Yes, you can change your username from your profile settings. Go to User Library > Edit Profile to update your username.</p>
      </div>
    </div>
    
    <div class='faq-section' id='music' style='margin-top: 30px;'>
      <h2>Music & Streaming</h2>
      <div class='faq-item'>
        <h3>How do I create a playlist?</h3>
        <p>Navigate to any track, click the 'Add to Playlist' button, and either select an existing playlist or create a new one.</p>
      </div>
      <div class='faq-item'>
        <h3>Can I download music for offline listening?</h3>
        <p>Offline listening is available for premium subscribers. Upgrade your plan to access this feature.</p>
      </div>
      <div class='faq-item'>
        <h3>How do I discover new music?</h3>
        <p>Use the Browse section, check out curated playlists, or explore recommendations based on your listening history.</p>
      </div>
    </div>
    
    <div class='faq-section' id='payment' style='margin-top: 30px;'>
      <h2>Payments & Subscriptions</h2>
      <div class='faq-item'>
        <h3>What subscription plans are available?</h3>
        <p>We offer Free, Premium, and Artist plans. Visit our Pricing page for detailed information about each plan.</p>
      </div>
      <div class='faq-item'>
        <h3>How do I cancel my subscription?</h3>
        <p>Go to User Library > Subscription > Manage Subscription and follow the cancellation instructions.</p>
      </div>
      <div class='faq-item'>
        <h3>What payment methods do you accept?</h3>
        <p>We accept credit/debit cards, PayPal, and various local payment methods depending on your region.</p>
      </div>
    </div>
    
    <div class='faq-section' id='technical' style='margin-top: 30px;'>
      <h2>Technical Support</h2>
      <div class='faq-item'>
        <h3>The app is not playing music</h3>
        <p>Check your internet connection, try refreshing the page, or clear your browser cache. If the issue persists, contact support.</p>
      </div>
      <div class='faq-item'>
        <h3>How do I report a bug?</h3>
        <p>If you encounter any bugs, please contact our support team with details about the issue and steps to reproduce it.</p>
      </div>
      <div class='faq-item'>
        <h3>Is the app available on mobile?</h3>
        <p>Yes! Our app is available on both iOS and Android. Download it from the App Store or Google Play Store.</p>
      </div>
    </div>
    
    <div class='contact-section'>
      <h2>Still need help?</h2>
      <p>Can't find what you're looking for? Contact our support team.</p>
      <a href='mailto:support@" . parse_url(web_address, PHP_URL_HOST) . "' class='contact-btn'>Contact Support</a>
    </div>
  </div>
  
  <script>
    document.getElementById('searchInput').addEventListener('keyup', function(e) {
      const query = e.target.value.toLowerCase();
      const faqItems = document.querySelectorAll('.faq-item');
      faqItems.forEach(item => {
        const text = item.textContent.toLowerCase();
        item.style.display = text.includes(query) ? 'block' : 'none';
      });
    });
  </script>
</body>
</html>";
  return;
}

if ( $path === "/sitemap.xml" || $path === "sitemap.xml" ){
  header( "Content-Type: application/xml; charset=utf-8" );
  $base = rtrim( web_address, "/" );
  echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>"
    . "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">"
    . "<url><loc>{$base}/</loc></url>"
    . "<url><loc>{$base}/search</loc></url>"
    . "<url><loc>{$base}/privacy-policy</loc></url>"
    . "<url><loc>{$base}/help</loc></url>"
    . "</urlset>";
  return;
}

// Login route (redirect to userAuth)
if ( $path === "/login" || $path === "login" ){
  $redirect = !empty($_GET["redirect"]) ? "?redirect=" . urlencode($_GET["redirect"]) : "";
  header("Location: /userAuth" . $redirect);
  exit;
}

// Music Distribution routing
if ( $path === "/distribution" || $path === "distribution" ){
  header( "Cache-Control: no-store, no-cache, must-revalidate, max-age=0" );
  header( "Pragma: no-cache" );
  header( "Expires: 0" );
  include __DIR__ . "/distribution.php";
  exit;
}
if ( $path === "/distribution/dashboard" || $path === "distribution/dashboard" ){
  header( "Cache-Control: no-store, no-cache, must-revalidate, max-age=0" );
  header( "Pragma: no-cache" );
  header( "Expires: 0" );
  include __DIR__ . "/distribution-dashboard.php";
  exit;
}
if ( $path === "/distribution/submit" || $path === "distribution/submit" ){
  header( "Cache-Control: no-store, no-cache, must-revalidate, max-age=0" );
  header( "Pragma: no-cache" );
  header( "Expires: 0" );
  include __DIR__ . "/distribution-submit.php";
  exit;
}
if ( $path === "/distribution/admin" || $path === "distribution/admin" ){
  header( "Cache-Control: no-store, no-cache, must-revalidate, max-age=0" );
  header( "Pragma: no-cache" );
  header( "Expires: 0" );
  include __DIR__ . "/distribution-admin.php";
  exit;
}
if ( $path === "/distribution/subscribe" || $path === "distribution/subscribe" || preg_match( "#^/?distribution/subscribe/#i", $path ) ){
  header( "Cache-Control: no-store, no-cache, must-revalidate, max-age=0" );
  header( "Pragma: no-cache" );
  header( "Expires: 0" );
  include __DIR__ . "/distribution-subscribe.php";
  exit;
}

if ( !headers_sent() ){
  header( "Cross-Origin-Opener-Policy: same-origin-allow-popups" );
  header( "Content-Security-Policy: default-src 'self' https: data: blob; script-src 'self' 'unsafe-inline' 'unsafe-eval' https:; style-src 'self' 'unsafe-inline' https:; img-src 'self' https: data: blob; font-src 'self' https: data:; connect-src 'self' https:; frame-ancestors 'self'; upgrade-insecure-requests" );
  if ( substr( web_address, 0, strlen("https") ) == "https" ){
    header( "Strict-Transport-Security: max-age=31536000; includeSubDomains; preload" );
  }
}

if ( !$match ){
    $match_path = ltrim($path, "/");
    if ( preg_match( "/^music\/(track|album|artist|playlist|podcast|episode|book)\/([^\/]{1,100})\/?$/", $match_path ) || preg_match( "/^(track|album|artist|playlist|podcast|episode|book)\/([^\/]{1,100})\/?$/", $match_path ) ){
      bof()->execute->run();
      bof()->response->display();
      return;
    }
  if ( !empty( parse_url( $link, PHP_URL_PATH ) ) ){
    require_once( dirname(__FILE__) . "/404.php" );
    return;
  }
}

if ( count( explode( "?", $link ) ) > 1 ){
  $_l = explode( "?", $link );
  if ( 
    substr( $_l[0], -8 ) != "userAuth" && 
    substr( $_l[0], -9 ) != "user_edit" && 
    substr( $_l[0], -12 ) != "user_library" && 
    substr( $_l[0], -6 ) != "search" && 
    substr( $_l[0], 0, 5 ) != "list/" 
  )
  $link = $_l[0];
}

$font_name = "Figtree";

?>
<!DOCTYPE html>
<html lang="en">
  <head>

    <base href="<?php echo web_address; ?>">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="HandheldFriendly" content="True">
    <meta name="MobileOptimized" content="360">
    <meta name="color-scheme" content="light dark">
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="manifest" href="manifest.json">
    <link rel="icon" type="image/png" href="api/assets/images/icon_128.png" />
    <?php if ( !empty( $client_config["brand"]["logo"] ) ) : ?>
    <link rel="preload" as="image" href="<?php echo $client_config["brand"]["logo"]; ?>">
    <?php endif; ?>


    <title><?php echo $seo["title"]; ?></title>

    <link rel="preload" href="<?php echo web_address . "/themes/shady/assets/x_icon_font/style.css" ?>" as="style" onload="this.rel='stylesheet'">
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link rel="stylesheet" href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap">
    <script>
      (function(){
        var loadCSS = function(href){
          var l = document.createElement('link');
          l.rel = 'stylesheet';
          l.href = href;
          l.media = 'all';
          document.head.appendChild(l);
        };
        var loaded = false;
        var schedule = function(){
          if (loaded) return;
          loaded = true;
          setTimeout(function(){
            loadCSS("//cdn.jsdelivr.net/npm/@mdi/font@6.5.95/css/materialdesignicons.min.css");
          }, 10000);
        };
        // Defer aggressively: only on user interaction or long-delay fallback
        window.addEventListener("pointerdown", schedule, {once:true});
        schedule();
      })();
    </script>

    <meta name="twitter:title" content="<?php echo $seo["title"]; ?>" />
    <meta property="og:title" content="<?php echo $seo["title"]; ?>" />
    <meta property="og:site_name" content="<?php echo bof()->object->db_setting->get( "sitename" ); ?>" />
    <?php if ( !empty( $seo["description"] ) ) : ?>
    <meta name='description' content='<?php echo $seo["description"]; ?>' >
    <meta name="twitter:description" content="<?php echo $seo["description"]; ?>" />
    <meta property="og:description" content="<?php echo $seo["description"]; ?>" />
    <?php endif; ?><?php if ( !empty( $seo["tags"] ) ) : ?>
    <meta name="keywords" content="<?php echo $seo["tags"]; ?>">
    <?php endif; ?><?php if ( !empty( $seo["image"] ) ) : ?>
    <meta property="og:image" content="<?php echo $seo["image"]; ?>" />
    <meta name="twitter:image" content="<?php echo $seo["image"]; ?>" />
    <?php endif; ?>
    <meta property="og:url" content="<?php echo web_address . $link ?>" />
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="google-site-verification" content="google40ad904876c94f34.html" />
    <link rel="canonical" href="<?php echo rtrim( web_address, "/" ) . $path; ?>">
    <?php if ( empty( $seo["description"] ) ) : ?>
    <meta name='description' content='<?php echo bof()->object->db_setting->get( "sitename" ); ?> - Listen to music online'>
    <?php endif; ?>
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Organization",
      "name": "<?php echo bof()->object->db_setting->get( "sitename" ); ?>",
      "url": "<?php echo web_address; ?>",
      "logo": "<?php echo $client_config["brand"]["logo"] ?: ( web_address . '/api/assets/images/icon_128.png' ); ?>"
    }
    </script>
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "WebSite",
      "url": "<?php echo web_address; ?>",
      "potentialAction": {
        "@type": "SearchAction",
        "target": "<?php echo rtrim( web_address, '/' ); ?>/search?q={search_term_string}",
        "query-input": "required name=search_term_string"
      }
    }
    </script>

  </head>
  <style>

  body {
    --theme_color: <?php echo bof()->object->db_setting->get("theme_color_rgb"); ?>;
    font-family: "<?php echo $font_name; ?>" !important
  }
  body.splash,
  body.splash.light {
    background: #f3f4f6 !important;
    --theme_color: 250, 45, 72;
  }
  body.splash .loader > div {
    width: 54px;
    height: 54px;
    border: 4px solid rgba(0,0,0,0.07);
    border-top-color: rgb(var(--theme_color));
    border-radius: 50%;
    display: inline-block;
    box-sizing: border-box;
    animation: splash_loader_rotation 0.8s linear infinite;
    box-shadow: 0 0 34px rgba(var(--theme_color), 0.45), 0 0 12px rgba(var(--theme_color), 0.3) inset;
  }
  body.splash .loader > .bof_part {
    display: none
  }
  body.splash .loader {
    position: fixed;
    height: fit-content;
    top: 0;
    bottom: 0;
    left: 0;
    right: 0;
    margin: auto;
    text-align: center;
    opacity: 1
  }
  .btn.btn-primary { background: linear-gradient(135deg, rgb(var(--theme_color)), #00b7ff) !important; color: #fff !important; box-shadow: 0 4px 14px rgba(14,165,233,.35); border-radius: 10px !important; }
  @keyframes splash_loader_rotation {
    0% {
      transform: rotate(0deg);
    }
    100% {
      transform: rotate(360deg);
    }
  }
  .bof_part {
    display: none
  }
  #ad_placeholder { min-height: 120px }

  </style>
  <body class="splash unloaded noParts noPaddings">

    <main id="main" role="main">

      <div class="content"></div>
      <div id="ad_placeholder" aria-hidden="true"></div>
      <div class="loader"><div></div></div>

    </main>

    <?php
      $custom_js = bof()->object->db_setting->get("custom_js");
      $ads_code = bof()->object->db_setting->get("ads_google_auto_code");
    ?>
    <script>
    (function(){
      var inject = function(html){ document.body.insertAdjacentHTML('beforeend', html); };
      var custom = "<?php echo base64_encode( $custom_js ); ?>";
      var ads = "<?php echo base64_encode( $ads_code ); ?>";
      var run = function(){
        try { inject( atob( custom ) ); } catch(e){}
        setTimeout(function(){ try { inject( atob( ads ) ); } catch(e){} }, 2000);
      };
      if (document.readyState === "complete") run(); else window.addEventListener("load", run);
    })();
    </script>
    <script>
    (function(){
      var ensureAlt = function(el){
        if (!el.hasAttribute('alt')) el.setAttribute('alt','');
        if (el.classList.contains('panda') && (!el.getAttribute('alt') || el.getAttribute('alt') === '')) el.setAttribute('alt','panda icon');
      };
      var fixAlt = function(){
        var imgs = document.querySelectorAll('img');
        for (var i=0;i<imgs.length;i++){ ensureAlt(imgs[i]); }
      };
      var mo = new MutationObserver(function(muts){
        muts.forEach(function(m){
          m.addedNodes.forEach(function(n){
            if (n.tagName === 'IMG') ensureAlt(n);
            var q = n.querySelectorAll ? n.querySelectorAll('img') : [];
            for (var i=0;i<q.length;i++) ensureAlt(q[i]);
          });
        });
      });
      if (document.readyState === "interactive") fixAlt(); else document.addEventListener("DOMContentLoaded", fixAlt);
      mo.observe(document.documentElement, {childList:true, subtree:true});
    })();
    </script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js" defer></script>
    <script>
    var $_bof_config = {
      production: <?php echo production ? "true" : "false"; ?>,
      version: <?php echo bof_version; ?>,
      web_address: "<?php echo web_address; ?>",
      endpoint_address: "<?php echo endpoint_address; ?>",
      assets_address: "<?php echo endpoint_address; ?>assets/",
      bof_assets_address: "<?php echo bof_assets_address; ?>",
      requested_page: "<?php echo $match[0]; ?>",
      requested_url: "<?php echo $link; ?>",
      sign_key: "<?php echo sign_key; ?>",
      cfc: <?php echo defined("cf_cache") ? ( cf_cache ? "true" : "false" ) : "false" ?>
    };
    </script>
    <script>
    (function(){
      try {
        var prefix = (typeof $_bof_config !== 'undefined' && $_bof_config.cache_prefix) ? $_bof_config.cache_prefix : 'dm_';
        var key = prefix + 'color';
        if (!localStorage.getItem(key)) {
          localStorage.setItem(key, 'light');
        }
      } catch (e) {}
    })();
    </script>
    <script src="<?php echo bof_assets_address; ?>js/bof/bof<?php echo production ? "_mini" : "" ?>.js?bof_version=<?php echo bof_version; ?>" defer></script>

    <?php if ( bof()->object->db_setting->get( "htx_adblock" ) ): ?>
    <script>
    window.HTX_ADBLOCK_MODE = "<?php echo htmlspecialchars( bof()->object->db_setting->get( "htx_adblock_mode" ) ?: "overlay", ENT_QUOTES ); ?>";
    window.HTX_ADBLOCK_URL  = "<?php echo htmlspecialchars( bof()->object->db_setting->get( "htx_adblock_url" ) ?: "", ENT_QUOTES ); ?>";
    </script>
    <script src="<?php echo web_address; ?>plugins/bof_tool_hitune_extras/assets/htx_adblock.js?v=1" defer></script>
    <?php endif; ?>

  </body>
</html>
