<?php
session_start();
require_once __DIR__ . '/utils.php'; // For the e() and embedYouTube() helpers, if you have them.

$guide = null;
$error = null;
$related_topics = [];

// Safely get the article ID from the URL
$articleId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$articleId) {
    $error = 'Invalid or missing guidance ID.';
} else {
    // Define the path to your data file
    $jsonPath = __DIR__ . '/../data/guidance-map.json';
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
                    if (count($related_topics) >= 5) break; // Limit to 5 related topics for performance
                }
            } else {
                $error = "Guidance article with ID {$articleId} not found.";
            }
        }
    }
}

// Now, include the HTML template to render the page with the data we've prepared.
require_once __DIR__ . '/../HTML/guidance-detail.html';