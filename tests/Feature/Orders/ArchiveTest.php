<?php

namespace Tests\Feature\Orders;

use App\Models\User;
use App\Notifications\OrderDeliveredNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_archive_delivered_orders_and_delete_archived_data(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $deliveredOrder = $customer->orders()->create([
            'order_number' => 1,
            'quantity' => 2,
            'delivery_address' => '12 River Street',
            'status' => 'delivered',
        ]);
        $activeOrder = $customer->orders()->create([
            'order_number' => 2,
            'quantity' => 1,
            'delivery_address' => '99 Lake Road',
            'status' => 'processing',
        ]);
        $customer->notify(new OrderDeliveredNotification($deliveredOrder));

        $this->actingAs($customer)
            ->patch('/admin/orders/'.$deliveredOrder->id.'/archive')
            ->assertForbidden();

        $this->actingAs($admin)
            ->patch('/admin/orders/'.$activeOrder->id.'/archive')
            ->assertForbidden();
        $this->assertNull($activeOrder->fresh()->archived_at);

        $this->actingAs($admin)
            ->get('/admin/archive')
            ->assertOk()
            ->assertSee('Customer Data')
            ->assertDontSee('#1');

        $this->actingAs($admin)
            ->patch('/admin/orders/'.$deliveredOrder->id.'/archive')
            ->assertRedirect()
            ->assertSessionHas('success', 'Order #1 was added to Customer Data.');

        $this->assertNotNull($deliveredOrder->fresh()->archived_at);
        $this->assertDatabaseHas('orders', ['id' => $deliveredOrder->id]);

        $this->actingAs($admin)
            ->get('/admin/orders')
            ->assertOk()
            ->assertDontSee('<td class="order-number">#1</td>', false)
            ->assertSee('<td class="order-number">#2</td>', false);
        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee('<td class="order-number">#1</td>', false)
            ->assertSee('<td class="order-number">#2</td>', false);
        $this->actingAs($customer)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('#1');

        $this->actingAs($customer)
            ->delete('/orders/'.$deliveredOrder->id)
            ->assertForbidden();
        $this->assertNotNull($deliveredOrder->fresh()->archived_at);

        $this->actingAs($admin)
            ->delete('/admin/archive/'.$deliveredOrder->id)
            ->assertRedirect()
            ->assertSessionHas('success', 'Archived order #1 was permanently deleted.');

        $this->assertDatabaseMissing('orders', ['id' => $deliveredOrder->id]);
        $this->assertSame(0, $customer->notifications()->count());
    }

    public function test_admin_cannot_delete_an_order_before_it_is_archived(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $customer->orders()->create([
            'order_number' => 1,
            'quantity' => 2,
            'delivery_address' => '12 River Street',
            'status' => 'delivered',
        ]);

        $this->actingAs($admin)
            ->delete('/admin/archive/'.$order->id)
            ->assertForbidden();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'delivered']);
    }
}
