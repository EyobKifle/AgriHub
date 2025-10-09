<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../HTML/Login.html');
    exit();
}
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utils.php';

$userId = (int)$_SESSION['user_id'];
$name = $_SESSION['name'] ?? 'User';
$email = $_SESSION['email'] ?? '';
$initial = strtoupper(mb_substr($name, 0, 1));
$avatar_url = '';
$currentPage = 'User-Dashboard';

// Fetch user avatar for header/sidebar
$stmt_avatar = $conn->prepare("SELECT avatar_url FROM users WHERE id = ?");
if ($stmt_avatar) {
    $stmt_avatar->bind_param('i', $userId);
    $stmt_avatar->execute();
    $avatar_url = $stmt_avatar->get_result()->fetch_object()->avatar_url ?? '';
    $stmt_avatar->close();
}

// --- Fetch Dashboard Stats ---

// Active Listings
$activeListings = 0;
$stmt_listings = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE seller_id = ? AND status = 'active'");
$stmt_listings->bind_param('i', $userId);
$stmt_listings->execute();
$activeListings = $stmt_listings->get_result()->fetch_assoc()['count'] ?? 0;
$stmt_listings->close();

// Unread Messages
$unreadMessages = 0;
$stmt_messages = $conn->prepare("
    SELECT COUNT(m.id) as count
    FROM messages m
    JOIN conversation_participants cp ON m.conversation_id = cp.conversation_id
    WHERE cp.user_id = ? 
      AND m.sender_id != ?
      AND (cp.last_read_at IS NULL OR m.created_at > cp.last_read_at)
");
if ($stmt_messages) {
    $stmt_messages->bind_param('ii', $userId, $userId);
    $stmt_messages->execute();
    $unreadMessages = $stmt_messages->get_result()->fetch_assoc()['count'] ?? 0;
    $stmt_messages->close();
}

// Sales Total
$salesTotal = "0.00";
$stmt_sales = $conn->prepare("SELECT SUM(amount) as total FROM completed_sales WHERE seller_id = ?");
if ($stmt_sales) {
    $stmt_sales->bind_param('i', $userId);
    $stmt_sales->execute();
    $salesTotal = $stmt_sales->get_result()->fetch_assoc()['total'] ?? 0.00;
    $stmt_sales->close();
}

// --- Fetch Recent Items ---

$recentListings = [];
$stmt_recent_listings = $conn->prepare("SELECT id, title, status FROM products WHERE seller_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt_recent_listings->bind_param('i', $userId);
$stmt_recent_listings->execute();
$recentListings = $stmt_recent_listings->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_recent_listings->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-i18n-key="user.dashboard.pageTitle">Dashboard - AgriHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../Css/User-Dashboard.css">
</head>
<body>
    <header class="main-header-bar">
        <div class="header-left">
            <button class="hamburger-menu" id="hamburger-menu" aria-label="Toggle Menu"><i class="fa-solid fa-bars"></i></button>
        </div>
        <div class="header-center">
            <div class="logo"><i class="fa-solid fa-leaf"></i><span data-i18n-key="brand.name">AgriHub</span></div>
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
            <div class="main-header">
                <h1 data-i18n-key="user.dashboard.welcome" data-i18n-name="<?php echo e($name); ?>">Welcome back, <?php echo e($name); ?>!</h1>
                <p data-i18n-key="user.dashboard.subtitle">Here's what's new on AgriHub today.</p>
            </div>

            <div class="cards-grid summary-grid">
                <div class="card">
                    <div class="card-header">
                        <span class="card-title" data-i18n-key="user.dashboard.activeListings">Active Listings</span>
                        <span class="card-icon icon-listings"><i class="fa-solid fa-store"></i></span>
                    </div>
                    <div class="card-value"><?php echo (int)$activeListings; ?></div>
                    <a href="User-Listings.php" class="card-footer" data-i18n-key="user.dashboard.activeListingsFooter">Manage your products</a>
                </div>
                <div class="card">
                    <div class="card-header">
                        <span class="card-title" data-i18n-key="user.dashboard.unreadMessages">Unread Messages</span>
                        <span class="card-icon icon-users"><i class="fa-solid fa-envelope"></i></span>
                    </div>
                    <div class="card-value"><?php echo (int)$unreadMessages; ?></div>
                    <a href="User-Messages.php" class="card-footer" data-i18n-key="user.dashboard.unreadMessagesFooter">View messages</a>
                </div>
                <div class="card">
                    <div class="card-header">
                        <span class="card-title" data-i18n-key="user.dashboard.sales">Sales Total</span>
                        <span class="card-icon icon-revenue"><i class="fa-solid fa-dollar-sign"></i></span>
                    </div>
                    <div class="card-value">ETB <?php echo number_format((float)$salesTotal, 2); ?></div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title" data-i18n-key="user.dashboard.recentListings.title">Your Recent Listings</h3>
                </div>
                <?php if (empty($recentListings)): ?>
                    <p data-i18n-key="user.dashboard.recentListings.none">You have not created any listings yet.</p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th data-i18n-key="user.listings.table.product">Product</th>
                                <th data-i18n-key="user.listings.table.status">Status</th>
                                <th data-i18n-key="user.listings.table.actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentListings as $item): ?>
                                <tr>
                                    <td><?php echo e($item['title']); ?></td>
                                    <td><span class="status status-<?php echo e(strtolower($item['status'])); ?>" data-i18n-key="user.listings.status.<?php echo e(strtolower($item['status'])); ?>"><?php echo e(ucfirst($item['status'])); ?></span></td>
                                    <td class="action-buttons">
                                        <a href="User-Listings.php#listing-<?php echo (int)$item['id']; ?>" class="btn-icon" data-i18n-title-key="user.listings.actions.edit" title="Edit Listing"><i class="fa-solid fa-pen"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script type="module" src="../Js/dashboard.js"></script>
      <script type="module" src="/AgriHub/Js/site.js"></script>
</body>
</html>