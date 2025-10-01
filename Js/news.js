/**
 * This file controls the filtering logic for the News page.
 * It allows users to filter articles by category or by tag.
 */
export const initializeNewsPage = () => {
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
   * It hides or shows cards based on the selection.
   */
  const filterNews = (type, value) => {
    let hasVisibleCards = false;

    newsCards.forEach(card => {
      let shouldShow = false;

      // If the filter is 'all', every card should be shown.
      if (value === 'all') {
        shouldShow = true;
      // If filtering by category, check if the card's category matches.
      } else if (type === 'category') {
        shouldShow = card.dataset.category === value;
      // If filtering by tag, check if the card's tags include the selected tag.
      } else if (type === 'tag') {
        // The tags are stored as a comma-separated string, so we split them into an array.
        const cardTags = card.dataset.tags.split(',');
        shouldShow = cardTags.includes(value);
      }

      card.hidden = !shouldShow;
      if (shouldShow) {
        hasVisibleCards = true;
      }
    });

    // If no cards are visible after filtering, show the "empty state" message.
    emptyState.hidden = hasVisibleCards;
  };

  // --- Event Listener for Categories ---
  categoryList.addEventListener('click', (e) => {
    e.preventDefault();
    const target = e.target.closest('.category-item');
    if (!target) return;

    // First, remove the 'active' class from all category items.
    categoryList.querySelectorAll('.category-item').forEach(item => item.classList.remove('active'));
    // Then, add the 'active' class to the one that was clicked.
    target.classList.add('active');

    // Since a category was clicked, we should clear any active tag filter.
    trendingTags.querySelectorAll('.tag').forEach(tag => tag.classList.remove('active'));

    const category = target.dataset.category;
    filterNews('category', category);
  });

  // --- Event Listener for Trending Tags ---
  trendingTags.addEventListener('click', (e) => {
    const target = e.target.closest('.tag');
    if (!target) return;

    const tagValue = target.dataset.tag;

    // This is a toggle feature: if you click an active tag again, it turns off the filter.
    if (target.classList.contains('active')) {
      target.classList.remove('active');
      // Find and re-activate the "All News" category to show all articles.
      const allNewsCategory = categoryList.querySelector('[data-category="all"]');
      if (allNewsCategory) {
        categoryList.querySelectorAll('.category-item').forEach(item => item.classList.remove('active'));
        allNewsCategory.classList.add('active');
        filterNews('category', 'all');
      }
    // If the clicked tag was not active...
    } else {
      // First, remove the 'active' class from all other tags.
      trendingTags.querySelectorAll('.tag').forEach(tag => tag.classList.remove('active'));
      // Then, add the 'active' class to the one that was clicked.
      target.classList.add('active');

      // Visually reset the category list to "All News" so it's clear the tag is the active filter.
      categoryList.querySelectorAll('.category-item').forEach(item => item.classList.remove('active'));
      categoryList.querySelector('[data-category="all"]').classList.add('active');
      filterNews('tag', tagValue);
    }
  });
};