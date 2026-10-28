<?php
/**
 * HiTune Distribution Admin - Referral Program (strategy doc §8)
 * Lists referral events + credit totals; lets admin tune `referral_bonus`.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/config.php';

session_start();
requireAdmin();

$success = '';
$error = '';

if (isset($_POST['save_bonus'])) {
    $bonus = round((float)$_POST['referral_bonus'], 2);
    if ($bonus < 0 || $bonus > 100000) {
        $error = 'Invalid amount';
    } else {
        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('referral_bonus', ?)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $v = (string)$bonus;
        $stmt->bind_param("s", $v);
        $success = $stmt->execute() ? 'Referral bonus updated' : ('Error: ' . $conn->error);
    }
}

$bonus_setting = '0';
$r = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'referral_bonus' LIMIT 1");
if ($r && $r->num_rows) $bonus_setting = $r->fetch_assoc()['setting_value'];

$stats = array('signups' => 0, 'credited' => 0, 'credit_total' => 0.0, 'referrers' => 0);
$r = $conn->query("SELECT event, COUNT(*) c, COALESCE(SUM(credit),0) s FROM referral_events GROUP BY event");
if ($r) while ($x = $r->fetch_assoc()) {
    if ($x['event'] === 'signup') $stats['signups'] = (int)$x['c'];
    if ($x['event'] === 'credit') { $stats['credited'] = (int)$x['c']; $stats['credit_total'] = (float)$x['s']; }
}
$r = $conn->query("SELECT COUNT(DISTINCT referrer_id) c FROM referral_events WHERE event = 'signup'");
if ($r && $r->num_rows) $stats['referrers'] = (int)$r->fetch_assoc()['c'];

$events = $conn->query("SELECT e.*, ru.name AS referrer, fu.name AS referee
    FROM referral_events e
    LEFT JOIN users ru ON ru.id = e.referrer_id
    LEFT JOIN users fu ON fu.id = e.referee_id
    ORDER BY e.id DESC LIMIT 200");

$top = $conn->query("SELECT ru.name AS username, ru.referral_code, ru.referral_credit,
        (SELECT COUNT(*) FROM referral_events e WHERE e.referrer_id = ru.id AND e.event = 'signup') AS signups
    FROM users ru WHERE ru.referral_code IS NOT NULL AND ru.referral_code != ''
    ORDER BY signups DESC, ru.referral_credit DESC LIMIT 20");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Referrals - Admin Panel</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@6.5.95/css/materialdesignicons.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #0a0e27; color: #fff; min-height: 100vh; }
        .container { display: flex; min-height: 100vh; }
        .sidebar { width: 260px; background: #0d1230; padding: 20px 0; position: fixed; height: 100vh; overflow-y: auto; }
        .sidebar-header { padding: 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header .logo { width: 60px; height: 60px; background: linear-gradient(135deg, #00b7ff, #8b5cf6); border-radius: 15px; margin: 0 auto 15px; display: flex; align-items: center; justify-content: center; font-size: 28px; }
        .sidebar-header h2 { font-size: 18px; font-weight: 700; }
        .nav-menu { padding: 20px 0; }
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 15px 25px; color: rgba(255,255,255,0.6); text-decoration: none; transition: all 0.3s; }
        .nav-item:hover, .nav-item.active { background: rgba(0,183,255,0.1); color: #00b7ff; border-left: 3px solid #00b7ff; }
        .main-content { flex: 1; margin-left: 260px; padding: 30px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .header h1 { font-size: 28px; font-weight: 700; }
        .btn { background: linear-gradient(135deg, #00b7ff, #8b5cf6); color: #fff; border: none; padding: 12px 24px; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 15px; padding: 25px; margin-bottom: 25px; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th { text-align: left; padding: 12px 15px; font-size: 12px; font-weight: 600; color: rgba(255,255,255,0.5); text-transform: uppercase; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .data-table td { padding: 14px 15px; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 14px; }
        input[type="number"] { background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.15); color: #fff; padding: 10px 14px; border-radius: 8px; width: 140px; }
        .stat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 25px; }
        .stat-box { background: rgba(0,183,255,0.08); border: 1px solid rgba(0,183,255,0.2); border-radius: 15px; padding: 25px; text-align: center; }
        .stat-box h3 { font-size: 30px; font-weight: 700; color: #00b7ff; margin-bottom: 5px; }
        .stat-box p { font-size: 13px; color: rgba(255,255,255,0.6); }
    </style>
</head>
<body>
<div class="container">
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="logo"><span class="mdi mdi-shield-account"></span></div>
            <h2>Admin Panel</h2>
            <p>HiTune Distribution</p>
        </div>
        <nav class="nav-menu">
            <a href="index.php" class="nav-item"><span class="mdi mdi-view-dashboard"></span>Dashboard</a>
            <a href="users.php" class="nav-item"><span class="mdi mdi-account-group"></span>Users</a>
            <a href="submissions.php" class="nav-item"><span class="mdi mdi-music"></span>Releases</a>
            <a href="artist_verifications.php" class="nav-item"><span class="mdi mdi-check-decagram"></span>Artist Verifications</a>
            <a href="revenue.php" class="nav-item"><span class="mdi mdi-currency-usd"></span>Revenue</a>
            <a href="payouts.php" class="nav-item"><span class="mdi mdi-cash-multiple"></span>Payouts</a>
            <a href="referrals.php" class="nav-item active"><span class="mdi mdi-account-multiple-plus"></span>Referrals</a>
            <a href="settings.php" class="nav-item"><span class="mdi mdi-cog"></span>Settings</a>
            <a href="logout.php" class="nav-item"><span class="mdi mdi-logout"></span>Logout</a>
        </nav>
    </aside>

    <main class="main-content">
        <div class="header">
            <h1><span class="mdi mdi-account-multiple-plus"></span> Referral Program</h1>
            <a href="index.php" class="btn"><span class="mdi mdi-view-dashboard"></span> Dashboard</a>
        </div>

        <?php if ($success): ?>
        <div class="card" style="border-color:#00c853; color:#00c853;"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="card" style="border-color:#ff5252; color:#ff5252;"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="stat-grid">
            <div class="stat-box"><h3><?php echo $stats['referrers']; ?></h3><p>Active Referrers</p></div>
            <div class="stat-box"><h3><?php echo $stats['signups']; ?></h3><p>Referred Signups</p></div>
            <div class="stat-box"><h3><?php echo $stats['credited']; ?></h3><p>Credits Awarded</p></div>
            <div class="stat-box"><h3>₹<?php echo number_format($stats['credit_total'], 2); ?></h3><p>Total Credit Given</p></div>
        </div>

        <div class="card">
            <h3 style="margin-bottom:15px;">Bonus per converted referral (₹)</h3>
            <form method="post" style="display:flex; gap:12px; align-items:center;">
                <input type="number" step="0.01" min="0" name="referral_bonus" value="<?php echo htmlspecialchars($bonus_setting); ?>">
                <button type="submit" name="save_bonus" class="btn">Save</button>
                <span style="color:rgba(255,255,255,.5); font-size:13px;">Credited to the referrer when the referred artist's first release goes live on HiTune.</span>
            </form>
        </div>

        <div class="card">
            <h3 style="margin-bottom:15px;">Top Referrers</h3>
            <table class="data-table">
                <tr><th>User</th><th>Code</th><th>Signups</th><th>Credit Balance</th></tr>
                <?php while ($top && $t = $top->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($t['username']); ?></td>
                    <td><code><?php echo htmlspecialchars($t['referral_code']); ?></code></td>
                    <td><?php echo $t['signups']; ?></td>
                    <td>₹<?php echo number_format($t['referral_credit'], 2); ?></td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>

        <div class="card">
            <h3 style="margin-bottom:15px;">Recent Events</h3>
            <table class="data-table">
                <tr><th>ID</th><th>Referrer</th><th>Referee</th><th>Event</th><th>Credit</th><th>Meta</th><th>Time</th></tr>
                <?php while ($events && $e = $events->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $e['id']; ?></td>
                    <td><?php echo htmlspecialchars($e['referrer'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($e['referee'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($e['event']); ?></td>
                    <td><?php echo $e['credit'] !== null ? '₹' . number_format($e['credit'], 2) : '-'; ?></td>
                    <td style="color:rgba(255,255,255,.5); font-size:12px;"><?php echo htmlspecialchars($e['meta'] ?? ''); ?></td>
                    <td style="color:rgba(255,255,255,.5); font-size:12px;"><?php echo $e['created_at'] ?? ''; ?></td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </main>
</div>
</body>
</html>
