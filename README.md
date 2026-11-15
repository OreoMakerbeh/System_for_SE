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

- **Customers** register with their name, contact information, and delivery address. An administrator must approve the account before the customer can sign in.
- **Administrators** approve customers, manage orders, create staff accounts, and assign riders.
- **Riders** claim available processing orders and post delivery updates for their assigned orders.

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
