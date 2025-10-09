<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../HTML/login.html');
    exit();
}
require_once __DIR__ . '/config.php';

// Only allow admins (role assigned at login)
$role = $_SESSION['role'] ?? 'user';
if ($role !== 'admin') {
    http_response_code(403);
    echo 'Forbidden: Admins only.';
    exit();
}

$name = $_SESSION['name'] ?? 'Admin';
$email = $_SESSION['email'] ?? '';
$initial = strtoupper(mb_substr($name, 0, 1));

// Filters
$q = trim($_GET['q'] ?? '');
$roleFilter = trim($_GET['role'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$where = [];
$params = [];
$types = '';

if ($q !== '') {
    $where[] = '(u.name LIKE CONCAT("%", ?, "%") OR u.email LIKE CONCAT("%", ?, "%"))';
    $params[] = $q; $params[] = $q; $types .= 'ss';
}
if ($roleFilter !== '') {
    $where[] = 'u.role = ?';
    $params[] = $roleFilter; $types .= 's';
}
if ($statusFilter !== '') {
    $where[] = 'u.status = ?';
    $params[] = $statusFilter; $types .= 's';
}

$sql = 'SELECT u.id, u.name, u.email, u.role, u.status, u.created_at FROM users u';
if (!empty($where)) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY u.created_at DESC LIMIT 200';

$rows = [];
$stmt = $conn->prepare($sql);
if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $rows[] = $r;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-i18n-key="admin.userManagement.pageTitle">AgriHub - Admin User Management</title>
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
                <li><a href="Admin-User-Management.php" class="active" data-i18n-key="admin.nav.userManagement"><i class="fa-solid fa-users"></i> User Management</a></li>
                <li><a href="Admin-Listings-Management.php" data-i18n-key="admin.nav.listingManagement"><i class="fa-solid fa-store"></i> Listing Management</a></li>
                <li><a href="Admin-News-Management.php" data-i18n-key="admin.nav.newsManagement"><i class="fa-solid fa-newspaper"></i> News Management</a></li>
                <li><a href="Admin-Reports-Management.php" data-i18n-key="admin.nav.reports"><i class="fa-solid fa-flag"></i> Reports</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="content-wrapper">
                <div class="main-header">
                    <h1 data-i18n-key="admin.userManagement.title">User Management</h1>
                    <p data-i18n-key="admin.userManagement.subtitle">Manage users on the platform.</p>
                </div>

                <div class="page-controls">
                    <div class="search-filter-group">
                        <form method="GET" action="">
                            <input type="text" name="q" class="search-input" data-i18n-placeholder-key="admin.userManagement.searchPlaceholder" placeholder="Search users..." value="<?php echo htmlspecialchars($q); ?>">
                            <select name="role" class="filter-select">
                                <option value="" data-i18n-key="admin.userManagement.filter.allRoles">All Roles</option>
                                <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?> data-i18n-key="admin.userManagement.role.admin">Admin</option>
                                <option value="user" <?php echo $roleFilter === 'user' ? 'selected' : ''; ?> data-i18n-key="admin.userManagement.role.user">User</option>
                            </select>
                            <select name="status" class="filter-select">
                                <option value="" data-i18n-key="admin.userManagement.filter.allStatuses">All Statuses</option>
                                <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?> data-i18n-key="admin.userManagement.status.active">Active</option>
                                <option value="banned" <?php echo $statusFilter === 'banned' ? 'selected' : ''; ?> data-i18n-key="admin.userManagement.status.banned">Banned</option>
                            </select>
                            <button type="submit" class="btn btn-primary" data-i18n-key="admin.userManagement.searchButton">Search</button>
                        </form>
                    </div>
                </div>

                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th data-i18n-key="admin.userManagement.table.id">ID</th>
                                <th data-i18n-key="admin.userManagement.table.user">User</th>
                                <th data-i18n-key="admin.userManagement.table.email">Email</th>
                                <th data-i18n-key="admin.userManagement.table.role">Role</th>
                                <th data-i18n-key="admin.userManagement.table.status">Status</th>
                                <th data-i18n-key="admin.userManagement.table.joined">Joined</th>
                                <th data-i18n-key="admin.userManagement.table.actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['id']); ?></td>
                                <td class="user-info">
                                    <div class="user-avatar" style="background-color: #1E4620; color: white;"><?php echo htmlspecialchars(strtoupper($user['name'][0])); ?></div>
                                    <div>
                                        <div class="user-name"><?php echo htmlspecialchars($user['name']); ?></div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td data-i18n-key="admin.userManagement.role.<?php echo htmlspecialchars($user['role']); ?>"><?php echo htmlspecialchars(ucfirst($user['role'])); ?></td>
                                <td><span class="status status-<?php echo htmlspecialchars($user['status']); ?>" data-i18n-key="admin.userManagement.status.<?php echo htmlspecialchars($user['status']); ?>"><?php echo htmlspecialchars(ucfirst($user['status'])); ?></span></td>
                                <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($user['created_at']))); ?></td>
                                <td class="action-buttons">
                                    <?php if ($user['status'] === 'active'): ?>
                                    <button data-i18n-title-key="admin.userManagement.actions.ban" title="Ban User"><i class="fa-solid fa-ban"></i></button>
                                    <?php else: ?>
                                    <button data-i18n-title-key="admin.userManagement.actions.unban" title="Unban User"><i class="fa-solid fa-check"></i></button>
                                    <?php endif; ?>
                                    <button data-i18n-title-key="admin.userManagement.actions.edit" title="Edit User"><i class="fa-solid fa-edit"></i></button>
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
