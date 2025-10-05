<?php
session_start();
require_once __DIR__ . '/php/auth_check.php';
require_once __DIR__ . '/config.php';

// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header('Location: User-Dashboard.php');
    exit();
}

$name = $_SESSION['name'] ?? 'Admin';
$email = $_SESSION['email'] ?? '';
$initial = !empty($name) ? strtoupper($name[0]) : '?';

// Filtering and searching
$q = filter_input(INPUT_GET, 'q', FILTER_SANITIZE_STRING) ?? '';
$statusFilter = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_STRING) ?? '';

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
        JOIN users u ON p.user_id = u.id 
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

require_once __DIR__ . '/HTML/Listings-Management.html';
?>