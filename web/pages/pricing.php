<?php
/**
 * HiTune Music Distribution - Premium Pricing Page
 */
$pageTitle = 'Pricing Plans - HiTune Music Distribution';
$metaDescription = 'Choose your music distribution plan. Artist ₹699/yr, Artist Pro ₹1,499/yr, Label ₹2,499/yr — or pay per release from ₹149. Keep 100% royalties.';
$metaKeywords = 'music distribution pricing, music distribution plans, hitune pricing';
$canonicalUrl = 'https://web.hitune.in/index.php?q=pricing';
$path = 'pricing';
$jsonLd = ['@context' => 'https://schema.org', '@type' => 'Organization', 'name' => 'HiTune Music Distribution', 'url' => 'https://web.hitune.in'];
include __DIR__ . '/../includes/header_premium.php';
?>
<style>
    .pricing-hero {
        padding: 160px 40px 80px;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    
    .pricing-hero::before {
        content: '';
        position: absolute;
        width: 800px;
        height: 800px;
        background: radial-gradient(circle, rgba(0, 200, 83, 0.15) 0%, transparent 60%);
        top: -300px;
        left: 50%;
        transform: translateX(-50%);
        animation: heroGlow 8s ease-in-out infinite;
        pointer-events: none;
    }
    
    @keyframes heroGlow {
        0%, 100% { transform: translateX(-50%) scale(1); opacity: 0.5; }
        50% { transform: translateX(-50%) scale(1.2); opacity: 0.8; }
    }
    
    .pricing-hero h1 {
        font-size: clamp(42px, 8vw, 72px);
        font-weight: 800;
        margin-bottom: 25px;
        position: relative;
        z-index: 1;
    }
    .pricing-hero p {
        font-size: 20px;
        color: rgba(255, 255, 255, 0.6);
        max-width: 600px;
        margin: 0 auto;
    }
    /* Toggle */
    .plan-toggle {
        display: flex;
        justify-content: center;
        gap: 20px;
        margin: 40px 0;
        position: relative;
        z-index: 2;
    }
    .toggle-btn {
        padding: 12px 30px;
        border-radius: 30px;
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: #fff;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        transition: all 0.3s;
    }
    .toggle-btn.active {
        background: linear-gradient(135deg, #00b7ff, #8b5cf6);
        border-color: transparent;
    }
    /* Pricing Cards */
    .pricing-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 40px 30px 100px;
    }
    .pricing-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 30px;
    }
    .pricing-card {
        background: linear-gradient(135deg, rgba(255,255,255,0.03) 0%, rgba(255,255,255,0.01) 100%);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 30px;
        padding: 45px;
        position: relative;
        transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        overflow: hidden;
    }
    
    .pricing-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            pointer-events: none;
    }
    
    .pricing-card:hover {
        transform: translateY(-15px);
        border-color: rgba(0, 183, 255, 0.3);
        box-shadow: 0 30px 60px rgba(0, 0, 0, 0.4), 0 0 40px rgba(0, 183, 255, 0.1);
    }
    
    .pricing-card.featured {
        background: linear-gradient(180deg, rgba(0, 183, 255, 0.08) 0%, rgba(255,255,255,0.02) 100%);
        border-color: rgba(0, 183, 255, 0.4);
        transform: scale(1.05);
        box-shadow: 0 20px 50px rgba(0, 183, 255, 0.15);
    }
    
    .pricing-card.featured:hover {
        transform: scale(1.05) translateY(-15px);
        box-shadow: 0 30px 70px rgba(0, 183, 255, 0.25);
    }
    .badge {
        position: absolute;
        top: -15px;
        left: 50%;
        transform: translateX(-50%);
        background: linear-gradient(135deg, #00b7ff, #8b5cf6);
        color: #fff;
        padding: 8px 24px;
        border-radius: 30px;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        box-shadow: 0 5px 20px rgba(0, 183, 255, 0.4);
        animation: badgePulse 2s ease-in-out infinite;
    }
    
    @keyframes badgePulse {
        0%, 100% { box-shadow: 0 5px 20px rgba(0, 183, 255, 0.4); }
        50% { box-shadow: 0 5px 30px rgba(0, 183, 255, 0.7); }
    }
    .plan-name {
        font-size: 24px;
        font-weight: 700;
        margin-bottom: 10px;
        text-transform: uppercase;
    }
    .plan-subtitle {
        font-size: 14px;
        color: rgba(255, 255, 255, 0.6);
        margin-bottom: 30px;
        min-height: 40px;
    }
    .plan-price {
        font-size: 56px;
        font-weight: 800;
        margin-bottom: 30px;
        background: linear-gradient(135deg, #00b7ff, #8b5cf6);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .plan-price span {
        font-size: 16px;
        color: rgba(255, 255, 255, 0.6);
        font-weight: 400;
        -webkit-text-fill-color: rgba(255, 255, 255, 0.6);
    }
    .plan-period {
        font-size: 14px;
        color: rgba(255, 255, 255, 0.5);
        margin-bottom: 30px;
    }
    .plan-features {
        list-style: none;
        margin-bottom: 30px;
    }
    .plan-features li {
        padding: 12px 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .plan-features li i {
        color: #00c853;
        font-size: 18px;
    }
    .plan-features li:last-child {
        border-bottom: none;
    }
    .plan-cta {
        width: 100%;
        padding: 16px;
        border-radius: 12px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    .plan-cta.primary {
        background: linear-gradient(135deg, #00b7ff, #8b5cf6);
        color: #fff;
    }
    .plan-cta.primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 30px rgba(0, 183, 255, 0.3);
    }
    .plan-cta.outline {
        background: transparent;
        color: #fff;
        border: 2px solid rgba(255, 255, 255, 0.3);
    }
    .plan-cta.outline:hover {
        background: rgba(255, 255, 255, 0.1);
    }
    /* Comparison Table */
    .comparison {
        max-width: 1200px;
        margin: 0 auto;
        padding: 60px 30px 100px;
    }
    .comparison h2 {
        text-align: center;
        font-size: 36px;
        font-weight: 800;
        margin-bottom: 50px;
    }
    .comparison-table {
        width: 100%;
        border-collapse: collapse;
        background: rgba(255, 255, 255, 0.03);
        border-radius: 16px;
        overflow: hidden;
    }
    .comparison-table th,
    .comparison-table td {
        padding: 20px;
        text-align: left;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    .comparison-table th {
        background: rgba(255, 255, 255, 0.05);
        font-weight: 600;
        font-size: 14px;
        text-transform: uppercase;
    }
    .comparison-table td:first-child {
        font-weight: 500;
    }
    .comparison-table td:not(:first-child) {
        text-align: center;
    }
    .comparison-table .check {
        color: #00c853;
        font-size: 20px;
    }
    .comparison-table .cross {
        color: rgba(255, 255, 255, 0.3);
        font-size: 20px;
    }
</style>

<section class="pricing-hero">
    <h1>Choose the Right Plan for Your <span class="gradient-text">Music</span></h1>
    <p>Get your music on 150+ streaming platforms. No hidden fees, keep 100% of your royalties.</p>
    <p style="margin-top:14px;font-size:14px;opacity:.85"><i class="mdi mdi-gift-outline"></i> <b>Start free:</b> 2 releases every month on us — no card required. Upgrade anytime for unlimited.</p>

    <div class="plan-toggle">
        <button class="toggle-btn active" onclick="showYearly()">Yearly Plans</button>
        <button class="toggle-btn" onclick="showSingle()">Per Release</button>
    </div>
</section>

<section class="pricing-container">
    <div class="pricing-grid" id="yearly-plans">
        <!-- Artist -->
        <div class="pricing-card">
            <div class="plan-name">Artist</div>
            <div class="plan-subtitle">The essential distribution plan. Release unlimited music to stores across the globe.</div>
            <div class="plan-price">₹699<span>/year</span></div>
            <div class="plan-period">Annual subscription · ~₹58/month</div>
            <ul class="plan-features">
                <li><i class="mdi mdi-check-circle"></i> Upload to 50+ platforms</li>
                <li><i class="mdi mdi-check-circle"></i> Unlimited releases</li>
                <li><i class="mdi mdi-check-circle"></i> Keep 100% royalties</li>
                <li><i class="mdi mdi-check-circle"></i> Free ISRC &amp; UPC codes</li>
                <li><i class="mdi mdi-check-circle"></i> Basic streaming analytics</li>
                <li><i class="mdi mdi-check-circle"></i> Lyrics delivery to stores</li>
                <li><i class="mdi mdi-check-circle"></i> 1 artist profile</li>
                <li><i class="mdi mdi-check-circle"></i> Standard support (48h)</li>
                <li><i class="mdi mdi-close-circle" style="color: rgba(255,255,255,0.3);"></i> YouTube Content ID</li>
                <li><i class="mdi mdi-close-circle" style="color: rgba(255,255,255,0.3);"></i> Custom label name</li>
                <li><i class="mdi mdi-close-circle" style="color: rgba(255,255,255,0.3);"></i> Revenue splits</li>
                <li><i class="mdi mdi-close-circle" style="color: rgba(255,255,255,0.3);"></i> Scheduled releases</li>
            </ul>
            <a href="/index.php?q=checkout&plan=rising_artist" class="plan-cta outline" style="text-decoration: none;">Get Started</a>
        </div>

        <!-- Artist Pro -->
        <div class="pricing-card featured">
            <div class="badge">Best Deal!</div>
            <div class="plan-name">Artist Pro</div>
            <div class="plan-subtitle">Unlimited music plus advanced tools to grow and customize your releases.</div>
            <div class="plan-price">₹1499<span>/year</span></div>
            <div class="plan-period">Annual subscription · ~₹125/month</div>
            <ul class="plan-features">
                <li><i class="mdi mdi-check-circle"></i> Upload to 100+ platforms</li>
                <li><i class="mdi mdi-check-circle"></i> Unlimited releases</li>
                <li><i class="mdi mdi-check-circle"></i> Keep 100% royalties</li>
                <li><i class="mdi mdi-check-circle"></i> Free ISRC &amp; UPC codes</li>
                <li><i class="mdi mdi-check-circle"></i> Advanced analytics + trends</li>
                <li><i class="mdi mdi-check-circle"></i> YouTube Content ID</li>
                <li><i class="mdi mdi-check-circle"></i> Custom label name</li>
                <li><i class="mdi mdi-check-circle"></i> Revenue splits with collaborators</li>
                <li><i class="mdi mdi-check-circle"></i> 3 artist profiles</li>
                <li><i class="mdi mdi-check-circle"></i> Priority support (24h)</li>
                <li><i class="mdi mdi-close-circle" style="color: rgba(255,255,255,0.3);"></i> Scheduled releases</li>
                <li><i class="mdi mdi-close-circle" style="color: rgba(255,255,255,0.3);"></i> Playlist pitching</li>
            </ul>
            <a href="/index.php?q=checkout&plan=breakout_artist" class="plan-cta primary" style="text-decoration: none;">Get Started</a>
        </div>

        <!-- Professional -->
        <div class="pricing-card">
            <div class="plan-name">Label</div>
            <div class="plan-subtitle">For serious artists and labels. Full feature set with VIP support.</div>
            <div class="plan-price">₹2499<span>/year</span></div>
            <div class="plan-period">Annual subscription · ~₹208/month</div>
            <ul class="plan-features">
                <li><i class="mdi mdi-check-circle"></i> Upload to 150+ platforms</li>
                <li><i class="mdi mdi-check-circle"></i> Unlimited releases</li>
                <li><i class="mdi mdi-check-circle"></i> Keep 100% royalties</li>
                <li><i class="mdi mdi-check-circle"></i> Free ISRC &amp; UPC codes</li>
                <li><i class="mdi mdi-check-circle"></i> Professional analytics suite</li>
                <li><i class="mdi mdi-check-circle"></i> YouTube Content ID</li>
                <li><i class="mdi mdi-check-circle"></i> Custom label name</li>
                <li><i class="mdi mdi-check-circle"></i> Revenue splits with collaborators</li>
                <li><i class="mdi mdi-check-circle"></i> Scheduled releases + pre-save</li>
                <li><i class="mdi mdi-check-circle"></i> Playlist pitching</li>
                <li><i class="mdi mdi-check-circle"></i> Unlimited artist profiles</li>
                <li><i class="mdi mdi-check-circle"></i> VIP support (12h)</li>
            </ul>
            <a href="/index.php?q=checkout&plan=professional" class="plan-cta outline" style="text-decoration: none;">Get Started</a>
        </div>
    </div>

    <div class="pricing-grid" id="single-plans" style="display:none;">
        <!-- Single -->
        <div class="pricing-card">
            <div class="plan-name">Single</div>
            <div class="plan-subtitle">One song, everywhere. Pay once — your release stays live forever, no renewal.</div>
            <div class="plan-price">₹149<span>/release</span></div>
            <div class="plan-period">One-time payment · 1 track</div>
            <ul class="plan-features">
                <li><i class="mdi mdi-check-circle"></i> 1 track to 50+ platforms</li>
                <li><i class="mdi mdi-check-circle"></i> Stays live forever — no renewal</li>
                <li><i class="mdi mdi-check-circle"></i> Keep 100% royalties</li>
                <li><i class="mdi mdi-check-circle"></i> Free ISRC &amp; UPC codes</li>
                <li><i class="mdi mdi-check-circle"></i> Basic analytics</li>
                <li><i class="mdi mdi-check-circle"></i> Lyrics delivery</li>
                <li><i class="mdi mdi-check-circle"></i> Standard support (48h)</li>
            </ul>
            <a href="<?php echo isLoggedIn() ? '/index.php?q=release-create' : '/index.php?q=signup'; ?>" class="plan-cta outline" style="text-decoration: none;">Release a Single</a>
        </div>

        <!-- EP -->
        <div class="pricing-card featured">
            <div class="badge">Most Popular</div>
            <div class="plan-name">EP</div>
            <div class="plan-subtitle">Perfect for 2–6 track projects. One payment, lifetime distribution.</div>
            <div class="plan-price">₹449<span>/release</span></div>
            <div class="plan-period">One-time payment · up to 6 tracks</div>
            <ul class="plan-features">
                <li><i class="mdi mdi-check-circle"></i> Up to 6 tracks to 100+ platforms</li>
                <li><i class="mdi mdi-check-circle"></i> Stays live forever — no renewal</li>
                <li><i class="mdi mdi-check-circle"></i> Keep 100% royalties</li>
                <li><i class="mdi mdi-check-circle"></i> Free ISRC &amp; UPC codes</li>
                <li><i class="mdi mdi-check-circle"></i> Advanced analytics</li>
                <li><i class="mdi mdi-check-circle"></i> YouTube Content ID</li>
                <li><i class="mdi mdi-check-circle"></i> Custom label name</li>
                <li><i class="mdi mdi-check-circle"></i> Priority support (24h)</li>
            </ul>
            <a href="<?php echo isLoggedIn() ? '/index.php?q=release-create' : '/index.php?q=signup'; ?>" class="plan-cta primary" style="text-decoration: none;">Release an EP</a>
        </div>

        <!-- Album -->
        <div class="pricing-card">
            <div class="plan-name">Album</div>
            <div class="plan-subtitle">Full-length release, 7+ tracks. Premium delivery with every pro tool included.</div>
            <div class="plan-price">₹899<span>/release</span></div>
            <div class="plan-period">One-time payment · 7+ tracks</div>
            <ul class="plan-features">
                <li><i class="mdi mdi-check-circle"></i> 7+ tracks to 150+ platforms</li>
                <li><i class="mdi mdi-check-circle"></i> Stays live forever — no renewal</li>
                <li><i class="mdi mdi-check-circle"></i> Keep 100% royalties</li>
                <li><i class="mdi mdi-check-circle"></i> Free ISRC &amp; UPC codes</li>
                <li><i class="mdi mdi-check-circle"></i> Professional analytics</li>
                <li><i class="mdi mdi-check-circle"></i> YouTube Content ID</li>
                <li><i class="mdi mdi-check-circle"></i> Scheduled release date</li>
                <li><i class="mdi mdi-check-circle"></i> VIP support (12h)</li>
            </ul>
            <a href="<?php echo isLoggedIn() ? '/index.php?q=release-create' : '/index.php?q=signup'; ?>" class="plan-cta outline" style="text-decoration: none;">Release an Album</a>
        </div>
    </div>
    <p id="perrelease-note" style="display:none;text-align:center;margin-top:30px;font-size:14px;opacity:0.7;">
        Pay once per release — no subscription. Need unlimited releases? Yearly plans work out cheaper.
    </p>
</section>

<section class="comparison">
    <h2>Compare All <span class="gradient-text">Features</span></h2>
    <table class="comparison-table">
        <thead>
            <tr>
                <th>Feature</th>
                <th>Artist</th>
                <th>Artist Pro</th>
                <th>Label</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Annual Price</td>
                <td>₹699</td>
                <td>₹1499</td>
                <td>₹2499</td>
            </tr>
            <tr>
                <td>Platform Count</td>
                <td>50+</td>
                <td>100+</td>
                <td>150+</td>
            </tr>
            <tr>
                <td>Unlimited Releases</td>
                <td><i class="mdi mdi-check-circle check"></i></td>
                <td><i class="mdi mdi-check-circle check"></i></td>
                <td><i class="mdi mdi-check-circle check"></i></td>
            </tr>
            <tr>
                <td>100% Royalties</td>
                <td><i class="mdi mdi-check-circle check"></i></td>
                <td><i class="mdi mdi-check-circle check"></i></td>
                <td><i class="mdi mdi-check-circle check"></i></td>
            </tr>
            <tr>
                <td>Free ISRC &amp; UPC Codes</td>
                <td><i class="mdi mdi-check-circle check"></i></td>
                <td><i class="mdi mdi-check-circle check"></i></td>
                <td><i class="mdi mdi-check-circle check"></i></td>
            </tr>
            <tr>
                <td>Lyrics Delivery</td>
                <td><i class="mdi mdi-check-circle check"></i></td>
                <td><i class="mdi mdi-check-circle check"></i></td>
                <td><i class="mdi mdi-check-circle check"></i></td>
            </tr>
            <tr>
                <td>YouTube Content ID</td>
                <td><i class="mdi mdi-close-circle cross"></i></td>
                <td><i class="mdi mdi-check-circle check"></i></td>
                <td><i class="mdi mdi-check-circle check"></i></td>
            </tr>
            <tr>
                <td>Custom Label Name</td>
                <td><i class="mdi mdi-close-circle cross"></i></td>
                <td><i class="mdi mdi-check-circle check"></i></td>
                <td><i class="mdi mdi-check-circle check"></i></td>
            </tr>
            <tr>
                <td>Revenue Splits</td>
                <td><i class="mdi mdi-close-circle cross"></i></td>
                <td><i class="mdi mdi-check-circle check"></i></td>
                <td><i class="mdi mdi-check-circle check"></i></td>
            </tr>
            <tr>
                <td>Scheduled Releases + Pre-save</td>
                <td><i class="mdi mdi-close-circle cross"></i></td>
                <td><i class="mdi mdi-close-circle cross"></i></td>
                <td><i class="mdi mdi-check-circle check"></i></td>
            </tr>
            <tr>
                <td>Playlist Pitching</td>
                <td><i class="mdi mdi-close-circle cross"></i></td>
                <td><i class="mdi mdi-close-circle cross"></i></td>
                <td><i class="mdi mdi-check-circle check"></i></td>
            </tr>
            <tr>
                <td>Artist Profiles</td>
                <td>1</td>
                <td>3</td>
                <td>Unlimited</td>
            </tr>
            <tr>
                <td>Support Response</td>
                <td>48 hours</td>
                <td>24 hours</td>
                <td>12 hours</td>
            </tr>
        </tbody>
    </table>
</section>

<script>
function showYearly() {
    document.querySelectorAll('.toggle-btn')[0].classList.add('active');
    document.querySelectorAll('.toggle-btn')[1].classList.remove('active');
    document.getElementById('yearly-plans').style.display = 'grid';
    document.getElementById('single-plans').style.display = 'none';
    document.getElementById('perrelease-note').style.display = 'none';
}
function showSingle() {
    document.querySelectorAll('.toggle-btn')[0].classList.remove('active');
    document.querySelectorAll('.toggle-btn')[1].classList.add('active');
    document.getElementById('yearly-plans').style.display = 'none';
    document.getElementById('single-plans').style.display = 'grid';
    document.getElementById('perrelease-note').style.display = 'block';
}
</script>

<?php
// FAQ data for pricing page
$pricingFaqs = [
    [
        'question' => 'Can I upgrade my plan at any time?',
        'answer'   => 'Yes! You can upgrade your plan at any time. The new plan takes effect immediately and you\'ll be charged the difference for the remaining period.'
    ],
    [
        'question' => 'Do I keep 100% of my royalties?',
        'answer'   => 'Absolutely. HiTune never takes a cut of your royalties. Every rupee earned from streams and downloads goes directly to you.'
    ],
    [
        'question' => 'How many releases can I submit per year?',
        'answer'   => 'All plans include unlimited releases. There is no cap on the number of singles, EPs, or albums you can distribute.'
    ],
    [
        'question' => 'What happens when my subscription expires?',
        'answer'   => 'Your existing releases remain live on all platforms. However, you won\'t be able to submit new releases until you renew your subscription.'
    ],
    [
        'question' => 'Is there a free trial available?',
        'answer'   => 'We don\'t offer a free trial, but all plans come with a 7-day money-back guarantee if you\'re not satisfied for any reason.'
    ],
];

// JSON-LD FAQPage schema
$faqSchema = [
    '@context'   => 'https://schema.org',
    '@type'      => 'FAQPage',
    'mainEntity' => array_map(function($faq) {
        return [
            '@type'          => 'Question',
            'name'           => $faq['question'],
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text'  => $faq['answer'],
            ],
        ];
    }, $pricingFaqs),
];
?>

<script type="application/ld+json"><?php echo json_encode($faqSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>

<section class="pricing-faq" style="max-width:900px;margin:0 auto;padding:60px 30px 100px;">
    <h2 style="text-align:center;font-size:36px;font-weight:800;margin-bottom:50px;">Frequently Asked <span class="gradient-text">Questions</span></h2>

    <style>
        .faq-item {
            background: linear-gradient(135deg, rgba(255,255,255,0.03) 0%, rgba(255,255,255,0.01) 100%);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 16px;
            margin-bottom: 16px;
            overflow: hidden;
        }
        .faq-question {
            width: 100%;
            background: none;
            border: none;
            color: #fff;
            font-family: inherit;
            font-size: 16px;
            font-weight: 600;
            text-align: left;
            padding: 22px 24px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            transition: background 0.2s;
        }
        .faq-question:hover {
            background: rgba(255,255,255,0.04);
        }
        .faq-question .faq-icon {
            font-size: 22px;
            color: #00b7ff;
            flex-shrink: 0;
            transition: transform 0.3s;
        }
        .faq-answer {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.35s ease, padding 0.35s ease;
            color: rgba(255,255,255,0.7);
            font-size: 15px;
            line-height: 1.7;
            padding: 0 24px;
        }
        .faq-item.open .faq-answer {
            max-height: 300px;
            padding: 0 24px 22px;
        }
        .faq-item.open .faq-icon {
            transform: rotate(45deg);
        }
    </style>

    <?php foreach ($pricingFaqs as $faq): ?>
    <div class="faq-item">
        <button class="faq-question" onclick="this.closest('.faq-item').classList.toggle('open')" aria-expanded="false">
            <?php echo htmlspecialchars($faq['question']); ?>
            <span class="mdi mdi-plus faq-icon"></span>
        </button>
        <div class="faq-answer">
            <?php echo htmlspecialchars($faq['answer']); ?>
        </div>
    </div>
    <?php endforeach; ?>
</section>

<?php include __DIR__ . '/../includes/footer_premium.php'; ?>
