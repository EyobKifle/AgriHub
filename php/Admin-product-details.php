<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utils.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../HTML/login.html');
    exit();
}

$adminName = $_SESSION['name'] ?? 'Admin';
$adminInitial = !empty($adminName) ? strtoupper($adminName[0]) : 'A';

$product = null;
$images = [];
$seller = null;
$error_message = null;

$productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$productId) {
    http_response_code(400);
    $error_message = "Invalid product ID provided.";
} else {
    // Handle POST actions for edit/delete
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        if ($action === 'delete_product') {
            // You might want to delete associated images from the server as well
            $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
            $stmt->bind_param('i', $productId);
            if ($stmt->execute()) {
                header('Location: Admin-Listings-Management.php?deleted=true');
                exit();
            } else {
                $error_message = "Failed to delete product.";
            }
            $stmt->close();
        } elseif ($action === 'update_product') {
            $title = trim($_POST['title'] ?? '');
            $price = (float)($_POST['price'] ?? 0);
            $status = trim($_POST['status'] ?? 'inactive');
            $description = trim($_POST['description'] ?? '');

            $stmt = $conn->prepare("UPDATE products SET title = ?, price = ?, status = ?, description = ? WHERE id = ?");
            $stmt->bind_param('sdssi', $title, $price, $status, $description, $productId);
            if (!$stmt->execute()) {
                $error_message = "Failed to update product.";
            }
            $stmt->close();
            // No redirect, so the page reloads with fresh data below
        }
    }

    // Fetch product details along with seller info
    $stmt = $conn->prepare("
        SELECT
            p.id, p.title, p.description, p.price, p.unit, p.quantity_available, p.created_at, p.status,
            c.name AS category_name,
            u.id AS seller_id, u.name AS seller_name, u.email AS seller_email, u.phone AS seller_phone, u.location AS seller_location, u.avatar_url AS seller_avatar
        FROM products p
        JOIN categories c ON p.category_id = c.id
        JOIN users u ON p.seller_id = u.id
        WHERE p.id = ?
    ");
    $stmt->bind_param('i', $productId);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$product) {
        http_response_code(404);
        $error_message = "Product not found.";
    } else {
        // Fetch product images
        $stmt_img = $conn->prepare("SELECT image_url FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, id ASC");
        $stmt_img->bind_param('i', $productId);
        $stmt_img->execute();
        $images = $stmt_img->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_img->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin: <?php echo $product ? e($product['title']) : 'Product Detail'; ?> - AgriHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../Css/Admin-Product-Detail.css" />
</head>
<body>
    <header class="main-header-bar">
        <div class="header-left"><a href="Admin-Listings-Management.php" class="back-link" style="font-size: 1.5rem;"><i class="fa-solid fa-arrow-left"></i></a></div>
        <div class="header-center"><div class="logo"><i class="fa-solid fa-leaf"></i><span>AgriHub</span></div></div>
        <div class="header-right"><a href="Admin-Settings.php" class="profile-link"><div class="profile-avatar"><?php echo e($adminInitial); ?></div></a></div>
    </header>

    <main class="main-content" style="margin-left:0; padding-top: 70px;">
        <?php if ($error_message): ?>
            <div class="error-container">
                <h2>Error</h2>
                <p><?php echo e($error_message); ?></p>
                <a href="Admin-Listings-Management.php" class="btn btn-primary">Back to Listings</a>
            </div>
        <?php elseif ($product): ?>
            <div class="product-detail-layout admin-detail-layout">
                <!-- Left Column: Product Info & Images -->
                <div class="product-view-panel">
                    <div class="product-images">
                        <img src="../<?php echo e(!empty($images) ? $images[0]['image_url'] : 'https://placehold.co/600x400?text=No+Image'); ?>" alt="Main product image" class="main-image" id="main-product-image">
                        <div class="product-thumbnails">
                            <?php foreach ($images as $index => $image): ?>
                                <img src="../<?php echo e($image['image_url']); ?>" alt="Thumbnail <?php echo $index + 1; ?>" class="<?php echo $index === 0 ? 'active' : ''; ?>">
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="product-info">
                        <span class="category"><?php echo e($product['category_name']); ?></span>
                        <h1><?php echo e($product['title']); ?></h1>
                        <div class="price">ETB <?php echo number_format($product['price'], 2); ?> / <?php echo e($product['unit']); ?></div>
                        <p><?php echo nl2br(e($product['description'])); ?></p>
                    </div>
                </div>

                <!-- Right Column: Admin Actions & Seller Info -->
                <div class="admin-actions-panel">
                    <div class="card">
                        <h3 class="card-title">Seller Information</h3>
                        <div class="seller-info-admin">
                            <p><strong>Name:</strong> <?php echo e($product['seller_name']); ?></p>
                            <p><strong>Email:</strong> <?php echo e($product['seller_email']); ?></p>
                            <p><strong>Phone:</strong> <?php echo e($product['seller_phone'] ?? 'N/A'); ?></p>
                            <p><strong>Location:</strong> <?php echo e($product['seller_location'] ?? 'N/A'); ?></p>
                        </div>
                    </div>

                    <div class="card">
                        <h3 class="card-title">Edit Product</h3>
                        <form method="POST" class="settings-form">
                            <input type="hidden" name="action" value="update_product">
                            <div class="form-group">
                                <label for="title">Title</label>
                                <input type="text" id="title" name="title" value="<?php echo e($product['title']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="price">Price</label>
                                <input type="number" step="0.01" id="price" name="price" value="<?php echo e($product['price']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select id="status" name="status" required>
                                    <option value="active" <?php echo $product['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $product['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    <option value="pending" <?php echo $product['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="sold" <?php echo $product['status'] === 'sold' ? 'selected' : ''; ?>>Sold</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea id="description" name="description" rows="4"><?php echo e($product['description']); ?></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </form>
                    </div>

                    <div class="card danger-zone">
                        <h3 class="card-title">Danger Zone</h3>
                        <form method="POST" onsubmit="return confirm('Are you sure you want to permanently delete this product? This cannot be undone.');">
                            <input type="hidden" name="action" value="delete_product">
                            <button type="submit" class="btn btn-danger">Delete Product</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const mainImage = document.getElementById('main-product-image');
            const thumbnails = document.querySelectorAll('.product-thumbnails img');

            thumbnails.forEach(thumb => {
                thumb.addEventListener('click', function () {
                    // Set main image src
                    mainImage.src = this.src;

                    // Update active class
                    thumbnails.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                });
            });
        });
    </script>
</body>
</html>
