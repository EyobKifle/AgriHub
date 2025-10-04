let slideIndex = 0;
const slides = document.querySelector(".slider-images");
const numSlides = slides ? slides.children.length : 0;

function showSlide(index) {
    if (!slides) return;
    if (index >= numSlides) { slideIndex = 0; }
    if (index < 0) { slideIndex = numSlides - 1; }
    slides.style.transform = `translateX(-${slideIndex * 100}%)`;
}

function moveSlide(n) {
    slideIndex += n;
    showSlide(slideIndex);
}

document.addEventListener('DOMContentLoaded', () => {
    showSlide(slideIndex);
});
