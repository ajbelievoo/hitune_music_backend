<?php
/**
 * HiTune Music Distribution - Cookie Policy Page
 */
$pageTitle = 'Cookie Policy - HiTune Music Distribution';
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
        background: radial-gradient(circle, rgba(139, 92, 246, 0.2) 0%, transparent 60%);
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
        color: #8b5cf6;
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
    
    .cookie-table {
        width: 100%;
        border-collapse: collapse;
        margin: 30px 0;
    }
    
    .cookie-table th,
    .cookie-table td {
        padding: 16px;
        text-align: left;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .cookie-table th {
        font-weight: 600;
        color: rgba(255, 255, 255, 0.9);
        background: rgba(255, 255, 255, 0.03);
    }
    
    .cookie-table td {
        color: rgba(255, 255, 255, 0.6);
    }
    
    .cookie-table tr:hover td {
        background: rgba(255, 255, 255, 0.02);
    }
    
    .last-updated {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 12px 24px;
        background: rgba(139, 92, 246, 0.1);
        border: 1px solid rgba(139, 92, 246, 0.2);
        border-radius: 50px;
        font-size: 14px;
        color: #8b5cf6;
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
        
        .cookie-table {
            font-size: 14px;
        }
        
        .cookie-table th,
        .cookie-table td {
            padding: 12px;
        }
    }
</style>

<section class="legal-hero">
    <h1><span class="gradient-text">Cookie</span> Policy</h1>
    <p>How we use cookies and similar technologies</p>
</section>

<div class="legal-container">
    <div class="last-updated">
        <span class="mdi mdi-calendar"></span>
        Last Updated: April 20, 2026
    </div>
    
    <div class="legal-card">
        <h2>1. What Are Cookies</h2>
        <p>Cookies are small text files that are stored on your computer or mobile device when you visit a website. They are widely used to make websites work more efficiently and provide information to the website owners.</p>
        
        <h2>2. How We Use Cookies</h2>
        <p>HiTune Music Distribution uses cookies for various purposes, including:</p>
        <ul>
            <li><strong>Essential Cookies:</strong> Required for the website to function properly</li>
            <li><strong>Authentication Cookies:</strong> To keep you logged in to your account</li>
            <li><strong>Analytics Cookies:</strong> To understand how visitors interact with our website</li>
            <li><strong>Preference Cookies:</strong> To remember your settings and preferences</li>
            <li><strong>Marketing Cookies:</strong> To deliver relevant advertisements</li>
        </ul>
        
        <h2>3. Types of Cookies We Use</h2>
        <table class="cookie-table">
            <thead>
                <tr>
                    <th>Cookie Name</th>
                    <th>Purpose</th>
                    <th>Duration</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>session_id</td>
                    <td>Maintains your login session</td>
                    <td>Session</td>
                </tr>
                <tr>
                    <td>user_prefs</td>
                    <td>Stores your preferences</td>
                    <td>1 year</td>
                </tr>
                <tr>
                    <td>_ga</td>
                    <td>Google Analytics - visitor identification</td>
                    <td>2 years</td>
                </tr>
                <tr>
                    <td>_gid</td>
                    <td>Google Analytics - session identification</td>
                    <td>24 hours</td>
                </tr>
                <tr>
                    <td>cookie_consent</td>
                    <td>Stores your cookie preferences</td>
                    <td>1 year</td>
                </tr>
            </tbody>
        </table>
        
        <h2>4. Third-Party Cookies</h2>
        <p>We may also use third-party cookies from our partners and service providers. These cookies are set by domains other than hitune.in. We use third-party cookies for:</p>
        <ul>
            <li>Payment processing</li>
            <li>Analytics and performance monitoring</li>
            <li>Social media integration</li>
            <li>Customer support services</li>
        </ul>
        
        <h2>5. Managing Cookies</h2>
        <p>Most web browsers allow you to control cookies through their settings. You can usually find these settings in the "Options" or "Preferences" menu of your browser. You can:</p>
        <ul>
            <li>Delete all cookies</li>
            <li>Block all cookies</li>
            <li>Allow all cookies</li>
            <li>Block third-party cookies</li>
            <li>Clear all cookies when you close the browser</li>
        </ul>
        
        <h2>6. Changes to This Policy</h2>
        <p>We may update this Cookie Policy from time to time to reflect changes in technology, legislation, or our data practices. Any changes will be posted on this page with an updated revision date.</p>
        
        <h2>7. Contact Us</h2>
        <p>If you have any questions about our use of cookies, please contact us:</p>
        <p>Email: privacy@hitune.in<br>
        Address: HiTune Music Distribution, Privacy Department</p>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer_premium.php'; ?>
