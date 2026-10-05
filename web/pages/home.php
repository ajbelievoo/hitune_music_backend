<?php
/**
 * HiTune Music Distribution - Premium Homepage
 * Task 3: Full redesign with hero, DSP marquee, features, testimonials, pricing preview, CTA
 */

$pageTitle       = 'HiTune Music Distribution - Distribute Music to 150+ Platforms';
$metaDescription = 'Distribute your music to Spotify, Apple Music, YouTube Music and 150+ platforms. Keep 100% royalties, track per-platform delivery, and get real human support.';
$metaKeywords    = 'music distribution, distribute music online, sell music, streaming platforms, royalties, independent artist, music distributor India';
$canonicalUrl    = 'https://web.hitune.in/';
$path            = 'home';
$jsonLd = [
    '@context' => 'https://schema.org',
    '@graph'   => [
        [
            '@type'       => 'Organization',
            'name'        => 'HiTune Music Distribution',
            'url'         => 'https://web.hitune.in',
            'logo'        => 'https://web.hitune.in/assets/logo.png',
            'description' => 'Music distribution platform for independent artists',
            'sameAs'      => [
                'https://twitter.com/hitune_music',
                'https://instagram.com/hitune_music',
            ],
        ],
        [
            '@type'           => 'WebSite',
            'name'            => 'HiTune Music Distribution',
            'url'             => 'https://web.hitune.in',
            'potentialAction' => [
                '@type'       => 'SearchAction',
                'target'      => 'https://web.hitune.in/index.php?q=search&query={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ],
    ],
];

include __DIR__ . '/../includes/header_premium.php';
?>

<style>
/* ==================== HERO SECTION ==================== */
.hero-premium {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 140px 40px 80px;
    position: relative;
    overflow: hidden;
}

.hero-premium::before {
    content: '';
    position: absolute;
    width: 800px; height: 800px;
    background: radial-gradient(circle, rgba(0,183,255,0.25) 0%, transparent 60%);
    top: -200px; right: -200px;
    animation: heroGlow1 8s ease-in-out infinite;
        pointer-events: none;
    }

.hero-premium::after {
    content: '';
    position: absolute;
    width: 600px; height: 600px;
    background: radial-gradient(circle, rgba(102,126,234,0.2) 0%, transparent 60%);
    bottom: -100px; left: -100px;
    animation: heroGlow2 10s ease-in-out infinite reverse;
        pointer-events: none;
    }

@keyframes heroGlow1 {
    0%, 100% { transform: scale(1); opacity: 0.5; }
    50%       { transform: scale(1.3); opacity: 0.8; }
}

@keyframes heroGlow2 {
    0%, 100% { transform: scale(1); opacity: 0.4; }
    50%       { transform: scale(1.2); opacity: 0.7; }
}

.hero-content {
    text-align: center;
    max-width: 1000px;
    position: relative;
    z-index: 1;
}

.hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 10px 24px;
    background: rgba(0,183,255,0.1);
    border: 1px solid rgba(0,183,255,0.3);
    border-radius: 50px;
    font-size: 14px;
    font-weight: 500;
    color: #00b7ff;
    margin-bottom: 30px;
    animation: fadeInUp 0.8s ease-out;
}

.hero-premium h1 {
    font-size: clamp(48px, 10vw, 90px);
    font-weight: 800;
    line-height: 1.05;
    margin-bottom: 30px;
    animation: fadeInUp 0.8s ease-out 0.1s both;
}

.gradient-text {
    background: linear-gradient(135deg, #00b7ff, #8b5cf6, #ff6b9d);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    background-size: 200% 200%;
    animation: gradientShift 5s ease infinite;
}

@keyframes gradientShift {
    0%, 100% { background-position: 0% 50%; }
    50%       { background-position: 100% 50%; }
}

.hero-description {
    font-size: clamp(18px, 2.5vw, 22px);
    color: rgba(255,255,255,0.7);
    max-width: 700px;
    margin: 0 auto 40px;
    line-height: 1.7;
    animation: fadeInUp 0.8s ease-out 0.2s both;
}

.hero-buttons {
    display: flex;
    gap: 20px;
    justify-content: center;
    flex-wrap: wrap;
    animation: fadeInUp 0.8s ease-out 0.3s both;
}

.hero-btn {
    padding: 18px 40px;
    border-radius: 50px;
    font-size: 16px;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 12px;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.hero-btn-primary {
    background: linear-gradient(135deg, #00b7ff, #8b5cf6);
    color: white;
    box-shadow: 0 10px 40px rgba(0,183,255,0.4);
    animation: btnGlow 3s ease-in-out infinite;
}

@keyframes btnGlow {
    0%, 100% { box-shadow: 0 10px 40px rgba(0,183,255,0.4); }
    50%       { box-shadow: 0 15px 60px rgba(0,183,255,0.7); }
}

.hero-btn-primary::before {
    content: '';
    position: absolute;
    top: 0; left: -100%;
    width: 100%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    transition: left 0.6s;
        pointer-events: none;
    }

.hero-btn-primary:hover { transform: translateY(-4px) scale(1.02); }
.hero-btn-primary:hover::before { left: 100%; }

.hero-btn-secondary {
    background: rgba(255,255,255,0.05);
    border: 2px solid rgba(255,255,255,0.2);
    color: white;
}

.hero-btn-secondary:hover {
    background: rgba(255,255,255,0.1);
    border-color: rgba(0,183,255,0.5);
    transform: translateY(-4px);
}

.hero-stats {
    display: flex;
    justify-content: center;
    gap: 60px;
    margin-top: 60px;
    padding-top: 40px;
    border-top: 1px solid rgba(255,255,255,0.1);
    animation: fadeInUp 0.8s ease-out 0.4s both;
}

.hero-stat { text-align: center; }

.hero-stat h4 {
    font-size: 32px;
    font-weight: 700;
    background: linear-gradient(135deg, #00b7ff, #8b5cf6);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 5px;
}

.hero-stat p { font-size: 14px; color: rgba(255,255,255,0.5); }

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(40px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* ==================== DSP MARQUEE SECTION ==================== */
.dsp-marquee-section {
    padding: 80px 0;
    overflow: hidden;
    position: relative;
    border-top: 1px solid rgba(255,255,255,0.06);
    border-bottom: 1px solid rgba(255,255,255,0.06);
}

.dsp-marquee-section::before,
.dsp-marquee-section::after {
    content: '';
    position: absolute;
    top: 0; bottom: 0;
    width: 120px;
    z-index: 2;
    pointer-events: none;
}

.dsp-marquee-section::before {
    left: 0;
    background: linear-gradient(90deg, #0a0a0f, transparent);
}

.dsp-marquee-section::after {
    right: 0;
    background: linear-gradient(270deg, #0a0a0f, transparent);
}

.dsp-marquee-header {
    text-align: center;
    margin-bottom: 40px;
    padding: 0 40px;
}

.dsp-marquee-header p {
    font-size: 13px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 3px;
    color: rgba(255,255,255,0.35);
}

.marquee-track {
    display: flex;
    gap: 0;
    width: max-content;
}

.marquee-row {
    overflow: hidden;
    margin-bottom: 16px;
}

.marquee-row:last-child { margin-bottom: 0; }

.marquee-row-1 .marquee-track { animation: marqueeLeft 30s linear infinite; }
.marquee-row-2 .marquee-track { animation: marqueeRight 35s linear infinite; }

@keyframes marqueeLeft {
    0%   { transform: translateX(0); }
    100% { transform: translateX(-50%); }
}

@keyframes marqueeRight {
    0%   { transform: translateX(-50%); }
    100% { transform: translateX(0); }
}

.dsp-chip {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 12px 24px;
    margin: 0 10px;
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 50px;
    white-space: nowrap;
    font-size: 14px;
    font-weight: 500;
    color: rgba(255,255,255,0.65);
    transition: all 0.3s;
}

.dsp-chip .mdi { font-size: 20px; }
.dsp-chip.spotify  { color: #1db954; }
.dsp-chip.apple    { color: #fc3c44; }
.dsp-chip.youtube  { color: #ff0000; }
.dsp-chip.amazon   { color: #00a8e1; }
.dsp-chip.tidal    { color: #00ffff; }
.dsp-chip.deezer   { color: #a238ff; }
.dsp-chip.jiosaavn { color: #2bc5b4; }
.dsp-chip.gaana    { color: #e72c30; }
.dsp-chip.tiktok   { color: #69c9d0; }
.dsp-chip.instagram{ color: #e1306c; }
.dsp-chip.wynk     { color: #0057ff; }
.dsp-chip.boomplay { color: #0090d9; }
.dsp-chip.facebook { color: #1877f2; }
.dsp-chip.shazam   { color: #0088ff; }
.dsp-chip.napster  { color: #0090d9; }
.dsp-chip.iheartradio { color: #c6002b; }

.dsp-view-all {
    text-align: center;
    margin-top: 36px;
    padding: 0 40px;
}

.dsp-view-all a {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 28px;
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 50px;
    color: rgba(255,255,255,0.7);
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s;
}

.dsp-view-all a:hover {
    background: rgba(0,183,255,0.1);
    border-color: rgba(0,183,255,0.3);
    color: #00b7ff;
    transform: translateY(-2px);
}

/* ==================== FEATURES SECTION ==================== */
.features-section {
    padding: 120px 40px;
    position: relative;
}

.features-section::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
        pointer-events: none;
    }

.section-header {
    text-align: center;
    margin-bottom: 80px;
}

.section-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 18px;
    background: rgba(0,183,255,0.1);
    border: 1px solid rgba(0,183,255,0.2);
    border-radius: 50px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 2px;
    color: #00b7ff;
    margin-bottom: 25px;
}

.section-header h2 {
    font-size: clamp(36px, 5vw, 56px);
    font-weight: 800;
    margin-bottom: 20px;
}

.section-header p {
    font-size: 18px;
    color: rgba(255,255,255,0.6);
    max-width: 600px;
    margin: 0 auto;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 30px;
    max-width: 1300px;
    margin: 0 auto;
}

.feature-card {
    background: linear-gradient(135deg, rgba(255,255,255,0.03) 0%, rgba(255,255,255,0.01) 100%);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 24px;
    padding: 45px 35px;
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.feature-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        pointer-events: none;
    }

.feature-card:hover {
    transform: translateY(-8px);
    border-color: rgba(0,183,255,0.3);
    box-shadow: 0 30px 60px rgba(0,0,0,0.4), 0 0 40px rgba(0,183,255,0.1);
}

.feature-icon {
    width: 70px; height: 70px;
    background: linear-gradient(135deg, #00b7ff, #8b5cf6);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    color: white;
    margin-bottom: 28px;
    box-shadow: 0 10px 30px rgba(0,183,255,0.3);
}

.feature-card h3 { font-size: 22px; font-weight: 700; margin-bottom: 15px; }
.feature-card p  { color: rgba(255,255,255,0.6); font-size: 15px; line-height: 1.7; }

/* Stagger delays */
.stagger-1 { transition-delay: 0s; }
.stagger-2 { transition-delay: 0.05s; }
.stagger-3 { transition-delay: 0.1s; }
.stagger-4 { transition-delay: 0.15s; }
.stagger-5 { transition-delay: 0.2s; }
.stagger-6 { transition-delay: 0.25s; }

/* ==================== TESTIMONIALS SECTION ==================== */
.testimonials-section {
    padding: 120px 40px;
    position: relative;
}

.testimonials-section::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
        pointer-events: none;
    }

.testimonials-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 30px;
    max-width: 1200px;
    margin: 0 auto;
}

.testimonial-card {
    background: rgba(255,255,255,0.03);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 24px;
    padding: 40px 35px;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.testimonial-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
        pointer-events: none;
    }

.testimonial-card:hover {
    transform: translateY(-8px);
    border-color: rgba(0,183,255,0.25);
    box-shadow: 0 20px 50px rgba(0,0,0,0.3), 0 0 30px rgba(0,183,255,0.08);
}

.testimonial-stars {
    display: flex;
    gap: 4px;
    margin-bottom: 20px;
}

.testimonial-stars .mdi { font-size: 18px; color: #ffd700; }

.testimonial-quote {
    font-size: 15px;
    line-height: 1.8;
    color: rgba(255,255,255,0.75);
    margin-bottom: 28px;
    font-style: italic;
}

.testimonial-quote::before { content: '\201C'; font-size: 40px; color: #00b7ff; line-height: 0; vertical-align: -0.4em; margin-right: 4px; }

.testimonial-author {
    display: flex;
    align-items: center;
    gap: 14px;
}

.testimonial-avatar {
    width: 48px; height: 48px;
    background: linear-gradient(135deg, #00b7ff, #8b5cf6);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    color: white;
    font-weight: 700;
    flex-shrink: 0;
}

.testimonial-name { font-size: 15px; font-weight: 600; color: white; }
.testimonial-genre {
    display: inline-block;
    margin-top: 4px;
    padding: 3px 10px;
    background: rgba(0,183,255,0.12);
    border: 1px solid rgba(0,183,255,0.2);
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    color: #00b7ff;
    text-transform: uppercase;
    letter-spacing: 1px;
}

/* ==================== PRICING PREVIEW SECTION ==================== */
.pricing-preview-section {
    padding: 120px 40px;
    position: relative;
}

.pricing-preview-section::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
        pointer-events: none;
    }

.pricing-preview-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 30px;
    max-width: 1100px;
    margin: 0 auto 50px;
}

.pricing-card {
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 24px;
    padding: 40px 35px;
    position: relative;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
}

.pricing-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
        pointer-events: none;
    }

.pricing-card.recommended {
    border-color: rgba(0,183,255,0.4);
    background: rgba(0,183,255,0.04);
    box-shadow: 0 0 40px rgba(0,183,255,0.1);
}

.pricing-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 50px rgba(0,0,0,0.3);
}

.pricing-card.recommended:hover {
    box-shadow: 0 20px 50px rgba(0,0,0,0.3), 0 0 50px rgba(0,183,255,0.15);
}

.recommended-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 14px;
    background: linear-gradient(135deg, #00b7ff, #8b5cf6);
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    color: white;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 20px;
}

.pricing-plan-name { font-size: 20px; font-weight: 700; color: white; margin-bottom: 8px; }

.pricing-price {
    margin-bottom: 28px;
}

.pricing-price .amount {
    font-size: 42px;
    font-weight: 800;
    background: linear-gradient(135deg, #00b7ff, #8b5cf6);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.pricing-price .period { font-size: 14px; color: rgba(255,255,255,0.45); margin-left: 4px; }

.pricing-features { list-style: none; padding: 0; margin: 0 0 32px; }

.pricing-features li {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 0;
    font-size: 14px;
    color: rgba(255,255,255,0.7);
    border-bottom: 1px solid rgba(255,255,255,0.05);
}

.pricing-features li:last-child { border-bottom: none; }
.pricing-features li .mdi { font-size: 18px; color: #00c853; flex-shrink: 0; }

.pricing-cta-wrap { text-align: center; }

.pricing-view-all {
    text-align: center;
}

.pricing-view-all a {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 16px 36px;
    background: linear-gradient(135deg, #00b7ff, #8b5cf6);
    color: white;
    border-radius: 50px;
    font-size: 15px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s;
    box-shadow: 0 8px 30px rgba(0,183,255,0.35);
}

.pricing-view-all a:hover {
    transform: translateY(-3px);
    box-shadow: 0 14px 40px rgba(0,183,255,0.5);
}

/* ==================== STATS SECTION ==================== */
.stats-section {
    padding: 100px 40px;
    background: linear-gradient(180deg, transparent 0%, rgba(0,183,255,0.03) 50%, transparent 100%);
    position: relative;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 40px;
    max-width: 1200px;
    margin: 0 auto;
}

.stat-card {
    text-align: center;
    padding: 40px 30px;
    background: rgba(255,255,255,0.02);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 24px;
    transition: all 0.4s;
}

.stat-card:hover {
    background: rgba(255,255,255,0.04);
    border-color: rgba(0,183,255,0.2);
    transform: translateY(-5px);
}

.stat-number {
    font-size: 56px;
    font-weight: 800;
    background: linear-gradient(135deg, #00b7ff, #8b5cf6);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 10px;
    display: block;
}

.stat-label { font-size: 15px; color: rgba(255,255,255,0.5); font-weight: 500; }

/* ==================== CTA SECTION ==================== */
.cta-section {
    padding: 120px 40px;
    position: relative;
    overflow: hidden;
}

.cta-section::before {
    content: '';
    position: absolute;
    top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    width: 800px; height: 800px;
    background: radial-gradient(circle, rgba(0,183,255,0.15) 0%, transparent 60%);
    animation: ctaGlow 6s ease-in-out infinite;
        pointer-events: none;
    }

@keyframes ctaGlow {
    0%, 100% { transform: translate(-50%, -50%) scale(1); opacity: 0.5; }
    50%       { transform: translate(-50%, -50%) scale(1.2); opacity: 0.8; }
}

.cta-container {
    max-width: 800px;
    margin: 0 auto;
    text-align: center;
    position: relative;
    z-index: 1;
}

.cta-card {
    background: rgba(255,255,255,0.03);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 30px;
    padding: 70px 50px;
    position: relative;
    overflow: hidden;
}

.cta-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
        pointer-events: none;
    }

.cta-card h2 { font-size: clamp(32px, 5vw, 48px); font-weight: 800; margin-bottom: 20px; }
.cta-card p  { font-size: 18px; color: rgba(255,255,255,0.6); margin-bottom: 40px; }

.cta-btn {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    padding: 20px 50px;
    background: linear-gradient(135deg, #00b7ff, #8b5cf6);
    color: white;
    border-radius: 50px;
    font-size: 17px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.4s;
    box-shadow: 0 10px 40px rgba(0,183,255,0.4);
}

.cta-btn:hover {
    transform: translateY(-4px) scale(1.02);
    box-shadow: 0 20px 50px rgba(0,183,255,0.6);
}

/* ==================== SCROLL ANIMATIONS ==================== */
.animate-on-scroll {
    opacity: 0;
    transform: translateY(30px);
    transition: opacity 0.8s cubic-bezier(0.4, 0, 0.2, 1), transform 0.8s cubic-bezier(0.4, 0, 0.2, 1);
}

.animate-on-scroll.visible {
    opacity: 1;
    transform: translateY(0);
}

/* ==================== RESPONSIVE ==================== */
@media (max-width: 1024px) {
    .features-grid,
    .testimonials-grid,
    .pricing-preview-grid { grid-template-columns: repeat(2, 1fr); }
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
    .hero-stats { gap: 40px; }
}

@media (max-width: 768px) {
    .hero-premium { padding: 120px 20px 60px; }
    .hero-buttons { flex-direction: column; align-items: center; }
    .hero-btn { width: 100%; max-width: 300px; justify-content: center; }
    .hero-stats { flex-direction: column; gap: 30px; }
    .features-grid,
    .testimonials-grid,
    .pricing-preview-grid { grid-template-columns: 1fr; }
    .features-section,
    .testimonials-section,
    .pricing-preview-section,
    .stats-section,
    .cta-section { padding: 80px 20px; }
    .stats-grid { grid-template-columns: 1fr; gap: 20px; }
    .cta-card { padding: 50px 30px; }
}

/* ==================== HOW IT WORKS ==================== */
.how-section { padding: 100px 40px; position: relative; }
.how-grid {
    max-width: 1200px; margin: 60px auto 0;
    display: grid; grid-template-columns: repeat(3, 1fr); gap: 32px;
    position: relative;
}
.how-grid::before {
    content: '';
    position: absolute; top: 60px; left: 12%; right: 12%;
    height: 2px;
    background: linear-gradient(90deg, transparent, rgba(0,183,255,0.4), rgba(139,92,246,0.4), transparent);
    z-index: 0;
        pointer-events: none;
    }
.how-step {
    position: relative; z-index: 1;
    background: var(--glass-bg);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid var(--glass-border);
    border-radius: 24px;
    padding: 44px 32px;
    text-align: center;
    transition: transform 0.4s, box-shadow 0.4s, border-color 0.4s;
}
.how-step:hover {
    transform: translateY(-8px);
    border-color: rgba(0,183,255,0.35);
    box-shadow: 0 24px 60px rgba(0,183,255,0.15);
}
.how-step-num {
    width: 40px; height: 40px; margin: 0 auto 20px;
    border-radius: 50%;
    background: linear-gradient(135deg, #00b7ff, #8b5cf6);
    color: #fff; font-weight: 800; font-size: 17px;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 8px 24px rgba(0,183,255,0.35);
}
.how-step-icon {
    width: 72px; height: 72px; margin: 0 auto 22px;
    border-radius: 20px;
    background: linear-gradient(135deg, rgba(0,183,255,0.12), rgba(139,92,246,0.12));
    border: 1px solid rgba(0,183,255,0.2);
    display: flex; align-items: center; justify-content: center;
    font-size: 34px; color: #00b7ff;
}
.how-step h3 { font-size: 22px; font-weight: 700; color: var(--text-primary); margin-bottom: 12px; }
.how-step p { font-size: 15px; line-height: 1.7; color: var(--text-secondary); }

/* ==================== FAQ ==================== */
.faq-home-section { padding: 100px 40px; position: relative; }
.faq-home-list { max-width: 820px; margin: 50px auto 0; display: flex; flex-direction: column; gap: 16px; }
.faq-home-item {
    background: var(--glass-bg);
    backdrop-filter: blur(20px);
    border: 1px solid var(--glass-border);
    border-radius: 18px;
    overflow: hidden;
    transition: border-color 0.3s, box-shadow 0.3s;
}
.faq-home-item.open { border-color: rgba(0,183,255,0.35); box-shadow: 0 12px 40px rgba(0,183,255,0.1); }
.faq-home-q {
    width: 100%; background: none; border: none; cursor: pointer;
    display: flex; align-items: center; justify-content: space-between; gap: 16px;
    padding: 22px 26px; text-align: left;
    color: var(--text-primary); font-size: 17px; font-weight: 600;
    font-family: inherit;
}
.faq-home-q .mdi { font-size: 22px; color: #00b7ff; transition: transform 0.35s; flex-shrink: 0; }
.faq-home-item.open .faq-home-q .mdi { transform: rotate(45deg); }
.faq-home-a {
    max-height: 0; overflow: hidden;
    transition: max-height 0.4s cubic-bezier(0.4,0,0.2,1), padding 0.3s;
    padding: 0 26px;
}
.faq-home-item.open .faq-home-a { max-height: 300px; padding: 0 26px 24px; }
.faq-home-a p { color: var(--text-secondary); font-size: 15px; line-height: 1.75; }

html[data-theme="light"] .how-step { box-shadow: 0 10px 40px rgba(15,23,42,0.07); }
html[data-theme="light"] .how-step:hover { box-shadow: 0 24px 60px rgba(0,183,255,0.18); }
html[data-theme="light"] .faq-home-item { box-shadow: 0 8px 30px rgba(15,23,42,0.05); }

@media (max-width: 968px) {
    .how-grid { grid-template-columns: 1fr; gap: 24px; }
    .how-grid::before { display: none; }
    .how-section, .faq-home-section { padding: 70px 20px; }
}
</style>

<!-- ==================== HERO SECTION ==================== -->
<section class="hero-premium" aria-labelledby="hero-heading">
    <div class="hero-content">
        <div class="hero-badge" aria-hidden="true">
            <span class="mdi mdi-lightning-bolt"></span>
            Independent Music Distribution — A Believoo Product
        </div>
        <h1 id="hero-heading">
            Distribute Your<br>
            <span class="gradient-text">Music</span> Worldwide
        </h1>
        <p class="hero-description">
            Get your music on Spotify, Apple Music, YouTube Music and 150+ streaming platforms.
            Keep 100% of your royalties and own your career.
        </p>
        <div class="hero-buttons">
            <a href="/index.php?q=pricing" class="hero-btn hero-btn-primary">
                <span class="mdi mdi-rocket-launch" aria-hidden="true"></span>
                Get Started
            </a>
            <a href="/index.php?q=sell" class="hero-btn hero-btn-secondary">
                <span class="mdi mdi-play-circle" aria-hidden="true"></span>
                How It Works
            </a>
        </div>
        <div class="hero-stats" aria-label="Platform statistics">
            <div class="hero-stat">
                <h4><span data-target="150" data-suffix="+">150+</span></h4>
                <p>Platforms</p>
            </div>
            <div class="hero-stat">
                <h4><span data-target="100" data-suffix="%">100%</span></h4>
                <p>Royalties</p>
            </div>
            <div class="hero-stat">
                <h4><span data-target="0" data-suffix="">₹0</span></h4>
                <p>Hidden Fees</p>
            </div>
            <div class="hero-stat">
                <h4><span data-target="24" data-suffix="/7">24/7</span></h4>
                <p>Support</p>
            </div>
        </div>
    </div>
</section>

<!-- ==================== DSP MARQUEE SECTION ==================== -->
<section class="dsp-marquee-section" aria-label="Supported streaming platforms">
    <div class="dsp-marquee-header">
        <p>Your music on every major platform</p>
    </div>

    <?php
    $row1 = [
        ['spotify',    'mdi-spotify',       'Spotify'],
        ['apple',      'mdi-apple',         'Apple Music'],
        ['youtube',    'mdi-youtube',       'YouTube Music'],
        ['amazon',     'mdi-amazon',        'Amazon Music'],
        ['tidal',      'mdi-music-circle',  'Tidal'],
        ['deezer',     'mdi-music-box',     'Deezer'],
        ['jiosaavn',   'mdi-music-note',    'JioSaavn'],
        ['gaana',      'mdi-headphones',    'Gaana'],
    ];
    $row2 = [
        ['tiktok',      'mdi-music-note-eighth', 'TikTok'],
        ['instagram',   'mdi-instagram',         'Instagram'],
        ['wynk',        'mdi-music-circle-outline','Wynk'],
        ['boomplay',    'mdi-play-circle',        'Boomplay'],
        ['facebook',    'mdi-facebook',           'Facebook'],
        ['shazam',      'mdi-shazam',             'Shazam'],
        ['napster',     'mdi-music-box-outline',  'Napster'],
        ['iheartradio', 'mdi-radio',              'iHeartRadio'],
    ];

    // Helper to render a doubled marquee row
    function renderMarqueeRow(array $dsps, string $rowClass): void {
        echo '<div class="marquee-row ' . htmlspecialchars($rowClass) . '">';
        echo '<div class="marquee-track" aria-hidden="true">';
        // Render twice for seamless loop
        for ($i = 0; $i < 2; $i++) {
            foreach ($dsps as [$cls, $icon, $name]) {
                echo '<span class="dsp-chip ' . htmlspecialchars($cls) . '">';
                echo '<span class="mdi ' . htmlspecialchars($icon) . '"></span>';
                echo htmlspecialchars($name);
                echo '</span>';
            }
        }
        echo '</div></div>';
    }
    renderMarqueeRow($row1, 'marquee-row-1');
    renderMarqueeRow($row2, 'marquee-row-2');
    ?>

    <div class="dsp-view-all">
        <a href="/index.php?q=stores">
            <span class="mdi mdi-store-outline" aria-hidden="true"></span>
            View all 150+ stores
            <span class="mdi mdi-arrow-right" aria-hidden="true"></span>
        </a>
    </div>
</section>

<!-- ==================== HOW IT WORKS ==================== -->
<section class="how-section" aria-labelledby="how-heading">
    <div class="section-header">
        <div class="section-badge" aria-hidden="true">
            <span class="mdi mdi-map-marker-path"></span>
            Simple Process
        </div>
        <h2 id="how-heading">Release Your Music in <span class="gradient-text">3 Easy Steps</span></h2>
        <p>From upload to worldwide release — we handle everything</p>
    </div>
    <div class="how-grid">
        <div class="how-step animate-on-scroll stagger-1">
            <div class="how-step-num" aria-hidden="true">1</div>
            <div class="how-step-icon" aria-hidden="true"><span class="mdi mdi-cloud-upload-outline"></span></div>
            <h3>Upload Your Music</h3>
            <p>Create a free account and upload your tracks in studio quality. Add artwork, metadata, and pick your release date — takes minutes.</p>
        </div>
        <div class="how-step animate-on-scroll stagger-2">
            <div class="how-step-num" aria-hidden="true">2</div>
            <div class="how-step-icon" aria-hidden="true"><span class="mdi mdi-earth"></span></div>
            <h3>We Distribute Worldwide</h3>
            <p>We deliver your release to Spotify, Apple Music, JioSaavn, YouTube Music and 150+ platforms. Most releases go live within 24–72 hours.</p>
        </div>
        <div class="how-step animate-on-scroll stagger-3">
            <div class="how-step-num" aria-hidden="true">3</div>
            <div class="how-step-icon" aria-hidden="true"><span class="mdi mdi-cash-multiple"></span></div>
            <h3>You Get Paid</h3>
            <p>Track streams and earnings in real-time from your dashboard. Keep 100% of your royalties and withdraw anytime.</p>
        </div>
    </div>
</section>

<!-- ==================== FEATURES SECTION ==================== -->
<section class="features-section" aria-labelledby="features-heading">
    <div class="section-header">
        <div class="section-badge" aria-hidden="true">
            <span class="mdi mdi-star"></span>
            Premium Features
        </div>
        <h2 id="features-heading">Everything You Need to <span class="gradient-text">Succeed</span></h2>
        <p>Powerful tools and services designed for independent artists and labels</p>
    </div>
    <div class="features-grid">
        <div class="feature-card animate-on-scroll stagger-1">
            <div class="feature-icon" aria-hidden="true">
                <span class="mdi mdi-cloud-upload"></span>
            </div>
            <h3>Unlimited Releases</h3>
            <p>Release as much music as you want. No limits, no hidden fees. Upload singles, albums, and EPs anytime.</p>
        </div>
        <div class="feature-card animate-on-scroll stagger-2">
            <div class="feature-icon" aria-hidden="true">
                <span class="mdi mdi-currency-usd"></span>
            </div>
            <h3>Keep 100% Royalties</h3>
            <p>You worked hard for your music. Keep every penny you earn from streams, downloads, and publishing.</p>
        </div>
        <div class="feature-card animate-on-scroll stagger-3">
            <div class="feature-icon" aria-hidden="true">
                <span class="mdi mdi-chart-line"></span>
            </div>
            <h3>Detailed Analytics</h3>
            <p>Track your performance across all platforms. Know where your fans are and how they discover your music.</p>
        </div>
        <div class="feature-card animate-on-scroll stagger-4">
            <div class="feature-icon" aria-hidden="true">
                <span class="mdi mdi-calendar-clock"></span>
            </div>
            <h3>Scheduled Releases</h3>
            <p>Plan your release strategy. Schedule your music to go live on specific dates across all platforms.</p>
        </div>
        <div class="feature-card animate-on-scroll stagger-5">
            <div class="feature-icon" aria-hidden="true">
                <span class="mdi mdi-file-music"></span>
            </div>
            <h3>Music Publishing</h3>
            <p>Register your songs and collect publishing royalties from radio, TV, films, and streaming worldwide.</p>
        </div>
        <div class="feature-card animate-on-scroll stagger-6">
            <div class="feature-icon" aria-hidden="true">
                <span class="mdi mdi-account-group"></span>
            </div>
            <h3>Collaborator Splits</h3>
            <p>Split royalties automatically with producers, songwriters, and band members. No manual calculations.</p>
        </div>
    </div>
</section>

<!-- ==================== TESTIMONIALS SECTION ==================== -->
<section class="testimonials-section" aria-labelledby="testimonials-heading">
    <div class="section-header">
        <div class="section-badge" aria-hidden="true">
            <span class="mdi mdi-account-heart"></span>
            Why HiTune
        </div>
        <h2 id="testimonials-heading">Built for <span class="gradient-text">Independent Artists</span></h2>
        <p>Honest pricing, real delivery tracking, and support that actually answers.</p>
    </div>
    <div class="testimonials-grid">
        <article class="testimonial-card animate-on-scroll">
            <div class="testimonial-stars" aria-label="" style="color:#00d4aa">
                <span class="mdi mdi-cash-check" aria-hidden="true" style="font-size:28px"></span>
            </div>
            <p class="testimonial-quote" style="font-weight:700;color:#fff">100% Royalties, Always</p>
            <p class="testimonial-quote">Your streams earn your money — we never take a cut. What the stores pay out is what lands in your dashboard.</p>
        </article>
        <article class="testimonial-card animate-on-scroll">
            <div class="testimonial-stars" aria-label="" style="color:#00d4aa">
                <span class="mdi mdi-map-check" aria-hidden="true" style="font-size:28px"></span>
            </div>
            <p class="testimonial-quote" style="font-weight:700;color:#fff">Track Every Store</p>
            <p class="testimonial-quote">Watch your release move from submitted to live — with a per-platform status and a direct link once it is up on each store.</p>
        </article>
        <article class="testimonial-card animate-on-scroll">
            <div class="testimonial-stars" aria-label="" style="color:#00d4aa">
                <span class="mdi mdi-face-agent" aria-hidden="true" style="font-size:28px"></span>
            </div>
            <p class="testimonial-quote" style="font-weight:700;color:#fff">Humans, Not Bots</p>
            <p class="testimonial-quote">Questions about your release? Our team replies personally — no ticket black holes, no auto-replies.</p>
        </article>
    </div>
</section>

<!-- ==================== PRICING PREVIEW SECTION ==================== -->
<section class="pricing-preview-section" aria-labelledby="pricing-heading">
    <div class="section-header">
        <div class="section-badge" aria-hidden="true">
            <span class="mdi mdi-tag-multiple"></span>
            Simple Pricing
        </div>
        <h2 id="pricing-heading">Plans for Every <span class="gradient-text">Artist</span></h2>
        <p>Start free, scale as you grow. No hidden fees, ever.</p>
    </div>
    <div class="pricing-preview-grid">
        <!-- Artist -->
        <div class="pricing-card animate-on-scroll">
            <div class="pricing-plan-name">Artist</div>
            <div class="pricing-price">
                <span class="amount">₹699</span><span class="period">/year</span>
            </div>
            <ul class="pricing-features">
                <li><span class="mdi mdi-check-circle" aria-hidden="true"></span> 50+ Streaming Platforms</li>
                <li><span class="mdi mdi-check-circle" aria-hidden="true"></span> 1 Artist Profile</li>
                <li><span class="mdi mdi-check-circle" aria-hidden="true"></span> 100% Royalties</li>
                <li><span class="mdi mdi-check-circle" aria-hidden="true"></span> Basic Analytics</li>
            </ul>
            <div class="pricing-cta-wrap">
                <a href="/index.php?q=pricing" class="hero-btn hero-btn-secondary" style="justify-content:center;width:100%;box-sizing:border-box;">Get Started</a>
            </div>
        </div>
        <!-- Artist Pro (Recommended) -->
        <div class="pricing-card recommended animate-on-scroll">
            <div class="recommended-badge">
                <span class="mdi mdi-star" aria-hidden="true"></span>
                Recommended
            </div>
            <div class="pricing-plan-name">Artist Pro</div>
            <div class="pricing-price">
                <span class="amount">₹1,499</span><span class="period">/year</span>
            </div>
            <ul class="pricing-features">
                <li><span class="mdi mdi-check-circle" aria-hidden="true"></span> 100+ Streaming Platforms</li>
                <li><span class="mdi mdi-check-circle" aria-hidden="true"></span> 3 Artist Profiles</li>
                <li><span class="mdi mdi-check-circle" aria-hidden="true"></span> YouTube Content ID</li>
                <li><span class="mdi mdi-check-circle" aria-hidden="true"></span> Custom Label Name</li>
            </ul>
            <div class="pricing-cta-wrap">
                <a href="/index.php?q=pricing" class="hero-btn hero-btn-primary" style="justify-content:center;width:100%;box-sizing:border-box;">Get Started</a>
            </div>
        </div>
        <!-- Professional -->
        <div class="pricing-card animate-on-scroll">
            <div class="pricing-plan-name">Label</div>
            <div class="pricing-price">
                <span class="amount">₹2,499</span><span class="period">/year</span>
            </div>
            <ul class="pricing-features">
                <li><span class="mdi mdi-check-circle" aria-hidden="true"></span> 150+ Streaming Platforms</li>
                <li><span class="mdi mdi-check-circle" aria-hidden="true"></span> Unlimited Artist Profiles</li>
                <li><span class="mdi mdi-check-circle" aria-hidden="true"></span> Scheduled Releases</li>
                <li><span class="mdi mdi-check-circle" aria-hidden="true"></span> Priority Support</li>
            </ul>
            <div class="pricing-cta-wrap">
                <a href="/index.php?q=pricing" class="hero-btn hero-btn-secondary" style="justify-content:center;width:100%;box-sizing:border-box;">Get Started</a>
            </div>
        </div>
    </div>
    <div class="pricing-view-all">
        <a href="/index.php?q=pricing">
            <span class="mdi mdi-tag-multiple-outline" aria-hidden="true"></span>
            View Full Pricing
            <span class="mdi mdi-arrow-right" aria-hidden="true"></span>
        </a>
    </div>
</section>

<!-- ==================== STATS SECTION ==================== -->
<section class="stats-section" aria-labelledby="stats-heading">
    <h2 id="stats-heading" class="sr-only">Platform Statistics</h2>
    <div class="stats-grid">
        <div class="stat-card animate-on-scroll">
            <span class="stat-number" data-target="150" data-suffix="+">150+</span>
            <div class="stat-label">Streaming Platforms</div>
        </div>
        <div class="stat-card animate-on-scroll">
            <span class="stat-number" data-target="100" data-suffix="%">100%</span>
            <div class="stat-label">Royalties Kept</div>
        </div>
        <div class="stat-card animate-on-scroll">
            <span class="stat-number" data-target="24" data-suffix="/7">24/7</span>
            <div class="stat-label">Human Support</div>
        </div>
        <div class="stat-card animate-on-scroll">
            <span class="stat-number" data-target="0" data-suffix="">₹0</span>
            <div class="stat-label">Hidden Fees</div>
        </div>
    </div>
</section>

<!-- ==================== FAQ SECTION ==================== -->
<section class="faq-home-section" aria-labelledby="faq-home-heading">
    <div class="section-header">
        <div class="section-badge" aria-hidden="true">
            <span class="mdi mdi-frequently-asked-questions"></span>
            FAQ
        </div>
        <h2 id="faq-home-heading">Frequently Asked <span class="gradient-text">Questions</span></h2>
        <p>Everything you need to know about distributing with HiTune</p>
    </div>
    <div class="faq-home-list">
        <div class="faq-home-item">
            <button class="faq-home-q" type="button" aria-expanded="false">
                How long does it take for my music to go live?
                <span class="mdi mdi-plus" aria-hidden="true"></span>
            </button>
            <div class="faq-home-a">
                <p>Most releases go live within 24–72 hours. Spotify and JioSaavn are usually fastest; Apple Music can take up to 5 days. We recommend scheduling your release at least 2 weeks ahead for the best playlist pitching window.</p>
            </div>
        </div>
        <div class="faq-home-item">
            <button class="faq-home-q" type="button" aria-expanded="false">
                Do I really keep 100% of my royalties?
                <span class="mdi mdi-plus" aria-hidden="true"></span>
            </button>
            <div class="faq-home-a">
                <p>Yes — 100% on all paid plans. We never take a cut of your earnings. You pay a flat plan fee and everything your music earns is yours, paid out monthly to your bank account.</p>
            </div>
        </div>
        <div class="faq-home-item">
            <button class="faq-home-q" type="button" aria-expanded="false">
                Which platforms will my music appear on?
                <span class="mdi mdi-plus" aria-hidden="true"></span>
            </button>
            <div class="faq-home-a">
                <p>Spotify, Apple Music, YouTube Music, JioSaavn, Amazon Music, Tidal, Deezer, Gaana, Wynk, Boomplay, TikTok, Instagram, Shazam and 150+ more stores and streaming services worldwide.</p>
            </div>
        </div>
        <div class="faq-home-item">
            <button class="faq-home-q" type="button" aria-expanded="false">
                Can I switch from another distributor to HiTune?
                <span class="mdi mdi-plus" aria-hidden="true"></span>
            </button>
            <div class="faq-home-a">
                <p>Absolutely. Upload your releases with the same ISRC codes and your stream counts, playlists and followers carry over. Our support team helps with the migration for free.</p>
            </div>
        </div>
        <div class="faq-home-item">
            <button class="faq-home-q" type="button" aria-expanded="false">
                What audio formats do you accept?
                <span class="mdi mdi-plus" aria-hidden="true"></span>
            </button>
            <div class="faq-home-a">
                <p>WAV, FLAC, AIFF and high-quality MP3 (320kbps). For the best store quality we recommend 16-bit or 24-bit WAV at 44.1kHz. Artwork should be 3000×3000px JPG or PNG.</p>
            </div>
        </div>
        <div class="faq-home-item">
            <button class="faq-home-q" type="button" aria-expanded="false">
                How and when do I get paid?
                <span class="mdi mdi-plus" aria-hidden="true"></span>
            </button>
            <div class="faq-home-a">
                <p>Royalties are reported monthly as platforms pay us (typically 1–2 months after streams). Withdraw anytime via bank transfer or UPI once your balance crosses the minimum threshold — no hidden deductions.</p>
            </div>
        </div>
    </div>
</section>

<!-- ==================== FINAL CTA SECTION ==================== -->
<section class="cta-section" aria-labelledby="cta-heading">
    <div class="cta-container">
        <div class="cta-card">
            <h2 id="cta-heading">Ready to Release<br><span class="gradient-text">Your Music?</span></h2>
            <p>Upload once — we deliver your music to Spotify, Apple Music, JioSaavn and 150+ platforms. You keep 100% of the royalties.</p>
            <a href="/index.php?q=pricing" class="cta-btn">
                <span class="mdi mdi-rocket-launch" aria-hidden="true"></span>
                Start Distributing Now
            </a>
        </div>
    </div>
</section>

<script>
(function () {
    // Intersection Observer for scroll animations
    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

    document.querySelectorAll('.animate-on-scroll').forEach(function (el) {
        observer.observe(el);
    });

    // FAQ accordion
    document.querySelectorAll('.faq-home-q').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var item = btn.closest('.faq-home-item');
            var wasOpen = item.classList.contains('open');
            document.querySelectorAll('.faq-home-item.open').forEach(function (el) {
                el.classList.remove('open');
                el.querySelector('.faq-home-q').setAttribute('aria-expanded', 'false');
            });
            if (!wasOpen) {
                item.classList.add('open');
                btn.setAttribute('aria-expanded', 'true');
            }
        });
    });
})();
</script>

<?php include __DIR__ . '/../includes/footer_premium.php'; ?>
