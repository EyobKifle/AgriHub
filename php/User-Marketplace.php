<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    // Redirect to public marketplace if not logged in
    header('Location: ../HTML/Marketplace.html');
    exit();
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utils.php';

$name = $_SESSION['name'] ?? 'User';
$email = $_SESSION['email'] ?? '';
$userId = (int)($_SESSION['user_id'] ?? 0);
$initial = strtoupper(mb_substr($name, 0, 1));
$currentPage = 'User-Marketplace';
$avatar_url = '';

// Fetch user avatar for header/sidebar
$stmt_avatar = $conn->prepare("SELECT avatar_url FROM users WHERE id = ?");
if ($stmt_avatar) {
    $stmt_avatar->bind_param('i', $userId);
    $stmt_avatar->execute();
    $avatar_url = $stmt_avatar->get_result()->fetch_object()->avatar_url ?? '';
    $stmt_avatar->close();
}


// Fetch categories for the filter sidebar
$categories = [];
$catStmt = $conn->prepare("SELECT id, name, slug FROM categories ORDER BY name");
if ($catStmt) {
    $catStmt->execute();
    $result = $catStmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    $catStmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marketplace - AgriHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/AgriHub/Css/User-Dashboard.css" /> <!-- Add this for consistent dashboard styling -->
    <link rel="stylesheet" href="../Css/User-Marketplace.css" />
</head>

<body>
    <header class="main-header-bar">
        <div class="header-left">
            <button class="hamburger-menu" id="hamburger-menu" aria-label="Toggle Menu">
                <i class="fa-solid fa-bars"></i>
            </button>
        </div>
        <div class="header-center">
            <div class="logo">
                <i class="fa-solid fa-leaf"></i>
                <span>AgriHub</span>
            </div>
        </div>
        <div class="header-right">
            <a href="User-Account.php" class="profile-link" aria-label="User Profile">
                <div class="profile-avatar">
                    <?php if (!empty($avatar_url)): ?><img src="/AgriHub/<?php echo e($avatar_url); ?>" alt="User Avatar"><?php else: ?><?php echo e($initial); ?><?php endif; ?>
                </div>
            </a>
        </div>
    </header>

    <div class="dashboard-container">
        <?php include __DIR__ . '/_sidebar.php'; ?>

        <main class="main-content">

            <div class="marketplace-layout">
                <aside class="marketplace-sidebar">
                    <div class="sidebar-section">
                        <a href="User-Listings.php" class="btn btn-primary">+ List a Product</a>
                    </div>
                    <div class="sidebar-section">
                        <h3>Categories</h3>
                        <ul class="category-list" id="category-list">
                            <li data-slug="" class="selected">All Products</li>
                            <?php foreach ($categories as $cat): ?>
                                <li data-slug="<?php echo htmlspecialchars($cat['slug']); ?>"><?php echo htmlspecialchars($cat['name']); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </aside>

                <div class="products-content">
                    <section>
                        <div class="search-bar">
                            <input id="search-input" type="text" placeholder="Search products..." />
                        </div>
    
                        <div id="products-grid" class="products-grid" aria-live="polite">
                            <!-- Products will be loaded here by JavaScript -->
                        </div>
                        <div id="empty-state" class="empty-state" hidden>
                            <p>No products found. Try a different search or category.</p>
                        </div>
                    </section>
                </div>
            </div>
        </main>
    </div>


    <script type="module" src="../Js/User-Marketplace.js"></script>
    <script type="module" src="../Js/dashboard.js"></script>
</body>

</html>