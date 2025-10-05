<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    // If not logged in, redirect to the public HTML version
    header('Location: ../HTML/Marketplace.html');
    exit();
}

$name = $_SESSION['name'] ?? 'User';
$email = $_SESSION['email'] ?? '';
$initial = strtoupper(mb_substr($name, 0, 1));
$currentPage = 'User-Marketplace';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marketplace - AgriHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../Css/User-Dashboard.css">
    <!-- Marketplace Specific Styles -->
    <link rel="stylesheet" href="../Css/marketplace.css" />
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
                <div class="profile-avatar"><?php echo htmlspecialchars($initial, ENT_QUOTES, 'UTF-8'); ?></div>
            </a>
        </div>
    </header>
    
    <div class="dashboard-container">
        <?php include __DIR__ . '/_sidebar.php'; ?>

        <main class="main-content">
            <!-- Original Marketplace.html content starts here -->
            <div class="marketplace-layout" style="padding:0; border:none; background:none;">
                <!-- Sidebar: Filters -->
                <aside class="marketplace-sidebar">
                    <div class="sidebar-section">
                        <a href="User-Listings.php" class="btn btn-primary full-width" data-i18n-key="market.sidebar.listProduct">+ List a Product</a>
                    </div>
                    <div class="sidebar-section">
                        <h3 data-i18n-key="market.sidebar.categories">Categories</h3>
                        <ul class="category-list" id="category-list"></ul>
                    </div>
                    <div class="sidebar-section">
                        <h3 data-i18n-key="market.sidebar.priceRange">Price Range (ETB)</h3>
                        <div class="price-range">
                            <input id="price-min" type="number" placeholder="Min" min="0" />
                            <input id="price-max" type="number" placeholder="Max" min="0" />
                        </div>
                        <button id="price-apply" class="btn btn-secondary full-width" data-i18n-key="market.sidebar.applyFilter">Apply Filter</button>
                    </div>
                </aside>

                <!-- Main Content -->
                <section class="main-content" style="padding:0; border:none; background:none;">
                    <div class="search-bar">
                        <input id="search-input" type="text" placeholder="Search products..." data-i18n-placeholder-key="market.search.placeholder" />
                        <select id="sort-select" aria-label="Sort products">
                            <option value="latest" data-i18n-key="market.sort.latest">Sort by: Latest</option>
                            <option value="price-asc" data-i18n-key="market.sort.priceAsc">Sort by: Price Low to High</option>
                            <option value="price-desc" data-i18n-key="market.sort.priceDesc">Sort by: Price High to Low</option>
                        </select>
                    </div>

                    <div id="products-grid" class="products-grid" aria-live="polite"></div>
                    <div id="empty-state" class="empty-state" hidden>
                        <i class="fa-regular fa-face-frown"></i>
                        <p data-i18n-key="market.emptyState">No products match your filters. Try adjusting your search or category.</p>
                    </div>
                </section>
            </div>
            <!-- Original Marketplace.html content ends here -->
        </main>
    </div>

    <script type="module" src="../Js/dashboard.js"></script>
</body>
</html>