<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../HTML/Farming-Guidance.html');
    exit();
}
require_once __DIR__ . '/utils.php';

$name = $_SESSION['name'] ?? 'User';
$email = $_SESSION['email'] ?? '';
$initial = strtoupper(mb_substr($name, 0, 1));
$currentPage = 'User-Farming-Guidance';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farming Guidance - AgriHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../Css/User-Dashboard.css">
    <link rel="stylesheet" href="../Css/guidance.css">
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
                <div class="profile-avatar"><?php echo e($initial); ?></div>
            </a>
        </div>
    </header>
    
    <div class="dashboard-container">
        <?php include __DIR__ . '/_sidebar.php'; ?>

        <main class="main-content">
            <!-- Original Farming-Guidance.html content starts here -->
            <div class="main-header">
                <h1 data-i18n-key="guidance.page.title">Farming Guidance</h1>
                <p data-i18n-key="guidance.page.subtitle">Master content map for Ethiopian farming: crops, livestock, techniques, health & safety, business, innovation, and policy. Click any topic to open details.</p>
            </div>
            <div class="guidance-map-container" id="guidance-map-container">
                <!-- Guidance map will be loaded by JS -->
            </div>
            <!-- Original Farming-Guidance.html content ends here -->
        </main>
    </div>

    <script type="module" src="../Js/dashboard.js"></script>
</body>
</html>