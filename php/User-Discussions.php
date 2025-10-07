<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../HTML/Login.html');
    exit();
}
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utils.php';

$currentPage = 'User-Discussions';
$userId = (int)$_SESSION['user_id'];

$name = $_SESSION['name'] ?? 'User';
$email = $_SESSION['email'] ?? '';
$initial = strtoupper(mb_substr($name, 0, 1));

// Fetch discussion categories for the dropdown
$categories = [];
$stmt = $conn->prepare('SELECT id, name FROM discussion_categories ORDER BY name');
if ($stmt) {
    $stmt->execute();
    $categories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Fetch user's discussions for server-side rendering
$discussions = [];
$stmt = $conn->prepare(
   "SELECT d.id, d.title, d.updated_at, d.comment_count, c.name as category_name
    FROM discussions d
    JOIN discussion_categories c ON d.category_id = c.id
    WHERE d.author_id = ?
    ORDER BY d.updated_at DESC"
);
if ($stmt) {
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $discussions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Fetch user avatar for header
$avatar_url = '';
$stmt = $conn->prepare("SELECT avatar_url FROM users WHERE id = ?");
if ($stmt) {
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $avatar_url = $stmt->get_result()->fetch_object()->avatar_url ?? '';
    $stmt->close();
}
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
                <div class="profile-avatar">
                    <?php if (!empty($avatar_url)): ?>
                        <img src="../<?php echo e($avatar_url); ?>" alt="User Avatar">
                    <?php else: echo e($initial); endif; ?>
                </div>
            </a>
        </div>
    </header>
    
    <div class="dashboard-container">
        <?php include __DIR__ . '/_sidebar.php'; ?>

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
                <form id="create-discussion-form" action="Community.php" method="POST">
                    <input type="hidden" name="action" value="create_discussion">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="title">Title</label>
                            <input type="text" id="title" name="title" required>
                        </div>
                        <div class="form-group">
                            <label for="category_id">Category</label>
                            <select name="category_id" id="category_id" required>
                                <option value="">Select a category...</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo (int)$category['id']; ?>"><?php echo e($category['name']); ?></option>
                                <?php endforeach; ?>
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
                        <?php if (empty($discussions)): ?>
                            <tr><td colspan="5" style="text-align:center; opacity:.8;">You have not started any discussions yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($discussions as $item): ?>
                                <tr data-id="<?php echo (int)$item['id']; ?>">
                                    <td><a href="discussion.php?id=<?php echo (int)$item['id']; ?>"><?php echo e($item['title']); ?></a></td>
                                    <td><?php echo e($item['category_name']); ?></td>
                                    <td><?php echo time_ago($item['updated_at']); ?></td>
                                    <td><?php echo (int)$item['comment_count']; ?></td>
                                    <td class="action-buttons">
                                        <button class="delete-btn" title="Delete Discussion"><i class="fa-solid fa-trash"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    
    <script type="module" src="../Js/dashboard.js"></script>
    <script type="module" src="../Js/User-Discussions.js"></script>
</body>
</html>
