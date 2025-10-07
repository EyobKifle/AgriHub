<?php
session_start();
require_once __DIR__ . '/config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../HTML/login.html');
    exit();
}

$name = $_SESSION['name'] ?? 'Admin';
$email = $_SESSION['email'] ?? '';
$initial = !empty($name) ? strtoupper($name[0]) : 'A';

// Handle form submission for adding/editing news
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0); // Assuming category is handled by ID
    $articleId = (int)($_POST['news_id'] ?? 0); // This is the article ID
    $imageUrl = $_POST['existing_image_url'] ?? ''; // Keep existing image if no new one is uploaded

    // Handle file upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/news_images/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileName = uniqid() . '-' . basename($_FILES['image']['name']);
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
            // If there was an old image for an existing article, delete it
            if ($articleId > 0 && !empty($imageUrl) && file_exists('..' . $imageUrl)) {
                unlink('..' . $imageUrl);
            }
            $imageUrl = '/uploads/news_images/' . $fileName; // Store relative path for web access
        }
    }

    if (!empty($title) && !empty($content) && $category_id > 0) {
        if ($articleId > 0) {
            // Update
            $stmt = $conn->prepare("UPDATE articles SET title = ?, content = ?, category_id = ?, image_url = ? WHERE id = ?");
            $stmt->bind_param('ssisi', $title, $content, $category_id, $imageUrl, $articleId);
        } else {
            // Insert
            $stmt = $conn->prepare("INSERT INTO articles (title, content, category_id, author_id, image_url, status) VALUES (?, ?, ?, ?, ?, 'published')");
            $stmt->bind_param('ssiis', $title, $content, $category_id, $_SESSION['user_id'], $imageUrl);
        }

        if ($stmt->execute()) {
            $stmt->close();
            header("Location: " . $_SERVER['PHP_SELF'] . "?success=saved");
            exit();
        } else {
            // Handle error
            $error = "Database error: " . $stmt->error;
        }
    }
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $articleId = (int)$_POST['article_id'];
    $stmt = $conn->prepare("DELETE FROM articles WHERE id = ?");
    // First, get the image_url to delete the file
    $img_stmt = $conn->prepare("SELECT image_url FROM articles WHERE id = ?");
    $img_stmt->bind_param('i', $articleId);
    $img_stmt->execute();
    $result = $img_stmt->get_result()->fetch_assoc();
    $img_stmt->close();

    if ($result && !empty($result['image_url']) && file_exists('..' . $result['image_url'])) {
        unlink('..' . $result['image_url']);
    }

    if ($stmt) {
        $stmt->bind_param('i', $articleId);
        $stmt->execute();
        $stmt->close();
        // Using AJAX now, so we echo a response instead of redirecting.
    }
}

// Fetch news categories for the form dropdown
$cat_stmt = $conn->prepare("SELECT id, name_key FROM content_categories WHERE type = 'news' ORDER BY name_key");
$cat_stmt->execute();
$categories = $cat_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$cat_stmt->close();

// Fetch all articles to display in the grid
$stmt = $conn->prepare("
    SELECT a.id, a.title, a.content, a.status, a.category_id, a.image_url, cc.name_key as category_name, a.created_at
    FROM articles a
    LEFT JOIN content_categories cc ON a.category_id = cc.id 
    ORDER BY a.created_at DESC
");
$stmt->execute();
$newsList = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<?php
function get_summary($html, $word_limit = 20) {
    $text = strip_tags($html);
    $words = preg_split("/[\n\r\t ]+/", $text, $word_limit + 1, PREG_SPLIT_NO_EMPTY);
    if (count($words) > $word_limit) {
        array_pop($words);
        return implode(' ', $words) . '...';
    }
    return implode(' ', $words);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgriHub - Admin News Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../Css/Admin-Dashboard.css">
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
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
            <a href="Admin-Settings.php" class="profile-link" aria-label="User Settings">
                <div class="profile-avatar"><?php echo htmlspecialchars($initial); ?></div>
            </a>
        </div>
    </header>

    <div class="dashboard-container">
        <aside class="sidebar" id="sidebar">
            <ul class="sidebar-nav">
                <li><a href="Admin-Dashboard.php" data-i18n-key="admin.nav.dashboard"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>
                <li><a href="Admin-User-Management.php" data-i18n-key="admin.nav.userManagement"><i class="fa-solid fa-users"></i> User Management</a></li>
                <li><a href="Admin-Listings-Management.php" data-i18n-key="admin.nav.listingManagement"><i class="fa-solid fa-store"></i> Listing Management</a></li>
                <li><a href="Admin-News-Management.php" class="active" data-i18n-key="admin.nav.newsManagement"><i class="fa-solid fa-newspaper"></i> News Management</a></li>
                <li><a href="Admin-Reports-Management.php" data-i18n-key="admin.nav.reports"><i class="fa-solid fa-flag"></i> Reports</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="content-wrapper">
                <div class="main-header">
                    <h1>News Management</h1>
                    <p>Manage news articles on the platform.</p>
                </div>

                <div class="form-container">
                    <form method="POST" class="form-group" enctype="multipart/form-data">
                        <input type="hidden" name="news_id" id="news_id" value="">
                        <input type="hidden" name="existing_image_url" id="existing_image_url" value="">
                        <label for="title">Title</label>
                        <input type="text" id="title" name="title" required>
                        <label for="image">Feature Image</label>
                        <input type="file" id="image" name="image" accept="image/*">
                        <label for="category_id">Category</label>
                        <select id="category_id" name="category_id" required>
                            <option value="">-- Select a Category --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo (int)$cat['id']; ?>">
                                    <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $cat['name_key']))); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <label for="content">Content</label>
                        <textarea id="content" name="content" rows="6" required></textarea>
                        <button type="submit" class="btn btn-primary">Add News</button>
                    </form>
                </div>

                <div class="admin-news-grid">
                    <?php foreach ($newsList as $news): ?>
                    <div class="news-card">
                        <div class="card-content">
                            <div class="card-category"><?php echo htmlspecialchars($news['category_name'] ?? 'Uncategorized'); ?></div>
                            <h3><?php echo htmlspecialchars($news['title']); ?></h3>
                            <div class="card-meta">Published: <?php echo htmlspecialchars(date('Y-m-d', strtotime($news['created_at']))); ?></div>
                            <div class="card-admin-overlay">
                                <button class="btn btn-sm btn-primary" onclick="editNews(<?php echo $news['id']; ?>)">Edit</button>
                                <a href="?delete=<?php echo $news['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this news?')">Delete</a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>

    <script src="../Js/dashboard.js" type="module"></script>
    <script>
     