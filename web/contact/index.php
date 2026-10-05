<?php
/**
 * HiTune Music Distribution - Contact Page
 * Direct access version
 */
$pageTitle = 'Contact Us - HiTune Music Distribution';
include __DIR__ . '/../includes/header_premium.php';

$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $success = true;
}
?>
<style>
    .contact-hero {
        padding: 120px 30px 60px;
        text-align: center;
        background: radial-gradient(ellipse at center, #1a1a2e 0%, #0a0a0a 100%);
    }
    .contact-hero h1 {
        font-size: clamp(36px, 6vw, 64px);
        font-weight: 800;
        margin-bottom: 20px;
    }
    .contact-hero p {
        font-size: 20px;
        color: rgba(255, 255, 255, 0.6);
        max-width: 700px;
        margin: 0 auto;
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
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, #00b7ff, #8b5cf6);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: #fff;
        flex-shrink: 0;
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
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 24px;
        padding: 40px;
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
        padding: 15px 20px;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        font-size: 15px;
        color: #fff;
        transition: all 0.3s;
        font-family: inherit;
    }
    .form-group input:focus,
    .form-group textarea:focus,
    .form-group select:focus {
        outline: none;
        border-color: #00b7ff;
        background: rgba(255, 255, 255, 0.08);
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
        padding: 16px;
        background: linear-gradient(135deg, #00b7ff, #8b5cf6);
        color: #fff;
        border: none;
        border-radius: 12px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }
    .submit-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 40px rgba(0, 183, 255, 0.3);
    }
    .success-message {
        text-align: center;
        padding: 40px;
    }
    .success-message i {
        font-size: 64px;
        color: #00c853;
        margin-bottom: 20px;
    }
    .success-message h3 {
        font-size: 24px;
        font-weight: 600;
        margin-bottom: 10px;
    }
    .success-message p {
        color: rgba(255, 255, 255, 0.6);
    }
    .faq-section {
        margin-top: 80px;
        text-align: center;
    }
    .faq-section h2 {
        font-size: 32px;
        font-weight: 700;
        margin-bottom: 20px;
    }
    .faq-section p {
        font-size: 16px;
        color: rgba(255, 255, 255, 0.6);
        margin-bottom: 30px;
    }
    .faq-btn {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 14px 30px;
        background: rgba(255, 255, 255, 0.1);
        color: #fff;
        text-decoration: none;
        border-radius: 30px;
        font-weight: 600;
        transition: all 0.3s;
    }
    .faq-btn:hover {
        background: linear-gradient(135deg, #00b7ff, #8b5cf6);
        transform: translateY(-3px);
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

<?php include __DIR__ . '/../includes/footer_premium.php'; ?>
