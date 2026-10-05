<?php
/**
 * HiTune Music Distribution - Help Center / Support System
 */
$pageTitle = 'Help Center - HiTune Music Distribution';
include __DIR__ . '/../includes/header_premium.php';
?>
<style>
    /* Hero Section */
    .help-hero {
        padding: 140px 40px 80px;
        text-align: center;
        position: relative;
        overflow: hidden;
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
    }
    
    .help-hero::before {
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
    
    .help-hero h1 {
        font-size: clamp(36px, 6vw, 56px);
        font-weight: 800;
        margin-bottom: 15px;
        position: relative;
        z-index: 1;
    }
    
    .help-hero p {
        font-size: 18px;
        color: rgba(255, 255, 255, 0.6);
        margin-bottom: 40px;
        position: relative;
        z-index: 1;
    }
    
    /* Search Box */
    .help-search {
        max-width: 700px;
        margin: 0 auto 30px;
        position: relative;
        z-index: 1;
    }
    
    .help-search input {
        width: 100%;
        padding: 20px 60px 20px 25px;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 50px;
        font-size: 16px;
        color: #fff;
        backdrop-filter: blur(10px);
        transition: all 0.3s;
    }
    
    .help-search input:focus {
        outline: none;
        border-color: #00b7ff;
        background: rgba(255, 255, 255, 0.08);
        box-shadow: 0 0 30px rgba(0, 183, 255, 0.2);
    }
    
    .help-search input::placeholder {
        color: rgba(255, 255, 255, 0.4);
    }
    
    .help-search button {
        position: absolute;
        right: 8px;
        top: 50%;
        transform: translateY(-50%);
        width: 45px;
        height: 45px;
        background: linear-gradient(135deg, #00b7ff, #8b5cf6);
        border: none;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s;
    }
    
    .help-search button:hover {
        transform: translateY(-50%) scale(1.1);
        box-shadow: 0 5px 20px rgba(0, 183, 255, 0.4);
    }
    
    .help-search button span {
        color: #fff;
        font-size: 20px;
    }
    
    /* Quick Links */
    .quick-links {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 12px;
        position: relative;
        z-index: 1;
    }
    
    .quick-links a {
        padding: 10px 20px;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 25px;
        font-size: 14px;
        color: rgba(255, 255, 255, 0.7);
        transition: all 0.3s;
    }
    
    .quick-links a:hover {
        background: rgba(0, 183, 255, 0.1);
        border-color: rgba(0, 183, 255, 0.3);
        color: #00b7ff;
    }
    
    /* Categories Grid */
    .help-categories {
        max-width: 1200px;
        margin: 0 auto;
        padding: 80px 40px;
    }
    
    .help-categories h2 {
        text-align: center;
        font-size: 32px;
        font-weight: 700;
        margin-bottom: 50px;
    }
    
    .categories-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 25px;
    }
    
    .category-card {
        background: linear-gradient(135deg, rgba(255,255,255,0.03) 0%, rgba(255,255,255,0.01) 100%);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 24px;
        padding: 35px;
        transition: all 0.4s;
        cursor: pointer;
        position: relative;
        overflow: hidden;
    }
    
    .category-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            pointer-events: none;
    }
    
    .category-card:hover {
        transform: translateY(-8px);
        border-color: rgba(0, 183, 255, 0.2);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
    }
    
    .category-icon {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, rgba(0, 183, 255, 0.2), rgba(139, 92, 246, 0.1));
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 20px;
        font-size: 28px;
        color: #00b7ff;
    }
    
    .category-card h3 {
        font-size: 20px;
        font-weight: 600;
        margin-bottom: 10px;
    }
    
    .category-card p {
        font-size: 14px;
        color: rgba(255, 255, 255, 0.5);
        line-height: 1.6;
        margin-bottom: 15px;
    }
    
    .category-card .article-count {
        font-size: 13px;
        color: #00c853;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    /* FAQ Section */
    .faq-section {
        max-width: 900px;
        margin: 0 auto;
        padding: 0 40px 80px;
    }
    
    .faq-section h2 {
        text-align: center;
        font-size: 32px;
        font-weight: 700;
        margin-bottom: 50px;
    }
    
    .faq-item {
        background: linear-gradient(135deg, rgba(255,255,255,0.03) 0%, rgba(255,255,255,0.01) 100%);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 16px;
        margin-bottom: 15px;
        overflow: hidden;
        transition: all 0.3s;
    }
    
    .faq-item:hover {
        border-color: rgba(255, 255, 255, 0.15);
    }
    
    .faq-question {
        padding: 25px 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
        font-weight: 500;
        font-size: 16px;
    }
    
    .faq-question span {
        font-size: 24px;
        color: rgba(255, 255, 255, 0.5);
        transition: transform 0.3s;
    }
    
    .faq-item.active .faq-question span {
        transform: rotate(45deg);
        color: #00b7ff;
    }
    
    .faq-answer {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease-out;
    }
    
    .faq-item.active .faq-answer {
        max-height: 500px;
    }
    
    .faq-answer p {
        padding: 0 30px 25px;
        font-size: 15px;
        color: rgba(255, 255, 255, 0.6);
        line-height: 1.8;
    }
    
    /* Support Cards */
    .support-options {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 40px 100px;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 30px;
    }
    
    .support-card {
        background: linear-gradient(135deg, rgba(255,255,255,0.05) 0%, rgba(255,255,255,0.02) 100%);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 24px;
        padding: 40px;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    
    .support-card.featured {
        background: linear-gradient(135deg, rgba(0, 183, 255, 0.1), rgba(139, 92, 246, 0.05));
        border-color: rgba(0, 183, 255, 0.2);
    }
    
    .support-card h3 {
        font-size: 22px;
        font-weight: 600;
        margin-bottom: 15px;
    }
    
    .support-card p {
        font-size: 15px;
        color: rgba(255, 255, 255, 0.6);
        margin-bottom: 25px;
        line-height: 1.7;
    }
    
    .support-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 14px 28px;
        background: linear-gradient(135deg, #00b7ff, #8b5cf6);
        border: none;
        border-radius: 50px;
        font-size: 15px;
        font-weight: 600;
        color: #fff;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .support-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 30px rgba(0, 183, 255, 0.3);
    }
    
    .support-btn.secondary {
        background: transparent;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    
    .support-btn.secondary:hover {
        background: rgba(255, 255, 255, 0.05);
        border-color: rgba(255, 255, 255, 0.3);
    }
    
    /* Contact Form Modal */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.8);
        backdrop-filter: blur(5px);
        z-index: 1000;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    
    .modal-overlay.active {
        display: flex;
    }
    
    .modal {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 24px;
        padding: 40px;
        max-width: 500px;
        width: 100%;
        position: relative;
        animation: modalSlide 0.3s ease-out;
    }
    
    @keyframes modalSlide {
        from {
            opacity: 0;
            transform: translateY(-30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .modal h3 {
        font-size: 24px;
        font-weight: 700;
        margin-bottom: 25px;
    }
    
    .modal-close {
        position: absolute;
        top: 20px;
        right: 20px;
        width: 35px;
        height: 35px;
        background: rgba(255, 255, 255, 0.05);
        border: none;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s;
    }
    
    .modal-close:hover {
        background: rgba(0, 183, 255, 0.2);
    }
    
    .modal-close span {
        color: rgba(255, 255, 255, 0.7);
        font-size: 20px;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        font-size: 14px;
        font-weight: 500;
        margin-bottom: 8px;
        color: rgba(255, 255, 255, 0.8);
    }
    
    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 14px 18px;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        font-size: 15px;
        color: #fff;
        transition: all 0.3s;
    }
    
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #00b7ff;
        background: rgba(255, 255, 255, 0.08);
    }
    
    .form-group textarea {
        min-height: 120px;
        resize: vertical;
    }
    
    /* Live Chat Button */
    .live-chat-btn {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #00c853, #00e676);
        border: none;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 5px 20px rgba(0, 200, 83, 0.4);
        transition: all 0.3s;
        z-index: 100;
    }
    
    .live-chat-btn:hover {
        transform: scale(1.1);
        box-shadow: 0 8px 30px rgba(0, 200, 83, 0.5);
    }
    
    .live-chat-btn span {
        color: #fff;
        font-size: 28px;
    }
    
    @media (max-width: 768px) {
        .help-hero {
            padding: 120px 20px 60px;
        }
        
        .help-categories,
        .faq-section,
        .support-options {
            padding-left: 20px;
            padding-right: 20px;
        }
        
        .category-card,
        .support-card {
            padding: 25px;
        }
    }
</style>

<!-- Hero Section -->
<section class="help-hero">
    <h1><span class="gradient-text">Help</span> Center</h1>
    <p>How can we help you today? Search our knowledge base or contact support.</p>
    
    <div class="help-search">
        <input type="text" id="searchInput" placeholder="Search for help topics...">
        <button onclick="searchHelp()">
            <span class="mdi mdi-magnify"></span>
        </button>
    </div>
    
    <div class="quick-links">
        <a href="#account">Account Issues</a>
        <a href="#upload">Upload Music</a>
        <a href="#payments">Payments</a>
        <a href="#royalties">Royalties</a>
        <a href="#stores">Stores</a>
    </div>
</section>

<!-- Categories -->
<section class="help-categories">
    <h2>Browse by <span class="gradient-text">Category</span></h2>
    
    <div class="categories-grid">
        <div class="category-card" onclick="showCategory('account')">
            <div class="category-icon">
                <span class="mdi mdi-account-circle"></span>
            </div>
            <h3>Account & Profile</h3>
            <p>Manage your account settings, profile information, and security preferences.</p>
            <div class="article-count">
                <span class="mdi mdi-file-document"></span> 12 Articles
            </div>
        </div>
        
        <div class="category-card" onclick="showCategory('music')">
            <div class="category-icon">
                <span class="mdi mdi-music"></span>
            </div>
            <h3>Music & Streaming</h3>
            <p>Upload releases, manage metadata, and understand streaming analytics.</p>
            <div class="article-count">
                <span class="mdi mdi-file-document"></span> 18 Articles
            </div>
        </div>
        
        <div class="category-card" onclick="showCategory('payments')">
            <div class="category-icon">
                <span class="mdi mdi-credit-card"></span>
            </div>
            <h3>Payments & Subscriptions</h3>
            <p>Billing information, payment methods, subscription plans, and invoices.</p>
            <div class="article-count">
                <span class="mdi mdi-file-document"></span> 9 Articles
            </div>
        </div>
        
        <div class="category-card" onclick="showCategory('technical')">
            <div class="category-icon">
                <span class="mdi mdi-wrench"></span>
            </div>
            <h3>Technical Support</h3>
            <p>Troubleshooting guides, system requirements, and bug reporting.</p>
            <div class="article-count">
                <span class="mdi mdi-file-document"></span> 15 Articles
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="faq-section">
    <h2>Frequently Asked <span class="gradient-text">Questions</span></h2>
    
    <div class="faq-item">
        <div class="faq-question" onclick="toggleFaq(this)">
            How do I upload my music?
            <span class="mdi mdi-plus"></span>
        </div>
        <div class="faq-answer">
            <p>To upload your music, log in to your account and click on "Submit Music" or "Releases" in the dashboard. You'll need to provide audio files (WAV, FLAC, or MP3 320kbps), artwork (3000x3000px), and metadata including song titles, artist names, and release date. After submission, your release will be reviewed and distributed to selected platforms.</p>
        </div>
    </div>
    
    <div class="faq-item">
        <div class="faq-question" onclick="toggleFaq(this)">
            How long does distribution take?
            <span class="mdi mdi-plus"></span>
        </div>
        <div class="faq-answer">
            <p>Standard distribution takes 3-5 business days for review and processing. Premium users get priority review within 24-48 hours. Once approved, it takes an additional 2-7 days for your music to appear on most streaming platforms. Some platforms like Spotify and Apple Music may take up to 2 weeks.</p>
        </div>
    </div>
    
    <div class="faq-item">
        <div class="faq-question" onclick="toggleFaq(this)">
            When do I get paid my royalties?
            <span class="mdi mdi-plus"></span>
        </div>
        <div class="faq-answer">
            <p>Royalties are typically paid out 2-3 months after the streaming month ends (industry standard). You can request a payout once your balance reaches $25. Payments are processed through PayPal or bank transfer on the 15th of each month.</p>
        </div>
    </div>
    
    <div class="faq-item">
        <div class="faq-question" onclick="toggleFaq(this)">
            Can I edit my release after it's live?
            <span class="mdi mdi-plus"></span>
        </div>
        <div class="faq-answer">
            <p>Yes, you can edit certain metadata like artist bio, lyrics, and descriptions. However, changing audio files or primary artist names requires creating a new release. Minor corrections can be requested through your dashboard under the "Releases" section.</p>
        </div>
    </div>
    
    <div class="faq-item">
        <div class="faq-question" onclick="toggleFaq(this)">
            What audio formats do you accept?
            <span class="mdi mdi-plus"></span>
        </div>
        <div class="faq-answer">
            <p>We accept WAV (16-bit or 24-bit), FLAC, and MP3 (320kbps) files. WAV is recommended for best quality. Files must be stereo, and we recommend -1dB to -3dB headroom to prevent distortion.</p>
        </div>
    </div>
    
    <div class="faq-item">
        <div class="faq-question" onclick="toggleFaq(this)">
            How do I get verified on streaming platforms?
            <span class="mdi mdi-plus"></span>
        </div>
        <div class="faq-answer">
            <p>Verification is handled directly by each streaming platform. For Spotify, you can claim your profile through Spotify for Artists. For Apple Music, use Apple Music for Artists. We provide links and guidance in your dashboard under "Artist Services" to help you get verified.</p>
        </div>
    </div>
</section>

<!-- Support Options -->
<section class="support-options">
    <div class="support-card">
        <h3>Email Support</h3>
        <p>Send us a detailed message and we'll respond within 24-48 hours.</p>
        <button class="support-btn secondary" onclick="openModal('emailModal')">
            <span class="mdi mdi-email"></span>
            Send Email
        </button>
    </div>
    
    <div class="support-card featured">
        <h3>Live Chat</h3>
        <p>Chat with our support team in real-time for quick assistance.</p>
        <button class="support-btn" onclick="openChat()">
            <span class="mdi mdi-chat"></span>
            Start Chat
        </button>
    </div>
    
    <div class="support-card">
        <h3>Community Forum</h3>
        <p>Connect with other artists and find answers from the community.</p>
        <button class="support-btn secondary" onclick="window.open('#', '_blank')">
            <span class="mdi mdi-forum"></span>
            Visit Forum
        </button>
    </div>
</section>

<!-- Contact Modal -->
<div class="modal-overlay" id="emailModal">
    <div class="modal">
        <button class="modal-close" onclick="closeModal('emailModal')">
            <span class="mdi mdi-close"></span>
        </button>
        <h3>Contact Support</h3>
        <form onsubmit="submitSupport(event)">
            <div class="form-group">
                <label>Your Name</label>
                <input type="text" required placeholder="Enter your name">
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" required placeholder="Enter your email">
            </div>
            <div class="form-group">
                <label>Category</label>
                <select required>
                    <option value="">Select a category</option>
                    <option value="account">Account Issues</option>
                    <option value="upload">Upload Problems</option>
                    <option value="payment">Payments & Billing</option>
                    <option value="royalties">Royalties</option>
                    <option value="technical">Technical Issues</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="form-group">
                <label>Message</label>
                <textarea required placeholder="Describe your issue in detail..."></textarea>
            </div>
            <button type="submit" class="support-btn" style="width: 100%;">
                <span class="mdi mdi-send"></span>
                Submit Ticket
            </button>
        </form>
    </div>
</div>

<!-- Live Chat Button -->
<button class="live-chat-btn" onclick="openChat()">
    <span class="mdi mdi-chat-processing"></span>
</button>

<script>
    // FAQ Toggle
    function toggleFaq(element) {
        const item = element.parentElement;
        const isActive = item.classList.contains('active');
        
        // Close all
        document.querySelectorAll('.faq-item').forEach(faq => {
            faq.classList.remove('active');
        });
        
        // Open clicked if wasn't active
        if (!isActive) {
            item.classList.add('active');
        }
    }
    
    // Search Functionality
    function searchHelp() {
        const query = document.getElementById('searchInput').value.toLowerCase();
        if (query) {
            alert('Searching for: ' + query + '\n\n(This would search the knowledge base in a real implementation)');
        }
    }
    
    // Enter key search
    document.getElementById('searchInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            searchHelp();
        }
    });
    
    // Category Click
    function showCategory(category) {
        alert('Showing articles for: ' + category + '\n\n(This would filter articles in a real implementation)');
    }
    
    // Modal Functions
    function openModal(modalId) {
        document.getElementById(modalId).classList.add('active');
    }
    
    function closeModal(modalId) {
        document.getElementById(modalId).classList.remove('active');
    }
    
    // Close modal on overlay click
    document.querySelector('.modal-overlay').addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.remove('active');
        }
    });
    
    // Submit Support Ticket
    function submitSupport(e) {
        e.preventDefault();
        alert('Support ticket submitted!\n\nWe will get back to you within 24-48 hours.');
        closeModal('emailModal');
    }
    
    // Open Live Chat
    function openChat() {
        alert('Live Chat\n\nConnecting you to a support agent...\n\n(WhatsApp integration would go here: wa.me/919999999999)');
    }
</script>

<?php include __DIR__ . '/../includes/footer_premium.php'; ?>
