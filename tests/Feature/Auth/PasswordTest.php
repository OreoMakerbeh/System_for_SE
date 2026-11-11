<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_change_password_after_confirming_current_password(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'password' => 'current-password',
        ]);

        $this->actingAs($admin)
            ->get('/account/password')
            ->assertOk()
            ->assertSee('Change your password');

        $this->actingAs($admin)
            ->put('/account/password', [
                'current_password' => 'current-password',
                'password' => 'replacement-password',
                'password_confirmation' => 'replacement-password',
            ])
            ->assertRedirect('/account/password');

        $this->assertAuthenticatedAs($admin);
        $this->assertTrue(Hash::check('replacement-password', $admin->fresh()->password));
        $this->assertFalse(Hash::check('current-password', $admin->fresh()->password));
    }

    public function test_password_change_rejects_an_incorrect_current_password(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'password' => 'current-password',
        ]);

        $this->actingAs($admin)
            ->from('/account/password')
            ->put('/account/password', [
                'current_password' => 'wrong-password',
                'password' => 'replacement-password',
                'password_confirmation' => 'replacement-password',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('current-password', $admin->fresh()->password));
    }
}
