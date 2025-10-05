<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utils.php';

$userId = $_SESSION['user_id'] ?? 0;

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
$stmt = $conn->prepare('SELECT COUNT(*) FROM users WHERE is_active = 1');
if ($stmt) {
    $stmt->execute();
    $stmt->bind_result($activeMembers);
    $stmt->fetch();
    $stmt->close();
}

// Fetch latest discussions
$discussions = [];
$stmt = $conn->prepare("SELECT d.id, d.title, d.content, d.updated_at, d.comment_count, d.like_count, u.name as author_name, c.name as category_name FROM discussions d JOIN users u ON d.author_id = u.id JOIN discussion_categories c ON d.category_id = c.id WHERE d.status = 'published' ORDER BY d.updated_at DESC LIMIT 10");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $discussions[] = $row;
    }
    $stmt->close();
}

// Inject categories
$categoryHtml = '<li>All Discussions <span>(' . $totalDiscussions . ')</span></li>';
foreach ($categories as $cat) {
    $categoryHtml .= '<li><a href="Community.php?category=' . (int)$cat['id'] . '">' . htmlspecialchars($cat['name']) . ' <span>(' . (int)$cat['count'] . ')</span></a></li>';
}

// Inject discussions
$discussionHtml = '';
foreach ($discussions as $d) {
    $discussionHtml .= '<div class="discussion-card">
        <span class="category">' . htmlspecialchars($d['category_name']) . '</span>
        <span class="time">' . time_ago($d['updated_at']) . '</span>
        <h3><a href="discussion.php?id=' . (int)$d['id'] . '">' . htmlspecialchars($d['title']) . '</a></h3>
        <p>' . htmlspecialchars(mb_strimwidth($d['content'], 0, 150, "...")) . '</p>
        <p class="author">by ' . htmlspecialchars($d['author_name']) . '</p>
        <div class="interaction">
            <span><i class="fa-regular fa-thumbs-up"></i> ' . (int)$d['like_count'] . '</span>
            <span><i class="fa-regular fa-comment"></i> ' . (int)$d['comment_count'] . '</span>
        </div>
    </div>';
}

// Inject form if user is logged in
$newDiscussionForm = '';
if ($userId) {
    $newDiscussionForm = '<form method="post" action="Community.php">
        <input type="hidden" name="action" value="create_discussion">
        <input type="text" name="title" placeholder="Discussion title..." required>
        <select name="category_id" required>
            <option value="">Select a category</option>';
    foreach ($categories as $cat) {
        $newDiscussionForm .= '<option value="' . (int)$cat['id'] . '">' . htmlspecialchars($cat['name']) . '</option>';
    }
    $newDiscussionForm .= '</select>
        <textarea name="content" placeholder="What would you like to discuss?" required></textarea>
        <button type="submit" class="btn post">Post Discussion</button>
    </form>';
}

// Load HTML template
$html = file_get_contents('Community.html');

// Replace placeholders (via DOM manipulation or simple str_replace for simplicity)
$html = str_replace('<ul id="category-list">', '<ul id="category-list">' . $categoryHtml, $html);
$html = str_replace('<div class="discussions" id="discussion-cards">', '<div class="discussions" id="discussion-cards">' . $discussionHtml, $html);
$html = str_replace('<p id="login-message">Please <a href="Login.html">login</a> or <a href="Signup.html">create an account</a> to post.</p>', $newDiscussionForm ?: '<p id="login-message">Please <a href="Login.html">login</a> or <a href="Signup.html">create an account</a> to post.</p>', $html);
$html = str_replace('<span id="active-members">0</span>', number_format($activeMembers), $html);
$html = str_replace('<span id="total-discussions">0</span>', number_format($totalDiscussions), $html);

echo $html;
