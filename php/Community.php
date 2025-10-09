<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utils.php';

$selectedCategoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$userId = $_SESSION['user_id'] ?? 0;

// Handle POST request for creating a new discussion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_discussion' && $userId > 0) {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);

    if (!empty($title) && !empty($content) && $categoryId > 0) {
        $stmt = $conn->prepare("INSERT INTO discussions (author_id, category_id, title, content, status) VALUES (?, ?, ?, ?, 'published')");
        $stmt->bind_param('iiss', $userId, $categoryId, $title, $content);
        $stmt->execute();
        $newDiscussionId = $conn->insert_id;
        $stmt->close();

        header('Location: discussion.php?id=' . $newDiscussionId);
        exit();
    }
}

// Fetch categories
$categories = [];
$totalDiscussions = 0;
$stmt = $conn->prepare('SELECT c.id, c.name, COUNT(d.id) as count FROM discussion_categories c LEFT JOIN discussions d ON c.id = d.category_id GROUP BY c.id, c.name ORDER BY c.display_order, c.name');
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
        $totalDiscussions += (int)$row['count'];
    }
    $stmt->close();
}

// Fetch stats
$activeMembers = 0;
$stmt = $conn->prepare('SELECT COUNT(*) FROM users WHERE status = "active"');
if ($stmt) {
    $stmt->execute();
    $stmt->bind_result($activeMembers);
    $stmt->fetch();
    $stmt->close();
}

// Fetch latest discussions
$discussions = [];
$sql = "SELECT d.id, d.title, d.content, d.updated_at, d.comment_count, d.like_count, u.name as author_name, c.name as category_name 
        FROM discussions d 
        JOIN users u ON d.author_id = u.id 
        JOIN discussion_categories c ON d.category_id = c.id 
        WHERE d.status = 'published'";

$params = [];
$types = '';

if ($selectedCategoryId > 0) {
    $sql .= " AND d.category_id = ?";
    $params[] = $selectedCategoryId;
    $types .= 'i';
}

$sql .= " ORDER BY d.updated_at DESC LIMIT 20";

