# Working on the project

## Folder guide

```text
app/
  Console/Commands/       Administrator setup and password reset commands
  Http/
    Controllers/
      Admin/              Orders, archives, customers, approvals, and staff
      Auth/               Sign-in, registration, and password changes
      Customer/           Customer orders and notifications
      Rider/              Delivery claims and updates
      DashboardController.php
    Middleware/           Shared administrator access check
  Models/                 Users, orders, and delivery updates
  Notifications/          Delivery notification data
database/
  migrations/             Schema updates and required order-number lock row
  mysql/                  Clean MySQL import with no sample business data
routes/
  web.php                 Shared dashboard and route groups
  auth.php                Sign-in, registration, logout, and password routes
  admin.php               Administrator routes
  customer.php            Order and notification routes
  rider.php               Rider routes
resources/
  views/                  Blade pages and shared partials
  css/                    Styles grouped by component or page
  js/
    auth/                 Sign-in and CAPTCHA interaction
    forms/                Confirmations and automatic filters
    notifications/        Delivery alerts and polling
public/                   Browser-ready files for the PHP-only setup
scripts/                  Static asset build script
tests/Feature/            Tests grouped by feature
```

`routes/web.php` applies authentication to the signed-in pages and applies
`EnsureAdministrator` to the entire administrator route group. Customer order
ownership and rider assignment checks stay close to the actions that need them.

## Changing backend code

Keep each controller focused on one feature. Use explicit names for values and
small helpers for repeated operations. Comments should explain a rule or a reason
that is not obvious from the code.

Keep order-number allocation and status changes inside their database transactions.
Their locks prevent two requests from claiming the same number or overwriting a
newer status. Do not change historical migrations to update an existing database;
add a new migration instead.

Run these checks after changing PHP:

```sh
composer format
php artisan test
```

The feature tests cover authentication, authorization, customer orders, rider
updates, archives, delivery notifications, and administrator commands. To run one
feature, use a directory such as `php artisan test tests/Feature/Orders`.

## Changing frontend code

Edit files under `resources/`. The stylesheet entry point, `resources/css/app.css`,
imports the component styles in their existing cascade order. `theme.css` holds the
shared theme overrides. `admin-orders.css` loads after the theme and adds
administrator status button styles.

`resources/js/app.js` initializes the page modules. Each module checks for its own
page elements before attaching listeners. Templates provide URLs and confirmation
messages through `data-` attributes, keeping JavaScript out of the markup.

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

Run `npm run format:check` and `composer format:check` to check formatting without
editing files. Never edit dependencies in `vendor/` or `node_modules/`.
