<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../HTML/Farming-Guidance.html');
    exit();
}
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utils.php';

$name = $_SESSION['name'] ?? 'User';
$email = $_SESSION['email'] ?? '';
$userId = (int)($_SESSION['user_id'] ?? 0);
$initial = strtoupper(mb_substr($name, 0, 1));
$currentPage = 'User-Farming-Guidance';
$avatar_url = '';

// Fetch user avatar for header/sidebar
$stmt_avatar = $conn->prepare("SELECT avatar_url FROM users WHERE id = ?");
if ($stmt_avatar) {
    $stmt_avatar->bind_param('i', $userId);
    $stmt_avatar->execute();
    $avatar_url = $stmt_avatar->get_result()->fetch_object()->avatar_url ?? '';
    $stmt_avatar->close();
}

$conn->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farming Guidance - AgriHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/AgriHub/Css/User-Dashboard.css">
    <link rel="stylesheet" href="/AgriHub/Css/guidance.css">
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
            <a href="User-Account.php" class="profile-link" aria-label="User Profile">
                <div class="profile-avatar">
                    <?php if (!empty($avatar_url)): ?><img src="/AgriHub/<?php echo e($avatar_url); ?>" alt="User Avatar"><?php else: ?><?php echo e($initial); ?><?php endif; ?>
                </div>
            </a>
        </div>
    </header>
    
    <div class="dashboard-container">
        <?php include __DIR__ . '/_sidebar.php'; ?>

        <main class="main-content">
            <!-- Content from Farming-Guidance.php adapted for the dashboard -->
            <div class="page-header">
                <h1 data-i18n-key="guidance.title">Farming Guidance</h1>
                <p data-i18n-key="guidance.subtitle">Explore categories to find articles, tips, and discussions.</p>
            </div>

            <div class="search-bar-container" style="margin-bottom: 2rem;">
                <input type="search" id="category-search-input" data-i18n-key="guidance.searchPlaceholder" placeholder="Search for categories like 'Teff', 'Cattle'...">
            </div>

            <div id="guidance-categories-placeholder">
                <!-- Categories will be loaded here by JavaScript -->
                <div class="loading-spinner"></div>
            </div>
        </main>
    </div>

    <script type="module" src="/AgriHub/Js/dashboard.js"></script>
    <script type="module">
        import { initializeGuidancePage } from '/AgriHub/Js/guidance.js';
        initializeGuidancePage();
    </script>
</body>
</html>