$stmt = $conn->prepare($sql);
if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $discussions[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title data-i18n-key="community.page.title">Community - AgriHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="../Css/header.css">
<link rel="stylesheet" href="../Css/community.css">
<link rel="stylesheet" href="../Css/footer.css">

</head>
<body>
    <div id="header-placeholder"></div>

    <main class="page-container">
        <div class="content-wrapper">
            <header class="page-header">
                <h1 data-i18n-key="community.page.title">Community Forum</h1>
                <p data-i18n-key="community.page.subtitle">Connect with fellow farmers, share knowledge, and get answers to your questions</p>
            </header>

            <div class="community-layout">
                <aside class="community-sidebar">
                    <div class="sidebar-section">
                        <h3 data-i18n-key="community.sidebar.categories">Categories</h3>
                        <ul id="category-list">
                            <li><a href="Community.php" class="<?php echo $selectedCategoryId === 0 ? 'active' : ''; ?>" data-i18n-key="community.cat.all">All Discussions</a> <span>(<?php echo $totalDiscussions; ?>)</span></li>
                            <?php foreach ($categories as $cat): ?>
                                <li><a href="Community.php?category=<?php echo (int)$cat['id']; ?>" class="<?php echo $selectedCategoryId === (int)$cat['id'] ? 'active' : ''; ?>"><?php echo htmlspecialchars($cat['name']); ?> <span>(<?php echo (int)$cat['count']; ?>)</span></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="sidebar-section">
                        <h3 data-i18n-key="community.stats.title">Community Stats</h3>
                        <ul class="stats-list">
                            <li><i class="fa-solid fa-users"></i> <span data-i18n-key="community.stats.members">Active Members</span>: <strong><?php echo number_format($activeMembers); ?></strong></li>
                            <li><i class="fa-solid fa-comments"></i> <span data-i18n-key="community.stats.discussions">Discussions</span>: <strong><?php echo number_format($totalDiscussions); ?></strong></li>
                        </ul>
                    </div>
                </aside>

                <section class="main-content">
                    <div class="new-discussion-box">
                        <?php if ($userId): ?>
                            <button id="new-discussion-btn" class="btn btn-primary"><i class="fa-solid fa-plus"></i> <span data-i18n-key="community.new.title">Start a New Discussion</span></button>
                            <div id="new-discussion-form-container" class="new-discussion-form-container" style="display: none;">
                                <form method="POST" action="Community.php">
                                    <input type="hidden" name="action" value="create_discussion">
                                    <h3 data-i18n-key="user.discussions.newDiscussionTitle">Start New Discussion</h3>
                                    <div class="form-group">
                                        <label for="title" data-i18n-key="user.discussions.form.title">Title</label>
                                        <input type="text" id="title" name="title" required data-i18n-placeholder-key="user.discussions.form.titlePlaceholder" placeholder="What is your question or topic?">
                                    </div>
                                    <div class="form-group">
                                        <label for="category_id" data-i18n-key="user.discussions.form.category">Category</label>
                                        <select id="category_id" name="category_id" required>
                                            <option value="" disabled selected data-i18n-key="user.discussions.form.selectCategory">-- Select a category --</option>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?php echo (int)$cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="content" data-i18n-key="user.discussions.form.content">Details</label>
                                        <textarea id="content" name="content" rows="5" required data-i18n-placeholder-key="user.discussions.form.contentPlaceholder" placeholder="Provide more details, context, or background for your discussion..."></textarea>
                                    </div>
                                    <div class="form-actions">
                                        <button type="submit" class="btn btn-primary" data-i18n-key="user.discussions.actions.createDiscussion">Create Discussion</button>
                                    </div>
                                </form>
                            </div>
                        <?php else: ?>
                            <p id="login-message" data-i18n-key="community.loginPromptShort">Please <a href="../HTML/Login.html" data-i18n-key="discussion.loginLink">login</a> or <a href="../HTML/Signup.html" data-i18n-key="discussion.signupLink">create an account</a> to post.</p>
                        <?php endif; ?>
                    </div>

                    <div class="discussions" id="discussion-cards">
                        <?php if (empty($discussions)): ?>
                            <p data-i18n-key="community.emptyState">No discussions found.</p>
                        <?php else: ?>
                            <?php foreach ($discussions as $d): ?>
                                <div class="discussion-card">
                                    <span class="category"><?php echo htmlspecialchars($d['category_name']); ?></span>
                                    <span class="time"><?php echo time_ago($d['updated_at']); ?></span>
                                    <h3><a href="discussion.php?id=<?php echo (int)$d['id']; ?>"><?php echo htmlspecialchars($d['title']); ?></a></h3>
                                    <p><?php echo htmlspecialchars(mb_strimwidth($d['content'], 0, 150, "...")); ?></p>
                                    <p class="author"><span data-i18n-key="common.by">by</span> <?php echo htmlspecialchars($d['author_name']); ?></p>
                                    <div class="interaction">
                                        <span><i class="fa-regular fa-thumbs-up"></i> <?php echo (int)$d['like_count']; ?></span>
                                        <span><i class="fa-regular fa-comment"></i> <?php echo (int)$d['comment_count']; ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <script type="module" src="/AgriHub/Js/site.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const newDiscussionBtn = document.getElementById('new-discussion-btn');
            const newDiscussionForm = document.getElementById('new-discussion-form-container');

            if (newDiscussionBtn && newDiscussionForm) {
                newDiscussionBtn.addEventListener('click', function() {
                    const isVisible = newDiscussionForm.style.display === 'block';
                    newDiscussionForm.style.display = isVisible ? 'none' : 'block';
                });
            }
        });
    </script>
    <div id="footer-placeholder"></div>
</body>
</html>
