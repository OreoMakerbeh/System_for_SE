<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CustomerApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_decline_a_registration_without_deleting_the_customer_or_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer', 'is_approved' => false]);
        $order = $customer->orders()->create(['quantity' => 1, 'delivery_address' => '12 River Street', 'status' => 'pending']);
        $feedback = $customer->feedback()->create(['rating' => 4, 'message' => 'Existing feedback.']);

        $this->actingAs($admin)->get('/admin/approvals')->assertOk()
            ->assertSee('Approve account')->assertSee('Decline account');
        $this->patch('/admin/approvals/'.$customer->id.'/decline')
            ->assertRedirect('/admin/approvals')->assertSessionHas('success');

        $this->assertFalse($customer->fresh()->is_approved);
        $this->assertNotNull($customer->fresh()->declined_at);
        $this->assertModelExists($order);
        $this->assertModelExists($feedback);
        $this->get('/admin/approvals')->assertOk()
            ->assertSee('0 pending')->assertSee('1 declined')
            ->assertSee($customer->name)->assertDontSee('Decline account');
    }

    public function test_declined_customer_cannot_sign_in(): void
    {
        User::factory()->create([
            'role' => 'customer', 'phone' => '09171234567',
            'is_approved' => false, 'declined_at' => now(),
        ]);

        $this->withSession(['login_captcha_verified' => true])->from('/login')->post('/login', [
            'role' => 'customer', 'login' => '09171234567', 'password' => 'password',
        ])->assertRedirect('/login')
            ->assertSessionHasErrors(['login' => 'Your registration was declined. Please contact the station.']);

        $this->assertGuest();
    }

    public function test_declined_account_does_not_reveal_its_status_for_a_wrong_password(): void
    {
        User::factory()->create([
            'role' => 'customer', 'phone' => '09171234567',
            'is_approved' => false, 'declined_at' => now(),
        ]);

        $this->withSession(['login_captcha_verified' => true])->from('/login')->post('/login', [
            'role' => 'customer', 'login' => '09171234567', 'password' => 'wrong-password',
        ])->assertRedirect('/login')
            ->assertSessionHasErrors(['login' => 'Those details do not match an account.']);

        $this->assertGuest();
    }

    public function test_administrator_can_approve_a_previously_declined_customer(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create([
            'role' => 'customer', 'phone' => '09171234567',
            'is_approved' => false, 'declined_at' => now(),
        ]);

        $this->actingAs($admin)->patch('/admin/approvals/'.$customer->id)->assertRedirect('/admin/approvals');

        $this->assertTrue($customer->fresh()->is_approved);
        $this->assertNull($customer->fresh()->declined_at);
        $this->get('/admin/approvals')->assertOk()->assertSee('0 declined')->assertSee('0 pending')
            ->assertViewHas('pendingAccounts', fn ($customers) => $customers->isEmpty())
            ->assertViewHas('declinedAccounts', fn ($customers) => $customers->isEmpty());
        $this->post('/logout');
        $this->withSession(['login_captcha_verified' => true])->post('/login', [
            'role' => 'customer', 'login' => '09171234567', 'password' => 'password',
        ])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($customer);
    }

    #[DataProvider('nonAdministratorRoles')]
    public function test_only_administrators_can_make_approval_decisions(string $role): void
    {
        $actor = User::factory()->create(['role' => $role]);
        $customer = User::factory()->create(['role' => 'customer', 'is_approved' => false]);

        $this->actingAs($actor)->patch('/admin/approvals/'.$customer->id)->assertForbidden();
        $this->patch('/admin/approvals/'.$customer->id.'/decline')->assertForbidden();
        $this->assertFalse($customer->fresh()->is_approved);
        $this->assertNull($customer->fresh()->declined_at);
    }

    public static function nonAdministratorRoles(): array
    {
        return [['customer'], ['rider']];
    }

    public function test_guests_cannot_make_approval_decisions(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'is_approved' => false]);

        $this->patch('/admin/approvals/'.$customer->id)->assertRedirect('/login');
        $this->patch('/admin/approvals/'.$customer->id.'/decline')->assertRedirect('/login');
        $this->assertNull($customer->fresh()->declined_at);
    }

    public function test_approved_customers_and_staff_cannot_be_declined_by_the_registration_action(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        foreach (['customer', 'admin', 'rider'] as $role) {
            $account = User::factory()->create(['role' => $role, 'is_approved' => true]);
            $this->actingAs($admin)->patch('/admin/approvals/'.$account->id.'/decline')->assertNotFound();
            $this->patch('/admin/approvals/'.$account->id)->assertNotFound();
            $this->assertTrue($account->fresh()->is_approved);
            $this->assertNull($account->fresh()->declined_at);
        }
    }

    public function test_repeated_decline_does_not_change_the_original_decision_date(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer', 'is_approved' => false, 'declined_at' => now()->subDay()]);
        $date = $customer->declined_at->toDateTimeString();

        $this->actingAs($admin)->patch('/admin/approvals/'.$customer->id.'/decline')->assertNotFound();
        $this->assertSame($date, $customer->fresh()->declined_at->toDateTimeString());
    }

    public function test_pending_and_declined_lists_have_separate_pagination(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        foreach (['customer' => 7, 'rider' => 6] as $role => $count) {
            User::factory()->count($count)->create(['role' => $role, 'is_approved' => false]);
            User::factory()->count($count)->create(['role' => $role, 'is_approved' => false, 'declined_at' => now()]);
        }

        $this->actingAs($admin)->get('/admin/approvals')->assertOk()
            ->assertViewHas('pendingAccounts', fn ($customers) => $customers->count() === 12 && $customers->total() === 13)
            ->assertViewHas('declinedAccounts', fn ($customers) => $customers->count() === 12 && $customers->total() === 13);
        $this->get('/admin/approvals?page=2&declined_page=2')->assertOk()
            ->assertViewHas('pendingAccounts', fn ($customers) => $customers->count() === 1)
            ->assertViewHas('declinedAccounts', fn ($customers) => $customers->count() === 1);
    }
}
