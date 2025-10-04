document.addEventListener('DOMContentLoaded', () => {
    // Image preview for avatar upload
    const avatarInput = document.getElementById('avatar-upload');
    const avatarPreview = document.getElementById('profile-image-preview');
    if (avatarInput && avatarPreview) {
        avatarInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                avatarPreview.src = URL.createObjectURL(file);
            }
        });
    }
});
