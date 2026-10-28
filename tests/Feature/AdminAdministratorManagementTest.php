<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAdministratorManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_administrators_can_open_administrator_management(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)->get('/admin/administrators')->assertForbidden();
        $this->actingAs($customer)->post('/admin/administrators', [])->assertForbidden();
        $this->actingAs($admin)->get('/admin/administrators')->assertOk()->assertSee('Administrator accounts');
    }

    public function test_administrator_can_approve_a_pending_customer_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create([
            'role' => 'customer',
            'is_approved' => false,
        ]);

        $this->actingAs($customer)->get('/admin/approvals')->assertForbidden();
        $this->actingAs($admin)->get('/admin/approvals')
            ->assertOk()
            ->assertSee($customer->email)
            ->assertSee('Approve account');

        $this->actingAs($admin)->patch('/admin/approvals/'.$customer->id)
            ->assertRedirect('/admin/approvals');

        $this->assertDatabaseHas('users', [
            'id' => $customer->id,
            'is_approved' => true,
        ]);

        $this->post('/logout')->assertRedirect('/login');
        $this->get('/login');
        $this->postJson(route('login.captcha.verify'), [
            'captcha_choices' => $this->app['session.store']->get('login_captcha_correct_choices'),
        ])->assertOk();
        $this->post('/login', [
            'role' => 'customer',
            'email' => $customer->email,
            'password' => 'password',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($customer);
    }

    public function test_administrator_can_create_another_administrator(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/admin/administrators', [
            'first_name' => 'Taylor',
            'middle_name' => 'Lee',
            'last_name' => 'Administrator',
            'email' => 'taylor@example.com',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ])->assertRedirect();

        $created = User::where('email', 'taylor@example.com')->firstOrFail();
        $this->assertSame('admin', $created->role);
        $this->assertSame('Taylor Lee Administrator', $created->name);
        $this->assertTrue(Hash::check('secure-password', $created->password));
    }

    public function test_administrator_can_update_an_account_and_optionally_reset_its_password(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $otherAdmin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->patch('/admin/administrators/'.$otherAdmin->id, [
            'first_name' => 'Updated',
            'middle_name' => '',
            'last_name' => 'Administrator',
            'email' => 'updated@example.com',
            'password' => 'replacement-password',
            'password_confirmation' => 'replacement-password',
        ])->assertRedirect();

        $otherAdmin->refresh();
        $this->assertSame('Updated', $otherAdmin->first_name);
        $this->assertNull($otherAdmin->middle_name);
        $this->assertSame('Administrator', $otherAdmin->last_name);
        $this->assertSame('Updated Administrator', $otherAdmin->name);
        $this->assertSame('updated@example.com', $otherAdmin->email);
        $this->assertTrue(Hash::check('replacement-password', $otherAdmin->password));
    }

    public function test_administrator_can_revoke_another_administrators_access_without_deleting_the_account_or_orders(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $otherAdmin = User::factory()->create(['role' => 'admin']);
        $order = $otherAdmin->orders()->create([
            'quantity' => 2,
            'delivery_address' => '12 River Street',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->patch('/admin/administrators/'.$otherAdmin->id.'/revoke')
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $otherAdmin->id, 'role' => 'customer']);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'user_id' => $otherAdmin->id]);
    }

    public function test_administrator_cannot_revoke_their_own_access_or_the_last_administrators_access(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->patch('/admin/administrators/'.$admin->id.'/revoke')
            ->assertForbidden();

        $otherAdmin = User::factory()->create(['role' => 'admin']);
        $otherAdmin->delete();

        $this->actingAs($admin)
            ->patch('/admin/administrators/'.$admin->id.'/revoke')
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $admin->id, 'role' => 'admin']);
    }

    public function test_administrator_creation_rejects_passwords_that_do_not_match(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->from('/admin/administrators')->post('/admin/administrators', [
            'first_name' => 'Taylor',
            'middle_name' => '',
            'last_name' => 'Administrator',
            'email' => 'taylor@example.com',
            'password' => 'secure-password',
            'password_confirmation' => 'different-password',
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'taylor@example.com']);
    }

    public function test_administrator_can_update_customer_name_parts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($admin)->patch('/admin/customers/'.$customer->id, [
            'first_name' => 'Morgan',
            'middle_name' => 'Alex',
            'last_name' => 'Rivera',
            'phone' => '555-0101',
            'address' => '24 Bay Street',
        ])->assertRedirect();

        $customer->refresh();
        $this->assertSame('Morgan Alex Rivera', $customer->name);
        $this->assertSame('Alex', $customer->middle_name);
    }
}
