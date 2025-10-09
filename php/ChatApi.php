<?php
/**
 * AgriHub Private Messaging API
 *
 * This script handles all backend operations for the private messaging feature.
 *
 * Actions:
 * - get_conversations: Fetches all conversations for the current user.
 * - get_messages: Fetches all messages for a specific conversation.
 * - send_message: Adds a new message to a conversation. Creates a conversation if it doesn't exist.
 */

session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

function send_json_error($message, $statusCode = 400) {
    http_response_code($statusCode);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

function send_json_success($data = []) {
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    send_json_error('Unauthorized', 401);
}

$currentUserId = (int)$_SESSION['user_id'];
$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?? [];

try {
    switch ($action) {
        case 'get_conversations':
            $stmt = $conn->prepare("
                SELECT
                    c.id AS conversation_id,
                    other_user.id AS other_user_id,
                    other_user.name AS other_user_name,
                    other_user.avatar_url AS other_user_avatar,
                    last_message.content AS last_message_content,
                    last_message.created_at AS last_message_time,
                    (SELECT COUNT(*) FROM messages m WHERE m.conversation_id = c.id AND m.is_read = 0 AND m.recipient_id = ?) AS unread_count
                FROM conversations c
                JOIN conversation_participants cp ON c.id = cp.conversation_id
                JOIN users other_user ON cp.user_id = other_user.id
                LEFT JOIN (
                    SELECT conversation_id, content, created_at
                    FROM messages
                    WHERE id IN (SELECT MAX(id) FROM messages GROUP BY conversation_id)
                ) AS last_message ON c.id = last_message.conversation_id
                WHERE c.id IN (SELECT conversation_id FROM conversation_participants WHERE user_id = ?)
                AND other_user.id != ?
                ORDER BY last_message.created_at DESC
            ");
            $stmt->bind_param("iii", $currentUserId, $currentUserId, $currentUserId);
            $stmt->execute();
            $conversations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            send_json_success($conversations);
            break;

        case 'get_messages':
            $conversationId = filter_input(INPUT_GET, 'conversation_id', FILTER_VALIDATE_INT);
            if (!$conversationId) send_json_error('Invalid conversation ID.');

            // Mark messages as read
            $stmt_read = $conn->prepare("UPDATE messages SET is_read = 1 WHERE conversation_id = ? AND recipient_id = ?");
            $stmt_read->bind_param("ii", $conversationId, $currentUserId);
            $stmt_read->execute();
            $stmt_read->close();

            // Fetch messages
            $stmt = $conn->prepare("
                SELECT m.id, m.sender_id, m.content, m.created_at, u.name as sender_name, u.avatar_url as sender_avatar
                FROM messages m
                JOIN users u ON m.sender_id = u.id
                WHERE m.conversation_id = ?
                ORDER BY m.created_at ASC
            ");
            $stmt->bind_param("i", $conversationId);
            $stmt->execute();
            $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            send_json_success($messages);
            break;

        case 'send_message':
            $recipientId = filter_var($input['recipient_id'] ?? 0, FILTER_VALIDATE_INT);
            $content = trim($input['content'] ?? '');

            if (!$recipientId || empty($content)) {
                send_json_error('Recipient ID and message content are required.');
            }

            $conn->begin_transaction();

            // Find existing conversation or create a new one
            $stmt_find = $conn->prepare("
                SELECT conversation_id FROM conversation_participants WHERE user_id = ?
                INTERSECT
                SELECT conversation_id FROM conversation_participants WHERE user_id = ?
            ");
            $stmt_find->bind_param("ii", $currentUserId, $recipientId);
            $stmt_find->execute();
            $conversation = $stmt_find->get_result()->fetch_assoc();
            $stmt_find->close();

            $conversationId = $conversation['conversation_id'] ?? null;

            if (!$conversationId) {
                // Create a new conversation
                $stmt_create_conv = $conn->prepare("INSERT INTO conversations (created_at) VALUES (NOW())");
                $stmt_create_conv->execute();
                $conversationId = $conn->insert_id;
                $stmt_create_conv->close();

                // Add participants
                $stmt_add_p1 = $conn->prepare("INSERT INTO conversation_participants (conversation_id, user_id) VALUES (?, ?)");
                $stmt_add_p1->bind_param("ii", $conversationId, $currentUserId);
                $stmt_add_p1->execute();
                $stmt_add_p1->close();

                $stmt_add_p2 = $conn->prepare("INSERT INTO conversation_participants (conversation_id, user_id) VALUES (?, ?)");
                $stmt_add_p2->bind_param("ii", $conversationId, $recipientId);
                $stmt_add_p2->execute();
                $stmt_add_p2->close();
            }

            // Insert the message
            $stmt_insert = $conn->prepare("INSERT INTO messages (conversation_id, sender_id, recipient_id, content) VALUES (?, ?, ?, ?)");
            $stmt_insert->bind_param("iiis", $conversationId, $currentUserId, $recipientId, $content);
            $stmt_insert->execute();
            $newMessageId = $conn->insert_id;
            $stmt_insert->close();

            $conn->commit();
            send_json_success(['message_id' => $newMessageId, 'conversation_id' => $conversationId]);
            break;

        default:
            send_json_error('Invalid action specified.');
            break;
    }
} catch (Exception $e) {
    {
        $conn->rollback();
    }
    send_json_error('Server Error: ' . $e->getMessage(), 500);
}

?>