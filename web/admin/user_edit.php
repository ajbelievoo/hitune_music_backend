<?php
/**
 * HiTune Admin Panel - Edit User
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/config.php';

session_start();
requireAdmin();

$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($user_id === 0) {
    header('Location: users.php');
    exit;
}

$success = '';
$error = '';

// Get user details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header('Location: users.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    
    if (empty($name) || empty($email)) {
        $error = 'Name and email are required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } else {
        // Check if email is already taken by another user
        $check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check->bind_param("si", $email, $user_id);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = 'Email is already in use by another user';
        } else {
            // Update user
            if (!empty($new_password)) {
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, password = ? WHERE id = ?");
                $stmt->bind_param("ssssi", $name, $email, $phone, $password_hash, $user_id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
                $stmt->bind_param("sssi", $name, $email, $phone, $user_id);
            }
            
            if ($stmt->execute()) {
                $success = 'User updated successfully';
                // Refresh user data
                $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();
            } else {
                $error = 'Error updating user: ' . $conn->error;
            }
        }
    }
}

// Handle delete request
if (isset($_POST['delete_user'])) {
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        header('Location: users.php?deleted=1');
        exit;
    } else {
        $error = 'Error deleting user: ' . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Admin Panel</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@6.5.95/css/materialdesignicons.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: #0a0a0a;
            color: #fff;
            min-height: 100vh;
        }
        .admin-container { display: flex; min-height: 100vh; }
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            position: fixed;
            top: 0; left: 0; bottom: 0;
            overflow-y: auto;
        }
        .sidebar-header {
            padding: 30px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }
        .sidebar-header .logo {
            width: 60px; height: 60px;
            background: linear-gradient(135deg, #00b7ff, #8b5cf6);
            border-radius: 15px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 15px;
            font-size: 28px;
        }
        .sidebar-header h2 { font-size: 18px; font-weight: 700; }
        .sidebar-header p { font-size: 12px; color: rgba(255,255,255,0.5); margin-top: 5px; }
        .nav-menu { padding: 20px 0; }
        .nav-item {
            display: flex; align-items: center; gap: 15px;
            padding: 15px 30px;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        .nav-item:hover, .nav-item.active {
            background: rgba(0, 183, 255, 0.1);
            color: #00b7ff;
            border-left-color: #00b7ff;
        }
        .nav-item span { font-size: 20px; }
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 30px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .header h1 { font-size: 28px; font-weight: 700; }
        .btn {
            padding: 12px 25px;
            background: linear-gradient(135deg, #00b7ff, #8b5cf6);
            border: none;
            border-radius: 10px;
            color: #fff;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .btn-danger {
            background: rgba(255, 82, 82, 0.2);
            border: 1px solid rgba(255, 82, 82, 0.3);
            color: #ff5252;
        }
        
        /* Form Styles */
        .form-container {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 30px;
            max-width: 800px;
        }
        .form-group {
            margin-bottom: 25px;
        }
        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.8);
        }
        .form-group label span {
            color: #00b7ff;
        }
        .form-control {
            width: 100%;
            padding: 15px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            color: #fff;
            font-size: 15px;
            font-family: inherit;
        }
        .form-control:focus {
            outline: none;
            border-color: #00b7ff;
        }
        .help-text {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.5);
            margin-top: 8px;
        }
        
        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .alert-success {
            background: rgba(0, 200, 83, 0.2);
            border: 1px solid rgba(0, 200, 83, 0.3);
            color: #00c853;
        }
        .alert-error {
            background: rgba(255, 82, 82, 0.2);
            border: 1px solid rgba(255, 82, 82, 0.3);
            color: #ff5252;
        }
        
        /* Delete Section */
        .delete-section {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid rgba(255, 82, 82, 0.3);
        }
        .delete-section h3 {
            color: #ff5252;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .delete-section p {
            color: rgba(255, 255, 255, 0.6);
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo"><span class="mdi mdi-shield-account"></span></div>
                <h2>Admin Panel</h2>
                <p>HiTune Distribution</p>
            </div>
            <nav class="nav-menu">
                <a href="index.php" class="nav-item"><span class="mdi mdi-view-dashboard"></span>Dashboard</a>
                <a href="users.php" class="nav-item active"><span class="mdi mdi-account-group"></span>Users</a>
                <a href="submissions.php" class="nav-item"><span class="mdi mdi-music"></span>Releases</a>
                <a href="cover_art.php" class="nav-item"><span class="mdi mdi-image"></span>Cover Art</a>
                <a href="manage_artists.php" class="nav-item"><span class="mdi mdi-account-music"></span>Manage Artists</a>
                <a href="user_artists.php" class="nav-item"><span class="mdi mdi-account-question"></span>Artist Requests</a>
                <a href="artist_verifications.php" class="nav-item"><span class="mdi mdi-check-decagram"></span>Artist Verifications</a>
                <a href="revenue.php" class="nav-item"><span class="mdi mdi-currency-usd"></span>Revenue</a>
                <a href="payouts.php" class="nav-item"><span class="mdi mdi-cash-multiple"></span>Payouts</a>
                <a href="payments.php" class="nav-item"><span class="mdi mdi-credit-card"></span>Payments</a>
                <a href="settings.php" class="nav-item"><span class="mdi mdi-cog"></span>Settings</a>
                <a href="logout.php" class="nav-item"><span class="mdi mdi-logout"></span>Logout</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="header">
                <h1><span class="mdi mdi-pencil"></span> Edit User</h1>
                <div style="display: flex; gap: 10px;">
                    <a href="user_view.php?id=<?php echo $user_id; ?>" class="btn btn-secondary"><span class="mdi mdi-eye"></span> View Profile</a>
                    <a href="users.php" class="btn btn-secondary"><span class="mdi mdi-arrow-left"></span> Back to Users</a>
                </div>
            </div>

            <?php if ($success): ?>
            <div class="alert alert-success">
                <span class="mdi mdi-check-circle"></span>
                <?php echo htmlspecialchars($success); ?>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="alert alert-error">
                <span class="mdi mdi-alert-circle"></span>
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <div class="form-container">
                <form method="POST">
                    <div class="form-group">
                        <label>User ID</label>
                        <input type="text" class="form-control" value="#<?php echo $user['id']; ?>" disabled>
                    </div>

                    <div class="form-group">
                        <label>Full Name <span>*</span></label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Email Address <span>*</span></label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label>New Password <span class="mdi mdi-lock" style="color: #00b7ff;"></span></label>
                        <input type="password" name="new_password" class="form-control" placeholder="Leave empty to keep current password">
                        <div class="help-text">Only enter a password if you want to change it. Leave blank to keep the current password.</div>
                    </div>

                    <div class="form-group">
                        <label>Joined Date</label>
                        <input type="text" class="form-control" value="<?php echo date('F j, Y g:i A', strtotime($user['created_at'])); ?>" disabled>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn"><span class="mdi mdi-content-save"></span> Save Changes</button>
                        <a href="users.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>

                <!-- Delete Section -->
                <div class="delete-section">
                    <h3><span class="mdi mdi-delete"></span> Danger Zone</h3>
                    <p>Deleting this user will permanently remove all their data including releases, payments, and artist accounts. This action cannot be undone.</p>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this user? All their data will be permanently removed.');">
                        <button type="submit" name="delete_user" class="btn btn-danger"><span class="mdi mdi-delete"></span> Delete User</button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
