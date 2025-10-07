<?php
require_once __DIR__ . '/config.php';

try {
    // Insert guidance categories into content_categories
    $insert_categories = $conn->prepare("INSERT INTO content_categories (type, name_key, slug, description_key, display_order) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE name_key = name_key");
    $categories = [
        ['guidance', 'crops', 'crops', 'crops_desc', 1],
        ['guidance', 'livestock', 'livestock', 'livestock_desc', 2],
        ['guidance', 'soil_health', 'soil-health', 'soil_health_desc', 3],
        ['guidance', 'pest_management', 'pest-management', 'pest_management_desc', 4],
        ['guidance', 'water_management', 'water-management', 'water_management_desc', 5],
    ];
    foreach ($categories as $cat) {
        $insert_categories->bind_param("sssss", $cat[0], $cat[1], $cat[2], $cat[3], $cat[4]);
        $insert_categories->execute();
    }
    $insert_categories->close();

    // Insert sample articles
    $insert_article = $conn->prepare("INSERT INTO articles (category_id, author_id, title, content, excerpt, status) VALUES (?, 1, ?, ?, ?, 'published')");
    $articles = [
        [1, 'Introduction to Crop Farming', 'Content about crop farming...', 'Learn the basics of crop farming.'],
        [2, 'Livestock Management Tips', 'Content about livestock...', 'Essential tips for managing livestock.'],
        [3, 'Soil Health Improvement', 'Content about soil...', 'How to improve soil health.'],
    ];
    foreach ($articles as $art) {
        $insert_article->bind_param("isss", $art[0], $art[1], $art[2], $art[3]);
        $insert_article->execute();
    }
    $insert_article->close();

    echo "Guidance data inserted successfully.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

$conn->close();
?>
