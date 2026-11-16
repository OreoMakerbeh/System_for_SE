<?php

namespace Tests\Feature\Console;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdministratorCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_promoting_a_pending_customer_also_approves_the_administrator_account(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'is_approved' => false,
        ]);

        $this->artisan('app:make-admin', ['email' => $customer->email])
            ->expectsOutput($customer->email.' is now an administrator.')
            ->assertSuccessful();

        $this->assertSame('admin', $customer->fresh()->role);
        $this->assertTrue($customer->fresh()->is_approved);
    }

    public function test_admin_command_promotes_an_existing_customer(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->artisan('app:make-admin', ['email' => $customer->email])
            ->expectsOutput($customer->email.' is now an administrator.')
            ->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'id' => $customer->id,
            'role' => 'admin',
        ]);
    }

    public function test_admin_command_creates_an_account_with_split_name_fields(): void
    {
        $this->artisan('app:make-admin', ['email' => 'new.admin@example.com'])
            ->expectsQuestion('Administrator first name', 'Taylor')
            ->expectsQuestion('Administrator middle name (optional)', 'Lee')
            ->expectsQuestion('Administrator last name', 'Rivera')
            ->expectsQuestion('Password (at least 8 characters)', 'secure-password')
            ->expectsOutput('Administrator account created for new.admin@example.com.')
            ->assertSuccessful();

        $administrator = User::where('email', 'new.admin@example.com')->firstOrFail();
        $this->assertSame('admin', $administrator->role);
        $this->assertSame('Taylor Lee Rivera', $administrator->name);
        $this->assertSame('Lee', $administrator->middle_name);
    }

    public function test_admin_password_reset_preserves_the_account_and_its_orders(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $admin->orders()->create([
            'quantity' => 2,
            'delivery_address' => '12 River Street',
            'status' => 'pending',
        ]);

        $this->artisan('app:reset-admin-password', ['email' => $admin->email])
            ->expectsQuestion('New password (at least 8 characters)', 'replacement-password')
            ->expectsQuestion('Confirm new password', 'replacement-password')
            ->expectsOutput('The password for '.$admin->email.' has been reset.')
            ->assertSuccessful();

        $this->assertTrue(Hash::check('replacement-password', $admin->fresh()->password));
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'user_id' => $admin->id]);
    }

    public function test_admin_password_reset_does_not_change_password_when_confirmation_differs(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'password' => 'current-password',
        ]);

        $this->artisan('app:reset-admin-password', ['email' => $admin->email])
            ->expectsQuestion('New password (at least 8 characters)', 'replacement-password')
            ->expectsQuestion('Confirm new password', 'different-password')
            ->expectsOutput('The password confirmation does not match.')
            ->assertFailed();

        $this->assertTrue(Hash::check('current-password', $admin->fresh()->password));
    }
}
