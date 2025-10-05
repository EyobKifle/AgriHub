/**
 * Manages the Farming Guidance detail page.
 * Fetches guidance data from a JSON file and renders the selected article.
 */

const els = {
    title: document.querySelector('title'),
    errorBox: document.getElementById('error-box'),
    guidanceContent: document.getElementById('guidance-content'),
    breadcrumbItem: document.getElementById('breadcrumb-item'),
    articleTitle: document.getElementById('article-title'),
    articleDomain: document.getElementById('article-domain'),
    articleSubdomain: document.getElementById('article-subdomain'),
    articleDesc: document.getElementById('article-desc'),
    articleImage: document.getElementById('article-image'),
    guideTitle1: document.getElementById('guide-title-1'),
    guideItem1: documentgetElementById('guide-item-1'),
    relatedTopicsList: document.getElementById('related-topics-list'),
};

/**
 * Displays an error message on the page.
 * @param {string} message The error message to show.
 */
function showError(message) {
    els.errorBox.textContent = message;
    els.errorBox.style.display = 'block';
    els.guidanceContent.style.display = 'none';
}

/**
 * Renders the fetched article data onto the page.
 * @param {object} article The article object to render.
 * @param {Array<object>} allArticles The complete list of articles to find related topics.
 */
function renderArticle(article, allArticles) {
    // --- Update main content ---
    els.title.textContent = `${article.title} - ${article.item} | AgriHub`;
    els.breadcrumbItem.textContent = article.item;
    els.articleTitle.textContent = article.title;
    els.articleDomain.textContent = article.domain;
    els.articleSubdomain.textContent = article.subdomain;
    els.articleDesc.textContent = article.desc;
    els.articleImage.src = article.image;
    els.articleImage.alt = article.title;

    // --- Update placeholder text in the guide ---
    els.guideTitle1.textContent = article.title;
    els.guideItem1.textContent = article.item;

    // --- Find and render related topics ---
    const relatedTopics = allArticles.filter(
        (a) => a.id !== article.id && a.item === article.item
    ).slice(0, 5); // Get up to 5 related topics

    if (relatedTopics.length > 0) {
        els.relatedTopicsList.innerHTML = relatedTopics.map(topic => `
            <li><a href="guidance-detail.html?id=${topic.id}">${topic.title}</a></li>
        `).join('');
    } else {
        els.relatedTopicsList.innerHTML = '<li>No related topics found.</li>';
    }

    // --- Show the content ---
    els.guidanceContent.style.display = 'block';
}

/**
 * Fetches the guidance data from the JSON file.
 */
async function fetchGuidanceData() {
    const params = new URLSearchParams(window.location.search);
    const articleId = parseInt(params.get('id'), 10);

    if (!articleId) {
        showError('No guidance article ID was specified. Please go back and select an article.');
        return;
    }

    try {
        const response = await fetch('../data/guidance-map.json');
        if (!response.ok) throw new Error('Failed to load guidance data.');
        const data = await response.json();
        const article = data.articles.find(a => a.id === articleId);

        if (!article) throw new Error(`Article with ID ${articleId} could not be found.`);
        
        renderArticle(article, data.articles);
    } catch (error) {
        showError(error.message);
    }
}

document.addEventListener('DOMContentLoaded', fetchGuidanceData);