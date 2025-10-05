<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'User not authenticated.']);
    exit();
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

$userId = (int)$_SESSION['user_id'];

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
    $name = $_SESSION['name'] ?? 'User';
    $email = $_SESSION['email'] ?? '';
    $initial = strtoupper(mb_substr($name, 0, 1));

    // Fetch categories
    $categories = $conn->query('SELECT id, name FROM categories ORDER BY name')->fetch_all(MYSQLI_ASSOC);

    // Fetch user's listings
    $stmt = $conn->prepare(
       "SELECT p.id, p.title, p.description, p.category_id, p.price, p.unit, p.quantity_available, p.status, c.name as category_name
        FROM products p
        JOIN categories c ON p.category_id = c.id
        WHERE p.seller_id = ?
        ORDER BY p.created_at DESC"
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $listings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode([
        'user' => ['name' => $name, 'email' => $email, 'initial' => $initial],
        'categories' => $categories,
        'listings' => $listings,
    ]);
}

/**
 * Handles POST requests to create, update, or delete listings.
 */
function handlePost($conn, $userId) {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'save_listing':
            saveListing($conn, $userId);
            break;
        case 'delete_listing':
            deleteListing($conn, $userId);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action specified.']);
            break;
    }
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

    if (empty($title) || $categoryId <= 0 || $price <= 0 || empty($unit) || $quantity < 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Please fill in all required fields.']);
        return;
    }

    if ($productId > 0) { // Update existing listing
        $stmt = $conn->prepare(
            "UPDATE products SET title=?, description=?, category_id=?, price=?, unit=?, quantity_available=? WHERE id=? AND seller_id=?"
        );
        $stmt->bind_param('ssidsdii', $title, $description, $categoryId, $price, $unit, $quantity, $productId, $userId);
    } else { // Create new listing
        $stmt = $conn->prepare(
            "INSERT INTO products (seller_id, title, description, category_id, price, unit, quantity_available, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')"
        );
        $stmt->bind_param('issidsd', $userId, $title, $description, $categoryId, $price, $unit, $quantity);
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Listing saved successfully.']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: Could not save the listing.']);
    }
    $stmt->close();
}

/**
 * Deletes a product listing.
 */
function deleteListing($conn, $userId) {
    $productId = (int)($_POST['product_id'] ?? 0);

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