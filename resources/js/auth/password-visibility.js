export function initializePasswordVisibility() {
    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        const input = document.getElementById(button.getAttribute('aria-controls'));

        if (!(input instanceof HTMLInputElement)) {
            return;
        }

        button.hidden = false;
        button.addEventListener('click', () => {
            const showPassword = input.type === 'password';
            const action = showPassword ? 'Hide' : 'Show';
            const label = `${action} ${button.dataset.passwordLabel}`;

            input.type = showPassword ? 'text' : 'password';
            button.setAttribute('aria-pressed', String(showPassword));
            button.setAttribute('aria-label', label);
            button.title = label;
        });
    });
}
