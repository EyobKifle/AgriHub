<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'User not authenticated.']);
    exit();
}
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utils.php';

$userId = (int)$_SESSION['user_id'];
$name = $_SESSION['name'] ?? 'User';
$email = $_SESSION['email'] ?? '';
$initial = strtoupper(mb_substr($name, 0, 1));

// This script can now handle both GET (for fetching data) and POST (for creating data)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Handle data fetching for the page load
    fetchDiscussionData($conn, $userId, $name, $email, $initial);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle form submission for creating a new discussion
    handleFormSubmission($conn, $userId);
}

function handleFormSubmission($conn, $userId) {
$formError = '';

// Handle "create discussion" form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_discussion') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);

    $response = [];
    if (empty($title) || empty($content) || $categoryId <= 0) {
        http_response_code(400);
        $response['error'] = 'Please fill in all fields.';
    } else {
        if ($stmt = $conn->prepare('INSERT INTO discussions (author_id, category_id, title, content, status) VALUES (?, ?, ?, ?, "published")')) {
            $stmt->bind_param('iiss', $userId, $categoryId, $title, $content);
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Discussion created successfully.';
            } else {
                http_response_code(500);
                $response['error'] = 'Failed to create discussion. Please try again.';
            }
            $stmt->close();
        } else {
            http_response_code(500);
            $response['error'] = 'Server error. Please try again later.';
        }
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}
}

function fetchDiscussionData($conn, $userId, $name, $email, $initial) {

// Fetch categories for the form dropdown
$categories = [];
if ($stmt = $conn->prepare('SELECT id, name FROM discussion_categories ORDER BY name')) {
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $categories[] = $r;
    }
    $stmt->close();
}

// Fetch discussions created by the user
$rows = [];
$sql = "SELECT d.id, d.title, d.updated_at, d.comment_count, c.name as category_name FROM discussions d JOIN discussion_categories c ON d.category_id = c.id WHERE d.author_id = ? ORDER BY d.updated_at DESC";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();
}

$conn->close();

header('Content-Type: application/json');
echo json_encode([
    'user' => [
        'name' => $name,
        'email' => $email,
        'initial' => $initial,
    ],
    'categories' => $categories,
    'discussions' => $rows,
]);
}
