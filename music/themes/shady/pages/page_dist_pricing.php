<?php
/**
 * Music Distribution - Pricing Plans Page
 */

if (!defined('bof_root')) die('Direct access not allowed');

// Simple check if user is logged in
$is_logged = bof()->user->get()->logged;

ob_start();
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
    margin: 0 auto;
    padding: 40px 20px;
}

.plan-card {
    background: rgba(var(--bg_color), 1);
    border: 1px solid rgba(var(--font_color), 0.1);
    border-radius: 20px;
    padding: 40px 30px;
    position: relative;
    transition: all 0.3s ease;
}
.plan-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.3);
    border-color: rgba(var(--theme_color), 0.3);
}
.plan-card.featured {
    background: linear-gradient(135deg, rgba(var(--theme_color), 0.05) 0%, rgba(var(--bg_color), 1) 100%);
    border: 2px solid rgba(var(--theme_color), 0.5);
    transform: scale(1.05);
}
.plan-card.featured:hover {
    transform: scale(1.05) translateY(-10px);
}

.plan-badge {
    position: absolute;
    top: -12px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(var(--theme_color), 1);
    color: #fff;
    padding: 6px 20px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.plan-name {
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 10px;
}
.plan-price {
    font-size: 48px;
    font-weight: 800;
    color: rgba(var(--theme_color), 1);
    margin-bottom: 5px;
}
.plan-price span {
    font-size: 16px;
    font-weight: 400;
    opacity: 0.7;
}
.plan-period {
    font-size: 14px;
    opacity: 0.6;
    margin-bottom: 30px;
}

.plan-features {
    list-style: none;
    margin-bottom: 30px;
}
.plan-features li {
    padding: 12px 0;
    border-bottom: 1px solid rgba(var(--font_color), 0.05);
    display: flex;
    align-items: center;
    gap: 12px;
}
.plan-features li:last-child {
    border-bottom: none;
}
.plan-features .mdi {
    font-size: 20px;
}
.plan-features .mdi-check {
    color: #4CAF50;
}
.plan-features .mdi-close {
    color: #f44336;
    opacity: 0.5;
}
.plan-features .feature-text {
    flex: 1;
}
.plan-features .feature-highlight {
    color: rgba(var(--theme_color), 1);
    font-weight: 600;
}

.response-time {
    display: inline-block;
    background: rgba(var(--theme_color), 0.1);
    color: rgba(var(--theme_color), 1);
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    margin-left: 8px;
}

.plan-cta {
    width: 100%;
    padding: 16px 30px;
    background: rgba(var(--theme_color), 1);
    color: #fff;
    border: none;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}
.plan-cta:hover {
    background: rgba(var(--theme_color), 0.8);
    transform: scale(1.02);
}
.plan-cta.secondary {
    background: transparent;
    border: 2px solid rgba(var(--theme_color), 1);
    color: rgba(var(--theme_color), 1);
}
.plan-cta.secondary:hover {
    background: rgba(var(--theme_color), 1);
    color: #fff;
}

.comparison-section {
    max-width: 1000px;
    margin: 60px auto;
    padding: 0 20px;
}
.comparison-section h2 {
    text-align: center;
    font-size: 32px;
    margin-bottom: 40px;
}
.comparison-table {
    width: 100%;
    border-collapse: collapse;
}
.comparison-table th,
.comparison-table td {
    padding: 20px;
    text-align: left;
    border-bottom: 1px solid rgba(var(--font_color), 0.1);
}
.comparison-table th {
    font-weight: 600;
    background: rgba(var(--bg_color2), 0.3);
}
.comparison-table td:first-child {
    font-weight: 500;
}
.comparison-table .check {
    color: #4CAF50;
    font-size: 24px;
}
.comparison-table .cross {
    color: #f44336;
    opacity: 0.3;
    font-size: 24px;
}
.comparison-table .time {
    color: rgba(var(--theme_color), 1);
    font-weight: 600;
}

.platforms-section {
    text-align: center;
    padding: 60px 20px;
    background: rgba(var(--bg_color2), 0.3);
}
.platforms-section h2 {
    font-size: 28px;
    margin-bottom: 40px;
}
.platforms-grid {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 30px;
    max-width: 1000px;
    margin: 0 auto;
}
.platform-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 15px 25px;
    background: rgba(var(--bg_color), 0.8);
    border-radius: 10px;
    font-weight: 500;
}
.platform-item .mdi {
    font-size: 24px;
    color: rgba(var(--theme_color), 1);
}

