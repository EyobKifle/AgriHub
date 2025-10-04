document.addEventListener('DOMContentLoaded', () => {
    const createBtn = document.getElementById('create-discussion-btn');
    const createCard = document.getElementById('create-discussion-card');
    if (createBtn && createCard) {
        createBtn.addEventListener('click', () => {
            if (createCard.style.display === 'none' || createCard.style.display === '') {
                createCard.style.display = 'block';
            } else {
                createCard.style.display = 'none';
            }
        });
    }
});
