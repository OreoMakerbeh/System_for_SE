<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeAdmin extends Command
{
    protected $signature = 'app:make-admin {email : The email address to promote or create}';

    protected $description = 'Create an administrator account or promote an existing customer';

    public function handle(): int
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if ($user) {
            $user->role = 'admin';
            $user->save();
            $this->info("{$email} is now an administrator.");

            return self::SUCCESS;
        }

        $firstName = trim((string) $this->ask('Administrator first name'));
        $middleName = trim((string) $this->ask('Administrator middle name (optional)', ''));
        $lastName = trim((string) $this->ask('Administrator last name'));
        $password = $this->secret('Password (at least 8 characters)');

        if ($firstName === '' || $lastName === '') {
            $this->error('The administrator first name and last name are required.');

            return self::FAILURE;
        }

        if (strlen($password) < 8) {
            $this->error('The password must be at least 8 characters.');

            return self::FAILURE;
        }

        $user = new User([
            'first_name' => $firstName,
            'middle_name' => $middleName !== '' ? $middleName : null,
            'last_name' => $lastName,
            'email' => $email,
            'password' => $password,
        ]);
        $user->role = 'admin';
        $user->save();

        $this->info("Administrator account created for {$email}.");

        return self::SUCCESS;
    }
}