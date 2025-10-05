<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

$slug = trim($_GET['slug'] ?? '');
$sort = trim($_GET['sort'] ?? 'newest');

if (empty($slug)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Category slug is required.']);
    exit();
}

$response = [
    'success' => false,
    'category' => null,
    'articles' => []
];

try {
    // 1. Get category details from the 'categories' table using the slug
    $stmt_cat = $conn->prepare("SELECT id, name, description_key FROM categories WHERE slug = ?");
    $stmt_cat->bind_param("s", $slug);
    $stmt_cat->execute();
    $category_result = $stmt_cat->get_result();
    $category = $category_result->fetch_assoc();
    $stmt_cat->close();

    if ($category) {
        $response['success'] = true;
        $response['category'] = $category;

        // Determine sort order
        $orderBy = "ORDER BY a.created_at DESC"; // Default
        if ($sort === 'popular') {
            $orderBy = "ORDER BY a.views DESC, a.created_at DESC";
        }

        // 2. Get articles. We assume articles are linked via the category's name_key matching the content_categories' name_key. Also fetch title_key for translation.
        $sql_articles = "SELECT a.id, a.title, a.title_key, a.excerpt, a.image_url, a.views, a.created_at, u.name as author_name
             FROM articles a
             JOIN users u ON a.author_id = u.id
             JOIN content_categories cc ON a.category_id = cc.id
             WHERE cc.name_key = ? AND a.status = 'published'
             $orderBy";

        $stmt_articles = $conn->prepare($sql_articles);
        $stmt_articles->bind_param("s", $category['name_key']);
        $stmt_articles->execute();
        $articles_result = $stmt_articles->get_result();
        $response['articles'] = $articles_result->fetch_all(MYSQLI_ASSOC);
        $stmt_articles->close();
    }
} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Database error: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response);