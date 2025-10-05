<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'moderator'])) {
    header('Location: ../HTML/Login.html');
    exit();
}

require_once __DIR__ . '/config.php';

$name = $_SESSION['name'] ?? 'Admin';
$email = $_SESSION['email'] ?? '';
$initial = strtoupper(mb_substr($name, 0, 1));

// Fetch articles
$sql = "
    SELECT
        a.id,
        a.title,
        a.image_url,
        a.status,
        a.created_at,
        u.name as author_name,
        cc.name as category_name
    FROM articles a
    JOIN users u ON a.author_id = u.id
    JOIN content_categories cc ON a.category_id = cc.id
    ORDER BY a.created_at DESC
";

$articles = [];
if ($result = $conn->query($sql)) {
    while ($row = $result->fetch_assoc()) {
        $articles[] = $row;
    }
    $result->free();
}

include '../HTML/News-Management.html';

?>