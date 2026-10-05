<?php
/**
 * HiTune Music Distribution - Premium Contact Page
 */
$pageTitle = 'Contact Us - HiTune Music Distribution';
include __DIR__ . '/includes/header_premium.php';

$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $success = true;
}
?>
<style>
    .contact-hero {
        padding: 160px 40px 80px;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    
    .contact-hero::before {
        content: '';
        position: absolute;
        width: 700px;
        height: 700px;
        background: radial-gradient(circle, rgba(102, 126, 234, 0.2) 0%, transparent 60%);
        top: -300px;
        left: 50%;
        transform: translateX(-50%);
        animation: heroGlow 8s ease-in-out infinite;
    }
    
    @keyframes heroGlow {
        0%, 100% { transform: translateX(-50%) scale(1); opacity: 0.5; }
        50% { transform: translateX(-50%) scale(1.2); opacity: 0.8; }
    }
    
    .contact-hero h1 {
        font-size: clamp(42px, 8vw, 72px);
        font-weight: 800;
        margin-bottom: 25px;
        position: relative;
        z-index: 1;
    }
    
    .contact-hero p {
        font-size: 20px;
        color: rgba(255, 255, 255, 0.6);
        max-width: 700px;
        margin: 0 auto;
        position: relative;
        z-index: 1;
    }
    .contact-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 60px 30px;
    }
    .contact-grid {
        display: grid;
        grid-template-columns: 1fr 1.5fr;
        gap: 60px;
    }
    .contact-info h2 {
        font-size: 28px;
        font-weight: 700;
        margin-bottom: 30px;
    }
    .contact-method {
        display: flex;
        align-items: flex-start;
        gap: 20px;
        margin-bottom: 30px;
    }
    .contact-icon {
        width: 55px;
        height: 55px;
        background: linear-gradient(135deg, #00b7ff, #8b5cf6);
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 26px;
        color: #fff;
        flex-shrink: 0;
        box-shadow: 0 10px 30px rgba(0, 183, 255, 0.3);
        animation: iconPulse 3s ease-in-out infinite;
    }
    
    @keyframes iconPulse {
        0%, 100% { box-shadow: 0 10px 30px rgba(0, 183, 255, 0.3); }
        50% { box-shadow: 0 15px 40px rgba(0, 183, 255, 0.5); }
    }
    .contact-details h3 {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 5px;
    }
    .contact-details p {
        font-size: 15px;
        color: rgba(255, 255, 255, 0.6);
        line-height: 1.5;
    }
    .contact-details a {
        color: #00b7ff;
        text-decoration: none;
    }
    .contact-details a:hover {
        text-decoration: underline;
    }
    .social-links {
        margin-top: 40px;
    }
    .social-links h3 {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 20px;
    }
    .social-icons {
        display: flex;
        gap: 15px;
    }
    .social-icons a {
        width: 45px;
        height: 45px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        color: #fff;
        text-decoration: none;
        transition: all 0.3s;
    }
    .social-icons a:hover {
        background: linear-gradient(135deg, #00b7ff, #8b5cf6);
        transform: translateY(-3px);
    }
    .contact-form-wrapper {
        background: linear-gradient(135deg, rgba(255,255,255,0.03) 0%, rgba(255,255,255,0.01) 100%);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 30px;
        padding: 50px;
        position: relative;
        overflow: hidden;
    }
    
    .contact-form-wrapper::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    }
    .contact-form-wrapper h2 {
        font-size: 28px;
        font-weight: 700;
        margin-bottom: 30px;
    }
    .form-group {
        margin-bottom: 25px;
    }
    .form-group label {
        display: block;
        font-size: 14px;
        font-weight: 500;
        margin-bottom: 10px;
        color: rgba(255, 255, 255, 0.8);
    }
    .form-group input,
    .form-group textarea,
    .form-group select {
        width: 100%;
        padding: 16px 20px;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 16px;
        font-size: 15px;
        color: #fff;
        transition: all 0.3s;
        font-family: inherit;
    }
    
    .form-group input:focus,
    .form-group textarea:focus,
    .form-group select:focus {
        outline: none;
        border-color: rgba(0, 183, 255, 0.5);
        background: rgba(255, 255, 255, 0.08);
        box-shadow: 0 0 25px rgba(0, 183, 255, 0.15);
    }
    .form-group textarea {
        min-height: 150px;
        resize: vertical;
    }
    .form-group select option {
        background: #1a1a2e;
        color: #fff;
    }
    .submit-btn {
        width: 100%;
        padding: 18px;
        background: linear-gradient(135deg, #00b7ff, #8b5cf6);
        color: #fff;
        border: none;
        border-radius: 16px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        box-shadow: 0 10px 30px rgba(0, 183, 255, 0.3);
        position: relative;
        overflow: hidden;
    }
    
    .submit-btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        transition: left 0.6s;
    }
    
    .submit-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 40px rgba(0, 183, 255, 0.5);
    }
    
    .submit-btn:hover::before {
        left: 100%;
    }
    .success-message {
        text-align: center;
        padding: 50px;
    }
    
    .success-message i {
        font-size: 72px;
        background: linear-gradient(135deg, #00c853, #00e676);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-bottom: 25px;
        animation: successPop 0.5s ease-out;
    }
    
    @keyframes successPop {
        0% { transform: scale(0); }
        50% { transform: scale(1.2); }
        100% { transform: scale(1); }
    }
    
    .success-message h3 {
        font-size: 28px;
        font-weight: 700;
        margin-bottom: 15px;
    }
    
    .success-message p {
        font-size: 16px;
        color: rgba(255, 255, 255, 0.6);
    }
    .faq-section {
        margin-top: 100px;
        text-align: center;
        padding: 60px;
        background: linear-gradient(135deg, rgba(255,255,255,0.02) 0%, rgba(255,255,255,0.01) 100%);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 30px;
        position: relative;
        overflow: hidden;
    }
    
    .faq-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    }
    
    .faq-section h2 {
        font-size: 36px;
        font-weight: 700;
        margin-bottom: 20px;
    }
    
    .faq-section p {
        font-size: 17px;
        color: rgba(255, 255, 255, 0.6);
        margin-bottom: 35px;
    }
    
    .faq-btn {
        display: inline-flex;
        align-items: center;
        gap: 12px;
        padding: 16px 35px;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: #fff;
        text-decoration: none;
        border-radius: 50px;
        font-weight: 600;
        font-size: 15px;
        transition: all 0.4s;
    }
    
    .faq-btn:hover {
        background: linear-gradient(135deg, #00b7ff, #8b5cf6);
        border-color: transparent;
        transform: translateY(-3px);
        box-shadow: 0 15px 40px rgba(0, 183, 255, 0.3);
    }
    @media (max-width: 968px) {
        .contact-grid {
            grid-template-columns: 1fr;
        }
        .contact-info {
            order: 2;
        }
        .contact-form-wrapper {
            order: 1;
        }
    }
</style>

<section class="contact-hero">
    <h1>Get In <span class="gradient-text">Touch</span></h1>
    <p>Have questions? We'd love to hear from you. Send us a message and we'll respond as soon as possible.</p>
</section>

<div class="contact-container">
    <div class="contact-grid">
        <div class="contact-info">
            <h2>Contact Information</h2>
            
            <div class="contact-method">
                <div class="contact-icon">
                    <i class="mdi mdi-email"></i>
                </div>
                <div class="contact-details">
                    <h3>Email Us</h3>
                    <p>For general inquiries:<br><a href="mailto:support@hitune.in">support@hitune.in</a></p>
                </div>
            </div>
            
            <div class="contact-method">
                <div class="contact-icon">
                    <i class="mdi mdi-headphones"></i>
                </div>
                <div class="contact-details">
                    <h3>Artist Support</h3>
                    <p>For artist-specific questions:<br><a href="mailto:artists@hitune.in">artists@hitune.in</a></p>
                </div>
            </div>
            
            <div class="contact-method">
                <div class="contact-icon">
                    <i class="mdi mdi-briefcase"></i>
                </div>
                <div class="contact-details">
                    <h3>Business Inquiries</h3>
                    <p>For partnerships and business:<br><a href="mailto:business@hitune.in">business@hitune.in</a></p>
                </div>
            </div>
            
            <div class="contact-method">
                <div class="contact-icon">
                    <i class="mdi mdi-clock-outline"></i>
                </div>
                <div class="contact-details">
                    <h3>Response Time</h3>
                    <p>We typically respond to all inquiries within 24-48 hours during business days.</p>
                </div>
            </div>

            <div class="social-links">
                <h3>Follow Us</h3>
                <div class="social-icons">
                    <a href="#" target="_blank"><i class="mdi mdi-facebook"></i></a>
                    <a href="#" target="_blank"><i class="mdi mdi-twitter"></i></a>
                    <a href="#" target="_blank"><i class="mdi mdi-instagram"></i></a>
                    <a href="#" target="_blank"><i class="mdi mdi-linkedin"></i></a>
                    <a href="#" target="_blank"><i class="mdi mdi-youtube"></i></a>
                </div>
            </div>
        </div>

        <div class="contact-form-wrapper">
            <?php if ($success): ?>
                <div class="success-message">
                    <i class="mdi mdi-check-circle"></i>
                    <h3>Message Sent!</h3>
                    <p>Thank you for contacting us. We'll get back to you shortly.</p>
                </div>
            <?php else: ?>
                <h2>Send a Message</h2>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="name">Your Name</label>
                        <input type="text" id="name" name="name" required placeholder="Enter your name">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required placeholder="Enter your email">
                    </div>
                    
                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <select id="subject" name="subject" required>
                            <option value="">Select a subject</option>
                            <option value="general">General Inquiry</option>
                            <option value="support">Artist Support</option>
                            <option value="sales">Sales & Partnerships</option>
                            <option value="technical">Technical Issue</option>
                            <option value="billing">Billing Question</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Your Message</label>
                        <textarea id="message" name="message" required placeholder="Tell us how we can help you..."></textarea>
                    </div>
                    
                    <button type="submit" class="submit-btn">
                        <i class="mdi mdi-send"></i> Send Message
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- FAQ Section -->
    <div class="faq-section">
        <h2>Looking for quick answers?</h2>
        <p>Check our Help Center for frequently asked questions and troubleshooting guides.</p>
        <a href="https://music.hitune.in/help" target="_blank" class="faq-btn">
            <i class="mdi mdi-help-circle"></i> Visit Help Center
        </a>
    </div>
</div>

<?php include __DIR__ . '/includes/footer_premium.php'; ?>
