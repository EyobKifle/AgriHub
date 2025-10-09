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

// --- Fetch Chart Data ---

// 1. User Growth Data (Last 6 Months)
$userGrowthLabels = [];
$userGrowthData = [];
$sixMonthsAgo = new DateTime('-5 months'); // Go back 5 months to get a total of 6
$sixMonthsAgo->modify('first day of this month');
for ($i = 0; $i < 6; $i++) {
    $userGrowthLabels[] = $sixMonthsAgo->format('M Y');
    $userGrowthData[$sixMonthsAgo->format('Y-m')] = 0;
    $sixMonthsAgo->modify('+1 month');
}

$userGrowthQuery = "
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(id) as count
    FROM users
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY month
    ORDER BY month ASC
";
$userGrowthResult = $conn->query($userGrowthQuery);
while ($row = $userGrowthResult->fetch_assoc()) {
    $userGrowthData[$row['month']] = (int)$row['count'];
}
$userGrowthData = array_values($userGrowthData); // Convert associative array to indexed for JS

// 2. Listing Categories Data (Doughnut Chart)
$categoryDataQuery = "
    SELECT c.name as category_name, COUNT(p.id) as product_count
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.status = 'active'
    GROUP BY c.name
    ORDER BY product_count DESC
";
$categoryResult = $conn->query($categoryDataQuery);
$listingCategories = $categoryResult->fetch_all(MYSQLI_ASSOC);

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
<title data-i18n-key="admin.dashboard.pageTitle">AgriHub - Admin Dashboard</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            <span data-i18n-key="brand.name">AgriHub</span>
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
            <li><a href="Admin-Dashboard.php" class="active" data-i18n-key="admin.nav.dashboard"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>
            <li><a href="Admin-User-Management.php" data-i18n-key="admin.nav.userManagement"><i class="fa-solid fa-users"></i> User Management</a></li>
            <li><a href="Admin-Listings-Management.php" data-i18n-key="admin.nav.listingManagement"><i class="fa-solid fa-store"></i> Listing Management</a></li>
            <li><a href="Admin-News-Management.php" data-i18n-key="admin.nav.newsManagement"><i class="fa-solid fa-newspaper"></i> News Management</a></li>
            <li><a href="Admin-Reports-Management.php" data-i18n-key="admin.nav.reports"><i class="fa-solid fa-flag"></i> Reports</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="content-wrapper">
            <div class="main-header">
                <h1 data-i18n-key="admin.dashboard.title">Dashboard</h1>
                <p data-i18n-key="admin.dashboard.subtitle" data-i18n-name="<?php echo htmlspecialchars($name); ?>">Welcome back, <?php echo htmlspecialchars($name); ?>! Here's an overview of your platform.</p>
            </div>

            <div class="cards-grid chart-grid">
                <div class="card">
                    <h3 class="card-title" data-i18n-key="admin.dashboard.charts.userGrowth">User Growth (Last 6 Months)</h3>
                    <div class="chart-container"><canvas id="userGrowthChart"></canvas></div>
                </div>
                <div class="card">
                    <h3 class="card-title" data-i18n-key="admin.dashboard.charts.listingCategories">Listing Categories</h3>
                    <div class="chart-container"><canvas id="listingCategoriesChart"></canvas></div>
                </div>
            </div>

            <div class="cards-grid summary-grid">
                <div class="card">
                    <div class="card-header">
                        <span class="card-title" data-i18n-key="admin.dashboard.summary.totalUsers">Total Users</span>
                        <span class="card-icon icon-users"><i class="fa-solid fa-users"></i></span>
                    </div>
                    <div class="card-value"><?php echo htmlspecialchars($totalUsers); ?></div>
                    <div class="card-footer" data-i18n-key="admin.dashboard.summary.totalUsersFooter">+12% this month</div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <span class="card-title" data-i18n-key="admin.dashboard.summary.activeListings">Active Listings</span>
                        <span class="card-icon icon-listings"><i class="fa-solid fa-store"></i></span>
                    </div>
                    <div class="card-value"><?php echo htmlspecialchars($activeListings); ?></div>
                    <div class="card-footer" data-i18n-key="admin.dashboard.summary.activeListingsFooter">+30 new today</div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <span class="card-title" data-i18n-key="admin.dashboard.summary.pendingReports">Pending Reports</span>
                        <span class="card-icon icon-news"><i class="fa-solid fa-flag"></i></span>
                    </div>
                    <div class="card-value"><?php echo htmlspecialchars($pendingReports); ?></div>
                    <div class="card-footer" data-i18n-key="admin.dashboard.summary.pendingReportsFooter">Action required</div>
                </div>
            </div>

            <div class="recent-items-grid">
                <div class="card list-card">
                    <h3 class="card-title" data-i18n-key="admin.dashboard.recent.usersTitle">Recent Users</h3>
                    <ul class="recent-list">
                        <?php foreach ($recentUsers as $user): ?>
                        <li><a href="#"><?php echo htmlspecialchars($user['name']); ?></a> <span data-i18n-key="admin.dashboard.recent.joined">Joined:</span> <?php echo htmlspecialchars(date('Y-m-d', strtotime($user['created_at']))); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="Admin-User-Management.php" class="view-all-link" data-i18n-key="admin.dashboard.recent.viewAllUsers">View All Users &rarr;</a>
                </div>

                <div class="card list-card">
                    <h3 class="card-title" data-i18n-key="admin.dashboard.recent.listingsTitle">Recent Listings</h3>
                    <ul class="recent-list">
                        <?php foreach ($recentListings as $listing): ?>
                        <li><a href="#"><?php echo htmlspecialchars($listing['title']); ?></a> <span data-i18n-key="common.by">By:</span> <?php echo htmlspecialchars($listing['seller_name']); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="Admin-Listings-Management.php" class="view-all-link" data-i18n-key="admin.dashboard.recent.viewAllListings">View All Listings &rarr;</a>
                </div>

                <div class="card list-card">
                    <h3 class="card-title" data-i18n-key="admin.dashboard.recent.reportsTitle">Forum Reports</h3>
                    <ul class="recent-list">
                        <?php foreach ($recentReports as $report): ?>
                        <li><a href="#"><?php echo htmlspecialchars($report['reason']); ?></a> <span data-i18n-key="admin.dashboard.recent.reportedBy">Reported by:</span> <?php echo htmlspecialchars($report['reporter_name']); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="Admin-Reports-Management.php" class="view-all-link" data-i18n-key="admin.dashboard.recent.manageReports">Manage Reports &rarr;</a>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Pass data from PHP to JavaScript -->
<script id="dashboard-data" type="application/json">
    <?php echo json_encode([
        'userGrowth' => ['labels' => $userGrowthLabels, 'data' => $userGrowthData],
        'listingCategories' => $listingCategories
    ]); ?>
</script>

<script src="../Js/dashboard.js" type="module"></script>
<script src="../Js/site.js" type="module"></script>
</body>
</html>
<?php $conn->close(); ?>
