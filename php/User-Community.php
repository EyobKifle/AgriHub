<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../HTML/Community.html');
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
    <title>Community - AgriHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../Css/User-Dashboard.css">
    <link rel="stylesheet" href="../Css/community.css">
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
                <li><a href="User-Community.php" class="active" data-i18n-key="header.nav.community"><i class="fa-solid fa-users"></i> Community</a></li>
                <li><a href="User-Farming-Guidance.php" data-i18n-key="header.nav.guidance"><i class="fa-solid fa-book-open"></i> Farming Guidance</a></li>
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
            <!-- Original Community.html content starts here -->
            <div class="community-layout">
                <aside class="community-sidebar">
                    <div class="sidebar-section">
                        <h3 data-i18n-key="community.sidebar.categories">Categories</h3>
                        <ul class="category-list" id="category-list">
                            <!-- Categories will be populated by JS -->
                        </ul>
                    </div>
                    <div class="sidebar-section">
                        <h3 data-i18n-key="community.stats.title">Community Stats</h3>
                        <ul class="stats-list">
                            <li><i class="fa-solid fa-users"></i> <span data-i18n-key="community.stats.members">Active Members</span> <strong id="active-members">0</strong></li>
                            <li><i class="fa-solid fa-comments"></i> <span data-i18n-key="community.stats.discussions">Discussions</span> <strong id="total-discussions">0</strong></li>
                        </ul>
                    </div>
                </aside>
                <section class="main-content" style="padding:0; border:none; background:none;">
                    <div class="search-bar">
                        <input id="search-input" type="text" data-i18n-placeholder-key="community.search.placeholder" placeholder="Search discussions...">
                    </div>
                    <div class="new-discussion-box">
                        <a href="User-Discussions.php" class="btn btn-primary">
                            <i class="fa-solid fa-plus"></i> <span data-i18n-key="community.new.title">Start a New Discussion</span>
                        </a>
                    </div>
                    <div class="discussions" id="discussion-cards">
                        <!-- Discussion cards will be populated by JS -->
                    </div>
                </section>
            </div>
            <!-- Original Community.html content ends here -->
        </main>
    </div>

    <script type="module" src="../Js/dashboard.js"></script>
</body>
</html>