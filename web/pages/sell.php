<?php
/**
 * HiTune Music Distribution - Sell Your Music Page
 */
$pageTitle = 'Sell Your Music Online - HiTune Music Distribution';
include __DIR__ . '/../includes/header_premium.php';
?>
<style>
    /* ===== SELL HERO ===== */
    .sell-hero {
        padding: 140px 30px 100px;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    .sell-hero::before {
        content: '';
        position: absolute; inset: 0;
        background:
            radial-gradient(ellipse 600px 400px at 20% 10%, rgba(0,183,255,0.18), transparent 60%),
            radial-gradient(ellipse 600px 400px at 80% 90%, rgba(139,92,246,0.16), transparent 60%);
        pointer-events: none;
    }
    .sell-hero::after {
        content: '';
        position: absolute; inset: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="40" fill="none" stroke="rgba(0,183,255,0.08)" stroke-width="0.5"/></svg>') center/200px;
        opacity: 0.6;
        pointer-events: none;
    }
    .sell-hero-content { position: relative; z-index: 1; max-width: 900px; margin: 0 auto; }
    .sell-badge {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 8px 20px; border-radius: 30px;
        background: var(--glass-bg); border: 1px solid var(--glass-border);
        backdrop-filter: blur(10px);
        font-size: 13px; font-weight: 600; color: #00b7ff;
        margin-bottom: 28px;
        animation: fadeInUp 0.7s ease-out both;
    }
    .sell-hero h1 {
        font-size: clamp(38px, 6.5vw, 74px);
        font-weight: 800; line-height: 1.08;
        margin-bottom: 26px; letter-spacing: -1px;
        color: var(--text-primary);
        animation: fadeInUp 0.7s ease-out 0.1s both;
    }
    .sell-hero .lead {
        font-size: clamp(16px, 2vw, 20px);
        color: var(--text-secondary);
        max-width: 720px; margin: 0 auto 42px; line-height: 1.75;
        animation: fadeInUp 0.7s ease-out 0.2s both;
    }
    .sell-hero-btns {
        display: flex; gap: 16px; justify-content: center; flex-wrap: wrap;
        animation: fadeInUp 0.7s ease-out 0.3s both;
    }
    .sell-cta {
        display: inline-flex; align-items: center; gap: 10px;
        padding: 18px 42px;
        background: linear-gradient(135deg, #00b7ff, #8b5cf6);
        color: #fff; text-decoration: none; border-radius: 40px;
        font-size: 16px; font-weight: 700;
        box-shadow: 0 10px 40px rgba(0,183,255,0.35);
        transition: all 0.3s;
    }
    .sell-cta:hover { transform: translateY(-4px); box-shadow: 0 20px 60px rgba(0,183,255,0.5); }
    .sell-cta-alt {
        display: inline-flex; align-items: center; gap: 10px;
        padding: 18px 38px;
        background: var(--glass-bg); border: 1px solid var(--glass-border);
        backdrop-filter: blur(10px);
        color: var(--text-primary); text-decoration: none; border-radius: 40px;
        font-size: 16px; font-weight: 600;
        transition: all 0.3s;
    }
    .sell-cta-alt:hover { border-color: #00b7ff; transform: translateY(-4px); }

    .sell-mini-stats {
        display: flex; justify-content: center; gap: 48px; flex-wrap: wrap;
        margin-top: 70px;
        animation: fadeInUp 0.7s ease-out 0.45s both;
    }
    .sell-mini-stat { text-align: center; }
    .sell-mini-stat strong {
        display: block; font-size: 30px; font-weight: 800;
        background: linear-gradient(135deg, #00b7ff, #8b5cf6);
        -webkit-background-clip: text; background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .sell-mini-stat span { font-size: 13px; color: var(--text-muted); font-weight: 500; }

    .platforms-bar {
        display: flex; justify-content: center; align-items: center;
        gap: 44px; margin-top: 70px; flex-wrap: wrap;
        animation: fadeInUp 0.7s ease-out 0.55s both;
    }
    .platforms-bar i {
        font-size: 34px;
        color: var(--text-muted);
        filter: grayscale(100%);
        transition: all 0.3s;
    }
    .platforms-bar i:hover { filter: grayscale(0%); color: var(--text-primary); transform: scale(1.15); }

    /* ===== SECTIONS SHARED ===== */
    .sell-section { padding: 100px 30px; position: relative; }
    .sell-section-inner { max-width: 1200px; margin: 0 auto; }
    .sell-section-tag {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 7px 18px; border-radius: 30px;
        background: rgba(0,183,255,0.1); border: 1px solid rgba(0,183,255,0.25);
        font-size: 12px; font-weight: 700; color: #00b7ff;
        text-transform: uppercase; letter-spacing: 1px;
        margin-bottom: 18px;
    }
    .sell-h2 {
        font-size: clamp(30px, 4.5vw, 48px); font-weight: 800;
        color: var(--text-primary); margin-bottom: 16px; letter-spacing: -0.5px;
    }
    .sell-sub {
        font-size: 17px; color: var(--text-secondary);
        max-width: 680px; line-height: 1.75;
    }
    .sell-center { text-align: center; }
    .sell-center .sell-sub { margin: 0 auto; }

    /* ===== STEPS ===== */
    .steps-grid {
        display: grid; grid-template-columns: repeat(4, 1fr); gap: 26px;
        margin-top: 60px;
    }
    .step-card {
        position: relative;
        padding: 42px 28px; text-align: center;
        background: var(--glass-bg); border: 1px solid var(--glass-border);
        backdrop-filter: blur(20px);
        border-radius: 24px;
        transition: all 0.4s;
    }
    .step-card:hover {
        transform: translateY(-8px);
        border-color: rgba(0,183,255,0.4);
        box-shadow: 0 24px 60px rgba(0,183,255,0.15);
    }
    .step-number {
        width: 58px; height: 58px;
        background: linear-gradient(135deg, #00b7ff, #8b5cf6);
        border-radius: 18px;
        display: flex; align-items: center; justify-content: center;
        font-size: 24px; font-weight: 800; color: #fff;
        margin: 0 auto 22px;
        box-shadow: 0 10px 30px rgba(0,183,255,0.35);
    }
    .step-card h3 { font-size: 19px; font-weight: 700; color: var(--text-primary); margin-bottom: 12px; }
    .step-card p { color: var(--text-secondary); font-size: 14.5px; line-height: 1.7; }

    /* ===== FEATURE ROWS ===== */
    .feature-row {
        display: grid; grid-template-columns: 1fr 1fr;
        gap: 70px; align-items: center;
        margin-bottom: 90px;
    }
    .feature-row:last-child { margin-bottom: 0; }
    .feature-row.alt { direction: rtl; }
    .feature-row.alt > * { direction: ltr; }
    .feature-content h3 { font-size: clamp(24px, 3vw, 34px); font-weight: 800; color: var(--text-primary); margin-bottom: 18px; letter-spacing: -0.5px; }
    .feature-content p { color: var(--text-secondary); font-size: 16px; line-height: 1.8; margin-bottom: 24px; }
    .feature-list { list-style: none; }
    .feature-list li {
        padding: 10px 0; display: flex; align-items: center; gap: 12px;
        color: var(--text-primary); font-size: 15px; font-weight: 500;
    }
    .feature-list li i {
        color: #00c853; font-size: 20px;
        background: rgba(0,200,83,0.12); border-radius: 50%;
        width: 28px; height: 28px;
        display: inline-flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .feature-visual {
        position: relative;
        background: var(--glass-bg); border: 1px solid var(--glass-border);
        backdrop-filter: blur(20px);
        border-radius: 28px; padding: 56px 40px;
        display: flex; align-items: center; justify-content: center;
        min-height: 340px; overflow: hidden;
        transition: all 0.4s;
    }
    .feature-visual::before {
        content: '';
        position: absolute; inset: 0;
        background: radial-gradient(circle at 50% 50%, rgba(0,183,255,0.12), transparent 65%);
        pointer-events: none;
    }
    .feature-visual:hover { transform: translateY(-6px); border-color: rgba(0,183,255,0.3); box-shadow: 0 30px 70px rgba(0,183,255,0.12); }
    .feature-visual > i {
        font-size: 110px;
        background: linear-gradient(135deg, #00b7ff, #8b5cf6);
        -webkit-background-clip: text; background-clip: text;
        -webkit-text-fill-color: transparent;
        filter: drop-shadow(0 10px 30px rgba(0,183,255,0.35));
    }
    .fv-chip {
        position: absolute;
        background: var(--bg-card); border: 1px solid var(--glass-border);
        backdrop-filter: blur(12px);
        border-radius: 14px; padding: 10px 18px;
        font-size: 13px; font-weight: 600; color: var(--text-primary);
        display: flex; align-items: center; gap: 8px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.12);
        animation: chipFloat 4s ease-in-out infinite;
    }
    .fv-chip i { color: #00b7ff; font-size: 17px; }
    .fv-chip-1 { top: 24px; left: 24px; }
    .fv-chip-2 { bottom: 24px; right: 24px; animation-delay: 1.2s; }
    .fv-chip-3 { top: 24px; right: 24px; animation-delay: 2s; }
    @keyframes chipFloat {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-10px); }
    }
    html[data-theme="light"] .fv-chip { box-shadow: 0 10px 30px rgba(15,23,42,0.1); }

    /* ===== VS TABLE ===== */
    .vs-table-wrap {
        max-width: 860px; margin: 56px auto 0;
        background: var(--glass-bg); border: 1px solid var(--glass-border);
        backdrop-filter: blur(20px);
        border-radius: 26px; overflow: hidden;
    }
    .vs-table { width: 100%; border-collapse: collapse; }
    .vs-table th, .vs-table td { padding: 20px 26px; text-align: left; }
    .vs-table th {
        background: linear-gradient(135deg, rgba(0,183,255,0.12), rgba(139,92,246,0.12));
        color: var(--text-primary); font-size: 15px; font-weight: 700;
    }
    .vs-table td {
        border-top: 1px solid var(--glass-border);
        color: var(--text-secondary); font-size: 15px;
    }
    .vs-table td:first-child { color: var(--text-primary); font-weight: 600; }
    .vs-table .vs-yes { color: #00c853; font-weight: 700; }
    .vs-table .vs-no { color: #ff5252; }
    .vs-table td .mdi { font-size: 19px; vertical-align: -3px; }

    /* ===== EARNINGS STRIP ===== */
    .earn-strip {
        max-width: 1100px; margin: 0 auto;
        display: grid; grid-template-columns: repeat(3, 1fr); gap: 26px;
    }
    .earn-card {
        padding: 40px 30px; text-align: center;
        background: var(--glass-bg); border: 1px solid var(--glass-border);
        backdrop-filter: blur(20px);
        border-radius: 24px;
        transition: all 0.4s;
    }
    .earn-card:hover { transform: translateY(-6px); border-color: rgba(139,92,246,0.4); box-shadow: 0 24px 60px rgba(139,92,246,0.15); }
    .earn-card i {
        font-size: 40px; margin-bottom: 18px;
        background: linear-gradient(135deg, #00b7ff, #8b5cf6);
        -webkit-background-clip: text; background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .earn-card h4 { font-size: 19px; font-weight: 700; color: var(--text-primary); margin-bottom: 10px; }
    .earn-card p { font-size: 14.5px; color: var(--text-secondary); line-height: 1.7; }

    /* ===== FINAL CTA ===== */
    .sell-final {
        text-align: center; padding: 110px 30px;
        position: relative; overflow: hidden;
    }
    .sell-final::before {
        content: '';
        position: absolute; top: 50%; left: 50%;
        transform: translate(-50%, -50%);
        width: 700px; height: 700px;
        background: radial-gradient(circle, rgba(0,183,255,0.14), transparent 60%);
        animation: ctaGlow 6s ease-in-out infinite;
        pointer-events: none;
    }
    @keyframes ctaGlow {
        0%, 100% { transform: translate(-50%,-50%) scale(1); opacity: 0.5; }
        50% { transform: translate(-50%,-50%) scale(1.15); opacity: 0.85; }
    }
    .sell-final h2 { font-size: clamp(30px, 4.5vw, 52px); font-weight: 800; color: var(--text-primary); margin-bottom: 18px; position: relative; }
    .sell-final p { color: var(--text-secondary); font-size: 17px; margin-bottom: 38px; position: relative; }

    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 968px) {
        .steps-grid { grid-template-columns: repeat(2, 1fr); }
        .earn-strip { grid-template-columns: 1fr; }
        .feature-row { grid-template-columns: 1fr; gap: 36px; margin-bottom: 60px; }
        .feature-row.alt { direction: ltr; }
        .vs-table th, .vs-table td { padding: 14px 16px; font-size: 13.5px; }
        .sell-section { padding: 70px 20px; }
    }
    @media (max-width: 560px) {
        .steps-grid { grid-template-columns: 1fr; }
        .sell-mini-stats { gap: 30px; }
        .platforms-bar { gap: 26px; }
        .platforms-bar i { font-size: 26px; }
    }
</style>

<section class="sell-hero">
    <div class="sell-hero-content">
        <div class="sell-badge">
            <span class="mdi mdi-lightning-bolt"></span>
            Independent Music Distribution
        </div>
        <h1>Sell <span class="gradient-text">Your Music</span><br>Online — Everywhere</h1>
        <p class="lead">Make money from your songs on 150+ streaming platforms and social networks — Spotify, Apple Music, JioSaavn, TikTok and more. You keep 100% of your royalties. Always.</p>
        <div class="sell-hero-btns">
            <a href="<?php echo isLoggedIn() ? '/index.php?q=dashboard' : '/index.php?q=signup'; ?>" class="sell-cta">
                <span class="mdi mdi-<?php echo isLoggedIn() ? 'view-dashboard' : 'rocket-launch'; ?>"></span>
                <?php echo isLoggedIn() ? 'Go to Your Dashboard' : "Start Selling — It's Free to Join"; ?>
            </a>
            <a href="/index.php?q=pricing" class="sell-cta-alt">
                <span class="mdi mdi-tag-outline"></span>
                See Plans
            </a>
        </div>

        <div class="sell-mini-stats">
            <div class="sell-mini-stat"><strong>150+</strong><span>Platforms &amp; Stores</span></div>
            <div class="sell-mini-stat"><strong>100%</strong><span>Royalties Kept</span></div>
            <div class="sell-mini-stat"><strong>24–72h</strong><span>Average Go-Live</span></div>
            <div class="sell-mini-stat"><strong>₹0</strong><span>Hidden Fees</span></div>
        </div>

        <div class="platforms-bar">
            <i class="mdi mdi-spotify" title="Spotify"></i>
            <i class="mdi mdi-apple" title="Apple Music"></i>
            <i class="mdi mdi-youtube" title="YouTube Music"></i>
            <i class="mdi mdi-amazon" title="Amazon Music"></i>
            <i class="mdi mdi-music" title="Tidal"></i>
            <i class="mdi mdi-music-circle" title="Deezer"></i>
            <i class="mdi mdi-music-note" title="JioSaavn"></i>
            <i class="mdi mdi-shazam" title="Shazam"></i>
        </div>
    </div>
</section>

<section class="sell-section">
    <div class="sell-section-inner sell-center">
        <div class="sell-section-tag"><span class="mdi mdi-map-marker-path"></span> The Process</div>
        <h2 class="sell-h2">How to Sell Your Music <span class="gradient-text">Online</span></h2>
        <p class="sell-sub">It's never been easier to get your music on stores and start earning. Four simple steps — that's it.</p>

        <div class="steps-grid">
            <div class="step-card animate-on-scroll">
                <div class="step-number">1</div>
                <h3>Create Account</h3>
                <p>Sign up free in under a minute. Pick a plan that fits — yearly unlimited or per-release.</p>
            </div>
            <div class="step-card animate-on-scroll">
                <div class="step-number">2</div>
                <h3>Upload Music</h3>
                <p>Drop your WAV, FLAC or 320kbps MP3 with artwork and metadata. Quality-checked instantly.</p>
            </div>
            <div class="step-card animate-on-scroll">
                <div class="step-number">3</div>
                <h3>We Distribute</h3>
                <p>We deliver to Spotify, Apple Music, JioSaavn and 150+ stores. Most releases live in 24–72 hours.</p>
            </div>
            <div class="step-card animate-on-scroll">
                <div class="step-number">4</div>
                <h3>You Get Paid</h3>
                <p>Royalties land in your dashboard monthly. Withdraw to bank or UPI — keep every rupee.</p>
            </div>
        </div>
    </div>
</section>

<section class="sell-section" style="padding-top:0;">
    <div class="sell-section-inner">

        <div class="feature-row">
            <div class="feature-content">
                <div class="sell-section-tag"><span class="mdi mdi-infinity"></span> Unlimited</div>
                <h3>Unlimited Music Distribution</h3>
                <p>Release as much music as you want with yearly plans. No per-release fees, no upload limits, no hidden costs. Singles, EPs, albums — upload and go.</p>
                <ul class="feature-list">
                    <li><i class="mdi mdi-check"></i> 150+ streaming platforms &amp; stores</li>
                    <li><i class="mdi mdi-check"></i> Social media monetization (TikTok, Instagram, YouTube)</li>
                    <li><i class="mdi mdi-check"></i> Free ISRC &amp; UPC codes included</li>
                    <li><i class="mdi mdi-check"></i> Scheduled releases &amp; pre-orders</li>
                </ul>
            </div>
            <div class="feature-visual">
                <i class="mdi mdi-cloud-upload"></i>
                <div class="fv-chip fv-chip-1"><i class="mdi mdi-check-decagram"></i> QC Approved</div>
                <div class="fv-chip fv-chip-2"><i class="mdi mdi-store"></i> 150+ Stores</div>
            </div>
        </div>

        <div class="feature-row alt">
            <div class="feature-content">
                <div class="sell-section-tag"><span class="mdi mdi-cash-100"></span> 100% Yours</div>
                <h3>Keep Every Rupee You Earn</h3>
                <p>Unlike most distributors, we never take a cut of your royalties. What your music earns is what you get — paid monthly, straight to your bank.</p>
                <ul class="feature-list">
                    <li><i class="mdi mdi-check"></i> 100% royalty retention — zero commission</li>
                    <li><i class="mdi mdi-check"></i> Monthly payouts via bank transfer / UPI</li>
                    <li><i class="mdi mdi-check"></i> Detailed earnings reports per platform</li>
                    <li><i class="mdi mdi-check"></i> No withdrawal fees on your earnings</li>
                </ul>
            </div>
            <div class="feature-visual">
                <i class="mdi mdi-hand-coin"></i>
                <div class="fv-chip fv-chip-1"><i class="mdi mdi-trending-up"></i> +₹12,480 this month</div>
                <div class="fv-chip fv-chip-3"><i class="mdi mdi-bank"></i> Instant UPI payout</div>
            </div>
        </div>

        <div class="feature-row">
            <div class="feature-content">
                <div class="sell-section-tag"><span class="mdi mdi-chart-box-outline"></span> Insights</div>
                <h3>Analytics That Actually Help</h3>
                <p>See exactly where your fans are and how they find you. Real-time stream counts, geographic breakdowns, playlist adds — all in one dashboard.</p>
                <ul class="feature-list">
                    <li><i class="mdi mdi-check"></i> Real-time streaming data per platform</li>
                    <li><i class="mdi mdi-check"></i> Geographic &amp; demographic insights</li>
                    <li><i class="mdi mdi-check"></i> Playlist &amp; discovery tracking</li>
                    <li><i class="mdi mdi-check"></i> Revenue forecasting tools</li>
                </ul>
            </div>
            <div class="feature-visual">
                <i class="mdi mdi-chart-line"></i>
                <div class="fv-chip fv-chip-1"><i class="mdi mdi-map-marker"></i> Mumbai · Delhi · London</div>
                <div class="fv-chip fv-chip-2"><i class="mdi mdi-playlist-music"></i> 42 playlist adds</div>
            </div>
        </div>

    </div>
</section>

<section class="sell-section" style="padding-top:0;">
    <div class="sell-section-inner sell-center">
        <div class="sell-section-tag"><span class="mdi mdi-scale-balance"></span> Why HiTune</div>
        <h2 class="sell-h2">HiTune vs <span class="gradient-text">Typical Distributors</span></h2>
        <p class="sell-sub">We built HiTune for independent artists — transparent pricing, real payouts, and tools that actually help you grow.</p>

        <div class="vs-table-wrap animate-on-scroll">
            <table class="vs-table">
                <thead>
                    <tr>
                        <th>What matters</th>
                        <th>HiTune</th>
                        <th>Typical Distributors</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Your royalties</td>
                        <td class="vs-yes"><i class="mdi mdi-check-circle"></i> 100% yours</td>
                        <td class="vs-no"><i class="mdi mdi-close-circle"></i> Often 85–90%</td>
                    </tr>
                    <tr>
                        <td>Per-release fees</td>
                        <td class="vs-yes"><i class="mdi mdi-check-circle"></i> None on yearly plans</td>
                        <td class="vs-no"><i class="mdi mdi-close-circle"></i> ₹500–2000 per release</td>
                    </tr>
                    <tr>
                        <td>Indian platforms</td>
                        <td class="vs-yes"><i class="mdi mdi-check-circle"></i> JioSaavn, Gaana, Wynk + global</td>
                        <td class="vs-no"><i class="mdi mdi-close-circle"></i> Limited India coverage</td>
                    </tr>
                    <tr>
                        <td>Payout options</td>
                        <td class="vs-yes"><i class="mdi mdi-check-circle"></i> Bank, UPI, monthly</td>
                        <td class="vs-no"><i class="mdi mdi-close-circle"></i> Slow international wires</td>
                    </tr>
                    <tr>
                        <td>Support</td>
                        <td class="vs-yes"><i class="mdi mdi-check-circle"></i> 24/7 human support</td>
                        <td class="vs-no"><i class="mdi mdi-close-circle"></i> Ticket queues, days of wait</td>
                    </tr>
                    <tr>
                        <td>Catalog switching</td>
                        <td class="vs-yes"><i class="mdi mdi-check-circle"></i> Free ISRC migration</td>
                        <td class="vs-no"><i class="mdi mdi-close-circle"></i> Lose your stream counts</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<section class="sell-section" style="padding-top:0;">
    <div class="sell-section-inner">
        <div class="sell-center" style="margin-bottom:56px;">
            <div class="sell-section-tag"><span class="mdi mdi-star-four-points"></span> More Tools</div>
            <h2 class="sell-h2">Everything an Artist <span class="gradient-text">Needs</span></h2>
        </div>
        <div class="earn-strip">
            <div class="earn-card animate-on-scroll">
                <i class="mdi mdi-youtube-subscription"></i>
                <h4>YouTube Content ID</h4>
                <p>Earn money whenever anyone uses your music in YouTube videos — automatic claims and collection.</p>
            </div>
            <div class="earn-card animate-on-scroll">
                <i class="mdi mdi-account-music"></i>
                <h4>Artist Verification</h4>
                <p>Get verified artist profiles on Spotify for Artists and Apple Music for Artists with our fast-track setup.</p>
            </div>
            <div class="earn-card animate-on-scroll">
                <i class="mdi mdi-account-group"></i>
                <h4>Split Payments</h4>
                <p>Automatically split royalties with collaborators, producers and featured artists — no manual math.</p>
            </div>
        </div>
    </div>
</section>

<section class="sell-final">
    <h2>Your Music Deserves to Be <span class="gradient-text">Heard</span></h2>
    <p>Join thousands of independent artists distributing worldwide with HiTune.</p>
    <a href="<?php echo isLoggedIn() ? '/index.php?q=release-create' : '/index.php?q=signup'; ?>" class="sell-cta" style="position:relative;">
        <span class="mdi mdi-<?php echo isLoggedIn() ? 'cloud-upload' : 'rocket-launch'; ?>"></span>
        <?php echo isLoggedIn() ? 'Upload a New Release' : 'Start Distributing Now'; ?>
    </a>
</section>

<?php include __DIR__ . '/../includes/footer_premium.php'; ?>
