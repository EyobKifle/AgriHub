<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../HTML/Login.html');
    exit();
}
require_once __DIR__ . '/../php/config.php';
require_once __DIR__ . '/utils.php';

$userId = (int)($_SESSION['user_id'] ?? 0);
$name = isset($_SESSION['name']) ? $_SESSION['name'] : 'User';
$initial = strtoupper(mb_substr($name, 0, 1));
$currentPage = 'User-Dashboard'; // For the sidebar active state

// Active listings count (all listings by the user)
$activeListingsCount = 0;
if ($stmt = $conn->prepare('SELECT COUNT(*) AS cnt FROM products WHERE seller_id = ?')) {
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $activeListingsCount = (int)$row['cnt'];
    }
    $stmt->close();
}

// Unread messages placeholder (no messages table implemented yet)
$unreadMessagesCount = 0;

// Sales this month (sum of quantity * unit_price for order_items where user is seller)
$salesThisMonth = 0.0;
if ($stmt = $conn->prepare('SELECT COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS total FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE oi.seller_id = ? AND YEAR(o.created_at) = YEAR(CURDATE()) AND MONTH(o.created_at) = MONTH(CURDATE())')) {
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $salesThisMonth = (float)$row['total'];
    }
    $stmt->close();
}

// Recent activity: combine listings, orders, discussions (limit to 10 items total)
$activities = [];
// Listings created by user
if ($stmt = $conn->prepare('SELECT title AS label, created_at AS ts, "listing" AS type FROM products WHERE seller_id = ? ORDER BY created_at DESC LIMIT 10')) {
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) { $activities[] = $r; }
    $stmt->close();
}
// Orders involving user
if ($stmt = $conn->prepare('SELECT CONCAT("Order #", o.id) AS label, o.created_at AS ts, "order" AS type FROM orders o LEFT JOIN order_items oi ON o.id = oi.order_id WHERE o.buyer_id = ? OR oi.seller_id = ? GROUP BY o.id ORDER BY o.created_at DESC LIMIT 10')) {
    $stmt->bind_param('ii', $userId, $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) { $activities[] = $r; }
    $stmt->close();
}
// Discussions authored by user
if ($stmt = $conn->prepare('SELECT title AS label, updated_at AS ts, "discussion" AS type FROM discussions WHERE author_id = ? ORDER BY updated_at DESC LIMIT 10')) {
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) { $activities[] = $r; }
    $stmt->close();
}
// Sort and limit
usort($activities, function($a, $b) {
    return strtotime($b['ts']) <=> strtotime($a['ts']);
});
$activities = array_slice($activities, 0, 10);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgriHub - User Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../Css/User-Dashboard.css">
</head>
<body>
    <!-- Full-width Header -->
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
            <div class="main-header">
                <h1>Welcome back, <?php echo e($name); ?>!</h1>
                <p data-i18n-key="user.dashboard.subtitle">Here's what's new on AgriHub today.</p>
            </div>

            <div class="cards-grid">
                <div class="card">
                    <div class="card-header">
                        <span class="card-title" data-i18n-key="user.dashboard.activeListings">Active Listings</span>
                        <span class="card-icon icon-listings"><i class="fa-solid fa-list-check"></i></span>
                    </div>
                    <div class="card-value"><?php echo (int)$activeListingsCount; ?></div>
                    <div class="card-footer" data-i18n-key="user.dashboard.activeListingsFooter">Manage your products</div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <span class="card-title" data-i18n-key="user.dashboard.unreadMessages">Unread Messages</span>
                        <span class="card-icon icon-users"><i class="fa-solid fa-envelope"></i></span>
                    </div>
                    <div class="card-value"><?php echo (int)$unreadMessagesCount; ?></div>
                    <div class="card-footer">Check your inbox</div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <span class="card-title" data-i18n-key="user.dashboard.sales">Sales This Month</span>
                        <span class="card-icon icon-revenue"><i class="fa-solid fa-dollar-sign"></i></span>
                    </div>
                    <div class="card-value">ETB <?php echo number_format((float)$salesThisMonth, 2); ?></div>
                    <div class="card-footer">This month</div>
                </div>
            </div>

            <div class="content-section">
                <div class="main-header">
                    <h2 data-i18n-key="user.dashboard.activity.title">Recent Activity</h2>
                    <p data-i18n-key="user.dashboard.activity.subtitle">Here's what has happened recently on your account.</p>
                </div>
                <div class="card activity-feed">
                    <!-- Activity items will be populated dynamically -->
                    <ul class="recent-list" id="activity-list">
                        <?php if (empty($activities)): ?>
                            <li style="opacity:.8;">No recent activity.</li>
                        <?php else: ?>
                            <?php foreach ($activities as $a): ?>
                                <li>
                                    <span class="item-title"><?php echo e($a['label']); ?></span>
                                    <span class="item-meta" style="opacity:.8; float:right;">
                                        <?php echo e(time_ago($a['ts'])); ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

        </main>
    </div>
    
    <script type="module" src="../Js/dashboard.js"></script>
</body>
</html>
