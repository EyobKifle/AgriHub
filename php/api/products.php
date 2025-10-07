<?php
session_start();
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

try {
$sql = "SELECT
            p.id, p.title, p.price, p.unit, p.status,
            c.name AS category_name, c.slug AS category_slug,
            (SELECT image_url FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, id ASC LIMIT 1) AS image_url
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.status='active'
        ORDER BY p.created_at DESC";

    $result = $conn->query($sql);

    $products = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            // Ensure price is a number for JavaScript
            $row['price'] = (float)$row['price'];
            $products[] = $row;
        }
        $result->free();
    }

    echo json_encode(['products' => $products]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch products']);
}
$conn->close();
