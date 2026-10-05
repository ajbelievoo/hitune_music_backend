<?php
/**
 * HiTune Music Distribution - Privacy Policy Page
 */
$pageTitle = 'Privacy Policy - HiTune Music Distribution';
include __DIR__ . '/../includes/header_premium.php';
?>
<style>
    .legal-hero {
        padding: 160px 40px 80px;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    
    .legal-hero::before {
        content: '';
        position: absolute;
        width: 600px;
        height: 600px;
        background: radial-gradient(circle, rgba(102, 126, 234, 0.2) 0%, transparent 60%);
        top: -200px;
        left: 50%;
        transform: translateX(-50%);
        animation: heroGlow 8s ease-in-out infinite;
            pointer-events: none;
    }
    
    @keyframes heroGlow {
        0%, 100% { transform: translateX(-50%) scale(1); opacity: 0.5; }
        50% { transform: translateX(-50%) scale(1.2); opacity: 0.8; }
    }
    
    .legal-hero h1 {
        font-size: clamp(42px, 8vw, 64px);
        font-weight: 800;
        margin-bottom: 20px;
        position: relative;
        z-index: 1;
    }
    
    .legal-hero p {
        font-size: 18px;
        color: rgba(255, 255, 255, 0.5);
        position: relative;
        z-index: 1;
    }
    
    .legal-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 0 40px 100px;
    }
    
    .legal-card {
        background: linear-gradient(135deg, rgba(255,255,255,0.03) 0%, rgba(255,255,255,0.01) 100%);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 30px;
        padding: 60px;
        position: relative;
        overflow: hidden;
    }
    
    .legal-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            pointer-events: none;
    }
    
    .legal-card h2 {
        font-size: 28px;
        font-weight: 700;
        margin: 50px 0 20px;
        color: #00b7ff;
    }
    
    .legal-card h2:first-child {
        margin-top: 0;
    }
    
    .legal-card h3 {
        font-size: 20px;
        font-weight: 600;
        margin: 30px 0 15px;
        color: rgba(255, 255, 255, 0.9);
    }
    
    .legal-card p {
        font-size: 16px;
        color: rgba(255, 255, 255, 0.6);
        line-height: 1.8;
        margin-bottom: 20px;
    }
    
    .legal-card ul {
        margin: 20px 0;
        padding-left: 30px;
    }
    
    .legal-card li {
        font-size: 16px;
        color: rgba(255, 255, 255, 0.6);
        line-height: 1.8;
        margin-bottom: 10px;
    }
    
    .last-updated {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 12px 24px;
        background: rgba(0, 183, 255, 0.1);
        border: 1px solid rgba(0, 183, 255, 0.2);
        border-radius: 50px;
        font-size: 14px;
        color: #00b7ff;
        margin-bottom: 40px;
    }
    
    @media (max-width: 768px) {
        .legal-card {
            padding: 40px 25px;
            border-radius: 24px;
        }
        
        .legal-card h2 {
            font-size: 24px;
        }
        
        .legal-card h3 {
            font-size: 18px;
        }
    }
</style>

<section class="legal-hero">
    <h1><span class="gradient-text">Privacy</span> Policy</h1>
    <p>How we protect and handle your data</p>
</section>

<div class="legal-container">
    <div class="last-updated">
        <span class="mdi mdi-calendar"></span>
        Last Updated: April 20, 2026
    </div>
    
    <div class="legal-card">
        <h2>1. Introduction</h2>
        <p>At HiTune Music Distribution, we take your privacy seriously. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our music distribution services. Please read this privacy policy carefully. If you do not agree with the terms of this privacy policy, please do not access the site.</p>
        
        <h2>2. Information We Collect</h2>
        <h3>Personal Data</h3>
        <p>We may collect personal identification information from you in a variety of ways, including but not limited to:</p>
        <ul>
            <li>Name, email address, phone number, and billing information</li>
            <li>Artist or band name, biography, and profile images</li>
            <li>Music files, artwork, and metadata (song titles, lyrics, credits)</li>
            <li>IP address, browser type, and device information</li>
        </ul>
        
        <h3>Usage Data</h3>
        <p>We automatically collect certain information when you visit, use, or navigate the platform. This information does not reveal your specific identity but may include device and usage information.</p>
        
        <h2>3. How We Use Your Information</h2>
        <p>We use personal information collected via our platform for a variety of business purposes, including:</p>
        <ul>
            <li>To distribute your music to streaming platforms and stores</li>
            <li>To process payments and royalties</li>
            <li>To provide analytics and reporting on your music performance</li>
            <li>To communicate with you about your account and releases</li>
            <li>To improve our services and develop new features</li>
            <li>To comply with legal obligations</li>
        </ul>
        
        <h2>4. Data Security</h2>
        <p>We have implemented appropriate technical and organizational security measures designed to protect the security of any personal information we process. However, please also remember that we cannot guarantee that the internet itself is 100% secure.</p>
        
        <h2>5. Your Rights</h2>
        <p>Depending on your location, you may have certain rights regarding your personal information, including:</p>
        <ul>
            <li>The right to access your personal data</li>
            <li>The right to correct inaccurate information</li>
            <li>The right to request deletion of your data</li>
            <li>The right to restrict or object to processing</li>
            <li>The right to data portability</li>
        </ul>
        
        <h2>6. Cookies and Tracking Technologies</h2>
        <p>We may use cookies, web beacons, tracking pixels, and other tracking technologies on the platform to help customize the platform and improve your experience.</p>
        
        <h2>7. Contact Us</h2>
        <p>If you have questions or comments about this Privacy Policy, please contact us at:</p>
        <p>Email: privacy@hitune.in<br>
        Address: HiTune Music Distribution, Privacy Department</p>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer_premium.php'; ?>
