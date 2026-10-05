<?php
/**
 * HiTune Music Distribution - Royalties Page
 * Shows all royalty entries for the current user's tracks.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$user   = getCurrentUser();
$userId = $user['id'];

// Fetch all track_royalties for current user's tracks
$stmt = $conn->prepare("
    SELECT tr.id, rt.song_title AS track_title, r.title AS release_title,
           tr.platform, tr.reporting_period, tr.plays, tr.revenue, tr.royalty_amount,
           tr.created_at
    FROM track_royalties tr
    JOIN release_tracks rt ON tr.track_id = rt.id
    JOIN releases r ON rt.release_id = r.id
    WHERE r.user_id = ?
    ORDER BY tr.reporting_period DESC, tr.created_at DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$royaltiesResult = $stmt->get_result();

// Compute total royalties
$totalRoyalties = 0;
$royaltiesRows  = [];
while ($row = $royaltiesResult->fetch_assoc()) {
    $totalRoyalties += (float) $row['royalty_amount'];
    $royaltiesRows[] = $row;
}

// Compute approved payouts
$stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as withdrawn FROM payouts WHERE user_id = ? AND status = 'approved'");
$stmt->bind_param("i", $userId);
$stmt->execute();
$withdrawn = (float) ($stmt->get_result()->fetch_assoc()['withdrawn'] ?? 0);

$availableBalance = $totalRoyalties - $withdrawn;

$pageTitle       = 'Royalties - HiTune Music Distribution';
$metaDescription = 'View your royalty earnings from all streaming platforms on HiTune Music Distribution.';
$canonicalUrl    = 'https://web.hitune.in/index.php?q=royalties';
$path            = 'royalties';
include __DIR__ . '/../includes/header_premium.php';
?>

<style>
    .royalties-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 120px 40px 80px;
    }
    .royalties-container::before {
        content: '';
        position: fixed;
        width: 600px;
        height: 600px;
        background: radial-gradient(circle, rgba(0, 200, 83, 0.12) 0%, transparent 60%);
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
    .royalties-header { margin-bottom: 40px; }
    .royalties-header h1 {
        font-size: 36px;
        font-weight: 800;
        margin-bottom: 10px;
        background: linear-gradient(135deg, #00d4aa, #00c853);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .royalties-header p { color: rgba(255,255,255,0.6); font-size: 16px; }

    /* Balance card */
    .balance-hero {
        background: linear-gradient(135deg, rgba(0,200,83,0.15), rgba(0,212,170,0.1));
        border: 1px solid rgba(0,200,83,0.3);
        border-radius: 24px;
        padding: 40px;
        text-align: center;
        margin-bottom: 35px;
    }
    .balance-hero .balance-label {
        font-size: 14px;
        color: rgba(255,255,255,0.6);
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 10px;
    }
    .balance-hero .balance-amount {
        font-size: 56px;
        font-weight: 800;
        color: #38ef7d;
        margin-bottom: 8px;
    }
    .balance-hero .balance-sub {
        font-size: 14px;
        color: rgba(255,255,255,0.5);
        display: flex;
        justify-content: center;
        gap: 30px;
        margin-top: 15px;
    }
    .balance-hero .balance-sub span strong {
        display: block;
        font-size: 18px;
        color: #fff;
    }

    /* Table */
    .royalty-table-wrap {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 20px;
        overflow: hidden;
    }
    .royalty-table-wrap table {
        width: 100%;
        border-collapse: collapse;
    }
    .royalty-table-wrap th {
        background: rgba(255,255,255,0.06);
        padding: 16px 20px;
        text-align: left;
        font-size: 12px;
        font-weight: 600;
        color: rgba(255,255,255,0.5);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1px solid rgba(255,255,255,0.08);
    }
    .royalty-table-wrap td {
        padding: 16px 20px;
        border-bottom: 1px solid rgba(255,255,255,0.05);
        font-size: 14px;
        color: rgba(255,255,255,0.8);
    }
    .royalty-table-wrap tr:last-child td { border-bottom: none; }
    .royalty-table-wrap tr:hover td { background: rgba(255,255,255,0.02); }

    .amount-cell { font-weight: 700; color: #38ef7d; }
    .platform-tag {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 10px;
        background: rgba(102,126,234,0.15);
        border: 1px solid rgba(102,126,234,0.3);
        border-radius: 20px;
        font-size: 12px;
        color: #667eea;
    }

    /* Empty state */
    .empty-state {
        text-align: center;
        padding: 80px 20px;
    }
    .empty-state i { font-size: 56px; color: rgba(255,255,255,0.2); display: block; margin-bottom: 20px; }
    .empty-state h3 { font-size: 20px; margin-bottom: 10px; }
    .empty-state p { color: rgba(255,255,255,0.5); font-size: 14px; }
</style>

<div class="royalties-container">
    <div class="royalties-header">
        <h1>Royalty Reports</h1>
        <p>Your earnings from all streaming platforms</p>
    </div>

    <!-- Balance Hero -->
    <div class="balance-hero">
        <div class="balance-label">Available Balance</div>
        <div class="balance-amount">₹<?php echo number_format($availableBalance, 2); ?></div>
        <div class="balance-sub">
            <span><strong>₹<?php echo number_format($totalRoyalties, 2); ?></strong>Total Royalties</span>
            <span><strong>₹<?php echo number_format($withdrawn, 2); ?></strong>Withdrawn</span>
        </div>
        <?php if ($availableBalance > 0): ?>
        <a href="/index.php?q=payouts" style="display: inline-flex; align-items: center; gap: 8px; margin-top: 20px; padding: 12px 28px; background: linear-gradient(135deg, #00c853, #00e676); color: #000; border-radius: 50px; font-size: 14px; font-weight: 700; text-decoration: none;">
            <i class="mdi mdi-cash-multiple"></i> Request Payout
        </a>
        <?php endif; ?>
    </div>

    <!-- Royalties Table -->
    <?php if (!empty($royaltiesRows)): ?>
    <div class="royalty-table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Track Title</th>
                    <th>Release</th>
                    <th>Platform</th>
                    <th>Reporting Period</th>
                    <th>Plays</th>
                    <th>Revenue</th>
                    <th>Royalty Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($royaltiesRows as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['track_title']); ?></td>
                    <td><?php echo htmlspecialchars($row['release_title']); ?></td>
                    <td><span class="platform-tag"><i class="mdi mdi-music-circle"></i><?php echo htmlspecialchars($row['platform']); ?></span></td>
                    <td><?php echo htmlspecialchars($row['reporting_period'] ?? '—'); ?></td>
                    <td><?php echo number_format((int)$row['plays']); ?></td>
                    <td>₹<?php echo number_format((float)$row['revenue'], 2); ?></td>
                    <td class="amount-cell">₹<?php echo number_format((float)$row['royalty_amount'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="royalty-table-wrap">
        <div class="empty-state">
            <i class="mdi mdi-cash-remove"></i>
            <h3>No royalties yet</h3>
            <p>Your royalty earnings will appear here once your releases start generating streams.</p>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer_premium.php'; ?>
