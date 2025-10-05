<?php
session_start();
require_once __DIR__ . '/php/utils.php'; // For e() and other helpers

$guide = null;
$error = null;
$related_topics = [];

// Safely get the article ID from the URL
$articleId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$articleId) {
    $error = 'Invalid or missing guidance ID.';
} else {
    // Define the path to your data file
    $jsonPath = __DIR__ . '/data/guidance-map.json';
    if (!file_exists($jsonPath)) {
        $error = 'Guidance data file not found.';
    } else {
        $jsonContent = file_get_contents($jsonPath);
        $data = json_decode($jsonContent, true);

        // Check if the JSON was decoded correctly
        if (json_last_error() !== JSON_ERROR_NONE) {
            $error = 'Error decoding guidance data.';
        } else {
            $articles = $data['articles'] ?? [];
            
            // Loop through articles to find the one with the matching ID
            foreach ($articles as $a) {
                if ($a['id'] === $articleId) {
                    $guide = $a;
                    break;
                }
            }

            // If we found the article, look for related topics
            if ($guide) {
                foreach ($articles as $a) {
                    // A related topic has the same 'item' but a different 'id'
                    if ($a['item'] === $guide['item'] && $a['id'] !== $guide['id']) {
                        $related_topics[] = $a;
                    }
                    if (count($related_topics) >= 5) break; // Limit to 5 related topics
                }
            } else {
                $error = "Guidance article with ID {$articleId} not found.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $guide ? htmlspecialchars($guide['title']) : 'Guidance Detail'; ?> - AgriHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="Css/main.css">
    <link rel="stylesheet" href="Css/guidance.css">
</head>
<body>
    <?php require_once __DIR__ . '/partials/header.php'; ?>

    <main class="page-container">
        <div class="content-wrapper">
            <a href="Farming-Guidance.php" class="back-link">&larr; Back to Guidance Map</a>

            <?php if ($error): ?>
                <div class="error-message">
                    <h1>Error</h1>
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php elseif ($guide): ?>
                <article class="guidance-article">
                    <header>
                        <h1><?php echo htmlspecialchars($guide['title']); ?></h1>
                        <p class="article-meta">Category: <?php echo htmlspecialchars($guide['category']); ?> | Topic: <?php echo htmlspecialchars($guide['item']); ?></p>
                    </header>
                    <div class="article-content">
                        <?php echo $guide['content_html']; // Assuming content is safe HTML ?>
                    </div>
                </article>

                <?php if (!empty($related_topics)): ?>
                    <aside class="related-topics">
                        <h2>Related Topics</h2>
                        <ul>
                            <?php foreach ($related_topics as $related): ?>
                                <li>
                                    <a href="guidance-detail.php?id=<?php echo $related['id']; ?>">
                                        <?php echo htmlspecialchars($related['title']); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </aside>
                <?php endif; ?>

            <?php endif; ?>
        </div>
    </main>

    <?php require_once __DIR__ . '/partials/footer.php'; ?>

    <script src="Js/site.js" type="module"></script>
</body>
</html>