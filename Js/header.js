import { fetchAndCache } from './cache.js';
import { setLanguage, updateLangSwitcher } from './i18n.js';

/**
 * Loads the shared header content into the page using the cache.
 */
export const loadHeader = async () => {
  const placeholder = document.getElementById('header-placeholder');
  if (!placeholder) {
    console.error('Header placeholder not found');
    return;
  }

  try {
    const data = await fetchAndCache('headerHTML', '../HTML/header.html');
    placeholder.innerHTML = data;
  } catch (error) {
    console.error('Error loading header:', error);
    placeholder.innerHTML = '<p>Error loading header. Please try again later.</p>';
  }
};

/**
 * Initializes interactive features of the header, like the mobile menu,
 * dropdowns, and dark mode toggle. This should be called AFTER the
 * header HTML has been loaded.
 */
export const initializeHeaderScripts = () => {
  // --- Mobile Menu ---
  const mobileBtn = document.querySelector('.mobile-menu-toggle');
  const header = document.querySelector('.header');

  if (mobileBtn && header) {
    mobileBtn.addEventListener('click', () => {
      header.classList.toggle('open');
    });
  }

  // --- Language Dropdown ---
  const dropdown = document.querySelector('.dropdown');

  if (dropdown) {
    const dropbtn = dropdown.querySelector('.dropbtn');
    if (!dropbtn) return;

    dropbtn.addEventListener('click', (e) => {
      e.preventDefault();
      dropdown.classList.toggle('active');
    });

  // --- Handle Language Selection ---
  const dropdownContent = dropdown.querySelector('.dropdown-content');
  dropdownContent?.addEventListener('click', (e) => {
    const lang = e.target.closest('[data-lang]')?.getAttribute('data-lang');
    if (lang) {
      e.preventDefault();
      setLanguage(lang);

      dropdown.classList.remove('active'); // Close dropdown after selection
    }
  });

    // Close dropdown if clicking outside of it
    window.addEventListener('click', (event) => {
      if (!dropdown.contains(event.target)) {
        dropdown.classList.remove('active');
      }
    });
  }

  // --- Dark Mode ---
  const darkToggle = document.querySelector('.dark-mode-toggle');
  if (darkToggle) {
    // Set initial state from localStorage
    const isDarkMode = localStorage.getItem('theme') === 'dark';
    if (isDarkMode) {
      document.body.classList.add('dark-mode');
    }

    darkToggle.addEventListener('click', (e) => {
      e.preventDefault();
      if (document.body.classList.contains('dark-mode')) { 
        document.body.classList.remove('dark-mode');
        localStorage.setItem('theme', 'light');
      } else {
        document.body.classList.add('dark-mode');
        localStorage.setItem('theme', 'dark');
      }
    });
  }
};
