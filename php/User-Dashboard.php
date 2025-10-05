<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: Login.html');
    exit();
}
require_once __DIR__ . '/../php/config.php';
require_once __DIR__ . '/utils.php';

$userId = (int)($_SESSION['user_id'] ?? 0);
$name = isset($_SESSION['name']) ? $_SESSION['name'] : 'User';
$initial = strtoupper(mb_substr($name, 0, 1));

// Active listings count (all listings by the user)
$activeListingsCount = 0;
if ($stmt = $conn->prepare('SELECT COUNT(*) AS cnt FROM products WHERE seller_id = ?')) {
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $activeListingsCount = (int)$row['cnt'];
    }
    $stmt->close();
}

// Unread messages placeholder (no messages table implemented yet)
$unreadMessagesCount = 0;

// Sales this month (sum of total_price for orders where user is seller)
$salesThisMonth = 0.0;
if ($stmt = $conn->prepare('SELECT COALESCE(SUM(total_price),0) AS total FROM orders WHERE seller_id = ? AND YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())')) {
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $salesThisMonth = (float)$row['total'];
    }
    $stmt->close();
}

// Recent activity: combine listings, orders, discussions
$activities = [];
// Listings created by user
if ($stmt = $conn->prepare('SELECT title AS label, created_at AS ts, "listing" AS type FROM products WHERE seller_id = ? ORDER BY created_at DESC LIMIT 10')) {
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) { $activities[] = $r; }
    $stmt->close();
}
// Orders involving user
if ($stmt = $conn->prepare('SELECT CONCAT("Order #", id) AS label, created_at AS ts, "order" AS type FROM orders WHERE seller_id = ? OR buyer_id = ? ORDER BY created_at DESC LIMIT 10')) {
    $stmt->bind_param('ii', $userId, $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) { $activities[] = $r; }
    $stmt->close();
}
// Discussions authored by user
if ($stmt = $conn->prepare('SELECT title AS label, updated_at AS ts, "discussion" AS type FROM discussions WHERE author_id = ? ORDER BY updated_at DESC LIMIT 10')) {
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) { $activities[] = $r; }
    $stmt->close();
}
// Sort and limit
usort($activities, function($a, $b) {
    return strtotime($b['ts']) <=> strtotime($a['ts']);
});
$activities = array_slice($activities, 0, 10);

include '../HTML/User-Dashboard.html';
