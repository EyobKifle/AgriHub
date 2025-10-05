<?php
/**
 * AgriHub Discussion Page & API
 *
 * This script serves a dual purpose:
 * 1. If accessed via a standard GET request without an 'action', it displays the HTML for a discussion page.
 * 2. If accessed with an 'action' parameter, it functions as a JSON API for chat messages within that discussion.
 *
 * Actions:
 * - get_messages: Fetches all messages for a specific discussion.
 * - add_message: Adds a new message to a discussion.
 * - update_message: Edits an existing message.
 * - delete_message: Deletes a message.
 */

session_start();

$isApiRequest = !empty($_REQUEST['action']);

if ($isApiRequest) {
    header('Content-Type: application/json');
    require_once __DIR__ . '/config.php';

    function send_json_error($message, $statusCode = 400) {
        http_response_code($statusCode);
        echo json_encode(['success' => false, 'message' => $message]);
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

    $current_user_id = $_SESSION['user_id'] ?? 0;
    if ($current_user_id === 0 && $_SERVER['REQUEST_METHOD'] !== 'GET') {
        send_json_error('You must be logged in to post messages.', 401);
    }

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
        send_json_error('No action specified.');
    }

    switch ($action) {
        case 'get_messages':
            $discussion_id = (int)($_GET['discussion_id'] ?? 0);
            if ($discussion_id <= 0) {
                send_json_error('Invalid discussion ID.');
            }

            $stmt = $conn->prepare("
                SELECT m.id, m.content, m.created_at, u.id as user_id, u.name as user_name, u.avatar_url
                FROM discussion_messages m
                JOIN users u ON m.user_id = u.id
                WHERE m.discussion_id = ?
                ORDER BY m.created_at ASC
            ");
            $stmt->bind_param('i', $discussion_id);
            $stmt->execute();
            $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            // Reformat for frontend JS expectations (user object)
            $formatted_messages = array_map(function($msg) {
                return [
                    'id' => $msg['id'],
                    'text' => $msg['content'],
                    'createdAt' => $msg['created_at'],
                    'user' => [
                        'id' => $msg['user_id'],
                        'name' => $msg['user_name'],
                        'avatar' => !empty($msg['avatar_url']) ? '../' . $msg['avatar_url'] : 'https://placehold.co/48x48/cccccc/FFF?text=' . strtoupper(substr($msg['user_name'], 0, 1)),
                    ],
                    'attachments' => [], // Attachments not implemented in DB yet
                ];
            }, $messages);

            send_json_success(['messages' => $formatted_messages]);
            break;

        case 'add_message':
            $messageData = $input['message'] ?? null;
            if (!$messageData || empty($messageData['text']) || empty($messageData['discussionId'])) {
                send_json_error('Message data is missing or invalid.');
            }

            $stmt = $conn->prepare("INSERT INTO discussion_messages (discussion_id, user_id, content) VALUES (?, ?, ?)");
            $stmt->bind_param('iis', $messageData['discussionId'], $current_user_id, $messageData['text']);
            if ($stmt->execute()) {
                send_json_success(['message_id' => $conn->insert_id]);
            } else {
                send_json_error('Failed to save message.', 500);
            }
            $stmt->close();
            break;

        case 'update_message':
            $messageId = (int)($input['message_id'] ?? 0);
            $text = trim($input['text'] ?? '');
            if ($messageId <= 0 || empty($text)) {
                send_json_error('Message ID and text are required for update.');
            }
            // Check ownership before updating
            $stmt = $conn->prepare("UPDATE discussion_messages SET content = ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param('sii', $text, $messageId, $current_user_id);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                send_json_success(['message' => 'Message updated successfully.']);
            } else {
                send_json_error('Message not found or you do not have permission to edit it.', 404);
            }
            $stmt->close();
            break;

        case 'delete_message':
            $messageId = (int)($input['message_id'] ?? 0);
            if ($messageId <= 0) {
                send_json_error('Message ID is required for deletion.');
            }
            // Check ownership before deleting
            $stmt = $conn->prepare("DELETE FROM discussion_messages WHERE id = ? AND user_id = ?");
            $stmt->bind_param('ii', $messageId, $current_user_id);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                send_json_success(['message' => 'Message deleted successfully.']);
            } else {
                send_json_error('Message not found or you do not have permission to delete it.', 404);
            }
            $stmt->close();
            break;

        default:
            send_json_error('Invalid action specified.');
            break;
    }
    $conn->close();
    exit;
}

// --- HTML Page Rendering Logic ---
$discussionId = (int)($_GET['id'] ?? 0);
$html = file_get_contents('../HTML/discussion.html');
$html = str_replace('data-discussion-id=""', 'data-discussion-id="' . $discussionId . '"', $html);
echo $html;
?>