<?php
/**
 * AgriHub Discussion Page & API
 * AgriHub Discussion Page & Chat API
 *
 * This script serves a dual purpose:
 * 1. If accessed via a standard GET request without an 'action', it displays the HTML for a discussion page.
 * 2. If accessed with an 'action' parameter, it functions as a JSON API for chat messages within that discussion.
 * 2. If accessed with an 'action' parameter, it functions as a JSON API for discussions and their messages.
 *
 * Actions:
 * - get_messages: Fetches all messages for a specific discussion.
 * - add_message: Adds a new message to a discussion.
 * - update_message: Edits an existing message.
 * - delete_message: Deletes a message.
 */

session_start();
require_once __DIR__ . '/config.php'; // Moved to the top

$isApiRequest = !empty($_REQUEST['action']);
$isPageRequest = !$isApiRequest && isset($_GET['id']);

if ($isApiRequest) {
    // Set a custom error handler to catch PHP errors and return them as JSON.
    set_error_handler(function($severity, $message, $file, $line) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => "Server Error: $message in $file on line $line"]);
        exit;
    });

    header('Content-Type: application/json');
    // require_once has been moved to the top of the file.

    function send_json_error($message, $statusCode = 400) {
        // The message is now an i18n key
        http_response_code($statusCode);
        echo json_encode(['success' => false, 'error' => ['code' => $message]]);
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
    if ($current_user_id === 0 && $_SERVER['REQUEST_METHOD'] !== 'GET') {
        send_json_error('error.unauthorized', 401);
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
        send_json_error('error.api.noAction');
    }

    try {
        switch ($action) {
            case 'get_messages':
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
    
                // Reformat for frontend JS expectations (user object)
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
                        'attachments' => [], // Attachments not implemented in DB yet
                    ];
                }, $messages);
    
                send_json_success(['messages' => $formatted_messages]);
                break;
    
            case 'add_message':
                $discussionId = (int)($input['message']['discussionId'] ?? 0);
                $text = trim($input['message']['text'] ?? '');
    
                if ($discussionId <= 0 || empty($text)) {
                    send_json_error('error.discussion.missingData');
                }
    
                $conn->begin_transaction();
                
                // 1. Insert the new message
                $stmt_insert = $conn->prepare("INSERT INTO discussion_comments (discussion_id, author_id, content) VALUES (?, ?, ?)");
                $stmt_insert->bind_param('iis', $discussionId, $current_user_id, $text);
                $stmt_insert->execute();
                $newMessageId = $conn->insert_id;
                $stmt_insert->close();
    
                // 2. Update the parent discussion's timestamp and comment count
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
                // Check ownership before updating
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
                
                // 1. Find the discussion_id and verify ownership before deleting
                $stmt_select = $conn->prepare("SELECT discussion_id FROM discussion_comments WHERE id = ? AND author_id = ?");
                $stmt_select->bind_param('ii', $messageId, $current_user_id);
                $stmt_select->execute();
                $result = $stmt_select->get_result();
                $message = $result->fetch_assoc();
                $stmt_select->close();
    
                if (!$message) {
                    $conn->rollback();
                    send_json_error('error.discussion.notFoundOrNoPermission', 404);
                }
                $discussionId = $message['discussion_id'];
    
                // 2. Delete the message
                $stmt_delete = $conn->prepare("DELETE FROM discussion_comments WHERE id = ?");
                $stmt_delete->bind_param('i', $messageId);
                $stmt_delete->execute();
                $stmt_delete->close();
    
                // 3. Decrement the comment count on the parent discussion
                $stmt_update = $conn->prepare("UPDATE discussions SET comment_count = GREATEST(0, comment_count - 1) WHERE id = ?");
                $stmt_update->bind_param('i', $discussionId);
                $stmt_update->execute();
                $stmt_update->close();
    
                $conn->commit();
                send_json_success();
                break;
    
            default:
                send_json_error('error.api.invalidAction');
                break;
        }
    } catch (Exception $e) {
  $echo("error");
        $conn->rollback();
    }
}

