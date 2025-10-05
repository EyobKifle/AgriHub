<?php
/**
 * AgriHub Chat API
 *
 * This script handles all backend operations for the community chat feature.
 * It processes requests to fetch, add, update, and delete messages.
 *
 * Actions:
 * - get_messages: Fetches all messages for a specific discussion.
 * - add_message: Adds a new message to a discussion.
 * - update_message: Edits an existing message.
 * - delete_message: Deletes a message.
 */

session_start();
header('Content-Type: application/json');

// Note: In a real application, you would include your database connection file here.
// require_once __DIR__ . '/../config/database.php';

/**
 * A helper function to send a standardized JSON error response and exit.
 * @param string $message The error message.
 * @param int $statusCode The HTTP status code to send.
 */
function send_json_error($message, $statusCode = 400) {
    http_response_code($statusCode);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

/**
 * A helper function to send a standardized JSON success response and exit.
 * @param array|null $data The data payload to include in the response.
 */
function send_json_success($data = null) {
    $response = ['success' => true];
    if ($data !== null) {
        // If data is not an associative array, wrap it.
        if (is_array($data) && array_keys($data) === range(0, count($data) - 1)) {
             $response['data'] = $data;
        } else {
            $response = array_merge($response, $data);
        }
    }
    echo json_encode($response);
    exit;
}

// For demonstration, we'll use a mock user ID. In a real app, this comes from the session.
$current_user_id = $_SESSION['user_id'] ?? 1; // Fallback to 1 for guests/dev

$request_method = $_SERVER['REQUEST_METHOD'];
$action = '';

if ($request_method === 'GET') {
    $action = $_GET['action'] ?? '';
} elseif ($request_method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
}

if (empty($action)) {
    send_json_error('No action specified.');
}

// --- MOCK DATABASE LOGIC ---
// In a real application, this part would interact with a MySQL/PostgreSQL database.
// We are using mock responses to illustrate the API's behavior.

switch ($action) {
    case 'get_messages':
        $discussion_id = $_GET['discussion_id'] ?? 'general';
        // In a real app: SELECT * FROM messages WHERE discussion_id = ? ORDER BY created_at ASC
        // For now, return an empty array as the frontend uses localStorage.
        send_json_success(['messages' => []]);
        break;

    case 'add_message':
        if (!isset($input['message'])) {
            send_json_error('Message data is missing.');
        }
        // In a real app: INSERT INTO messages (user_id, discussion_id, content) VALUES (?, ?, ?)
        $new_message_id = rand(100, 999); // Simulate a new database ID
        send_json_success(['message_id' => $new_message_id]);
        break;

    case 'update_message':
        if (!isset($input['message_id']) || !isset($input['text'])) {
            send_json_error('Message ID and text are required for update.');
        }
        // In a real app: UPDATE messages SET content = ? WHERE id = ? AND user_id = ?
        send_json_success(['message' => 'Message updated successfully.']);
        break;

    case 'delete_message':
        if (!isset($input['message_id'])) {
            send_json_error('Message ID is required for deletion.');
        }
        // In a real app: DELETE FROM messages WHERE id = ? AND user_id = ?
        send_json_success(['message' => 'Message deleted successfully.']);
        break;

    default:
        send_json_error('Invalid action specified.');
        break;
}
?>
