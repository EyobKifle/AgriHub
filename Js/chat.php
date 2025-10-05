<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'User not authenticated']);
    exit();
}

require_once __DIR__ . '/../config.php';

switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        handlePost($conn);
        break;
    case 'GET':
        handleGet($conn);
        break;
    case 'DELETE':
        handleDelete($conn);
        break;
    case 'PUT':
        handlePut($conn);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method Not Allowed']);
        break;
}

/**
 * Handles GET requests to fetch all comments for a discussion.
 */
function handleGet($conn) {

$discussionId = isset($_GET['discussion_id']) ? (int)$_GET['discussion_id'] : 0;

if ($discussionId === 0) {
    http_response_code(400);
    echo json_encode(['error' => 'No discussion ID provided.']);
    exit();
}

$messages = [];
$stmt = $conn->prepare("
    SELECT
        dc.id,
        dc.content AS text,
        dc.created_at AS createdAt,
        u.id AS user_id,
        u.name AS user_name,
        u.avatar_url AS user_avatar
    FROM
        discussion_comments dc
    JOIN
        users u ON dc.author_id = u.id
    WHERE
        dc.discussion_id = ?
    ORDER BY
        dc.created_at ASC
");
$stmt->bind_param('i', $discussionId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $messages[] = [
        'id' => $row['id'],
        'text' => $row['text'],
        'createdAt' => $row['createdAt'],
        'attachments' => [], // attachments are not stored in DB for comments yet
        'user' => [
            'id' => (int)$row['user_id'],
            'name' => $row['user_name'],
            'avatar' => !empty($row['user_avatar']) ? '../' . $row['user_avatar'] : 'https://placehold.co/48x48/2a9d8f/FFF?text=' . strtoupper(mb_substr($row['user_name'], 0, 1))
        ]
    ];
}
$stmt->close();

echo json_encode(['messages' => $messages]);
}

/**
 * Handles POST requests to add a new comment to a discussion.
 */
function handlePost($conn) {
    $currentUserId = (int)$_SESSION['user_id'];
    $input = json_decode(file_get_contents('php://input'), true);

    $discussionId = isset($input['discussionId']) ? (int)$input['discussionId'] : 0;
    $text = isset($input['text']) ? trim($input['text']) : '';

    if ($discussionId === 0 || empty($text)) {
        http_response_code(400);
        echo json_encode(['error' => 'Discussion ID and message text are required.']);
        exit();
    }

    $conn->begin_transaction();

    try {
        // 1. Insert the new comment
        $stmt = $conn->prepare("INSERT INTO discussion_comments (discussion_id, author_id, content) VALUES (?, ?, ?)");
        $stmt->bind_param('iis', $discussionId, $currentUserId, $text);
        $stmt->execute();
        $newCommentId = $conn->insert_id;
        $stmt->close();

        // 2. Update the comment count and updated_at timestamp on the parent discussion
        $stmt = $conn->prepare("UPDATE discussions SET comment_count = comment_count + 1, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param('i', $discussionId);
        $stmt->execute();
        $stmt->close();

        $conn->commit();

        // Return the newly created comment object so the UI can render it
        $newComment = $input;
        $newComment['id'] = $newCommentId;
        $newComment['createdAt'] = date('c'); // ISO 8601 format

        echo json_encode($newComment);
    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['error' => 'Database error: Could not save the comment.']);
    }
}

/**
 * Handles DELETE requests to remove a comment.
 */
function handleDelete($conn) {
    $currentUserId = (int)$_SESSION['user_id'];
    $input = json_decode(file_get_contents('php://input'), true);
    $commentId = isset($input['messageId']) ? (int)$input['messageId'] : 0;

    if ($commentId === 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid comment ID.']);
        exit();
    }

    $conn->begin_transaction();

    try {
        // First, get the author_id and discussion_id to verify ownership and update counts
        $stmt = $conn->prepare("SELECT author_id, discussion_id FROM discussion_comments WHERE id = ?");
        $stmt->bind_param('i', $commentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $comment = $result->fetch_assoc();
        $stmt->close();

        if (!$comment) {
            throw new Exception('Comment not found.', 404);
        }

        if ((int)$comment['author_id'] !== $currentUserId) {
            throw new Exception('You do not have permission to delete this comment.', 403);
        }

        // 1. Delete the comment
        $stmt = $conn->prepare("DELETE FROM discussion_comments WHERE id = ?");
        $stmt->bind_param('i', $commentId);
        $stmt->execute();
        $stmt->close();

        // 2. Decrement the comment count on the parent discussion
        $stmt = $conn->prepare("UPDATE discussions SET comment_count = GREATEST(0, comment_count - 1) WHERE id = ?");
        $stmt->bind_param('i', $comment['discussion_id']);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Comment deleted.']);
    } catch (Exception $e) {
        $conn->rollback();
        http_response_code($e->getCode() > 0 ? $e->getCode() : 500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

/**
 * Handles PUT requests to update a comment.
 */
function handlePut($conn) {
    $currentUserId = (int)$_SESSION['user_id'];
    $input = json_decode(file_get_contents('php://input'), true);
    $commentId = isset($input['messageId']) ? (int)$input['messageId'] : 0;
    $content = isset($input['content']) ? trim($input['content']) : '';

    if ($commentId === 0 || empty($content)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid comment ID or empty content.']);
        exit();
    }

    // Verify ownership before updating
    $stmt = $conn->prepare("SELECT author_id FROM discussion_comments WHERE id = ?");
    $stmt->bind_param('i', $commentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $comment = $result->fetch_assoc();
    $stmt->close();

    if (!$comment) {
        http_response_code(404);
        echo json_encode(['error' => 'Comment not found.']);
        exit();
    }

    if ((int)$comment['author_id'] !== $currentUserId) {
        http_response_code(403);
        echo json_encode(['error' => 'You do not have permission to edit this comment.']);
        exit();
    }

    // Update the comment
    $stmt = $conn->prepare("UPDATE discussion_comments SET content = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param('si', $content, $commentId);
    if ($stmt->execute()) {
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Comment updated.']);
    } else {
        $stmt->close();
        http_response_code(500);
        echo json_encode(['error' => 'Database error: Could not update the comment.']);
    }
}