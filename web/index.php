<?php
/**
 * HiTune Music Distribution - Main Entry Point
 * web.hitune.in - Distribution Website
 */

// Disable cache for development
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Start session for web.hitune.in (separate from music.hitune.in)
session_start();

// Include config and auth
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';

// Get requested path
$path = isset($_GET['q']) ? $_GET['q'] : '';
$path = trim($path, '/');

// Route to appropriate page
switch($path) {
    case '':
    case 'home':
        include __DIR__ . '/pages/home.php';
        break;
    case 'pricing':
        include __DIR__ . '/pages/pricing.php';
        break;
    case 'checkout':
        include __DIR__ . '/pages/checkout.php';
        break;
    case 'payment_callback':
        include __DIR__ . '/pages/payment_callback.php';
        break;
    case 'payment_webhook':
        include __DIR__ . '/pages/payment_webhook.php';
        break;
    case 'dashboard':
        include __DIR__ . '/pages/dashboard.php';
        break;
    case 'submit':
        // Redirect old submit to new multi-step release flow
        header('Location: /index.php?q=release-create');
        exit;
        break;
    case 'services':
        include __DIR__ . '/pages/services.php';
        break;
    case 'sell':
        include __DIR__ . '/pages/sell.php';
        break;
    case 'publishing':
        include __DIR__ . '/pages/publishing.php';
        break;
    case 'splits':
        include __DIR__ . '/pages/splits.php';
        break;
    case 'accelerator':
        include __DIR__ . '/pages/accelerator.php';
        break;
    case 'cart':
        include __DIR__ . '/pages/cart.php';
        break;
    case 'forgot_password':
        include __DIR__ . '/pages/forgot_password.php';
        break;
    case 'reset_password':
        include __DIR__ . '/pages/reset_password.php';
        break;
    case 'login':
        include __DIR__ . '/pages/login.php';
        break;
    case 'signup':
        include __DIR__ . '/pages/signup.php';
        break;
    case 'verify_email':
        include __DIR__ . '/pages/verify_email.php';
        break;
    case 'resend_verification':
        include __DIR__ . '/pages/resend_verification.php';
        break;
    case 'google_callback':
        include __DIR__ . '/pages/google_callback.php';
        break;
    case 'analytics':
        include __DIR__ . '/pages/analytics.php';
        break;
    case 'stores':
        include __DIR__ . '/pages/stores.php';
        break;
    case 'earnings':
        include __DIR__ . '/pages/earnings.php';
        break;
    case 'releases':
        include __DIR__ . '/pages/releases.php';
        break;
    case 'release-create':
        $step = isset($_GET['step']) ? intval($_GET['step']) : 1;
        switch($step) {
            case 2:
                include __DIR__ . '/pages/release_step2.php';
                break;
            case 3:
                include __DIR__ . '/pages/release_step3.php';
                break;
            case 4:
                include __DIR__ . '/pages/release_step4.php';
                break;
            default:
                include __DIR__ . '/pages/release_create.php';
                break;
        }
        break;
    case 'release-view':
        include __DIR__ . '/pages/release_view.php';
        break;
    case 'revenue':
        include __DIR__ . '/pages/revenue.php';
        break;
    case 'artist-panel':
    case 'artist_panel':
        include __DIR__ . '/pages/artist_panel.php';
        break;
    case 'artist-page':
        include __DIR__ . '/pages/artist_public.php';
        break;
    // Legacy artist pages now live inside the Artist Panel (profile + real verification)
    case 'artist':
        header('Location: /index.php?q=artist-panel#profile', true, 302);
        exit;
    case 'spotify_for_artists':
    case 'apple_music_for_artists':
        header('Location: /index.php?q=artist-panel#verification', true, 302);
        exit;
    case 'youtube_oac':
        include __DIR__ . '/pages/youtube_oac.php';
        break;
    case 'isrc':
        include __DIR__ . '/pages/isrc.php';
        break;
    case 'royalties':
        include __DIR__ . '/pages/royalties.php';
        break;
    case 'payouts':
        include __DIR__ . '/pages/payouts.php';
        break;
    case 'about':
        include __DIR__ . '/pages/about.php';
        break;
    case 'careers':
        include __DIR__ . '/pages/careers.php';
        break;
    case 'contact':
        include __DIR__ . '/pages/contact.php';
        break;
    case 'privacy':
        include __DIR__ . '/pages/privacy.php';
        break;
    case 'terms':
        include __DIR__ . '/pages/terms.php';
        break;
    case 'cookies':
        include __DIR__ . '/pages/cookies.php';
        break;
    case '404':
        include __DIR__ . '/pages/404.php';
        break;
    case 'help':
        include __DIR__ . '/pages/help.php';
        break;
    case 'logout':
        require_once __DIR__ . '/includes/auth.php';
        logoutUser();
        break;
    default:
        include __DIR__ . '/pages/404.php';
        break;
}
exit;
