<?php
/**
 * web.hitune.in - ISRC/UPC Management
 * TuneCore-like ISRC/UPC management feature
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$pageTitle = 'ISRC/UPC - HiTune Music Distribution';
include __DIR__ . '/../includes/header_premium.php';
?>

<style>
    .isrc-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 120px 40px 80px;
    }
    .isrc-container::before {
        content: '';
        position: fixed;
        width: 600px;
        height: 600px;
        background: radial-gradient(circle, rgba(102, 126, 234, 0.12) 0%, transparent 60%);
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
    .isrc-header {
        margin-bottom: 40px;
    }
    .isrc-header h1 {
        font-size: 36px;
        font-weight: 800;
        margin-bottom: 10px;
        background: linear-gradient(135deg, #00b7ff, #8b5cf6);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .isrc-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 25px;
        margin-bottom: 40px;
    }
    .isrc-card {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 20px;
        padding: 30px;
        transition: all 0.3s;
    }
    .isrc-card:hover {
        background: rgba(255, 255, 255, 0.08);
        transform: translateY(-5px);
    }
    .isrc-card h3 {
        font-size: 20px;
        font-weight: 700;
        margin-bottom: 15px;
    }
    .isrc-card p {
        color: rgba(255, 255, 255, 0.6);
        font-size: 14px;
        margin-bottom: 20px;
    }
    .isrc-card .code {
        font-family: 'Courier New', monospace;
        font-size: 18px;
        color: #38ef7d;
        background: rgba(56, 239, 125, 0.1);
        padding: 10px 15px;
        border-radius: 8px;
        display: inline-block;
    }
    .btn-request {
        padding: 10px 24px;
        background: linear-gradient(135deg, #00b7ff, #8b5cf6);
        color: #fff;
        border: none;
        border-radius: 50px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        margin-top: 15px;
    }
</style>

<div class="isrc-container">
    <div class="isrc-header">
        <h1>ISRC & UPC Management</h1>
        <p>Manage your International Standard Recording Codes and Universal Product Codes</p>
    </div>

    <div class="isrc-grid">
        <div class="isrc-card">
            <h3>ISRC Codes</h3>
            <p>International Standard Recording Codes for your tracks. Free ISRC codes included with your subscription.</p>
            <div class="code">IN-ABC-24-00001</div>
            <button class="btn-request">Request New ISRC</button>
        </div>

        <div class="isrc-card">
            <h3>UPC Codes</h3>
            <p>Universal Product Codes for your releases. Required for digital distribution to major platforms.</p>
            <div class="code">123456789012</div>
            <button class="btn-request">Request New UPC</button>
        </div>

        <div class="isrc-card">
            <h3>Code History</h3>
            <p>View all your assigned ISRC and UPC codes in one place.</p>
            <div class="code">24 codes assigned</div>
            <button class="btn-request">View History</button>
        </div>
    </div>

    <div style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 20px; padding: 30px;">
        <h3 style="font-size: 20px; font-weight: 700; margin-bottom: 20px;">Recent Assignments</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.1);">
                    <th style="padding: 15px; text-align: left; font-weight: 600; font-size: 14px;">Type</th>
                    <th style="padding: 15px; text-align: left; font-weight: 600; font-size: 14px;">Code</th>
                    <th style="padding: 15px; text-align: left; font-weight: 600; font-size: 14px;">Release</th>
                    <th style="padding: 15px; text-align: left; font-weight: 600; font-size: 14px;">Date</th>
                </tr>
            </thead>
            <tbody>
                <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.05);">
                    <td style="padding: 15px; font-size: 14px;">ISRC</td>
                    <td style="padding: 15px; font-size: 14px; color: #38ef7d;">IN-ABC-24-00001</td>
                    <td style="padding: 15px; font-size: 14px;">Summer Vibes</td>
                    <td style="padding: 15px; font-size: 14px;">2024-04-15</td>
                </tr>
                <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.05);">
                    <td style="padding: 15px; font-size: 14px;">UPC</td>
                    <td style="padding: 15px; font-size: 14px; color: #38ef7d;">123456789012</td>
                    <td style="padding: 15px; font-size: 14px;">Summer Vibes EP</td>
                    <td style="padding: 15px; font-size: 14px;">2024-04-14</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer_premium.php'; ?>
