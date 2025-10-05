import { initializeDashboardCommon } from './dashboard-common.js';
import { initializeI18n, applyTranslationsToPage, updateLangSwitcher } from './i18n.js';

/**
 * This is the main entry point for the site's dashboard JavaScript.
 * It runs on all authenticated user and admin pages.
 */
const initializeDashboard = async () => {
  // 1. Initialize i18n first so static labels render correctly.
  await initializeI18n();
  applyTranslationsToPage();
  updateLangSwitcher();

  // 2. Initialize common scripts for all dashboard pages (e.g., hamburger menu).
  initializeDashboardCommon();

  // 3. Dynamically load and run page-specific scripts if they exist.
  const currentPage = window.location.pathname
    .split('/')
    .pop()
    .replace(/\.(php|html)$/i, '');

  if (currentPage) {
    import(`./${currentPage}.js`).catch(err => console.log(`No specific script for ${currentPage}.js`));
  }
};

document.addEventListener('DOMContentLoaded', initializeDashboard);