<?php
/**
 * HiTune Music Distribution - Publishing Page
 */
$pageTitle = 'Publishing - HiTune Music Distribution';
include __DIR__ . '/../includes/header_premium.php';
?>
<style>
    .pub-hero { padding: 120px 30px 80px; background: radial-gradient(ellipse at center, #1a1a2e 0%, #0a0a0a 100%); }
    .pub-hero-container { max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: center; }
    .pub-hero-content h1 { font-size: clamp(32px, 5vw, 52px); font-weight: 800; line-height: 1.2; margin-bottom: 25px; }
    .pub-hero-content p { font-size: 18px; color: rgba(255,255,255,0.7); line-height: 1.8; margin-bottom: 30px; }
    .pub-cta { display: inline-flex; align-items: center; gap: 10px; padding: 16px 35px; background: linear-gradient(135deg, #00b7ff, #8b5cf6); color: #fff; text-decoration: none; border-radius: 30px; font-weight: 600; transition: all 0.3s; }
    .pub-cta:hover { transform: translateY(-3px); box-shadow: 0 15px 40px rgba(0,183,255,0.4); }
    .pub-video { background: rgba(255,255,255,0.05); border-radius: 20px; overflow: hidden; aspect-ratio: 16/10; display: flex; align-items: center; justify-content: center; position: relative; }
    .pub-video::before { content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: linear-gradient(135deg, rgba(0,183,255,0.2), rgba(139,92,246,0.2));         pointer-events: none;
    }
    .play-btn { width: 80px; height: 80px; background: #ff0000; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 40px; position: relative; z-index: 1; cursor: pointer; transition: all 0.3s; }
    .play-btn:hover { transform: scale(1.1); }
    .play-btn i { color: #fff; margin-left: 5px; }
    .what-is { padding: 100px 30px; background: #0a0a0a; text-align: center; }
    .what-is h2 { font-size: 48px; font-weight: 800; margin-bottom: 30px; }
    .what-is h2 span { color: #00b7ff; }
    .what-is p { max-width: 800px; margin: 0 auto 40px; font-size: 18px; color: rgba(255,255,255,0.7); line-height: 1.8; }
    .songwriters { padding: 100px 30px; background: linear-gradient(180deg, #0a0a0a 0%, #111 50%, #0a0a0a 100%); }
    .sw-container { max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: 1fr 1fr; gap: 80px; align-items: center; }
    .sw-content h2 { font-size: 42px; font-weight: 800; margin-bottom: 25px; line-height: 1.2; }
    .sw-content h2 span { color: #00b7ff; }
    .sw-content p { font-size: 16px; color: rgba(255,255,255,0.7); line-height: 1.8; margin-bottom: 20px; }
    .visualizer { background: rgba(255,255,255,0.05); border-radius: 20px; padding: 60px 40px; text-align: center; }
    .visualizer-wave { display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 30px; }
    .wave-bar { width: 8px; background: linear-gradient(180deg, #00b7ff, #8b5cf6); border-radius: 4px; animation: wave 1.5s ease-in-out infinite; }
    .wave-bar:nth-child(1) { height: 30px; animation-delay: 0s; }
    .wave-bar:nth-child(2) { height: 50px; animation-delay: 0.1s; }
    .wave-bar:nth-child(3) { height: 70px; animation-delay: 0.2s; }
    .wave-bar:nth-child(4) { height: 40px; animation-delay: 0.3s; }
    .wave-bar:nth-child(5) { height: 80px; animation-delay: 0.4s; }
    .wave-bar:nth-child(6) { height: 60px; animation-delay: 0.5s; }
    .wave-bar:nth-child(7) { height: 45px; animation-delay: 0.6s; }
    .wave-bar:nth-child(8) { height: 55px; animation-delay: 0.7s; }
    .wave-bar:nth-child(9) { height: 35px; animation-delay: 0.8s; }
    .wave-bar:nth-child(10) { height: 65px; animation-delay: 0.9s; }
    .wave-bar:nth-child(11) { height: 50px; animation-delay: 1s; }
    @keyframes wave { 0%, 100% { transform: scaleY(1); } 50% { transform: scaleY(1.2); } }
    .benefits { padding: 100px 30px; background: #0a0a0a; }
    .benefits-container { max-width: 1200px; margin: 0 auto; }
    .benefits h2 { text-align: center; font-size: 36px; font-weight: 800; margin-bottom: 60px; }
    .benefits-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 30px; }
    .benefit-card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 35px; transition: all 0.3s; }
    .benefit-card:hover { transform: translateY(-5px); background: rgba(255,255,255,0.05); }
    .benefit-card i { font-size: 40px; color: #00b7ff; margin-bottom: 20px; }
    .benefit-card h3 { font-size: 20px; font-weight: 700; margin-bottom: 12px; }
    .benefit-card p { font-size: 15px; color: rgba(255,255,255,0.6); line-height: 1.6; }
    @media (max-width: 768px) { .pub-hero-container, .sw-container { grid-template-columns: 1fr; gap: 40px; } }
</style>

<section class="pub-hero">
    <div class="pub-hero-container">
        <div class="pub-hero-content">
            <h1>What Is <span class="gradient-text">Music Publishing?</span></h1>
            <p>Music distribution and music publishing are two totally different things. Music publishers collect the royalties that don't come from distribution. Are you collecting yours?</p>
            <a href="/index.php?q=pricing" class="pub-cta"><i class="mdi mdi-cash-multiple"></i> Collect More Royalties</a>
        </div>
        <div class="pub-video">
            <div class="play-btn"><i class="mdi mdi-play"></i></div>
            <div style="position: absolute; bottom: 20px; right: 20px; font-size: 14px; color: rgba(255,255,255,0.6);">Watch on <i class="mdi mdi-youtube" style="color: #ff0000;"></i> YouTube</div>
        </div>
    </div>
</section>

<section class="what-is">
    <h2>What Is <span>Music Publishing</span>?</h2>
    <p>Music distribution gets your music on streaming platforms. Music publishing ensures you get paid when your music is played on radio, TV, films, in restaurants, and more. You could be missing out on significant royalty income if you're not registered with a publisher.</p>
    <a href="/index.php?q=pricing" class="pub-cta">Learn More</a>
</section>

<section class="songwriters">
    <div class="sw-container">
        <div class="sw-content">
            <h2>Songwriters earn more <span>$$</span> with HiTune Publishing.</h2>
            <p>No more third-party providers or having to manually split royalties between artists. You have the power to fully customize the percent paid to each collaborator from the streaming, download royalties on any track or album.</p>
            <p>Create new splits, edit existing splits, and accept royalties from tracks you've worked on with other artists & producers. All paid out directly to your HiTune account.</p>
            <a href="/index.php?q=splits" class="pub-cta"><i class="mdi mdi-arrow-right"></i> Get Started</a>
        </div>
        <div class="visualizer">
            <div class="visualizer-wave">
                <div class="wave-bar"></div><div class="wave-bar"></div><div class="wave-bar"></div><div class="wave-bar"></div><div class="wave-bar"></div><div class="wave-bar"></div><div class="wave-bar"></div><div class="wave-bar"></div><div class="wave-bar"></div><div class="wave-bar"></div><div class="wave-bar"></div>
            </div>
            <h4>Your Song</h4>
            <p>Earning royalties worldwide</p>
        </div>
    </div>
</section>

<section class="benefits">
    <div class="benefits-container">
        <h2>HiTune Publishing <span class="gradient-text">Benefits</span></h2>
        <div class="benefits-grid">
            <div class="benefit-card"><i class="mdi mdi-earth"></i><h3>Global Collection</h3><p>We collect your publishing royalties from 100+ countries worldwide, ensuring you never miss a payment.</p></div>
            <div class="benefit-card"><i class="mdi mdi-television-classic"></i><h3>TV & Film Sync</h3><p>Get your music placed in movies, TV shows, commercials, and video games. Access our sync licensing network.</p></div>
            <div class="benefit-card"><i class="mdi mdi-radio"></i><h3>Radio Play Royalties</h3><p>Collect performance royalties every time your song plays on radio stations around the world.</p></div>
            <div class="benefit-card"><i class="mdi mdi-store"></i><h3>Venue Performance</h3><p>Earn when your music is played in bars, restaurants, clubs, and retail stores. We track it all.</p></div>
            <div class="benefit-card"><i class="mdi mdi-account-group"></i><h3>Collaborator Splits</h3><p>Automatically split publishing royalties with co-writers, producers, and band members.</p></div>
            <div class="benefit-card"><i class="mdi mdi-chart-bar"></i><h3>Detailed Reports</h3><p>See exactly where your music is being played and how much you're earning with detailed analytics.</p></div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer_premium.php'; ?>
