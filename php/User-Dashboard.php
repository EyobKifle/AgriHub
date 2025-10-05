<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../HTML/Login.html');
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

// Sales this month (sum of quantity * unit_price for order_items where user is seller)
$salesThisMonth = 0.0;
if ($stmt = $conn->prepare('SELECT COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS total FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE oi.seller_id = ? AND YEAR(o.created_at) = YEAR(CURDATE()) AND MONTH(o.created_at) = MONTH(CURDATE())')) {
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $salesThisMonth = (float)$row['total'];
    }
    $stmt->close();
}

// Recent activity: combine listings, orders, discussions (limit to 10 items total)
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
if ($stmt = $conn->prepare('SELECT CONCAT("Order #", o.id) AS label, o.created_at AS ts, "order" AS type FROM orders o LEFT JOIN order_items oi ON o.id = oi.order_id WHERE o.buyer_id = ? OR oi.seller_id = ? GROUP BY o.id ORDER BY o.created_at DESC LIMIT 10')) {
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
