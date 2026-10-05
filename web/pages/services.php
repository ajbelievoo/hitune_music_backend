<?php
/**
 * HiTune Music Distribution - Services Page
 */
$pageTitle = 'Services - HiTune Music Distribution';
include __DIR__ . '/../includes/header_premium.php';
?>
<style>
    .services-hero {
        padding: 120px 30px 80px;
        text-align: center;
        background: radial-gradient(ellipse at center, #1a1a2e 0%, #0a0a0a 100%);
    }
    .services-hero h1 {
        font-size: clamp(36px, 6vw, 64px);
        font-weight: 800;
        margin-bottom: 20px;
    }
    .services-hero p {
        font-size: 20px;
        color: rgba(255, 255, 255, 0.6);
        max-width: 700px;
        margin: 0 auto;
    }
    .services-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 60px 30px;
    }
    .service-section {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 60px;
        align-items: center;
        margin-bottom: 100px;
    }
    .service-section:nth-child(even) {
        direction: rtl;
    }
    .service-section:nth-child(even) > * {
        direction: ltr;
    }
    .service-content h2 {
        font-size: 36px;
        font-weight: 800;
        margin-bottom: 20px;
    }
    .service-content h2 span {
        color: #00b7ff;
    }
    .service-content p {
        font-size: 16px;
        color: rgba(255, 255, 255, 0.7);
        line-height: 1.8;
        margin-bottom: 25px;
    }
    .service-btn {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 14px 30px;
        background: linear-gradient(135deg, #00b7ff, #8b5cf6);
        color: #fff;
        text-decoration: none;
        border-radius: 30px;
        font-weight: 600;
        transition: all 0.3s;
    }
    .service-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 40px rgba(0, 183, 255, 0.3);
    }
    .service-visual {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 24px;
        padding: 40px;
        text-align: center;
    }
    .service-visual i {
        font-size: 120px;
        color: rgba(0, 183, 255, 0.5);
    }
    .service-visual img {
        max-width: 100%;
        border-radius: 16px;
    }
    /* Phone Mockup */
    .phone-mockup {
        max-width: 300px;
        margin: 0 auto;
        background: #1a1a2e;
        border-radius: 40px;
        padding: 20px;
        border: 8px solid #333;
    }
    .phone-screen {
        background: linear-gradient(180deg, #16213e, #0a0a0a);
        border-radius: 30px;
        padding: 30px;
        min-height: 500px;
    }
    .phone-header {
        text-align: center;
        margin-bottom: 30px;
    }
    .phone-header h4 {
        font-size: 14px;
        opacity: 0.7;
    }
    .release-tracker {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    .tracker-item {
        display: flex;
        align-items: center;
        gap: 15px;
        background: rgba(255, 255, 255, 0.05);
        padding: 15px;
        border-radius: 12px;
    }
    .tracker-status {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
    }
    .status-green {
        background: #00c853;
    }
    .status-yellow {
        background: #ffc107;
    }
    @media (max-width: 768px) {
        .service-section {
            grid-template-columns: 1fr;
            gap: 40px;
        }
        .service-section:nth-child(even) {
            direction: ltr;
        }
    }
</style>

<section class="services-hero">
    <h1>Artist <span class="gradient-text">Services</span></h1>
    <p>Whichever stage you're at in your music career, we've got the tools you need to better connect with new listeners and existing fans.</p>
</section>

<div class="services-container">
    <!-- Release Tracker -->
    <div class="service-section">
        <div class="service-content">
            <h2>Track Your Release Status with <span>HiTune</span> Release Tracker</h2>
            <p>You pour your blood, sweat, and tears into writing and recording a song. You upload it to streaming services. You wait for it to appear online with little to no feedback... until now.</p>
            <p>Introducing HiTune Release Tracker. Know exactly where your music is in the distribution process, from submission to going live on every platform.</p>
            <a href="/index.php?q=dashboard" class="service-btn">
                <i class="mdi mdi-arrow-right"></i>
                Here's How It Works
            </a>
        </div>
        <div class="service-visual">
            <div class="phone-mockup">
                <div class="phone-screen">
                    <div class="phone-header">
                        <h4>Release Tracker</h4>
                    </div>
                    <div class="release-tracker">
                        <div class="tracker-item">
                            <div class="tracker-status status-green"><i class="mdi mdi-check"></i></div>
                            <div>
                                <strong>Submitted</strong>
                                <div style="font-size: 12px; opacity: 0.6;">Aug 15, 2024</div>
                            </div>
                        </div>
                        <div class="tracker-item">
                            <div class="tracker-status status-green"><i class="mdi mdi-check"></i></div>
                            <div>
                                <strong>In Review</strong>
                                <div style="font-size: 12px; opacity: 0.6;">Aug 16, 2024</div>
                            </div>
                        </div>
                        <div class="tracker-item">
                            <div class="tracker-status status-yellow"><i class="mdi mdi-clock"></i></div>
                            <div>
                                <strong>Going Live</strong>
                                <div style="font-size: 12px; opacity: 0.6;">Pending...</div>
                            </div>
                        </div>
                    </div>
                    <div style="text-align: center; margin-top: 30px; padding: 20px; background: rgba(0,200,83,0.1); border-radius: 12px;">
                        <div style="font-size: 12px; opacity: 0.7; margin-bottom: 5px;">Your music is</div>
                        <strong style="color: #00c853;">LIVE!</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mastering -->
    <div class="service-section">
        <div class="service-content">
            <h2>Master Your Tracks <span>Instantly</span></h2>
            <p>HiTune Mastering offers AI-driven, professional sounding tracks at just $5 USD per track. Get radio-ready sound without expensive studio time.</p>
            <p>Upload your mix, choose your style, and get mastered audio in minutes. Perfect for independent artists who want professional quality on a budget.</p>
            <a href="/services" class="service-btn">
                <i class="mdi mdi-play"></i>
                Get Started
            </a>
        </div>
        <div class="service-visual">
            <i class="mdi mdi-waveform"></i>
            <div style="margin-top: 20px;">
                <div style="display: flex; align-items: center; justify-content: center; gap: 20px;">
                    <div style="text-align: center;">
                        <div style="font-size: 12px; opacity: 0.6; margin-bottom: 10px;">Before</div>
                        <div style="width: 100px; height: 60px; background: linear-gradient(90deg, #333 20%, #444 40%, #333 60%, #444 80%); border-radius: 4px;"></div>
                    </div>
                    <i class="mdi mdi-arrow-right" style="font-size: 30px; color: #00c853;"></i>
                    <div style="text-align: center;">
                        <div style="font-size: 12px; opacity: 0.6; margin-bottom: 10px;">After</div>
                        <div style="width: 100px; height: 60px; background: linear-gradient(90deg, #00b7ff 20%, #8b5cf6 40%, #00b7ff 60%, #8b5cf6 80%); border-radius: 4px;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Promotional Tools -->
    <div class="service-section">
        <div class="service-content">
            <h2>Promote Your <span>Music</span></h2>
            <p>Get your music in front of the right audience with our promotional tools. From playlist pitching to social media assets, we've got you covered.</p>
            <p>Connect with curators, create shareable links, and track your promotion performance all in one place.</p>
            <a href="/services" class="service-btn">
                <i class="mdi mdi-bullhorn"></i>
                Learn More
            </a>
        </div>
        <div class="service-visual">
            <i class="mdi mdi-trending-up"></i>
            <div style="margin-top: 30px; text-align: left;">
                <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                    <div style="width: 50px; height: 50px; background: rgba(0,200,83,0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                        <i class="mdi mdi-playlist-music" style="color: #00c853; font-size: 24px;"></i>
                    </div>
                    <div>
                        <div style="font-weight: 600;">Playlist Pitching</div>
                        <div style="font-size: 13px; opacity: 0.6;">Submit to 1000+ curators</div>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                    <div style="width: 50px; height: 50px; background: rgba(0,183,255,0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                        <i class="mdi mdi-share-variant" style="color: #00b7ff; font-size: 24px;"></i>
                    </div>
                    <div>
                        <div style="font-weight: 600;">Smart Links</div>
                        <div style="font-size: 13px; opacity: 0.6;">One link for all platforms</div>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="width: 50px; height: 50px; background: rgba(255,193,7,0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                        <i class="mdi mdi-image" style="color: #ffc107; font-size: 24px;"></i>
                    </div>
                    <div>
                        <div style="font-weight: 600;">Social Assets</div>
                        <div style="font-size: 13px; opacity: 0.6;">Auto-generated artwork</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer_premium.php'; ?>
