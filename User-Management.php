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
$roleFilter = filter_input(INPUT_GET, 'role', FILTER_SANITIZE_STRING) ?? '';
$statusFilter = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_STRING) ?? '';

// Fetch users from DB
$sql = "SELECT id, name, email, role, status, created_at FROM users WHERE 1=1";
$params = [];
$types = '';

if (!empty($q)) {
    $sql .= " AND (name LIKE ? OR email LIKE ?)";
    $searchTerm = "%{$q}%";
    array_push($params, $searchTerm, $searchTerm);
    $types .= 'ss';
}
if (!empty($roleFilter)) {
    $sql .= " AND role = ?";
    $params[] = $roleFilter;
    $types .= 's';
}
if (!empty($statusFilter)) {
    $sql .= " AND status = ?";
    $params[] = $statusFilter;
    $types .= 's';
}
$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$rows = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

require_once __DIR__ . '/HTML/User-Management.html';
?>