<?php
/**
 * web.hitune.in - Revenue Tracking
 * TuneCore-like revenue tracking feature
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$pageTitle = 'Revenue - HiTune Music Distribution';
include __DIR__ . '/../includes/header_premium.php';
?>

<style>
    .revenue-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 120px 40px 80px;
    }
    .revenue-container::before {
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
    .revenue-header {
        margin-bottom: 40px;
    }
    .revenue-header h1 {
        font-size: 36px;
        font-weight: 800;
        margin-bottom: 10px;
        background: linear-gradient(135deg, #00b7ff, #8b5cf6);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .stats-overview {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 25px;
        margin-bottom: 40px;
    }
    .stat-box {
        background: linear-gradient(135deg, rgba(0, 183, 255, 0.1), rgba(139, 92, 246, 0.1));
        border: 1px solid rgba(0, 183, 255, 0.3);
        border-radius: 20px;
        padding: 30px;
        text-align: center;
    }
    .stat-box h3 {
        font-size: 14px;
        color: rgba(255, 255, 255, 0.6);
        margin-bottom: 10px;
    }
    .stat-box .value {
        font-size: 36px;
        font-weight: 800;
        background: linear-gradient(135deg, #00b7ff, #8b5cf6);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .revenue-table {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 20px;
        overflow: hidden;
    }
    .revenue-table table {
        width: 100%;
        border-collapse: collapse;
    }
    .revenue-table th {
        background: rgba(255, 255, 255, 0.1);
        padding: 20px;
        text-align: left;
        font-weight: 600;
        font-size: 14px;
        color: rgba(255, 255, 255, 0.8);
    }
    .revenue-table td {
        padding: 20px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        font-size: 14px;
    }
    .revenue-table tr:last-child td {
        border-bottom: none;
    }
    .amount {
        color: #38ef7d;
        font-weight: 600;
    }
</style>

<div class="revenue-container">
    <div class="revenue-header">
        <h1>Revenue Dashboard</h1>
        <p>Track your earnings across all platforms</p>
    </div>

    <div class="stats-overview">
        <div class="stat-box">
            <h3>Total Revenue</h3>
            <div class="value">$12,450</div>
        </div>
        <div class="stat-box">
            <h3>This Month</h3>
            <div class="value">$2,340</div>
        </div>
        <div class="stat-box">
            <h3>Pending</h3>
            <div class="value">$890</div>
        </div>
        <div class="stat-box">
            <h3>Paid Out</h3>
            <div class="value">$11,560</div>
        </div>
    </div>

    <div class="revenue-table">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Platform</th>
                    <th>Release</th>
                    <th>Streams</th>
                    <th>Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>2024-04-15</td>
                    <td>Spotify</td>
                    <td>Summer Vibes</td>
                    <td>45,230</td>
                    <td class="amount">$234.50</td>
                    <td>Paid</td>
                </tr>
                <tr>
                    <td>2024-04-14</td>
                    <td>Apple Music</td>
                    <td>Summer Vibes</td>
                    <td>12,450</td>
                    <td class="amount">$87.20</td>
                    <td>Pending</td>
                </tr>
                <tr>
                    <td>2024-04-13</td>
                    <td>YouTube Music</td>
                    <td>Night Drive</td>
                    <td>28,900</td>
                    <td class="amount">$156.30</td>
                    <td>Paid</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer_premium.php'; ?>
