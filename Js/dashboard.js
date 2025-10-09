import { applyTranslationsToPage, initializeI18n } from './i18n.js';

/**
 * Initializes the admin and user dashboard scripts.
 * Handles mobile menu toggling and page-specific initializations.
 */

/**
 * Toggles the visibility of the sidebar.
 */
function initializeSidebarToggle() {
    const hamburger = document.getElementById('hamburger-menu');
    const container = document.querySelector('.dashboard-container');

    if (hamburger && container) {
        hamburger.addEventListener('click', () => {
            container.classList.toggle('sidebar-collapsed');
        });
    }
}

/**
 * Reads chart data from the DOM and initializes the admin dashboard charts.
 */
function initializeAdminCharts() {
    const dataElement = document.getElementById('dashboard-data');
    if (!dataElement) return;

    const chartData = JSON.parse(dataElement.textContent);

    // 1. User Growth Chart (Line)
    const userGrowthCtx = document.getElementById('userGrowthChart');
    if (userGrowthCtx && chartData.userGrowth) {
        new Chart(userGrowthCtx, {
            type: 'line',
            data: {
                labels: chartData.userGrowth.labels,
                datasets: [{
                    label: 'New Users',
                    data: chartData.userGrowth.data,
                    fill: true,
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // 2. Listing Categories Chart (Doughnut)
    const listingCategoriesCtx = document.getElementById('listingCategoriesChart');
    if (listingCategoriesCtx && chartData.listingCategories) {
        const labels = chartData.listingCategories.map(c => c.category_name);
        const data = chartData.listingCategories.map(c => c.product_count);

        new Chart(listingCategoriesCtx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Listings',
                    data: data,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.7)',
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(255, 206, 86, 0.7)',
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(153, 102, 255, 0.7)',
                        'rgba(255, 159, 64, 0.7)'
                    ],
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
            }
        });
    }
}

/**
 * Applies the theme (light/dark) to the page by toggling a class on the root <html> element.
 * @param {string} theme - The theme to apply, either 'light' or 'dark'.
 */
function applyTheme(theme) {
    document.documentElement.classList.toggle('dark-mode', theme === 'dark');
}

/**
 * Initializes the theme based on the user's saved preference, which is read from
 * a data attribute on the body element.
 */
function initializeTheme() {
    const theme = document.body.dataset.theme || 'light';
    applyTheme(theme);
}

/**
 * Sets up the event listener for the dark mode toggle on the settings page.
 * When the toggle is changed, it applies the theme immediately.
 */
function initializeThemeToggle() {
    const darkModeToggle = document.getElementById('dark-mode-toggle');
    if (darkModeToggle) {
        darkModeToggle.addEventListener('change', () => {
            const theme = darkModeToggle.checked ? 'dark' : 'light';
            applyTheme(theme);
        });
    }
}

/**
 * Syncs the selected language from the settings page to localStorage.
 * This allows the i18n library to immediately reflect the language change
 * without requiring a page reload or re-login.
 */
function initializeLanguageSync() {
    const languageSelect = document.getElementById('language-select');
    if (languageSelect) {
        languageSelect.addEventListener('change', () => {
            localStorage.setItem('agrihub_lang', languageSelect.value);
        });
    }
}

/**
 * Handles the AJAX submission for the user settings form.
 */
function initializeSettingsForm() {
    const form = document.getElementById('settings-form');
    if (!form) return;

    const statusMessageEl = document.getElementById('settings-status-message');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const submitButton = form.querySelector('button[type="submit"]');
        const originalButtonText = submitButton.textContent;
        submitButton.disabled = true;
        submitButton.textContent = 'Saving...';

        const formData = new FormData(form);
        const newTheme = formData.get('dark_mode') ? 'dark' : 'light';
        const newLang = formData.get('language');

        try {
            const response = await fetch(window.location.pathname, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || 'An unknown error occurred.');
            }

            // Update localStorage to persist theme and language across the site
            localStorage.setItem('agrihub_theme', newTheme);
            localStorage.setItem('agrihub_lang', newLang);

            // Re-apply translations to the current page
            await initializeI18n(); // Re-load the language file if it changed
            applyTranslationsToPage();

            // Show success message
            statusMessageEl.textContent = result.message;
            statusMessageEl.className = 'alert alert-success';
            statusMessageEl.style.display = 'block';

        } catch (error) {
            // Show error message
            statusMessageEl.textContent = error.message;
            statusMessageEl.className = 'alert alert-danger';
            statusMessageEl.style.display = 'block';
        } finally {
            // Restore button
            submitButton.disabled = false;
            submitButton.textContent = originalButtonText;
        }
    });
}

/**
 * Initializes the mobile header menu toggle for the main site header.
 */
function initializeHeaderMenuToggle() {
    const menuToggle = document.querySelector('.mobile-menu-toggle');
    const header = document.querySelector('.header');

    if (menuToggle && header) {
        menuToggle.addEventListener('click', () => {
            header.classList.toggle('open');
        });
    }
}

/**
 * Main initialization function.
 */
async function init() {
    // Initialize i18n and apply translations to the static content on the page.
    await initializeI18n();
    applyTranslationsToPage();

    initializeSidebarToggle();
    initializeTheme(); // Apply theme on all dashboard pages
    
    // This is for the main site header, not the dashboard-specific one.
    // It's safe to run on all pages.
    initializeHeaderMenuToggle();

    // Page-specific initializations
    if (document.getElementById('dark-mode-toggle')) {
        initializeThemeToggle();
    }
    // Check if we are on the admin dashboard page
    if (document.getElementById('userGrowthChart')) {
        initializeAdminCharts();
    }

    // Check if we are on the settings page
    if (document.getElementById('settings-form')) {
        initializeSettingsForm();
    }
}

// Run initialization once the DOM is fully loaded.
document.addEventListener('DOMContentLoaded', init);