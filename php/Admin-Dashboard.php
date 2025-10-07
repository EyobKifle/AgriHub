<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utils.php';

// Authentication and Authorization Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../HTML/login.html');
    exit();
}

$name = $_SESSION['name'] ?? 'Admin';
$email = $_SESSION['email'] ?? '';
$initial = !empty($name) ? strtoupper($name[0]) : 'A';

// --- Fetch Dashboard Stats ---

$totalUsersResult = $conn->query("SELECT COUNT(*) as count FROM users");
$totalUsers = $totalUsersResult->fetch_assoc()['count'] ?? 0;

$activeListingsResult = $conn->query("SELECT COUNT(*) as count FROM products WHERE status = 'active'");
$activeListings = $activeListingsResult->fetch_assoc()['count'] ?? 0;

$pendingReportsResult = $conn->query("SELECT COUNT(*) as count FROM reports WHERE status = 'open'");
$pendingReports = $pendingReportsResult->fetch_assoc()['count'] ?? 0;

// --- Fetch Recent Items ---

$recentUsersStmt = $conn->prepare("SELECT id, name, created_at FROM users ORDER BY created_at DESC LIMIT 4");
$recentUsersStmt->execute();
$recentUsers = $recentUsersStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$recentUsersStmt->close();

$recentListingsStmt = $conn->prepare("
    SELECT p.id, p.title, u.name as seller_name
    FROM products p
    JOIN users u ON p.seller_id = u.id
    ORDER BY p.created_at DESC LIMIT 4
");
$recentListingsStmt->execute();
$recentListings = $recentListingsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$recentListingsStmt->close();

$recentReportsStmt = $conn->prepare("
    SELECT r.id, r.reason, u.name as reporter_name
    FROM reports r
    JOIN users u ON r.reporter_id = u.id
    WHERE r.status = 'open'
    ORDER BY r.created_at DESC LIMIT 3
");
$recentReportsStmt->execute();
$recentReports = $recentReportsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$recentReportsStmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AgriHub - Admin Dashboard</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../Css/Admin-Dashboard.css">
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
        <a href="Admin-Settings.php" class="profile-link" aria-label="User Settings">
            <div class="profile-avatar"><?php echo htmlspecialchars($initial); ?></div>
        </a>
    </div>
</header>

<div class="dashboard-container">
    <aside class="sidebar" id="sidebar">
        <ul class="sidebar-nav">
            <li><a href="Admin-Dashboard.php" class="active"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>
            <li><a href="Admin-User-Management.php"><i class="fa-solid fa-users"></i> User Management</a></li>
            <li><a href="Admin-Listings-Management.php"><i class="fa-solid fa-store"></i> Listing Management</a></li>
            <li><a href="Admin-News-Management.php"><i class="fa-solid fa-newspaper"></i> News Management</a></li>
            <li><a href="Admin-Reports-Management.php"><i class="fa-solid fa-flag"></i> Reports</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="content-wrapper">
            <div class="main-header">
                <h1>Dashboard</h1>
                <p>Welcome back, <?php echo htmlspecialchars($name); ?>! Here's an overview of your platform.</p>
            </div>

            <div class="cards-grid chart-grid">
                <div class="card">
                    <h3 class="card-title">User Growth (Last 6 Months)</h3>
                    <div class="chart-container">[Chart.js: User Growth]</div>
                </div>
                <div class="card">
                    <h3 class="card-title">Listing Categories</h3>
                    <div class="chart-container">[Chart.js: Doughnut Chart]</div>
                </div>
            </div>

            <div class="cards-grid summary-grid">
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Total Users</span>
                        <span class="card-icon icon-users"><i class="fa-solid fa-users"></i></span>
                    </div>
                    <div class="card-value"><?php echo htmlspecialchars($totalUsers); ?></div>
                    <div class="card-footer">+12% this month</div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Active Listings</span>
                        <span class="card-icon icon-listings"><i class="fa-solid fa-store"></i></span>
                    </div>
                    <div class="card-value"><?php echo htmlspecialchars($activeListings); ?></div>
                    <div class="card-footer">+30 new today</div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Pending Reports</span>
                        <span class="card-icon icon-news"><i class="fa-solid fa-flag"></i></span>
                    </div>
                    <div class="card-value"><?php echo htmlspecialchars($pendingReports); ?></div>
                    <div class="card-footer">Action required</div>
                </div>
            </div>

            <div class="recent-items-grid">
                <div class="card list-card">
                    <h3 class="card-title">Recent Users</h3>
                    <ul class="recent-list">
                        <?php foreach ($recentUsers as $user): ?>
                        <li><a href="#"><?php echo htmlspecialchars($user['name']); ?></a> Joined: <?php echo htmlspecialchars(date('Y-m-d', strtotime($user['created_at']))); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="Admin-User-Management.php" class="view-all-link">View All Users &rarr;</a>
                </div>

                <div class="card list-card">
                    <h3 class="card-title">Recent Listings</h3>
                    <ul class="recent-list">
                        <?php foreach ($recentListings as $listing): ?>
                        <li><a href="#"><?php echo htmlspecialchars($listing['title']); ?></a> By: <?php echo htmlspecialchars($listing['seller_name']); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="Admin-Listings-Management.php" class="view-all-link">View All Listings &rarr;</a>
                </div>

                <div class="card list-card">
                    <h3 class="card-title">Forum Reports</h3>
                    <ul class="recent-list">
                        <?php foreach ($recentReports as $report): ?>
                        <li><a href="#"><?php echo htmlspecialchars($report['reason']); ?></a> Reported by: <?php echo htmlspecialchars($report['reporter_name']); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="Admin-Reports-Management.php" class="view-all-link">Manage Reports &rarr;</a>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="../Js/dashboard.js" type="module"></script>
</body>
</html>
<?php $conn->close(); ?>
