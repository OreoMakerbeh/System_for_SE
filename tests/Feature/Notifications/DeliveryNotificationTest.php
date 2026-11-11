<?php

namespace Tests\Feature\Notifications;

use App\Models\User;
use App\Notifications\OrderDeliveredNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_delete_only_their_own_notifications(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $otherCustomer = User::factory()->create(['role' => 'customer']);
        $order = $customer->orders()->create([
            'order_number' => 1,
            'quantity' => 1,
            'delivery_address' => '12 River Street',
            'status' => 'delivered',
        ]);
        $customer->notify(new OrderDeliveredNotification($order));
        $notification = $customer->notifications()->firstOrFail();

        $this->actingAs($otherCustomer)
            ->delete('/notifications/'.$notification->id)
            ->assertNotFound();
        $this->assertSame(1, $customer->notifications()->count());

        $this->actingAs($customer)
            ->delete('/notifications/'.$notification->id)
            ->assertRedirect()
            ->assertSessionHas('success', 'Notification deleted.');

        $this->assertSame(0, $customer->notifications()->count());
        $this->assertDatabaseHas('orders', ['id' => $order->id]);
    }

    public function test_customer_is_notified_when_an_order_is_delivered_once(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $customer->orders()->create([
            'quantity' => 2,
            'delivery_address' => '12 River Street',
            'status' => 'out_for_delivery',
        ]);

        $this->actingAs($admin)
            ->patch('/admin/orders/'.$order->id, ['status' => 'delivered'])
            ->assertRedirect();

        $notification = $customer->notifications()->firstOrFail();
        $this->assertSame([
            'order_id' => $order->id,
            'message' => 'Your order #'.$order->id.' has been delivered.',
        ], $notification->data);

        $this->actingAs($admin)
            ->patch('/admin/orders/'.$order->id, ['status' => 'delivered'])
            ->assertRedirect();

        $this->assertSame(1, $customer->notifications()->count());

        $this->actingAs($customer)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('dialog', false)
            ->assertSee('Your order has arrived')
            ->assertSee('Got it')
            ->assertSee('Notifications')
            ->assertSee('Your order #'.$order->id.' has been delivered.')
            ->assertSee('/orders/'.$order->id);

        $this->assertNotNull($notification->fresh()->read_at);

        $this->actingAs($customer)
            ->get('/orders/'.$order->id)
            ->assertOk()
            ->assertSee('is-delivered')
            ->assertSee('--progress: 100%', false);

        $this->actingAs($customer)
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee('dialog', false)
            ->assertSee('Your order #'.$order->id.' has been delivered.');
    }

    public function test_customer_can_receive_delivery_alerts_while_using_the_app(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $customer->orders()->create([
            'quantity' => 1,
            'delivery_address' => '99 Lake Road',
            'status' => 'out_for_delivery',
        ]);

        $this->actingAs($admin)
            ->patch('/admin/orders/'.$order->id, ['status' => 'delivered'])
            ->assertRedirect();

        $this->actingAs($customer)
            ->getJson('/notifications/unread')
            ->assertOk()
            ->assertJsonPath('alerts.0.message', 'Your order #'.$order->id.' has been delivered.')
            ->assertJsonPath('alerts.0.url', route('orders.show', $order));

        $this->assertNotNull($customer->notifications()->firstOrFail()->fresh()->read_at);

        $this->actingAs($customer)
            ->getJson('/notifications/unread')
            ->assertOk()
            ->assertExactJson(['alerts' => []]);
    }
}
