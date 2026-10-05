<?php
/**
 * web.hitune.in - Stores Page
 * TuneCore-like stores list feature
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Stores - HiTune Music Distribution';
include __DIR__ . '/../includes/header_premium.php';
?>

<style>
    .stores-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 120px 40px 80px;
    }
    .stores-container::before {
        content: '';
        position: fixed;
        width: 600px;
        height: 600px;
        background: radial-gradient(circle, rgba(118, 75, 162, 0.12) 0%, transparent 60%);
        top: -200px;
        right: -200px;
        animation: bgGlow 8s ease-in-out infinite;
        z-index: -1;
        pointer-events: none;
    }
    @keyframes bgGlow {
        0%, 100% { transform: scale(1); opacity: 0.5; }
        50% { transform: scale(1.2); opacity: 0.8; }
    }
    .stores-header {
        text-align: center;
        margin-bottom: 60px;
    }
    .stores-header h1 {
        font-size: 48px;
        font-weight: 800;
        margin-bottom: 20px;
        background: linear-gradient(135deg, #00b7ff, #8b5cf6);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .stores-header p {
        color: rgba(255, 255, 255, 0.7);
        font-size: 18px;
        max-width: 600px;
        margin: 0 auto;
    }
    .stores-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 25px;
        margin-bottom: 60px;
    }
    .store-card {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 20px;
        padding: 30px 20px;
        text-align: center;
        transition: all 0.3s;
        cursor: pointer;
    }
    .store-card:hover {
        background: rgba(255, 255, 255, 0.1);
        transform: translateY(-8px);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
        border-color: rgba(0, 183, 255, 0.5);
    }
    .store-icon {
        width: 80px;
        height: 80px;
        margin: 0 auto 20px;
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 36px;
        background: rgba(255, 255, 255, 0.1);
    }
    .store-name {
        font-size: 16px;
        font-weight: 600;
        margin-bottom: 8px;
    }
    .store-status {
        font-size: 12px;
        color: #38ef7d;
        font-weight: 500;
    }
    .section-title {
        font-size: 32px;
        font-weight: 700;
        margin-bottom: 30px;
        text-align: center;
    }
</style>

<div class="stores-container">
    <div class="stores-header">
        <h1>Distribution Stores</h1>
        <p>Your music distributed to 150+ streaming platforms worldwide</p>
    </div>

    <?php
// All 150+ platforms with icons and colors
$platforms = [
    // Major Streaming (Tier 1)
    'Major Streaming' => [
        ['name' => 'Spotify', 'icon' => 'spotify', 'color' => 'linear-gradient(135deg, #1DB954, #191414)'],
        ['name' => 'Apple Music', 'icon' => 'apple', 'color' => 'linear-gradient(135deg, #FA243C, #000000)'],
        ['name' => 'YouTube Music', 'icon' => 'youtube', 'color' => 'linear-gradient(135deg, #FF0000, #282828)'],
        ['name' => 'Amazon Music', 'icon' => 'amazon', 'color' => 'linear-gradient(135deg, #00C6FF, #0072FF)'],
        ['name' => 'Tidal', 'icon' => 'music', 'color' => 'linear-gradient(135deg, #000000, #FFFFFF)'],
        ['name' => 'Deezer', 'icon' => 'music-box', 'color' => 'linear-gradient(135deg, #00b7ff, #8b5cf6)'],
        ['name' => 'Pandora', 'icon' => 'radio', 'color' => 'linear-gradient(135deg, #005483, #3668FF)'],
        ['name' => 'SoundCloud', 'icon' => 'soundcloud', 'color' => 'linear-gradient(135deg, #FF5500, #FF8800)'],
    ],
    // Social & Short Video
    'Social Media & Short Video' => [
        ['name' => 'TikTok', 'icon' => 'music-note', 'color' => 'linear-gradient(135deg, #000000, #FF0050)'],
        ['name' => 'Instagram', 'icon' => 'instagram', 'color' => 'linear-gradient(45deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888)'],
        ['name' => 'Facebook', 'icon' => 'facebook', 'color' => 'linear-gradient(135deg, #1877F2, #4267B2)'],
        ['name' => 'Snapchat', 'icon' => 'snapchat', 'color' => 'linear-gradient(135deg, #FFFC00, #000000)'],
        ['name' => 'Triller', 'icon' => 'video', 'color' => 'linear-gradient(135deg, #FF0000, #000000)'],
        ['name' => 'Likee', 'icon' => 'heart', 'color' => 'linear-gradient(135deg, #FF0050, #00D4FF)'],
        ['name' => 'Trebel', 'icon' => 'download', 'color' => 'linear-gradient(135deg, #00C853, #00D4AA)'],
    ],
    // Asian Streaming
    'Asian Streaming' => [
        ['name' => 'JioSaavn', 'icon' => 'music', 'color' => 'linear-gradient(135deg, #2BC47C, #1E8C5A)'],
        ['name' => 'Gaana', 'icon' => 'music-circle', 'color' => 'linear-gradient(135deg, #E91E63, #C2185B)'],
        ['name' => 'Wynk Music', 'icon' => 'music-note', 'color' => 'linear-gradient(135deg, #FF6B00, #FF8E00)'],
        ['name' => 'Hungama', 'icon' => 'play-circle', 'color' => 'linear-gradient(135deg, #FF4081, #C51162)'],
        ['name' => 'QQ Music', 'icon' => 'qqchat', 'color' => 'linear-gradient(135deg, #31C27C, #00A651)'],
        ['name' => 'NetEase Cloud', 'icon' => 'cloud-music', 'color' => 'linear-gradient(135deg, #C20C0C, #8B0000)'],
        ['name' => 'KuGou', 'icon' => 'dog', 'color' => 'linear-gradient(135deg, #00BFFF, #1E90FF)'],
        ['name' => 'Joox', 'icon' => 'music-box-outline', 'color' => 'linear-gradient(135deg, #00B894, #00CEC9)'],
        ['name' => 'AWA', 'icon' => 'waves', 'color' => 'linear-gradient(135deg, #00BCD4, #0097A7)'],
        ['name' => 'Line Music', 'icon' => 'chat', 'color' => 'linear-gradient(135deg, #00B900, #00E676)'],
        ['name' => 'Recochoku', 'icon' => 'music-note-eighth', 'color' => 'linear-gradient(135deg, #FF1744, #D50000)'],
        ['name' => 'Mora', 'icon' => 'album', 'color' => 'linear-gradient(135deg, #7C4DFF, #651FFF)'],
    ],
    // European Streaming
    'European Streaming' => [
        ['name' => 'Anghami', 'icon' => 'music-note', 'color' => 'linear-gradient(135deg, #FF4081, #7C4DFF)'],
        ['name' => 'Shazam', 'icon' => 'shuffle-variant', 'color' => 'linear-gradient(135deg, #0088FF, #0055FF)'],
        ['name' => '7digital', 'icon' => 'numeric-7-box', 'color' => 'linear-gradient(135deg, #00b7ff, #8b5cf6)'],
        ['name' => '8tracks', 'icon' => 'numeric-8-box', 'color' => 'linear-gradient(135deg, #00b7ff, #8b5cf6)'],
        ['name' => 'Akazoo', 'icon' => 'music-circle-outline', 'color' => 'linear-gradient(135deg, #00BCD4, #0097A7)'],
        ['name' => 'Beatport', 'icon' => 'vinyl', 'color' => 'linear-gradient(135deg, #01C4FF, #00A8E8)'],
        ['name' => 'Boomplay', 'icon' => 'boombox', 'color' => 'linear-gradient(135deg, #FF6B00, #FF8E00)'],
        ['name' => 'Claro Musica', 'icon' => 'cellphone', 'color' => 'linear-gradient(135deg, #C41E3A, #8B0000)'],
        ['name' => 'Napster', 'icon' => 'cat', 'color' => 'linear-gradient(135deg, #000000, #333333)'],
        ['name' => 'Qobuz', 'icon' => 'quality-high', 'color' => 'linear-gradient(135deg, #1A1A1A, #2C2C2C)'],
    ],
    // Regional & Niche
    'Regional Platforms' => [
        ['name' => 'Audiomack', 'icon' => 'headphones', 'color' => 'linear-gradient(135deg, #FF9500, #FF5E3A)'],
        ['name' => 'Bandcamp', 'icon' => 'bandcamp', 'color' => 'linear-gradient(135deg, #1DA0C3, #0E7A99)'],
        ['name' => 'iHeartRadio', 'icon' => 'heart-circle', 'color' => 'linear-gradient(135deg, #C6002B, #8B0019)'],
        ['name' => 'Last.fm', 'icon' => 'lastfm', 'color' => 'linear-gradient(135deg, #D51007, #B50005)'],
        ['name' => 'LiveXLive', 'icon' => 'broadcast', 'color' => 'linear-gradient(135deg, #00D4AA, #00B894)'],
        ['name' => 'Mixcloud', 'icon' => 'mixcloud', 'color' => 'linear-gradient(135deg, #5000FF, #3140FF)'],
        ['name' => 'Slacker', 'icon' => 'sleep', 'color' => 'linear-gradient(135deg, #00b7ff, #8b5cf6)'],
        ['name' => 'Spinrilla', 'icon' => 'rotate-3d-variant', 'color' => 'linear-gradient(135deg, #00D4FF, #00B8D4)'],
        ['name' => 'Twitch', 'icon' => 'twitch', 'color' => 'linear-gradient(135deg, #9146FF, #772CE8)'],
        ['name' => 'Vimeo', 'icon' => 'vimeo', 'color' => 'linear-gradient(135deg, #1AB7EA, #0A9AC9)'],
        ['name' => 'Yandex', 'icon' => 'alpha-y-box', 'color' => 'linear-gradient(135deg, #FC3F1D, #D9391B)'],
        ['name' => 'Zvooq', 'icon' => 'music-note-whole', 'color' => 'linear-gradient(135deg, #00b7ff, #8b5cf6)'],
    ],
    // Download Stores
    'Download Stores' => [
        ['name' => 'iTunes', 'icon' => 'itunes', 'color' => 'linear-gradient(135deg, #007AFF, #5856D6)'],
        ['name' => 'Amazon MP3', 'icon' => 'shopping-music', 'color' => 'linear-gradient(135deg, #FF9900, #0090d9)'],
        ['name' => 'Google Play', 'icon' => 'google-play', 'color' => 'linear-gradient(135deg, #4285F4, #34A853)'],
        ['name' => 'eMusic', 'icon' => 'music-note', 'color' => 'linear-gradient(135deg, #00D4AA, #00C853)'],
        ['name' => 'MediaNet', 'icon' => 'ethernet', 'color' => 'linear-gradient(135deg, #00BCD4, #0097A7)'],
        ['name' => 'Tradebit', 'icon' => 'swap-horizontal', 'color' => 'linear-gradient(135deg, #00b7ff, #8b5cf6)'],
        ['name' => 'CD Baby', 'icon' => 'disc', 'color' => 'linear-gradient(135deg, #00B894, #00CEC9)'],
    ],
    // Telecom & Carrier
    'Telecom Partners' => [
        ['name' => 'Vodafone', 'icon' => 'signal', 'color' => 'linear-gradient(135deg, #E60000, #B30000)'],
        ['name' => 'Vivo', 'icon' => 'cellphone-check', 'color' => 'linear-gradient(135deg, #660099, #9933CC)'],
        ['name' => 'TIM', 'icon' => 'cellphone-message', 'color' => 'linear-gradient(135deg, #003399, #0066CC)'],
        ['name' => 'Sprint', 'icon' => 'run-fast', 'color' => 'linear-gradient(135deg, #FFDD05, #FFCC00)'],
        ['name' => 'T-Mobile', 'icon' => 'signal-variant', 'color' => 'linear-gradient(135deg, #E20074, #B3005C)'],
        ['name' => 'Airtel', 'icon' => 'tower-cell', 'color' => 'linear-gradient(135deg, #FF0000, #CC0000)'],
        ['name' => 'MTN', 'icon' => 'cellphone-wireless', 'color' => 'linear-gradient(135deg, #FFCC00, #FF9900)'],
    ],
    // TV & Media
    'TV & Media' => [
        ['name' => 'Roku', 'icon' => 'set-top-box', 'color' => 'linear-gradient(135deg, #662D91, #4A1C6F)'],
        ['name' => 'Fire TV', 'icon' => 'fire', 'color' => 'linear-gradient(135deg, #FF9900, #0090d9)'],
        ['name' => 'Samsung TV', 'icon' => 'television', 'color' => 'linear-gradient(135deg, #1428A0, #0E1F7A)'],
        ['name' => 'LG TV', 'icon' => 'monitor', 'color' => 'linear-gradient(135deg, #A50034, #7A0027)'],
        ['name' => 'Sony Music', 'icon' => 'alpha-s-box', 'color' => 'linear-gradient(135deg, #000000, #333333)'],
        ['name' => 'Universal', 'icon' => 'alpha-u-box', 'color' => 'linear-gradient(135deg, #000000, #1A1A1A)'],
        ['name' => 'Warner', 'icon' => 'alpha-w-box', 'color' => 'linear-gradient(135deg, #000000, #2C2C2C)'],
    ],
    // More International
    'More International' => [
        ['name' => 'Pandora Premium', 'icon' => 'crown', 'color' => 'linear-gradient(135deg, #3668FF, #005483)'],
        ['name' => 'iMusica', 'icon' => 'music-clef-treble', 'color' => 'linear-gradient(135deg, #00b7ff, #8b5cf6)'],
        ['name' => 'KDigital', 'icon' => 'music-rest-quarter', 'color' => 'linear-gradient(135deg, #00D4AA, #00C853)'],
        ['name' => 'Kuack', 'icon' => 'duck', 'color' => 'linear-gradient(135deg, #FFD700, #FFC107)'],
        ['name' => 'Musicload', 'icon' => 'download-circle', 'color' => 'linear-gradient(135deg, #00b7ff, #8b5cf6)'],
        ['name' => 'Neurotic Media', 'icon' => 'brain', 'color' => 'linear-gradient(135deg, #9C27B0, #7B1FA2)'],
        ['name' => 'NMusic', 'icon' => 'music-note-sixteenth', 'color' => 'linear-gradient(135deg, #FF4081, #C2185B)'],
        ['name' => 'OnDevice', 'icon' => 'cellphone', 'color' => 'linear-gradient(135deg, #607D8B, #455A64)'],
        ['name' => 'Pretzel', 'icon' => 'food', 'color' => 'linear-gradient(135deg, #8D6E63, #6D4C41)'],
        ['name' => 'Sonos', 'icon' => 'speaker', 'color' => 'linear-gradient(135deg, #000000, #333333)'],
        ['name' => 'Stingray', 'icon' => 'ray-start-arrow', 'color' => 'linear-gradient(135deg, #FF6B00, #FF8E00)'],
        ['name' => 'Tango', 'icon' => 'human-male-female', 'color' => 'linear-gradient(135deg, #00D4AA, #00C853)'],
    ],
];

$total_stores = 0;
foreach ($platforms as $category => $items) {
    $total_stores += count($items);
}
?>

    <div class="stores-count" style="text-align: center; margin-bottom: 40px; padding: 20px; background: rgba(0,212,170,0.1); border-radius: 15px;">
        <h2 style="font-size: 48px; font-weight: 800; color: #00d4aa; margin-bottom: 10px;"><?php echo $total_stores; ?>+</h2>
        <p style="font-size: 18px; color: rgba(255,255,255,0.7);">Platforms Worldwide</p>
    </div>

<?php foreach ($platforms as $category => $stores): ?>
    <h2 class="section-title"><?php echo $category; ?> <span style="font-size: 14px; color: rgba(255,255,255,0.5);">(<?php echo count($stores); ?>)</span></h2>
    <div class="stores-grid">
        <?php foreach ($stores as $store): ?>
        <div class="store-card">
            <div class="store-icon" style="background: <?php echo $store['color']; ?>;">
                <?php if ($store['name'] === 'TikTok'): ?>
                    <svg viewBox="0 0 24 24" width="36" height="36" fill="currentColor">
                        <path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.1z"/>
                    </svg>
                <?php else: ?>
                    <i class="mdi mdi-<?php echo $store['icon']; ?>"></i>
                <?php endif; ?>
            </div>
            <div class="store-name"><?php echo $store['name']; ?></div>
            <div class="store-status"><i class="mdi mdi-check-circle" style="font-size: 10px;"></i> Active</div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>
</div>

<?php include __DIR__ . '/../includes/footer_premium.php'; ?>
