import '../css/app.css';

import { initializeLogin } from './auth/login.js';
import { initializePasswordVisibility } from './auth/password-visibility.js';
import { initializeFormActions } from './forms/actions.js';
import { initializeDeliveryAlerts } from './notifications/delivery-alerts.js';

initializeLogin();
initializePasswordVisibility();
initializeFormActions();
initializeDeliveryAlerts();
