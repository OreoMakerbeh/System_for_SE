<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_and_admin_dashboards_render_their_workspaces(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $customer->orders()->create([
            'quantity' => 2,
            'delivery_address' => '12 River Street',
            'status' => 'pending',
        ]);
        $deliveredOrder = $customer->orders()->create([
            'quantity' => 1,
            'delivery_address' => '99 Lake Road',
            'status' => 'delivered',
        ]);

        $this->actingAs($customer)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Recent orders')
            ->assertSee('12 River Street');

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('FULFILLMENT QUEUE')
            ->assertSee($customer->name)
            ->assertSee('css/admin-orders.css', false)
            ->assertSee('Archive delivered order '.$deliveredOrder->display_number, false);

        $this->actingAs($admin)
            ->get('/admin/orders')
            ->assertOk()
            ->assertSee('css/admin-orders.css', false)
            ->assertSee('Pending confirmation')
            ->assertSee('Confirmed')
            ->assertSee('Processing')
            ->assertSee('Out for delivery')
            ->assertSee('Delivered')
            ->assertSee('name="status"', false)
            ->assertSee('Archive delivered order '.$deliveredOrder->display_number, false)
            ->assertDontSee('Current')
            ->assertDontSee('<select name="status"', false);

        $this->actingAs($admin)
            ->get('/admin/customers')
            ->assertOk()
            ->assertSee($customer->email)
            ->assertSee('Items ordered')
            ->assertSee('Last order')
            ->assertSee(now()->format('M d, Y'));

        $this->actingAs($admin)
            ->get('/admin/orders?status=pending')
            ->assertOk()
            ->assertSee('FULFILLMENT CONTROL')
            ->assertSee('1 order')
            ->assertSee('12 River Street')
            ->assertDontSee('99 Lake Road');

        $this->actingAs($customer)
            ->get('/admin/orders')
            ->assertForbidden();
    }

    public function test_the_application_redirects_to_the_dashboard(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/dashboard');
    }
}