@media (max-width: 768px) {
    .dist-hero h1 {
        font-size: 28px;
    }
    .plans-container {
        grid-template-columns: 1fr;
    }
    .plan-card.featured {
        transform: none;
    }
    .plan-card.featured:hover {
        transform: translateY(-10px);
    }
    .comparison-table {
        font-size: 14px;
    }
    .comparison-table th,
    .comparison-table td {
        padding: 12px 8px;
    }
}
</style>

<div class="dist-hero">
    <h1>Distribute Your Music Worldwide</h1>
    <p>Get your music on Spotify, Apple Music, YouTube Music, TikTok, and 100+ platforms. Keep 100% of your royalties with our professional distribution service.</p>
</div>

<div class="plans-container" id="plans-container">
    <!-- Plans will be loaded via JavaScript -->
    <div class="plan-card featured">
        <div class="plan-badge">Most Popular</div>
        <div class="plan-name">Professional</div>
        <div class="plan-price">₹4499<span>/year</span></div>
        <div class="plan-period">For serious artists</div>
        <ul class="plan-features">
            <li><span class="mdi mdi-check"></span><span class="feature-text">Unlimited releases to all stores</span></li>
            <li><span class="mdi mdi-check"></span><span class="feature-text">Schedule your own release date</span></li>
            <li><span class="mdi mdi-check"></span><span class="feature-text">Spotify Verified Artist Checkmark</span></li>
            <li><span class="mdi mdi-check"></span><span class="feature-text">Apple Music for Artists Verification</span></li>
            <li><span class="mdi mdi-check"></span><span class="feature-text">Artist Revenue Splits</span></li>
            <li><span class="mdi mdi-check"></span><span class="feature-text">Social platforms (20% fee)</span></li>
            <li><span class="mdi mdi-check"></span><span class="feature-text feature-highlight">Customer Service: 24 Hours</span></li>
            <li><span class="mdi mdi-check"></span><span class="feature-text">Store Automator & Daily Trends</span></li>
            <li><span class="mdi mdi-check"></span><span class="feature-text">Cover Art Creator</span></li>
            <li><span class="mdi mdi-check"></span><span class="feature-text">Use Your Own ISRC & UPC</span></li>
            <li><span class="mdi mdi-check"></span><span class="feature-text">Exclusive Partnerships (Tidal, Twitch)</span></li>
            <li><span class="mdi mdi-check"></span><span class="feature-text">Promotional Opportunities</span></li>
            <li><span class="mdi mdi-check"></span><span class="feature-text">Pro Panels & Expert Sessions</span></li>
            <li><span class="mdi mdi-check"></span><span class="feature-text">Custom Label Name</span></li>
            <li><span class="mdi mdi-check"></span><span class="feature-text">Country Restrictions</span></li>
            <li><span class="mdi mdi-check"></span><span class="feature-text">YouTube Content ID & OAC</span></li>
            <li><span class="mdi mdi-check"></span><span class="feature-text">TuneCore Direct Advance</span></li>
        </ul>
        <button class="plan-cta" onclick="selectPlan('professional')">Get Started</button>
    </div>

    <div class="plan-card">
        <div class="plan-name">Breakout Artist</div>
        <div class="plan-price">₹2799<span>/year</span></div>
        <div class="plan-period">For growing artists</div>
        <ul class="plan-features">
            <li><span class="mdi mdi-check"></span><span class="feature-text">Unlimited releases to all stores</span></li>
            <li><span class="mdi mdi-check"></span><span class="feature-text">Schedule your own release date</span></li>
            <li><span class="mdi mdi-check"></span><span class="feature-text">Spotify Verified Artist Checkmark</span></li>
            <li><span class="mdi mdi-check"></span><span class="feature-text">Apple Music for Artists Verification</span></li>
            <li><span class="mdi mdi-check"></span><span class="feature-text">Artist Revenue Splits</span></li>
            <li><span class="mdi mdi-check"></span><span class="feature-text">Social platforms (20% fee)</span></li>
            <li><span class="mdi mdi-check"></span><span class="feature-text feature-highlight">Customer Service: 48 Hours</span></li>
            <li><span class="mdi mdi-close"></span><span class="feature-text">Store Automator</span></li>
            <li><span class="mdi mdi-check"></span><span class="feature-text">Daily Trend Reports</span></li>
            <li><span class="mdi mdi-check"></span><span class="feature-text">Cover Art Creator</span></li>
            <li><span class="mdi mdi-check"></span><span class="feature-text">Use Your Own ISRC & UPC</span></li>
            <li><span class="mdi mdi-check"></span><span class="feature-text">Exclusive Partnerships</span></li>
            <li><span class="mdi mdi-check"></span><span class="feature-text">Promotional Opportunities</span></li>
            <li><span class="mdi mdi-close"></span><span class="feature-text">Pro Panels & Expert Sessions</span></li>
            <li><span class="mdi mdi-check"></span><span class="feature-text">Custom Label Name</span></li>
            <li><span class="mdi mdi-close"></span><span class="feature-text">Country Restrictions</span></li>
            <li><span class="mdi mdi-check"></span><span class="feature-text">YouTube Content ID</span></li>
            <li><span class="mdi mdi-close"></span><span class="feature-text">YouTube OAC</span></li>
            <li><span class="mdi mdi-close"></span><span class="feature-text">TuneCore Direct Advance</span></li>
        </ul>
        <button class="plan-cta secondary" onclick="selectPlan('breakout')">Get Started</button>
    </div>

    <div class="plan-card">
        <div class="plan-name">Rising Artist</div>
        <div class="plan-price">₹1599<span>/year</span></div>
        <div class="plan-period">For new artists</div>
        <ul class="plan-features">
            <li><span class="mdi mdi-check"></span><span class="feature-text">Unlimited releases to all stores</span></li>
            <li><span class="mdi mdi-check"></span><span class="feature-text">Schedule your own release date</span></li>
            <li><span class="mdi mdi-check"></span><span class="feature-text">Spotify Verified Artist Checkmark</span></li>
            <li><span class="mdi mdi-close"></span><span class="feature-text">Apple Music for Artists Verification</span></li>
            <li><span class="mdi mdi-close"></span><span class="feature-text">Artist Revenue Splits</span></li>
            <li><span class="mdi mdi-check"></span><span class="feature-text">Social platforms (20% fee)</span></li>
            <li><span class="mdi mdi-check"></span><span class="feature-text feature-highlight">Customer Service: 72 Hours</span></li>
            <li><span class="mdi mdi-close"></span><span class="feature-text">Store Automator</span></li>
            <li><span class="mdi mdi-close"></span><span class="feature-text">Daily Trend Reports</span></li>
            <li><span class="mdi mdi-check"></span><span class="feature-text">Cover Art Creator</span></li>
            <li><span class="mdi mdi-close"></span><span class="feature-text">Use Your Own ISRC</span></li>
            <li><span class="mdi mdi-close"></span><span class="feature-text">Exclusive Partnerships</span></li>
            <li><span class="mdi mdi-close"></span><span class="feature-text">Promotional Opportunities</span></li>
            <li><span class="mdi mdi-close"></span><span class="feature-text">Pro Panels & Expert Sessions</span></li>
            <li><span class="mdi mdi-close"></span><span class="feature-text">Custom Label Name</span></li>
            <li><span class="mdi mdi-close"></span><span class="feature-text">Country Restrictions</span></li>
            <li><span class="mdi mdi-close"></span><span class="feature-text">YouTube Content ID</span></li>
            <li><span class="mdi mdi-close"></span><span class="feature-text">YouTube OAC</span></li>
            <li><span class="mdi mdi-close"></span><span class="feature-text">TuneCore Direct Advance</span></li>
        </ul>
        <button class="plan-cta secondary" onclick="selectPlan('rising')">Get Started</button>
    </div>
