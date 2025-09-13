// This function will run after the header is loaded into the page.
// It finds the menu elements and makes them interactive.
function initializeHeaderScripts() {
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const navElements = document.querySelector('.elements');

    if (mobileMenuToggle && navElements) {
        mobileMenuToggle.addEventListener('click', () => {
            navElements.classList.toggle('active');
        });
    }

    const dropdown = document.querySelector('.dropdown');
    const dropbtn = document.querySelector('.dropbtn');

    if (dropdown && dropbtn) {
        dropbtn.addEventListener('click', (e) => {
            e.preventDefault(); // Prevent page from jumping to top
            dropdown.classList.toggle('active');
        });

        // Close the dropdown if the user clicks outside of it
        window.addEventListener('click', (e) => {
            if (!dropdown.contains(e.target)) {
                dropdown.classList.remove('active');
            }
        });
    }
}

// This function fetches the header.html file and injects it into the placeholder.
function loadHeader() {
    const headerPlaceholder = document.getElementById('header-placeholder');
    if (headerPlaceholder) {
        fetch('header.html')
            .then(response => response.text())
            .then(data => {
                headerPlaceholder.innerHTML = data;
                initializeHeaderScripts(); // Run the scripts for the newly loaded header
            });
    }
}

// When the page content is loaded, run the function to load the header.
document.addEventListener('DOMContentLoaded', loadHeader);