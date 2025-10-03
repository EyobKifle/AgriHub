import { fetchAndCache } from './cache.js';

/**
 * Loads the shared footer content into the page.
 * It uses the `fetchAndCache` function to download the footer once and
 * load it from a saved copy on subsequent page loads for speed.
 */
export const loadFooter = async () => {
  const placeholder = document.getElementById('footer-placeholder');
  if (!placeholder) {
    console.error('Footer placeholder not found');
    return;
  }

  try {
    const data = await fetchAndCache('footerHTML', '../HTML/footer.html');
    placeholder.innerHTML = data;
  } catch (error) {
    console.error('Error loading footer:', error);
    placeholder.innerHTML = '<p>Error loading footer. Please try again later.</p>';
  }
};