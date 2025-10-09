<?php
session_start();
require_once __DIR__ . '/config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../HTML/login.html');
    exit();
}

$name = $_SESSION['name'] ?? 'Admin';
$email = $_SESSION['email'] ?? '';
$initial = !empty($name) ? strtoupper($name[0]) : 'A';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newName = trim($_POST['name'] ?? '');
    $newEmail = trim($_POST['email'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';

    $userId = $_SESSION['user_id'];

    // Update name and email
    if (!empty($newName) && !empty($newEmail)) {
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
        $stmt->bind_param('ssi', $newName, $newEmail, $userId);
        $stmt->execute();
        $stmt->close();
        $_SESSION['name'] = $newName;
        $_SESSION['email'] = $newEmail;
        $name = $newName;
        $email = $newEmail;
    }

    // Update password
    if (!empty($currentPassword) && !empty($newPassword)) {
        // Verify current password
        $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            if (password_verify($currentPassword, $row['password_hash'])) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $stmt->bind_param('si', $hashedPassword, $userId);
                $stmt->execute();
                $stmt->close();
            }
        }
        $stmt->close();
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-i18n-key="admin.settings.pageTitle">AgriHub - Admin Settings</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../Css/Admin-Dashboard.css">
</head>
<body>

    <header class="main-header-bar">
        <div class="header-left">
            <button class="hamburger-menu" id="hamburger-menu" aria-label="Toggle Menu">
                <i class="fa-solid fa-bars"></i>
            </button>
        </div>
        <div class="header-center">
            <div class="logo">
                <i class="fa-solid fa-leaf"></i>
                <span data-i18n-key="brand.name">AgriHub</span>
            </div>
        </div>
        <div class="header-right">
            <a href="Admin-Settings.php" class="profile-link" aria-label="User Settings">
                <div class="profile-avatar"><?php echo htmlspecialchars($initial); ?></div>
            </a>
        </div>
    </header>

    <div class="dashboard-container">
        <aside class="sidebar" id="sidebar">
            <ul class="sidebar-nav">
                <li><a href="Admin-Dashboard.php" data-i18n-key="admin.nav.dashboard"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>
                <li><a href="Admin-User-Management.php" data-i18n-key="admin.nav.userManagement"><i class="fa-solid fa-users"></i> User Management</a></li>
                <li><a href="Admin-Listings-Management.php" data-i18n-key="admin.nav.listingManagement"><i class="fa-solid fa-store"></i> Listing Management</a></li>
                <li><a href="Admin-News-Management.php" data-i18n-key="admin.nav.newsManagement"><i class="fa-solid fa-newspaper"></i> News Management</a></li>
                <li><a href="Admin-Reports-Management.php" data-i18n-key="admin.nav.reports"><i class="fa-solid fa-flag"></i> Reports</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="content-wrapper">
                <div class="main-header">
                    <h1 data-i18n-key="admin.settings.title">Settings</h1>
                    <p data-i18n-key="admin.settings.subtitle">Manage your admin account settings.</p>
                </div>

                <div class="settings-grid">
                    <div class="card">
                        <h3 data-i18n-key="admin.settings.profile.title">Profile Information</h3>
                        <form method="POST" class="settings-form">
                            <div class="form-group">
                                <label for="name" data-i18n-key="admin.settings.profile.name">Name</label>
                                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="email" data-i18n-key="admin.settings.profile.email">Email</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                            </div>
                            <button type="submit" class="btn btn-primary" data-i18n-key="admin.settings.profile.save">Update Profile</button>
                        </form>
                    </div>

                    <div class="card">
                        <h3 data-i18n-key="admin.settings.security.title">Change Password</h3>
                        <form method="POST" class="settings-form">
                            <div class="form-group">
                                <label for="current_password" data-i18n-key="admin.settings.security.currentPassword">Current Password</label>
                                <input type="password" id="current_password" name="current_password" autocomplete="current-password" required>
                            </div>
                            <div class="form-group">
                                <label for="new_password" data-i18n-key="admin.settings.security.newPassword">New Password</label>
                                <input type="password" id="new_password" name="new_password" autocomplete="new-password" required>
                            </div>
                            <button type="submit" class="btn btn-primary" data-i18n-key="admin.settings.security.updatePassword">Change Password</button>
                        </form>
                    </div>
                </div>

                <div class="logout-section">
                    <h3 data-i18n-key="admin.settings.logout.title">Logout</h3>
                    <p data-i18n-key="admin.settings.logout.text">Sign out of your admin account.</p>
                    <a href="auth.php?action=logout" class="btn btn-danger" data-i18n-key="admin.settings.logout.button">Logout</a>
                </div>
            </div>
        </main>
    </div>

    <script type="module" src="/AgriHub/Js/dashboard.js"></script>
    <script type="module" src="/AgriHub/Js/site.js"></script>
</body>
</html>
<?php
$conn->close();
?>
