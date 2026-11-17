<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RiderRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_links_to_rider_registration_with_the_correct_account_type_selected(): void
    {
        $this->get('/login')->assertOk()->assertSee('Create a rider account')
            ->assertSee(route('register', ['role' => 'rider']), false);
        $this->get('/register?role=rider')->assertOk()->assertViewHas('registrationRole', 'rider')
            ->assertSee('Delivery rider')->assertSee('Email address (optional)')->assertDontSee('value="admin"', false);
        $this->get('/register')->assertOk()->assertViewHas('registrationRole', 'customer');
        $this->get('/register?role=admin')->assertOk()->assertViewHas('registrationRole', 'customer');
    }

    public function test_public_rider_registration_requires_approval_and_cannot_set_privileged_fields(): void
    {
        $this->post('/register', $this->registrationData() + [
            'is_approved' => true, 'declined_at' => now(),
        ])->assertRedirect('/login')->assertSessionHasNoErrors();

        $rider = User::where('phone', '09171234567')->firstOrFail();
        $this->assertSame('rider', $rider->role);
        $this->assertSame('Jamie Rivera', $rider->name);
        $this->assertFalse($rider->is_approved);
        $this->assertNull($rider->declined_at);
        $this->assertNull($rider->email);
        $this->assertTrue(Hash::check('registration-password', $rider->password));
        $this->assertGuest();
        $this->signIn($rider, 'registration-password')->assertRedirect('/login')->assertSessionHasErrors([
            'login' => 'Your account is awaiting administrator approval.',
        ]);
        $this->assertGuest();
    }

    #[DataProvider('invalidRoles')]
    public function test_registration_rejects_roles_other_than_customer_or_rider(mixed $role): void
    {
        $data = $this->registrationData();
        $data['role'] = $role;
        $this->from('/register')->post('/register', $data)
            ->assertRedirect('/register')->assertSessionHasErrors('role');
        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    public function test_rider_and_customer_requests_share_approval_lists_with_clear_account_types(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $pendingAdmin = User::factory()->create(['role' => 'admin', 'is_approved' => false]);
        $customer = User::factory()->create(['role' => 'customer', 'is_approved' => false]);
        $rider = User::factory()->create(['role' => 'rider', 'is_approved' => false]);
        $declined = User::factory()->create(['role' => 'rider', 'is_approved' => false, 'declined_at' => now()]);
        User::factory()->create(['role' => 'rider', 'is_approved' => true]);

        $this->actingAs($admin)->get('/admin/approvals')->assertOk()
            ->assertSee('Account approvals')->assertSee('Delivery rider')->assertSee('Customer')
            ->assertViewHas('pendingAccounts', fn ($accounts) => $accounts->pluck('id')->sort()->values()->all() === [$customer->id, $rider->id])
            ->assertViewHas('declinedAccounts', fn ($accounts) => $accounts->pluck('id')->all() === [$declined->id]);
        $this->patch(route('admin.approvals.approve', $pendingAdmin))->assertNotFound();
        $this->patch(route('admin.approvals.decline', $pendingAdmin))->assertNotFound();
        $this->assertFalse($pendingAdmin->fresh()->is_approved);
    }

    public function test_admin_can_decline_a_rider_then_approve_them_without_changing_their_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $rider = User::factory()->create(['role' => 'rider', 'phone' => '09171234567', 'is_approved' => false]);
        $this->actingAs($admin)->patch(route('admin.approvals.decline', $rider))->assertRedirect('/admin/approvals');
        $this->assertFalse($rider->fresh()->is_approved);
        $this->assertNotNull($rider->fresh()->declined_at);
        $this->post('/logout');
        $this->signIn($rider)->assertRedirect('/login')->assertSessionHasErrors([
            'login' => 'Your registration was declined. Please contact the station.',
        ]);
        $this->assertGuest();

        $this->actingAs($admin)->patch(route('admin.approvals.approve', $rider))->assertRedirect('/admin/approvals');
        $this->assertTrue($rider->fresh()->is_approved);
        $this->assertNull($rider->fresh()->declined_at);
        $this->assertSame('rider', $rider->fresh()->role);
        $this->post('/logout');
        $this->signIn($rider)->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($rider);
        $this->get('/dashboard')->assertOk()->assertSee('My deliveries');
        $this->get('/admin/approvals')->assertForbidden();
    }

    public function test_only_administrators_can_approve_or_decline_a_pending_rider(): void
    {
        $rider = User::factory()->create(['role' => 'rider', 'is_approved' => false]);
        $this->patch(route('admin.approvals.approve', $rider))->assertRedirect('/login');
        $this->patch(route('admin.approvals.decline', $rider))->assertRedirect('/login');
        foreach (['customer', 'rider'] as $role) {
            $actor = $role === 'rider' ? $rider : User::factory()->create(['role' => $role]);
            $this->actingAs($actor)->patch(route('admin.approvals.approve', $rider))->assertForbidden();
            $this->patch(route('admin.approvals.decline', $rider))->assertForbidden();
        }
        $this->assertFalse($rider->fresh()->is_approved);
        $this->assertNull($rider->fresh()->declined_at);
    }

    #[DataProvider('unapprovedStates')]
    public function test_unapproved_riders_are_excluded_from_active_accounts_and_delivery_assignments(bool $declined): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $rider = User::factory()->create([
            'role' => 'rider', 'is_approved' => false, 'declined_at' => $declined ? now() : null,
        ]);
        $approved = User::factory()->create(['role' => 'rider', 'is_approved' => true]);
        $order = User::factory()->create()->orders()->create([
            'quantity' => 1, 'delivery_address' => '12 River Street', 'status' => 'processing',
        ]);
        $this->actingAs($admin)->get('/admin/riders')->assertOk()
            ->assertViewHas('riders', fn ($riders) => $riders->pluck('id')->all() === [$approved->id]);
        $this->get(route('admin.orders.edit', $order))->assertOk()
            ->assertViewHas('riders', fn ($riders) => $riders->pluck('id')->all() === [$approved->id]);
        $data = ['container_type' => 'round_gallon', 'quantity' => 1, 'delivery_address' => '12 River Street', 'rider_id' => $rider->id];
        $this->patch(route('admin.orders.save', $order), $data)->assertSessionHasErrors('rider_id');
        $this->assertNull($order->fresh()->rider_id);

        $this->patch(route('admin.approvals.approve', $rider))->assertRedirect('/admin/approvals');
        $this->patch(route('admin.orders.save', $order), $data)->assertRedirect(route('admin.orders'))->assertSessionHasNoErrors();
        $this->assertSame($rider->id, $order->fresh()->rider_id);
    }

    private function signIn(User $rider, string $password = 'password'): TestResponse
    {
        return $this->withSession(['login_captcha_verified' => true])->from('/login')->post('/login', [
            'role' => 'rider', 'login' => $rider->phone,
            'password' => $password,
        ]);
    }

    private function registrationData(): array
    {
        return [
            'role' => 'rider', 'first_name' => 'Jamie', 'last_name' => 'Rivera',
            'phone' => '09171234567', 'address' => '12 River Street',
            'password' => 'registration-password', 'password_confirmation' => 'registration-password',
        ];
    }

    public static function invalidRoles(): array
    {
        return [['admin'], ['unknown'], [''], [null], [['rider']]];
    }

    public static function unapprovedStates(): array
    {
        return [[false], [true]];
    }
}
