<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

header('Content-Type: application/json');

// Helper functions for consistent JSON responses
function send_json_error($message, $statusCode = 400) {
    http_response_code($statusCode);
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

function send_json_success($data = []) {
    $response = ['success' => true];
    if (!empty($data)) {
        $response = array_merge($response, $data);
    }
    echo json_encode($response);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    send_json_error('User not authenticated.', 401);
}

$userId = (int)$_SESSION['user_id'];
$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {
        case 'get_conversations':
            handleGetConversations($conn, $userId);
            break;
        case 'get_messages':
            handleGetMessages($conn, $userId);
            break;
        case 'send_message':
            handleSendMessage($conn, $userId);
            break;
        case 'get_user_details':
            handleGetUserDetails($conn);
            break;
        default:
            send_json_error('Invalid action specified.', 400);
            break;
    }
} catch (Exception $e) {
    send_json_error('An unexpected server error occurred.', 500);
} finally {
    $conn->close();
}

/**
 * Fetches all conversations for the current user.
 */
function handleGetConversations($conn, $userId) {
    $stmt = $conn->prepare("
        SELECT
            c.id AS conversation_id,
            other_user.id AS other_user_id,
            other_user.name AS other_user_name,
            other_user.avatar_url AS other_user_avatar,
            last_message.content AS last_message_content,
            last_message.created_at AS last_message_time
        FROM conversations c
        JOIN conversation_participants cp ON c.id = cp.conversation_id
        JOIN users other_user ON cp.user_id = other_user.id
        LEFT JOIN (
            SELECT conversation_id, content, created_at
            FROM messages m
            WHERE (conversation_id, id) IN (
                SELECT conversation_id, MAX(id)
                FROM messages
                GROUP BY conversation_id
            )
        ) AS last_message ON c.id = last_message.conversation_id
        WHERE c.id IN (SELECT conversation_id FROM conversation_participants WHERE user_id = ?)
          AND cp.user_id != ?
        ORDER BY last_message.created_at DESC
    ");
    $stmt->bind_param('ii', $userId, $userId);
    $stmt->execute();
    $conversations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    send_json_success(['conversations' => $conversations]);
}

/**
 * Fetches all messages for a given conversation.
 */
function handleGetMessages($conn, $userId) {
    $conversationId = (int)($_GET['conversation_id'] ?? 0);

    // Security check: Ensure the user is part of this conversation
    $checkStmt = $conn->prepare("SELECT 1 FROM conversation_participants WHERE conversation_id = ? AND user_id = ?");
    $checkStmt->bind_param('ii', $conversationId, $userId);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows === 0) {
        $checkStmt->close();
        send_json_error('Access denied.', 403);
    }
    $checkStmt->close();

    $stmt = $conn->prepare("
        SELECT m.id, m.sender_id, m.content, m.created_at
        FROM messages m
        WHERE m.conversation_id = ?
        ORDER BY m.created_at ASC
    ");
    $stmt->bind_param('i', $conversationId);
    $stmt->execute();
    $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    send_json_success(['messages' => $messages]);
}

/**
 * Sends a new message to a user, creating a conversation if one doesn't exist.
 */
function handleSendMessage($conn, $userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    $recipientId = (int)($input['recipient_id'] ?? 0);
    $content = trim($input['content'] ?? '');

    if ($recipientId <= 0 || empty($content)) {
        send_json_error('Recipient and message content are required.', 400);
    }

    // Find existing conversation between the two users
    $stmt = $conn->prepare("
        SELECT conversation_id
        FROM conversation_participants cp1
        JOIN conversation_participants cp2 ON cp1.conversation_id = cp2.conversation_id
        WHERE cp1.user_id = ? AND cp2.user_id = ?
    ");
    $stmt->bind_param('ii', $userId, $recipientId);
    $stmt->execute();
    $result = $stmt->get_result();
    $conversationId = $result->fetch_assoc()['conversation_id'] ?? null;
    $stmt->close();

    // If no conversation exists, create one
    if (!$conversationId) {
        $createConvStmt = $conn->prepare("INSERT INTO conversations () VALUES ()");
        if (!$createConvStmt->execute()) {
            send_json_error('Failed to create conversation.', 500);
        }
        $conversationId = $conn->insert_id;
        $createConvStmt->close();

        $partStmt = $conn->prepare("INSERT INTO conversation_participants (conversation_id, user_id) VALUES (?, ?), (?, ?)");
        $partStmt->bind_param('iiii', $conversationId, $userId, $conversationId, $recipientId);
        if (!$partStmt->execute()) {
            send_json_error('Failed to add participants.', 500);
        }
        $partStmt->close();
    }

    // Insert the new message
    $msgStmt = $conn->prepare("INSERT INTO messages (conversation_id, sender_id, content) VALUES (?, ?, ?)");
    $msgStmt->bind_param('iis', $conversationId, $userId, $content);
    if ($msgStmt->execute()) {
        send_json_success(['message' => 'Message sent.', 'conversation_id' => $conversationId]);
    } else {
        send_json_error('Failed to send message.', 500);
    }
    $msgStmt->close();
}

/**
 * Fetches basic details for a given user ID.
 */
function handleGetUserDetails($conn) {
    $targetUserId = (int)($_GET['user_id'] ?? 0);
    if ($targetUserId <= 0) {
        send_json_error('Invalid user ID.', 400);
    }

    $stmt = $conn->prepare("SELECT id, name, avatar_url FROM users WHERE id = ?");
    $stmt->bind_param('i', $targetUserId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($user) {
        send_json_success(['user' => $user]);
    } else {
        send_json_error('User not found.', 404);
    }
}