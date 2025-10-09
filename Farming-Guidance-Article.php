<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Article - AgriHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/AgriHub/Css/header.css">
    <link rel="stylesheet" href="/AgriHub/Css/footer.css">
    <link rel="stylesheet" href="/AgriHub/Css/guidance.css">
</head>
<body>
    <div id="header-placeholder"></div>

    <main class="page-container">
        <div class="content-wrapper article-layout">
            <article id="article-content-placeholder" class="article-main-content">
                <!-- Full article content will be loaded here by JavaScript -->
                <div class="loading-spinner"></div>
            </article>

            <aside class="article-sidebar">
                <section id="related-articles-section" style="display: none;">
                    <h2 data-i18n-key="article.related.title">Related Articles</h2>
                    <div id="related-articles-grid">
                        <!-- Related articles will be loaded here -->
                    </div>
                </section>
            </aside>
        </div>
    </main>

    <div id="footer-placeholder"></div>
    <script type="module" src="/AgriHub/Js/site.js"></script>
</body>
</html>