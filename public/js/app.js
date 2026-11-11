import { initializeLogin } from './auth/login.js';
import { initializeFormActions } from './forms/actions.js';
import { initializeDeliveryAlerts } from './notifications/delivery-alerts.js';

initializeLogin();
initializeFormActions();
initializeDeliveryAlerts();
