<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: Login.html');
    exit();
}
require_once __DIR__ . '/../php/config.php'; // agrihub DB

$userId = (int)$_SESSION['user_id'];
$name = $_SESSION['name'] ?? 'User';
$email = $_SESSION['email'] ?? '';
$initial = strtoupper(mb_substr($name, 0, 1));

$formError = '';
$categories = [];
// Load categories for discussion creation
if ($stmt = $conn->prepare('SELECT id, name FROM discussion_categories ORDER BY display_order, name')) {
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) { $categories[] = $r; }
    $stmt->close();
}

// Handle create discussion POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['action'] ?? '') === 'create_discussion')) {
    $title = trim($_POST['title'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $content = trim($_POST['content'] ?? '');

    if ($title === '' || $categoryId <= 0 || $content === '') {
        $formError = 'Please fill in all required fields.';
    } else {
        if ($stmt = $conn->prepare('INSERT INTO discussions (author_id, category_id, title, content, status) VALUES (?, ?, ?, ?, "published")')) {
            $stmt->bind_param('iiss', $userId, $categoryId, $title, $content);
            if ($stmt->execute()) {
                $stmt->close();
                header('Location: User-Discussions.php?created=1');
                exit();
            } else {
                $formError = 'Failed to create discussion. Please try again.';
                $stmt->close();
            }
        } else {
            $formError = 'Server error. Please try again later.';
        }
    }
}

// Load discussions authored by this user
$rows = [];
$stmt = $conn->prepare('SELECT d.id, d.title, d.updated_at, d.comment_count, COALESCE(dc.name, "Uncategorized") AS category_name
                        FROM discussions d
                        LEFT JOIN discussion_categories dc ON dc.id = d.category_id
                        WHERE d.author_id = ?
                        ORDER BY d.updated_at DESC');
if ($stmt) {
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $rows[] = $r;
    }
    $stmt->close();
}
function time_ago($ts) {
    $t = strtotime($ts);
    if (!$t) return '';
    $diff = time() - $t;
    if ($diff < 60) return $diff . 's ago';
    if ($diff < 3600) return floor($diff/60) . 'm ago';
    if ($diff < 86400) return floor($diff/3600) . 'h ago';
    return floor($diff/86400) . 'd ago';
}

include '../HTML/User-Discussions.html';