// --- HTML Page Rendering Logic (Only if it's a page request) ---
if ($isPageRequest) {
    $discussionId = (int)($_GET['id'] ?? 0);
    // We need to get some user info for the header if they are logged in
    $name = $_SESSION['name'] ?? 'Guest';
    $initial = !empty($name) && $name !== 'Guest' ? strtoupper(mb_substr($name, 0, 1)) : '?';
    $avatar_url = $_SESSION['avatar_url'] ?? '';
    $isLoggedIn = isset($_SESSION['user_id']);

    // Fetch the main discussion post details
    require_once __DIR__ . '/utils.php';
    $discussion = null;
    if ($discussionId > 0) {
        $stmt = $conn->prepare("
            SELECT d.title, d.content, d.created_at, u.name as author_name, c.name as category_name
            FROM discussions d
            JOIN users u ON d.author_id = u.id
            JOIN discussion_categories c ON d.category_id = c.id
            WHERE d.id = ?
        ");
        if ($stmt) {
            $stmt->bind_param('i', $discussionId);
            $stmt->execute();
            $result = $stmt->get_result();
            $discussion = $result->fetch_assoc();
            $stmt->close();
        }
    }
    $conn->close();

    // We also need the current user's info for the chat JS
    $currentUserJson = json_encode([
        'id' => $_SESSION['user_id'] ?? 0,
        'name' => $name,
        'avatar' => !empty($avatar_url) ? '/AgriHub/' . e($avatar_url) : 'https://placehold.co/48x48/cccccc/FFF?text=' . urlencode($initial)
    ]);

    ?>
    <!DOCTYPE html>
    <html lang="en" data-theme="light">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Discussion - AgriHub</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link rel="stylesheet" href="/AgriHub/Css/header.css">
        <link rel="stylesheet" href="/AgriHub/Css/footer.css">
        <link rel="stylesheet" href="/AgriHub/Css/discussion.css">
    </head>
    <body data-discussion-id="<?php echo $discussionId; ?>">
        
        <div id="header-placeholder"></div>
        
        <main class="page-container">
            <div class="content-wrapper">
                <a href="Community.php" class="back-link" data-i18n-key="discussion.backButton">&larr; Back to Community Forum</a>

                <?php if ($discussion): ?>
                    <div class="discussion-post">
                        <header class="discussion-post-header">
                            <h1><?php echo htmlspecialchars($discussion['title']); ?></h1>
                            <div class="discussion-post-meta">
                                <span data-i18n-key="common.by">By</span> <span class="author-name"><?php echo htmlspecialchars($discussion['author_name']); ?></span> &bull; <?php echo time_ago($discussion['created_at']); ?>
                                 <?php if ($isLoggedIn): ?>
                                <button class="btn-icon report-btn" id="report-discussion-btn" data-i18n-title-key="discussion.reportButton.title" title="Report this discussion"><i class="fa-solid fa-flag"></i> <span data-i18n-key="discussion.reportButton">Report</span></button>
                                <?php endif; ?>
                                <span class="category-badge"><?php echo htmlspecialchars($discussion['category_name']); ?></span>
                            </div>
                        </header>
                        <div class="discussion-post-body">
                            <p><?php echo nl2br(htmlspecialchars($discussion['content'])); ?></p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="chat-container">
                    <div class="chat-messages" id="messages">
                        <!-- Messages will be loaded here by JavaScript -->
                    </div>
                    
                    <?php if ($isLoggedIn): ?>
                    <form class="chat-input-area" id="message-form" data-mode="new" data-editing-id="">
                        <div class="chat-file-previews" id="previews"></div>
                        <div class="chat-input-controls">
                            <label for="file-input" class="btn-icon" title="Attach file" data-i18n-title-key="discussion.attachFile">
                                <i class="fas fa-paperclip"></i>
                            </label>
                            <input type="file" id="file-input" multiple hidden>
                            
                            <input type="text" id="message-input" data-i18n-placeholder-key="discussion.messagePlaceholder" placeholder="Type a message..." autocomplete="off" aria-label="Message" required>
                            
                            <button type="submit" class="btn-icon send-btn" aria-label="Send message" data-i18n-aria-label-key="discussion.sendButton">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                        <div class="edit-mode-indicator" id="edit-indicator" style="display: none;">
                            <span data-i18n-key="discussion.editing">Editing message...</span> <button type="button" id="cancel-edit-btn" data-i18n-key="common.cancel">Cancel</button>
                        </div>
                    </form>
                    <?php else: ?>
                        <div class="login-prompt">
                            <p data-i18n-key="discussion.loginPrompt">Please <a href="../HTML/Login.html" data-i18n-key="discussion.loginLink">log in</a> or <a href="../HTML/Signup.html" data-i18n-key="discussion.signupLink">sign up</a> to join the conversation.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>

        <!-- Report Modal -->
        <div id="report-modal" class="modal-overlay" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 data-i18n-key="discussion.reportModal.title">Report Discussion</h2>
                    <button id="close-report-modal" class="close-btn" data-i18n-key="common.close">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="report-form">
                        <p data-i18n-key="discussion.reportModal.reasonPrompt">Please select a reason for reporting this discussion:</p>
                        <div class="form-group">
                            <label><input type="radio" name="reason" value="spam" required> <span data-i18n-key="discussion.reportModal.reason.spam">It's spam or advertising</span></label>
                            <label><input type="radio" name="reason" value="hate_speech"> <span data-i18n-key="discussion.reportModal.reason.hate">It's hate speech or harassment</span></label>
                            <label><input type="radio" name="reason" value="misinformation"> <span data-i18n-key="discussion.reportModal.reason.misinfo">It contains false information</span></label>
                            <label><input type="radio" name="reason" value="inappropriate"> <span data-i18n-key="discussion.reportModal.reason.inappropriate">It's inappropriate or offensive</span></label>
                            <label><input type="radio" name="reason" value="other"> <span data-i18n-key="discussion.reportModal.reason.other">Other</span></label>
                        </div>
                        <div class="form-group">
                            <label for="report-details" data-i18n-key="discussion.reportModal.detailsLabel">Additional Details (optional):</label>
                            <textarea id="report-details" name="details" rows="3" data-i18n-placeholder-key="discussion.reportModal.detailsPlaceholder" placeholder="Provide more information..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary" data-i18n-key="discussion.reportModal.submit">Submit Report</button>
                    </form>
                </div>
            </div>
        </div>
        <div id="footer-placeholder"></div>

        <!-- Pass current user data to JS -->
        <script id="user-data" type="application/json">
            <?php echo $currentUserJson; ?>
        </script>

        <script type="module" src="/AgriHub/Js/site.js"></script>
        <script type="module" src="/AgriHub/Js/chat.js"></script>
    </body>
    </html>
    <?php
}
?>