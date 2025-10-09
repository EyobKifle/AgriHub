<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../HTML/Login.html');
    exit();
}
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utils.php';

$userId = (int)$_SESSION['user_id'];
$name = $_SESSION['name'] ?? 'User';
$email = $_SESSION['email'] ?? '';
$initial = strtoupper(mb_substr($name, 0, 1));
$avatar_url = '';
$currentPage = 'User-Discussions';

// Fetch user avatar for header/sidebar
$stmt_avatar = $conn->prepare("SELECT avatar_url FROM users WHERE id = ?");
if ($stmt_avatar) {
    $stmt_avatar->bind_param('i', $userId);
    $stmt_avatar->execute();
    $avatar_url = $stmt_avatar->get_result()->fetch_object()->avatar_url ?? '';
    $stmt_avatar->close();
}

// Handle POST request for creating a new discussion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_discussion') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);

    if (!empty($title) && !empty($content) && $categoryId > 0) {
        $stmt = $conn->prepare("INSERT INTO discussions (author_id, category_id, title, content, status) VALUES (?, ?, ?, ?, 'published')");
        $stmt->bind_param('iiss', $userId, $categoryId, $title, $content);
        if ($stmt->execute()) {
            $newDiscussionId = $conn->insert_id;
            $stmt->close();
            header('Location: discussion.php?id=' . $newDiscussionId);
            exit();
        }
    }
}

// Fetch discussion categories for the form dropdown
$categories = [];
$cat_stmt = $conn->prepare("SELECT id, name FROM discussion_categories ORDER BY display_order, name");
if ($cat_stmt) {
    $cat_stmt->execute();
    $categories = $cat_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $cat_stmt->close();
}

// Fetch discussions started by the user
$discussions = [];
$disc_stmt = $conn->prepare(
   "SELECT d.id, d.title, d.updated_at, d.comment_count, c.name as category_name
    FROM discussions d
    JOIN discussion_categories c ON d.category_id = c.id
    WHERE d.author_id = ?
    ORDER BY d.updated_at DESC"
);
$disc_stmt->bind_param('i', $userId);
$disc_stmt->execute();
$discussions = $disc_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$disc_stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-i18n-key="user.discussions.pageTitle">My Discussions - AgriHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../Css/User-Dashboard.css">
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
        <?php include __DIR__ . '/_sidebar.php'; ?>

        <main class="main-content">
            <div class="main-header">
                <h1 data-i18n-key="user.discussions.title">My Discussions</h1>
                <p data-i18n-key="user.discussions.subtitle">Keep track of conversations you've started or participated in.</p>
            </div>

            <div class="page-controls">
                <button type="button" id="create-discussion-btn" class="btn btn-primary" data-i18n-key="user.discussions.new"><i class="fa-solid fa-plus"></i> Start New Discussion</button>
            </div>

            <div class="card" id="create-discussion-card" style="display: none;">
                <h3 class="card-title" data-i18n-key="user.discussions.newDiscussionTitle">Start New Discussion</h3>
                <form method="POST" class="settings-form">
                    <input type="hidden" name="action" value="create_discussion">
                    <div class="form-group">
                        <label for="title" data-i18n-key="user.discussions.form.title">Title</label>
                        <input type="text" id="title" name="title" data-i18n-placeholder-key="user.discussions.form.titlePlaceholder" placeholder="What is your question or topic?" required>
                    </div>
                    <div class="form-group">
                        <label for="category_id" data-i18n-key="user.discussions.form.category">Category</label>
                        <select id="category_id" name="category_id" required>
                            <option value="" data-i18n-key="user.discussions.form.selectCategory">-- Select a category --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo (int)$cat['id']; ?>"><?php echo e($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="content" data-i18n-key="user.discussions.form.content">Details</label>
                        <textarea id="content" name="content" rows="5" data-i18n-placeholder-key="user.discussions.form.contentPlaceholder" placeholder="Provide more details, context, or background for your discussion..." required></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" id="cancel-discussion-btn" data-i18n-key="common.cancel">Cancel</button>
                        <button type="submit" class="btn btn-primary" data-i18n-key="user.discussions.actions.createDiscussion">Create Discussion</button>
                    </div>
                </form>
            </div>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th data-i18n-key="user.discussions.table.topic">Topic</th>
                            <th data-i18n-key="user.discussions.table.category">Category</th>
                            <th data-i18n-key="user.discussions.table.lastUpdate">Last Update</th>
                            <th data-i18n-key="user.discussions.table.replies">Replies</th>
                            <th data-i18n-key="user.discussions.table.actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($discussions)): ?>
                            <tr><td colspan="5" style="text-align:center; opacity:.8;" data-i18n-key="user.discussions.empty">You have not started any discussions yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($discussions as $discussion): ?>
                                <tr>
                                    <td><?php echo e($discussion['title']); ?></td>
                                    <td><?php echo e($discussion['category_name']); ?></td>
                                    <td><?php echo time_ago($discussion['updated_at']); ?></td>
                                    <td><?php echo (int)$discussion['comment_count']; ?></td>
                                    <td class="action-buttons">
                                        <a href="discussion.php?id=<?php echo (int)$discussion['id']; ?>" class="btn-icon" data-i18n-title-key="user.discussions.actions.view" title="View Discussion">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
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
      <script type="module" src="/AgriHub/Js/site.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const createBtn = document.getElementById('create-discussion-btn');
            const cancelBtn = document.getElementById('cancel-discussion-btn');
            const formCard = document.getElementById('create-discussion-card');

            createBtn.addEventListener('click', () => {
                formCard.style.display = 'block';
                createBtn.style.display = 'none';
            });

            cancelBtn.addEventListener('click', () => {
                formCard.style.display = 'none';
                createBtn.style.display = 'inline-flex';
            });
        });
    </script>
</body>
</html>