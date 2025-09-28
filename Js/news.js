document.addEventListener('DOMContentLoaded', () => {
  const categoryList = document.getElementById('category-list');
  const trendingTags = document.getElementById('trending-tags');
  const newsCards = document.querySelectorAll('.news-card');
  const emptyState = document.getElementById('empty-state');

  if (!categoryList || !trendingTags || !newsCards.length) {
    console.error('News page elements not found.');
    return;
  }

  /**
   * Filters news articles based on a given filter type and value.
   * @param {string} type - The type of filter ('category' or 'tag').
   * @param {string} value - The value to filter by (e.g., 'policy', 'coffee').
   */
  const filterNews = (type, value) => {
    let hasVisibleCards = false;

    newsCards.forEach(card => {
      let shouldShow = false;
      if (value === 'all') {
        shouldShow = true;
      } else if (type === 'category') {
        shouldShow = card.dataset.category === value;
      } else if (type === 'tag') {
        const cardTags = card.dataset.tags.split(',');
        shouldShow = cardTags.includes(value);
      }

      card.hidden = !shouldShow;
      if (shouldShow) {
        hasVisibleCards = true;
      }
    });

    // Show or hide the empty state message
    emptyState.hidden = hasVisibleCards;
  };

  // --- Event Listener for Categories ---
  categoryList.addEventListener('click', (e) => {
    e.preventDefault();
    const target = e.target.closest('.category-item');
    if (!target) return;

    // Update active state for categories
    categoryList.querySelectorAll('.category-item').forEach(item => item.classList.remove('active'));
    target.classList.add('active');

    // Remove active state from tags
    trendingTags.querySelectorAll('.tag').forEach(tag => tag.classList.remove('active'));

    const category = target.dataset.category;
    filterNews('category', category);
  });

  // --- Event Listener for Trending Tags ---
  trendingTags.addEventListener('click', (e) => {
    const target = e.target.closest('.tag');
    if (!target) return;

    const tagValue = target.dataset.tag;

    // If the clicked tag is already active, reset to "All News"
    if (target.classList.contains('active')) {
      target.classList.remove('active');
      // Find and activate "All News"
      const allNewsCategory = categoryList.querySelector('[data-category="all"]');
      if (allNewsCategory) {
        categoryList.querySelectorAll('.category-item').forEach(item => item.classList.remove('active'));
        allNewsCategory.classList.add('active');
        filterNews('category', 'all');
      }
    } else {
      // Update active state for tags
      trendingTags.querySelectorAll('.tag').forEach(tag => tag.classList.remove('active'));
      target.classList.add('active');
      // Reset category to "All News" visually
      categoryList.querySelectorAll('.category-item').forEach(item => item.classList.remove('active'));
      categoryList.querySelector('[data-category="all"]').classList.add('active');
      filterNews('tag', tagValue);
    }
  });
});