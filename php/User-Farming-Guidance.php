<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../HTML/Farming-Guidance.html');
    exit();
}
require_once __DIR__ . '/utils.php';

$name = $_SESSION['name'] ?? 'User';
$email = $_SESSION['email'] ?? '';
$initial = strtoupper(mb_substr($name, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farming Guidance - AgriHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../Css/User-Dashboard.css">
    <link rel="stylesheet" href="../Css/guidance.css">
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
                <span>AgriHub</span>
            </div>
        </div>
        <div class="header-right">
            <a href="User-Profile.php" class="profile-link" aria-label="User Profile">
                <div class="profile-avatar"><?php echo e($initial); ?></div>
            </a>
        </div>
    </header>
    
    <div class="dashboard-container">
        <aside class="sidebar" id="sidebar">
            <ul class="sidebar-nav">
                <li><a href="User-Dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>
                <li><a href="User-Profile.php"><i class="fa-solid fa-user"></i> My Profile</a></li>
                <li><a href="User-Listings.php"><i class="fa-solid fa-list-check"></i> My Listings</a></li>
                <li><a href="User-Orders.php"><i class="fa-solid fa-receipt"></i> Order History</a></li>
                <li><a href="User-Messages.php"><i class="fa-solid fa-envelope"></i> Messages</a></li>
                <li><a href="User-Discussions.php"><i class="fa-solid fa-comments"></i> My Discussions</a></li>
                <li><a href="User-Settings.php"><i class="fa-solid fa-gear"></i> Settings</a></li>
                <hr>
                <li><a href="User-Marketplace.php" data-i18n-key="header.nav.marketplace"><i class="fa-solid fa-store"></i> Marketplace</a></li>
                <li><a href="User-News.php" data-i18n-key="header.nav.news"><i class="fa-regular fa-newspaper"></i> News</a></li>
                <li><a href="User-Community.php" data-i18n-key="header.nav.community"><i class="fa-solid fa-users"></i> Community</a></li>
                <li><a href="User-Farming-Guidance.php" class="active" data-i18n-key="header.nav.guidance"><i class="fa-solid fa-book-open"></i> Farming Guidance</a></li>
            </ul>
             <div class="sidebar-footer">
                <div class="profile-dropdown">
                    <div>
                        <div class="profile-name"><?php echo e($name); ?></div>
                        <div class="profile-email" style="opacity:.8; font-size:12px;"><?php echo e($email); ?></div>
                        <small><a href="auth.php?action=logout" style="color:inherit; text-decoration:none;" data-i18n-key="user.nav.logout">Logout</a></small>
                    </div>
                </div>
            </div>
        </aside>

        <main class="main-content">
            <!-- Original Farming-Guidance.html content starts here -->
            <div class="main-header">
                <h1 data-i18n-key="guidance.page.title">Farming Guidance</h1>
                <p data-i18n-key="guidance.page.subtitle">Master content map for Ethiopian farming: crops, livestock, techniques, health & safety, business, innovation, and policy. Click any topic to open details.</p>
            </div>
            <div class="guidance-map-container" id="guidance-map-container">
                <!-- Guidance map will be loaded by JS -->
            </div>
            <!-- Original Farming-Guidance.html content ends here -->
        </main>
    </div>

    <script type="module" src="../Js/dashboard.js"></script>
</body>
</html>