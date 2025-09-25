let headerCache = null;

// Load header.html into the header-placeholder
export const loadHeader = async () => {
  const placeholder = document.getElementById('header-placeholder');
  if (!placeholder) {
    console.error('Header placeholder not found');
    return;
  }

  try {
    if (headerCache) {
      placeholder.innerHTML = headerCache;
      return;
    }

    const response = await fetch('header.html');
    if (!response.ok) {
      throw new Error(`Failed to load header.html: ${response.status}`);
    }
    const data = await response.text();
    placeholder.innerHTML = data;
    headerCache = data;
  } catch (error) {
    console.error('Error loading header:', error);
    placeholder.innerHTML = '<p>Error loading header. Please try again later.</p>';
  }
};

// Initialize header interactivity (mobile menu, dropdown, dark mode)
export const initializeHeaderScripts = () => {
  // Mobile menu toggle
  const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
  const navContainer = document.querySelector('.elements') || document.querySelector('.header');
  if (mobileMenuToggle && navContainer) {
    mobileMenuToggle.addEventListener('click', () => {
      navContainer.classList.toggle(navContainer.classList.contains('header') ? 'open' : 'active');
    });
  } else {
    console.warn('Mobile menu elements not found');
  }

  // Dropdown menu
  const dropdown = document.querySelector('.dropdown');
  const dropbtn = document.querySelector('.dropbtn');
  if (dropdown && dropbtn) {
    dropbtn.addEventListener('click', (e) => {
      e.preventDefault();
      dropdown.classList.toggle('active');
    });

    window.addEventListener('click', (e) => {
      if (!dropdown.contains(e.target)) {
        dropdown.classList.remove('active');
      }
    });
  } else {
    console.warn('Dropdown elements not found');
  }

  // Dark mode toggle
  const toggleSwitch = document.querySelector('.dark-mode-toggle');
  if (toggleSwitch) {
    if (localStorage.getItem('theme') === 'dark') {
      document.body.classList.add('dark-mode');
      toggleSwitch.checked = true;
    }

    toggleSwitch.addEventListener('change', () => {
      document.body.classList.toggle('dark-mode');
      localStorage.setItem('theme', document.body.classList.contains('dark-mode') ? 'dark' : 'light');
      toggleSwitch.checked = document.body.classList.contains('dark-mode');
    });
  } else {
    console.warn('Dark mode toggle not found');
  }
};