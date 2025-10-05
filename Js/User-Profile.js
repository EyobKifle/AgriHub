const els = {
    form: document.getElementById('profile-form'),
    statusMessage: document.getElementById('form-status-message'),
    avatarInput: document.getElementById('avatar-upload'),
    avatarPreview: document.getElementById('profile-image-preview'),
    // Input fields
    fullName: document.getElementById('full-name'),
    email: document.getElementById('email'),
    phone: document.getElementById('phone'),
    location: document.getElementById('location'),
    bio: document.getElementById('bio'),
    farmSize: document.getElementById('farm-size'),
    experience: document.getElementById('experience'),
    specialization: document.getElementById('specialization'),
    businessName: document.getElementById('business-name'),
    businessAddress: document.getElementById('business-address'),
    languagePreference: document.getElementById('language-preference'),
    themePreference: document.getElementById('theme-preference'),
    emailNotifications: document.getElementById('email-notifications'),
    existingAvatarUrl: document.getElementById('existing_avatar_url'),
};

async function fetchData() {
    try {
        const response = await fetch('../php/api/profile.php');
        if (!response.ok) {
            if (response.status === 401) window.location.href = '../HTML/guest/Login.html';
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();
        renderPage(data);
    } catch (error) {
        console.error('Error fetching profile data:', error);
        showStatus('Failed to load profile data.', '#900');
    }
}

function renderPage(data) {
    if (!data) return;

    els.fullName.value = data.name || '';
    els.email.value = data.email || '';
    els.phone.value = data.phone || '';
    els.location.value = data.location || '';
    els.bio.value = data.bio || '';
    els.farmSize.value = data.farm_size_hectares || '';
    els.experience.value = data.experience_years || '';
    els.specialization.value = data.specialization || '';
    els.businessName.value = data.business_name || '';
    els.businessAddress.value = data.business_address || '';
    els.languagePreference.value = data.language_preference || 'en';
    els.themePreference.value = data.pref_theme || 'light';
    els.emailNotifications.checked = data.pref_email_notifications;
    
    if (data.avatar_url) {
        els.avatarPreview.src = `../${data.avatar_url}`;
        els.existingAvatarUrl.value = data.avatar_url;
    } else {
        const initial = (data.name || 'U').charAt(0).toUpperCase();
        els.avatarPreview.src = `https://placehold.co/150x150/2a9d8f/FFF?text=${encodeURIComponent(initial)}`;
    }
}

async function handleFormSubmit(e) {
    e.preventDefault();
    const formData = new FormData(els.form);
    showStatus('Saving...', '#555');

    try {
        const response = await fetch('../php/api/profile.php', { method: 'POST', body: formData });
        const result = await response.json();
        if (!response.ok) throw new Error(result.error || 'An unknown error occurred.');
        
        showStatus(result.message, '#090');
        // Optionally, refresh parts of the page like the header avatar if it changed
    } catch (error) {
        showStatus(`Error: ${error.message}`, '#900');
    }
}

function showStatus(message, color) {
    els.statusMessage.textContent = message;
    els.statusMessage.style.color = color;
    setTimeout(() => { els.statusMessage.textContent = ''; }, 5000);
}

function initializeEventListeners() {
    if (els.form) {
        els.form.addEventListener('submit', handleFormSubmit);
    }

    if (els.avatarInput && els.avatarPreview) {
        els.avatarInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                els.avatarPreview.src = URL.createObjectURL(file);
            }
        });
    }
}

function init() {
    if (!document.getElementById('profile-form')) return; // Only run on the profile page
    
    initializeEventListeners();
    fetchData();
}

init();
