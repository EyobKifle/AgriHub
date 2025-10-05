/**
 * Creates the HTML for a single category card.
 * @param {object} category - The category data object.
 * @returns {string} - The HTML string for the category card.
 */
function createCategoryCard(category) {
  // Use a placeholder image if the category image is missing
  const imageUrl = category.image_url || 'https://via.placeholder.com/300x200.png?text=AgriHub';
  // The link can point to a future page for that category, e.g., /guidance/crops/teff
  const categoryLink = `guidance-category.html?slug=${category.slug}`;

  return `
    <a href="${categoryLink}" class="category-card">
      <img src="${imageUrl}" alt="${category.name}" class="category-card-image">
      <div class="category-card-content">
        <span>${category.name}</span>
      </div>
    </a>
  `;
}

/**
 * Fetches guidance categories from the server and renders them on the page.
 */
async function renderGuidanceCategories() {
  const placeholder = document.getElementById('guidance-categories-placeholder');
  if (!placeholder) return;

  try {
    const response = await fetch('../php/get-guidance-categories.php');
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    const categoryGroups = await response.json();

    if (categoryGroups.length === 0) {
      placeholder.innerHTML = '<p>No guidance categories found.</p>';
      return;
    }

    let html = '';
    for (const group of categoryGroups) {
      html += `
        <section class="category-section">
          <h2 class="category-section-title">${group.details.name}</h2>
          <div class="categories-grid">
            ${group.children.map(createCategoryCard).join('')}
          </div>
        </section>
      `;
    }
    placeholder.innerHTML = html;
  } catch (error) {
    console.error("Failed to fetch or render guidance categories:", error);
    placeholder.innerHTML = '<p class="error-message">Could not load categories. Please try again later.</p>';
  }
}

export function initializeGuidancePage() {
  renderGuidanceCategories();
}

/**
 * Creates the HTML for a single article card.
 * @param {object} article - The article data object.
 * @returns {string} - The HTML string for the article card.
 */
function createArticleCard(article) {
  const imageUrl = article.image_url || 'https://via.placeholder.com/400x300.png?text=Article';
  const articleLink = `article.html?id=${article.id}`; 
  const excerpt = article.excerpt || 'No summary available.';
  const author = article.author_name || 'AgriHub Staff';
  const postDate = new Date(article.created_at).toLocaleDateString('en-US', {
    year: 'numeric', month: 'long', day: 'numeric'
  });

  return `
    <a href="${articleLink}" class="article-card">
      <img src="${imageUrl}" alt="${article.title}" class="article-card-image">
      <div class="article-card-content">
        <h3 class="article-card-title">${article.title}</h3>
        <p class="article-card-excerpt">${excerpt}</p>
        <div class="article-card-meta">
          <span>By ${author}</span>
          <span>•</span>
          <span>${postDate}</span>
        </div>
      </div>
    </a>
  `;
}

/**
 * Fetches and renders the content for a specific guidance category page.
 */
async function renderCategoryPage() {
  const headerPlaceholder = document.getElementById('category-header-placeholder');
  const articlesPlaceholder = document.getElementById('articles-list-placeholder');
  if (!headerPlaceholder || !articlesPlaceholder) return;

  const params = new URLSearchParams(window.location.search);
  const slug = params.get('slug');

  if (!slug) {
    headerPlaceholder.innerHTML = '<h1>Category not found</h1><p>No category was specified.</p>';
    return;
  }

  try {
    const response = await fetch(`../php/get-articles-by-category.php?slug=${slug}`);
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    const data = await response.json();

    if (!data.success || !data.category) {
      headerPlaceholder.innerHTML = `<h1>Category "${slug}" not found</h1>`;
      return;
    }

    // Render header
    document.title = `${data.category.name} - AgriHub Guidance`;
    headerPlaceholder.innerHTML = `
      <h1 data-i18n-key="${data.category.name_key}">${data.category.name}</h1>
      <p data-i18n-key="${data.category.description_key}">Articles and guides about ${data.category.name.toLowerCase()}.</p>
    `;

    // Render articles
    if (data.articles.length > 0) {
      articlesPlaceholder.innerHTML = data.articles.map(createArticleCard).join('');
    } else {
      articlesPlaceholder.innerHTML = `<p>No articles found in this category yet. Please check back later.</p>`;
    }

  } catch (error) {
    console.error("Failed to fetch or render category content:", error);
    articlesPlaceholder.innerHTML = '<p class="error-message">Could not load articles. Please try again later.</p>';
  }
}

export function initializeGuidanceCategoryPage() {
  renderCategoryPage();
}

/**
 * Creates the HTML for a single related article card.
 * @param {object} article - The related article data object.
 * @returns {string} - The HTML string for the card.
 */
function createRelatedArticleCard(article) {
  const imageUrl = article.image_url || 'https://via.placeholder.com/300x200.png?text=AgriHub';
  const articleLink = `article.html?id=${article.id}`;

  return `
    <a href="${articleLink}" class="related-article-card">
      <img src="${imageUrl}" alt="${article.title}" class="related-article-image">
      <div class="related-article-content">
        <h3 class="related-article-title">${article.title}</h3>
      </div>
    </a>
  `;
}

/**
 * Fetches and renders a single full article.
 */
async function renderArticlePage() {
  const placeholder = document.getElementById('article-content-placeholder');
  if (!placeholder) return;

  const params = new URLSearchParams(window.location.search);
  const articleId = params.get('id');

  if (!articleId) {
    placeholder.innerHTML = '<h1>Article not found</h1><p>No article ID was specified.</p>';
    return;
  }

  try {
    const response = await fetch(`../php/get-article.php?id=${articleId}`);
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    const data = await response.json();

    if (!data.success || !data.article) {
      placeholder.innerHTML = '<h1>Article Not Found</h1><p>The requested article could not be found or is no longer available.</p>';
      return;
    }

    const article = data.article;
    document.title = `${article.title} - AgriHub`;

    const postDate = new Date(article.created_at).toLocaleDateString('en-US', {
      year: 'numeric', month: 'long', day: 'numeric'
    });

    // Note: Using innerHTML for article.content assumes the content is trusted
    // and has been sanitized on the server before being saved to the database.
    placeholder.innerHTML = `
      <header class="article-header">
        <a href="guidance-category.html?slug=${article.category_slug}" class="article-category-link">${article.category_name}</a>
        <h1 class="article-title">${article.title}</h1>
        <div class="article-meta">
          <span>By <strong>${article.author_name}</strong></span>
          <span>•</span>
          <span>${postDate}</span>
        </div>
      </header>

      ${article.image_url ? `
        <figure class="article-image-container">
          <img src="${article.image_url}" alt="${article.title}" class="article-image">
        </figure>
      ` : ''}

      <div class="article-body">
        ${article.content}
      </div>
    `;

    // Render related articles if they exist
    if (data.related_articles && data.related_articles.length > 0) {
      const relatedSection = document.getElementById('related-articles-section');
      const relatedGrid = document.getElementById('related-articles-grid');
      relatedGrid.innerHTML = data.related_articles.map(createRelatedArticleCard).join('');
      relatedSection.style.display = 'block';
    }

  } catch (error) {
    console.error("Failed to fetch or render article:", error);
    placeholder.innerHTML = '<p class="error-message">Could not load the article. Please try again later.</p>';
  }
}

export function initializeArticlePage() {
  renderArticlePage();
}