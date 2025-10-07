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
$initial = !empty($name) ? strtoupper(mb_substr($name, 0, 1)) : 'U';
$article = null;
$related_articles = [];
$error_message = null;

$articleId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$articleId) {
    http_response_code(400);
    $error_message = "Invalid article ID provided.";
} else {
    // --- Increment view count ---
    $stmt_view = $conn->prepare("UPDATE articles SET view_count = view_count + 1 WHERE id = ?");
    if ($stmt_view) {
        $stmt_view->bind_param('i', $articleId);
        $stmt_view->execute();
        $stmt_view->close();
    }

    // --- Fetch the main article ---
    $stmt_article = $conn->prepare("
        SELECT a.id, a.title, a.content, a.image_url, a.created_at, a.view_count,
               u.name as author_name, u.avatar_url as author_avatar,
               cc.id as category_id, cc.name_key as category_name, cc.slug as category_slug
        FROM articles a
        JOIN users u ON a.author_id = u.id
        JOIN content_categories cc ON a.category_id = cc.id
        WHERE a.id = ? AND a.status = 'published'
    ");
    if ($stmt_article) {
        $stmt_article->bind_param('i', $articleId);
        $stmt_article->execute();
        $result = $stmt_article->get_result();
        $article = $result->fetch_assoc();
        $stmt_article->close();
    }

    if (!$article) {
        http_response_code(404);
        $error_message = "The article you are looking for could not be found.";
    } else {
        // --- Fetch related articles (from the same category) ---
        $stmt_related = $conn->prepare("
            SELECT id, title, image_url
            FROM articles
            WHERE category_id = ? AND id != ? AND status = 'published'
            ORDER BY created_at DESC
            LIMIT 3
        ");
        if ($stmt_related) {
            $stmt_related->bind_param('ii', $article['category_id'], $article['id']);
            $stmt_related->execute();
            $related_articles = $stmt_related->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt_related->close();
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $article ? e($article['title']) : 'Article'; ?> - AgriHub Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/AgriHub/Css/User-Dashboard.css">
    <link rel="stylesheet" href="/AgriHub/Css/article.css">
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
                <div class="profile-avatar"><?php echo e($initial); ?></div>
            </a>
        </div>
    </header>

    <div class="dashboard-container">
        <?php $currentPage = 'User-News'; include '_sidebar.php'; ?>

        <main class="main-content">
            <div class="content-wrapper article-view">
                <?php if ($error_message): ?>
                    <div class="error-message">
                        <h1>Error</h1>
                        <p><?php echo e($error_message); ?></p>
                        <a href="User-News.php" class="btn btn-primary">Back to News</a>
                    </div>
                <?php elseif ($article): ?>
                    <article class="article-content">
                        <header class="article-header">
                            <a href="User-News.php?category=<?php echo e($article['category_slug']); ?>" class="article-category-link"><?php echo e(ucfirst(str_replace('_', ' ', $article['category_name']))); ?></a>
                            <h1 class="article-title"><?php echo e($article['title']); ?></h1>
                            <div class="article-meta">
                                <span>By <strong><?php echo e($article['author_name']); ?></strong></span>
                                <span>&bull;</span>
                                <span><?php echo date('F j, Y', strtotime($article['created_at'])); ?></span>
                                <span>&bull;</span>
                                <span><i class="fa-solid fa-eye"></i> <?php echo number_format($article['view_count']); ?> views</span>
                            </div>
                        </header>

                        <?php if (!empty($article['image_url'])): ?>
                            <figure class="article-image-container">
                                <img src="/AgriHub/<?php echo e($article['image_url']); ?>" alt="<?php echo e($article['title']); ?>" class="article-image">
                            </figure>
                        <?php endif; ?>

                        <div class="article-body">
                            <?php echo $article['content']; // Assuming content is trusted HTML from an admin editor ?>
                        </div>
                    </article>

                    <?php if (!empty($related_articles)): ?>
                        <aside class="related-articles">
                            <h2 class="related-articles-title">Related Articles</h2>
                            <div class="related-articles-grid">
                                <?php foreach ($related_articles as $related): ?>
                                    <a href="User-Article.php?id=<?php echo (int)$related['id']; ?>" class="related-article-card">
                                        <img src="/AgriHub/<?php echo e(empty($related['image_url']) ? 'https://placehold.co/300x200' : $related['image_url']); ?>" alt="<?php echo e($related['title']); ?>" class="related-article-image">
                                        <div class="related-article-content"><h3 class="related-article-title"><?php echo e($related['title']); ?></h3></div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </aside>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </main>
    </main>

    <script type="module" src="/AgriHub/Js/dashboard.js"></script>
</body>
</html>
