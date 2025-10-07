<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utils.php';

$product = null;
$images = [];
$error_message = null;

$productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$productId) {
    http_response_code(400);
    $error_message = "Invalid product ID provided.";
} else {
    // Fetch main product details along with seller info
    $stmt = $conn->prepare("
        SELECT
            p.id, p.title, p.description, p.price, p.unit, p.quantity_available, p.created_at,
            c.name AS category_name,
            u.name AS seller_name,
            u.avatar_url AS seller_avatar,
            p.seller_id
        FROM products p
        JOIN categories c ON p.category_id = c.id
        JOIN users u ON p.seller_id = u.id
        WHERE p.id = ? AND p.status = 'active'
    ");
    $stmt->bind_param('i', $productId);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$product) {
        http_response_code(404);
        $error_message = "Product not found or is no longer available.";
    } else {
        // Fetch product images
        $stmt_img = $conn->prepare("SELECT image_url FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, id ASC");
        $stmt_img->bind_param('i', $productId);
        $stmt_img->execute();
        $images = $stmt_img->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_img->close();
    }
}

$name = $_SESSION['name'] ?? 'User';
$initial = strtoupper(mb_substr($name, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $product ? e($product['title']) : 'Product Detail'; ?> - AgriHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/AgriHub/Css/Product-Detail.css" />
</head>
<body>
    <header class="main-header-bar">
        <div class="header-left"><a href="User-Marketplace.php" class="back-link" style="font-size: 1.5rem;"><i class="fa-solid fa-arrow-left"></i></a></div>
        <div class="header-center"><div class="logo"><i class="fa-solid fa-leaf"></i><span>AgriHub</span></div></div>
        <div class="header-right"><a href="User-Account.php" class="profile-link"><div class="profile-avatar"><?php echo e($initial); ?></div></a></div>
    </header>

    <main class="main-content" style="margin-left:0; padding-top: 70px;">
        <?php if ($error_message): ?>
            <div class="error-container">
                <h2>Error</h2>
                <p><?php echo e($error_message); ?></p>
                <a href="User-Marketplace.php" class="btn btn-primary">Back to Marketplace</a>
            </div>
        <?php elseif ($product): ?>
            <div class="product-detail-layout">
                <div class="product-images">
                    <img src="/AgriHub/<?php echo e(!empty($images) ? $images[0]['image_url'] : 'https://placehold.co/600x400?text=No+Image'); ?>" alt="Main product image" class="main-image" id="main-product-image">
                    <div class="product-thumbnails">
                        <?php foreach ($images as $index => $image): ?>
                            <img src="/AgriHub/<?php echo e($image['image_url']); ?>" alt="Thumbnail <?php echo $index + 1; ?>" class="<?php echo $index === 0 ? 'active' : ''; ?>">
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="product-info">
                    <span class="category"><?php echo e($product['category_name']); ?></span>
                    <h1><?php echo e($product['title']); ?></h1>
                    <div class="price">ETB <?php echo number_format($product['price'], 2); ?> / <?php echo e($product['unit']); ?></div>
                    <p><?php echo nl2br(e($product['description'])); ?></p>
                    <div class="seller-info">
                        <h3>Seller Information</h3>
                        <p><strong>Name:</strong> <?php echo e($product['seller_name']); ?></p>
                        <a href="User-Messages.php?user_id=<?php echo $product['seller_id']; ?>" class="btn btn-primary">Contact Seller</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <script src="/AgriHub/Js/product-detail.js"></script>
</body>
</html>