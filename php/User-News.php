<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../HTML/News.html');
    exit();
}
require_once __DIR__ . '/utils.php';

$name = $_SESSION['name'] ?? 'User';
$email = $_SESSION['email'] ?? '';
$initial = strtoupper(mb_substr($name, 0, 1));
$currentPage = 'User-News';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>News - AgriHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../Css/User-Dashboard.css">
    <link rel="stylesheet" href="../Css/news.css">
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
            <!-- Original News.html content starts here -->
            <div class="news-layout">
                <aside class="news-sidebar">
                    <div class="sidebar-section">
                        <h3 data-i18n-key="news.sidebar.categories">Categories</h3>
                        <ul class="category-list" id="category-list">
                            <!-- Categories will be loaded by JS -->
                        </ul>
                    </div>
                    <div class="sidebar-section">
                        <h3 data-i18n-key="news.sidebar.trending">Trending Topics</h3>
                        <div class="tag-list" id="tag-list">
                            <!-- Tags will be loaded by JS -->
                        </div>
                    </div>
                </aside>
                <section class="main-content" style="padding:0; border:none; background:none;">
                    <div id="news-grid" class="news-grid">
                        <!-- News articles will be loaded here by JS -->
                    </div>
                    <div id="empty-state" class="empty-state" hidden>
                        <i class="fa-regular fa-folder-open"></i>
                        <p data-i18n-key="news.emptyState">No news articles match the current filter.</p>
                    </div>
                </section>
            </div>
            <!-- Original News.html content ends here -->
        </main>
    </div>

    <script type="module" src="../Js/dashboard.js"></script>
</body>
</html>