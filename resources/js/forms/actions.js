export function initializeFormActions() {
    document.addEventListener('submit', (event) => {
        const form = event.target;

        if (!(form instanceof HTMLFormElement) || !form.dataset.confirm) {
            return;
        }

        if (!window.confirm(form.dataset.confirm)) {
            event.preventDefault();
        }
    });

    document.addEventListener('change', (event) => {
        const field = event.target;

        if (field instanceof HTMLSelectElement && field.hasAttribute('data-auto-submit')) {
            field.form?.requestSubmit();
        }
    });
}
