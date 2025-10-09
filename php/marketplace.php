<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utils.php';

// --- Fetch Categories with Product Counts ---
$categories = [];
$sql_cat = "
    SELECT c.id, c.name, c.slug, COUNT(p.id) as product_count
    FROM categories c
    LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active'
    GROUP BY c.id, c.name, c.slug
    ORDER BY c.name ASC
";
$stmt_cat = $conn->prepare($sql_cat);
if ($stmt_cat) {
    $stmt_cat->execute();
    $categories = $stmt_cat->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_cat->close();
}

// --- Handle Category Filtering ---
$selected_category_slug = isset($_GET['category']) ? trim($_GET['category']) : null;
$selected_category_id = null;

if ($selected_category_slug) {
    foreach ($categories as $cat) {
        if ($cat['slug'] === $selected_category_slug) {
            $selected_category_id = (int)$cat['id'];
            break;
        }
    }
}

// --- Pagination Setup ---
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$products_per_page = 12; // Number of products to show per page
$offset = ($page - 1) * $products_per_page;

// --- Count Total Products for Pagination ---
$sql_count = "SELECT COUNT(p.id) FROM products p WHERE p.status = 'active'";
$count_params = [];
$count_types = '';
if ($selected_category_id) {
    $sql_count .= " AND p.category_id = ?";
    $count_params[] = $selected_category_id;
    $count_types .= 'i';
}
$total_products = 0;
$stmt_count = $conn->prepare($sql_count);
if ($stmt_count) {
    if (!empty($count_params)) {
        $stmt_count->bind_param($count_types, ...$count_params);
    }
    $stmt_count->execute();
    $stmt_count->bind_result($total_products);
    $stmt_count->fetch();
    $stmt_count->close();
}
$total_pages = ceil($total_products / $products_per_page);

// --- Fetch Products ---
$products = [];
$sql_products = "
    SELECT
        p.id, p.title, p.price, p.unit,
        c.name as category_name,
        (SELECT image_url FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, id ASC LIMIT 1) as image_url
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.status = 'active'
";

$params = [];
$types = '';

if ($selected_category_id) {
    $sql_products .= " AND p.category_id = ?";
    $params[] = $selected_category_id;
    $types .= 'i';
}

$sql_products .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
$params[] = $products_per_page;
$params[] = $offset;
$types .= 'ii';

$stmt_products = $conn->prepare($sql_products);
if ($stmt_products) {
    if (!empty($params)) {
        $stmt_products->bind_param($types, ...$params);
    }
    $stmt_products->execute();
    $products = $stmt_products->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_products->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title data-i18n-key="marketplace.pageTitleTag">Marketplace - AgriHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/AgriHub/Css/header.css">
    <link rel="stylesheet" href="/AgriHub/Css/footer.css">
    <link rel="stylesheet" href="/AgriHub/Css/Marketplace.css">
</head>
<body>
    <div id="header-placeholder"></div>

    <main class="page-container">
        <div class="content-wrapper">
            <div class="page-header">
                <h1 data-i18n-key="marketplace.title">Marketplace</h1>
                <p data-i18n-key="marketplace.subtitle">Browse fresh produce, seeds, and equipment from sellers across the nation.</p>
            </div>

            <div class="market-layout">
                <aside class="market-sidebar">
                    <div class="sidebar-section">
                        <h3 data-i18n-key="marketplace.categories">Categories</h3>
                        <ul class="category-list">
                            <li><a href="marketplace.php" class="<?php echo !$selected_category_slug ? 'active' : ''; ?>" data-i18n-key="marketplace.allProducts">All Products</a></li>
                            <?php foreach ($categories as $category): ?>
                                <li>
                                    <a href="marketplace.php?category=<?php echo e($category['slug']); ?>" class="<?php echo $selected_category_slug === $category['slug'] ? 'active' : ''; ?>">
                                        <?php echo e($category['name']); ?>
                                        <span><?php echo (int)$category['product_count']; ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </aside>

                <div class="main-market-content">
                    <div class="product-grid">
                        <?php if (empty($products)): ?>
                            <div class="empty-state">
                                <p data-i18n-key="marketplace.emptyState">No products found in this category.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($products as $product): ?>
                            <div class="product-card">
                                <a href="product-detail.php?id=<?php echo (int)$product['id']; ?>" class="product-card-image-link">
                                    <img src="/AgriHub/<?php echo e(empty($product['image_url']) ? 'https://placehold.co/400x250?text=No+Image' : $product['image_url']); ?>" alt="<?php echo e($product['title']); ?>" class="news-card-image">
                                </a>
                                <div class="product-card-content">
                                    <div class="product-card-category"><?php echo e($product['category_name']); ?></div>
                                    <h3 class="product-card-title">
                                        <a href="product-detail.php?id=<?php echo (int)$product['id']; ?>"><?php echo e($product['title']); ?></a>
                                    </h3>
                                    <div class="product-card-price">
                                        ETB <?php echo number_format($product['price'], 2); ?> <span class="unit">/ <?php echo e($product['unit']); ?></span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Pagination Controls -->
                    <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php
                        // Preserve category filter in pagination links
                        $query_params = [];
                        if ($selected_category_slug) {
                            $query_params['category'] = $selected_category_slug;
                        }
                        ?>

                        <?php if ($page > 1): ?>
                            <a href="?<?php echo http_build_query(array_merge($query_params, ['page' => $page - 1])); ?>" class="pagination-link">&laquo; Prev</a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?<?php echo http_build_query(array_merge($query_params, ['page' => $i])); ?>" class="pagination-link <?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?<?php echo http_build_query(array_merge($query_params, ['page' => $page + 1])); ?>" class="pagination-link">Next &raquo;</a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <div id="footer-placeholder"></div>
    <script type="module" src="/AgriHub/Js/site.js"></script>
</body>
</html>