<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utils.php'; // Include shared utilities

$userId = $_SESSION['user_id'] ?? 0;
$formError = '';

// Handle create discussion POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_discussion') {
    if (!$userId) {
        header('Location: Login.html'); // Redirect to login if not logged in
        exit();
    }

    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);

    if (empty($title) || empty($content) || $categoryId <= 0) {
        $formError = 'Please fill in all fields to start a discussion.';
    } else {
        if ($stmt = $conn->prepare('INSERT INTO discussions (author_id, category_id, title, content, status) VALUES (?, ?, ?, ?, "published")')) {
            $stmt->bind_param('iiss', $userId, $categoryId, $title, $content);
            if ($stmt->execute()) {
                header('Location: Community.php?created=1'); // Stays on the same page
                exit();
            } else {
                $formError = 'Failed to create discussion. Please try again.';
            }
            $stmt->close();
        } else {
            $formError = 'Server error. Please try again later.';
        }
    }
}

// Fetch categories and their counts
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

// Fetch community stats
$activeMembers = 0;
$stmt = $conn->prepare('SELECT COUNT(*) FROM users WHERE is_active = 1');
if ($stmt) {
    $stmt->execute();
    $stmt->bind_result($activeMembers);
    $stmt->fetch();
    $stmt->close();
}

// Fetch recent discussions
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

include 'Community.html';