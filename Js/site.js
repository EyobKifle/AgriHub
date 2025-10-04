import { loadHeader, initializeHeaderScripts } from './header.js';
import { loadFooter } from './footer.js';
import{ initializeImageSlider } from './imageSlider.js';
import { initializeI18n, applyTranslationsToPage, updateLangSwitcher } from './i18n.js';
import { highlightActiveLink } from './navigation.js';
import { initializeMarketplace } from './marketplace.js';
import { initializeNewsPage } from './news.js';
import { initializeGuidancePage } from './guidance.js';

/*
 * This is the main entry point for the site's global JavaScript.
 * It runs on every page to set up the common elements.
 *
 * Here's the sequence of events:
 * 1. It loads the header and footer HTML content at the same time.
 * 2. Once the header and footer are loaded, it initializes the scripts for them (like the mobile menu).
 * 3. Finally, it highlights the active link in the main navigation.
 */
const initializeSite = async () => {
  // 1. Initialize i18n to load the correct language from localStorage.
  await initializeI18n();

  // 2. Load header and footer HTML concurrently.
  await Promise.all([
    loadHeader(),
    loadFooter()
  ]);

  // 3. Now that the header/footer are in the DOM, apply translations to them.
  applyTranslationsToPage();
  updateLangSwitcher(); // Ensure the language dropdown text is correct.

  // 4. Initialize all interactive scripts.
  initializeHeaderScripts();
  initializeImageSlider();
  // And we can find the links in the nav to highlight the current page.
  highlightActiveLink();

  // 5. Run page-specific scripts.
  // This improves performance by only executing the code needed for the current page.
  const currentPage = window.location.pathname.split('/').pop();

  switch (currentPage) {
    case 'HomePage.html':
    case '': // Handles the root path (e.g., "www.site.com/")
      initializeImageSlider();
      break;
    case 'Marketplace.html':
      initializeMarketplace();
      break;
    case 'News.html':
      initializeNewsPage();
      break;
    case 'Farming-Guidance.html':
      initializeGuidancePage();
      break;
  }
};

// This is the standard way to make sure our script runs only after the
// initial HTML document has been fully loaded and is ready to be manipulated.
document.addEventListener('DOMContentLoaded', initializeSite);