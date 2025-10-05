<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'moderator'])) {
    header('Location: ../HTML/Login.html');
    exit();
}

require_once __DIR__ . '/config.php';

$name = $_SESSION['name'] ?? 'Admin';
$email = $_SESSION['email'] ?? '';
$initial = strtoupper(mb_substr($name, 0, 1));

// Handle POST actions (Dismiss, Resolve)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $reportId = isset($_POST['report_id']) ? (int)$_POST['report_id'] : 0;
    $adminId = (int)$_SESSION['user_id'];

    if ($reportId > 0 && $adminId > 0) {
        $newStatus = '';
        if ($action === 'dismiss') {
            $newStatus = 'dismissed';
        } elseif ($action === 'resolve') {
            $newStatus = 'resolved';
        }

        if ($newStatus) {
            $stmt = $conn->prepare("UPDATE reports SET status = ?, resolved_by = ? WHERE id = ?");
            $stmt->bind_param('sii', $newStatus, $adminId, $reportId);
            $stmt->execute();
            $stmt->close();
        }
    }
    header('Location: Reports-Management.php'); // Redirect to avoid form resubmission
    exit();
}

// Filters
$statusFilter = trim($_GET['status'] ?? '');

$where = [];
$params = [];
$types = '';

if ($statusFilter !== '') {
    $where[] = 'r.status = ?';
    $params[] = $statusFilter;
    $types .= 's';
}

$sql = "
    SELECT
        r.id,
        r.reported_item_type,
        r.reported_item_id,
        r.reason,
        r.created_at,
        r.status,
        u.name as reporter_name
    FROM reports r
    JOIN users u ON r.reporter_id = u.id
";
if (!empty($where)) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY r.created_at DESC LIMIT 200';

$reports = [];
$stmt = $conn->prepare($sql);
if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $reports[] = $r;
    }
    $stmt->close();
}

include '../HTML/Reports-Management.html';

?>