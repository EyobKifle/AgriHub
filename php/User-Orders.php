<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: Login.html');
    exit();
}
require_once __DIR__ . '/../php/config.php'; // agrihub DB

$userId = (int)$_SESSION['user_id'];
$name = $_SESSION['name'] ?? 'User';
$email = $_SESSION['email'] ?? '';
$initial = strtoupper(mb_substr($name, 0, 1));

$orders = [];
$sql = 'SELECT o.id, o.buyer_id, o.seller_id, o.quantity, o.unit_price, o.total_price, o.status, o.created_at,
               p.title AS product_title,
               ub.name AS buyer_name,
               us.name AS seller_name
        FROM orders o
        JOIN products p ON p.id = o.product_id
        JOIN users ub ON ub.id = o.buyer_id
        JOIN users us ON us.id = o.seller_id
        WHERE o.buyer_id = ? OR o.seller_id = ?
        ORDER BY o.created_at DESC';
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param('ii', $userId, $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $orders[] = $row;
    }
    $stmt->close();
}

include '../HTML/User-Orders.html';
