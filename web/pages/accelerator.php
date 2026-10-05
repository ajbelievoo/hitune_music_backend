<?php
/**
 * HiTune Music Distribution - Accelerator Page
 */
$pageTitle = 'Accelerator - HiTune Music Distribution';
include __DIR__ . '/../includes/header_premium.php';
?>
<style>
    .accelerator-hero { padding: 120px 30px 80px; text-align: center; background: radial-gradient(ellipse at center, #1a1a2e 0%, #0a0a0a 100%); }
    .accelerator-hero h1 { font-size: clamp(32px, 5vw, 52px); font-weight: 800; margin-bottom: 20px; }
    .accelerator-hero h1 span { color: #00b7ff; }
    .accelerator-hero > p { font-size: 18px; color: rgba(255, 255, 255, 0.7); max-width: 800px; margin: 0 auto 50px; }
    .stats-banner { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 40px; max-width: 900px; margin: 60px auto; }
    .stat-box { background: linear-gradient(135deg, rgba(0,183,255,0.2), rgba(139,92,246,0.1)); border-radius: 20px; padding: 30px; text-align: center; }
    .stat-box h3 { font-size: 42px; font-weight: 800; color: #00b7ff; margin-bottom: 5px; }
    .stat-box p { font-size: 14px; color: rgba(255, 255, 255, 0.7); }
    .features-grid { max-width: 1200px; margin: 0 auto; padding: 80px 30px; display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 30px; }
    .feature-card { background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 20px; padding: 40px; }
    .feature-card i { font-size: 50px; color: #00b7ff; margin-bottom: 20px; }
    .feature-card h3 { font-size: 24px; font-weight: 700; margin-bottom: 15px; }
    .feature-card p { color: rgba(255, 255, 255, 0.7); line-height: 1.7; }
    .cta-section { padding: 100px 30px; text-align: center; background: linear-gradient(180deg, #0a0a0a 0%, #1a1a2e 100%); }
    .cta-section h2 { font-size: 36px; font-weight: 800; margin-bottom: 20px; }
    .cta-section .btn { padding: 16px 40px; font-size: 16px; }
</style>

<section class="accelerator-hero">
    <h1>HiTune <span class="gradient-text">Accelerator</span>:<br>How Artists Grow</h1>
    <p>Launched in 2023, HiTune Accelerator is the powerhouse platform that develops independent artists. Even in today's crowded musical landscape, this report shows how Accelerator transforms artists careers through global fan discovery, catalog optimization, and sustaining artist momentum.</p>
    <div class="stats-banner">
        <div class="stat-box"><h3>500K</h3><p>Artists Enrolled</p></div>
        <div class="stat-box"><h3>15B</h3><p>Track Discoveries</p></div>
        <div class="stat-box"><h3>250%</h3><p>Average Growth</p></div>
    </div>
</section>

<section class="features-grid">
    <div class="feature-card">
        <i class="mdi mdi-trending-up"></i>
        <h3>Key Insights</h3>
        <p>Get detailed analytics about your music performance. Understand your audience demographics, geographic reach, and trending metrics to optimize your releases.</p>
    </div>
    <div class="feature-card">
        <i class="mdi mdi-book-open-variant"></i>
        <h3>Case Studies</h3>
        <p>Learn from successful independent artists who have grown their careers using HiTune. Discover their strategies, marketing approaches, and growth tactics.</p>
    </div>
    <div class="feature-card">
        <i class="mdi mdi-bullhorn"></i>
        <h3>Promotional Tools</h3>
        <p>Access advanced promotional features including playlist pitching, social media assets, smart links, and pre-save campaigns to maximize your reach.</p>
    </div>
</section>

<section class="cta-section">
    <h2>Ready to Accelerate Your Career?</h2>
    <p style="color: rgba(255,255,255,0.7); margin-bottom: 30px;">Join thousands of artists growing with HiTune Accelerator</p>
    <a href="/index.php?q=pricing" class="btn btn-primary">Learn More About Accelerator</a>
</section>

<?php include __DIR__ . '/../includes/footer_premium.php'; ?>
