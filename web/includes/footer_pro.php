<?php
/**
 * web.hitune.in - Professional Premium Footer
 */
?>
    </main>

    <!-- Premium Footer -->
    <footer class="footer">
        <div class="footer-glow"></div>
        
        <div class="footer-container">
            <!-- Top Section -->
            <div class="footer-top">
                <div class="footer-brand">
                    <div class="footer-logo">
                        <div class="logo-icon">
                            <span class="mdi mdi-music-note-eighth"></span>
                        </div>
                        <span class="footer-logo-text">HiTune</span>
                    </div>
                    <p class="footer-tagline">Distribute your music to 150+ streaming platforms worldwide. Keep 100% of your royalties.</p>
                    <div class="footer-social">
                        <a href="#" class="social-link"><span class="mdi mdi-facebook"></span></a>
                        <a href="#" class="social-link"><span class="mdi mdi-twitter"></span></a>
                        <a href="#" class="social-link"><span class="mdi mdi-instagram"></span></a>
                        <a href="#" class="social-link"><span class="mdi mdi-youtube"></span></a>
                        <a href="#" class="social-link"><span class="mdi mdi-linkedin"></span></a>
                    </div>
                </div>

                <div class="footer-links-grid">
                    <div class="footer-col">
                        <h4>Product</h4>
                        <ul>
                            <li><a href="/index.php?q=pricing">Pricing</a></li>
                            <li><a href="/index.php?q=releases">Releases</a></li>
                            <li><a href="/index.php?q=analytics">Analytics</a></li>
                            <li><a href="/index.php?q=stores">Stores</a></li>
                            <li><a href="/index.php?q=isrc">ISRC/UPC</a></li>
                        </ul>
                    </div>
                    
                    <div class="footer-col">
                        <h4>Company</h4>
                        <ul>
                            <li><a href="/index.php?q=about">About Us</a></li>
                            <li><a href="/index.php?q=careers">Careers</a></li>
                            <li><a href="/index.php?q=contact">Contact</a></li>
                            <li><a href="/index.php?q=publishing">Publishing</a></li>
                            <li><a href="/index.php?q=accelerator">Accelerator</a></li>
                        </ul>
                    </div>
                    
                    <div class="footer-col">
                        <h4>Resources</h4>
                        <ul>
                            <li><a href="#">Blog</a></li>
                            <li><a href="#">Help Center</a></li>
                            <li><a href="#">Artist Guide</a></li>
                            <li><a href="#">API Docs</a></li>
                            <li><a href="#">Status</a></li>
                        </ul>
                    </div>
                    
                    <div class="footer-col">
                        <h4>Legal</h4>
                        <ul>
                            <li><a href="#">Terms of Service</a></li>
                            <li><a href="#">Privacy Policy</a></li>
                            <li><a href="#">Cookie Policy</a></li>
                            <li><a href="#">Copyright</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Newsletter -->
            <div class="footer-newsletter">
                <div class="newsletter-content">
                    <div class="newsletter-text">
                        <h3>Stay in the loop</h3>
                        <p>Get the latest music industry news and tips delivered to your inbox.</p>
                    </div>
                    <form class="newsletter-form">
                        <input type="email" placeholder="Enter your email" required>
                        <button type="submit" class="btn-subscribe">Subscribe</button>
                    </form>
                </div>
            </div>

            <!-- Bottom Section -->
            <div class="footer-bottom">
                <div class="footer-bottom-content">
                    <p class="copyright">© <?php echo date("Y"); ?> HiTune Music Distribution. All rights reserved.</p>
                    <div class="payment-methods">
                        <span class="mdi mdi-credit-card"></span>
                        <span class="mdi mdi-credit-card-outline"></span>
                        <span class="mdi mdi-paypal"></span>
                        <span class="mdi mdi-bank"></span>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <style>
        /* Premium Footer */
        .footer {
            position: relative;
            background: rgba(10, 10, 15, 0.95);
            backdrop-filter: blur(20px);
            border-top: 1px solid var(--border);
            padding: 80px 0 40px;
            overflow: hidden;
            margin-top: 100px;
        }
        
        .footer-glow {
            position: absolute;
            top: -100px;
            left: 50%;
            transform: translateX(-50%);
            width: 600px;
            height: 200px;
            background: radial-gradient(ellipse, rgba(0, 183, 255, 0.15) 0%, transparent 70%);
            pointer-events: none;
        }
        
        .footer-container {
            max-width: 1440px;
            margin: 0 auto;
            padding: 0 40px;
        }
        
        .footer-top {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 80px;
            margin-bottom: 60px;
            padding-bottom: 60px;
            border-bottom: 1px solid var(--border);
        }
        
        .footer-brand {
            max-width: 320px;
        }
        
        .footer-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }
        
        .footer-logo .logo-icon {
            width: 44px;
            height: 44px;
            background: var(--gradient-1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }
        
        .footer-logo-text {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 24px;
            font-weight: 700;
        }
        
        .footer-tagline {
            color: var(--text-muted);
            font-size: 15px;
            line-height: 1.7;
            margin-bottom: 25px;
        }
        
        .footer-social {
            display: flex;
            gap: 12px;
        }
        
        .social-link {
            width: 44px;
            height: 44px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 20px;
            transition: all 0.3s ease;
        }
        
        .social-link:hover {
            background: var(--gradient-1);
            border-color: transparent;
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0, 183, 255, 0.3);
        }
        
        .footer-links-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 40px;
        }
        
        .footer-col h4 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--text);
        }
        
        .footer-col ul {
            list-style: none;
        }
        
        .footer-col ul li {
            margin-bottom: 12px;
        }
        
        .footer-col ul li a {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
            display: inline-block;
        }
        
        .footer-col ul li a:hover {
            color: var(--primary);
            transform: translateX(5px);
        }
        
        /* Newsletter */
        .footer-newsletter {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 40px;
        }
        
        .newsletter-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 40px;
        }
        
        .newsletter-text h3 {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .newsletter-text p {
            color: var(--text-muted);
            font-size: 14px;
        }
        
        .newsletter-form {
            display: flex;
            gap: 12px;
        }
        
        .newsletter-form input {
            width: 280px;
            padding: 14px 20px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border);
            border-radius: 12px;
            color: var(--text);
            font-size: 14px;
            outline: none;
            transition: all 0.3s ease;
        }
        
        .newsletter-form input:focus {
            border-color: var(--primary);
            background: rgba(0, 183, 255, 0.05);
        }
        
        .btn-subscribe {
            padding: 14px 28px;
            background: var(--gradient-1);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-subscribe:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 183, 255, 0.4);
        }
        
        /* Bottom */
        .footer-bottom {
            padding-top: 30px;
        }
        
        .footer-bottom-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .copyright {
            color: var(--text-muted);
            font-size: 14px;
        }
        
        .payment-methods {
            display: flex;
            gap: 16px;
            color: var(--text-muted);
            font-size: 24px;
        }
        
        @media (max-width: 1024px) {
            .footer-top {
                grid-template-columns: 1fr;
                gap: 40px;
            }
            .footer-links-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .newsletter-content {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</body>
</html>
