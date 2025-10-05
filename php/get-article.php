<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

$article_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$article_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid or missing article ID.']);
    exit();
}

$response = ['success' => false, 'article' => null];

$sql = "SELECT 
            a.id, a.title, a.content, a.image_url, a.created_at,
            u.name as author_name,
            cat.name as category_name,
            cat.slug as category_slug
        FROM articles a
        JOIN users u ON a.author_id = u.id
        JOIN content_categories cc ON a.category_id = cc.id
        JOIN categories cat ON cc.name_key = cat.name_key
        WHERE a.id = ? AND a.status = 'published' AND cat.parent_id IS NOT NULL";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $article_id);
$stmt->execute();
$result = $stmt->get_result();
$article = $result->fetch_assoc();

if ($article) {
    $response['success'] = true;
    $response['article'] = $article;

    // Fetch 3 related articles from the same category, excluding the current one
    $related_sql = "SELECT 
                        a.id, a.title, a.image_url,
                        cat.slug as category_slug
                    FROM articles a
                    JOIN content_categories cc ON a.category_id = cc.id
                    JOIN categories cat ON cc.name_key = cat.name_key
                    WHERE cat.slug = ? AND a.id != ? AND a.status = 'published'
                    ORDER BY a.created_at DESC
                    LIMIT 3";
    $stmt_related = $conn->prepare($related_sql);
    $stmt_related->bind_param("si", $article['category_slug'], $article['id']);
    $stmt_related->execute();
    $response['related_articles'] = $stmt_related->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_related->close();
}

$stmt->close();
$conn->close();

echo json_encode($response);