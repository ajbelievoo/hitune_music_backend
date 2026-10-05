<?php
/**
 * HiTune Premium Footer — Single Canonical Footer
 * Expects: $loadCharts (bool, optional) — set true to load Chart.js
 */
$loadCharts = isset($loadCharts) && $loadCharts === true;
?>
    </main><!-- /.main-content -->

    <!-- ==================== PREMIUM FOOTER ==================== -->
    <footer class="premium-footer" role="contentinfo">
        <div class="footer-glow" aria-hidden="true"></div>

        <div class="footer-container">
            <!-- Footer Top -->
            <div class="footer-top">
                <!-- Brand -->
                <div class="footer-brand">
                    <a href="/" class="footer-logo" aria-label="HiTune Home">
                        <div class="footer-logo-icon" aria-hidden="true">
                            <span class="mdi mdi-music-circle"></span>
                        </div>
                        <span class="footer-logo-text">HiTune</span>
                    </a>
                    <p class="footer-tagline">Empowering artists worldwide to share their music and keep 100% of their royalties.</p>
                    <div class="footer-social" aria-label="Social media links">
                        <a href="https://twitter.com/hitune_music" class="social-link" aria-label="Twitter" target="_blank" rel="noopener noreferrer">
                            <span class="mdi mdi-twitter" aria-hidden="true"></span>
                        </a>
                        <a href="https://instagram.com/hitune_music" class="social-link" aria-label="Instagram" target="_blank" rel="noopener noreferrer">
                            <span class="mdi mdi-instagram" aria-hidden="true"></span>
                        </a>
                        <a href="https://youtube.com/@hitune" class="social-link" aria-label="YouTube" target="_blank" rel="noopener noreferrer">
                            <span class="mdi mdi-youtube" aria-hidden="true"></span>
                        </a>
                        <a href="https://linkedin.com/company/hitune" class="social-link" aria-label="LinkedIn" target="_blank" rel="noopener noreferrer">
                            <span class="mdi mdi-linkedin" aria-hidden="true"></span>
                        </a>
                    </div>
                </div>

                <!-- Link Groups -->
                <div class="footer-links-grid">
                    <div class="footer-links-column">
                        <h3 class="footer-column-title">Company</h3>
                        <ul class="footer-links-list">
                            <li><a href="/about.php">About Us</a></li>
                            <li><a href="/index.php?q=pricing">Pricing</a></li>
                            <li><a href="/index.php?q=services">Services</a></li>
                            <li><a href="/careers.php">Careers</a></li>
                        </ul>
                    </div>

                    <div class="footer-links-column">
                        <h3 class="footer-column-title">Distribution</h3>
                        <ul class="footer-links-list">
                            <li><a href="/index.php?q=stores">Stores</a></li>
                            <li><a href="/index.php?q=sell">Sell Music</a></li>
                            <li><a href="/index.php?q=releases">Releases</a></li>
                            <li><a href="/index.php?q=isrc">ISRC Codes</a></li>
                        </ul>
                    </div>

                    <div class="footer-links-column">
                        <h3 class="footer-column-title">Support</h3>
                        <ul class="footer-links-list">
                            <li><a href="/index.php?q=help">Help Center</a></li>
                            <li><a href="/contact.php">Contact</a></li>
                            <li><a href="/index.php?q=privacy">Privacy Policy</a></li>
                            <li><a href="/index.php?q=terms">Terms of Service</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Footer Divider -->
            <div class="footer-divider" aria-hidden="true"></div>

            <!-- Footer Bottom -->
            <div class="footer-bottom">
                <p class="footer-copy">&copy; <?php echo date('Y'); ?> HiTune Music Distribution. All rights reserved.</p>
                <div class="footer-bottom-links">
                    <a href="/index.php?q=privacy">Privacy</a>
                    <a href="/index.php?q=terms">Terms</a>
                    <a href="/index.php?q=cookies">Cookies</a>
                </div>
            </div>
        </div>
    </footer>

    <style>
        /* ==================== PREMIUM FOOTER ==================== */
        .premium-footer {
            position: relative;
            background: linear-gradient(180deg, rgba(10,10,15,0.98) 0%, #0a0a0f 100%);
            border-top: 1px solid rgba(255,255,255,0.08);
            padding: 80px 0 40px;
            margin-top: auto;
            overflow: hidden;
        }

        .footer-glow {
            position: absolute;
            top: 0; left: 50%;
            transform: translateX(-50%);
            width: 800px; height: 400px;
            background: radial-gradient(ellipse, rgba(0,183,255,0.12) 0%, transparent 70%);
            pointer-events: none;
        }

        .footer-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 40px;
            position: relative;
            z-index: 1;
        }

        .footer-top {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 80px;
            margin-bottom: 60px;
        }

        .footer-brand { max-width: 300px; }

        .footer-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: white;
            font-weight: 800;
            font-size: 26px;
            margin-bottom: 18px;
            transition: transform 0.3s;
        }

        .footer-logo:hover { transform: scale(1.02); }

        .footer-logo-icon {
            width: 46px; height: 46px;
            background: linear-gradient(135deg, #00b7ff, #8b5cf6);
            border-radius: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            animation: logoPulse 3s ease-in-out infinite;
        }

        @keyframes logoPulse {
            0%, 100% { box-shadow: 0 0 20px rgba(0,183,255,0.3); }
            50%       { box-shadow: 0 0 40px rgba(0,183,255,0.6); }
        }

        .footer-tagline {
            color: rgba(255,255,255,0.55);
            font-size: 14px;
            line-height: 1.7;
            margin-bottom: 28px;
        }

        .footer-social {
            display: flex;
            gap: 10px;
        }

        .social-link {
            width: 42px; height: 42px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(255,255,255,0.65);
            font-size: 20px;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .social-link:hover {
            background: linear-gradient(135deg, #00b7ff, #8b5cf6);
            border-color: transparent;
            color: white;
            transform: translateY(-4px) scale(1.1);
            box-shadow: 0 10px 30px rgba(0,183,255,0.4);
        }

        .footer-links-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 40px;
        }

        .footer-column-title {
            font-size: 15px;
            font-weight: 600;
            color: white;
            margin-bottom: 20px;
        }

        .footer-links-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-links-list li { margin-bottom: 12px; }

        .footer-links-list a {
            color: rgba(255,255,255,0.55);
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
        }

        .footer-links-list a:hover { color: #00b7ff; padding-left: 6px; }

        .footer-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            margin-bottom: 28px;
        }

        .footer-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        .footer-copy {
            color: rgba(255,255,255,0.45);
            font-size: 13px;
        }

        .footer-bottom-links {
            display: flex;
            gap: 24px;
        }

        .footer-bottom-links a {
            color: rgba(255,255,255,0.45);
            text-decoration: none;
            font-size: 13px;
            transition: color 0.3s;
        }

        .footer-bottom-links a:hover { color: #00b7ff; }

        @media (max-width: 1024px) {
            .footer-top {
                grid-template-columns: 1fr;
                gap: 50px;
            }
            .footer-brand { max-width: 100%; }
            .footer-links-grid { grid-template-columns: repeat(3, 1fr); }
        }

        @media (max-width: 640px) {
            .footer-container { padding: 0 20px; }
            .footer-links-grid { grid-template-columns: repeat(2, 1fr); gap: 24px; }
            .footer-bottom { flex-direction: column; text-align: center; }
            .footer-bottom-links { flex-wrap: wrap; justify-content: center; }
        }

        @media (max-width: 400px) {
            .footer-links-grid { grid-template-columns: 1fr; }
        }

        /* Light theme footer */
        html[data-theme="light"] .premium-footer {
            background: linear-gradient(180deg, #f8fafc 0%, #eef2f7 100%) !important;
            border-top-color: rgba(15,23,42,0.1) !important;
        }
        html[data-theme="light"] .footer-logo { color: #0f172a !important; }
        html[data-theme="light"] .footer-tagline { color: rgba(15,23,42,0.6) !important; }
        html[data-theme="light"] .social-link {
            background: rgba(15,23,42,0.05) !important;
            border-color: rgba(15,23,42,0.12) !important;
            color: rgba(15,23,42,0.65) !important;
        }
        html[data-theme="light"] .social-link:hover { color: #fff !important; }
        html[data-theme="light"] .footer-column-title { color: #0f172a !important; }
        html[data-theme="light"] .footer-links-list a { color: rgba(15,23,42,0.6) !important; }
        html[data-theme="light"] .footer-links-list a:hover { color: #00b7ff !important; }
        html[data-theme="light"] .footer-divider {
            background: linear-gradient(90deg, transparent, rgba(15,23,42,0.12), transparent) !important;
        }
        html[data-theme="light"] .footer-copy,
        html[data-theme="light"] .footer-bottom-links a { color: rgba(15,23,42,0.5) !important; }
        html[data-theme="light"] .footer-bottom-links a:hover { color: #00b7ff !important; }
        }
    </style>

    <?php if ($loadCharts): ?>
    <!-- Chart.js (loaded only when $loadCharts = true) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <?php endif; ?>

    <script>
    // ==================== INTERSECTION OBSERVER: Scroll Animations ====================
    (function() {
        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

        document.querySelectorAll('.animate-on-scroll').forEach(function(el) {
            observer.observe(el);
        });
    })();

    // ==================== HAMBURGER MENU TOGGLE ====================
    (function() {
        var toggle = document.getElementById('mobileToggle');
        var nav    = document.getElementById('mobileNav');
        if (!toggle || !nav) return;

        toggle.addEventListener('click', function() {
            var isOpen = nav.classList.toggle('active');
            toggle.classList.toggle('active', isOpen);
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            nav.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
            document.body.style.overflow = isOpen ? 'hidden' : '';
        });

        // Close on nav link click
        nav.querySelectorAll('.mobile-nav-link, .nav-btn').forEach(function(link) {
            link.addEventListener('click', function() {
                nav.classList.remove('active');
                toggle.classList.remove('active');
                toggle.setAttribute('aria-expanded', 'false');
                nav.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
            });
        });

        // Close on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && nav.classList.contains('active')) {
                nav.classList.remove('active');
                toggle.classList.remove('active');
                toggle.setAttribute('aria-expanded', 'false');
                nav.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
            }
        });
    })();

    // ==================== ANIMATED COUNTERS ====================
    (function() {
        function animateCounter(el) {
            var target = parseInt(el.getAttribute('data-target'), 10);
            if (isNaN(target)) return;
            var duration = 2000;
            var start    = performance.now();
            var suffix   = el.getAttribute('data-suffix') || '';

            function step(now) {
                var elapsed  = now - start;
                var progress = Math.min(elapsed / duration, 1);
                // Ease out cubic
                var eased = 1 - Math.pow(1 - progress, 3);
                el.textContent = Math.floor(eased * target) + suffix;
                if (progress < 1) requestAnimationFrame(step);
            }

            requestAnimationFrame(step);
        }

        var counterObserver = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    animateCounter(entry.target);
                    counterObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        document.querySelectorAll('[data-target]').forEach(function(el) {
            counterObserver.observe(el);
        });
    })();
    </script>
</body>
</html>
