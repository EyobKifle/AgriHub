<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: Login.html');
    exit();
}

require_once __DIR__ . '/../php/config.php';

$discussionId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($discussionId === 0) {
    // Or redirect to a 404 page
    die('No discussion ID provided.');
}

$currentUserId = (int)$_SESSION['user_id'];
$currentUserName = $_SESSION['name'] ?? 'User';

// Fetch user's avatar URL
$stmt = $conn->prepare("SELECT avatar_url FROM users WHERE id = ?");
$stmt->bind_param('i', $currentUserId);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();
$currentUserAvatar = !empty($user['avatar_url']) ? '../' . $user['avatar_url'] : 'https://placehold.co/48x48/2a9d8f/FFF?text=' . strtoupper(mb_substr($currentUserName, 0, 1));
$stmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Community Chat</title>
  <link rel="stylesheet" href="../Css/header.css">
  <link rel="stylesheet" href="../Css/footer.css">
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="../Css/discussion.css">
</head>
<body 
    data-discussion-id="<?php echo $discussionId; ?>"
    data-user-id="<?php echo $currentUserId; ?>"
    data-user-name="<?php echo htmlspecialchars($currentUserName, ENT_QUOTES, 'UTF-8'); ?>"
    data-user-avatar="<?php echo htmlspecialchars($currentUserAvatar, ENT_QUOTES, 'UTF-8'); ?>"
> 
  <div id="header-placeholder"></div>

  <main class="chat-area">
    <div class="container">
      <div id="chat-messages" class="chat-messages" aria-live="polite"></div>
    </div>
  </main>

  <section class="chat-input">
    <div class="container">
      <form id="chat-form" autocomplete="off">
        <label for="file-upload" class="file-btn" data-i18n-title-key="discussion.attachFile" title="Attach file">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
            <polyline points="17 8 12 3 7 8"></polyline>
            <line x1="12" y1="3" x2="12" y2="15"></line>
          </svg>
        </label>
        <input id="message-input" type="text" data-i18n-placeholder-key="discussion.messagePlaceholder" placeholder="Type a message..." data-i18n-aria-label-key="discussion.messageLabel" aria-label="Message" required />
        <input id="file-upload" type="file" accept="image/*,.pdf" multiple />
        <button type="submit" class="send-btn" data-i18n-key="discussion.sendButton">Send</button>
      </form>
      <div id="file-previews" class="file-previews" aria-live="polite"></div>
    </div>
  </section>

  <div id="footer-placeholder"></div>

  <script src="../Js/chat.js" type="module"></script>
  <script src="../Js/site.js" type="module"></script>
</body>
</html>
