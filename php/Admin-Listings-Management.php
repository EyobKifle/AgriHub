<?php
session_start();
require_once __DIR__ . '/config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../HTML/login.html');
    exit();
}

$name = $_SESSION['name'] ?? 'Admin';
$email = $_SESSION['email'] ?? '';
$initial = !empty($name) ? strtoupper($name[0]) : 'A';

// Filtering and searching
$q = $_GET['q'] ?? '';
$statusFilter = $_GET['status'] ?? '';

// Handle actions (approve, reject, delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['product_id'])) {
    $productId = (int)$_POST['product_id'];
    $action = $_POST['action'];

    if ($action === 'approve') {
        $stmt = $conn->prepare("UPDATE products SET status = 'active' WHERE id = ?");
    } elseif ($action === 'reject') {
        $stmt = $conn->prepare("UPDATE products SET status = 'inactive' WHERE id = ?");
    } elseif ($action === 'delete') {
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    }

    if (isset($stmt)) {
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        $stmt->close();
        header("Location: " . $_SERVER['PHP_SELF']); // Refresh to see changes
        exit();
    }
}

// Fetch listings from DB
$sql = "SELECT p.id, p.title, p.price, p.unit, p.status, u.name as seller_name, c.name as category_name
        FROM products p
        JOIN users u ON p.seller_id = u.id
        JOIN categories c ON p.category_id = c.id
        WHERE 1=1";

$params = [];
$types = '';

if (!empty($q)) {
    $sql .= " AND (p.title LIKE ? OR u.name LIKE ?)";
    $searchTerm = "%{$q}%";
    array_push($params, $searchTerm, $searchTerm);
    $types .= 'ss';
}
if (!empty($statusFilter)) {
    $sql .= " AND p.status = ?";
    $params[] = $statusFilter;
    $types .= 's';
}
$sql .= " ORDER BY p.created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$listings = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgriHub - Admin Listings Management</title>
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
                    <h1>Listings Management</h1>
                    <p>Manage product listings on the platform.</p>
                </div>

                <div class="page-controls">
                    <div class="search-filter-group">
                        <form method="GET" action="">
                            <input type="text" name="q" class="search-input" placeholder="Search listings..." value="<?php echo htmlspecialchars($q); ?>">
                            <select name="status" class="filter-select">
                                <option value="">All Statuses</option>
                                <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
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
                                <th>Title</th>
                                <th>Seller</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($listings as $listing): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($listing['id']); ?></td>
                                <td><?php echo htmlspecialchars($listing['title']); ?></td>
                                <td><?php echo htmlspecialchars($listing['seller_name']); ?></td>
                                <td><?php echo htmlspecialchars($listing['category_name']); ?></td>
                                <td><?php echo htmlspecialchars($listing['price'] . ' ' . $listing['unit']); ?></td>
                                <td><span class="status status-<?php echo htmlspecialchars($listing['status']); ?>"><?php echo htmlspecialchars($listing['status']); ?></span></td>
                                <td class="action-buttons">
                                    <?php if ($listing['status'] !== 'active'): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="product_id" value="<?php echo $listing['id']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" title="Approve"><i class="fa-solid fa-check"></i></button>
                                    </form>
                                    <?php endif; ?>
                                    <?php if ($listing['status'] === 'active'): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="product_id" value="<?php echo $listing['id']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" title="Reject"><i class="fa-solid fa-times"></i></button>
                                    </form>
                                    <?php endif; ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="product_id" value="<?php echo $listing['id']; ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" class="btn-danger" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script src="../Js/dashboard.js" type="module"></script>
</body>
</html>
<?php
$conn->close();
?>
