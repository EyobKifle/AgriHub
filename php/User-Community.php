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
$currentPage = 'User-Community';
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
            <a href="User-Account.php" class="profile-link" aria-label="User Profile">
                <div class="profile-avatar"><?php echo e($initial); ?></div>
            </a>
        </div>
    </header>
    
    <div class="dashboard-container">
        <?php include __DIR__ . '/_sidebar.php'; ?>

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