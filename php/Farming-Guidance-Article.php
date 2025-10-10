<?php
require_once __DIR__ . '/../php/config.php';
// You might include a header file here if necessary
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Loading Article... - AgriHub</title>
  <link rel="stylesheet" href="/AgriHub/Css/header.css" />
  <link rel="stylesheet" href="/AgriHub/Css/footer.css" />
  <link rel="stylesheet" href="/AgriHub/Css/guidance.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
</head>

<body>
  <div id="header-placeholder"></div>

  <main class="article-page-container">
    <article id="article-content-placeholder">
      <div class="loading-spinner">Loading article content...</div>
    </article>

    <hr class="article-separator">

    <section id="related-articles-section" style="display: none;">
      <h2 class="related-section-title" data-i18n-key="article.relatedArticles">Related Articles</h2>
      <div class="related-articles-grid" id="related-articles-grid">
        </div>
    </section>
  </main>

  <div id="footer-placeholder"></div>

  <script type="module">
    import { initializeArticlePage } from '/AgriHub/Js/guidance.js'; 
    initializeArticlePage();
  </script>
</body>

</html>