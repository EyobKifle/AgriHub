<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: Login.html');
    exit();
}

require_once __DIR__ . '/../php/config.php';

$name = $_SESSION['name'] ?? 'Admin';
$email = $_SESSION['email'] ?? '';
$initial = strtoupper(mb_substr($name, 0, 1));

$settings = [];
$message = '';

// Handle POST request to update settings
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fee = $_POST['marketplace_fee_percent'] ?? '0';
    $limit = $_POST['user_listing_limit'] ?? '0';

    $stmt1 = $conn->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = 'marketplace_fee_percent'");
    $stmt1->bind_param('s', $fee);
    $stmt1->execute();
    $stmt1->close();

    $stmt2 = $conn->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = 'user_listing_limit'");
    $stmt2->bind_param('s', $limit);
    $stmt2->execute();
    $stmt2->close();

    $message = 'Settings updated successfully!';
}

// Fetch current settings to display in the form
$result = $conn->query("SELECT setting_key, setting_value FROM system_settings");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

$marketplace_fee = $settings['marketplace_fee_percent'] ?? 0;
$user_listing_limit = $settings['user_listing_limit'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - AgriHub Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../Css/Admin-Dashboard.css">
</head>
<body>
    <!-- Header is now outside the container to be full-width -->
    <header class="main-header-bar">
        <div class="header-left">
            <button class="hamburger-menu" id="hamburger-menu" aria-label="Toggle Menu">
                <i class="fa-solid fa-bars"></i>
            </button>
        </div>
        <div class="header-center">
            <div class="logo">
                <i class="fa-solid fa-leaf"></i>
                <span>AgriHub</span>
            </div>
        </div>
        <div class="header-right">
            <a href="Settings.html" class="profile-link" aria-label="User Settings">
                <div class="profile-avatar"><?php echo htmlspecialchars($initial, ENT_QUOTES, 'UTF-8'); ?></div>
            </a>
        </div>
    </header>

    <div class="dashboard-container">
        <aside class="sidebar" id="sidebar">
            <ul class="sidebar-nav">
                <li><a href="Admin-Dashboard.php" data-i18n-key="admin.nav.dashboard"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>
                <li><a href="User-Management.php" data-i18n-key="admin.nav.userManagement"><i class="fa-solid fa-users"></i> User Management</a></li>
                <li><a href="Listings-Management.php" data-i18n-key="admin.nav.listingManagement"><i class="fa-solid fa-store"></i> Listing Management</a></li>
                <li><a href="News-Management.php" data-i18n-key="admin.nav.newsManagement"><i class="fa-solid fa-newspaper"></i> News Management</a></li>
                <li><a href="Reports-Management.php" data-i18n-key="admin.nav.reports"><i class="fa-solid fa-flag"></i> Reports</a></li>
                <li><a href="Settings.php" class="active" data-i18n-key="admin.nav.settings"><i class="fa-solid fa-gear"></i> Settings</a></li>
            </ul>
            <div class="sidebar-footer">
                <div class="profile-dropdown">
                    <div>
                        <div class="profile-name"><?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="profile-email" style="opacity:.8; font-size:12px;">
                            <?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                        <small><a href="../php/auth.php?action=logout" style="color:inherit; text-decoration:none;">Logout</a></small>
                    </div>
                </div>
            </ul>
        </aside>

        <main class="main-content">
            <div class="content-wrapper">
                <div class="main-header">
                    <h1 data-i18n-key="admin.settings.title">Settings</h1>
                    <p data-i18n-key="admin.settings.subtitle">Manage your administrator profile and site-wide configurations.</p>
                </div>
                <?php if ($message): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>

                <div class="settings-grid">
                    <!-- Admin Profile Card -->
                    <div class="card">
                        <h3 class="card-title" data-i18n-key="admin.settings.profile.title">Admin Profile</h3>
                        <form class="settings-form">
                            <div class="form-group">
                                <label for="admin-name" data-i18n-key="admin.settings.profile.name">Full Name</label>
                                <input type="text" id="admin-name" value="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="form-group">
                                <label for="admin-email" data-i18n-key="admin.settings.profile.email">Email Address</label>
                                <input type="email" id="admin-email" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" disabled>
                            </div>
                            <button type="submit" class="btn btn-primary" data-i18n-key="admin.settings.profile.save">Save Profile</button>
                        </form>
                    </div>

                    <!-- Security Settings Card -->
                    <div class="card">
                        <h3 class="card-title" data-i18n-key="admin.settings.security.title">Security Settings</h3>
                        <form class="settings-form">
                            <div class="form-group">
                                <label for="current-password" data-i18n-key="admin.settings.security.currentPassword">Current Password</label>
                                <input type="password" id="current-password" placeholder="••••••••">
                            </div>
                            <div class="form-group">
                                <label for="new-password" data-i18n-key="admin.settings.security.newPassword">New Password</label>
                                <input type="password" id="new-password" placeholder="••••••••">
                            </div>
                            <div class="form-group">
                                <label for="confirm-password" data-i18n-key="admin.settings.security.confirmPassword">Confirm New Password</label>
                                <input type="password" id="confirm-password" placeholder="••••••••">
                            </div>
                            <div class="form-group form-group-toggle">
                                <label for="2fa-toggle" data-i18n-key="admin.settings.security.twoFactor">Two-Factor Authentication (2FA)</label>
                                <label class="switch">
                                    <input type="checkbox" id="2fa-toggle">
                                    <span class="slider"></span>
                                </label>
                            </div>
                            <button type="submit" class="btn btn-primary" data-i18n-key="admin.settings.security.updatePassword">Update Password</button>
                        </form>
                    </div>

                    <!-- Platform Configuration Card -->
                    <div class="card">
                        <h3 class="card-title" data-i18n-key="admin.settings.platform.title">Platform Configuration</h3>
                        <form class="settings-form" method="post">
                            <div class="form-group">
                                <label for="transaction-fee" data-i18n-key="admin.settings.platform.fee">Marketplace Transaction Fee (%)</label>
                                <input type="number" name="marketplace_fee_percent" id="transaction-fee" value="<?php echo htmlspecialchars($marketplace_fee, ENT_QUOTES, 'UTF-8'); ?>" min="0" max="100">
                            </div>
                            <div class="form-group">
                                <label for="listing-limit" data-i18n-key="admin.settings.platform.limit">User Listing Limit</label>
                                <input type="number" name="user_listing_limit" id="listing-limit" value="<?php echo htmlspecialchars($user_listing_limit, ENT_QUOTES, 'UTF-8'); ?>" min="1">
                            </div>
                            <button type="submit" class="btn btn-primary" data-i18n-key="admin.settings.platform.save">Save Configuration</button>
                        </form>
                    </div>
                </div>

                <div class="logout-section">
                    <p class="form-text" data-i18n-key="admin.settings.logout.text">End your current session and return to the login page.</p>
                    <a href="../php/auth.php?action=logout" class="btn btn-danger" data-i18n-key="admin.settings.logout.button"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                </div>
            </div>
        </main>
    </div>

    <script src="../Js/dashboard.js" type="module"></script>
</body>
</html>