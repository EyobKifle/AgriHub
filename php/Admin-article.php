<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utils.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../HTML/login.html');
    exit();
}

$adminName = $_SESSION['name'] ?? 'Admin';
$adminInitial = !empty($adminName) ? strtoupper($adminName[0]) : 'A';

$article = null;
$error_message = null;

$articleId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$articleId) {
    http_response_code(400);
    $error_message = "Invalid article ID provided.";
} else {
    // Handle POST actions for delete
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        if ($action === 'delete_article') {
            // First, get the image_url to delete the file
            $img_stmt = $conn->prepare("SELECT image_url FROM articles WHERE id = ?");
            $img_stmt->bind_param('i', $articleId);
            $img_stmt->execute();
            $result = $img_stmt->get_result()->fetch_assoc();
            $img_stmt->close();

            if ($result && !empty($result['image_url']) && file_exists('..' . $result['image_url'])) {
                unlink('..' . $result['image_url']);
            }

            // Then, delete the article
            $stmt = $conn->prepare("DELETE FROM articles WHERE id = ?");
            $stmt->bind_param('i', $articleId);
            if ($stmt->execute()) {
                header('Location: Admin-News-Management.php?success=deleted');
                exit();
            } else {
                $error_message = "Failed to delete article.";
            }
            $stmt->close();
        }
    }

    // Fetch article details
    $stmt_article = $conn->prepare("
        SELECT a.id, a.title, a.content, a.image_url, a.created_at, a.status,
               u.name as author_name,
               cc.name_key as category_name
        FROM articles a
        JOIN users u ON a.author_id = u.id
        LEFT JOIN content_categories cc ON a.category_id = cc.id
        WHERE a.id = ?
    ");
    $stmt_article->bind_param('i', $articleId);
    $stmt_article->execute();
    $article = $stmt_article->get_result()->fetch_assoc();
    $stmt_article->close();

    if (!$article) {
        http_response_code(404);
        $error_message = "Article not found.";
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin: <?php echo $article ? e($article['title']) : 'Article Detail'; ?> - AgriHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../Css/Admin-Dashboard.css">
    <link rel="stylesheet" href="../Css/Admin-article.css">
</head>

<body>
    <header class="main-header-bar">
        <div class="header-left"><a href="Admin-News-Management.php" class="back-link" style="font-size: 1.5rem; color: #fff; text-decoration: none;"><i class="fa-solid fa-arrow-left"></i></a></div>
        <div class="header-center">
            <div class="logo"><i class="fa-solid fa-leaf"></i><span>AgriHub</span></div>
        </div>
        <div class="header-right"><a href="Admin-Settings.php" class="profile-link">
                <div class="profile-avatar"><?php echo e($adminInitial); ?></div>
            </a></div>
    </header>

    <main class="main-content" style="margin-left:0; padding-top: 70px;">
        <div class="content-wrapper article-view">
            <?php if ($error_message): ?>
                <div class="error-message">
                    <h1>Error</h1>
                    <p><?php echo e($error_message); ?></p>
                    <a href="Admin-News-Management.php" class="btn btn-primary">Back to News Management</a>
                </div>
            <?php elseif ($article): ?>
                <div class="article-actions-bar">
                    <a href="Admin-News-Management.php?edit_id=<?php echo $article['id']; ?>#form-container" class="btn btn-primary"><i class="fa-solid fa-pencil"></i> Edit Article</a>
                </div>

                <article class="article-content">
                    <header class="article-header">
                        <span class="article-category-link"><?php echo e(ucfirst(str_replace('_', ' ', $article['category_name']))); ?></span>
                        <h1 class="article-title"><?php echo e($article['title']); ?></h1>
                        <div class="article-meta">
                            <span>By <strong><?php echo e($article['author_name']); ?></strong></span>
                            <span>&bull;</span>
                            <span><?php echo date('F j, Y', strtotime($article['created_at'])); ?></span>
                            <span>&bull;</span>
                            <span class="status status-<?php echo e(strtolower($article['status'])); ?>"><?php echo e($article['status']); ?></span>
                        </div>
                    </header>

                    <?php if (!empty($article['image_url'])): ?>
                        <figure class="article-image-container">
                            <img src="../<?php echo e($article['image_url']); ?>" alt="<?php echo e($article['title']); ?>" class="article-image">
                        </figure>
                    <?php endif; ?>

                    <div class="article-body">
                        <?php echo $article['content']; // Content is trusted HTML from admin's TinyMCE editor 
                        ?>
                    </div>
                </article>

                <div class="card danger-zone" style="margin-top: 2rem;">
                    <h3 class="card-title">Danger Zone</h3>
                    <p>This action is permanent and cannot be undone.</p>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to permanently delete this article?');">
                        <input type="hidden" name="action" value="delete_article">
                        <button type="submit" class="btn btn-danger">Delete This Article</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>

</html>