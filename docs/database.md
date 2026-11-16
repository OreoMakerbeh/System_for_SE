# Clean database for XAMPP, MariaDB, or MySQL

The import file is [`database/mysql/ordering-system.sql`](../database/mysql/ordering-system.sql).
It creates `albay_water_refilling_clean` with the current application schema. It
contains the order-number lock row and migration history, with no sample accounts,
customers, orders, addresses, passwords, or unrelated business data.

Your existing database stays available while you switch the application to the
clean database. The SQL file contains no `DROP`, `DELETE`, or `TRUNCATE` statements.

## XAMPP on Windows

1. Open the **XAMPP Control Panel** and start **Apache** and **MySQL**. XAMPP
   commonly uses MariaDB behind the MySQL label. Laravel's `DB_CONNECTION=mysql`
   setting works with this server.
2. Open <http://localhost/phpmyadmin> and follow the import steps below. You do not
   need a separate MySQL installation.
3. Use XAMPP's PHP 8.2 or later. Open PowerShell in the project folder containing
   `artisan`. If `php` is not available on your PATH, use the following commands
   after importing the SQL and updating `.env`:

    ```powershell
    & "C:\xampp\php\php.exe" artisan config:clear
    & "C:\xampp\php\php.exe" artisan migrate
    & "C:\xampp\php\php.exe" artisan app:make-admin
    & "C:\xampp\php\php.exe" artisan serve
    ```

    Adjust `C:\xampp` if you installed XAMPP elsewhere. Open
    <http://127.0.0.1:8000/login> to sign in to the application. phpMyAdmin manages
    the database; the application runs at the address printed by `artisan serve`.

XAMPP commonly uses the `root` database user with an empty password for a fresh
local installation. If you changed those credentials, use your own values in
`.env`. Set `DB_PORT` to the MySQL port shown in the XAMPP Control Panel; the
example uses `3306`. If PHP reports a missing driver, enable `extension=pdo_mysql`
in XAMPP's `php\php.ini` and restart the PHP process.

## Replace the database used by the application

1. In phpMyAdmin, select your current database and use **Export → SQL** to save a
   backup on your computer. Keep the export private because it contains account
   and order data.
2. Return to the phpMyAdmin server home page. Choose **Import**, select
   `database/mysql/ordering-system.sql` from the repository, and import it. The
   file creates and selects `albay_water_refilling_clean` itself. If that database
   already exists, use a different new name in both the `CREATE DATABASE` and
   `USE` lines before importing.
3. In the project's `.env`, set the following values. Use your actual MySQL
   username and password; an empty password applies only if your local MySQL
   account has no password. Enable PHP's `pdo_mysql` extension.

    ```dotenv
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=albay_water_refilling_clean
    DB_USERNAME=root
    DB_PASSWORD=
    ```

    If you chose another database name, use it for `DB_DATABASE`. Remove an old
    `DB_URL` value if one is present, because it can override these settings.

4. From the project directory, clear cached configuration and check migrations:

    ```sh
    php artisan config:clear
    php artisan migrate
    ```

    The imported database already includes the recorded migrations. Later code
    updates can add new migrations, which this command will apply.

5. Create your real administrator account:

    ```sh
    php artisan app:make-admin
    ```

    Enter the administrator's email, first name, optional middle name, last name,
    and a password of at least eight characters. Password input is hidden and the
    database stores its hash. No shared administrator password is supplied in the
    repository.

6. Start the application with `php artisan serve`. At `/login`, select
   **Administrator** and use the email and password you just entered. Confirm
   that the customer and order lists are empty. Add riders through **Riders**;
   customers register with their mobile numbers and wait for approval.

The application now uses the clean database. Keep the old database and its backup
until you have checked sign-in and ordering. You can switch back by restoring the
previous `.env` database settings and running `php artisan config:clear`.

## Tables and required records

| Tables                               | Purpose                                                                 |
| ------------------------------------ | ----------------------------------------------------------------------- |
| `users`                              | Customers, administrators, and riders; customer email is nullable       |
| `orders`                             | Container choices, quantities, delivery addresses, status, and archives |
| `delivery_updates`                   | Rider milestones and delivery notes                                     |
| `notifications`                      | Customer delivery notifications                                         |
| `order_number_sequences`             | The required row with `id = 1` used to lock order-number allocation     |
| `sessions`                           | Sign-in sessions for `SESSION_DRIVER=database`                          |
| `cache`, `cache_locks`               | Storage for `CACHE_STORE=database`                                      |
| `jobs`, `job_batches`, `failed_jobs` | Storage for `QUEUE_CONNECTION=database`                                 |
| `password_reset_tokens`              | Storage named in the Laravel password broker configuration              |
| `personal_access_tokens`             | Storage for the configured Sanctum API authentication                   |
| `migrations`                         | The applied migration history, required for later schema updates        |

The support tables are kept because the application or its configured Laravel
services use them. They start empty. Container choices and status labels are
defined in the code, so they do not need duplicate seed tables.

## Future updates and other database engines

Use `php artisan migrate` for later updates. Do not reimport the clean SQL file
over an active database. Make schema changes through new Laravel migrations;
keep the existing migrations so installed databases can upgrade.

The SQL snapshot was verified on MariaDB 10.4, commonly bundled with XAMPP, and
MySQL 8.4. To install SQLite instead, set `DB_CONNECTION=sqlite`, remove the MySQL
`DB_DATABASE` value, and run `php artisan migrate` to create
`database/database.sqlite`. The tests use SQLite.
