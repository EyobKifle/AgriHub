<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../HTML/Login.html');
    exit();
}

require_once __DIR__ . '/config.php';

$userId = (int)$_SESSION['user_id'];
$name = $_SESSION['name'] ?? 'User';
$email = $_SESSION['email'] ?? '';
$initial = strtoupper(mb_substr($name, 0, 1));

$orders = [];

// Fetch all orders placed by the current user
$stmt = $conn->prepare("
    SELECT
        o.id,
        o.order_code,
        o.created_at,
        o.total_amount,
        o.status,
        (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) as item_count
    FROM
        orders o
    WHERE
        o.buyer_id = ?
    ORDER BY
        o.created_at DESC
");
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
$orders = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

include '../HTML/User-Orders.html';