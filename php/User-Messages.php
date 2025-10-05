<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'User not authenticated.']);
    exit();
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utils.php';

$userId = (int)$_SESSION['user_id'];

header('Content-Type: application/json');

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            handleGet($conn, $userId);
            break;
        case 'POST':
            handlePost($conn, $userId);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method Not Allowed']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log("User-Messages.php Error: " . $e->getMessage());
    echo json_encode(['error' => 'An unexpected server error occurred.']);
} finally {
    $conn->close();
}

/**
 * Handles GET requests to fetch initial page data.
 */
function handleGet($conn, $userId) {
    $name = $_SESSION['name'] ?? 'User';
    $email = $_SESSION['email'] ?? '';
    $initial = strtoupper(mb_substr($name, 0, 1));

    // This query is complex. It finds conversations involving the user,
    // gets the other participant's name, the last message, and a count of unread messages.
    $stmt = $conn->prepare("
        SELECT
            c.id, c.subject, c.updated_at,
            (SELECT body FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) AS last_message,
            (SELECT COUNT(*) FROM messages WHERE conversation_id = c.id AND recipient_id = ? AND is_read = 0) AS unread_count,
            other_user.name AS other_participant_name
        FROM conversations c
        JOIN conversation_participants cp ON c.id = cp.conversation_id
        JOIN users other_user ON cp.user_id = other_user.id
        WHERE c.id IN (SELECT conversation_id FROM conversation_participants WHERE user_id = ?)
          AND cp.user_id != ?
        GROUP BY c.id
        ORDER BY c.updated_at DESC
    ");
    $stmt->bind_param('iii', $userId, $userId, $userId);
    $stmt->execute();
    $conversations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode([
        'user' => ['name' => $name, 'email' => $email, 'initial' => $initial],
        'conversations' => $conversations,
    ]);
}

/**
 * Handles POST requests to compose a new message.
 */
function handlePost($conn, $userId) {
    $recipientEmail = trim($_POST['recipient_email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $body = trim($_POST['body'] ?? '');

    if (empty($recipientEmail) || empty($subject) || empty($body)) {
        http_response_code(400);
        echo json_encode(['error' => 'Please fill in all fields.']);
        return;
    }

    // Find recipient
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param('s', $recipientEmail);
    $stmt->execute();
    $recipient = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$recipient) {
        http_response_code(404);
        echo json_encode(['error' => 'Recipient not found.']);
        return;
    }
    $recipientId = (int)$recipient['id'];

    if ($recipientId === $userId) {
        http_response_code(400);
        echo json_encode(['error' => 'You cannot send a message to yourself.']);
        return;
    }

    $conn->begin_transaction();
    try {
        // 1. Create conversation
        $stmt = $conn->prepare("INSERT INTO conversations (subject) VALUES (?)");
        $stmt->bind_param('s', $subject);
        $stmt->execute();
        $convoId = $conn->insert_id;

        // 2. Add participants
        $stmt = $conn->prepare("INSERT INTO conversation_participants (conversation_id, user_id) VALUES (?, ?), (?, ?)");
        $stmt->bind_param('iiii', $convoId, $userId, $convoId, $recipientId);
        $stmt->execute();

        // 3. Add message
        $stmt = $conn->prepare("INSERT INTO messages (conversation_id, sender_id, recipient_id, body) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('iiis', $convoId, $userId, $recipientId, $body);
        $stmt->execute();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Message sent successfully.']);
    } catch (Exception $e) {
        $conn->rollback();
        throw $e; // Re-throw to be caught by the main try-catch block
    }
}