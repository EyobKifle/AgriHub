<?php
require_once __DIR__ . '/../php/config.php';
header('Content-Type: text/html; charset=utf-8')
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Category Articles - AgriHub Guidance</title>
  <link rel="stylesheet" href="/AgriHub/Css/header.css" />
  <link rel="stylesheet" href="/AgriHub/Css/footer.css" />
  <link rel="stylesheet" href="/AgriHub/Css/guidance.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
</head>

<body>
  <div id="header-placeholder"></div>

  <main class="category-page-container">
    <div class="category-header-controls">
      <div id="category-header-placeholder">
        <div class="loading-spinner">Loading category information...</div>
      </div>
      
      <div class="sort-control">
        <label for="sort-articles" data-i18n-key="category.sortLabel">Sort By:</label>
        <select id="sort-articles">
          <option value="newest" data-i18n-key="category.sortNewest">Newest</option>
          <option value="popular" data-i18n-key="category.sortPopular">Most Popular</option>
          <option value="oldest" data-i18n-key="category.sortOldest">Oldest</option>
        </select>
      </div>
    </div>

    <hr>
    
    <div class="articles-grid" id="articles-list-placeholder">
      <div class="loading-spinner">Loading articles...</div>
    </div>

  </main>

  <div id="footer-placeholder"></div>

  <script type="module">
    import { initializeGuidanceCategoryPage } from '/AgriHub/Js/guidance.js'; 
    initializeGuidanceCategoryPage();
  </script>
</body>

</html>