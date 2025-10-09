<?php
session_start();
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

function send_json_error($message, $statusCode = 400) {
    http_response_code($statusCode);
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

function send_json_success($data = []) {
    echo json_encode(array_merge(['success' => true], $data));
    exit;
}

$userId = (int)($_SESSION['user_id'] ?? 0);
if ($userId === 0) {
    send_json_error('User not authenticated.', 401);
}

$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true);

if (empty($action)) {
    $action = $input['action'] ?? '';
}

try {
    switch ($action) {
        case 'get_conversations':
            // Fetches all conversations for the current user, along with the other participant's details and the last message.
            $stmt = $conn->prepare("
                SELECT
                    c.id AS conversation_id,
                    other_user.id AS other_user_id,
                    other_user.name AS other_user_name,
                    other_user.avatar_url AS other_user_avatar,
                    last_message.content AS last_message_content,
                    last_message.created_at AS last_message_timestamp,
                    (SELECT COUNT(*) FROM messages m WHERE m.conversation_id = c.id AND m.sender_id != ? AND (cp.last_read_at IS NULL OR m.created_at > cp.last_read_at)) as unread_count
                FROM conversation_participants cp
                JOIN conversations c ON cp.conversation_id = c.id
                JOIN conversation_participants other_cp ON c.id = other_cp.conversation_id AND other_cp.user_id != ?
                JOIN users other_user ON other_cp.user_id = other_user.id
                LEFT JOIN (
                    SELECT conversation_id, content, created_at
                    FROM messages
                    WHERE (conversation_id, created_at) IN (
                        SELECT conversation_id, MAX(created_at)
                        FROM messages
                        GROUP BY conversation_id
                    )
                ) AS last_message ON c.id = last_message.conversation_id
                WHERE cp.user_id = ?
                ORDER BY COALESCE(last_message.created_at, c.created_at) DESC
            ");
            $stmt->bind_param('iii', $userId, $userId, $userId);
            $stmt->execute();
            $conversations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            send_json_success(['conversations' => $conversations]);
            break;

        case 'get_messages':
            $conversationId = (int)($_GET['conversation_id'] ?? 0);
            if ($conversationId <= 0) send_json_error('Invalid conversation ID.');

            // Verify user is part of this conversation
            $verify_stmt = $conn->prepare("SELECT 1 FROM conversation_participants WHERE conversation_id = ? AND user_id = ?");
            $verify_stmt->bind_param('ii', $conversationId, $userId);
            $verify_stmt->execute();
            if ($verify_stmt->get_result()->num_rows === 0) {
                send_json_error('Access denied to this conversation.', 403);
            }
            $verify_stmt->close();

            // Fetch messages
            $stmt = $conn->prepare("
                SELECT id, sender_id, content, created_at
                FROM messages
                WHERE conversation_id = ?
                ORDER BY created_at ASC
            ");
            $stmt->bind_param('i', $conversationId);
            $stmt->execute();
            $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            // Mark messages as read
            $update_stmt = $conn->prepare("UPDATE conversation_participants SET last_read_at = NOW() WHERE conversation_id = ? AND user_id = ?");
            $update_stmt->bind_param('ii', $conversationId, $userId);
            $update_stmt->execute();
            $update_stmt->close();

            send_json_success(['messages' => $messages]);
            break;

        case 'send_message':
            $conversationId = (int)($input['conversation_id'] ?? 0);
            $recipientId = (int)($input['recipient_id'] ?? 0);
            $content = trim($input['content'] ?? '');

            if (empty($content) || ($conversationId <= 0 && $recipientId <= 0)) {
                send_json_error('Missing required data.');
            }

            $conn->begin_transaction();

            // If conversationId is 0, find or create a new conversation
            if ($conversationId === 0) {
                // Check if a conversation already exists between these two users
                $stmt = $conn->prepare("
                SELECT conversation_id FROM conversation_participants WHERE user_id = ? 
                AND conversation_id IN (
                    SELECT conversation_id FROM conversation_participants WHERE user_id = ?
                )
                ");
                $stmt->bind_param('ii', $userId, $recipientId);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    $conversationId = $row['conversation_id'];
                } else {
                    // Create a new conversation
                    $conn->query("INSERT INTO conversations (created_at, updated_at) VALUES (NOW(), NOW())");
                    $conversationId = $conn->insert_id;

                    // Add both participants
                    $stmt_part = $conn->prepare("INSERT INTO conversation_participants (conversation_id, user_id) VALUES (?, ?), (?, ?)");
                    $stmt_part->bind_param('iiii', $conversationId, $userId, $conversationId, $recipientId);
                    $stmt_part->execute();
                    $stmt_part->close();
                }
                $stmt->close();
            }

            // Insert the message
            $stmt_msg = $conn->prepare("INSERT INTO messages (conversation_id, sender_id, content) VALUES (?, ?, ?)");
            $stmt_msg->bind_param('iis', $conversationId, $userId, $content);
            $stmt_msg->execute();
            $newMessageId = $conn->insert_id;
            $stmt_msg->close();

            // Update the conversation's updated_at timestamp
            $stmt_update = $conn->prepare("UPDATE conversations SET updated_at = NOW() WHERE id = ?");
            $stmt_update->bind_param('i', $conversationId);
            $stmt_update->execute();
            $stmt_update->close();

            $conn->commit();
            send_json_success(['message_id' => $newMessageId, 'conversation_id' => $conversationId]);
            break;

        default:
            send_json_error('Invalid action specified.');
            break;
    }
} catch (Exception $e) {
    $conn->rollback();
    send_json_error('Server error: ' . $e->getMessage(), 500);
}

$conn->close();
?>
