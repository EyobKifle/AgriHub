/**
 * Finds the current page's link in the main navigation and gives it an 'active' class.
 * This is used to visually highlight which page the user is currently on.
 *
 * How it works:
 * 1. It gets the current page's file name from the browser's URL.
 * 2. It loops through all the navigation links.
 * 3. If a link's destination (`href`) matches the current page's file name, it adds the 'active' class.
 */
export const highlightActiveLink = () => {
  const navLinks = document.querySelectorAll('.nav-links a');
  // Get the last part of the URL path (e.g., 'News.html').
  let currentPage = window.location.pathname.split('/').pop();

  // If the path is empty (like on the homepage), we set it to 'HomePage.html' to match the link.
  if (currentPage === '') {
    currentPage = 'HomePage.html';
  }

  navLinks.forEach(link => {
    // Check if the link's href attribute is the same as the current page's filename.
    if (link.getAttribute('href') === currentPage) {
      link.classList.add('active');
    }
  });
};