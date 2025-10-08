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
    <title>Listings Management - AgriHub Admin</title>
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
                <div class="profile-avatar"><?php echo e($initial); ?></div>
            </a>
        </div>
    </header>

    <div class="dashboard-container">
        <aside class="sidebar" id="sidebar">
            <ul class="sidebar-nav">
                <li><a href="Admin-Dashboard.php"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>
                <li><a href="Admin-User-Management.php"><i class="fa-solid fa-users"></i> User Management</a></li>
                <li><a href="Admin-Listings-Management.php" class="active"><i class="fa-solid fa-store"></i> Listing Management</a></li>
                <li><a href="Admin-News-Management.php"><i class="fa-solid fa-newspaper"></i> News Management</a></li>
                <li><a href="Admin-Reports-Management.php"><i class="fa-solid fa-flag"></i> Reports</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="content-wrapper">
                <div class="main-header">
                    <h1>Listings Management</h1>
                    <p>Review and manage all product listings on the platform.</p>
                </div>

                <div class="page-controls">
                    <div class="search-filter-group">
                        <form method="GET" action="">
                            <input type="text" name="q" class="search-input" placeholder="Search by title..." value="<?php echo e($q); ?>">
                            <select name="category" class="filter-select">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo (int)$cat['id']; ?>" <?php echo $categoryFilter === (int)$cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo e($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <select name="status" class="filter-select">
                                <option value="">All Statuses</option>
                                <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="sold" <?php echo $statusFilter === 'sold' ? 'selected' : ''; ?>>Sold</option>
                            </select>
                            <button type="submit" class="btn btn-primary">Search</button>
                        </form>
                    </div>
                </div>

                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Product</th>
                                <th>Seller</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Listed On</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($listings)): ?>
                                <tr><td colspan="8" style="text-align: center;">No listings found matching your criteria.</td></tr>
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
                                    <td><span class="status status-<?php echo e($listing['status']); ?>"><?php echo e($listing['status']); ?></span></td>
                                    <td><?php echo date('Y-m-d', strtotime($listing['created_at'])); ?></td>
                                    <td class="action-buttons">
                                        <a href="Admin-product-details.php?id=<?php echo (int)$listing['id']; ?>" title="View & Edit Details" class="btn-icon">
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
</body>
</html>
