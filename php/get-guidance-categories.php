<?php
try {
    require_once __DIR__ . '/config.php';

    header('Content-Type: application/json');

    $categories = [];
    $sql = "SELECT id, NULL as parent_id, name, name_key, slug, description_key, NULL as image_url FROM content_categories WHERE type='guidance' ORDER BY display_order";

    $result = $conn->query($sql);

    if ($result) {
        while($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
        $result->free();
    }

    $conn->close();

    // Group categories by parent
    $grouped = [];

    // First pass: Initialize all parent categories
    foreach ($categories as $category) {
        if ($category['parent_id'] === null) {
            $grouped[$category['id']] = ['details' => $category, 'children' => []];
        }
    }

    // Second pass: Assign children to their parents
    foreach ($categories as $category) {
        if ($category['parent_id'] !== null) {
            // This is a child category. Add it to its parent if the parent exists.
            if (isset($grouped[$category['parent_id']])) {
                $grouped[$category['parent_id']]['children'][] = $category;
            }
        }
    }

    echo json_encode(array_values($grouped));
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