</div>

<div class="comparison-section">
    <h2>Plan Comparison</h2>
    <table class="comparison-table">
        <thead>
            <tr>
                <th>Feature</th>
                <th>Rising Artist</th>
                <th>Breakout Artist</th>
                <th>Professional</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Price</td>
                <td>₹1599/year</td>
                <td>₹2799/year</td>
                <td class="feature-highlight">₹4499/year</td>
            </tr>
            <tr>
                <td>Unlimited Releases</td>
                <td><span class="mdi mdi-check check"></span></td>
                <td><span class="mdi mdi-check check"></span></td>
                <td><span class="mdi mdi-check check"></span></td>
            </tr>
            <tr>
                <td>Schedule Release Date</td>
                <td><span class="mdi mdi-check check"></span></td>
                <td><span class="mdi mdi-check check"></span></td>
                <td><span class="mdi mdi-check check"></span></td>
            </tr>
            <tr>
                <td>Customer Service Response</td>
                <td class="time">72 Hours</td>
                <td class="time">48 Hours</td>
                <td class="time">24 Hours</td>
            </tr>
            <tr>
                <td>Spotify Verified Checkmark</td>
                <td><span class="mdi mdi-check check"></span></td>
                <td><span class="mdi mdi-check check"></span></td>
                <td><span class="mdi mdi-check check"></span></td>
            </tr>
            <tr>
                <td>Apple Music Verification</td>
                <td><span class="mdi mdi-close cross"></span></td>
                <td><span class="mdi mdi-check check"></span></td>
                <td><span class="mdi mdi-check check"></span></td>
            </tr>
            <tr>
                <td>YouTube Content ID</td>
                <td><span class="mdi mdi-close cross"></span></td>
                <td><span class="mdi mdi-check check"></span></td>
                <td><span class="mdi mdi-check check"></span></td>
            </tr>
            <tr>
                <td>Custom Label Name</td>
                <td><span class="mdi mdi-close cross"></span></td>
                <td><span class="mdi mdi-check check"></span></td>
                <td><span class="mdi mdi-check check"></span></td>
            </tr>
            <tr>
                <td>Use Your Own ISRC/UPC</td>
                <td><span class="mdi mdi-close cross"></span></td>
                <td><span class="mdi mdi-check check"></span></td>
                <td><span class="mdi mdi-check check"></span></td>
            </tr>
        </tbody>
    </table>
