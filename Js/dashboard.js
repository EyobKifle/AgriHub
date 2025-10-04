import { initializeDashboardCommon } from './dashboard-common.js';

/**
 * This is the main entry point for the site's dashboard JavaScript.
 * It runs on all authenticated user pages (e.g., User-Dashboard, User-Listings).
 */
const initializeDashboard = () => {
  // 1. Initialize common scripts for all dashboard pages (e.g., hamburger menu).
  initializeDashboardCommon();

  // 2. Dynamically load and run page-specific scripts.
  // This improves performance by only executing the code needed for the current page.
  const currentPage = window.location.pathname.split('/').pop().replace('.php', '');

  if (currentPage) {
    // Dynamically import the module for the current page.
    import(`./${currentPage}.js`).catch(err => console.log(`No specific script for ${currentPage}.js`));
  }
};

document.addEventListener('DOMContentLoaded', initializeDashboard);