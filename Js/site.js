import { loadHeader, initializeHeaderScripts } from './header.js';
import { loadFooter } from './footer.js';
import { highlightActiveLink } from './navigation.js';

// Main initialization function
const initializeSite = async () => {
  await Promise.all([
    loadHeader(),
    loadFooter(),
  ]);

  initializeHeaderScripts();
  highlightActiveLink();
};

// Run initialization when DOM is fully loaded
document.addEventListener('DOMContentLoaded', initializeSite);