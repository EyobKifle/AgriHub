<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../HTML/Login.html');
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
    <title>My Discussions - AgriHub</title>
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
            <a href="User-Profile.php" class="profile-link" aria-label="User Profile">
                <div class="profile-avatar" id="user-initial-avatar"><?php echo e($initial); ?></div>
            </a>
        </div>
    </header>
    
    <div class="dashboard-container">
        <aside class="sidebar" id="sidebar">
            <ul class="sidebar-nav">
                <li><a href="User-Dashboard.php" data-i18n-key="user.nav.dashboard"><i class="fa-solid fa-house"></i> Dashboard</a></li>
                <li><a href="User-Profile.php" data-i18n-key="user.nav.profile"><i class="fa-solid fa-user"></i> My Profile</a></li>
                <li><a href="User-Listings.php" data-i18n-key="user.nav.listings"><i class="fa-solid fa-list-check"></i> My Listings</a></li>
                <li><a href="User-Orders.php" data-i18n-key="user.nav.orders"><i class="fa-solid fa-receipt"></i> Order History</a></li>
                <li><a href="User-Messages.php" data-i18n-key="user.nav.messages"><i class="fa-solid fa-envelope"></i> Messages</a></li>
                <li><a href="User-Discussions.php" class="active" data-i18n-key="user.nav.discussions"><i class="fa-solid fa-comments"></i> My Discussions</a></li>
                <li><a href="User-Settings.php" data-i18n-key="user.nav.settings"><i class="fa-solid fa-gear"></i> Settings</a></li>
                <hr>
                <!-- Site Navigation Links -->
                <li><a href="User-Marketplace.php" data-i18n-key="header.nav.marketplace"><i class="fa-solid fa-store"></i> Marketplace</a></li>
                <li><a href="User-News.php" data-i18n-key="header.nav.news"><i class="fa-regular fa-newspaper"></i> News</a></li>
                <li><a href="User-Community.php" data-i18n-key="header.nav.community"><i class="fa-solid fa-users"></i> Community</a></li>
                <li><a href="User-Farming-Guidance.php" data-i18n-key="header.nav.guidance"><i class="fa-solid fa-book-open"></i> Farming Guidance</a></li>
            </ul>
             <div class="sidebar-footer">
                <div class="profile-dropdown">
                    <div>
                        <div class="profile-name" id="user-profile-name"><?php echo e($name); ?></div>
                        <div class="profile-email" id="user-profile-email" style="opacity:.8; font-size:12px;"><?php echo e($email); ?></div>
                        <small><a href="auth.php?action=logout" style="color:inherit; text-decoration:none;" data-i18n-key="user.nav.logout">Logout</a></small>
                    </div>
                </div>
            </div>
        </aside>

        <main class="main-content">
            <div class="main-header">
                <h1 data-i18n-key="user.discussions.title">My Discussions</h1>
                <p data-i18n-key="user.discussions.subtitle">Keep track of conversations you've started or participated in.</p>
            </div>

            <div class="page-controls">
                <button type="button" id="create-discussion-btn" class="btn btn-primary" data-i18n-key="user.discussions.new"><i class="fa-solid fa-plus"></i> Start New Discussion</button>
                <span id="form-status-message" style="margin-left:12px;"></span>
            </div>

            <div class="card" id="create-discussion-card" style="display: none;">
                <h3 class="card-title" data-i18n-key="user.discussions.newDiscussionTitle">Start New Discussion</h3>
                <form id="create-discussion-form">
                    <input type="hidden" name="action" value="create_discussion">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="title">Title</label>
                            <input type="text" id="title" name="title" required>
                        </div>
                        <div class="form-group">
                            <label for="category_id">Category</label>
                            <select id="category_id" name="category_id" required>
                                <!-- Categories will be populated by JS -->
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="content">Content</label>
                        <textarea id="content" name="content" rows="4" placeholder="Write your discussion topic..." required></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary" data-i18n-key="user.discussions.actions.create">Create</button>
                    </div>
                </form>
            </div>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th data-i18n-key="user.discussions.table.topic">Topic</th>
                            <th data-i18n-key="user.discussions.table.category">Category</th>
                            <th data-i18n-key="user.discussions.table.lastReply">Last Update</th>
                            <th data-i18n-key="user.discussions.table.replies">Replies</th>
                            <th data-i18n-key="user.discussions.table.actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="discussions-table-body">
                        <!-- Discussion rows will be populated by JS -->
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    
    <script type="module" src="../Js/dashboard.js"></script>
    <script type="module" src="../Js/User-Discussions.js"></script>
</body>
</html>
