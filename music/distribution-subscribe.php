<?php
/**
 * Music Distribution - Subscribe / Checkout Page (Standalone)
 * Starts the Razorpay payment flow for a distribution plan.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Canonical host is the distribution subdomain; bounce legacy music.hitune.in URLs.
if (($_SERVER['HTTP_HOST'] ?? '') === 'music.hitune.in') {
    $q = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
    header('Location: https://distribution.hitune.in/subscribe' . $q, true, 301);
    exit;
}

// Plan slug can come from ?plan= or from path /distribution/subscribe/<slug>
$plan_slug = !empty($_GET['plan']) ? $_GET['plan'] : '';
if (!$plan_slug && !empty($_SERVER['REQUEST_URI'])) {
    if (preg_match('#/(?:distribution/)?subscribe/([a-zA-Z0-9\-_]+)#', $_SERVER['REQUEST_URI'], $m)) {
        $plan_slug = $m[1];
    }
}
$plan_slug_js = htmlspecialchars($plan_slug, ENT_QUOTES);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscribe | Hitune Music Distribution</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@6.5.95/css/materialdesignicons.min.css">
    <script src="/dist-auth.js"></script>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/dist-style.css">
    <style>
        .container { max-width: 680px; }
        .card { padding: 34px; margin-top: 24px; }
        .card .plan-price { margin: 10px 0 14px; }
        .pay-btn { width: 100%; padding: 15px; font-size: 16px; }
        .secure-note { font-size: 13px; color: var(--d-faint); text-align: center; margin-top: 14px; }
        .link-btn { margin: 10px 10px 0 0; }
        .loading.card { text-align: center; }
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
        <div id="status-banner" style="margin-top:24px"></div>
        <div id="checkout-content">
            <div class="card loading">
                <div class="spinner"></div>
                <p>Loading checkout...</p>
            </div>
        </div>
    </div>

    <script>
    const planSlug = '<?php echo $plan_slug_js; ?>';
    const apiHeaders = {
        'x-bof-request-code': 'BusyOwlFrameWorkVersion201',
        'x-bof-platform': 'web',
        'x-bof-version': '2074'
    };
    distAuth.headers(apiHeaders);
    const content = document.getElementById('checkout-content');

    function esc(s) {
        const d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    function showStatusBanner() {
        const params = new URLSearchParams(window.location.search);
        const status = params.get('status');
        if (!status) return;
        const banner = document.getElementById('status-banner');
        if (status === 'success') {
            banner.innerHTML = `<div class="alert alert-success">
                <strong>Payment successful!</strong> Your subscription is now active.
                <br><a class="btn btn-primary" href="/submit">Submit Music</a>
                <a class="btn btn-secondary" href="/dashboard">Go to Dashboard</a>
            </div>`;
        } else if (status === 'failed') {
            banner.innerHTML = `<div class="alert alert-error">
                <strong>Payment could not be confirmed.</strong> If money was deducted it will be auto-refunded by Razorpay. You can try again below.
            </div>`;
        }
    }

    function renderError(title, msg, showPlansLink) {
        content.innerHTML = `<div class="card">
            <div class="alert alert-error"><strong>${esc(title)}</strong><br>${esc(msg)}</div>
            ${showPlansLink ? '<a class="btn btn-primary" href="/">View Plans</a>' : ''}
        </div>`;
    }

    function renderCheckout(data) {
        const p = data.plan;
        if (data.active_subscription) {
            content.innerHTML = `<div class="card">
                <div class="alert alert-info">
                    <strong>You already have an active subscription</strong><br>
                    Plan: ${esc(data.active_subscription.plan_name)} &middot; valid until ${esc(data.active_subscription.end_date)}
                </div>
                <a class="btn btn-primary" href="/submit">Submit Music</a>
                <a class="btn btn-secondary" href="/dashboard">Dashboard</a>
            </div>`;
            return;
        }
        if (!data.gateway_ready) {
            renderError('Payments unavailable', 'The payment gateway is not configured yet. Please contact support or try again later.', false);
            return;
        }
        content.innerHTML = `<div class="card">
            <div class="plan-name">${esc(p.name)}</div>
            <div class="plan-price">₹${Number(p.price).toLocaleString('en-IN')}<span> / year</span></div>
            <div class="plan-desc">Unlimited music distribution to Spotify, Apple Music, YouTube Music and more.</div>
            <button class="btn btn-primary pay-btn" id="pay-btn">Pay ₹${Number(p.price).toLocaleString('en-IN')} with Razorpay</button>
            <div class="secure-note"><i class="mdi mdi-lock"></i> Secure payment via Razorpay. UPI, cards, netbanking &amp; wallets supported.</div>
        </div>`;

        document.getElementById('pay-btn').addEventListener('click', function() {
            this.disabled = true;
            this.textContent = 'Creating payment link...';
            const fd = new FormData();
            fd.append('plan', p.slug);
            fetch('/api/dist/checkout', { method: 'POST', headers: apiHeaders, body: fd })
                .then(r => r.json())
                .then(res => {
                    if (res.success && res.payment_url) {
                        window.location.href = res.payment_url;
                        return;
                    }
                    const err = (res.messages && res.messages.join) ? res.messages.join(', ') : 'Payment could not be started';
                    this.disabled = false;
                    this.textContent = 'Pay ₹' + Number(p.price).toLocaleString('en-IN') + ' with Razorpay';
                    alert('Error: ' + err);
                })
                .catch(() => {
                    this.disabled = false;
                    this.textContent = 'Pay ₹' + Number(p.price).toLocaleString('en-IN') + ' with Razorpay';
                    alert('Network error. Please try again.');
                });
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        showStatusBanner();
        if (!planSlug) {
            renderError('No plan selected', 'Please choose a distribution plan first.', true);
            return;
        }
        fetch('/api/dist/checkout?plan=' + encodeURIComponent(planSlug), { headers: apiHeaders })
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    const err = (data.messages && data.messages.join) ? data.messages.join(', ') : (data.message || 'error');
                    if (data.code === 'plan_not_found') {
                        renderError('Plan not found', 'The selected plan does not exist or is no longer available.', true);
                    } else {
                        renderError('Error', err, true);
                    }
                    return;
                }
                if (!data.logged_in) {
                    window.location.href = distAuth.loginUrl();
                    return;
                }
                renderCheckout(data);
            })
            .catch(() => renderError('Error', 'Could not load checkout. Please try again.', true));
    });
    </script>
</body>
</html>
