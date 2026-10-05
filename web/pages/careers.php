<?php
/**
 * HiTune Music Distribution - Careers Page
 */
$pageTitle = 'Careers - HiTune Music Distribution';
include __DIR__ . '/../includes/header_premium.php';
?>
<style>
    .careers-hero {
        padding: 120px 30px 80px;
        text-align: center;
        background: radial-gradient(ellipse at center, #1a1a2e 0%, #0a0a0a 100%);
    }
    .careers-hero h1 {
        font-size: clamp(36px, 6vw, 64px);
        font-weight: 800;
        margin-bottom: 20px;
    }
    .careers-hero p {
        font-size: 20px;
        color: rgba(255, 255, 255, 0.6);
        max-width: 700px;
        margin: 0 auto;
    }
    .careers-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 60px 30px;
    }
    .why-join {
        margin-bottom: 80px;
    }
    .why-join h2 {
        font-size: 36px;
        font-weight: 700;
        margin-bottom: 20px;
        text-align: center;
    }
    .why-join > p {
        font-size: 16px;
        color: rgba(255, 255, 255, 0.7);
        line-height: 1.8;
        text-align: center;
        max-width: 800px;
        margin: 0 auto 50px;
    }
    .benefits-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 30px;
    }
    .benefit-card {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 20px;
        padding: 40px 30px;
        text-align: center;
        transition: all 0.3s;
    }
    .benefit-card:hover {
        transform: translateY(-10px);
        border-color: rgba(0, 183, 255, 0.3);
        box-shadow: 0 20px 40px rgba(0, 183, 255, 0.1);
    }
    .benefit-card i {
        font-size: 48px;
        color: #00b7ff;
        margin-bottom: 20px;
    }
    .benefit-card h3 {
        font-size: 20px;
        font-weight: 600;
        margin-bottom: 15px;
    }
    .benefit-card p {
        font-size: 15px;
        color: rgba(255, 255, 255, 0.6);
        line-height: 1.6;
    }
    .open-positions {
        margin-top: 80px;
    }
    .open-positions h2 {
        font-size: 36px;
        font-weight: 700;
        margin-bottom: 40px;
        text-align: center;
    }
    .job-list {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    .job-card {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 16px;
        padding: 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: all 0.3s;
    }
    .job-card:hover {
        border-color: rgba(0, 183, 255, 0.3);
        transform: translateX(10px);
    }
    .job-info h3 {
        font-size: 22px;
        font-weight: 600;
        margin-bottom: 10px;
    }
    .job-meta {
        display: flex;
        gap: 20px;
        font-size: 14px;
        color: rgba(255, 255, 255, 0.5);
    }
    .job-meta span {
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .apply-btn {
        padding: 12px 30px;
        background: linear-gradient(135deg, #00b7ff, #8b5cf6);
        color: #fff;
        text-decoration: none;
        border-radius: 30px;
        font-weight: 600;
        transition: all 0.3s;
    }
    .apply-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 40px rgba(0, 183, 255, 0.3);
    }
    .culture-section {
        background: rgba(255, 255, 255, 0.02);
        border-radius: 30px;
        padding: 60px 40px;
        margin: 80px 0;
        text-align: center;
    }
    .culture-section h2 {
        font-size: 36px;
        font-weight: 700;
        margin-bottom: 30px;
    }
    .culture-section p {
        font-size: 18px;
        color: rgba(255, 255, 255, 0.7);
        line-height: 1.8;
        max-width: 900px;
        margin: 0 auto;
    }
    .no-jobs {
        text-align: center;
        padding: 60px;
        background: rgba(255, 255, 255, 0.02);
        border-radius: 20px;
    }
    .no-jobs i {
        font-size: 64px;
        color: rgba(0, 183, 255, 0.5);
        margin-bottom: 20px;
    }
    .no-jobs h3 {
        font-size: 24px;
        margin-bottom: 10px;
    }
    .no-jobs p {
        color: rgba(255, 255, 255, 0.6);
    }
    @media (max-width: 768px) {
        .benefits-grid {
            grid-template-columns: 1fr;
        }
        .job-card {
            flex-direction: column;
            gap: 20px;
            text-align: center;
        }
        .job-meta {
            justify-content: center;
        }
    }
</style>

<section class="careers-hero">
    <h1>Join Our <span class="gradient-text">Team</span></h1>
    <p>Help us empower independent artists worldwide. Be part of the music revolution.</p>
</section>

<div class="careers-container">
    <!-- Why Join -->
    <div class="why-join">
        <h2>Why Join HiTune?</h2>
        <p>We're building the future of music distribution. Join a passionate team that believes in the power of independent artists and the democratization of the music industry.</p>
        <div class="benefits-grid">
            <div class="benefit-card">
                <i class="mdi mdi-music"></i>
                <h3>Music First</h3>
                <p>Work with music and artists every day. Your passion becomes your profession.</p>
            </div>
            <div class="benefit-card">
                <i class="mdi mdi-remote"></i>
                <h3>Remote Friendly</h3>
                <p>Work from anywhere in the world. We believe in flexibility and work-life balance.</p>
            </div>
            <div class="benefit-card">
                <i class="mdi mdi-cash-multiple"></i>
                <h3>Competitive Pay</h3>
                <p>Great salary, equity options, and benefits that take care of you and your family.</p>
            </div>
            <div class="benefit-card">
                <i class="mdi mdi-school"></i>
                <h3>Learning Budget</h3>
                <p>Annual budget for courses, conferences, and anything that helps you grow.</p>
            </div>
            <div class="benefit-card">
                <i class="mdi mdi-heart-pulse"></i>
                <h3>Health & Wellness</h3>
                <p>Comprehensive health coverage and mental wellness support for all team members.</p>
            </div>
            <div class="benefit-card">
                <i class="mdi mdi-chart-line"></i>
                <h3>Growth Opportunities</h3>
                <p>Fast-growing startup with plenty of room for career advancement and impact.</p>
            </div>
        </div>
    </div>

    <!-- Culture -->
    <div class="culture-section">
        <h2>Our Culture</h2>
        <p>At HiTune, we're a diverse group of music lovers, tech enthusiasts, and problem solvers united by our mission to empower artists. We celebrate creativity, embrace challenges, and believe that the best ideas can come from anyone, regardless of role or experience.</p>
    </div>

    <!-- Open Positions -->
    <div class="open-positions">
        <h2>Open Positions</h2>
        <div class="job-list">
            <div class="job-card">
                <div class="job-info">
                    <h3>Senior Software Engineer</h3>
                    <div class="job-meta">
                        <span><i class="mdi mdi-map-marker"></i> Remote</span>
                        <span><i class="mdi mdi-briefcase"></i> Full-time</span>
                    </div>
                </div>
                <a href="mailto:careers@hitune.in?subject=Application: Senior Software Engineer" class="apply-btn">Apply Now</a>
            </div>
            <div class="job-card">
                <div class="job-info">
                    <h3>Artist Relations Manager</h3>
                    <div class="job-meta">
                        <span><i class="mdi mdi-map-marker"></i> Remote</span>
                        <span><i class="mdi mdi-briefcase"></i> Full-time</span>
                    </div>
                </div>
                <a href="mailto:careers@hitune.in?subject=Application: Artist Relations Manager" class="apply-btn">Apply Now</a>
            </div>
            <div class="job-card">
                <div class="job-info">
                    <h3>Marketing Specialist</h3>
                    <div class="job-meta">
                        <span><i class="mdi mdi-map-marker"></i> Remote</span>
                        <span><i class="mdi mdi-briefcase"></i> Full-time</span>
                    </div>
                </div>
                <a href="mailto:careers@hitune.in?subject=Application: Marketing Specialist" class="apply-btn">Apply Now</a>
            </div>
            <div class="job-card">
                <div class="job-info">
                    <h3>Customer Support Lead</h3>
                    <div class="job-meta">
                        <span><i class="mdi mdi-map-marker"></i> Remote</span>
                        <span><i class="mdi mdi-briefcase"></i> Full-time</span>
                    </div>
                </div>
                <a href="mailto:careers@hitune.in?subject=Application: Customer Support Lead" class="apply-btn">Apply Now</a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer_premium.php'; ?>
