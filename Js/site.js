import { loadHeader, initializeHeaderScripts } from './header.js';
import { loadFooter } from './footer.js';
import { highlightActiveLink } from './navigation.js';
import { initializeMarketplace } from './marketplace.js';
import { initializeNewsPage } from './news.js';
import { initializeGuidancePage } from './guidance.js';

/**
 * This is the main entry point for the site's global JavaScript.
 * It runs on every page to set up the common elements.
 *
 * Here's the sequence of events:
 * 1. It loads the header and footer HTML content at the same time.
 * 2. Once the header and footer are loaded, it initializes the scripts for them (like the mobile menu).
 * 3. Finally, it highlights the active link in the main navigation.
 */
const initializeSite = async () => {
  // We wait for the header to finish loading first.
  await loadHeader();
  // Then, we wait for the footer to finish loading.
  // Loading them one by one is simpler to read than loading them at the same time.
  await loadFooter();

  // Now that the header HTML is in place, we can make it interactive.
  initializeHeaderScripts();
  // And we can find the links in the nav to highlight the current page.
  highlightActiveLink();
  // Finally, run any page-specific scripts, like for the marketplace.
  initializeMarketplace();
  initializeNewsPage();
  initializeGuidancePage();
};

// This is the standard way to make sure our script runs only after the
// initial HTML document has been fully loaded and is ready to be manipulated.
document.addEventListener('DOMContentLoaded', initializeSite);