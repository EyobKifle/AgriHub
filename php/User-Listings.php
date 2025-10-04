<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: Login.html');
    exit();
}
require_once __DIR__ . '/../php/config.php'; // agrihub DB

$userId = (int)$_SESSION['user_id'];
$name = $_SESSION['name'] ?? 'User';
$email = $_SESSION['email'] ?? '';
$initial = strtoupper(mb_substr($name, 0, 1));

$formError = '';
$categories = [];
// Load categories for listing creation form
if ($stmt = $conn->prepare('SELECT id, name FROM categories ORDER BY name')) {
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $categories[] = $r;
    }
    $stmt->close();
}

// Handle create listing POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['action'] ?? '') === 'create_listing')) {
    $productId = (int)($_POST['product_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $unit = trim($_POST['unit'] ?? '');
    $quantity = (float)($_POST['quantity_available'] ?? 0);
    $isUpdate = $productId > 0;

    if ($title === '' || $categoryId <= 0 || $price <= 0 || $unit === '' || $quantity < 0) {
        $formError = 'Please fill in all required fields with valid values.';
    } else {
        $conn->begin_transaction();
        $stmt = $isUpdate
            ? $conn->prepare('UPDATE products SET title=?, description=?, category_id=?, price=?, unit=?, quantity_available=? WHERE id=? AND seller_id=?')
            : $conn->prepare('INSERT INTO products (seller_id, title, description, category_id, price, unit, quantity_available, status) VALUES (?, ?, ?, ?, ?, ?, ?, "active")');
        if ($stmt) {
            if ($isUpdate) {
                $stmt->bind_param('ssidsdii', $title, $description, $categoryId, $price, $unit, $quantity, $productId, $userId);
            } else {
                $stmt->bind_param('issidsd', $userId, $title, $description, $categoryId, $price, $unit, $quantity);
            }

            if ($stmt->execute()) {
                if (!$isUpdate) {
                    $productId = $stmt->insert_id;
                }
                $stmt->close();

                // Handle multiple image uploads
                if (isset($_FILES['images']) && !empty($_FILES['images']['tmp_name'][0])) {
                    $upload_dir = '../uploads/products/';
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                    
                    $img_stmt = $conn->prepare('INSERT INTO product_images (product_id, image_url, is_primary) VALUES (?, ?, ?)');
                    $primary_flag = true;

                    foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                        if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                            $filename = $productId . '_' . time() . '_' . basename($_FILES['images']['name'][$key]);
                            if (move_uploaded_file($tmp_name, $upload_dir . $filename)) {
                                $image_url = 'uploads/products/' . $filename;
                                $is_primary = $primary_flag ? 1 : 0;
                                $img_stmt->bind_param('isi', $productId, $image_url, $is_primary);
                                $img_stmt->execute();
                                $primary_flag = false; // Only the first image is primary
                            }
                        }
                    }
                    $img_stmt->close();
                }
                $conn->commit();
                header('Location: User-Listings.php?' . ($isUpdate ? 'updated=1' : 'created=1'));
                exit();
            } else {
                $formError = 'Failed to save listing. Please try again.';
                $stmt->close();
            }
        } else {
            $formError = 'Server error. Please try again later.';
        }
        $conn->rollback();
    }
}

$listings = [];
$sql = 'SELECT p.id, p.title, p.description, p.category_id, p.price, p.unit, p.quantity_available, p.status, p.created_at, c.name AS category_name
        FROM products p
        JOIN categories c ON c.id = p.category_id
        WHERE p.seller_id = ?
        ORDER BY p.created_at DESC';
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $listings[] = $row;
    }
    $stmt->close();
}

include '../HTML/User-Listings.html';
