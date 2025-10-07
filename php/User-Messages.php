<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /AgriHub/HTML/Login.html');
    exit();
}
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utils.php';

$userId = (int)$_SESSION['user_id'];
$name = $_SESSION['name'] ?? 'User';
$email = $_SESSION['email'] ?? '';
$initial = strtoupper(mb_substr($name, 0, 1));
$avatar_url = '';

// Fetch user avatar for header/sidebar
$stmt_avatar = $conn->prepare("SELECT avatar_url FROM users WHERE id = ?");
if ($stmt_avatar) {
    $stmt_avatar->bind_param('i', $userId);
    $stmt_avatar->execute();
    $avatar_url = $stmt_avatar->get_result()->fetch_object()->avatar_url ?? '';
    $stmt_avatar->close();
}
$currentPage = 'User-Messages';

// Check if we are starting a new conversation from a user link (e.g., product page)
$newConversationUserId = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - AgriHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/AgriHub/Css/User-Dashboard.css">
</head>
<body>
    <header class="main-header-bar">
        <div class="header-left">
            <button class="hamburger-menu" id="hamburger-menu" aria-label="Toggle Menu">
                <i class="fa-solid fa-bars"></i>
            </button>
        </div>
        <div class="header-center">
            <div class="logo">
                <i class="fa-solid fa-leaf"></i>
                <span>AgriHub</span>
            </div>
        </div>
        <div class="header-right">
            <a href="/AgriHub/php/User-Account.php" class="profile-link" aria-label="User Profile">
                <div class="profile-avatar">
                    <?php if (!empty($avatar_url)): ?>
                        <img src="/AgriHub/<?php echo e($avatar_url); ?>" alt="User Avatar">
                    <?php else: echo e($initial); endif; ?>
                </div>
            </a>
        </div>
    </header>

    <div class="dashboard-container">
        <?php include __DIR__ . '/_sidebar.php'; ?>

        <main class="main-content no-padding">
            <div class="messages-layout" id="messages-layout" data-current-user-id="<?php echo e($userId); ?>" data-new-user-id="<?php echo e($newConversationUserId); ?>">

                <div class="conversations-list">
                    <div class="messages-header">
                        <h2>Conversations</h2>
                    </div>
                    <div id="conversations-list-body">
                        <!-- Conversations will be loaded here by JS -->
                        <p style="padding: 1rem; text-align: center; opacity: 0.7;">Loading conversations...</p>
                    </div>
                </div>

                <!-- Chat Panel -->
                <div class="chat-panel">
                    <div class="chat-header" id="chat-header" style="display: none;">
                        <button class="btn-icon back-to-convos" id="back-to-convos" aria-label="Back to conversations">
                            <i class="fas fa-arrow-left"></i>
                        </button>
                        <h3 id="chat-with-name"></h3>
                    </div>

                    <div class="chat-messages" id="chat-messages-area">
                        <!-- Welcome / Empty State -->
                        <div id="chat-empty-state" class="card" style="margin: 2rem; text-align: center; padding: 3rem;">
                            <i class="fa-solid fa-comments" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem;"></i>
                            <h3>Select a conversation</h3>
                            <p>Or start a new one by contacting a seller from a product page.</p>
                        </div>
                        <!-- Messages will be loaded here -->
                    </div>

                    <div class="chat-input-area" id="chat-input-area" style="display: none;">
                        <div class="chat-input-controls">
                            <input type="text" id="message-input" placeholder="Type a message..." autocomplete="off">
                            <button id="send-message-btn" class="btn-icon send-btn" aria-label="Send message">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script type="module" src="/AgriHub/Js/dashboard.js"></script>
    <script type="module" src="/AgriHub/Js/User-Messages.js"></script>
</body>
</html>
