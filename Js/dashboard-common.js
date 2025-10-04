/**
 * Initializes common scripts for all user dashboard pages.
 */
export function initializeDashboardCommon() {
  // --- Hamburger Menu Toggle ---
  const hamburger = document.getElementById('hamburger-menu');
  const container = document.querySelector('.dashboard-container');
  hamburger?.addEventListener('click', () => {
    container?.classList.toggle('sidebar-collapsed');
  });
}