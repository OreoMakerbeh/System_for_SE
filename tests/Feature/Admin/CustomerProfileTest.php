<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_order_tables_show_one_profile_per_customer_with_complete_history_totals(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create([
            'role' => 'customer', 'phone' => '09171234567', 'address' => '12 Customer Street',
        ]);
        foreach ([['pending', 2, null], ['delivered', 3, null], ['delivered', 4, now()]] as [$status, $quantity, $archived]) {
            $customer->orders()->create([
                'quantity' => $quantity, 'delivery_address' => '24 Delivery Street',
                'status' => $status, 'archived_at' => $archived,
            ]);
        }
        $other = User::factory()->create(['role' => 'customer']);
        $other->orders()->create(['quantity' => 1, 'delivery_address' => 'Other address', 'status' => 'confirmed']);

        foreach (['/dashboard', '/admin/orders'] as $path) {
            $response = $this->actingAs($admin)->get($path)->assertOk()
                ->assertSee('View customer details')->assertSee($customer->name)->assertSee($customer->email)
                ->assertSee('09171234567')->assertSee('12 Customer Street')->assertSee('24 Delivery Street')
                ->assertSee('Containers ordered')->assertSee('Orders in this view');
            $owner = $response->viewData('orders')->firstWhere('user_id', $customer->id)->user;
            $this->assertSame(3, $owner->orders_count);
            $this->assertSame(9, (int) $owner->orders_sum_quantity);
            $this->assertNotNull($owner->orders_max_created_at);
            $this->assertSame(1, substr_count($response->getContent(), 'id="customer-profile-'.$customer->id.'"'));
            $this->assertSame(2, substr_count($response->getContent(), 'data-profile-open="customer-profile-'.$customer->id.'"'));
            $response->assertDontSee($customer->password, false);
        }
    }

    public function test_filtered_and_paginated_orders_only_render_profiles_from_the_current_table(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $pendingCustomer = User::factory()->create(['role' => 'customer']);
        $olderOrder = $pendingCustomer->orders()->create([
            'quantity' => 1, 'delivery_address' => 'Pending address', 'status' => 'pending',
        ]);
        $olderOrder->forceFill(['created_at' => now()->subDay()])->save();
        $customer = User::factory()->create(['role' => 'customer']);
        foreach (range(1, 16) as $number) {
            $customer->orders()->create(['quantity' => $number, 'delivery_address' => 'Processing address', 'status' => 'processing']);
        }
        $this->actingAs($admin)->get('/admin/orders?status=processing&page=2')->assertOk()
            ->assertSee('id="customer-profile-'.$customer->id.'"', false)
            ->assertDontSee('id="customer-profile-'.$pendingCustomer->id.'"', false)
            ->assertViewHas('orders', fn ($orders) => $orders->count() === 1 && $orders->total() === 16);
        $this->get('/dashboard')->assertOk()
            ->assertDontSee('id="customer-profile-'.$pendingCustomer->id.'"', false)
            ->assertViewHas('orders', fn ($orders) => $orders->count() === 8);
    }

    public function test_customer_details_and_delivery_notes_are_escaped_and_passwords_are_not_rendered(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $address = '<script>alert("address")</script>';
        $note = '<img src=x onerror=alert("note")>';
        $customer = User::factory()->create(['role' => 'customer', 'address' => $address]);
        $customer->orders()->create(['quantity' => 1, 'delivery_address' => $address, 'notes' => $note, 'status' => 'pending']);

        foreach (['/dashboard', '/admin/orders'] as $path) {
            $this->actingAs($admin)->get($path)->assertOk()->assertSee($address)->assertSee($note)
                ->assertDontSee($address, false)->assertDontSee($note, false)
                ->assertDontSee($customer->password, false)->assertDontSee($customer->remember_token, false);
        }
    }

    public function test_older_accounts_with_missing_contact_details_can_be_viewed(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer', 'email' => null, 'phone' => null, 'address' => null, 'created_at' => null]);
        $customer->orders()->create(['quantity' => 1, 'delivery_address' => '12 River Street', 'status' => 'delivered']);
        $this->actingAs($admin)->get('/dashboard')->assertOk()->assertSee('Not provided')->assertSee('No contact added');
    }

    public function test_customer_profiles_are_not_rendered_on_customer_or_rider_dashboards(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $rider = User::factory()->create(['role' => 'rider']);
        $customer->orders()->create([
            'quantity' => 1, 'delivery_address' => '12 River Street', 'status' => 'processing', 'rider_id' => $rider->id,
        ]);
        foreach ([$customer, $rider] as $actor) {
            $this->actingAs($actor)->get('/dashboard')->assertOk()->assertDontSee('data-profile-open="customer-profile-', false);
            $this->get('/admin/orders')->assertForbidden();
        }
    }
}
