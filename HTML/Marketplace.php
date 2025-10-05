<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    // If not logged in, redirect to the public HTML version
    header('Location: Marketplace.html');
    exit();
}

$name = $_SESSION['name'] ?? 'User';
$email = $_SESSION['email'] ?? '';
$initial = strtoupper(mb_substr($name, 0, 1));
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
            <a href="User-Profile.php" class="profile-link" aria-label="User Profile">
                <div class="profile-avatar"><?php echo htmlspecialchars($initial, ENT_QUOTES, 'UTF-8'); ?></div>
            </a>
        </div>
    </header>
    
    <div class="dashboard-container">
        <aside class="sidebar" id="sidebar">
            <ul class="sidebar-nav">
                <li><a href="User-Dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>
                <li><a href="User-Profile.php"><i class="fa-solid fa-user"></i> My Profile</a></li>
                <li><a href="User-Listings.php"><i class="fa-solid fa-list-check"></i> My Listings</a></li>
                <li><a href="User-Orders.php"><i class="fa-solid fa-receipt"></i> Order History</a></li>
                <li><a href="User-Messages.php"><i class="fa-solid fa-envelope"></i> Messages</a></li>
                <li><a href="User-Discussions.php"><i class="fa-solid fa-comments"></i> My Discussions</a></li>
                <li><a href="User-Settings.php"><i class="fa-solid fa-gear"></i> Settings</a></li>
                <hr>
                <li><a href="Marketplace.php" class="active" data-i18n-key="header.nav.marketplace"><i class="fa-solid fa-store"></i> Marketplace</a></li>
                <li><a href="News.php" data-i18n-key="header.nav.news"><i class="fa-regular fa-newspaper"></i> News</a></li>
                <li><a href="Community.php" data-i18n-key="header.nav.community"><i class="fa-solid fa-users"></i> Community</a></li>
            </ul>
             <div class="sidebar-footer">
                <div class="profile-dropdown">
                    <div>
                        <div class="profile-name"><?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="profile-email" style="opacity:.8; font-size:12px;"><?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?></div>
                        <small><a href="../php/auth.php?action=logout" style="color:inherit; text-decoration:none;" data-i18n-key="user.nav.logout">Logout</a></small>
                    </div>
                </div>
            </div>
        </aside>

        <main class="main-content">
            <!-- Original Marketplace.html content starts here -->
            <div class="marketplace-layout" style="grid-template-columns: 1fr; gap: 0;">
                <!-- Main Content -->
                <section class="main-content">
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
    <script type="module">
        // Since we are in a dashboard, we can't use site.js which loads headers/footers.
        // We need to manually import and run the marketplace logic.
        import { initializeMarketplace } from '../Js/marketplace.js';
        import { initializeI18n, applyTranslationsToPage } from '../Js/i18n.js';

        async function initPage() {
            await initializeI18n();
            applyTranslationsToPage();
            initializeMarketplace();
        }
        initPage();
    </script>
</body>
</html>