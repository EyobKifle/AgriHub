<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../HTML/Login.html');
    exit();
}
require_once __DIR__ . '/../php/config.php'; // agrihub DB

$userId = (int)$_SESSION['user_id'];
$name = $_SESSION['name'] ?? 'User';
$email = $_SESSION['email'] ?? '';
$initial = strtoupper(mb_substr($name, 0, 1));

$orders = [];
// Aggregate orders visible to the current user (as buyer or as seller via order_items)
$sql = 'SELECT 
            o.id,
            o.order_code,
            o.total_amount,
            o.status,
            o.created_at,
            COUNT(oi.id) AS item_count
        FROM orders o
        LEFT JOIN order_items oi ON oi.order_id = o.id
        WHERE o.buyer_id = ? OR EXISTS (
            SELECT 1 FROM order_items oi2 WHERE oi2.order_id = o.id AND oi2.seller_id = ?
        )
        GROUP BY o.id
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
