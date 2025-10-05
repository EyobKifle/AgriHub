import { loadHeader, initializeHeaderScripts } from './header.js';
import { loadFooter } from './footer.js';
import{ initializeImageSlider } from './imageSlider.js';
import { initializeI18n, applyTranslationsToPage, updateLangSwitcher } from './i18n.js';
import { highlightActiveLink } from './navigation.js';
import { initializeMarketplace } from './marketplace.js';
import { initializeNewsPage } from './news.js';
import { initializeGuidancePage } from './guidance.js';

/**
 * Checks the URL for query parameters like 'error' or 'success' and displays
 * a message in a designated placeholder element on the page.
 */
function displayFlashMessages() {
  const placeholder = document.getElementById('form-message-placeholder');
  if (!placeholder) return;

  const params = new URLSearchParams(window.location.search);
  const error = params.get('error');
  const success = params.get('success');

  let message = '';
  let messageType = '';

  // Define all possible messages
  const errorMessages = {
    'invalid': 'Invalid email or password. Please try again.',
    'missing': 'Please fill in all required fields.',
    'invalid_email': 'The email address you entered is not valid.',
    'password_mismatch': 'The passwords you entered do not match.',
    'email_taken': 'An account with this email address already exists.',
    'server': 'A server error occurred. Please try again later.'
  };

  if (error && errorMessages[error]) {
      message = errorMessages[error];
      messageType = 'error';
  }

  const successMessages = {
      'logged_out': 'You have been successfully logged out.'
  };
  if (success && successMessages[success]) {
      message = successMessages[success];
      messageType = 'success';
  }

  if (message) {
    placeholder.innerHTML = `<div class="form-message ${messageType}">${message}</div>`;
  }
}

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

  // Display any flash messages from redirects (e.g., login errors)
  displayFlashMessages();

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