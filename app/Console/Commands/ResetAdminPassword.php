<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ResetAdminPassword extends Command
{
    protected $signature = 'app:reset-admin-password {email : The email address of the administrator}';

    protected $description = 'Reset the password for an existing administrator';

    public function handle(): int
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->where('role', 'admin')->first();

        if (! $user) {
            $this->error("No administrator account was found for {$email}.");

            return self::FAILURE;
        }

        $password = $this->secret('New password (at least 8 characters)');

        if (strlen($password) < 8) {
            $this->error('The password must be at least 8 characters.');

            return self::FAILURE;
        }

        $confirmation = $this->secret('Confirm new password');

        if ($password !== $confirmation) {
            $this->error('The password confirmation does not match.');

            return self::FAILURE;
        }

        $user->password = $password;
        $user->save();

        $this->info("The password for {$email} has been reset.");

        return self::SUCCESS;
    }
}
