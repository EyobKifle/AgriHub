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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $newsId = (int)($_POST['news_id'] ?? 0);

    if (!empty($title) && !empty($content)) {
        if ($newsId > 0) {
            // Update
            $stmt = $conn->prepare("UPDATE news SET title = ?, content = ?, category = ? WHERE id = ?");
            $stmt->bind_param('sssi', $title, $content, $category, $newsId);
        } else {
            // Insert
            $stmt = $conn->prepare("INSERT INTO news (title, content, category, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param('sss', $title, $content, $category);
        }
        $stmt->execute();
        $stmt->close();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $newsId = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM news WHERE id = ?");
    $stmt->bind_param('i', $newsId);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Fetch news
$stmt = $conn->prepare("SELECT id, title, category, created_at FROM news ORDER BY created_at DESC");
$stmt->execute();
$newsList = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgriHub - Admin News Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../Css/Admin-Dashboard.css">
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
                    <form method="POST" class="form-group">
                        <input type="hidden" name="news_id" value="">
                        <label for="title">Title</label>
                        <input type="text" id="title" name="title" required>
                        <label for="category">Category</label>
                        <input type="text" id="category" name="category">
                        <label for="content">Content</label>
                        <textarea id="content" name="content" rows="10" required></textarea>
                        <button type="submit" class="btn btn-primary">Add News</button>
                    </form>
                </div>

                <div class="admin-news-grid">
                    <?php foreach ($newsList as $news): ?>
                    <div class="news-card">
                        <div class="card-content">
                            <div class="card-category"><?php echo htmlspecialchars($news['category']); ?></div>
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
        function editNews(id) {
            // Implement edit functionality, perhaps fetch data and populate form
            alert('Edit functionality not implemented yet.');
        }
    </script>
</body>
</html>
<?php
$conn->close();
?>
