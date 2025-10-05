<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utils.php';

header('Content-Type: application/json');

$productId = (int)($_GET['id'] ?? 0);

if ($productId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid product ID provided.']);
    exit();
}

try {
    // Fetch main product details along with seller info
    $stmt = $conn->prepare("
        SELECT
            p.id, p.title, p.description, p.price, p.unit, p.quantity_available,
            c.name AS category_name,
            u.name AS seller_name,
            up.location AS seller_location,
            up.business_name
        FROM products p
        JOIN categories c ON p.category_id = c.id
        JOIN users u ON p.seller_id = u.id
        LEFT JOIN user_profiles up ON u.id = up.user_id
        WHERE p.id = ? AND p.status = 'active'
    ");
    $stmt->bind_param('i', $productId);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$product) {
        http_response_code(404);
        echo json_encode(['error' => 'Product not found or is no longer available.']);
        exit();
    }

    // Fetch product images
    $stmt = $conn->prepare("SELECT image_url FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, id ASC");
    $stmt->bind_param('i', $productId);
    $stmt->execute();
    $images = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $product['images'] = array_column($images, 'image_url');

    echo json_encode($product);

} catch (Exception $e) {
    http_response_code(500);
    error_log("view-product.php Error: " . $e->getMessage());
    echo json_encode(['error' => 'An unexpected server error occurred.']);
} finally {
    $conn->close();
}