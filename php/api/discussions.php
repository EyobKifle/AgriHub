<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config.php';

function send_json_error($message, $statusCode = 400) {
    http_response_code($statusCode);
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

$userId = $_SESSION['user_id'] ?? 0;
if ($userId === 0) {
    send_json_error('You must be logged in to perform this action.', 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_error('Invalid request method.', 405);
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$discussionId = (int)($input['id'] ?? 0);

if ($action === 'delete' && $discussionId > 0) {
    // Use a transaction to ensure both deletions succeed or fail together
    $conn->begin_transaction();
    try {
        // First, delete associated messages (optional, but good practice)
        $stmt_msgs = $conn->prepare("DELETE FROM discussion_messages WHERE discussion_id = ?");
        $stmt_msgs->bind_param('i', $discussionId);
        $stmt_msgs->execute();
        $stmt_msgs->close();

        // Then, delete the discussion, ensuring the user is the owner
        $stmt_disc = $conn->prepare("DELETE FROM discussions WHERE id = ? AND author_id = ?");
        $stmt_disc->bind_param('ii', $discussionId, $userId);
        $stmt_disc->execute();

        if ($stmt_disc->affected_rows > 0) {
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Discussion deleted successfully.']);
        } else {
            $conn->rollback();
            send_json_error('Discussion not found or you do not have permission to delete it.', 403);
        }
        $stmt_disc->close();
    } catch (Exception $e) {
        $conn->rollback();
        send_json_error('An error occurred while deleting the discussion.', 500);
    }
} else {
    send_json_error('Invalid action or discussion ID.');
}

$conn->close();