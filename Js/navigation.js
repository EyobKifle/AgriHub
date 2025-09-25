// Highlight the active navigation link
export const highlightActiveLink = () => {
  const navLinks = document.querySelectorAll('.nav-links a');
  let currentPage = window.location.pathname.split('/').pop() || 'index.html';

  navLinks.forEach(link => {
    if (link.getAttribute('href') === currentPage) {
      link.classList.add('active');
    }
  });
};