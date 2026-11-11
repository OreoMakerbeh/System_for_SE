<?php

namespace Tests\Feature\Rider;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_the_assigned_rider_can_post_delivery_milestones_in_order(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $rider = User::factory()->create(['role' => 'rider']);
        $otherRider = User::factory()->create(['role' => 'rider']);
        $order = $customer->orders()->create([
            'quantity' => 1,
            'delivery_address' => '12 River Street',
            'status' => 'processing',
            'rider_id' => $rider->id,
        ]);

        $this->actingAs($otherRider)
            ->post(route('rider.orders.updates.store', $order), ['status' => 'picked_up'])
            ->assertForbidden();

        $this->actingAs($rider)
            ->post(route('rider.orders.updates.store', $order), ['status' => 'in_transit'])
            ->assertSessionHasErrors('status');

        $this->actingAs($rider)
            ->post(route('rider.orders.updates.store', $order), [
                'status' => 'picked_up',
                'message' => 'Collected from the refill station',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->actingAs($rider)
            ->post(route('rider.orders.updates.store', $order), ['status' => 'in_transit'])
            ->assertRedirect();

        $this->assertDatabaseHas('delivery_updates', [
            'order_id' => $order->id,
            'rider_id' => $rider->id,
            'status' => 'picked_up',
            'message' => 'Collected from the refill station',
        ]);
        $this->assertDatabaseHas('delivery_updates', [
            'order_id' => $order->id,
            'status' => 'in_transit',
        ]);

        $this->actingAs($customer)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Picked up by rider')
            ->assertSee('Collected from the refill station')
            ->assertSee('On the way');

        $this->actingAs($rider)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('rider-progress-track', false)
            ->assertSee('On the way')
            ->assertSee('is-current', false)
            ->assertSee('--delivery-progress:', false);

        foreach (['out_for_delivery', 'delivered'] as $milestone) {
            $this->actingAs($rider)
                ->post(route('rider.orders.updates.store', $order), ['status' => $milestone])
                ->assertRedirect();
        }

        $this->actingAs($rider)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('style="--delivery-progress: 100%"', false);

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'delivered']);
        $this->assertSame(1, $customer->notifications()->count());
    }

    public function test_admin_can_create_and_assign_a_rider_to_an_order(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $customer->orders()->create([
            'quantity' => 2,
            'delivery_address' => '12 River Street',
            'status' => 'processing',
        ]);

        $this->actingAs($customer)->get('/admin/riders')->assertForbidden();
        $this->actingAs($customer)->post('/admin/riders', [])->assertForbidden();

        $this->actingAs($admin)->get('/admin/riders')->assertOk()->assertSee('Rider accounts');
        $this->actingAs($admin)->post('/admin/riders', [
            'first_name' => 'Casey',
            'middle_name' => '',
            'last_name' => 'Rider',
            'email' => 'casey.rider@example.com',
            'phone' => '555-0199',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ])->assertRedirect();

        $rider = User::where('email', 'casey.rider@example.com')->firstOrFail();
        $this->assertSame('rider', $rider->role);

        $this->actingAs($admin)
            ->get('/admin/orders/'.$order->id.'/edit')
            ->assertOk()
            ->assertSee('Casey Rider');

        $this->actingAs($admin)->patch('/admin/orders/'.$order->id.'/edit', [
            'quantity' => 2,
            'delivery_address' => '12 River Street',
            'notes' => '',
            'rider_id' => $rider->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'rider_id' => $rider->id]);
        $this->actingAs($rider)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('My deliveries')
            ->assertSee('12 River Street')
            ->assertSee('Picked up by rider');

        $this->actingAs($rider)->get('/orders/create')->assertForbidden();
        $this->actingAs($rider)->post('/orders', [])->assertForbidden();
        $this->actingAs($rider)->get(route('orders.show', $order))->assertForbidden();
    }

    public function test_riders_can_claim_only_unassigned_processing_orders_once(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $rider = User::factory()->create(['role' => 'rider']);
        $otherRider = User::factory()->create(['role' => 'rider']);
        $admin = User::factory()->create(['role' => 'admin']);
        $availableOrder = $customer->orders()->create([
            'quantity' => 1,
            'delivery_address' => 'Available delivery address',
            'status' => 'processing',
        ]);
        $pendingOrder = $customer->orders()->create([
            'quantity' => 1,
            'delivery_address' => 'Not ready address',
            'status' => 'pending',
        ]);
        $assignedOrder = $customer->orders()->create([
            'quantity' => 1,
            'delivery_address' => 'Already assigned address',
            'status' => 'processing',
            'rider_id' => $otherRider->id,
        ]);

        $this->actingAs($customer)
            ->patch(route('rider.orders.claim', $availableOrder))
            ->assertForbidden();

        $this->actingAs($rider)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Available delivery address')
            ->assertSee('Claim delivery')
            ->assertDontSee('Not ready address')
            ->assertDontSee('Already assigned address');

        $this->actingAs($rider)
            ->patch(route('rider.orders.claim', $pendingOrder))
            ->assertSessionHasErrors('order');
        $this->actingAs($rider)
            ->patch(route('rider.orders.claim', $assignedOrder))
            ->assertSessionHasErrors('order');

        $this->actingAs($rider)
            ->patch(route('rider.orders.claim', $availableOrder))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->actingAs($otherRider)
            ->patch(route('rider.orders.claim', $availableOrder))
            ->assertSessionHasErrors('order');

        $this->assertDatabaseHas('orders', [
            'id' => $availableOrder->id,
            'rider_id' => $rider->id,
        ]);
        $this->assertDatabaseHas('orders', [
            'id' => $assignedOrder->id,
            'rider_id' => $otherRider->id,
        ]);
        $this->actingAs($otherRider)
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee('Available delivery address');
        $this->actingAs($admin)
            ->patch(route('rider.orders.claim', $availableOrder))
            ->assertForbidden();
    }
}
