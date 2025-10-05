<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: Login.html');
    exit();
}
require_once __DIR__ . '/../php/config.php';

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

include '../HTML/User-Management.html';
