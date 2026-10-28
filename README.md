# Albay Water Refilling Station

A Laravel customer and operations portal for water-container refill orders.

## Requirements

- PHP 8.2 or later with the extensions required by Laravel
- Composer
- A configured Laravel database
- Node.js and npm only when rebuilding the Vite assets

## Local setup

1. Install PHP dependencies with `composer install`.
2. Create `.env` from `.env.example` if needed, then set the database connection.
3. Generate the application key with `php artisan key:generate`.
4. Apply migrations with `php artisan migrate`.
5. Start Laravel with `php artisan serve` and open `http://127.0.0.1:8000`.

The stylesheet is available as a static asset at `public/css/app.css`, so the portal can run without Node. If you change frontend assets, install npm dependencies and run `npm run build` to use Vite's production bundle.

## Administrator account

Customers and administrators use the same sign-in page at `/login`. Public registration creates customer accounts only. From the project directory, create an administrator with:

```powershell
C:\xampp\php\php.exe artisan app:make-admin admin@example.com
```

The command prompts for the administrator's first, middle (optional), and last names, followed by a password. Replace `admin@example.com` with the administrator's real email address. If that email already belongs to a customer, the command promotes the account without changing its password. After setup, sign in at `/login`; the administrator dashboard and controls appear automatically. Change the password from **Password & security** after signing in.

Administrators can create and update additional administrator accounts from **Administrators** in the admin navigation. This page is only available to signed-in administrators. It can also revoke another administrator's access while preserving that account and its order history; the current administrator and the last remaining administrator are protected.

If you cannot sign in and need to reset an existing administrator's password, run:

```powershell
C:\xampp\php\php.exe artisan app:reset-admin-password admin@example.com
```

Replace the email with the administrator's real email address. The command verifies the account is already an administrator and prompts for a new password and confirmation. It preserves the account and its order history.

## Workflows

- Customers register with first, optional middle, and last names plus contact and delivery details, place refill orders, and track confirmation, processing, delivery, and completion.
- Administrators review recent orders, update fulfillment status, edit or remove customer records, create rider accounts, and assign riders to orders.
- Riders see orders in processing that have no assigned rider, claim deliveries for their route, and post sequential pickup, transit, out-for-delivery, and delivered updates. Customers see the rider's timestamped updates on their order tracking page.
- Customer order history is private to its owner. Administration routes require an authenticated administrator.

Run the test suite with `php artisan test`.
<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

ban123@gmail.com
admin12345678910

cd C:/xampp/htdocs/System_for_SE