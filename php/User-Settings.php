<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /AgriHub/HTML/Login.html');
    exit();
}
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utils.php';

$userId = (int)$_SESSION['user_id'];
$name = $_SESSION['name'] ?? 'User';
$email = $_SESSION['email'] ?? '';
$initial = strtoupper(mb_substr($name, 0, 1));
$avatar_url = '';

// Fetch user avatar for header/sidebar
$stmt_avatar = $conn->prepare("SELECT avatar_url, language_preference, pref_email_notifications, pref_theme FROM user_profiles p JOIN users u ON p.user_id = u.id WHERE u.id = ?");
if ($stmt_avatar) {
    $stmt_avatar->bind_param('i', $userId);
    $stmt_avatar->execute();
    $avatar_url = $stmt_avatar->get_result()->fetch_object()->avatar_url ?? '';
    $stmt_avatar->close();
}

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

    // After saving, we can just let the page render with the new values.
    // Let's re-fetch the new values to be sure.
    $currentLang = $newLang;
    $prefEmailNotif = $newPrefEmailNotif;
    $prefDarkMode = ($newPrefTheme === 'dark');
    
    // We can show a success message without a full page reload.
    $savedMessage = "Your settings have been saved successfully.";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - AgriHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/AgriHub/Css/User-Dashboard.css">
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
            <a href="User-Account.php" class="profile-link" aria-label="User Profile">
                <div class="profile-avatar">
                    <?php if (!empty($avatar_url)): ?><img src="/AgriHub/<?php echo e($avatar_url); ?>" alt="User Avatar">
                    <?php else: ?><?php echo e($initial); ?><?php endif; ?>
                </div>
            </a>
        </div>
    </header>

    <div class="dashboard-container">
        <?php 
            $currentPage = 'User-Settings';
            include __DIR__ . '/_sidebar.php'; 
        ?>

        <main class="main-content">
            <div class="main-header">
                <h1 data-i18n-key="user.settings.title">Settings</h1>
                <p data-i18n-key="user.settings.subtitle">Manage your account preferences and site settings.</p>
                <?php if (isset($savedMessage)): ?>
                    <div class="alert alert-success" style="background-color: #d4edda; color: #155724; padding: 1rem; border-radius: 8px; margin-top: 1.5rem;">
                        <?php echo e($savedMessage); ?>
                    </div>
                <?php endif; ?>
            </div>

            <form class="settings-grid" method="post">
                <!-- Notification Settings -->
                <div class="card">
                    <h3 class="card-title" data-i18n-key="user.settings.notifications.title">Notifications</h3>
                    <div class="settings-form">
                        <div class="form-group form-group-toggle">
                            <label for="email-notifications" data-i18n-key="user.settings.notifications.email">Email Notifications</label>
                            <label class="switch">
                                <input type="checkbox" id="email-notifications" name="email_notifications" <?php echo $prefEmailNotif ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                        <p class="form-text" data-i18n-key="user.settings.notifications.desc">Receive updates about your listings and messages via email.</p>
                    </div>
                </div>

                <!-- Appearance Settings -->
                <div class="card">
                    <h3 class="card-title" data-i18n-key="user.settings.appearance.title">Appearance</h3>
                    <div class="settings-form">
                        <div class="form-group form-group-toggle">
                            <label for="dark-mode-toggle" data-i18n-key="user.settings.appearance.darkMode">Dark Mode</label>
                            <label class="switch">
                                <input type="checkbox" id="dark-mode-toggle" name="dark_mode" <?php echo $prefDarkMode ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                        <p class="form-text" data-i18n-key="user.settings.appearance.desc">Switch between light and dark themes.</p>
                    </div>
                </div>

                <!-- Language Settings -->
                <div class="card">
                    <h3 class="card-title" data-i18n-key="user.settings.language.title">Language</h3>
                    <div class="settings-form">
                        <div class="form-group">
                            <label for="language-select" data-i18n-key="user.settings.language.preferred">Preferred Language</label>
                            <select id="language-select" name="language">
                                <option value="en" <?php echo $currentLang==='en'?'selected':''; ?> data-i18n-key="lang.en">English</option>                                <option value="am" <?php echo $currentLang==='am'?'selected':''; ?> data-i18n-key="lang.amFull">Amharic (አማርኛ)</option>
                                <option value="om" <?php echo $currentLang==='om'?'selected':''; ?> data-i18n-key="lang.om">Oromo</option>
                                <option value="ti" <?php echo $currentLang==='ti'?'selected':''; ?> data-i18n-key="lang.ti">Tigrinya</option>
                            </select>
                        </div>
                        <p class="form-text" data-i18n-key="user.settings.language.desc">Choose the language for your dashboard interface.</p>
                    </div>
                </div>

                <div class="card" style="grid-column: 1 / -1;">
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Save Settings</button>
                    </div>
                </div>
            </form>
        </main>
    </div>

    <script type="module" src="/AgriHub/Js/dashboard.js"></script>
</body>
</html>
