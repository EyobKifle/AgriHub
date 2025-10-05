<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../HTML/Login.html');
    exit();
}
require_once __DIR__ . '/../php/config.php';
require_once __DIR__ . '/utils.php';

$userId = (int)$_SESSION['user_id'];
$name = $_SESSION['name'] ?? 'User';
$email = $_SESSION['email'] ?? '';
$initial = strtoupper(mb_substr($name, 0, 1));

// Load current user preferences from DB
$prefs = [
    'language_preference' => 'en',
    'pref_email_notifications' => true,
    'pref_theme' => 'light'
];
if ($stmt = $conn->prepare('SELECT language_preference, pref_email_notifications, pref_theme FROM user_profiles WHERE user_id = ?')) {
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $prefs['language_preference'] = $row['language_preference'] ?? 'en';
        $prefs['pref_email_notifications'] = (bool)$row['pref_email_notifications'];
        $prefs['pref_theme'] = $row['pref_theme'] ?? 'light';
    }
    $stmt->close();
}
$currentLang = $prefs['language_preference'];
$prefEmailNotif = $prefs['pref_email_notifications'];
$prefDarkMode = ($prefs['pref_theme'] === 'dark');

// Handle settings POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Checkboxes: if missing from POST, treat as off
    $newPrefEmailNotif = isset($_POST['email_notifications']);
    $newPrefTheme = isset($_POST['dark_mode']) ? 'dark' : 'light';

    // Language preference persists to DB
    $newLang = $_POST['language'] ?? $currentLang;
    if (!in_array($newLang, ['en', 'am', 'om', 'ti'], true)) {
        $newLang = 'en';
    }
    
    // Upsert into user_profiles
    if ($stmt = $conn->prepare('INSERT INTO user_profiles (user_id, language_preference, pref_email_notifications, pref_theme) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE language_preference = VALUES(language_preference), pref_email_notifications = VALUES(pref_email_notifications), pref_theme = VALUES(pref_theme)')) {
        $stmt->bind_param('isis', $userId, $newLang, $newPrefEmailNotif, $newPrefTheme);
        $stmt->execute();
        $stmt->close();
    }

    header('Location: user-settings.php?saved=1');
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - AgriHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../Css/User-Dashboard.css">
</head>
<body>
    <!-- Full-width Header -->
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
            <a href="user-profile.php" class="profile-link" aria-label="User Profile">
                <div class="profile-avatar"><?php echo e($initial); ?></div>
            </a>
        </div>
    </header>

    <div class="dashboard-container">
        <aside class="sidebar" id="sidebar">
            <ul class="sidebar-nav">
                <li><a href="user-dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>
                <li><a href="user-profile.php"><i class="fa-solid fa-user"></i> My Profile</a></li>
                <li><a href="user-listings.php"><i class="fa-solid fa-list-check"></i> My Listings</a></li>
                <li><a href="user-orders.php"><i class="fa-solid fa-receipt"></i> Order History</a></li>
                <li><a href="user-messages.php"><i class="fa-solid fa-envelope"></i> Messages</a></li>
                <li><a href="user-discussions.php"><i class="fa-solid fa-comments"></i> My Discussions</a></li>
                <li><a href="user-settings.php" class="active"><i class="fa-solid fa-gear"></i> Settings</a></li>
                <hr>
                <!-- Site Navigation Links -->
                <li><a href="User-Marketplace.php" data-i18n-key="header.nav.marketplace"><i class="fa-solid fa-store"></i> Marketplace</a></li>
                <li><a href="News.php" data-i18n-key="header.nav.news"><i class="fa-regular fa-newspaper"></i> News</a></li>
                <li><a href="Community.php" data-i18n-key="header.nav.community"><i class="fa-solid fa-users"></i> Community</a></li>
            </ul>
            <div class="sidebar-footer">
                <div class="profile-dropdown">
                    <div>
                        <div class="profile-name"><?php echo e($name); ?></div>
                        <div class="profile-email" style="opacity:.8; font-size:12px;">
                            <?php echo e($email); ?>
                        </div>
                        <small><a href="../php/auth.php?action=logout" style="color:inherit; text-decoration:none;">Logout</a></small>
                    </div>
                </div>
            </div>
        </aside>

        <main class="main-content">
            <div class="main-header">
                <h1>Settings</h1>
                <p>Manage your account preferences and notification settings.</p>
            </div>

            <?php if (isset($_GET['saved'])): ?>
                <div class="alert alert-success" style="background-color: #d4edda; color: #155724; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                    Your settings have been saved successfully.
                </div>
            <?php endif; ?>

            <div class="settings-grid">
                <div class="card">
                    <h3 class="card-title">General Preferences</h3>
                    <form method="post" action="user-settings.php" class="settings-form">
                        <div class="form-group">
                            <label for="language">Language</label>
                            <select id="language" name="language">
                                <option value="en" <?php if ($currentLang === 'en') echo 'selected'; ?>>English</option>
                                <option value="am" <?php if ($currentLang === 'am') echo 'selected'; ?>>Amharic</option>
                                <option value="om" <?php if ($currentLang === 'om') echo 'selected'; ?>>Oromo</option>
                                <option value="ti" <?php if ($currentLang === 'ti') echo 'selected'; ?>>Tigrinya</option>
                            </select>
                        </div>
                        <div class="form-group form-group-toggle">
                            <label for="dark_mode">Dark Mode</label>
                            <label class="switch">
                                <input type="checkbox" id="dark_mode" name="dark_mode" <?php if ($prefDarkMode) echo 'checked'; ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Save Preferences</button>
                        </div>
                    </form>
                </div>

                <div class="card">
                    <h3 class="card-title">Notification Settings</h3>
                    <form method="post" action="user-settings.php" class="settings-form">
                        <div class="form-group form-group-toggle">
                            <label for="email_notifications">Email Notifications</label>
                            <label class="switch">
                                <input type="checkbox" id="email_notifications" name="email_notifications" <?php if ($prefEmailNotif) echo 'checked'; ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                        <p class="form-text">Receive emails about new messages, order updates, and important announcements.</p>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Save Notifications</button>
                        </div>
                    </form>
                </div>

                <div class="card">
                    <h3 class="card-title">Account Security</h3>
                    <form method="post" action="change-password.php" class="settings-form">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" required>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Change Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script type="module" src="../Js/dashboard.js"></script>
</body>
</html>
