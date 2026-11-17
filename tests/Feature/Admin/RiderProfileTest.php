<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RiderProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profiles_show_totals_and_only_five_recent_unarchived_deliveries_per_rider(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $riders = User::factory()->count(2)->create(['role' => 'rider']);

        foreach ($riders as $rider) {
            foreach (['pending', 'confirmed', 'processing', 'out_for_delivery', 'delivered', 'delivered', 'cancelled'] as $index => $status) {
                $order = $customer->orders()->create([
                    'quantity' => 1, 'delivery_address' => '12 River Street',
                    'status' => $status, 'rider_id' => $rider->id, 'archived_at' => $index === 4 ? now() : null,
                ]);
                foreach (['picked_up', 'in_transit'] as $milestone) {
                    $order->deliveryUpdates()->create(['rider_id' => $rider->id, 'status' => $milestone]);
                }
            }
        }

        $response = $this->actingAs($admin)->get('/admin/riders')->assertOk()
            ->assertSee('View rider details')->assertSee('Recent deliveries')->assertSee('Password configuration')
            ->assertSee('--delivery-progress: 100%', false);
        foreach ($response->viewData('riders') as $rider) {
            $this->assertSame(7, $rider->assigned_deliveries_count);
            $this->assertSame(4, $rider->active_deliveries_count);
            $this->assertSame(2, $rider->completed_deliveries_count);
            $this->assertCount(5, $rider->assignedDeliveries);
            foreach ($rider->assignedDeliveries as $order) {
                $this->assertNull($order->archived_at);
                $this->assertSame($rider->id, $order->rider_id);
                $this->assertCount(1, $order->deliveryUpdates);
                $this->assertSame('in_transit', $order->deliveryUpdates->first()->status);
            }
            $response->assertDontSee($rider->password, false);
        }
    }

    public function test_admin_can_edit_contact_details_without_changing_password_or_permissions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $rider = User::factory()->create(['role' => 'rider', 'phone' => '09171234567']);
        $passwordHash = $rider->password;
        $this->actingAs($admin)->from('/admin/riders')->patch(route('admin.riders.update', $rider), [
            'first_name' => 'Updated', 'middle_name' => 'Lee', 'last_name' => 'Rider',
            'email' => '', 'phone' => '09179998888', 'address' => '24 River Street',
            'password' => '', 'password_confirmation' => '', 'role' => 'admin', 'is_approved' => false,
        ])->assertRedirect('/admin/riders')->assertSessionHasNoErrors();

        $rider->refresh();
        $this->assertSame('Updated Lee Rider', $rider->name);
        $this->assertNull($rider->email);
        $this->assertSame('09179998888', $rider->phone);
        $this->assertSame('24 River Street', $rider->address);
        $this->assertSame($passwordHash, $rider->password);
        $this->assertSame('rider', $rider->role);
        $this->assertTrue($rider->is_approved);
    }

    public function test_admin_can_reset_a_password_and_rider_can_sign_in_with_the_new_password(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $rider = User::factory()->create(['role' => 'rider', 'phone' => '09171234567']);
        $this->actingAs($admin)->patch(route('admin.riders.update', $rider), $this->details($rider) + [
            'password' => 'replacement-password', 'password_confirmation' => 'replacement-password',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $rider->refresh();
        $this->assertTrue(Hash::check('replacement-password', $rider->password));
        $this->assertFalse(Hash::check('password', $rider->password));
        $this->assertNull($rider->remember_token);
        $this->get('/admin/riders')->assertDontSee($rider->password, false)->assertDontSee('value="replacement-password"', false);
        $this->post('/logout');
        $this->withSession(['login_captcha_verified' => true])->post('/login', [
            'role' => 'rider', 'login' => $rider->phone, 'password' => 'replacement-password',
        ])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($rider);
    }

    #[DataProvider('invalidDetails')]
    public function test_invalid_changes_use_the_riders_error_bag_and_do_not_modify_the_account(array $changes, string $field): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $rider = User::factory()->create(['role' => 'rider', 'phone' => '09171234567']);
        User::factory()->create(['email' => 'taken@example.test', 'phone' => '09170000000']);
        $before = $rider->refresh()->getAttributes();
        $data = array_replace($this->details($rider), $changes, ['editing_rider' => $rider->id]);
        $this->actingAs($admin)->from('/admin/riders?page=1')->patch(route('admin.riders.update', $rider), $data)
            ->assertRedirect('/admin/riders?page=1')
            ->assertSessionHasErrorsIn('riderUpdate'.$rider->id, $field)
            ->assertSessionMissing('_old_input.password')
            ->assertSessionMissing('_old_input.password_confirmation');
        $this->assertSame($before, $rider->fresh()->getAttributes());
        $this->get('/admin/riders?page=1')->assertOk()->assertSee('data-open-on-load', false);
    }

    public function test_email_only_riders_can_keep_their_sign_in_contact(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $rider = User::factory()->create(['role' => 'rider', 'phone' => null]);
        $this->actingAs($admin)->patch(route('admin.riders.update', $rider), $this->details($rider))
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame($rider->email, $rider->fresh()->email);
        $this->assertNull($rider->fresh()->phone);
    }

    public function test_guests_customers_and_riders_cannot_edit_rider_accounts(): void
    {
        $rider = User::factory()->create(['role' => 'rider']);
        $this->patch(route('admin.riders.update', $rider), $this->details($rider))->assertRedirect('/login');
        foreach (['customer', 'rider'] as $role) {
            $actor = $role === 'rider' ? $rider : User::factory()->create(['role' => $role]);
            $this->actingAs($actor)->patch(route('admin.riders.update', $rider), $this->details($rider))->assertForbidden();
        }
    }

    public function test_edit_action_cannot_change_non_riders_or_unapproved_accounts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        foreach ([['customer', true, null], ['admin', true, null], ['rider', false, null], ['rider', false, now()]] as [$role, $approved, $declined]) {
            $user = User::factory()->create(['role' => $role, 'is_approved' => $approved, 'declined_at' => $declined]);
            $this->actingAs($admin)->patch(route('admin.riders.update', $user), $this->details($user))->assertNotFound();
        }
    }

    private function details(User $rider): array
    {
        return $rider->only(['first_name', 'middle_name', 'last_name', 'email', 'phone', 'address']);
    }

    public static function invalidDetails(): array
    {
        return [
            'duplicate email' => [['email' => 'taken@example.test'], 'email'],
            'duplicate phone' => [['phone' => '09170000000'], 'phone'],
            'missing contacts' => [['email' => '', 'phone' => ''], 'email'],
            'missing name' => [['first_name' => ''], 'first_name'],
            'malformed name' => [['first_name' => ['Invalid']], 'first_name'],
            'malformed phone' => [['phone' => ['09171234567']], 'phone'],
            'short password' => [['password' => 'short', 'password_confirmation' => 'short'], 'password'],
            'password mismatch' => [['password' => 'replacement-password', 'password_confirmation' => 'different-password'], 'password'],
            'confirmation without password' => [['password_confirmation' => 'replacement-password'], 'password'],
        ];
    }
}
