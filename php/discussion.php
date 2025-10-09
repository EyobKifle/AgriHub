<?php
/**
 * AgriHub Discussion Page & API
 *
 * This script serves a dual purpose:
 * 1. If accessed via a standard GET request without an 'action', it displays the HTML for a discussion page.
 * 2. If it receives an AJAX POST request for 'report_discussion', it handles the report.
 */

session_start();
require_once __DIR__ . '/config.php'; // Moved to the top

// --- HTML Page Rendering Logic (Only if it's a page request) ---
if (isset($_GET['id'])) {
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
                        <div id="report-status-message" class="modal-status-message" style="display: none;"></div>
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