# Working on the project

Run commands from the repository root. See [the README](../README.md) for first-time
installation and administrator account setup. The application uses Laravel 12 and
requires PHP 8.2 or later and Composer. Node.js is needed when editing or rebuilding
frontend assets; the included static bundles let the application run without it.

## Local database setup

`.env.example` defaults to SQLite. For XAMPP, start Apache and MySQL, create an
application database in phpMyAdmin, and configure `.env` with your local connection
details:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=albay_water
DB_USERNAME=root
DB_PASSWORD=
```

Replace the example database name, port, username, and password with your XAMPP
settings. Enable PHP's `pdo_mysql` extension for MySQL/MariaDB and `pdo_sqlite` for
the default test suite. On Windows, use `C:\xampp\php\php.exe` in place of `php`
if PHP is not on your PATH.

After changing an existing installation's database settings, run:

```sh
php artisan config:clear
php artisan migrate
php artisan serve
```

Open <http://127.0.0.1:8000>. Keep the local `.env` file out of Git. Migrations in
`database/migrations/` define the schema; apply pending migrations when updating
an existing installation so its records are retained.

## Folder guide

```text
app/
  Console/Commands/       Administrator setup and password reset commands
  Http/
    Controllers/
      Admin/              Orders, archives, customers, approvals, and staff
      Auth/               Sign-in, registration, and password changes
      Customer/           Customer orders, feedback, and notifications
      Rider/              Delivery claims and updates
      DashboardController.php
    Middleware/           Shared administrator access check
  Models/                 Users, orders, delivery updates, and feedback
  Notifications/          Delivery notification data
database/
  migrations/             Schema changes and data-preserving upgrades
  factories/              Test account creation
routes/
  web.php                 Shared dashboard and route groups
  auth.php                Sign-in, registration, logout, and password routes
  admin.php               Administrator routes
  customer.php            Order, feedback, and notification routes
  rider.php               Rider routes
  api.php                 Sanctum-protected current-user endpoint
resources/
  views/
    admin/                Account management, orders, approvals, and feedback
    admin/customers/      Customer summary popups in order tables
    admin/riders/         Editable rider summaries and delivery progress
    components/           Password fields and feedback rating controls
    auth/                 Login, registration, and password forms
    customer/             Customer dashboard and feedback
    orders/               Customer ordering and tracking
    rider/                Rider delivery dashboard
    layouts/              Shared page layout, assets, and pagination
  css/                    Styles grouped by component or page
  js/
    admin/                Shared customer and rider popup controls
    auth/                 Sign-in, CAPTCHA, and password visibility
    forms/                Confirmations, filters, and feedback stars
    notifications/        Delivery alerts and polling
public/
  css/app.css             Generated readable stylesheet
  js/app.js               Generated readable JavaScript bundle
  css/image/              Local login and registration background photo
  images/containers/      Local round gallon, slim gallon, and small jug photos
