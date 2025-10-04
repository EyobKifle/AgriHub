<?php
session_start();
require_once __DIR__ . '/config.php';

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = null;
$images = [];
$seller = null;
$error = '';

if ($productId <= 0) {
    $error = 'Invalid product ID specified.';
} else {
    // Fetch product details
    $stmt = $conn->prepare(
        "SELECT p.*, c.name as category_name, u.name as seller_name, u.location as seller_location, up.business_name
         FROM products p
         JOIN categories c ON p.category_id = c.id
         JOIN users u ON p.seller_id = u.id
         LEFT JOIN user_profiles up ON u.id = up.user_id
         WHERE p.id = ? AND p.status = 'active'"
    );
    if ($stmt) {
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        $stmt->close();
    }

    if (!$product) {
        $error = 'Product not found or is no longer available.';
    } else {
        // Fetch all product images, primary first
        $stmt = $conn->prepare("SELECT image_url, is_primary FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, id ASC");
        if ($stmt) {
            $stmt->bind_param('i', $productId);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $images[] = $row['image_url'];
            }
            $stmt->close();
        }
    }
}

function e($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
?>

<?php include 'HTML/view-product.html'; ?>
