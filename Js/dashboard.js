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
 * Main initialization function.
 */
function init() {
    initializeSidebarToggle();

    // Check if we are on the admin dashboard page
    if (document.getElementById('userGrowthChart')) {
        initializeAdminCharts();
    }
}

// Run initialization once the DOM is fully loaded.
document.addEventListener('DOMContentLoaded', init);