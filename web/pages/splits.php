<?php
/**
 * HiTune Music Distribution - Splits Page
 */
$pageTitle = 'Splits - HiTune Music Distribution';
include __DIR__ . '/../includes/header_premium.php';
?>
<style>
    .splits-hero {
        padding: 120px 30px 80px;
        text-align: center;
        background: radial-gradient(ellipse at center, #1a1a2e 0%, #0a0a0a 100%);
    }
    .splits-hero h1 {
        font-size: clamp(36px, 7vw, 64px);
        font-weight: 800;
        margin-bottom: 20px;
    }
    .splits-hero p {
        font-size: 20px;
        color: rgba(255, 255, 255, 0.7);
        max-width: 700px;
        margin: 0 auto 40px;
    }
    .splits-cta {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 16px 40px;
        background: linear-gradient(135deg, #00b7ff, #8b5cf6);
        color: #fff;
        text-decoration: none;
        border-radius: 30px;
        font-size: 16px;
        font-weight: 600;
        transition: all 0.3s;
    }
    .splits-cta:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 40px rgba(0, 183, 255, 0.4);
    }
    /* How It Works */
    .how-splits {
        padding: 100px 30px;
        background: linear-gradient(180deg, #0a0a0a 0%, #111 50%, #0a0a0a 100%);
    }
    .how-splits-container {
        max-width: 1200px;
        margin: 0 auto;
    }
    .how-splits h2 {
        text-align: center;
        font-size: 42px;
        font-weight: 800;
        margin-bottom: 20px;
    }
    .how-splits h2 span {
        color: #00b7ff;
    }
    .how-splits > p {
        text-align: center;
        font-size: 18px;
        color: rgba(255, 255, 255, 0.7);
        max-width: 700px;
        margin: 0 auto 60px;
    }
    .splits-steps {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 40px;
    }
    .split-step {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 20px;
        padding: 40px;
        text-align: center;
        transition: all 0.3s;
    }
    .split-step:hover {
        transform: translateY(-10px);
        background: rgba(255, 255, 255, 0.05);
    }
    .split-step i {
        font-size: 50px;
        color: #00b7ff;
        margin-bottom: 20px;
    }
    .split-step h3 {
        font-size: 22px;
        font-weight: 700;
        margin-bottom: 15px;
    }
    .split-step p {
        color: rgba(255, 255, 255, 0.6);
        font-size: 15px;
        line-height: 1.7;
    }
    /* Demo Section */
    .splits-demo {
        padding: 100px 30px;
        background: #0a0a0a;
    }
    .demo-container {
        max-width: 1000px;
        margin: 0 auto;
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 24px;
        padding: 50px;
    }
    .demo-header {
        text-align: center;
        margin-bottom: 40px;
    }
    .demo-header h3 {
        font-size: 28px;
        font-weight: 700;
        margin-bottom: 10px;
    }
    .demo-header p {
        color: rgba(255, 255, 255, 0.6);
    }
    .split-row {
        display: flex;
        align-items: center;
        gap: 20px;
        padding: 20px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 12px;
        margin-bottom: 15px;
    }
    .split-avatar {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, #00b7ff, #8b5cf6);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        flex-shrink: 0;
    }
    .split-info {
        flex: 1;
    }
    .split-info strong {
        display: block;
        margin-bottom: 5px;
    }
    .split-info span {
        font-size: 14px;
        color: rgba(255, 255, 255, 0.6);
    }
    .split-percentage {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    .split-percentage input {
        width: 80px;
        padding: 10px;
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 8px;
        color: #fff;
        font-size: 16px;
        text-align: center;
    }
    .split-total {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px;
        background: rgba(0, 200, 83, 0.1);
        border: 1px solid rgba(0, 200, 83, 0.3);
        border-radius: 12px;
        margin-top: 20px;
    }
    .split-total span {
        font-size: 14px;
        color: rgba(255, 255, 255, 0.8);
    }
    .split-total strong {
        font-size: 24px;
        color: #00c853;
    }
    .add-collaborator {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 15px;
        background: rgba(255, 255, 255, 0.05);
        border: 2px dashed rgba(255, 255, 255, 0.2);
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.3s;
        margin-top: 20px;
    }
    .add-collaborator:hover {
        border-color: #00b7ff;
        background: rgba(0, 183, 255, 0.1);
    }
</style>

<section class="splits-hero">
    <h1>HiTune <span class="gradient-text">Splits</span></h1>
    <p>Effortlessly split streaming & download royalties between all featured artists & producers. Get paid to collaborate.</p>
    <a href="/index.php?q=dashboard" class="splits-cta">
        <i class="mdi mdi-account-plus"></i>
        Sign Up Now
    </a>
</section>

<section class="how-splits">
    <div class="how-splits-container">
        <h2>How <span>HiTune Splits</span> Works</h2>
        <p>No more third-party providers or having to manually split royalties between artists. Create new splits, edit existing splits, and accept royalties from tracks you've worked on with other artists.</p>
        
        <div class="splits-steps">
            <div class="split-step">
                <i class="mdi mdi-account-multiple-plus"></i>
                <h3>Add Collaborators</h3>
                <p>Invite producers, songwriters, and featured artists to your release. They'll get access to track their earnings.</p>
            </div>
            <div class="split-step">
                <i class="mdi mdi-percent"></i>
                <h3>Set Percentages</h3>
                <p>Customize the percentage each collaborator receives. Change splits anytime before release.</p>
            </div>
            <div class="split-step">
                <i class="mdi mdi-cash-multiple"></i>
                <h3>Automatic Payouts</h3>
                <p>Everyone gets paid directly to their HiTune account. No manual calculations, no delays.</p>
            </div>
        </div>
    </div>
</section>

<section class="splits-demo">
    <div class="demo-container">
        <div class="demo-header">
            <h3>Royalty Split Example</h3>
            <p>"Summer Vibes" - Single Release</p>
        </div>
        
        <div class="split-row">
            <div class="split-avatar">A</div>
            <div class="split-info">
                <strong>Ajay Kumar</strong>
                <span>Primary Artist, Songwriter</span>
            </div>
            <div class="split-percentage">
                <input type="text" value="50" readonly>%
            </div>
        </div>
        
        <div class="split-row">
            <div class="split-avatar" style="background: linear-gradient(135deg, #00c853, #00e676);">R</div>
            <div class="split-info">
                <strong>Rahul Sharma</strong>
                <span>Featured Artist</span>
            </div>
            <div class="split-percentage">
                <input type="text" value="30" readonly>%
            </div>
        </div>
        
        <div class="split-row">
            <div class="split-avatar" style="background: linear-gradient(135deg, #9c27b0, #e91e63);">P</div>
            <div class="split-info">
                <strong>Studio Beats</strong>
                <span>Producer</span>
            </div>
            <div class="split-percentage">
                <input type="text" value="20" readonly>%
            </div>
        </div>
        
        <div class="split-total">
            <span>Total Split</span>
            <strong>100%</strong>
        </div>
        
        <div class="add-collaborator">
            <i class="mdi mdi-plus"></i>
            <span>Add Another Collaborator</span>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer_premium.php'; ?>
