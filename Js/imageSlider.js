export function initializeImageSlider() {
  const heroSection = document.getElementById('hero-section');
  if (!heroSection) {
    // If the hero section isn't on the page, do nothing.
    return;
  }

  const images = [
    '../images/1.jpg',
    '../images/2.jpg',
    '../images/3.jpg',
    '../images/4.jpg',
    '../images/5.jpg',
  ];

  let currentIndex = 0;
  let isScrollingPastHero = false;

  function setHeroBackground(index) {
    // Preload the image to prevent flickering
    const img = new Image();
    img.onload = () => {
      heroSection.style.backgroundImage = `url(${images[index]})`;
    };
    img.onerror = () => {
      console.error(`Failed to load image: ${images[index]}`);
      // Optionally, skip to the next image on error
      nextImage();
    };
    img.src = images[index];
  }

  function nextImage() {
    if (isScrollingPastHero) return; // Stop if user has scrolled past
    currentIndex = (currentIndex + 1) % images.length;
    setHeroBackground(currentIndex);
  }

  // Set the initial background image
  setHeroBackground(currentIndex);

  // Change image every 4 seconds
  setInterval(nextImage, 4000);

  // Stop the slideshow when the user scrolls past the hero section to save resources
  window.addEventListener('scroll', () => {
    const heroHeight = heroSection.offsetHeight;
    isScrollingPastHero = window.scrollY > heroHeight;
  });
}
