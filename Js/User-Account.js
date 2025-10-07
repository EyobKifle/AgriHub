/**
 * Page-specific JavaScript for the User Account page (User-Account.php).
 * Handles client-side interactions like the avatar preview.
 */

const initializeUserAccount = () => {
    const avatarUploadInput = document.getElementById('avatar-upload');
    const imagePreview = document.getElementById('profile-image-preview');

    if (!avatarUploadInput || !imagePreview) {
        console.warn('Avatar preview elements not found on this page.');
        return;
    }

    avatarUploadInput.addEventListener('change', () => {
        const file = avatarUploadInput.files[0];
        if (file) {
            imagePreview.src = URL.createObjectURL(file);
        }
    });
};

initializeUserAccount();
