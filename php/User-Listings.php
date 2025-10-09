<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../HTML/Login.html');
    exit();
}
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utils.php';

$userId = (int)$_SESSION['user_id'];
$name = $_SESSION['name'] ?? 'User';
$email = $_SESSION['email'] ?? '';
$initial = strtoupper(mb_substr($name, 0, 1));
$avatar_url = '';

// Fetch user avatar for header/sidebar
$stmt_avatar = $conn->prepare("SELECT avatar_url FROM users WHERE id = ?");
if ($stmt_avatar) {
    $stmt_avatar->bind_param('i', $userId);
    $stmt_avatar->execute();
    $avatar_url = $stmt_avatar->get_result()->fetch_object()->avatar_url ?? '';
    $stmt_avatar->close();
}
$currentPage = 'User-Listings';

// Fetch categories for the form dropdown
$categories = [];
$cat_stmt = $conn->prepare("SELECT id, name FROM categories ORDER BY name");
if ($cat_stmt) {
    $cat_stmt->execute();
    $categories = $cat_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $cat_stmt->close();
}

// Fetch user's listings
$listings = [];
$list_stmt = $conn->prepare(
   "SELECT p.id, p.title, p.price, p.unit, p.status, c.name as category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.seller_id = ?
    ORDER BY p.created_at DESC"
);
$list_stmt->bind_param('i', $userId);
$list_stmt->execute();
$listings = $list_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$list_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-i18n-key="user.listings.pageTitle">My Listings - AgriHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../Css/User-Dashboard.css">
</head>
<body>
    <!-- Full-width Header -->
    <header class="main-header-bar">
        <div class="header-left">
            <button class="hamburger-menu" id="hamburger-menu" aria-label="Toggle Menu">
                <i class="fa-solid fa-bars"></i>
            </button>
        </div>
        <div class="header-center">
            <div class="logo">
                <i class="fa-solid fa-leaf"></i>
                <span data-i18n-key="brand.name">AgriHub</span>
            </div>
        </div>
        <div class="header-right">
            <a href="User-Profile.php" class="profile-link" aria-label="User Profile">
                <div class="profile-avatar">
                    <?php if (!empty($avatar_url)): ?>
                        <img src="../<?php echo e($avatar_url); ?>" alt="User Avatar">
                    <?php else: echo e($initial); endif; ?>
                </div>
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
                <h3 class="card-title" id="form-title" data-i18n-key="user.listings.modal.title">New Listing</h3>
                <form id="listing-form" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="save_listing" />
                    <input type="hidden" name="product_id" id="product_id" value="0" />
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="form-title-input" data-i18n-key="user.listings.modal.name">Title</label>
                            <input type="text" id="form-title-input" name="title" required />
                        </div>
                        <div class="form-group">
                            <label for="form-category-id" data-i18n-key="user.listings.modal.category">Category</label>
                            <select id="form-category-id" name="category_id" required>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo (int)$cat['id']; ?>"><?php echo e($cat['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="form-description" data-i18n-key="user.listings.modal.description">Description</label>
                        <textarea id="form-description" name="description" rows="3" data-i18n-placeholder-key="user.listings.modal.descriptionPlaceholder" placeholder="Describe your product..."></textarea>
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="form-price" data-i18n-key="user.listings.modal.price">Price</label>
                            <input type="number" step="0.01" id="form-price" name="price" required />
                        </div>
                        <div class="form-group">
                            <label for="form-unit" data-i18n-key="user.listings.modal.unit">Unit</label>
                            <input type="text" id="form-unit" name="unit" data-i18n-placeholder-key="user.listings.modal.unitPlaceholder" placeholder="e.g., kg, quintal, bag" required />
                        </div>
                        <div class="form-group">
                            <label for="form-quantity" data-i18n-key="user.listings.modal.quantity">Quantity Available</label>
                            <input type="number" step="0.01" id="form-quantity" name="quantity_available" required />
                        </div>
                    </div>
                    <div class="form-group" id="status-form-group" style="display: none;">
                        <label for="form-status" data-i18n-key="user.listings.table.status">Status</label>
                        <select id="form-status" name="status">
                            <option value="active" data-i18n-key="user.listings.status.active">Active</option>
                            <option value="inactive" data-i18n-key="user.listings.status.inactive">Inactive</option>
                            <option value="sold" data-i18n-key="user.listings.status.sold">Sold</option>
                        </select>
                        <p class="form-text" data-i18n-key="user.listings.modal.statusHelp">Set the current status of your listing.</p>
                    </div>
                    <div class="form-group">
                        <label for="images">Product Images (first image will be primary)</label>
                        <input type="file" id="images" name="images[]" multiple accept="image/*" >
                        <p class="form-text">You can select multiple images. For updates, new images will be added to the existing ones.</p>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" id="cancel-edit-btn" style="display: none;" data-i18n-key="common.cancel">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="form-submit-btn" data-i18n-key="user.discussions.actions.create">Create</button>
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
                        <?php if (empty($listings)): ?>
                            <tr><td colspan="5" style="text-align:center; opacity:.8;" data-i18n-key="user.listings.empty">You have no listings yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($listings as $item): ?>
                                <?php $priceFormatted = number_format((float)$item['price'], 2) . ' / ' . e($item['unit']); ?>
                                <tr data-id="<?php echo (int)$item['id']; ?>">
                                    <td><?php echo e($item['title']); ?></td>
                                    <td><?php echo e($item['category_name']); ?></td>
                                    <td><?php echo $priceFormatted; ?></td>
                                    <td><span class="status status-<?php echo e(strtolower($item['status'])); ?>" data-i18n-key="user.listings.status.<?php echo e(strtolower($item['status'])); ?>"><?php echo e(ucfirst($item['status'])); ?></span></td>
                                    <td class="action-buttons">
                                        <button class="edit-btn" data-i18n-title-key="user.listings.actions.edit" title="Edit Listing"><i class="fa-solid fa-pen"></i></button>
                                        <button class="delete-btn" data-i18n-title-key="user.listings.actions.delete" title="Delete Listing"><i class="fa-solid fa-trash"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script type="module" src="../Js/dashboard.js"></script>
    <script type="module" src="../Js/User-Listings.js"></script>
</body>
</html>