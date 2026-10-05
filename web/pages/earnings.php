<?php
/**
 * HiTune Music Distribution - User Earnings & Royalties
 * Shows royalty data for published releases
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$user = getCurrentUser();
$user_id = $user['id'];

// Get all published releases with track royalties.
// total_earnings is the owner's NET share after collaborator splits (doc §1).
require_once __DIR__ . '/../includes/splits.php';
$releases = $conn->query("SELECT r.*,
    (SELECT SUM(tr.royalty_amount * (100 - COALESCE(sp.given,0)) / 100)
       FROM track_royalties tr
       JOIN release_tracks rt ON tr.track_id = rt.id
       LEFT JOIN (SELECT track_id, SUM(pct) AS given FROM track_splits WHERE status='active' GROUP BY track_id) sp
         ON sp.track_id = tr.track_id
       WHERE rt.release_id = r.id) as total_earnings,
    (SELECT SUM(plays) FROM track_royalties tr JOIN release_tracks rt ON tr.track_id = rt.id WHERE rt.release_id = r.id) as total_plays
    FROM releases r
    WHERE r.user_id = $user_id AND r.status = 'ready'
    ORDER BY r.ready_at DESC");

// Get lifetime stats — net of splits + incoming collaborator shares
$ownStats = $conn->query("SELECT
    SUM(tr.royalty_amount * (100 - COALESCE(sp.given,0)) / 100) as lifetime_earnings,
    SUM(tr.plays) as lifetime_plays,
    COUNT(DISTINCT r.id) as published_releases
    FROM track_royalties tr
    JOIN release_tracks rt ON tr.track_id = rt.id
    JOIN releases r ON rt.release_id = r.id
    LEFT JOIN (SELECT track_id, SUM(pct) AS given FROM track_splits WHERE status='active' GROUP BY track_id) sp
      ON sp.track_id = tr.track_id
    WHERE r.user_id = $user_id");
$lifetime = $ownStats->fetch_assoc();
$incomingStmt = $conn->prepare("SELECT COALESCE(SUM(tr.royalty_amount * s.pct / 100),0) AS inc
    FROM track_splits s JOIN track_royalties tr ON tr.track_id = s.track_id
    WHERE s.status='active' AND s.user_id = ?");
$incomingStmt->bind_param("i", $user_id);
$incomingStmt->execute();
$splitIncoming = (float)($incomingStmt->get_result()->fetch_assoc()['inc'] ?? 0);
$lifetime['lifetime_earnings'] = ($lifetime['lifetime_earnings'] ?? 0) + $splitIncoming;

// Get monthly earnings
$monthly = $conn->query("SELECT 
    DATE_FORMAT(tr.created_at, '%b %Y') as month,
    SUM(tr.royalty_amount) as earnings
    FROM track_royalties tr
    JOIN release_tracks rt ON tr.track_id = rt.id
    JOIN releases r ON rt.release_id = r.id
    WHERE r.user_id = $user_id
    GROUP BY DATE_FORMAT(tr.created_at, '%Y-%m')
    ORDER BY tr.created_at DESC
    LIMIT 6");

$pageTitle = 'My Earnings - HiTune Music Distribution';
include __DIR__ . '/../includes/header_premium.php';
?>

<style>
    .earnings-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 100px 20px 60px;
    }
    .earnings-header {
        margin-bottom: 40px;
    }
    .earnings-header h1 {
        font-size: 36px;
        font-weight: 800;
        background: linear-gradient(135deg, #00d4aa, #00c853);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-bottom: 10px;
    }
    .earnings-header p {
        color: rgba(255,255,255,0.6);
        font-size: 16px;
    }
    
    /* Stats Cards */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
    }
    .stat-card {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 20px;
        padding: 30px;
        text-align: center;
        transition: all 0.3s;
    }
    .stat-card:hover {
        background: rgba(255,255,255,0.05);
        transform: translateY(-5px);
    }
    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        margin: 0 auto 15px;
    }
    .stat-card h3 {
        font-size: 32px;
        font-weight: 700;
        margin-bottom: 5px;
    }
    .stat-card p {
        color: rgba(255,255,255,0.6);
        font-size: 14px;
    }
    
    /* Content Grid */
    .content-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 30px;
    }
    @media (max-width: 900px) {
        .content-grid { grid-template-columns: 1fr; }
    }
    
    /* Section Cards */
    .section-card {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 20px;
        padding: 25px;
        margin-bottom: 25px;
    }
    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    .section-title {
        font-size: 18px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .section-title i {
        color: #00d4aa;
    }
    
    /* Release Item */
    .release-item {
        background: rgba(0,0,0,0.2);
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 15px;
    }
    .release-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 15px;
    }
    .release-info h4 {
        font-size: 16px;
        font-weight: 600;
        margin-bottom: 5px;
    }
    .release-info p {
        font-size: 13px;
        color: rgba(255,255,255,0.5);
    }
    .release-stats {
        display: flex;
        gap: 20px;
    }
    .release-stat {
        text-align: center;
    }
    .release-stat strong {
        display: block;
        font-size: 20px;
        color: #00d4aa;
    }
    .release-stat span {
        font-size: 11px;
        color: rgba(255,255,255,0.5);
    }
    
    /* Track List */
    .track-list {
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid rgba(255,255,255,0.05);
    }
    .track-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid rgba(255,255,255,0.05);
    }
    .track-item:last-child {
        border-bottom: none;
    }
    .track-name {
        font-size: 14px;
    }
    .track-earnings {
        font-size: 14px;
        font-weight: 600;
        color: #00d4aa;
    }
    
    /* Monthly Chart */
    .monthly-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid rgba(255,255,255,0.05);
    }
    .monthly-item:last-child {
        border-bottom: none;
    }
    .month-name {
        font-size: 14px;
    }
    .month-amount {
        font-size: 16px;
        font-weight: 600;
        color: #00d4aa;
    }
    
    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
    }
    .empty-state i {
        font-size: 64px;
        color: rgba(255,255,255,0.2);
        margin-bottom: 20px;
    }
    .empty-state h3 {
        font-size: 20px;
        margin-bottom: 10px;
    }
    .empty-state p {
        color: rgba(255,255,255,0.5);
    }
    
    /* Payout Button */
    .btn-payout {
        width: 100%;
        padding: 15px;
        background: linear-gradient(135deg, #00d4aa, #00c853);
        border: none;
        border-radius: 12px;
        color: #000;
        font-weight: 700;
        font-size: 15px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        margin-top: 20px;
    }
    .payout-info {
        background: rgba(0,212,170,0.1);
        border: 1px solid rgba(0,212,170,0.2);
        border-radius: 12px;
        padding: 20px;
        margin-top: 20px;
    }
    .payout-info h4 {
        font-size: 14px;
        margin-bottom: 10px;
        color: #00d4aa;
    }
    .payout-info p {
        font-size: 13px;
        color: rgba(255,255,255,0.6);
        line-height: 1.5;
    }
</style>

<div class="earnings-container">
    <div class="earnings-header">
        <h1>My Earnings</h1>
        <p>Track your music revenue and royalties from all platforms</p>
    </div>

    <!-- Lifetime Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(0,212,170,0.2); color: #00d4aa;">
                <i class="mdi mdi-currency-usd"></i>
            </div>
            <h3 style="color: #00d4aa;">$<?php echo number_format($lifetime['lifetime_earnings'] ?? 0, 2); ?></h3>
            <p>Lifetime Earnings</p>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(79,172,254,0.2); color: #4facfe;">
                <i class="mdi mdi-play-circle"></i>
            </div>
            <h3 style="color: #4facfe;"><?php echo number_format($lifetime['lifetime_plays'] ?? 0); ?></h3>
            <p>Total Plays/Streams</p>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(255,193,7,0.2); color: #ffc107;">
                <i class="mdi mdi-album"></i>
            </div>
            <h3 style="color: #ffc107;"><?php echo $lifetime['published_releases'] ?? 0; ?></h3>
            <p>Published Releases</p>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(0,183,255,0.2); color: #00b7ff;">
                <i class="mdi mdi-wallet"></i>
            </div>
            <h3 style="color: #00b7ff;">$0.00</h3>
            <p>Available for Payout</p>
        </div>
    </div>

    <?php if ($lifetime['published_releases'] > 0): ?>
    <div class="content-grid">
        <!-- Left Column - Releases -->
        <div class="releases-column">
            <div class="section-card">
                <div class="section-header">
                    <h3 class="section-title">
                        <i class="mdi mdi-music"></i>
                        Release Earnings
                    </h3>
                </div>
                
                <?php while ($release = $releases->fetch_assoc()): 
                    // Get tracks for this release
                    $tracks_result = $conn->query("SELECT rt.*, 
                        (SELECT SUM(royalty_amount) FROM track_royalties WHERE track_id = rt.id) as track_earnings,
                        (SELECT SUM(plays) FROM track_royalties WHERE track_id = rt.id) as track_plays
                        FROM release_tracks rt 
                        WHERE rt.release_id = {$release['id']} 
                        ORDER BY rt.track_number");
                ?>
                <div class="release-item">
                    <div class="release-header">
                        <div class="release-info">
                            <h4><?php echo htmlspecialchars($release['title']); ?></h4>
                            <p>Published <?php echo date('M j, Y', strtotime($release['ready_at'])); ?></p>
                        </div>
                        <div class="release-stats">
                            <div class="release-stat">
                                <strong><?php echo number_format($release['total_plays'] ?? 0); ?></strong>
                                <span>Plays</span>
                            </div>
                            <div class="release-stat">
                                <strong>$<?php echo number_format($release['total_earnings'] ?? 0, 2); ?></strong>
                                <span>Earned</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="track-list">
                        <?php while ($track = $tracks_result->fetch_assoc()): 
                            if (($track['track_earnings'] ?? 0) > 0 || ($track['track_plays'] ?? 0) > 0):
                        ?>
                        <div class="track-item">
                            <span class="track-name">
                                <span class="mdi mdi-music-note" style="color: rgba(255,255,255,0.4); margin-right: 8px;"></span>
                                <?php echo htmlspecialchars($track['song_title']); ?>
                            </span>
                            <span class="track-earnings">
                                <?php echo number_format($track['track_plays'] ?? 0); ?> plays • 
                                $<?php echo number_format($track['track_earnings'] ?? 0, 2); ?>
                            </span>
                        </div>
                        <?php endif; endwhile; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Right Column - Monthly Stats -->
        <div class="sidebar-column">
            <div class="section-card">
                <div class="section-header">
                    <h3 class="section-title">
                        <i class="mdi mdi-calendar-text"></i>
                        Monthly Earnings
                    </h3>
                </div>
                
                <?php if ($monthly->num_rows > 0): 
                    while ($month = $monthly->fetch_assoc()): ?>
                <div class="monthly-item">
                    <span class="month-name"><?php echo $month['month']; ?></span>
                    <span class="month-amount">$<?php echo number_format($month['earnings'], 2); ?></span>
                </div>
                <?php endwhile; else: ?>
                <p style="text-align: center; color: rgba(255,255,255,0.5); padding: 20px;">
                    No earnings data yet
                </p>
                <?php endif; ?>
            </div>
            
            <div class="payout-info">
                <h4><i class="mdi mdi-information"></i> Payout Information</h4>
                <p>
                    Royalties are calculated based on actual streams and downloads across all platforms. 
                    Minimum payout threshold is $50. Payments are processed monthly.
                </p>
            </div>
            
            <button class="btn-payout" disabled style="opacity: 0.5;">
                <i class="mdi mdi-bank-transfer"></i>
                Request Payout ($<?php echo number_format($lifetime['lifetime_earnings'] ?? 0, 2); ?>)
            </button>
        </div>
    </div>
    <?php else: ?>
    <!-- Empty State -->
    <div class="empty-state">
        <i class="mdi mdi-music-off"></i>
        <h3>No Published Releases Yet</h3>
        <p>Your earnings will appear here once your music is published and starts generating streams.</p>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer_premium.php'; ?>
