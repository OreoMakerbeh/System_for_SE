export function initializeRiderDialogs() {
    document.querySelectorAll('[data-rider-open]').forEach((button) => {
        const dialog = document.getElementById(button.dataset.riderOpen);

        if (dialog instanceof HTMLDialogElement) {
            button.addEventListener('click', () => dialog.showModal());
        }
    });

    document.querySelectorAll('[data-rider-dialog]').forEach((dialog) => {
        dialog.querySelector('[data-rider-close]').addEventListener('click', () => dialog.close());
        dialog.addEventListener('close', () => {
            dialog.querySelectorAll('[data-password-toggle]').forEach((button) => {
                const input = document.getElementById(button.getAttribute('aria-controls'));
                input.value = '';
                input.type = 'password';
                const label = `Show ${button.dataset.passwordLabel}`;
                button.setAttribute('aria-pressed', 'false');
                button.setAttribute('aria-label', label);
                button.title = label;
            });
        });

        if (dialog.hasAttribute('data-open-on-load')) {
            dialog.showModal();
            dialog.querySelector('[aria-invalid="true"]')?.focus();
        }
    });
}
