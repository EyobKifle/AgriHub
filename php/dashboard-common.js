/**
 * Contains common JavaScript functionality for all dashboard pages (both user and admin).
 */

export const initializeDashboardCommon = () => {
    const hamburger = document.getElementById('hamburger-menu');
    const container = document.querySelector('.dashboard-container');

    if (hamburger && container) {
        hamburger.addEventListener('click', () => {
            container.classList.toggle('sidebar-collapsed');
        });
    }
};