import '../css/app.css';

import { initializeAccountDialogs } from './admin/account-dialogs.js';
import { initializeLogin } from './auth/login.js';
import { initializePasswordVisibility } from './auth/password-visibility.js';
import { initializeFormActions } from './forms/actions.js';
import { initializeFeedbackRatings } from './forms/feedback-ratings.js';
import { initializeDeliveryAlerts } from './notifications/delivery-alerts.js';

initializeLogin();
initializePasswordVisibility();
initializeFormActions();
initializeDeliveryAlerts();
initializeAccountDialogs();
initializeFeedbackRatings();
