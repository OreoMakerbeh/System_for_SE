<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\OrderDeliveredNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OrderWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_a_pending_customer_who_cannot_sign_in_yet(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertSee('First name')
            ->assertSee('Middle name')
            ->assertSee('Last name');

        $response = $this->post('/register', [
            'first_name' => 'Jamie',
            'middle_name' => 'Lee',
            'last_name' => 'Rivera',
            'email' => 'jamie@example.com',
            'phone' => '555-0100',
            'address' => '12 River Street',
            'password' => 'water1234',
            'password_confirmation' => 'water1234',
        ]);

        $user = User::where('email', 'jamie@example.com')->firstOrFail();

        $response->assertRedirect('/login')
            ->assertSessionHas('success', 'Your registration was submitted. You can sign in after an administrator approves your account.');
        $this->assertGuest();
        $this->assertSame('customer', $user->role);
        $this->assertFalse($user->is_approved);
        $this->assertSame('Jamie', $user->first_name);
        $this->assertSame('Lee', $user->middle_name);
        $this->assertSame('Rivera', $user->last_name);
        $this->assertSame('Jamie Lee Rivera', $user->name);
        $this->assertSame('555-0100', $user->phone);

        $this->get('/login');
        $this->verifyLoginCaptcha();
        $this->from('/login')->post('/login', [
            'role' => 'customer',
            'email' => $user->email,
            'password' => 'water1234',
        ])->assertRedirect('/login')->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_shared_sign_in_accepts_customer_rider_and_admin_accounts(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $rider = User::factory()->create(['role' => 'rider']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->get('/login')
            ->assertOk()
            ->assertSee('Sign in')
            ->assertSee('name="role"', false)
            ->assertSee('Delivery rider');
        $this->get('/admin/login')->assertNotFound();
        $this->verifyLoginCaptcha();

        $this->post('/login', [
            'role' => 'customer',
            'email' => $customer->email,
            'password' => 'password',
        ])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($customer);

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();

        $this->get('/login');
        $this->verifyLoginCaptcha();
        $this->post('/login', [
            'role' => 'admin',
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($admin);

        $this->post('/logout')->assertRedirect('/login');
        $this->get('/login');
        $this->verifyLoginCaptcha();
        $this->post('/login', [
            'role' => 'rider',
            'email' => $rider->email,
            'password' => 'password',
        ])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($rider);
    }

    public function test_sign_in_requires_the_selected_role_to_match_the_account(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->get('/login');
        $this->verifyLoginCaptcha();

        $this->from('/login')->post('/login', [
            'role' => 'admin',
            'email' => $customer->email,
            'password' => 'password',
        ])->assertRedirect('/login')->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_customer_can_sign_in_with_a_unique_mobile_number(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'phone' => '555-0100',
        ]);

        $this->get('/login')->assertOk()->assertSee('Email or mobile number');
        $this->verifyLoginCaptcha();

        $this->post('/login', [
            'role' => 'customer',
            'email' => '555-0100',
            'password' => 'password',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($customer);

        $this->post('/logout')->assertRedirect('/login');

        User::factory()->create(['phone' => '555-0100']);
        $this->get('/login');
        $this->verifyLoginCaptcha();

        $this->from('/login')->post('/login', [
            'role' => 'customer',
            'email' => '555-0100',
            'password' => 'password',
        ])->assertRedirect('/login')->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_requires_a_valid_human_check_before_authenticating(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->get('/login')
            ->assertOk()
            ->assertSee("I'm not a robot", false)
            ->assertSee('Select the matching photo')
            ->assertSee('Select photo 9')
            ->assertSee('captcha-tile-grid', false);

        $captchaChoices = $this->captchaChoices();
        $wrongChoices = $captchaChoices;
        $wrongChoices[0] = str_repeat('f', 32);

        $this->postJson(route('login.captcha.verify'), [
            'captcha_choices' => $wrongChoices,
        ])->assertUnprocessable();

        $this->from('/login')->post('/login', [
            'role' => 'customer',
            'email' => $customer->email,
            'password' => 'password',
        ])->assertRedirect('/login')->assertSessionHasErrors('captcha');

        $this->assertGuest();

        $this->postJson(route('login.captcha.verify'), [
            'captcha_choices' => $captchaChoices,
        ])->assertOk();

        $this->post('/login', [
            'role' => 'customer',
            'email' => $customer->email,
            'password' => 'password',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($customer);
    }

    private function captchaChoices(): array
    {
        return $this->app['session.store']->get('login_captcha_correct_choices');
    }

    public function test_login_challenge_target_changes_between_page_loads(): void
    {
        $this->get('/login')->assertOk();
        $firstTarget = $this->app['session.store']->get('login_captcha_target');
        $firstCorrectChoices = $this->captchaChoices();

        $this->get('/login')->assertOk();
        $secondTarget = $this->app['session.store']->get('login_captcha_target');
        $secondCorrectChoices = $this->captchaChoices();

        $this->assertNotSame($firstTarget, $secondTarget);
        $this->assertCount(1, $firstCorrectChoices);
        $this->assertCount(1, $secondCorrectChoices);
    }

    private function verifyLoginCaptcha(): void
    {
        $this->postJson(route('login.captcha.verify'), [
            'captcha_choices' => $this->captchaChoices(),
        ])->assertOk();
    }

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

        $this->actingAs($admin)
            ->patch('/admin/orders/'.$order->id, ['status' => 'processing'])
            ->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'processing',
        ]);
    }

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
            ->assertSee('Costumer Data')
            ->assertDontSee('#1');

        $this->actingAs($admin)
            ->patch('/admin/orders/'.$deliveredOrder->id.'/archive')
            ->assertRedirect()
            ->assertSessionHas('success', 'Order #1 was added to Costumer Data.');

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

    public function test_customer_and_admin_dashboards_render_their_workspaces(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $customer->orders()->create([
            'quantity' => 2,
            'delivery_address' => '12 River Street',
            'status' => 'pending',
        ]);
        $customer->orders()->create([
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
            ->assertSee($customer->name);

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

    public function test_admin_command_promotes_an_existing_customer(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->artisan('app:make-admin', ['email' => $customer->email])
            ->expectsOutput($customer->email.' is now an administrator.')
            ->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'id' => $customer->id,
            'role' => 'admin',
        ]);
    }

    public function test_admin_command_creates_an_account_with_split_name_fields(): void
    {
        $this->artisan('app:make-admin', ['email' => 'new.admin@example.com'])
            ->expectsQuestion('Administrator first name', 'Taylor')
            ->expectsQuestion('Administrator middle name (optional)', 'Lee')
            ->expectsQuestion('Administrator last name', 'Rivera')
            ->expectsQuestion('Password (at least 8 characters)', 'secure-password')
            ->expectsOutput('Administrator account created for new.admin@example.com.')
            ->assertSuccessful();

        $administrator = User::where('email', 'new.admin@example.com')->firstOrFail();
        $this->assertSame('admin', $administrator->role);
        $this->assertSame('Taylor Lee Rivera', $administrator->name);
        $this->assertSame('Lee', $administrator->middle_name);
    }

    public function test_admin_password_reset_preserves_the_account_and_its_orders(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $admin->orders()->create([
            'quantity' => 2,
            'delivery_address' => '12 River Street',
            'status' => 'pending',
        ]);

        $this->artisan('app:reset-admin-password', ['email' => $admin->email])
            ->expectsQuestion('New password (at least 8 characters)', 'replacement-password')
            ->expectsQuestion('Confirm new password', 'replacement-password')
            ->expectsOutput('The password for '.$admin->email.' has been reset.')
            ->assertSuccessful();

        $this->assertTrue(Hash::check('replacement-password', $admin->fresh()->password));
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'user_id' => $admin->id]);
    }

    public function test_admin_password_reset_does_not_change_password_when_confirmation_differs(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'password' => 'current-password',
        ]);

        $this->artisan('app:reset-admin-password', ['email' => $admin->email])
            ->expectsQuestion('New password (at least 8 characters)', 'replacement-password')
            ->expectsQuestion('Confirm new password', 'different-password')
            ->expectsOutput('The password confirmation does not match.')
            ->assertFailed();

        $this->assertTrue(Hash::check('current-password', $admin->fresh()->password));
    }

    public function test_admin_can_change_password_after_confirming_current_password(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'password' => 'current-password',
        ]);

        $this->actingAs($admin)
            ->get('/account/password')
            ->assertOk()
            ->assertSee('Change your password');

        $this->actingAs($admin)
            ->put('/account/password', [
                'current_password' => 'current-password',
                'password' => 'replacement-password',
                'password_confirmation' => 'replacement-password',
            ])
            ->assertRedirect('/account/password');

        $this->assertAuthenticatedAs($admin);
        $this->assertTrue(Hash::check('replacement-password', $admin->fresh()->password));
        $this->assertFalse(Hash::check('current-password', $admin->fresh()->password));
    }

    public function test_password_change_rejects_an_incorrect_current_password(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'password' => 'current-password',
        ]);

        $this->actingAs($admin)
            ->from('/account/password')
            ->put('/account/password', [
                'current_password' => 'wrong-password',
                'password' => 'replacement-password',
                'password_confirmation' => 'replacement-password',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('current-password', $admin->fresh()->password));
    }
}
