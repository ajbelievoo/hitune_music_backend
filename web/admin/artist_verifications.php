<?php
/**
 * HiTune Distribution Admin — Artist Verifications
 * Reviews HiTune Music / Spotify / Apple Music verification requests raised
 * from the Artist Panel. HiTune approvals drive the native music-side flow.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/artist_panel.php';

session_start();
requireAdmin();
ap_ensure_tables($conn);

$success = '';
$error = '';
$conflictId = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken($_POST['csrf_token'] ?? '');

    if (isset($_POST['save_spotify'])) {
        foreach (['spotify_client_id', 'spotify_client_secret'] as $k) {
            $v = trim((string) ($_POST[$k] ?? ''));
            if ($k === 'spotify_client_secret' && $v === '') continue; // keep the stored secret when left blank
            $st = $conn->prepare("INSERT INTO settings (setting_key, setting_value, setting_group) VALUES (?, ?, 'artist')
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), setting_group = VALUES(setting_group)");
            $st->bind_param("ss", $k, $v);
            $st->execute();
            $st->close();
        }
        @array_map('unlink', glob(sys_get_temp_dir() . '/ap_spotify_*.json') ?: []);
        $success = 'Spotify API settings saved.';
    } elseif (isset($_POST['decision'])) {
        $id = (int) ($_POST['id'] ?? 0);
        $decision = $_POST['decision'];
        if (!in_array($decision, ['approve', 'reject', 'revoke', 'approve_force'], true)) $decision = 'reject';
        $res = ap_review_verification($conn, $id, $decision === 'approve_force' ? 'approve' : $decision,
            (string) ($_POST['admin_notes'] ?? ''), $decision === 'approve_force');
        if (!empty($res['ok'])) $success = $res['message'];
        else { $error = $res['message']; if (!empty($res['conflict'])) $conflictId = $id; }
    }
}

$fStatus   = $_GET['status'] ?? 'pending';
$fPlatform = $_GET['platform'] ?? '';
$where = []; $types = ''; $params = [];
if (in_array($fStatus, ['pending', 'verified', 'rejected', 'revoked'], true)) { $where[] = 'v.status = ?'; $types .= 's'; $params[] = $fStatus; }
if (in_array($fPlatform, ['hitune', 'spotify', 'apple'], true)) { $where[] = 'v.platform = ?'; $types .= 's'; $params[] = $fPlatform; }
$sql = "SELECT v.*, u.name AS user_name, u.email FROM artist_verifications v JOIN users u ON u.id = v.user_id"
     . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . " ORDER BY (v.status = 'pending') DESC, v.id DESC LIMIT 200";
$st = $conn->prepare($sql);
if ($params) $st->bind_param($types, ...$params);
$st->execute();
$rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$st->close();

$counts = ['pending' => 0, 'verified' => 0, 'rejected' => 0, 'revoked' => 0];
$res = $conn->query("SELECT status, COUNT(*) c FROM artist_verifications GROUP BY status");
while ($res && ($r = $res->fetch_assoc())) $counts[$r['status']] = (int) $r['c'];
$hasSpotify = ap_spotify_token($conn) !== null;
$sid = ap_setting($conn, 'spotify_client_id', '');
$e = function ($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };
$platName = ['hitune' => 'HiTune Music', 'spotify' => 'Spotify', 'apple' => 'Apple Music'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artist Verifications - Admin Panel</title>
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
        .main-content { flex: 1; margin-left: 260px; padding: 30px; min-width: 0; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 12px; }
        .header h1 { font-size: 28px; font-weight: 700; }
        .btn { background: linear-gradient(135deg, #00b7ff, #8b5cf6); color: #fff; border: none; padding: 10px 18px; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; font-family: inherit; }
        .btn.ok { background: #00a651; } .btn.bad { background: #d93636; } .btn.ghost { background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.15); }
        .card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 15px; padding: 22px; margin-bottom: 22px; }
        .stat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 22px; }
        .stat-box { background: rgba(0,183,255,0.08); border: 1px solid rgba(0,183,255,0.2); border-radius: 15px; padding: 20px; text-align: center; text-decoration: none; color: inherit; }
        .stat-box h3 { font-size: 28px; font-weight: 700; color: #00b7ff; } .stat-box p { font-size: 13px; color: rgba(255,255,255,0.6); }
        .filters { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 18px; }
        .filters a { padding: 8px 14px; border-radius: 10px; font-size: 13px; text-decoration: none; color: rgba(255,255,255,.7); background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.1); }
        .filters a.on { background: linear-gradient(135deg, #00b7ff, #8b5cf6); color: #fff; border-color: transparent; }
        .req { border: 1px solid rgba(255,255,255,.1); border-radius: 14px; padding: 18px; margin-bottom: 14px; background: rgba(255,255,255,.02); }
        .req-h { display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap; align-items: flex-start; }
        .pill { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
        .pill.pending { background: rgba(255,193,7,.18); color: #ffc107; } .pill.verified { background: rgba(0,200,83,.18); color: #00d967; }
        .pill.rejected, .pill.revoked { background: rgba(255,82,82,.18); color: #ff6b6b; }
        .muted { color: rgba(255,255,255,.55); font-size: 13px; line-height: 1.6; }
        .kv { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 10px 20px; margin: 12px 0; font-size: 13px; }
        .kv b { display: block; font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: rgba(255,255,255,.45); margin-bottom: 2px; }
        .kv a { color: #4fc3ff; word-break: break-all; }
        textarea, input[type=text], input[type=password] { width: 100%; background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.15); color: #fff; padding: 10px 12px; border-radius: 8px; font-family: inherit; font-size: 13px; }
        .acts { display: flex; gap: 8px; flex-wrap: wrap; align-items: flex-start; margin-top: 10px; }
        .acts textarea { flex: 1; min-width: 220px; min-height: 42px; }
        .msg { padding: 14px 18px; border-radius: 12px; margin-bottom: 18px; border: 1px solid; }
        @media (max-width: 900px) { .sidebar { display: none; } .main-content { margin-left: 0; } .stat-grid { grid-template-columns: repeat(2, 1fr); } }
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
            <a href="artist_verifications.php" class="nav-item active"><span class="mdi mdi-check-decagram"></span>Artist Verifications</a>
            <a href="revenue.php" class="nav-item"><span class="mdi mdi-currency-usd"></span>Revenue</a>
            <a href="payouts.php" class="nav-item"><span class="mdi mdi-cash-multiple"></span>Payouts</a>
            <a href="referrals.php" class="nav-item"><span class="mdi mdi-account-multiple-plus"></span>Referrals</a>
            <a href="settings.php" class="nav-item"><span class="mdi mdi-cog"></span>Settings</a>
            <a href="logout.php" class="nav-item"><span class="mdi mdi-logout"></span>Logout</a>
        </nav>
    </aside>

    <main class="main-content">
        <div class="header">
            <h1><span class="mdi mdi-check-decagram"></span> Artist Verifications</h1>
            <a href="index.php" class="btn ghost"><span class="mdi mdi-view-dashboard"></span> Dashboard</a>
        </div>

        <?php if ($success): ?><div class="msg" style="border-color:#00c853;color:#00d967"><?php echo $e($success); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="msg" style="border-color:#ff5252;color:#ff6b6b"><?php echo $e($error); ?></div><?php endif; ?>

        <div class="stat-grid">
            <a class="stat-box" href="?status=pending"><h3><?php echo $counts['pending']; ?></h3><p>Pending review</p></a>
            <a class="stat-box" href="?status=verified"><h3><?php echo $counts['verified']; ?></h3><p>Verified</p></a>
            <a class="stat-box" href="?status=rejected"><h3><?php echo $counts['rejected']; ?></h3><p>Rejected</p></a>
            <a class="stat-box" href="?status=revoked"><h3><?php echo $counts['revoked']; ?></h3><p>Revoked</p></a>
        </div>

        <div class="card">
            <h3 style="margin-bottom:6px"><span class="mdi mdi-spotify" style="color:#1db954"></span> Spotify API (optional — enables automatic verification)</h3>
            <p class="muted" style="margin-bottom:12px">With a Spotify developer app (free at developer.spotify.com) the panel shows followers &amp; popularity and verifies artists <b>automatically</b> when a HiTune ISRC is found on their Spotify profile. Status: <b style="color:<?php echo $hasSpotify ? '#00d967' : '#ffc107'; ?>"><?php echo $hasSpotify ? 'connected' : 'not configured — requests need manual review'; ?></b></p>
            <form method="post" style="display:grid;grid-template-columns:1fr 1fr auto;gap:10px;align-items:end">
                <?php echo csrfField(); ?>
                <div><label class="muted">Client ID</label><input type="text" name="spotify_client_id" value="<?php echo $e($sid); ?>" autocomplete="off"></div>
                <div><label class="muted">Client secret <?php echo $hasSpotify ? '(leave blank to keep)' : ''; ?></label><input type="password" name="spotify_client_secret" autocomplete="new-password"></div>
                <button class="btn" name="save_spotify" value="1">Save</button>
            </form>
        </div>

        <div class="filters">
            <?php foreach (['pending' => 'Pending', 'verified' => 'Verified', 'rejected' => 'Rejected', 'revoked' => 'Revoked', 'all' => 'All'] as $k => $l): ?>
                <a class="<?php echo $fStatus === $k ? 'on' : ''; ?>" href="?status=<?php echo $k; ?>&amp;platform=<?php echo $e($fPlatform); ?>"><?php echo $l; ?></a>
            <?php endforeach; ?>
            <span style="flex:1"></span>
            <?php foreach (['' => 'Any platform', 'hitune' => 'HiTune Music', 'spotify' => 'Spotify', 'apple' => 'Apple Music'] as $k => $l): ?>
                <a class="<?php echo $fPlatform === $k ? 'on' : ''; ?>" href="?status=<?php echo $e($fStatus); ?>&amp;platform=<?php echo $k; ?>"><?php echo $l; ?></a>
            <?php endforeach; ?>
        </div>

        <?php if (!$rows): ?><div class="card muted" style="text-align:center;padding:40px">No requests here.</div><?php endif; ?>
        <?php foreach ($rows as $v): $m = json_decode((string) $v['meta'], true) ?: []; ?>
        <div class="req">
            <div class="req-h">
                <div>
                    <b style="font-size:16px"><?php echo $e($v['artist_name']); ?></b>
                    <span class="pill <?php echo $e($v['status']); ?>"><?php echo $e($v['status']); ?></span>
                    <span class="pill" style="background:rgba(0,183,255,.15);color:#4fc3ff"><?php echo $e($platName[$v['platform']]); ?></span>
                    <?php if ($v['method']): ?><span class="muted"> · via <?php echo $e($v['method']); ?></span><?php endif; ?>
                    <div class="muted"><?php echo $e($v['user_name']); ?> &lt;<?php echo $e($v['email']); ?>&gt; · requested <?php echo $e($v['requested_at']); ?></div>
                </div>
            </div>
            <div class="kv">
                <?php if ($v['real_name']): ?><div><b>Legal name</b><?php echo $e($v['real_name']); ?></div><?php endif; ?>
                <?php if ($v['profile_url']): ?><div><b>Profile / proof link</b><a href="<?php echo $e($v['profile_url']); ?>" target="_blank" rel="noopener nofollow"><?php echo $e($v['profile_url']); ?></a></div><?php endif; ?>
                <?php if ($v['doc_path']): ?><div><b>Proof document</b><a href="verification_doc.php?id=<?php echo (int) $v['id']; ?>" target="_blank">Open file</a></div><?php endif; ?>
                <?php if (!empty($m['name'])): ?><div><b>Found on <?php echo $e($platName[$v['platform']]); ?></b><?php echo $e($m['name']); ?><?php echo isset($m['name_similarity']) ? ' (' . (float) $m['name_similarity'] . '% name match)' : ''; ?></div><?php endif; ?>
                <?php if (isset($m['followers'])): ?><div><b>Followers / popularity</b><?php echo number_format((int) $m['followers']); ?> / <?php echo (int) ($m['popularity'] ?? 0); ?></div><?php endif; ?>
                <?php if (!empty($m['isrc_matches'])): ?><div><b>ISRC ownership match</b><span style="color:#00d967">✓ <?php echo $e(implode(', ', $m['isrc_matches'])); ?></span></div>
                <?php elseif ($v['platform'] === 'spotify' && ($m['api'] ?? '') === 'web_api'): ?><div><b>ISRC ownership match</b>none of <?php echo (int) ($m['isrc_checked'] ?? 0); ?> checked</div><?php endif; ?>
                <?php if (!empty($m['title_matches'])): ?><div><b>Catalogue title matches</b><?php echo $e(implode(', ', array_slice($m['title_matches'], 0, 6))); ?></div>
                <?php elseif ($v['platform'] === 'apple' && !empty($m['ok'])): ?><div><b>Catalogue title matches</b>none of the artist's <?php echo (int) ($m['songs_seen'] ?? 0); ?> songs match your releases</div><?php endif; ?>
                <?php if ($v['proof_note']): ?><div style="grid-column:1/-1"><b>Artist's note</b><?php echo nl2br($e($v['proof_note'])); ?></div><?php endif; ?>
                <?php if ($v['admin_notes']): ?><div style="grid-column:1/-1"><b>Admin notes</b><?php echo nl2br($e($v['admin_notes'])); ?></div><?php endif; ?>
            </div>
            <form method="post" class="acts">
                <?php echo csrfField(); ?><input type="hidden" name="id" value="<?php echo (int) $v['id']; ?>">
                <textarea name="admin_notes" placeholder="Note shown to the artist (required when rejecting)"></textarea>
                <?php if ($v['status'] === 'pending' || $v['status'] === 'rejected' || $v['status'] === 'revoked'): ?>
                    <button class="btn ok" name="decision" value="approve"><span class="mdi mdi-check"></span> Approve</button>
                    <?php if ($conflictId === (int) $v['id']): ?><button class="btn" name="decision" value="approve_force" onclick="return confirm('Transfer this artist from its current manager to this user?')"><span class="mdi mdi-swap-horizontal"></span> Approve &amp; transfer</button><?php endif; ?>
                <?php endif; ?>
                <?php if ($v['status'] === 'pending'): ?><button class="btn bad" name="decision" value="reject"><span class="mdi mdi-close"></span> Reject</button><?php endif; ?>
                <?php if ($v['status'] === 'verified'): ?><button class="btn bad" name="decision" value="revoke" onclick="return confirm('Remove this verification?')"><span class="mdi mdi-shield-off-outline"></span> Revoke</button><?php endif; ?>
            </form>
        </div>
        <?php endforeach; ?>
    </main>
</div>
</body>
</html>
