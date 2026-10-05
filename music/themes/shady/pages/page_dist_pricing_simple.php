<?php
/**
 * Music Distribution - Pricing Plans Page (Simplified)
 */

if (!defined('bof_root')) die('Direct access not allowed');

$user = bof()->user;
$is_logged = $user->get()->logged;
?>

<style>
.dist-hero {
    background: linear-gradient(135deg, rgba(var(--theme_color), 0.1) 0%, rgba(var(--bg_color), 1) 100%);
    padding: 60px 20px;
    text-align: center;
}
.dist-hero h1 {
    font-size: 42px;
    font-weight: 700;
    margin-bottom: 20px;
    background: linear-gradient(90deg, rgba(var(--theme_color), 1), rgba(var(--theme_color2), 1));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
.dist-hero p {
    font-size: 18px;
    opacity: 0.8;
    max-width: 600px;
    margin: 0 auto 30px;
}

.plans-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 30px;
    max-width: 1200px;
    margin: 40px auto;
    padding: 0 20px;
}

.pricing-card {
    background: rgba(var(--bg_color), 0.8);
    border: 1px solid rgba(var(--theme_color), 0.2);
    border-radius: 16px;
    padding: 40px 30px;
    position: relative;
    transition: transform 0.3s, box-shadow 0.3s;
}
.pricing-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.3);
}
.pricing-card.featured {
    border-color: rgba(var(--theme_color), 0.5);
    box-shadow: 0 0 30px rgba(var(--theme_color), 0.15);
}
.pricing-card.featured::before {
    content: "MOST POPULAR";
    position: absolute;
    top: -12px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(var(--theme_color), 1);
    color: #000;
    padding: 6px 20px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 1px;
}

.plan-name {
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 10px;
    color: rgba(var(--text_color), 1);
}
.plan-price {
    font-size: 48px;
    font-weight: 800;
    margin: 20px 0;
    color: rgba(var(--theme_color), 1);
}
.plan-price span {
    font-size: 16px;
    opacity: 0.7;
    font-weight: 400;
}
.plan-desc {
    font-size: 14px;
    opacity: 0.7;
    margin-bottom: 30px;
}

.features-list {
    list-style: none;
    padding: 0;
    margin: 0 0 30px 0;
    text-align: left;
}
.features-list li {
    padding: 10px 0;
    border-bottom: 1px solid rgba(var(--border_color), 0.3);
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.features-list li:before {
    content: "✓";
    color: rgba(var(--theme_color), 1);
    font-weight: bold;
    flex-shrink: 0;
}
.features-list li.disabled {
    opacity: 0.4;
}
.features-list li.disabled:before {
    content: "✕";
    color: #999;
}

.select-btn {
    width: 100%;
    padding: 15px;
    background: rgba(var(--theme_color), 1);
    color: #000;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s;
}
.select-btn:hover {
    transform: scale(1.02);
    box-shadow: 0 5px 20px rgba(var(--theme_color), 0.3);
}

.platforms-section {
    max-width: 1200px;
    margin: 60px auto;
    padding: 40px 20px;
    text-align: center;
}
.platforms-section h2 {
    font-size: 32px;
    margin-bottom: 40px;
}
.platforms-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 20px;
    margin-top: 30px;
}
.platform-item {
    padding: 20px;
    background: rgba(var(--bg_color), 0.5);
    border-radius: 12px;
    border: 1px solid rgba(var(--border_color), 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    font-size: 14px;
    opacity: 0.8;
}
.platform-item span {
    font-size: 24px;
    color: rgba(var(--theme_color), 1);
}
</style>

<div class="dist-hero">
    <h1>Distribute Your Music Worldwide</h1>
    <p>Get your music on Spotify, Apple Music, YouTube and 100+ streaming platforms. Keep 100% of your royalties.</p>
</div>

<div class="plans-container">
    <!-- Rising Artist -->
    <div class="pricing-card">
        <div class="plan-name">Rising Artist</div>
        <div class="plan-desc">Perfect for new artists starting their journey</div>
        <div class="plan-price">₹1599<span>/year</span></div>
        <ul class="features-list">
            <li>Upload to 50+ platforms</li>
            <li>Standard support (48h response)</li>
            <li>Basic analytics</li>
            <li>1 artist profile</li>
            <li>Unlimited releases</li>
            <li class="disabled">YouTube Content ID</li>
            <li class="disabled">Custom label name</li>
            <li class="disabled">Scheduled releases</li>
        </ul>
        <button class="select-btn" onclick="selectPlan('rising')">Choose Plan</button>
    </div>

    <!-- Breakout Artist -->
    <div class="pricing-card featured">
        <div class="plan-name">Breakout Artist</div>
        <div class="plan-desc">For growing artists ready to expand</div>
        <div class="plan-price">₹2799<span>/year</span></div>
        <ul class="features-list">
            <li>Upload to 100+ platforms</li>
            <li>Priority support (24h response)</li>
            <li>Advanced analytics</li>
            <li>3 artist profiles</li>
            <li>Unlimited releases</li>
            <li>YouTube Content ID</li>
            <li class="disabled">Custom label name</li>
            <li class="disabled">Scheduled releases</li>
        </ul>
        <button class="select-btn" onclick="selectPlan('breakout')">Choose Plan</button>
    </div>

    <!-- Professional -->
    <div class="pricing-card">
        <div class="plan-name">Professional</div>
        <div class="plan-desc">For serious artists and labels</div>
        <div class="plan-price">₹4499<span>/year</span></div>
        <ul class="features-list">
            <li>Upload to 150+ platforms</li>
            <li>VIP support (12h response)</li>
            <li>Professional analytics</li>
            <li>Unlimited artist profiles</li>
            <li>Unlimited releases</li>
            <li>YouTube Content ID</li>
            <li>Custom label name</li>
            <li>Scheduled releases</li>
        </ul>
        <button class="select-btn" onclick="selectPlan('professional')">Choose Plan</button>
    </div>
</div>

<div class="platforms-section">
    <h2>Supported Platforms</h2>
    <div class="platforms-grid">
        <div class="platform-item">Spotify</div>
        <div class="platform-item">Apple Music</div>
        <div class="platform-item">YouTube Music</div>
        <div class="platform-item">Amazon Music</div>
        <div class="platform-item">Deezer</div>
        <div class="platform-item">Tidal</div>
        <div class="platform-item">Pandora</div>
        <div class="platform-item">Instagram</div>
        <div class="platform-item">TikTok</div>
        <div class="platform-item">Snapchat</div>
    </div>
</div>

<script>
function selectPlan(planSlug) {
    var aliases = { 'rising-artist': 'rising', 'breakout-artist': 'breakout' };
    if ( aliases[planSlug] ) planSlug = aliases[planSlug];
    var checkoutUrl = '/distribution/subscribe?plan=' + planSlug;
    <?php if (!$is_logged): ?>
        // send the user back to checkout after login (BOF in-memory destination)
        try {
            if ( window.user && window.user.setLoginDestination && window.config && window.config.web )
                window.user.setLoginDestination( window.config.web.address + checkoutUrl );
            if ( window.ui && window.ui.link && window.ui.link.navigate ){
                window.ui.link.navigate( 'userAuth' );
                return;
            }
        } catch(e){}
        window.location.href = '/userAuth?redirect=' + encodeURIComponent(checkoutUrl);
        return;
    <?php endif; ?>
    window.location.href = checkoutUrl;
}

</script>
