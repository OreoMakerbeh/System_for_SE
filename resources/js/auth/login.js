export function initializeLogin() {
    const loginForm = document.querySelector('[data-login-form]');

    if (!(loginForm instanceof HTMLFormElement)) {
        return;
    }

    const passwordInput = document.getElementById('passwordInput');
    const humanCheck = document.getElementById('humanCheck');
    const humanCheckStatus = document.getElementById('humanCheckStatus');
    const captchaDialog = document.getElementById('captchaDialog');
    const captchaDialogError = document.getElementById('captchaDialogError');
    const captchaVerify = document.getElementById('captchaVerify');
    let captchaVerified = false;

    document.getElementById('showPassword').addEventListener('change', (event) => {
        passwordInput.type = event.target.checked ? 'text' : 'password';
    });

    humanCheck.addEventListener('change', () => {
        if (humanCheck.checked && !captchaVerified) {
            humanCheck.checked = false;
            captchaDialog.showModal();
        }
    });

    document.querySelectorAll('.captcha-tile').forEach((tile) => {
        tile.addEventListener('click', () => {
            const isSelected = tile.getAttribute('aria-pressed') === 'true';

            tile.setAttribute('aria-pressed', String(!isSelected));
            tile.classList.toggle('is-selected', !isSelected);
            captchaDialogError.hidden = true;
        });
    });

    document.getElementById('captchaCancel').addEventListener('click', () => {
        captchaDialog.close();
    });

    captchaVerify.addEventListener('click', async () => {
        const selectedTiles = Array.from(document.querySelectorAll('.captcha-tile.is-selected'));

        if (selectedTiles.length === 0) {
            captchaDialogError.textContent = 'Select the matching photos first.';
            captchaDialogError.hidden = false;
            return;
        }

        captchaVerify.disabled = true;
        captchaVerify.textContent = 'Checking...';

        const formData = new FormData();
        selectedTiles.forEach((tile) => {
            formData.append('captcha_choices[]', tile.dataset.choice);
        });

        try {
            const response = await fetch(loginForm.dataset.captchaUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    Accept: 'application/json',
                },
                body: formData,
            });
            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || 'That selection does not match. Try again.');
            }

            captchaVerified = true;
            humanCheck.checked = true;
            humanCheck.disabled = true;
            humanCheckStatus.textContent = 'Verified';
            captchaDialog.close();
        } catch (error) {
            captchaDialogError.textContent = error.message;
            captchaDialogError.hidden = false;
        } finally {
            captchaVerify.disabled = false;
            captchaVerify.textContent = 'Verify';
        }
    });

    loginForm.addEventListener('submit', (event) => {
        if (!captchaVerified) {
            event.preventDefault();
            humanCheckStatus.textContent = 'Complete the human check before signing in.';
            captchaDialog.showModal();
        }
    });
}
