<?php
require_once __DIR__ . '/../php/config.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Farming Guidance - AgriHub</title>
  <link rel="stylesheet" href="/AgriHub/Css/header.css" />
  <link rel="stylesheet" href="/AgriHub/Css/footer.css" />
  <link rel="stylesheet" href="/AgriHub/Css/guidance.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
</head>

<body>
  <div id="header-placeholder"></div>

  <main class="guidance-container">
    <header class="guidance-header">
      <h1 data-i18n-key="guidance.title">Farming Guidance</h1>
      <p data-i18n-key="guidance.subtitle">Explore categories to find articles, tips, and discussions.</p>
    </header>

    <div class="search-bar-container">
      <i class="fa-solid fa-search search-icon"></i>
      <input type="search" id="category-search-input" placeholder="Search for categories like 'Teff', 'Cattle'..." data-i18n-placeholder-key="guidance.searchPlaceholder" />
    </div>

    <div id="guidance-categories-placeholder">
      <?php
      try {
        $sql = "SELECT id, name, name_key, slug, description_key 
            FROM content_categories 
            WHERE type='guidance' 
            ORDER BY display_order";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
          echo '<div class="categories-grid">';
          while ($category = $result->fetch_assoc()) {
            $imageUrl = 'https://via.placeholder.com/300x200.png?text=' . urlencode($category['name']);
            $categoryLink = "guidance-category.php?slug={$category['slug']}";
            echo "
            <a href=\"{$categoryLink}\" class=\"category-card\">
              <img src=\"{$imageUrl}\" alt=\"{$category['name']}\" class=\"category-card-image\">
              <div class=\"category-card-content\">
                <span data-i18n-key=\"{$category['name_key']}\">{$category['name']}</span>
              </div>
            </a>";
          }
          echo '</div>';
        } else {
          echo '<p>No guidance categories found.</p>';
        }

        $result->free();
      } catch (Exception $e) {
        echo '<p>Could not load categories. Please try again later.</p>';
      }

      $conn->close();
      ?>
    </div>
  </main>

  <div id="footer-placeholder"></div>
  <script type="module" src="/AgriHub/Js/site.js"></script>
</body>

</html>