<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../HTML/Login.html');
    exit();
}
require_once __DIR__ . '/utils.php';

$name = $_SESSION['name'] ?? 'User';
$email = $_SESSION['email'] ?? '';
$initial = strtoupper(mb_substr($name, 0, 1));
$currentPage = 'User-Listings';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Listings - AgriHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../Css/User-Dashboard.css">
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
            <a href="User-Account.php" class="profile-link" aria-label="User Account">
                <div class="profile-avatar" id="user-initial-avatar"><?php echo e($initial); ?></div>
            </a>
        </div>
    </header>

    <div class="dashboard-container">
        <?php include __DIR__ . '/_sidebar.php'; ?>

        <main class="main-content">
            <div class="main-header">
                <h1 data-i18n-key="user.listings.title">My Listings</h1>
                <p data-i18n-key="user.listings.subtitle">Manage the products you are selling on the marketplace.</p>
            </div>

            <div class="page-controls">
                <button type="button" id="create-listing-btn" class="btn btn-primary" data-i18n-key="user.listings.add"><i class="fa-solid fa-plus"></i> Add New Listing</button>
                <span id="form-status-message" style="margin-left:12px;"></span>
            </div>

            <div class="card" id="create-listing-card" style="display: none;">
                <h3 class="card-title" id="form-title">New Listing</h3>
                <form id="listing-form" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="save_listing">
                    <input type="hidden" name="product_id" id="product_id" value="0">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="form-title-input">Title</label>
                            <input type="text" id="form-title-input" name="title" required>
                        </div>
                        <div class="form-group">
                            <label for="form-category-id">Category</label>
                            <select id="form-category-id" name="category_id" required>
                                <!-- Options will be populated by JS -->
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="form-description">Description</label>
                        <textarea id="form-description" name="description" rows="3" placeholder="Describe your product..."></textarea>
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="form-price">Price</label>
                            <input type="number" step="0.01" id="form-price" name="price" required>
                        </div>
                        <div class="form-group">
                            <label for="form-unit">Unit</label>
                            <input type="text" id="form-unit" name="unit" placeholder="e.g., kg, quintal, bag" required>
                        </div>
                        <div class="form-group">
                            <label for="form-quantity">Quantity Available</label>
                            <input type="number" step="0.01" id="form-quantity" name="quantity_available" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="images">Product Images (first image will be primary)</label>
                        <input type="file" id="images" name="images[]" multiple accept="image/*">
                        <p class="form-text">You can select multiple images. For updates, new images will be added to the existing ones.</p>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" id="cancel-edit-btn" style="display: none;">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="form-submit-btn">Create</button>
                    </div>
                </form>
            </div>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th data-i18n-key="user.listings.table.product">Product</th>
                            <th data-i18n-key="user.listings.table.category">Category</th>
                            <th data-i18n-key="user.listings.table.price">Price</th>
                            <th data-i18n-key="user.listings.table.status">Status</th>
                            <th data-i18n-key="user.listings.table.actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="listings-table-body">
                        <!-- Listing rows will be populated by JS -->
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script type="module" src="../Js/dashboard.js"></script>
    <script type="module" src="../Js/User-Listings.js"></script>
</body>
</html>