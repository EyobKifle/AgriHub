const initializePage = () => {
    const createBtn = document.getElementById('create-listing-btn');
    const createCard = document.getElementById('create-listing-card');
    const formTitle = document.getElementById('form-title');
    const formSubmitBtn = document.getElementById('form-submit-btn');
    const cancelEditBtn = document.getElementById('cancel-edit-btn');
    const form = createCard.querySelector('form');
    const els = {
        createBtn: document.getElementById('create-listing-btn'),
        createCard: document.getElementById('create-listing-card'),
        formTitle: document.getElementById('form-title'),
        formSubmitBtn: document.getElementById('form-submit-btn'),
        cancelEditBtn: document.getElementById('cancel-edit-btn'),
        form: document.querySelector('#create-listing-card form'),
        productIdInput: document.getElementById('product_id'),
        formTitleInput: document.getElementById('form_title'),
        formDescriptionInput: document.getElementById('form_description'),
        formCategoryIdInput: document.getElementById('form_category_id'),
        formPriceInput: document.getElementById('form_price'),
        formUnitInput: document.getElementById('form_unit'),
        formQuantityInput: document.getElementById('form_quantity'),
    };

    const resetForm = () => {
        form.reset();
        document.getElementById('product_id').value = '0';
        formTitle.textContent = 'New Listing';
        formSubmitBtn.textContent = 'Create';
        cancelEditBtn.style.display = 'none';
        els.form.reset();
        els.productIdInput.value = '0';
        els.formTitle.textContent = 'New Listing';
        els.formSubmitBtn.textContent = 'Create';
        els.cancelEditBtn.style.display = 'none';
    };

    if (createBtn && createCard) {
        createBtn.addEventListener('click', () => {
    if (els.createBtn && els.createCard) {
        els.createBtn.addEventListener('click', () => {
            resetForm();
            if (createCard.style.display === 'none' || createCard.style.display === '') {
                createCard.style.display = 'block';
                createCard.scrollIntoView({ behavior: 'smooth' });
            if (els.createCard.style.display === 'none' || els.createCard.style.display === '') {
                els.createCard.style.display = 'block';
                els.createCard.scrollIntoView({ behavior: 'smooth' });
            } else {
                createCard.style.display = 'none';
                els.createCard.style.display = 'none';
            }
        });
    }

    document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', (e) => {
            const row = e.target.closest('.listing-row');
            const data = row.dataset;
            const data = e.target.closest('.listing-row').dataset;

            document.getElementById('product_id').value = data.id;
            document.getElementById('form_title').value = data.title;
            document.getElementById('form_description').value = data.description;
            document.getElementById('form_category_id').value = data.categoryId;
            document.getElementById('form_price').value = data.price;
            document.getElementById('form_unit').value = data.unit;
            document.getElementById('form_quantity').value = data.quantityAvailable;
            els.productIdInput.value = data.id;
            els.formTitleInput.value = data.title;
            els.formDescriptionInput.value = data.description;
            els.formCategoryIdInput.value = data.categoryId;
            els.formPriceInput.value = data.price;
            els.formUnitInput.value = data.unit;
            els.formQuantityInput.value = data.quantityAvailable;

            formTitle.textContent = 'Edit Listing';
            formSubmitBtn.textContent = 'Save Changes';
            cancelEditBtn.style.display = 'inline-flex';
            createCard.style.display = 'block';
            createCard.scrollIntoView({ behavior: 'smooth' });
            els.formTitle.textContent = 'Edit Listing';
            els.formSubmitBtn.textContent = 'Save Changes';
            els.cancelEditBtn.style.display = 'inline-flex';
            els.createCard.style.display = 'block';
            els.createCard.scrollIntoView({ behavior: 'smooth' });
        });
    });

    cancelEditBtn.addEventListener('click', () => {
    els.cancelEditBtn.addEventListener('click', () => {
        resetForm();
        createCard.style.display = 'none';
        els.createCard.style.display = 'none';
    });
};

initializePage();
