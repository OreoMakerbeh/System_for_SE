# Albay Water Refilling Station

A Laravel portal for placing water refill orders, managing fulfillment, and tracking deliveries.

## Run locally

You need PHP 8.2 or later, Composer, and a database supported by Laravel. Enable PHP's database extension for the database you choose. The tests use SQLite.

1. Run `composer install`.
2. Copy `.env.example` to `.env` and configure your database connection.
3. Run `php artisan key:generate`.
4. Run `php artisan migrate`.
5. Run `php artisan serve` and open <http://127.0.0.1:8000>.

The portal uses the included `public/css/app.css` and `public/js/app.js` bundles by default, so Node.js is optional. Asset URLs change when these files are updated, so browsers load the current version after an update. Old Vite build files or a leftover `public/hot` file do not change this default.

To rebuild the frontend assets, run `npm ci` followed by `npm run build`. To use Vite explicitly, set `USE_VITE_ASSETS=true` in `.env` and either start `npm run dev` or generate a fresh build. See [the development guide](docs/development.md) for details.

If an update leaves a page unstyled, run `php artisan optimize:clear` and refresh the browser with **Ctrl+F5**. Keep `USE_VITE_ASSETS=false` for the default PHP-only setup.

If PHP is not on your PATH on Windows, replace `php` in these commands with the full path to your PHP executable, such as `C:\xampp\php\php.exe`.

## Accounts and access

Customers and staff use the same sign-in page at `/login`.

- **Customers** register with their name, a unique phone number, and delivery address. Email is optional. An administrator must approve the account before the customer can sign in with their registered phone number and password.
- **Administrators** approve customer and rider registrations, manage orders, create staff accounts, and assign approved riders.
- **Riders** use **Create a rider account** on the sign-in page to register with a unique mobile number, name, address, and password. Email is optional. After administrator approval, riders can sign in, claim available processing orders, and post delivery updates for their assigned orders. Rider accounts created directly by an administrator are already approved.

Staff can continue signing in with their email or mobile number and password.
Choose the account role on the sign-in page to see the appropriate sign-in field.
Password fields have an eye button to show or hide that field without changing
its value. Passwords start hidden and are never filled back into a form after an
error.

In **Approvals**, administrators can choose **Approve account** or **Decline account**
for each pending customer or rider registration. The account type is shown on each card.
Declined customers and riders cannot sign in and appear under
**Declined registrations**, where an administrator can approve them later. Declining
an account preserves its details and history.

On **Riders**, select a rider's information to open their summary, edit contact details,
or set a new password. Leave both password fields blank to keep the current password.
The summary shows delivery totals and animated progress for recent assignments;
refresh the page for the latest status. Reduced-motion preferences are respected.

Run `php artisan migrate` after updating to enable optional customer email and
recorded decline decisions. Existing accounts, approval flags, passwords, orders,
and feedback are preserved. If you roll back the optional-email migration later,
first add an email to every account that has none; the rollback stops rather than
deleting those accounts.

The application starts without demo accounts or sample customer data. Create the
first administrator from the project directory:

```sh
php artisan app:make-admin admin@example.com
```

Use the administrator's real email address. The command prompts for a name and password. If the email already belongs to a customer, it promotes that account and preserves its password and order history.

Additional administrators can be managed from **Administrators** after signing in. Administrators can change their password from **Password & security**. To reset an administrator's password from the command line:

```sh
php artisan app:reset-admin-password admin@example.com
```

## How orders work

1. A customer chooses **Round gallon**, **Slim gallon**, or **Small jug**, then submits a quantity from 1 to 100 containers, a delivery address, and optional notes. All three choices use product photos stored in `public/images/containers` and served by the application.
2. The order starts as **Pending confirmation**. The customer can cancel it while it is pending.
3. An administrator moves the order through **Confirmed → Processing → Out for delivery → Delivered**.
4. Riders post **Picked up by rider → On the way → Out for delivery → Delivered** updates. Customers see the timestamped updates on their tracking page.
5. A delivery notification is sent when the order becomes delivered. Administrators can archive delivered orders.

Administrators may skip ahead when recording work that is already complete, but cannot move an order backwards. For example, a processing order can move to out for delivery or delivered; it cannot return to pending. Choosing the current status again leaves the order unchanged and does not send another notification. Cancelled and archived orders cannot change status.

Customers can view only their own orders. They can delete delivered or cancelled orders unless an administrator has archived them. Archived orders can only be deleted by an administrator. Deleted order numbers can be reused.

Run `php artisan migrate` after updating to enable container selection. Existing orders are preserved with their container type shown as **Not specified**. New orders require an explicit choice, which is shown to the customer, administrator, and rider.

## Customer feedback

Customers can open **Feedback** in the sidebar to choose clickable, highlighted
five-star ratings for **Overall experience** and **Product quality**, then write
a comment of up to 2,000 characters. Both ratings are required for new submissions.
Their feedback page shows only their own
submissions. Administrators open **Customer feedback** to read all submissions,
including the customer's name, mobile number, optional email, both ratings, and date.
The newest feedback appears first, with pagination for longer histories.

Run `php artisan migrate` after updating to add product quality ratings. Older
feedback keeps its original rating and comment, with product quality shown as **Not rated**. Existing
accounts and orders are preserved. Deleting a customer also removes their feedback,
as stated in the deletion confirmation.

## Project layout

Controllers and routes are grouped by feature: **Admin**, **Auth**, **Customer**,
and **Rider**. Blade pages live in `resources/views`, with shared assets and form
fields in partials. CSS and JavaScript live in `resources/css` and `resources/js`.
Tests are grouped by the same features under `tests/Feature`.

See [the development guide](docs/development.md) for the folder map, formatting
commands, and frontend build steps.

`Order::FULFILLMENT_STATUSES` defines the ordered delivery steps.
`Order::STATUSES` also includes cancelled orders for labels and filtering.
`availableStatusOptions()` supplies the same choices to validation and the admin
buttons, so the interface and server follow one rule. The current step stays
visible with a disabled button.

## Check changes

Run all tests:

```sh
php artisan test
```

Run the status update tests only:

```sh
php artisan test --filter=OrderStatusUpdateTest
```

The tests cover authentication, administrator access, order creation and cancellation, status changes, rider milestones, archives, and delivery notifications. They also check that invalid filters do not crash the order list and that promoting a pending customer creates an approved administrator.
