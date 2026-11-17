<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RiderManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_remove_a_rider_who_can_no_longer_sign_in(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $rider = User::factory()->create(['role' => 'rider', 'phone' => '09171234567']);

        $this->actingAs($admin)->get('/admin/riders')->assertOk()
            ->assertSee(route('admin.riders.delete', $rider), false)
            ->assertSee('Remove rider')
            ->assertSee('data-confirm=', false);

        $this->delete(route('admin.riders.delete', $rider))
            ->assertRedirect(route('admin.riders'))
            ->assertSessionHas('success', 'Rider account removed. Delivery history was kept.');

        $this->assertDatabaseMissing('users', ['id' => $rider->id]);
        $this->get('/admin/riders')->assertDontSee($rider->email);
        $this->post('/logout');

        foreach ([$rider->email, $rider->phone] as $identifier) {
            $this->get('/login');
            $this->postJson(route('login.captcha.verify'), [
                'captcha_choices' => $this->app['session.store']->get('login_captcha_correct_choices'),
            ])->assertOk();
            $this->from('/login')->post('/login', [
                'role' => 'rider',
                'login' => $identifier,
                'password' => 'password',
            ])->assertRedirect('/login')->assertSessionHasErrors('login');
            $this->assertGuest();
        }
    }

    public function test_removal_preserves_completed_orders_and_their_delivery_timeline(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $rider = User::factory()->create(['role' => 'rider']);
        $orders = [];

        foreach ([['delivered', false], ['delivered', true], ['cancelled', false]] as [$status, $archived]) {
            $order = $customer->orders()->create([
                'quantity' => 2,
                'delivery_address' => '12 River Street',
                'status' => $status,
                'rider_id' => $rider->id,
                'archived_at' => $archived ? now() : null,
            ]);
            $update = $order->deliveryUpdates()->create([
                'rider_id' => $rider->id,
                'status' => 'picked_up',
                'message' => 'Collected from the refill station',
            ]);
            $orders[] = [$order, $update];
        }

        $this->actingAs($admin)->delete(route('admin.riders.delete', $rider))->assertRedirect(route('admin.riders'));

        foreach ($orders as [$order, $update]) {
            $this->assertDatabaseHas('orders', [
                'id' => $order->id, 'user_id' => $customer->id, 'status' => $order->status, 'rider_id' => null,
            ]);
            $this->assertDatabaseHas('delivery_updates', [
                'id' => $update->id, 'order_id' => $order->id, 'rider_id' => null,
                'status' => 'picked_up', 'message' => 'Collected from the refill station',
            ]);
            $this->actingAs($customer)->get(route('orders.show', $order))->assertOk()
                ->assertSee('Picked up by rider')->assertSee('Collected from the refill station')->assertSee('Removed rider');
        }
    }

    #[DataProvider('unfinishedStatuses')]
    public function test_unfinished_deliveries_prevent_removal(string $status): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $rider = User::factory()->create(['role' => 'rider']);
        $order = User::factory()->create()->orders()->create([
            'quantity' => 1, 'delivery_address' => '12 River Street', 'status' => $status, 'rider_id' => $rider->id,
        ]);

        $this->actingAs($admin)->from('/admin/riders')->delete(route('admin.riders.delete', $rider))
            ->assertRedirect('/admin/riders')->assertSessionHasErrors('rider');
        $this->assertDatabaseHas('users', ['id' => $rider->id]);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'rider_id' => $rider->id, 'status' => $status]);
        $this->get('/admin/riders')->assertOk()
            ->assertSee('Complete or reassign this rider’s unfinished deliveries before removing their account.');
    }

    public function test_rider_can_be_removed_after_an_unfinished_delivery_is_reassigned(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $rider = User::factory()->create(['role' => 'rider']);
        $replacement = User::factory()->create(['role' => 'rider']);
        $order = User::factory()->create()->orders()->create([
            'quantity' => 1, 'delivery_address' => '12 River Street', 'status' => 'processing', 'rider_id' => $rider->id,
        ]);
        $update = $order->deliveryUpdates()->create(['rider_id' => $rider->id, 'status' => 'picked_up']);

        $this->actingAs($admin)->patch(route('admin.orders.save', $order), [
            'container_type' => 'round_gallon', 'quantity' => 1,
            'delivery_address' => '12 River Street', 'rider_id' => $replacement->id,
        ])->assertRedirect();
        $this->delete(route('admin.riders.delete', $rider))->assertRedirect(route('admin.riders'));
        $this->assertDatabaseMissing('users', ['id' => $rider->id]);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'rider_id' => $replacement->id]);
        $this->assertDatabaseHas('delivery_updates', ['id' => $update->id, 'rider_id' => null]);
        $this->actingAs($replacement)->post(route('rider.orders.updates.store', $order), ['status' => 'in_transit'])
            ->assertRedirect()->assertSessionHasNoErrors();
    }

    public function test_customers_riders_and_guests_cannot_remove_riders(): void
    {
        $rider = User::factory()->create(['role' => 'rider']);
        $this->delete(route('admin.riders.delete', $rider))->assertRedirect(route('login'));
        foreach (['customer', 'rider'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->delete(route('admin.riders.delete', $rider))->assertForbidden();
        }
        $this->assertDatabaseHas('users', ['id' => $rider->id]);
    }

    public function test_rider_removal_cannot_delete_a_customer_or_administrator(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        foreach (['customer', 'admin'] as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->actingAs($admin)->delete(route('admin.riders.delete', $user))->assertNotFound();
            $this->assertDatabaseHas('users', ['id' => $user->id, 'role' => $role]);
        }
        $this->delete(route('admin.riders.delete', $admin))->assertNotFound();
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'role' => 'admin']);
    }

    public static function unfinishedStatuses(): array
    {
        return [['pending'], ['confirmed'], ['processing'], ['out_for_delivery']];
    }
}
