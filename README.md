# Albay Water Refilling Station

A Laravel portal for placing water refill orders, managing fulfillment, and tracking deliveries.

## Run locally

You need PHP 8.2 or later, Composer, and a database supported by Laravel. Enable PHP's database extension for the database you choose. The tests use SQLite.

1. Run `composer install`.
2. Copy `.env.example` to `.env` and configure your database connection.
3. Run `php artisan key:generate`.
4. Run `php artisan migrate`.
5. Run `php artisan serve` and open <http://127.0.0.1:8000>.

The portal includes a static stylesheet at `public/css/app.css`, so Node.js is optional. To rebuild the frontend assets, run `npm ci` followed by `npm run build`.

If PHP is not on your PATH on Windows, replace `php` in these commands with the full path to your PHP executable, such as `C:\xampp\php\php.exe`.

## Accounts and access

Customers and staff use the same sign-in page at `/login`.

- **Customers** register with their name, contact information, and delivery address. An administrator must approve the account before the customer can sign in.
- **Administrators** approve customers, manage orders, create staff accounts, and assign riders.
- **Riders** claim available processing orders and post delivery updates for their assigned orders.

Create the first administrator from the project directory:

```sh
php artisan app:make-admin admin@example.com
```

Use the administrator's real email address. The command prompts for a name and password. If the email already belongs to a customer, it promotes that account and preserves its password and order history.

Additional administrators can be managed from **Administrators** after signing in. Administrators can change their password from **Password & security**. To reset an administrator's password from the command line:

```sh
php artisan app:reset-admin-password admin@example.com
```

## How orders work

1. A customer submits a quantity from 1 to 100 containers, a delivery address, and optional notes.
2. The order starts as **Pending confirmation**. The customer can cancel it while it is pending.
3. An administrator moves the order through **Confirmed → Processing → Out for delivery → Delivered**.
4. Riders post **Picked up by rider → On the way → Out for delivery → Delivered** updates. Customers see the timestamped updates on their tracking page.
5. A delivery notification is sent when the order becomes delivered. Administrators can archive delivered orders.

Administrators may skip ahead when recording work that is already complete, but cannot move an order backwards. For example, a processing order can move to out for delivery or delivered; it cannot return to pending. Choosing the current status again leaves the order unchanged and does not send another notification. Cancelled and archived orders cannot change status.

Customers can view only their own orders. They can delete delivered or cancelled orders unless an administrator has archived them. Archived orders can only be deleted by an administrator. Deleted order numbers can be reused.

## Where to find the ordering code

| File | Responsibility |
| --- | --- |
| `app/Models/Order.php` | Order relationships, status labels, and available status choices. |
| `app/Http/Controllers/OrderController.php` | Customer order creation, viewing, cancellation, and deletion. |
| `app/Http/Controllers/AdminController.php` | Staff order management and status updates. Status validation runs against the latest locked order inside a database transaction. |
| `app/Http/Controllers/DeliveryUpdateController.php` | Rider claims and sequential delivery updates. |
| `resources/views/admin/partials/order-status-actions.blade.php` | Shared status buttons used by both admin screens. |
| `resources/views/orders/show.blade.php` | Customer order tracking. |

`Order::FULFILLMENT_STATUSES` defines the ordered delivery steps. `Order::STATUSES` also includes cancelled orders for labels and filtering. `availableStatusOptions()` supplies the same choices to validation and the admin buttons, so the interface and server follow one rule. The current step stays visible with a disabled button.

## Check changes

Run all tests:

```sh
php artisan test
```

Run the status update tests only:

```sh
php artisan test --filter=OrderStatusUpdateTest
```

The tests cover forward updates, rejected backward updates, repeated delivery updates, invalid statuses, archived orders, and the choices shown on both admin screens. The existing workflow tests cover customer access, cancellation, rider milestones, and delivery notifications.
