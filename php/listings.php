<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utils.php';

if (!$conn) {
    throw new Exception("Database connection failed");
}

$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

header('Content-Type: application/json');

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            handleGet($conn, $userId);
            break;
        case 'POST':
            handlePost($conn, $userId);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method Not Allowed']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log("listings.php API Error: " . $e->getMessage());
    echo json_encode(['error' => 'An unexpected server error occurred.']);
} finally {
    $conn->close();
}

/**
 * Handles GET requests to fetch initial page data.
 */
function handleGet($conn, $userId) {
    // If a user is logged in, we can fetch their specific data.
    // Otherwise, we fetch all products for the public marketplace.
    if ($userId > 0) {
        $name = $_SESSION['name'] ?? 'User';
        $email = $_SESSION['email'] ?? '';
        $initial = strtoupper(mb_substr($name, 0, 1));

        // Fetch categories
        $categories = [];
        $cat_result = $conn->query('SELECT id, name FROM categories ORDER BY name');
        if ($cat_result) {
            $categories = $cat_result->fetch_all(MYSQLI_ASSOC);
            $cat_result->free();
        }

        // Fetch user's listings
        $listings = [];
        $stmt = $conn->prepare(
           "SELECT p.id, p.title, p.description, p.category_id, c.slug as category_slug, p.price, p.unit, p.quantity_available, p.status, c.name as category_name
            FROM products p
            JOIN categories c ON p.category_id = c.id
            WHERE p.seller_id = ?
            ORDER BY p.created_at DESC"
        );
        if ($stmt) {
            $stmt->bind_param('i', $userId);
            if ($stmt->execute()) {
                $listings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            }
            $stmt->close();
        }

        echo json_encode([
            'user' => ['name' => $name, 'email' => $email, 'initial' => $initial],
            'categories' => $categories,
            'products' => $listings, 
        ]);
    } else {
        // Public view: Fetch all active products
        $products = fetchAllProducts($conn);
        echo json_encode(['products' => $products]);
    }
}

/**
 * Handles POST requests to create, update, or delete listings.
 */
function handlePost($conn, $userId) {
    $action = $_POST['action'] ?? '';

    if ($userId === 0) {
        http_response_code(401);
        echo json_encode(['error' => 'You must be logged in to perform this action.']);
        return;
    }

    switch ($action) {
        case 'save_listing':
            saveListing($conn, $userId);
            break;
        case 'delete_listing':
            deleteListing($conn, $userId);
            break;
        case 'mark_as_sold':
            markAsSold($conn, $userId);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action specified.']);
            break;
    }
}

function sendJson($data) {
    // A small helper to ensure content type is set.
    echo json_encode($data);
}

/**
 * Creates or updates a product listing.
 */
