/**
 * This file controls the client-side filtering logic for the User News page
 * within the dashboard. It prevents full page reloads when filtering by category.
 */

/**
 * Filters news articles based on the selected category.
 * It hides or shows cards based on the selection and updates the URL.
 * @param {string} categorySlug - The slug of the category to filter by.
 */
function filterNewsByCategory(categorySlug) {
  const newsGrid = document.querySelector('.news-grid');
  const emptyState = newsGrid.querySelector('.empty-state');
  if (!newsGrid) return;
  
  let hasVisibleCards = false;
  const newsCards = newsGrid.querySelectorAll('.news-card');
  
  newsCards.forEach(card => {
    // The category slug is now stored in a data attribute for reliability.
    const cardCategorySlug = card.dataset.categorySlug;
    const shouldShow = (categorySlug === 'all' || cardCategorySlug === categorySlug);

    card.hidden = !shouldShow;
    if (shouldShow) {
      hasVisibleCards = true;
    }
  });

  if (emptyState) {
    emptyState.hidden = hasVisibleCards;
  }

  // Update the URL in the browser without reloading the page
  const newUrl = categorySlug === 'all' ? window.location.pathname : `${window.location.pathname}?category=${categorySlug}`;
  window.history.pushState({ path: newUrl }, '', newUrl);
}

export function initializeUserNewsPage() {
  const categoryList = document.querySelector('.category-list');
  if (!categoryList) return;

  categoryList.addEventListener('click', (e) => {
    const link = e.target.closest('a');
    if (!link) return;

    e.preventDefault(); // Stop the page from reloading
    categoryList.querySelectorAll('a').forEach(a => a.classList.remove('active'));
    link.classList.add('active');

    const categorySlug = new URL(link.href).searchParams.get('category') || 'all';
    filterNewsByCategory(categorySlug);
  });
}