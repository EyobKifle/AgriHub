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
$initial = !empty($name) ? strtoupper($name[0]) : 'A';

// --- Handle Filters ---
$q = trim($_GET['q'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$categoryFilter = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// --- Fetch Categories for Filter Dropdown ---
$categories = [];
$cat_stmt = $conn->prepare("SELECT id, name FROM categories ORDER BY name");
if ($cat_stmt) {
    $cat_stmt->execute();
    $categories = $cat_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $cat_stmt->close();
}

// --- Build SQL Query for Listings ---
$sql = "
    SELECT 
        p.id, p.title, p.price, p.unit, p.status, p.created_at,
        c.name as category_name,
        u.name as seller_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    JOIN users u ON p.seller_id = u.id
";

$where = [];
$params = [];
$types = '';

if ($q !== '') {
    $where[] = 'p.title LIKE CONCAT("%", ?, "%")';
    $params[] = $q;
    $types .= 's';
}
if ($statusFilter !== '') {
    $where[] = 'p.status = ?';
    $params[] = $statusFilter;
    $types .= 's';
}
if ($categoryFilter > 0) {
    $where[] = 'p.category_id = ?';
    $params[] = $categoryFilter;
    $types .= 'i';
}

if (!empty($where)) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY p.created_at DESC LIMIT 200';

$listings = [];
$stmt = $conn->prepare($sql);
if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $listings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-i18n-key="admin.listingManagement.pageTitle">Listings Management - AgriHub Admin</title>
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
                <li><a href="Admin-Dashboard.php" data-i18n-key="admin.nav.dashboard"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>
                <li><a href="Admin-User-Management.php" data-i18n-key="admin.nav.userManagement"><i class="fa-solid fa-users"></i> User Management</a></li>
                <li><a href="Admin-Listings-Management.php" class="active" data-i18n-key="admin.nav.listingManagement"><i class="fa-solid fa-store"></i> Listing Management</a></li>
                <li><a href="Admin-News-Management.php" data-i18n-key="admin.nav.newsManagement"><i class="fa-solid fa-newspaper"></i> News Management</a></li>
                <li><a href="Admin-Reports-Management.php" data-i18n-key="admin.nav.reports"><i class="fa-solid fa-flag"></i> Reports</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="content-wrapper">
                <div class="main-header">
                    <h1 data-i18n-key="admin.listingManagement.title">Listings Management</h1>
                    <p data-i18n-key="admin.listingManagement.subtitle">Review and manage all product listings on the platform.</p>
                </div>

                <div class="page-controls">
                    <div class="search-filter-group">
                        <form method="GET" action="">
                            <input type="text" name="q" class="search-input" data-i18n-placeholder-key="admin.listingManagement.searchPlaceholder" placeholder="Search by title..." value="<?php echo e($q); ?>">
                            <select name="category" class="filter-select">
                                <option value="" data-i18n-key="admin.listingManagement.filter.allCategories">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo (int)$cat['id']; ?>" <?php echo $categoryFilter === (int)$cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo e($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <select name="status" class="filter-select">
                                <option value="" data-i18n-key="admin.userManagement.filter.allStatuses">All Statuses</option>
                                <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?> data-i18n-key="user.listings.status.active">Active</option>
                                <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?> data-i18n-key="user.listings.status.inactive">Inactive</option>
                                <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?> data-i18n-key="user.listings.status.pending">Pending</option>
                                <option value="sold" <?php echo $statusFilter === 'sold' ? 'selected' : ''; ?> data-i18n-key="user.listings.status.sold">Sold</option>
                            </select>
                            <button type="submit" class="btn btn-primary" data-i18n-key="admin.userManagement.searchButton">Search</button>
                        </form>
                    </div>
                </div>

                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th data-i18n-key="admin.listingManagement.table.id">ID</th>
                                <th data-i18n-key="admin.listingManagement.table.product">Product</th>
                                <th data-i18n-key="admin.listingManagement.table.seller">Seller</th>
                                <th data-i18n-key="admin.listingManagement.table.category">Category</th>
                                <th data-i18n-key="admin.listingManagement.table.price">Price</th>
                                <th data-i18n-key="admin.listingManagement.table.status">Status</th>
                                <th data-i18n-key="admin.listingManagement.table.listedOn">Listed On</th>
                                <th data-i18n-key="admin.listingManagement.table.actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($listings)): ?>
                                <tr><td colspan="8" style="text-align: center;" data-i18n-key="admin.listingManagement.emptyState">No listings found matching your criteria.</td></tr>
                            <?php else: ?>
                                <?php foreach ($listings as $listing): ?>
                                <tr>
                                    <td><?php echo (int)$listing['id']; ?></td>
                                    <td class="user-info">
                                        <div>
                                            <div class="user-name"><?php echo e($listing['title']); ?></div>
                                        </div>
                                    </td>
                                    <td><?php echo e($listing['seller_name']); ?></td>
                                    <td><?php echo e($listing['category_name']); ?></td>
                                    <td>ETB <?php echo number_format((float)$listing['price'], 2); ?> / <?php echo e($listing['unit']); ?></td>
                                    <td><span class="status status-<?php echo e($listing['status']); ?>" data-i18n-key="user.listings.status.<?php echo e($listing['status']); ?>"><?php echo e($listing['status']); ?></span></td>
                                    <td><?php echo date('Y-m-d', strtotime($listing['created_at'])); ?></td>
                                    <td class="action-buttons">
                                        <a href="Admin-product-details.php?id=<?php echo (int)$listing['id']; ?>" data-i18n-title-key="admin.listingManagement.actions.view" title="View & Edit Details" class="btn-icon">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script type="module" src="../Js/dashboard.js"></script>
    <script type="module" src="../Js/site.js"></script>
</body>
</html>
