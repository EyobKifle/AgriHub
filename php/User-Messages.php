<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: Login.html');
    exit();
}
require_once __DIR__ . '/../php/config.php';

$userId = (int)$_SESSION['user_id'];
$name = $_SESSION['name'] ?? 'User';
$email = $_SESSION['email'] ?? '';
$initial = strtoupper(mb_substr($name, 0, 1));

$composeError = '';

// Handle compose POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['action'] ?? '') === 'compose')) {
    $recipientEmail = trim($_POST['recipient_email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $body = trim($_POST['body'] ?? '');

    if ($recipientEmail === '' || $subject === '' || $body === '') {
        $composeError = 'Please fill in recipient, subject and message.';
    } elseif (strcasecmp($recipientEmail, $_SESSION['email'] ?? '') === 0) {
        $composeError = 'You cannot send a message to yourself.';
    } else {
        $conn->begin_transaction();
        try {
            // Find recipient by email
            $stmt = $conn->prepare('SELECT id FROM users WHERE email = ? AND is_active = 1');
            $stmt->bind_param('s', $recipientEmail);
            $stmt->execute();
            $res = $stmt->get_result();
            $recipient = $res->fetch_assoc();
            $stmt->close();

            if (!$recipient) {
                throw new Exception('Recipient not found or inactive.');
            }
            $recipientId = (int)$recipient['id'];

            // Create a new conversation
            $stmt = $conn->prepare('INSERT INTO conversations (subject) VALUES (?)');
            $stmt->bind_param('s', $subject);
            $stmt->execute();
            $conversationId = $stmt->insert_id;
            $stmt->close();

            // Add sender and recipient to the conversation
            $stmt = $conn->prepare('INSERT INTO conversation_participants (conversation_id, user_id) VALUES (?, ?), (?, ?)');
            $stmt->bind_param('iiii', $conversationId, $userId, $conversationId, $recipientId);
            $stmt->execute();
            $stmt->close();

            // Insert the first message
            $stmt = $conn->prepare('INSERT INTO messages (conversation_id, sender_id, body) VALUES (?, ?, ?)');
            $stmt->bind_param('iis', $conversationId, $userId, $body);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            header('Location: User-Messages.php?sent=1');
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $composeError = $e->getMessage();
        }
    }
}

// Load conversations for the current user
$conversations = [];
$sql = "SELECT
            c.id,
            c.subject,
            c.updated_at,
            (SELECT m.body FROM messages m WHERE m.conversation_id = c.id ORDER BY m.created_at DESC LIMIT 1) as last_message,
            (SELECT u.name FROM users u JOIN conversation_participants cp_other ON u.id = cp_other.user_id WHERE cp_other.conversation_id = c.id AND cp_other.user_id != ?) as other_participant_name,
            (SELECT COUNT(*) FROM messages m WHERE m.conversation_id = c.id AND m.created_at > cp.last_read_at) as unread_count
        FROM conversations c
        JOIN conversation_participants cp ON c.id = cp.conversation_id
        WHERE cp.user_id = ?
        ORDER BY c.updated_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $userId, $userId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $conversations[] = $row;
}
$stmt->close();

include '../HTML/User-Messages.html';
