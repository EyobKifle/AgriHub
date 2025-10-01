import { fetchAndCache } from './cache.js';

/**
 * Loads the shared header content into the page.
 * It uses the `fetchAndCache` function to download the header once and
 * load it from a saved copy on subsequent page loads for speed.
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
 * Sets up all the interactive parts of the header after it has been loaded.
 * This includes the mobile menu, the language dropdown, and the dark mode toggle.
 * It's important to run this *after* the header HTML is on the page.
 */
export const initializeHeaderScripts = () => {
  // --- Mobile Menu ---
  // Find the hamburger icon and the navigation container.
  const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
  const navContainer = document.querySelector('.elements') || document.querySelector('.header');

  // If both elements are found, add a click event to the hamburger icon.
  if (mobileMenuToggle && navContainer) {
    mobileMenuToggle.addEventListener('click', () => {
      // When clicked, it adds or removes an 'open' or 'active' class to show/hide the menu.
      navContainer.classList.toggle(navContainer.classList.contains('header') ? 'open' : 'active');
    });
  } else {
    console.warn('Mobile menu elements not found');
  }

  // --- Language Dropdown Menu ---
  // Find the dropdown container and the button that opens it.
  const dropdown = document.querySelector('.dropdown');
  const dropbtn = document.querySelector('.dropbtn');

  // If both are found, set up the click events.
  if (dropdown && dropbtn) {
    dropbtn.addEventListener('click', (e) => {
      e.preventDefault();
      dropdown.classList.toggle('active');
    });

    /**
     * This function handles closing the dropdown menu if the user clicks anywhere
     * outside of it. It makes the menu feel more intuitive.
     */
    const closeDropdownOnClickOutside = (event) => {
      // We check if the place the user clicked (`event.target`) is *not* inside the dropdown menu.
      // `dropdown.contains()` is a handy way to see if one element is inside another.
      const isClickInsideDropdown = dropdown.contains(event.target);

      if (!isClickInsideDropdown) {
        // If the click was outside, we remove the 'active' class to hide the menu.
        dropdown.classList.remove('active');
      }
    };

    // We add this function as an event listener to the whole window.
    window.addEventListener('click', closeDropdownOnClickOutside);
  } else {
    console.warn('Dropdown elements not found');
  }

  // --- Dark Mode Toggle ---
  // Find the toggle switch element.
  const toggleSwitch = document.querySelector('.dark-mode-toggle');
  if (toggleSwitch) {
    // Check if a 'dark' theme was saved from a previous visit.
    if (localStorage.getItem('theme') === 'dark') {
      // If so, apply the dark mode class to the body and check the toggle.
      document.body.classList.add('dark-mode');
      toggleSwitch.checked = true;
    }

    // Add an event listener for when the toggle is clicked.
    toggleSwitch.addEventListener('change', () => {
      document.body.classList.toggle('dark-mode');
      // Save the user's choice ('dark' or 'light') in localStorage for their next visit.
      localStorage.setItem('theme', document.body.classList.contains('dark-mode') ? 'dark' : 'light');
      toggleSwitch.checked = document.body.classList.contains('dark-mode');
    });
  } else {
    console.warn('Dark mode toggle not found');
  }
};