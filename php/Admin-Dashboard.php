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
$email = $_SESSION['email'] ?? '';
$initial = !empty($name) ? strtoupper($name[0]) : 'A';

// --- Fetch Dashboard Stats ---

// Total Users
$totalUsersResult = $conn->query("SELECT COUNT(*) as count FROM users");
$totalUsers = $totalUsersResult->fetch_assoc()['count'] ?? 0;

// Active Listings
$activeListingsResult = $conn->query("SELECT COUNT(*) as count FROM products WHERE status = 'active'");
$activeListings = $activeListingsResult->fetch_assoc()['count'] ?? 0;

// Pending Reports
$pendingReportsResult = $conn->query("SELECT COUNT(*) as count FROM reports WHERE status = 'open'");
$pendingReports = $pendingReportsResult->fetch_assoc()['count'] ?? 0;

// --- Fetch Recent Items ---

// Recent Users
$recentUsersStmt = $conn->prepare("SELECT id, name, created_at FROM users ORDER BY created_at DESC LIMIT 4");
$recentUsersStmt->execute();
$recentUsers = $recentUsersStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$recentUsersStmt->close();

// Recent Listings
$recentListingsStmt = $conn->prepare("
    SELECT p.id, p.title, u.name as seller_name
    FROM products p
    JOIN users u ON p.user_id = u.id
    ORDER BY p.created_at DESC LIMIT 4
");
$recentListingsStmt->execute();
$recentListings = $recentListingsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$recentListingsStmt->close();

// Recent Reports
$recentReportsStmt = $conn->prepare("
    SELECT r.id, r.reason, u.name as reporter_name
    FROM reports r
    JOIN users u ON r.reporter_id = u.id
    WHERE r.status = 'open'
    ORDER BY r.created_at DESC LIMIT 3
");
$recentReportsStmt->execute();
$recentReports = $recentReportsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$recentReportsStmt->close();

// Render the view
require_once __DIR__ . '/../HTML/Admin-Dashboard.html';