<?php
header("Content-Type: application/json");
require_once 'config.php';

$sql = "SELECT id, title_key, desc_key, author_key, time_key, image_url, category_key, tags, is_featured FROM news_articles ORDER BY date_published DESC";
$result = $conn->query($sql);

$news = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $news[] = $row;
    }
}

echo json_encode($news);

$conn->close();
?>