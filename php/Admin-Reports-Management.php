<?php
session_start();
require_once __DIR__ . '/utils.php';
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
$stmt = $conn->prepare("
    SELECT
        r.id,
        r.reason,
        r.details,
        r.status,
        r.created_at,
        r.reported_item_id,
        r.reported_item_type,
        reporter.name AS reporter_name,
        p.title AS product_title,
        d.title AS discussion_title,
        dc.content AS comment_content
    FROM reports AS r
    JOIN users AS reporter ON r.reporter_id = reporter.id
    LEFT JOIN products AS p ON r.reported_item_id = p.id AND r.reported_item_type = 'product'
    LEFT JOIN discussions AS d ON r.reported_item_id = d.id AND r.reported_item_type = 'discussion'
    LEFT JOIN discussion_comments AS dc ON r.reported_item_id = dc.id AND r.reported_item_type = 'comment'
    ORDER BY r.created_at DESC
");
$stmt->execute();
$reports = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Helper function to generate a link to the reported content
function get_reported_item_link($report) {
    $id = htmlspecialchars($report['reported_item_id']);
    switch ($report['reported_item_type']) {
        case 'discussion': return "../php/discussion.php?id={$id}";
        default: return '#'; // Add links for other types like products or comments
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-i18n-key="admin.reportsManagement.pageTitle">AgriHub - Admin Reports Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../Css/Admin-Reports.css">
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

                <div class="reports-container">
                    <?php foreach ($reports as $report): ?>
                        <div class="report-card">
                            <div class="report-card-header">
                                <h3>
                                    <i class="fa-solid fa-flag"></i>
                                    Report #<?php echo htmlspecialchars($report['id']); ?>: <?php echo htmlspecialchars(ucfirst($report['reason'])); ?>
                                </h3>
                                <span class="status status-<?php echo htmlspecialchars($report['status']); ?>"><?php echo htmlspecialchars($report['status']); ?></span>
                            </div>
                            <div class="report-card-body">
                                <div class="report-detail">
                                    <strong>Reported Item</strong>
                                    <span>
                                        <?php echo htmlspecialchars(ucfirst($report['reported_item_type'])); ?> #<?php echo htmlspecialchars($report['reported_item_id']); ?>
                                        <a href="<?php echo get_reported_item_link($report); ?>" target="_blank" title="View Item">
                                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        </a>
                                    </span>
                                    <div class="reported-content-preview">
                                        <?php echo htmlspecialchars($report['discussion_title'] ?? $report['product_title'] ?? $report['comment_content'] ?? 'N/A'); ?>
                                    </div>
                                </div>
                                <div class="report-detail">
                                    <strong>Reporter</strong>
                                    <span><?php echo htmlspecialchars($report['reporter_name']); ?></span>
                                </div>
                                <div class="report-detail">
                                    <strong>Date</strong>
                                    <span><?php echo htmlspecialchars(time_ago($report['created_at'])); ?></span>
                                </div>
                                <div class="report-detail">
                                    <strong>Details from Reporter</strong>
                                    <span><?php echo !empty($report['details']) ? htmlspecialchars($report['details']) : 'No additional details provided.'; ?></span>
                                </div>
                            </div>
                            <div class="report-card-footer">
                                <div class="action-buttons">
                                    <?php if ($report['status'] !== 'resolved'): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                                            <input type="hidden" name="action" value="resolve">
                                            <button type="submit" class="btn-success" title="Mark as Resolved"><i class="fa-solid fa-check"></i> Resolve</button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this report?');">
                                        <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" class="btn-danger" title="Delete Report"><i class="fa-solid fa-trash"></i> Delete</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>

    <script type="module" src="/AgriHub/Js/dashboard.js"></script>
    <script type="module" src="/AgriHub/Js/site.js"></script>
</body>
</html>
<?php
$conn->close();
?>
