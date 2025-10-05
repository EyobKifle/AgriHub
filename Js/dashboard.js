import { initializeDashboardCommon } from './dashboard-common.js';

/**
 * This is the main entry point for the site's dashboard JavaScript.
 * It runs on all authenticated user and admin pages.
 */
const initializeDashboard = () => {
  // 1. Initialize common scripts for all dashboard pages (e.g., hamburger menu).
  initializeDashboardCommon();

  // 2. Dynamically load and run page-specific scripts if they exist.
  const currentPage = window.location.pathname.split('/').pop().replace('.php', '');

  if (currentPage) {
    import(`./${currentPage}.js`).catch(err => console.log(`No specific script for ${currentPage}.js`));
  }
};

document.addEventListener('DOMContentLoaded', initializeDashboard);