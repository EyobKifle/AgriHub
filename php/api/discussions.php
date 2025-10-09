<?php
/**
 * AgriHub Discussions API
 *
 * This script functions as a JSON API for public discussions and their comments.
 *
 * Actions:
 * - get_messages: Fetches all comments for a specific discussion.
 * - add_message: Adds a new comment to a discussion.
 * - update_message: Edits an existing comment.
 * - delete_message: Deletes a comment.
 * - report_discussion: Handles user reports for a discussion.
 */

session_start();
require_once __DIR__ . '/../config.php';

// Set a custom error handler to catch PHP errors and return them as JSON.
set_error_handler(function($severity, $message, $file, $line) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => ['code' => "Server Error: $message in $file on line $line"]]);
    exit;
});

header('Content-Type: application/json');

function send_json_error($message_key, $statusCode = 400) {
    http_response_code($statusCode);
    echo json_encode(['success' => false, 'error' => ['code' => $message_key]]);
    exit;
}

function send_json_success($data = null) {
    $response = ['success' => true];
    if ($data !== null) {
        $response = array_merge($response, $data);
    }
    echo json_encode($response);
    exit;
}

$current_user_id = (int)($_SESSION['user_id'] ?? 0);
$request_method = $_SERVER['REQUEST_METHOD'];
$action = '';
$input = [];

if ($request_method === 'GET') {
    $action = $_GET['action'] ?? '';
} elseif ($request_method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
}

if (empty($action)) {
    send_json_error('error.api.noAction');
}

// Publicly accessible actions
if ($action === 'get_messages') {
    $discussion_id = (int)($_GET['discussion_id'] ?? 0);
    if ($discussion_id <= 0) {
        send_json_error('error.discussion.invalidId');
    }

    $stmt = $conn->prepare("
        SELECT m.id, m.content, m.created_at, u.id as user_id, u.name as user_name, u.avatar_url
        FROM discussion_comments m
        JOIN users u ON m.author_id = u.id
        WHERE m.discussion_id = ?
        ORDER BY m.created_at ASC
    ");
    $stmt->bind_param('i', $discussion_id);
    $stmt->execute();
    $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $formatted_messages = array_map(function($msg) {
        return [
            'id' => $msg['id'],
            'text' => $msg['content'],
            'createdAt' => $msg['created_at'],
            'user' => [
                'id' => $msg['user_id'],
                'name' => $msg['user_name'],
                'avatar' => !empty($msg['avatar_url']) ? '/AgriHub/' . $msg['avatar_url'] : 'https://placehold.co/48x48/cccccc/FFF?text=' . strtoupper(substr($msg['user_name'], 0, 1)),
            ],
            'attachments' => [],
        ];
    }, $messages);

    send_json_success(['messages' => $formatted_messages]);
}

// Actions requiring a logged-in user
if ($current_user_id === 0) {
    send_json_error('error.unauthorized', 401);
}

try {
    switch ($action) {
        case 'add_message':
            $discussionId = (int)($input['message']['discussionId'] ?? 0);
            $text = trim($input['message']['text'] ?? '');

            if ($discussionId <= 0 || empty($text)) {
                send_json_error('error.discussion.missingData');
            }

            $conn->begin_transaction();
            $stmt_insert = $conn->prepare("INSERT INTO discussion_comments (discussion_id, author_id, content) VALUES (?, ?, ?)");
            $stmt_insert->bind_param('iis', $discussionId, $current_user_id, $text);
            $stmt_insert->execute();
            $newMessageId = $conn->insert_id;
            $stmt_insert->close();

            $stmt_update = $conn->prepare("UPDATE discussions SET updated_at = NOW(), comment_count = comment_count + 1 WHERE id = ?");
            $stmt_update->bind_param('i', $discussionId);
            $stmt_update->execute();
            $stmt_update->close();

            $conn->commit();
            send_json_success(['message_id' => $newMessageId]);
            break;

        case 'update_message':
            $messageId = (int)($input['message_id'] ?? 0);
            $text = trim($input['text'] ?? '');
            if ($messageId <= 0 || empty($text)) {
                send_json_error('error.discussion.updateMissingParams');
            }
            $stmt = $conn->prepare("UPDATE discussion_comments SET content = ? WHERE id = ? AND author_id = ?");
            $stmt->bind_param('sii', $text, $messageId, $current_user_id);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                send_json_success();
            } else {
                send_json_error('error.discussion.notFoundOrNoPermission', 404);
            }
            $stmt->close();
            break;

        case 'delete_message':
            $messageId = (int)($input['message_id'] ?? 0);
            if ($messageId <= 0) {
                send_json_error('error.discussion.deleteMissingId');
            }

            $conn->begin_transaction();
            $stmt_select = $conn->prepare("SELECT discussion_id FROM discussion_comments WHERE id = ? AND author_id = ?");
            $stmt_select->bind_param('ii', $messageId, $current_user_id);
            $stmt_select->execute();
            $message = $stmt_select->get_result()->fetch_assoc();
            $stmt_select->close();

            if (!$message) {
                $conn->rollback();
                send_json_error('error.discussion.notFoundOrNoPermission', 404);
            }
            $discussionId = $message['discussion_id'];

            $stmt_delete = $conn->prepare("DELETE FROM discussion_comments WHERE id = ?");
            $stmt_delete->bind_param('i', $messageId);
            $stmt_delete->execute();
            $stmt_delete->close();

            $stmt_update = $conn->prepare("UPDATE discussions SET comment_count = GREATEST(0, comment_count - 1) WHERE id = ?");
            $stmt_update->bind_param('i', $discussionId);
            $stmt_update->execute();
            $stmt_update->close();

            $conn->commit();
            send_json_success();
            break;

        case 'report_discussion':
            $discussionId = (int)($input['discussion_id'] ?? 0);
            $reason = trim($input['reason'] ?? '');
            $details = trim($input['details'] ?? '');

            if ($discussionId <= 0 || empty($reason)) {
                send_json_error('error.report.missingData');
            }

            // Note: The 'reason' and 'details' should be concatenated or stored separately based on DB schema.
            $fullReason = $reason . (!empty($details) ? ": " . $details : "");

            $stmt = $conn->prepare("INSERT INTO reports (reporter_id, reported_item_type, reported_item_id, reason) VALUES (?, 'discussion', ?, ?)");
            $stmt->bind_param('iis', $current_user_id, $discussionId, $fullReason);
            $stmt->execute();
            send_json_success(['message' => 'Report submitted successfully.']);
            break;

        default:
            // If we are here, it's a logged-in user with an invalid action
            send_json_error('error.api.invalidAction');
            break;
    }
} catch (Exception $e) {
    if ($conn->in_transaction) {
        $conn->rollback();
    }
    error_log("Discussions API Error: " . $e->getMessage());
    send_json_error('error.api.serverError', 500);
}
?>