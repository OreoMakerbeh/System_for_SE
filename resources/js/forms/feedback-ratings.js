export function initializeFeedbackRatings() {
    document.querySelectorAll('[data-star-rating]').forEach((group) => {
        const choices = [...group.querySelectorAll('[data-rating-value]')];
        const selectedValue = () => Number(group.querySelector('input:checked')?.value || 0);
        const highlight = (value) => {
            choices.forEach((choice) => {
                choice.classList.toggle(
                    'is-highlighted',
                    Number(choice.dataset.ratingValue) <= value,
                );
            });
        };

        group.classList.add('is-enhanced');
        highlight(selectedValue());

        group.addEventListener('pointerover', (event) => {
            const choice = event.target.closest('[data-rating-value]');
            if (choice) highlight(Number(choice.dataset.ratingValue));
        });
        group.addEventListener('pointerleave', () => highlight(selectedValue()));
        group.addEventListener('change', () => {
            const value = selectedValue();
            highlight(value);
            group.querySelector('[data-rating-description]').textContent =
                `${value} out of 5 · ${choices[value - 1].dataset.ratingLabel}`;
        });
    });

    const message = document.querySelector('[data-feedback-message]');
    const count = document.querySelector('[data-feedback-count]');
    if (message && count) {
        message.addEventListener('input', () => {
            count.textContent = `${Array.from(message.value).length}/2000`;
        });
    }
}
