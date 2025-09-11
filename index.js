const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
const navElements = document.querySelector('.elements');

mobileMenuToggle.addEventListener('click', () => {
    navElements.classList.toggle('active');
});

const dropdown = document.querySelector('.dropdown');
const dropbtn = document.querySelector('.dropbtn');

if (dropbtn) {
    dropbtn.addEventListener('click', (e) => {
        e.preventDefault();
        dropdown.classList.toggle('active');
    });

    window.addEventListener('click', (e) => {
        if (!dropdown.contains(e.target)) {
            dropdown.classList.remove('active');
        }
    });
}