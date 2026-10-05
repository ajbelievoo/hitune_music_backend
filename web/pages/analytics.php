<?php
/**
 * HiTune Music Distribution - Analytics Page
 * Lifetime stats, charts, platform breakdown, release breakdown.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$user   = getCurrentUser();
$userId = $user['id'];

// ── Lifetime stats from track_royalties ──────────────────────────────────────
$stmt = $conn->prepare("
    SELECT
        COALESCE(SUM(tr.plays), 0)         AS lifetime_streams,
        COALESCE(SUM(tr.revenue), 0)       AS lifetime_revenue,
        COALESCE(SUM(tr.royalty_amount), 0) AS total_royalties
    FROM track_royalties tr
    JOIN release_tracks rt ON tr.track_id = rt.id
    JOIN releases r ON rt.release_id = r.id
    WHERE r.user_id = ?
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$lifetimeStats = $stmt->get_result()->fetch_assoc();
$lifetimeStreams  = (float) ($lifetimeStats['lifetime_streams']  ?? 0);
$lifetimeRevenue = (float) ($lifetimeStats['lifetime_revenue']  ?? 0);
$totalRoyalties  = (float) ($lifetimeStats['total_royalties']   ?? 0);

$hasData = ($lifetimeStreams > 0 || $lifetimeRevenue > 0 || $totalRoyalties > 0);

// ── Monthly streams for last 12 months (line chart) ──────────────────────────
$monthlyStreams  = [];
$monthlyLabels  = [];
for ($i = 11; $i >= 0; $i--) {
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(tr.plays), 0) AS streams
        FROM track_royalties tr
        JOIN release_tracks rt ON tr.track_id = rt.id
        JOIN releases r ON rt.release_id = r.id
        WHERE r.user_id = ?
          AND YEAR(tr.created_at)  = YEAR(DATE_SUB(NOW(), INTERVAL ? MONTH))
          AND MONTH(tr.created_at) = MONTH(DATE_SUB(NOW(), INTERVAL ? MONTH))
    ");
    $stmt->bind_param("iii", $userId, $i, $i);
    $stmt->execute();
    $monthlyStreams[] = (float) ($stmt->get_result()->fetch_assoc()['streams'] ?? 0);
    $monthlyLabels[]  = date('M Y', strtotime("-$i months"));
}

// ── Earnings per release — top 5 (bar chart) ─────────────────────────────────
$stmt = $conn->prepare("
    SELECT r.title,
           COALESCE(SUM(tr.royalty_amount), 0) AS earnings
    FROM releases r
    JOIN release_tracks rt ON rt.release_id = r.id
    JOIN track_royalties tr ON tr.track_id = rt.id
    WHERE r.user_id = ?
    GROUP BY r.id, r.title
    ORDER BY earnings DESC
    LIMIT 5
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$earningsResult = $stmt->get_result();
$releaseLabels   = [];
$releaseEarnings = [];
while ($row = $earningsResult->fetch_assoc()) {
    $releaseLabels[]   = $row['title'];
    $releaseEarnings[] = (float) $row['earnings'];
}

// ── Platform breakdown (GROUP BY platform) ───────────────────────────────────
$stmt = $conn->prepare("
    SELECT tr.platform,
           COALESCE(SUM(tr.plays), 0)         AS streams,
           COALESCE(SUM(tr.revenue), 0)       AS revenue,
           COALESCE(SUM(tr.royalty_amount), 0) AS royalties
    FROM track_royalties tr
    JOIN release_tracks rt ON tr.track_id = rt.id
    JOIN releases r ON rt.release_id = r.id
    WHERE r.user_id = ?
    GROUP BY tr.platform
    ORDER BY streams DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$platformBreakdown = $stmt->get_result();

// ── Release-level breakdown ───────────────────────────────────────────────────
$stmt = $conn->prepare("
    SELECT r.title, r.primary_artist,
           COALESCE(SUM(tr.plays), 0)         AS total_streams,
           COALESCE(SUM(tr.revenue), 0)       AS total_revenue,
           COALESCE(SUM(tr.royalty_amount), 0) AS total_royalties
    FROM releases r
    JOIN release_tracks rt ON rt.release_id = r.id
    JOIN track_royalties tr ON tr.track_id = rt.id
    WHERE r.user_id = ?
    GROUP BY r.id, r.title, r.primary_artist
    ORDER BY total_streams DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$releaseBreakdown = $stmt->get_result();

$loadCharts = true;

$pageTitle       = 'Analytics - HiTune Music Distribution';
$metaDescription = 'Track your music performance, streams, revenue, and royalties across all platforms on HiTune.';
$canonicalUrl    = 'https://web.hitune.in/index.php?q=analytics';
$path            = 'analytics';
include __DIR__ . '/../includes/header_premium.php';
?>

<style>
    .analytics-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 120px 40px 80px;
    }
    .analytics-container::before {
        content: '';
        position: fixed;
        width: 700px;
        height: 700px;
        background: radial-gradient(circle, rgba(102,126,234,0.12) 0%, transparent 60%);
        top: -250px;
        right: -250px;
        animation: bgGlow 8s ease-in-out infinite;
        z-index: -1;
        pointer-events: none;
    }
    @keyframes bgGlow {
        0%, 100% { transform: scale(1); opacity: 0.5; }
        50% { transform: scale(1.2); opacity: 0.8; }
    }

    .analytics-header { margin-bottom: 40px; }
    .analytics-header h1 {
        font-size: 36px;
        font-weight: 800;
        margin-bottom: 10px;
        background: linear-gradient(135deg, #667eea, #764ba2);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .analytics-header p { color: rgba(255,255,255,0.6); font-size: 16px; }

    /* Stat cards */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        margin-bottom: 35px;
    }
    @media (max-width: 900px) { .stats-grid { grid-template-columns: 1fr; } }

    .stat-card {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 20px;
        padding: 28px;
        transition: all 0.3s;
    }
    .stat-card:hover {
        transform: translateY(-4px);
        border-color: rgba(102,126,234,0.3);
        box-shadow: 0 20px 40px rgba(0,0,0,0.3);
    }
    .stat-card .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        margin-bottom: 15px;
    }
    .stat-card .stat-label {
        font-size: 13px;
        color: rgba(255,255,255,0.5);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
    }
    .stat-card .stat-value {
        font-size: 36px;
        font-weight: 800;
    }

    /* Chart sections */
    .chart-section {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 20px;
        padding: 30px;
        margin-bottom: 30px;
    }
    .chart-section h2 {
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    /* Tables */
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table th {
        padding: 14px 16px;
        text-align: left;
        font-size: 12px;
        font-weight: 600;
        color: rgba(255,255,255,0.4);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1px solid rgba(255,255,255,0.08);
    }
    .data-table td {
        padding: 14px 16px;
        border-bottom: 1px solid rgba(255,255,255,0.05);
        font-size: 14px;
        color: rgba(255,255,255,0.8);
    }
    .data-table tr:last-child td { border-bottom: none; }
    .data-table tr:hover td { background: rgba(255,255,255,0.02); }

    /* Empty state */
    .empty-state {
        text-align: center;
        padding: 80px 20px;
    }
    .empty-state .empty-icon {
        width: 80px;
        height: 80px;
        background: rgba(255,255,255,0.05);
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        font-size: 40px;
        color: rgba(255,255,255,0.3);
    }
    .empty-state h3 { font-size: 20px; margin-bottom: 10px; }
    .empty-state p { color: rgba(255,255,255,0.5); font-size: 14px; max-width: 400px; margin: 0 auto; }
</style>

<div class="analytics-container">
    <div class="analytics-header">
        <h1>Analytics</h1>
        <p>Track your music performance across all platforms</p>
    </div>

    <?php if (!$hasData): ?>
    <!-- Empty state -->
    <div class="chart-section">
        <div class="empty-state">
            <div class="empty-icon"><span class="mdi mdi-chart-line"></span></div>
            <h3>No analytics data yet</h3>
            <p>No analytics data yet. Your stats will appear here once your release goes live.</p>
        </div>
    </div>
    <?php else: ?>

    <!-- Summary stat cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(102,126,234,0.2); color: #667eea;">
                <span class="mdi mdi-play-circle"></span>
            </div>
            <div class="stat-label">Lifetime Streams</div>
            <div class="stat-value" style="color: #667eea;"><?php echo number_format($lifetimeStreams); ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(0,200,83,0.2); color: #00c853;">
                <span class="mdi mdi-currency-inr"></span>
            </div>
            <div class="stat-label">Lifetime Revenue</div>
            <div class="stat-value" style="color: #00c853;">₹<?php echo number_format($lifetimeRevenue, 2); ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(255,193,7,0.2); color: #ffc107;">
                <span class="mdi mdi-cash-multiple"></span>
            </div>
            <div class="stat-label">Total Royalties</div>
            <div class="stat-value" style="color: #ffc107;">₹<?php echo number_format($totalRoyalties, 2); ?></div>
        </div>
    </div>

    <!-- Monthly Streams Line Chart -->
    <div class="chart-section">
        <h2><span class="mdi mdi-chart-line" style="color: #667eea;"></span> Monthly Streams (Last 12 Months)</h2>
        <canvas id="streamsChart" height="80"></canvas>
    </div>

    <!-- Earnings per Release Bar Chart -->
    <?php if (!empty($releaseLabels)): ?>
    <div class="chart-section">
        <h2><span class="mdi mdi-chart-bar" style="color: #00b7ff;"></span> Earnings per Release (Top 5)</h2>
        <canvas id="earningsChart" height="80"></canvas>
    </div>
    <?php endif; ?>

    <!-- Platform Breakdown Table -->
    <div class="chart-section">
        <h2><span class="mdi mdi-store" style="color: #4facfe;"></span> Platform Breakdown</h2>
        <?php
        $platformRows = [];
        while ($row = $platformBreakdown->fetch_assoc()) {
            $platformRows[] = $row;
        }
        ?>
        <?php if (!empty($platformRows)): ?>
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Platform</th>
                        <th>Streams</th>
                        <th>Revenue</th>
                        <th>Royalties</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($platformRows as $row): ?>
                    <tr>
                        <td style="font-weight: 600;"><?php echo htmlspecialchars($row['platform']); ?></td>
                        <td><?php echo number_format((float)$row['streams']); ?></td>
                        <td>₹<?php echo number_format((float)$row['revenue'], 2); ?></td>
                        <td style="color: #38ef7d; font-weight: 600;">₹<?php echo number_format((float)$row['royalties'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <p style="color: rgba(255,255,255,0.4); font-size: 14px;">No platform data available yet.</p>
        <?php endif; ?>
    </div>

    <!-- Release Breakdown Table -->
    <div class="chart-section">
        <h2><span class="mdi mdi-album" style="color: #00c853;"></span> Release Breakdown</h2>
        <?php
        $releaseRows = [];
        while ($row = $releaseBreakdown->fetch_assoc()) {
            $releaseRows[] = $row;
        }
        ?>
        <?php if (!empty($releaseRows)): ?>
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Release Title</th>
                        <th>Artist</th>
                        <th>Total Streams</th>
                        <th>Total Revenue</th>
                        <th>Total Royalties</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($releaseRows as $row): ?>
                    <tr>
                        <td style="font-weight: 600;"><?php echo htmlspecialchars($row['title']); ?></td>
                        <td><?php echo htmlspecialchars($row['primary_artist']); ?></td>
                        <td><?php echo number_format((float)$row['total_streams']); ?></td>
                        <td>₹<?php echo number_format((float)$row['total_revenue'], 2); ?></td>
                        <td style="color: #38ef7d; font-weight: 600;">₹<?php echo number_format((float)$row['total_royalties'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <p style="color: rgba(255,255,255,0.4); font-size: 14px;">No release data available yet.</p>
        <?php endif; ?>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var chartDefaults = {
            responsive: true,
            plugins: { legend: { labels: { color: 'rgba(255,255,255,0.7)' } } },
            scales: {
                x: { ticks: { color: 'rgba(255,255,255,0.6)' }, grid: { color: 'rgba(255,255,255,0.05)' } },
                y: { ticks: { color: 'rgba(255,255,255,0.6)' }, grid: { color: 'rgba(255,255,255,0.05)' } }
            }
        };

        // Monthly streams line chart
        var streamsCtx = document.getElementById('streamsChart');
        if (streamsCtx) {
            new Chart(streamsCtx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($monthlyLabels); ?>,
                    datasets: [{
                        label: 'Streams',
                        data: <?php echo json_encode($monthlyStreams); ?>,
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102,126,234,0.15)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#667eea',
                    }]
                },
                options: chartDefaults
            });
        }

        // Earnings per release bar chart
        var earningsCtx = document.getElementById('earningsChart');
        if (earningsCtx) {
            new Chart(earningsCtx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($releaseLabels); ?>,
                    datasets: [{
                        label: 'Earnings (₹)',
                        data: <?php echo json_encode($releaseEarnings); ?>,
                        backgroundColor: 'rgba(0,183,255,0.5)',
                        borderColor: '#00b7ff',
                        borderWidth: 2,
                        borderRadius: 8,
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { labels: { color: 'rgba(255,255,255,0.7)' } } },
                    scales: {
                        x: { ticks: { color: 'rgba(255,255,255,0.6)' }, grid: { color: 'rgba(255,255,255,0.05)' } },
                        y: {
                            ticks: { color: 'rgba(255,255,255,0.6)', callback: function(v) { return '₹' + v; } },
                            grid: { color: 'rgba(255,255,255,0.05)' }
                        }
                    }
                }
            });
        }
    });
    </script>

    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer_premium.php'; ?>
