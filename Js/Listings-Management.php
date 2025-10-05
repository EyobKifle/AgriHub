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

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

    if ($productId > 0) {
        $newStatus = '';
        if ($action === 'approve') {
            $newStatus = 'active';
        } elseif ($action === 'reject') {
            $newStatus = 'inactive'; // Or a new 'rejected' status if you add it
        }

        if ($newStatus) {
            $stmt = $conn->prepare("UPDATE products SET status = ? WHERE id = ?");
            $stmt->bind_param('si', $newStatus, $productId);
            $stmt->execute();
            $stmt->close();
        } elseif ($action === 'delete') {
            // Note: Deleting products can have cascading effects on orders.
            // A soft delete (setting status to 'deleted') is often safer.
            $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
            $stmt->bind_param('i', $productId);
            $stmt->execute();
            $stmt->close();
        }
    }
    header("Location: " . $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);
    exit();
}

// Filters
$q = trim($_GET['q'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$where = [];
$params = [];
$types = '';

if ($q !== '') {
    $where[] = '(p.title LIKE CONCAT("%", ?, "%") OR u.name LIKE CONCAT("%", ?, "%"))';
    $params[] = $q; $params[] = $q; $types .= 'ss';
}
if ($statusFilter !== '') {
    $where[] = 'p.status = ?';
    $params[] = $statusFilter; $types .= 's';
}

$sql = "
    SELECT
        p.id, p.title, p.price, p.unit, p.status,
        c.name as category_name,
        u.name as seller_name
    FROM products p
    JOIN users u ON p.seller_id = u.id
    JOIN categories c ON p.category_id = c.id
";
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
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $listings[] = $r;
    }
    $stmt->close();
}

include '../HTML/Listings-Management.html';

?>