function saveListing($conn, $userId) {
    $productId = (int)($_POST['product_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $unit = trim($_POST['unit'] ?? '');
    $quantity = (float)($_POST['quantity_available'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    // Status is only relevant for updates, defaults to 'active' on create
    $status = trim($_POST['status'] ?? 'active');

    if (empty($title) || $categoryId <= 0 || $price <= 0 || empty($unit) || $quantity < 0 || empty($status)) {
        http_response_code(400);
        echo json_encode(['error' => 'Please fill in all required fields.']);
        return;
    }

    if ($productId > 0) { // Update existing listing
        $stmt = $conn->prepare(
            "UPDATE products SET title=?, description=?, category_id=?, price=?, unit=?, quantity_available=?, status=? WHERE id=? AND seller_id=?"
        );
        $stmt->bind_param('ssidsdsii', $title, $description, $categoryId, $price, $unit, $quantity, $status, $productId, $userId);
    } else { // Create new listing
        $stmt = $conn->prepare(
            "INSERT INTO products (seller_id, title, description, category_id, price, unit, quantity_available, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $status = 'active'; // Default status for new listings
        $stmt->bind_param('issidsds', $userId, $title, $description, $categoryId, $price, $unit, $quantity, $status);
    }

    if ($stmt->execute()) {
        $newProductId = ($productId > 0) ? $productId : $conn->insert_id;

        // Handle image uploads
        if (isset($_FILES['images'])) {
            // For updates, we might want to decide whether to add to or replace images.
            // Current logic adds new images. We can also delete old ones first if needed.
            // For simplicity, we'll just add the new ones.
            if ($productId > 0) {
                handleImageUploads($conn, $productId, false); // Don't set a new primary if one exists
            } else {
                handleImageUploads($conn, $newProductId, true); // Set first image as primary for new listings
            }
        }

        echo json_encode(['success' => true, 'message' => 'Listing saved successfully.']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: Could not save the listing.']);
    }
    $stmt->close();
}

/**
 * Marks a listing as sold, logs the sale, and updates the product status.
 */
function markAsSold($conn, $userId) {
    $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

    if ($productId <= 0) {
        http_response_code(400);
        sendJson(['error' => 'Invalid Product ID.']);
        return;
    }

    $conn->begin_transaction();

    try {
        // 1. Get product details and verify ownership
        $stmt = $conn->prepare("SELECT title, price FROM products WHERE id = ? AND seller_id = ?");
        $stmt->bind_param('ii', $productId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        $stmt->close();

        if (!$product) {
            throw new Exception('Product not found or you do not have permission.', 404);
        }

        // 2. Insert into completed_sales
        $stmt = $conn->prepare("INSERT INTO completed_sales (seller_id, product_id, product_title, amount) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('iisd', $userId, $productId, $product['title'], $product['price']);
        if (!$stmt->execute()) {
            throw new Exception('Failed to record the sale.');
        }
        $stmt->close();

        // 3. Update product status to 'sold_out'
        $stmt = $conn->prepare("UPDATE products SET status = 'sold_out' WHERE id = ?");
        $stmt->bind_param('i', $productId);
        if (!$stmt->execute()) {
            throw new Exception('Failed to update product status.');
        }
        $stmt->close();

        // If all queries succeeded, commit the transaction
        $conn->commit();
        sendJson(['success' => true, 'message' => 'Sale recorded and listing marked as sold!']);

    } catch (Exception $e) {
        $conn->rollback(); // Rollback on any error
        $code = $e->getCode() ?: 500;
        http_response_code($code);
        error_log("markAsSold Error: " . $e->getMessage());
        sendJson(['error' => $e->getMessage()]);
    }
}

/**
 * Fetches all active products for the public marketplace view.
 */
function fetchAllProducts($conn) {
    $products = [];
    $sql = "SELECT p.id, p.title, p.description, p.category_id, c.slug as category_slug, p.price, p.unit, p.quantity_available, p.status, c.name as category_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.status = 'active'
            ORDER BY p.created_at DESC";
    $result = $conn->query($sql);
    if ($result) {
        $products = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();
    }
    return $products;
}

/**
 * Handles uploading and saving product images.
 */
function handleImageUploads($conn, $productId, $setFirstAsPrimary) {
    $uploadDir = __DIR__ . '/../uploads/products/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $imageCount = count($_FILES['images']['name']);
    $stmt = $conn->prepare("INSERT INTO product_images (product_id, image_url, is_primary) VALUES (?, ?, ?)");

    // Check if a primary image already exists for this product
    $hasPrimary = false;
    if (!$setFirstAsPrimary) {
        $checkStmt = $conn->prepare("SELECT 1 FROM product_images WHERE product_id = ? AND is_primary = 1");
        $checkStmt->bind_param('i', $productId);
        $checkStmt->execute();
        $hasPrimary = $checkStmt->get_result()->num_rows > 0;
        $checkStmt->close();
    }

    for ($i = 0; $i < $imageCount; $i++) {
        if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES['images']['tmp_name'][$i];
            $imageName = uniqid() . '_' . basename($_FILES['images']['name'][$i]);
            $targetPath = $uploadDir . $imageName;
            if (move_uploaded_file($tmpName, $targetPath)) {
                $imageUrl = 'uploads/products/' . $imageName;
                $isPrimary = ($setFirstAsPrimary && $i === 0 && !$hasPrimary);
                $stmt->bind_param('isi', $productId, $imageUrl, $isPrimary);
                $stmt->execute();
            }
        }
    }
    $stmt->close();
}

/**
 * Deletes a product listing.
 */
function deleteListing($conn, $userId) {
    // Deletion requests might come as JSON, so we read the input stream
    $input = json_decode(file_get_contents('php://input'), true);
    $productId = 0;
    if (isset($_POST['product_id'])) {
        $productId = (int)$_POST['product_id'];
    } elseif (isset($input['product_id'])) {
        $productId = (int)$input['product_id'];
    }

    if ($productId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid product ID.']);
        return;
    }

    $stmt = $conn->prepare("DELETE FROM products WHERE id = ? AND seller_id = ?");
    $stmt->bind_param('ii', $productId, $userId);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Listing deleted successfully.']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Listing not found or you do not have permission to delete it.']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: Could not delete the listing.']);
    }
    $stmt->close();
}