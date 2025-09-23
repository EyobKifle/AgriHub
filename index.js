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
    // Initialize dark mode toggle
    const toggleSwitch = document.querySelector('.dark-mode-toggle');
    if (toggleSwitch) {
        toggleSwitch.addEventListener('change', () => {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('theme', document.body.classList.contains('dark-mode') ? 'dark' : 'light');
            toggleSwitch.checked = document.body.classList.contains('dark-mode');
        });

        // Apply saved theme preference
        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark-mode');
            toggleSwitch.checked = true;
        } else {
            document.body.classList.remove('dark-mode');
            toggleSwitch.checked = false;
        }
    }
}


// This function fetches the header.html file and injects it into the placeholder.
function loadHeader() {
    const headerPlaceholder = document.getElementById('header-placeholder');
    if (headerPlaceholder) {
        fetch('header.html')
            .then(response => {
                if (!response.ok){
                throw new Error('Falied to load header.html');
            } 
            return response.text();
        })
            .then(data => {
                headerPlaceholder.innerHTML = data;
                initializeHeaderScripts(); // Run the scripts for the newly loaded header
            })
           .catch(error => {
                console.error('Error loading header:', error);
            });
    }
}

// When the page content is loaded, run the function to load the header.
document.addEventListener('DOMContentLoaded', loadHeader);