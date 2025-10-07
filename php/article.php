<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/Login.php');
    exit;
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utils.php';

$userId = (int)$_SESSION['user_id'];
$name = $_SESSION['name'] ?? 'User';
$email = $_SESSION['email'] ?? '';
$initial = strtoupper(mb_substr($name, 0, 1));
$avatar_url = '';

// Fetch user avatar for header/sidebar
$stmt_avatar = $conn->prepare("SELECT avatar_url FROM users WHERE id = ?");
if ($stmt_avatar) {
    $stmt_avatar->bind_param('i', $userId);
    $stmt_avatar->execute();
    $avatar_url = $stmt_avatar->get_result()->fetch_object()->avatar_url ?? '';
    $stmt_avatar->close();
}

$currentPage = 'User-News';

// Fetch articles from the database
$articles = [];
$sql = "
    SELECT a.id, a.title, a.excerpt, a.image_url, a.created_at, u.name as author_name, cc.name_key as category_name_key
    FROM articles a
    JOIN users u ON a.author_id = u.id
    JOIN content_categories cc ON a.category_id = cc.id
    WHERE a.status = 'published'
    ORDER BY a.created_at DESC LIMIT 20
";
$stmt_articles = $conn->prepare($sql);
if ($stmt_articles) {
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
    <title>News - AgriHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../Css/User-Dashboard.css">
    <link rel="stylesheet" href="../Css/User-News.css">
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
                <div class="profile-avatar"><?php if (!empty($avatar_url)): ?><img src="../<?php echo e($avatar_url); ?>" alt="User Avatar"><?php else: echo e($initial); endif; ?></div>
            </a>
        </div>
    </header>

    <div class="dashboard-container">
        <?php include '_sidebar.php'; ?>

        <main class="main-content">
            <div class="main-header">
                <h1 data-i18n-key="news.page.title">Agricultural News</h1>
                <p data-i18n-key="news.page.subtitle">Stay updated with the latest news, policies, and developments in Ethiopian agriculture.</p>
            </div>

            <div class="news-grid">
                <?php if (empty($articles)): ?>
                    <div class="card" style="grid-column: 1 / -1; text-align: center; padding: 2rem;">
                        <p>No news articles found at the moment.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($articles as $article): ?>
                    <div class="news-card">
                        <a href="User-Article.php?id=<?php echo (int)$article['id']; ?>" class="news-card-image-link">
                            <img src="../<?php echo e(empty($article['image_url']) ? 'https://placehold.co/400x250?text=No+Image' : $article['image_url']); ?>" alt="<?php echo e($article['title']); ?>" class="news-card-image">
                        </a>
                        <div class="news-card-content">
                            <div class="news-card-meta">
                                <span class="news-card-author">By <?php echo e($article['author_name']); ?></span> | <span class="news-card-time"><?php echo time_ago($article['created_at']); ?></span>
                            </div>
                            <h3 class="news-card-title"><a href="User-Article.php?id=<?php echo (int)$article['id']; ?>"><?php echo e($article['title']); ?></a></h3>
                            <p class="news-card-desc"><?php echo e($article['excerpt']); ?></p>
                            <a href="User-Article.php?id=<?php echo (int)$article['id']; ?>" class="read-more-link" data-i18n-key="common.readMore">Read More »</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script type="module" src="../Js/dashboard.js"></script>
</body>
</html>