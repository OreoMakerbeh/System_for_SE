<?php

namespace Tests\Feature\Orders;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_array_status_filter_does_not_crash_the_order_list(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get('/admin/orders?status[]=pending')
            ->assertOk();
    }

    public function test_admin_can_update_order_status_and_customer_cannot(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $customer->orders()->create([
            'quantity' => 2,
            'delivery_address' => '12 River Street',
            'status' => 'pending',
        ]);

        $this->actingAs($customer)
            ->patch('/admin/orders/'.$order->id, ['status' => 'confirmed'])
            ->assertForbidden();

        foreach (['pending', 'confirmed', 'processing', 'out_for_delivery', 'delivered'] as $status) {
            $this->actingAs($admin)
                ->patch('/admin/orders/'.$order->id, ['status' => $status])
                ->assertRedirect();

            $this->assertDatabaseHas('orders', [
                'id' => $order->id,
                'status' => $status,
            ]);
        }
    }

    public function test_admin_can_edit_order_details_without_changing_its_status(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $customer->orders()->create([
            'quantity' => 2,
            'delivery_address' => '12 River Street',
            'notes' => 'Side door',
            'status' => 'processing',
        ]);

        $this->actingAs($admin)
            ->get('/admin/orders/'.$order->id.'/edit')
            ->assertOk()
            ->assertSee('Edit order')
            ->assertSee('12 River Street');

        $this->actingAs($admin)
            ->patch('/admin/orders/'.$order->id.'/edit', [
                'quantity' => 4,
                'delivery_address' => '99 Lake Road',
                'notes' => 'Leave with reception',
            ])
            ->assertRedirect('/admin/orders')
            ->assertSessionHas('success', 'Order #'.$order->id.' details updated.');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'quantity' => 4,
            'delivery_address' => '99 Lake Road',
            'notes' => 'Leave with reception',
            'status' => 'processing',
        ]);

        $this->actingAs($customer)
            ->get('/admin/orders/'.$order->id.'/edit')
            ->assertForbidden();
    }

    public function test_cancelled_and_archived_orders_cannot_be_edited(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $cancelledOrder = $customer->orders()->create([
            'quantity' => 1,
            'delivery_address' => '12 River Street',
            'status' => 'cancelled',
        ]);
        $archivedOrder = $customer->orders()->create([
            'quantity' => 1,
            'delivery_address' => '99 Lake Road',
            'status' => 'delivered',
            'archived_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/admin/orders/'.$cancelledOrder->id.'/edit')
            ->assertForbidden();
        $this->actingAs($admin)
            ->patch('/admin/orders/'.$archivedOrder->id.'/edit', [
                'quantity' => 3,
                'delivery_address' => 'New address',
                'notes' => null,
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('orders', [
            'id' => $archivedOrder->id,
            'quantity' => 1,
            'delivery_address' => '99 Lake Road',
        ]);
    }

    public function test_cancelled_orders_cannot_be_reopened_by_an_administrator(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $customer->orders()->create([
            'quantity' => 1,
            'delivery_address' => '12 River Street',
            'status' => 'cancelled',
        ]);

        $this->actingAs($admin)
            ->patch('/admin/orders/'.$order->id, ['status' => 'processing'])
            ->assertForbidden();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'cancelled',
        ]);
    }
}
