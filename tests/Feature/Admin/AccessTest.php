<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AccessTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('adminPages')]
    public function test_customers_and_riders_cannot_open_admin_pages(string $path): void
    {
        foreach (['customer', 'rider'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get($path)->assertForbidden();
        }
    }

    #[DataProvider('adminPages')]
    public function test_guests_must_sign_in_before_opening_admin_pages(string $path): void
    {
        $this->get($path)->assertRedirect(route('login'));
    }

    #[DataProvider('nonAdminRoles')]
    public function test_staff_creation_requires_an_administrator_before_validating_input(string $role): void
    {
        $user = User::factory()->create(['role' => $role]);

        $this->actingAs($user)->post('/admin/riders')->assertForbidden();
        $this->actingAs($user)->post('/admin/administrators')->assertForbidden();
        $this->assertDatabaseCount('users', 1);
    }

    public static function adminPages(): array
    {
        return [
            'orders' => ['/admin/orders'],
            'archives' => ['/admin/archive'],
            'customers' => ['/admin/customers'],
            'approvals' => ['/admin/approvals'],
            'riders' => ['/admin/riders'],
            'administrators' => ['/admin/administrators'],
        ];
    }

    public static function nonAdminRoles(): array
    {
        return [
            'customer' => ['customer'],
            'rider' => ['rider'],
        ];
    }
}
