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

// Handle actions (resolve, delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['report_id'])) {
    $reportId = (int)$_POST['report_id'];
    $action = $_POST['action'];

    if ($action === 'resolve') {
        $stmt = $conn->prepare("UPDATE reports SET status = 'resolved' WHERE id = ?");
    } elseif ($action === 'delete') {
        $stmt = $conn->prepare("DELETE FROM reports WHERE id = ?");
    }

    if (isset($stmt)) {
        $stmt->bind_param('i', $reportId);
        $stmt->execute();
        $stmt->close();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Fetch reports
$stmt = $conn->prepare("SELECT r.id, r.reason, r.status, r.created_at, u.name as reporter_name FROM reports r JOIN users u ON r.reporter_id = u.id ORDER BY r.created_at DESC");
$stmt->execute();
$reports = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-i18n-key="admin.reportsManagement.pageTitle">AgriHub - Admin Reports Management</title>
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
                <li><a href="Admin-Listings-Management.php" data-i18n-key="admin.nav.listingManagement"><i class="fa-solid fa-store"></i> Listing Management</a></li>
                <li><a href="Admin-News-Management.php" data-i18n-key="admin.nav.newsManagement"><i class="fa-solid fa-newspaper"></i> News Management</a></li>
                <li><a href="Admin-Reports-Management.php" class="active" data-i18n-key="admin.nav.reports"><i class="fa-solid fa-flag"></i> Reports</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="content-wrapper">
                <div class="main-header">
                    <h1 data-i18n-key="admin.reportsManagement.title">Reports Management</h1>
                    <p data-i18n-key="admin.reportsManagement.subtitle">Manage user reports on the platform.</p>
                </div>

                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th data-i18n-key="admin.reportsManagement.table.id">ID</th>
                                <th data-i18n-key="admin.reportsManagement.table.reason">Reason</th>
                                <th data-i18n-key="admin.reportsManagement.table.reporter">Reporter</th>
                                <th data-i18n-key="admin.reportsManagement.table.status">Status</th>
                                <th data-i18n-key="admin.reportsManagement.table.reportedAt">Reported At</th>
                                <th data-i18n-key="admin.reportsManagement.table.actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reports as $report): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($report['id']); ?></td>
                                <td><?php echo htmlspecialchars($report['reason']); ?></td>
                                <td><?php echo htmlspecialchars($report['reporter_name']); ?></td>
                                <td><span class="status status-<?php echo htmlspecialchars($report['status']); ?>" data-i18n-key="admin.reportsManagement.status.<?php echo htmlspecialchars($report['status']); ?>"><?php echo htmlspecialchars($report['status']); ?></span></td>
                                <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($report['created_at']))); ?></td>
                                <td class="action-buttons">
                                    <?php if ($report['status'] !== 'resolved'): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                                        <input type="hidden" name="action" value="resolve">
                                        <button type="submit" data-i18n-title-key="admin.reportsManagement.actions.resolve" title="Resolve"><i class="fa-solid fa-check"></i></button>
                                    </form>
                                    <?php endif; ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" class="btn-danger" data-i18n-title-key="admin.reportsManagement.actions.delete" title="Delete"><i class="fa-solid fa-trash"></i></button>
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
