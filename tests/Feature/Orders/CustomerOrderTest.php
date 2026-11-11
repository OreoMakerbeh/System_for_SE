<?php

namespace Tests\Feature\Orders;

use App\Models\User;
use App\Notifications\OrderDeliveredNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_place_an_order_but_cannot_view_another_customers_order(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $otherCustomer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)
            ->post('/orders', [
                'quantity' => 3,
                'delivery_address' => '12 River Street',
                'notes' => 'Leave by the side door',
            ])
            ->assertRedirect();

        $order = $customer->orders()->firstOrFail();
        $this->assertSame('pending', $order->status);
        $this->assertSame(1, $order->id);
        $this->actingAs($customer)
            ->get('/orders/'.$order->id)
            ->assertOk()
            ->assertSee('Order #'.$order->id)
            ->assertSee('is-awaiting-confirmation')
            ->assertSee('--progress: 0%', false);

        $this->actingAs($otherCustomer)->get('/orders/'.$order->id)->assertNotFound();
    }

    public function test_customer_can_cancel_an_order_while_it_is_pending(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $customer->orders()->create([
            'quantity' => 2,
            'delivery_address' => '12 River Street',
            'status' => 'pending',
        ]);

        $this->actingAs($customer)
            ->patch('/orders/'.$order->id.'/cancel')
            ->assertRedirect()
            ->assertSessionHas('success', 'Order #'.$order->id.' was cancelled.');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'cancelled',
        ]);

        $this->actingAs($customer)
            ->get('/orders/'.$order->id)
            ->assertOk()
            ->assertSee('Cancelled')
            ->assertSee('This order was cancelled and will not be delivered.')
            ->assertDontSee('progress-track', false)
            ->assertDontSee('Cancel order');
    }

    public function test_customer_can_delete_completed_orders_and_reuse_their_order_numbers(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $payload = [
            'quantity' => 1,
            'delivery_address' => '12 River Street',
        ];

        foreach (range(1, 3) as $number) {
            $this->actingAs($customer)
                ->post('/orders', $payload)
                ->assertRedirect();
        }

        $orders = $customer->orders()->orderBy('order_number')->get();
        $this->assertSame([1, 2, 3], $orders->pluck('order_number')->all());

        $orders[0]->update(['status' => 'delivered']);
        $orders[1]->update(['status' => 'cancelled']);
        $orders[2]->update(['status' => 'delivered']);
        $customer->notify(new OrderDeliveredNotification($orders[0]));

        $this->actingAs($customer)
            ->delete('/orders/'.$orders[1]->id)
            ->assertRedirect('/dashboard');

        $this->assertDatabaseMissing('orders', ['id' => $orders[1]->id]);

        $this->actingAs($customer)
            ->post('/orders', $payload)
            ->assertRedirect();

        $replacement = $customer->orders()->latest('id')->firstOrFail();
        $this->assertSame(2, $replacement->order_number);

        $this->actingAs($customer)
            ->delete('/orders/'.$orders[0]->id)
            ->assertRedirect('/dashboard');

        $this->assertDatabaseMissing('orders', ['id' => $orders[0]->id]);
        $this->assertSame(0, $customer->notifications()->count());

        $this->actingAs($customer)
            ->delete('/orders/'.$orders[2]->id)
            ->assertRedirect('/dashboard');
        $replacement->update(['status' => 'cancelled']);
        $this->actingAs($customer)
            ->delete('/orders/'.$replacement->id)
            ->assertRedirect('/dashboard');

        $this->assertSame(0, $customer->orders()->count());

        $this->actingAs($customer)
            ->post('/orders', $payload)
            ->assertRedirect();

        $firstOrderAfterEmpty = $customer->orders()->latest('id')->firstOrFail();
        $this->assertSame(1, $firstOrderAfterEmpty->order_number);
    }

    public function test_customer_cannot_delete_active_or_another_customers_order(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $otherCustomer = User::factory()->create(['role' => 'customer']);
        $order = $customer->orders()->create([
            'order_number' => 1,
            'quantity' => 1,
            'delivery_address' => '12 River Street',
            'status' => 'processing',
        ]);
        $completedOrder = $customer->orders()->create([
            'order_number' => 2,
            'quantity' => 1,
            'delivery_address' => '12 River Street',
            'status' => 'delivered',
        ]);

        $this->actingAs($customer)
            ->delete('/orders/'.$order->id)
            ->assertForbidden();
        $this->actingAs($otherCustomer)
            ->delete('/orders/'.$completedOrder->id)
            ->assertNotFound();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'processing']);
        $this->assertDatabaseHas('orders', ['id' => $completedOrder->id, 'status' => 'delivered']);
    }

    public function test_customer_cannot_cancel_an_order_after_confirmation_or_cancel_another_customers_order(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $otherCustomer = User::factory()->create(['role' => 'customer']);

        foreach (['confirmed', 'processing', 'out_for_delivery', 'delivered'] as $status) {
            $order = $customer->orders()->create([
                'quantity' => 1,
                'delivery_address' => '99 Lake Road',
                'status' => $status,
            ]);

            $this->actingAs($customer)
                ->patch('/orders/'.$order->id.'/cancel')
                ->assertForbidden();

            $this->assertDatabaseHas('orders', [
                'id' => $order->id,
                'status' => $status,
            ]);
        }

        $pendingOrder = $customer->orders()->create([
            'quantity' => 1,
            'delivery_address' => '99 Lake Road',
            'status' => 'pending',
        ]);

        $this->actingAs($otherCustomer)
            ->patch('/orders/'.$pendingOrder->id.'/cancel')
            ->assertNotFound();

        $this->assertDatabaseHas('orders', [
            'id' => $pendingOrder->id,
            'status' => 'pending',
        ]);
    }
}
