<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'User not authenticated.']);
    exit();
}
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utils.php';

header('Content-Type: application/json');
$userId = (int)$_SESSION['user_id'];

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            fetchDiscussionData($conn, $userId);
            break;
        case 'POST':
            handleFormSubmission($conn, $userId);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method Not Allowed']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log("User-Discussions.php Error: " . $e->getMessage());
    echo json_encode(['error' => 'An unexpected server error occurred.']);
} finally {
    $conn->close();
}

function handleFormSubmission($conn, $userId) {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);

    if (empty($title) || empty($content) || $categoryId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Please fill in all fields.']);
        return;
    }

    $stmt = $conn->prepare('INSERT INTO discussions (author_id, category_id, title, content, status) VALUES (?, ?, ?, ?, "published")');
    $stmt->bind_param('iiss', $userId, $categoryId, $title, $content);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Discussion created successfully.']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create discussion. Please try again.']);
    }
    $stmt->close();
}

function fetchDiscussionData($conn, $userId) {
    $name = $_SESSION['name'] ?? 'User';
    $email = $_SESSION['email'] ?? '';
    $initial = strtoupper(mb_substr($name, 0, 1));
    // Fetch categories for the form dropdown
    $categories = $conn->query('SELECT id, name FROM discussion_categories ORDER BY name')->fetch_all(MYSQLI_ASSOC);

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