scripts/                  Static asset build script
tests/Feature/            Tests grouped by feature
```

`routes/web.php` applies authentication to the signed-in pages and applies
`EnsureAdministrator` to the entire administrator route group. Customer order
ownership and rider assignment checks stay close to the actions that need them.

## Where to change a feature

| Feature                  | Main files                                                                                                                                                                   | Current behavior                                                                                                                                                                                                                                       |
| ------------------------ | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Registration and sign-in | `app/Http/Controllers/Auth/`, `resources/views/auth/`                                                                                                                        | Customers and riders register with a unique mobile number and optional email. Public registrations wait for administrator approval. Customers sign in by mobile number; staff can use email or mobile number.                                          |
| Account approvals        | `app/Http/Controllers/Admin/ApprovalController.php`, `resources/views/admin/approvals/`                                                                                      | Customer and rider requests share pending and declined lists. An administrator can approve a previously declined account.                                                                                                                              |
| Orders and containers    | `app/Models/Order.php`, `app/Http/Controllers/Customer/OrderController.php`, `app/Http/Controllers/Admin/OrderController.php`                                                | Container choices and ordered fulfillment statuses are defined in `Order`. The server validates the same status choices shown by the administrator buttons.                                                                                            |
| Rider management         | `app/Http/Controllers/Admin/RiderController.php`, `resources/views/admin/riders/`                                                                                            | Approved riders have editable summaries, optional password resets, delivery totals, and up to five recent unarchived assignments. Unfinished deliveries must be completed or reassigned before removal.                                                |
| Customer summaries       | `app/Http/Controllers/DashboardController.php`, `resources/views/admin/customers/`                                                                                           | Customer information in Recent orders and All orders opens a popup. Totals include archived history; the order list follows the current table's page and filter.                                                                                       |
| Delivery tracking        | `app/Http/Controllers/Rider/DeliveryUpdateController.php`, `app/Models/DeliveryUpdate.php`, `resources/css/delivery.css`                                                     | Riders claim available processing orders and post milestones in order. Progress displays use recorded milestones and order status.                                                                                                                     |
| Feedback                 | `app/Models/Feedback.php`, customer and administrator `FeedbackController.php`, `resources/views/components/star-rating.blade.php`, `resources/js/forms/feedback-ratings.js` | New feedback requires independent overall and product-quality scores from 1 to 5 and a comment up to 2,000 characters. Customers see their own history; administrators see all submissions. Older quality scores remain null and display as Not rated. |

## Changing backend code

Keep each controller focused on one feature. Use explicit names for values and
small helpers for repeated operations. Comments should explain a rule or a reason
that is not obvious from the code.

Keep order-number allocation and status changes inside their database transactions.
Their locks prevent two requests from claiming the same number or overwriting a
newer status. `Order::availableStatusOptions()` allows the current step and later
fulfillment steps; cancelled and archived orders cannot advance. Container labels
and image paths live in `Order::CONTAINER_TYPES`.

Keep role and approval checks on the server. Public registration accepts only
customer or rider roles, and pending or declined riders must stay out of delivery
assignment choices. Feedback ownership comes from the signed-in customer.

Passwords use the `User` model's hashed cast. A blank password in a rider edit
keeps the current password. The rider form requires at least one sign-in contact
and uses a separate validation error bag for each rider so errors reopen the
correct popup. Keep passwords out of rendered values and flashed form input.

Add a new migration when changing an existing database. The current migrations
allow optional email, record declined registrations, preserve delivery updates
when riders are removed, and add nullable historical product-quality ratings.
Rider removal keeps order and delivery-update history through nullable rider
references. Customer deletion removes that customer's orders and feedback.

## Changing frontend code

Edit files under `resources/`. The stylesheet entry point, `resources/css/app.css`,
imports the component styles in their existing cascade order. `theme.css` holds the
shared theme overrides. `admin-orders.css` loads after the theme and adds
administrator status button styles.

`resources/js/app.js` initializes the page modules. Each module checks for its own
page elements before attaching listeners. Templates provide URLs and confirmation
messages through `data-` attributes, keeping JavaScript out of the markup.

`resources/js/admin/account-dialogs.js` handles native customer and rider dialogs
using `data-profile-open`, `data-profile-dialog`, and `data-profile-close`.
Shared `.profile-*` styles are in `resources/css/accounts.css`. Render one customer
dialog per customer on an order-table page, even when several rows share that
customer. Close and Escape return focus; closing a rider popup clears entered
passwords. Summaries show a page-load snapshot, so refresh for new delivery data.

Reusable Blade components in `resources/views/components/` provide password eye
buttons, clickable star ratings, and displayed feedback scores. Star inputs are
native radio groups; JavaScript adds hover previews, selection descriptions,
and a comment character count. Preserve keyboard operation and reduced-motion
styles when adjusting these controls or delivery animations.

The storefront photo is `public/css/image/albay-water-station.jpg`. Login and
registration share its `.auth-photo` styling in `resources/css/auth.css`.
Container pictures are stored in `public/images/containers/`. Keep these photos
in the repository and serve them locally.

## Building assets

Install the frontend tools once with `npm ci`, then run:

```sh
npm run format
npm run build
```

`build` generates two readable static bundles, `public/css/app.css` and
`public/js/app.js`, then builds the Vite bundle. Commit the updated static bundles
with the source changes. This keeps the PHP-only setup working without duplicating
every source file in `public/`. Use `npm run build:static` when only the static
bundles are needed. The views use these bundles by default, with versioned URLs so
browsers do not reuse old CSS or JavaScript after a change. An existing Vite
manifest or `public/hot` file does not switch the application to Vite.

For Vite live reload, set `USE_VITE_ASSETS=true` in `.env`, run
`php artisan config:clear`, and start `npm run dev` alongside the PHP server.
For a Vite production build, run `npm run build` before enabling the setting.
Set it back to `false` and clear the configuration cache to return to the included
static bundles. If a page is unstyled after updating, run `php artisan optimize:clear`
and hard-refresh the browser.

`public/build/` and `public/hot` are ignored by Git. Never edit dependencies in
`vendor/` or `node_modules/`, and make asset changes in `resources/` before
regenerating the static bundles.

## Checking changes

Use checks relevant to the files you changed. Format PHP with `composer format`
and frontend source with `npm run format`. To check formatting without editing:

```sh
composer format:check
npm run format:check
git diff --check
```

For PHP behavior changes, run `php artisan test`. `phpunit.xml` configures an
in-memory SQLite database and array-backed sessions/cache. Tests cover account
registration and approvals, authorization, customer and rider summaries, password
updates, orders, container selection, delivery milestones, archives, notifications,
feedback ratings/privacy, asset loading, and administrator commands.

Run a focused group or file while working on a feature:

```sh
php artisan test tests/Feature/Orders
php artisan test tests/Feature/Admin/CustomerProfileTest.php
php artisan test tests/Feature/Admin/RiderProfileTest.php
php artisan test tests/Feature/Feedback/FeedbackTest.php
```

For MySQL/MariaDB compatibility checks, use a separate test database:
`RefreshDatabase` recreates the schema. Review any exported `DB_*` environment
variables before testing because they can override the defaults in `phpunit.xml`.

Compile Blade views with `php artisan view:cache` after template changes. For
interactive changes, check desktop and narrow mobile layouts, keyboard access,
Close/Escape and focus return, password masking, and independent star selections.
Confirm that submitted edits and ratings appear under the correct account and
that customer and rider sessions cannot open administrator pages.

For a documentation-only edit, check the changed Markdown and its referenced
paths; an asset rebuild or application test run is unnecessary:

```sh
npx prettier --check docs/development.md
git diff --check
```
