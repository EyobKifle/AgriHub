document.addEventListener('DOMContentLoaded', () => {
    const mainImage = document.getElementById('main-product-image');
    const thumbnails = document.querySelectorAll('.product-thumbnails img');

    if (!mainImage || thumbnails.length === 0) {
        return;
    }

    thumbnails.forEach(thumb => {
        thumb.addEventListener('click', () => {
            // Set main image src to the clicked thumbnail's src
            mainImage.src = thumb.src;
            // Update active state
            thumbnails.forEach(t => t.classList.remove('active'));
            thumb.classList.add('active');
        });
    });
});