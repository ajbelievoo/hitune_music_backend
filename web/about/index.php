<?php
/**
 * HiTune Music Distribution - About Us Page
 * Direct access version
 */
$pageTitle = 'About Us - HiTune Music Distribution';
include __DIR__ . '/../includes/header_premium.php';
?>
<style>
    .about-hero {
        padding: 120px 30px 80px;
        text-align: center;
        background: radial-gradient(ellipse at center, #1a1a2e 0%, #0a0a0a 100%);
    }
    .about-hero h1 {
        font-size: clamp(36px, 6vw, 64px);
        font-weight: 800;
        margin-bottom: 20px;
    }
    .about-hero p {
        font-size: 20px;
        color: rgba(255, 255, 255, 0.6);
        max-width: 700px;
        margin: 0 auto;
    }
    .about-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 60px 30px;
    }
    .about-section {
        margin-bottom: 80px;
    }
    .about-section h2 {
        font-size: 36px;
        font-weight: 700;
        margin-bottom: 25px;
        text-align: center;
    }
    .about-section p {
        font-size: 16px;
        color: rgba(255, 255, 255, 0.7);
        line-height: 1.8;
        margin-bottom: 20px;
        text-align: center;
        max-width: 800px;
        margin-left: auto;
        margin-right: auto;
    }
    .mission-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 30px;
        margin-top: 60px;
    }
    .mission-card {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 20px;
        padding: 40px 30px;
        text-align: center;
        transition: all 0.3s;
    }
    .mission-card:hover {
        transform: translateY(-10px);
        border-color: rgba(0, 183, 255, 0.3);
        box-shadow: 0 20px 40px rgba(0, 183, 255, 0.1);
    }
    .mission-card i {
        font-size: 48px;
        color: #00b7ff;
        margin-bottom: 20px;
    }
    .mission-card h3 {
        font-size: 22px;
        font-weight: 600;
        margin-bottom: 15px;
    }
    .mission-card p {
        font-size: 15px;
        color: rgba(255, 255, 255, 0.6);
        line-height: 1.6;
    }
    .stats-section {
        background: rgba(255, 255, 255, 0.02);
        border-radius: 30px;
        padding: 60px 40px;
        margin: 60px 0;
    }
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 40px;
        text-align: center;
    }
    .stat-item h3 {
        font-size: 48px;
        font-weight: 800;
        color: #00b7ff;
        margin-bottom: 10px;
    }
    .stat-item p {
        font-size: 16px;
        color: rgba(255, 255, 255, 0.6);
    }
    .team-section {
        margin-top: 80px;
    }
    .team-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 30px;
        margin-top: 50px;
    }
    .team-member {
        text-align: center;
    }
    .team-avatar {
        width: 120px;
        height: 120px;
        background: linear-gradient(135deg, #00b7ff, #8b5cf6);
        border-radius: 50%;
        margin: 0 auto 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 48px;
        color: #fff;
    }
    .team-member h4 {
        font-size: 20px;
        font-weight: 600;
        margin-bottom: 5px;
    }
    .team-member p {
        font-size: 14px;
        color: rgba(255, 255, 255, 0.5);
    }
    @media (max-width: 968px) {
        .mission-grid {
            grid-template-columns: 1fr;
        }
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        .team-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    @media (max-width: 480px) {
        .stats-grid, .team-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<section class="about-hero">
    <h1>About <span class="gradient-text">HiTune</span></h1>
    <p>Empowering independent artists worldwide to share their music and keep 100% of their royalties.</p>
</section>

<div class="about-container">
    <!-- Our Story -->
    <div class="about-section">
        <h2>Our Story</h2>
        <p>HiTune Music Distribution was founded in 2020 with a simple mission: to democratize music distribution for independent artists. We believe every musician deserves access to global audiences without giving up their creative freedom or royalties.</p>
        <p>What started as a small platform has grown into a global music distribution network serving thousands of artists across 150+ countries. From bedroom producers to established indie bands, we help musicians at every stage of their career reach listeners on Spotify, Apple Music, YouTube Music, and over 150 streaming platforms worldwide.</p>
    </div>

    <!-- Mission & Values -->
    <div class="about-section">
        <h2>Our Mission & Values</h2>
        <div class="mission-grid">
            <div class="mission-card">
                <i class="mdi mdi-heart"></i>
                <h3>Artist First</h3>
                <p>We put artists at the center of everything we do. Your success is our success.</p>
            </div>
            <div class="mission-card">
                <i class="mdi mdi-currency-usd"></i>
                <h3>100% Royalties</h3>
                <p>Keep every penny you earn. We don't take a cut of your streaming revenue.</p>
            </div>
            <div class="mission-card">
                <i class="mdi mdi-earth"></i>
                <h3>Global Reach</h3>
                <p>Your music everywhere. Distribution to 150+ platforms in 200+ countries.</p>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-section">
        <div class="stats-grid">
            <div class="stat-item">
                <h3>50K+</h3>
                <p>Artists Worldwide</p>
            </div>
            <div class="stat-item">
                <h3>150+</h3>
                <p>Streaming Platforms</p>
            </div>
            <div class="stat-item">
                <h3>1M+</h3>
                <p>Tracks Distributed</p>
            </div>
            <div class="stat-item">
                <h3>200+</h3>
                <p>Countries Reached</p>
            </div>
        </div>
    </div>

    <!-- Team -->
    <div class="about-section team-section">
        <h2>Meet Our Team</h2>
        <p>The passionate people behind HiTune working to empower artists every day.</p>
        <div class="team-grid">
            <div class="team-member">
                <div class="team-avatar">
                    <i class="mdi mdi-account"></i>
                </div>
                <h4>Founder & CEO</h4>
                <p>Leadership Team</p>
            </div>
            <div class="team-member">
                <div class="team-avatar">
                    <i class="mdi mdi-headphones"></i>
                </div>
                <h4>Head of A&R</h4>
                <p>Music Team</p>
            </div>
            <div class="team-member">
                <div class="team-avatar">
                    <i class="mdi mdi-code-braces"></i>
                </div>
                <h4>CTO</h4>
                <p>Engineering Team</p>
            </div>
            <div class="team-member">
                <div class="team-avatar">
                    <i class="mdi mdi-bullhorn"></i>
                </div>
                <h4>Marketing Lead</h4>
                <p>Growth Team</p>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer_premium.php'; ?>