</div>

<div class="platforms-section">
    <h2>Distribute to 100+ Platforms Worldwide</h2>
    <div class="platforms-grid">
        <div class="platform-item"><span class="mdi mdi-spotify"></span> Spotify</div>
        <div class="platform-item"><span class="mdi mdi-apple"></span> Apple Music</div>
        <div class="platform-item"><span class="mdi mdi-youtube"></span> YouTube Music</div>
        <div class="platform-item"><span class="mdi mdi-amazon"></span> Amazon Music</div>
        <div class="platform-item"><span class="mdi mdi-music"></span> Tidal</div>
        <div class="platform-item"><span class="mdi mdi-music-circle"></span> Deezer</div>
        <div class="platform-item"><span class="mdi mdi-music-note"></span> Pandora</div>
        <div class="platform-item"><span class="mdi mdi-google-play"></span> Google Play</div>
        <div class="platform-item"><span class="mdi mdi-facebook"></span> Facebook</div>
        <div class="platform-item"><span class="mdi mdi-instagram"></span> Instagram</div>
        <div class="platform-item"><span class="mdi mdi-video"></span> TikTok</div>
        <div class="platform-item"><span class="mdi mdi-snapchat"></span> Snapchat</div>
    </div>
</div>

<script>
function selectPlan(planSlug) {
    <?php if (!$is_logged): ?>
        window.location.href = '/userAuth?redirect=/distribution';
        return;
    <?php endif; ?>
    
    // Redirect to subscription/payment page
    window.location.href = '/distribution/subscribe?plan=' + planSlug;
}

// Load plans from API
document.addEventListener('DOMContentLoaded', function() {
    // Plans are already hardcoded above for better SEO and faster loading
    // API can be used to update pricing dynamically if needed
});
</script>

<?php
echo ob_get_clean();
