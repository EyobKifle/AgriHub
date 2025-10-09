<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guidance Category - AgriHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/AgriHub/Css/header.css">
    <link rel="stylesheet" href="/AgriHub/Css/footer.css">
    <link rel="stylesheet" href="/AgriHub/Css/guidance.css">
</head>
<body>
    <div id="header-placeholder"></div>

    <main class="page-container">
        <div class="content-wrapper">
            <div class="page-header" id="category-header-placeholder">
                <!-- Category title and description loaded by JS -->
                <div class="loading-spinner"></div>
            </div>

            <div class="article-list-controls">
                <select id="sort-articles">
                    <option value="newest">Sort by Newest</option>
                    <option value="popular">Sort by Popular</option>
                </select>
            </div>

            <div class="articles-grid" id="articles-list-placeholder">
                <!-- Articles will be loaded here by JavaScript -->
                <div class="loading-spinner"></div>
            </div>
        </div>
    </main>

    <div id="footer-placeholder"></div>
    <script type="module" src="/AgriHub/Js/site.js"></script>
</body>
</html>