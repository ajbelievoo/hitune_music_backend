<?php
/**
 * HiTune Music Distribution - Terms of Service Page
 */
$pageTitle = 'Terms of Service - HiTune Music Distribution';
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
        background: radial-gradient(circle, rgba(0, 200, 83, 0.2) 0%, transparent 60%);
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
        color: #00c853;
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
        background: rgba(0, 200, 83, 0.1);
        border: 1px solid rgba(0, 200, 83, 0.2);
        border-radius: 50px;
        font-size: 14px;
        color: #00c853;
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
    <h1><span class="gradient-text">Terms</span> of Service</h1>
    <p>Rules and guidelines for using our platform</p>
</section>

<div class="legal-container">
    <div class="last-updated">
        <span class="mdi mdi-calendar"></span>
        Last Updated: April 20, 2026
    </div>
    
    <div class="legal-card">
        <h2>1. Agreement to Terms</h2>
        <p>By accessing or using HiTune Music Distribution services, you agree to be bound by these Terms of Service. If you disagree with any part of the terms, you may not access the service.</p>
        
        <h2>2. Description of Service</h2>
        <p>HiTune Music Distribution provides a platform for artists and labels to distribute their music to various streaming platforms and digital stores worldwide. Our services include:</p>
        <ul>
            <li>Music distribution to 150+ platforms</li>
            <li>Royalty collection and payment processing</li>
            <li>Analytics and reporting tools</li>
            <li>Artist profile management</li>
            <li>Music publishing administration</li>
        </ul>
        
        <h2>3. Account Registration</h2>
        <h3>3.1 Eligibility</h3>
        <p>You must be at least 18 years old to create an account. By creating an account, you represent and warrant that:</p>
        <ul>
            <li>You are at least 18 years of age</li>
            <li>You have the legal capacity to enter into these Terms</li>
            <li>You own or have rights to distribute the content you upload</li>
            <li>The information you provide is accurate and complete</li>
        </ul>
        
        <h3>3.2 Account Security</h3>
        <p>You are responsible for maintaining the confidentiality of your account and password. You agree to accept responsibility for all activities that occur under your account.</p>
        
        <h2>4. Content and Distribution</h2>
        <h3>4.1 Your Content</h3>
        <p>You retain all rights to the music and content you upload. By using our service, you grant us a limited license to distribute your content to the platforms you select.</p>
        
        <h3>4.2 Prohibited Content</h3>
        <p>You may not upload content that:</p>
        <ul>
            <li>Infringes on any third-party rights</li>
            <li>Contains hate speech, violence, or illegal activities</li>
            <li>Is defamatory, obscene, or harassing</li>
            <li>Contains malware or harmful code</li>
        </ul>
        
        <h2>5. Payments and Royalties</h2>
        <p>We collect royalties from streaming platforms on your behalf. Payments are processed according to our pricing terms. You are responsible for providing accurate payment information and reporting income for tax purposes.</p>
        
        <h2>6. Termination</h2>
        <p>We may terminate or suspend your account immediately, without prior notice or liability, for any reason whatsoever, including without limitation if you breach the Terms.</p>
        
        <h2>7. Limitation of Liability</h2>
        <p>In no event shall HiTune Music Distribution be liable for any indirect, incidental, special, consequential, or punitive damages, including without limitation, loss of profits, data, use, goodwill, or other intangible losses.</p>
        
        <h2>8. Changes to Terms</h2>
        <p>We reserve the right to modify or replace these Terms at any time. If a revision is material, we will try to provide at least 30 days' notice prior to any new terms taking effect.</p>
        
        <h2>9. Contact Information</h2>
        <p>If you have any questions about these Terms, please contact us:</p>
        <p>Email: legal@hitune.in<br>
        Address: HiTune Music Distribution, Legal Department</p>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer_premium.php'; ?>
