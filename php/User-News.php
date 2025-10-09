<?php
session_start();

// Ensure the user is logged in.
if (!isset($_SESSION['user_id'])) {
    header('Location: ../HTML/login.html?error=login_required');
    exit;
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utils.php';

$name = $_SESSION['name'] ?? 'User';
$email = $_SESSION['email'] ?? '';
$userId = (int)($_SESSION['user_id'] ?? 0);
$initial = !empty($name) ? strtoupper(mb_substr($name, 0, 1)) : 'U';
$avatar_url = '';

// Fetch user avatar for header/sidebar
$stmt_avatar = $conn->prepare("SELECT avatar_url FROM users WHERE id = ?");
if ($stmt_avatar) {
    $stmt_avatar->bind_param('i', $userId);
    $stmt_avatar->execute();
    $avatar_url = $stmt_avatar->get_result()->fetch_object()->avatar_url ?? '';
    $stmt_avatar->close();
}


// --- Fetch Categories with Article Counts ---
$categories = [];
$sql_cat = "
    SELECT cc.id, cc.name_key, cc.slug, COUNT(a.id) as article_count
    FROM content_categories cc
    LEFT JOIN articles a ON cc.id = a.category_id AND a.status = 'published'
    WHERE cc.type = 'news'
    GROUP BY cc.id, cc.name_key, cc.slug
    ORDER BY cc.name_key ASC
";
$stmt_cat = $conn->prepare($sql_cat);
if ($stmt_cat) {
    $stmt_cat->execute();
    $categories = $stmt_cat->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_cat->close();
}

// --- Handle Category Filtering ---
$selected_category_slug = isset($_GET['category']) ? trim($_GET['category']) : null;
$selected_category_id = null;
$selected_category_name = 'All Articles';

if ($selected_category_slug) {
    foreach ($categories as $cat) {
        if ($cat['slug'] === $selected_category_slug) {
            $selected_category_id = (int)$cat['id'];
            $selected_category_name = ucfirst(str_replace('_', ' ', $cat['name_key']));
            break;
        }
    }
}

// --- Fetch Articles ---
$articles = [];
$sql_articles = "
    SELECT a.id, a.title, a.excerpt, a.image_url, a.created_at, u.name as author_name, cc.name_key as category_name_key, cc.slug as category_slug
    FROM articles a
    JOIN users u ON a.author_id = u.id
    JOIN content_categories cc ON a.category_id = cc.id
    WHERE a.status = 'published'
";

$params = [];
$types = '';

if ($selected_category_id) {
    $sql_articles .= " AND a.category_id = ?";
    $params[] = $selected_category_id;
    $types .= 'i';
}

$sql_articles .= " ORDER BY a.created_at DESC LIMIT 20";

$stmt_articles = $conn->prepare($sql_articles);
if ($stmt_articles) {
    if (!empty($params)) {
        $stmt_articles->bind_param($types, ...$params);
    }
    $stmt_articles->execute();
    $articles = $stmt_articles->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_articles->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-i18n-key="user.news.pageTitle">News - AgriHub Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/AgriHub/Css/User-Dashboard.css"> <!-- Main dashboard styles -->
    <link rel="stylesheet" href="/AgriHub/Css/User-News.css">      <!-- Page-specific styles -->
    <link rel="stylesheet" href="/AgriHub/Css/News.css">           <!-- Re-use news card and grid styles -->
</head>
<body>
    <header class="main-header-bar">
        <div class="header-left">
            <button class="hamburger-menu" id="hamburger-menu" aria-label="Toggle Menu"><i class="fa-solid fa-bars"></i></button>
        </div>
        <div class="header-center">
            <div class="logo"><i class="fa-solid fa-leaf"></i><span data-i18n-key="brand.name">AgriHub</span></div>
        </div>
        <div class="header-right">
            <a href="User-Account.php" class="profile-link" aria-label="User Profile">
                <div class="profile-avatar">
                    <?php if (!empty($avatar_url)): ?><img src="/AgriHub/<?php echo e($avatar_url); ?>" alt="User Avatar"><?php else: ?><?php echo e($initial); ?><?php endif; ?>
                </div>
            </a>
        </div>
    </header>

    <div class="dashboard-container">
        <?php $currentPage = 'User-News'; include '_sidebar.php'; ?>

        <main class="main-content">
            <div class="news-wrapper">
                <div class="page-header">
                    <h1 data-i18n-key="news.page.title">Agricultural News</h1>
                    <p data-i18n-key="news.page.subtitle">Stay updated with the latest news, policies, and developments in Ethiopian agriculture.</p>
                </div>

                <div class="news-layout">
                    <aside class="news-sidebar">
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <div class="sidebar-section">
                            <a href="Admin-News-Management.php" class="btn btn-primary full-width" data-i18n-key="news.create.button"><i class="fa-solid fa-plus"></i> Create New Article</a>
                        </div>
                        <?php endif; ?>
                        <div class="sidebar-section">
                            <h3 data-i18n-key="news.sidebar.categories">Categories</h3>
                            <ul class="category-list">
                                <li><a href="User-News.php" class="<?php echo !$selected_category_slug ? 'active' : ''; ?>" data-i18n-key="news.sidebar.all">All Articles</a></li>
                                <?php foreach ($categories as $category): ?>
                                    <li>
                                        <a href="User-News.php?category=<?php echo e($category['slug']); ?>" class="<?php echo $selected_category_slug === $category['slug'] ? 'active' : ''; ?>" data-i18n-key="<?php echo e($category['name_key']); ?>">
                                            <?php echo e(ucfirst(str_replace('_', ' ', $category['name_key']))); ?>
                                            <span><?php echo (int)$category['article_count']; ?></span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </aside>

                    <div class="main-news-content">
                        <div class="news-grid">
                            <?php if (empty($articles)): ?>
                                <div class="empty-state">
                                    <p data-i18n-key="news.emptyState">No articles found in this category.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($articles as $article): ?>
                                <a href="User-Article.php?id=<?php echo (int)$article['id']; ?>" class="news-card-link">
                                    <div class="news-card">
                                        <div class="news-card-image-link">
                                            <img src="/AgriHub/<?php echo e(empty($article['image_url']) ? 'https://placehold.co/400x250?text=No+Image' : $article['image_url']); ?>" alt="<?php echo e($article['title']); ?>" class="news-card-image">
                                        </div>
                                        <div class="news-card-content">
                                            <div class="news-card-category" data-i18n-key="<?php echo e($article['category_name_key']); ?>"><?php echo e(ucfirst(str_replace('_', ' ', $article['category_name_key']))); ?></div>
                                            <h3 class="news-card-title">
                                                <?php echo e($article['title']); ?>

                                            </h3>
                                            <p class="news-card-excerpt"><?php echo e($article['excerpt']); ?></p>
                                            <div class="news-card-meta">
                                                <span><span data-i18n-key="common.by">By</span> <?php echo e($article['author_name']); ?></span> &bull; <span><?php echo time_ago($article['created_at']); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </main>

    <script type="module" src="/AgriHub/Js/dashboard.js"></script>
</body>
</html>
