import './bootstrap';

const deliveryPopup = document.querySelector('.delivery-popup');

if (deliveryPopup instanceof HTMLDialogElement && deliveryPopup.open) {
    deliveryPopup.close();
    deliveryPopup.showModal();
}

const notificationPoller = document.querySelector('#delivery-notification-poller');

if (notificationPoller instanceof HTMLElement) {
    const showDeliveryAlerts = (alerts) => {
        if (alerts.length === 0) {
            return;
        }

        const dialog = document.createElement('dialog');
        dialog.className = 'delivery-popup';
        dialog.setAttribute('aria-labelledby', 'delivery-popup-heading-live');

        const content = document.createElement('div');
        content.className = 'delivery-popup-content';

        const mark = document.createElement('span');
        mark.className = 'notification-mark';
        mark.setAttribute('aria-hidden', 'true');
        mark.textContent = '✓';

        const eyebrow = document.createElement('span');
        eyebrow.className = 'eyebrow';
        eyebrow.textContent = 'DELIVERY UPDATE';

        const heading = document.createElement('h2');
        heading.id = 'delivery-popup-heading-live';
        heading.textContent = alerts.length === 1 ? 'Your order has arrived' : 'Your orders have arrived';

        const list = document.createElement('div');
        list.className = 'delivery-popup-list';

        alerts.forEach((alert) => {
            const link = document.createElement('a');
            link.className = 'delivery-popup-order';
            link.href = alert.url;

            const message = document.createElement('span');
            message.textContent = alert.message;

            const view = document.createElement('span');
            view.className = 'notification-link';
            view.textContent = 'View order →';

            link.append(message, view);
            list.append(link);
        });

        const form = document.createElement('form');
        form.method = 'dialog';

        const dismiss = document.createElement('button');
        dismiss.className = 'button button-primary delivery-popup-dismiss';
        dismiss.type = 'submit';
        dismiss.textContent = 'Got it';
        form.append(dismiss);

        content.append(mark, eyebrow, heading, list, form);
        dialog.append(content);
        document.body.append(dialog);
        dialog.addEventListener('close', () => dialog.remove(), { once: true });
        dialog.showModal();
    };

    const pollForDeliveryAlerts = () => {
        if (document.hidden) {
            return;
        }

        fetch(notificationPoller.dataset.url, {
            headers: { Accept: 'application/json' },
            cache: 'no-store',
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`Delivery notification request failed (${response.status}).`);
                }

                return response.json();
            })
            .then((data) => showDeliveryAlerts(data.alerts))
            .catch((error) => console.error(error));
    };

    window.setInterval(pollForDeliveryAlerts, 15000);
    pollForDeliveryAlerts();
}
