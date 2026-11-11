<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OrderStatusUpdateTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('forwardStatusChanges')]
    public function test_an_administrator_can_move_an_order_forward(string $currentStatus, string $newStatus): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $customer->orders()->create([
            'quantity' => 2,
            'delivery_address' => '12 River Street',
            'status' => $currentStatus,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.orders.update', $order), ['status' => $newStatus])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame($newStatus, $order->fresh()->status);
        $this->assertSame($newStatus === 'delivered' ? 1 : 0, $customer->notifications()->count());
    }

    public static function forwardStatusChanges(): array
    {
        return [
            'confirm a new order' => ['pending', 'confirmed'],
            'begin processing' => ['confirmed', 'processing'],
            'send for delivery' => ['processing', 'out_for_delivery'],
            'complete delivery' => ['out_for_delivery', 'delivered'],
            'skip confirmation when already processing' => ['pending', 'processing'],
            'record an already completed delivery' => ['pending', 'delivered'],
        ];
    }

    #[DataProvider('backwardStatusChanges')]
    public function test_an_administrator_cannot_move_an_order_backwards(string $currentStatus, string $newStatus): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $customer->orders()->create([
            'quantity' => 2,
            'delivery_address' => '12 River Street',
            'status' => $currentStatus,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.orders'))
            ->patch(route('admin.orders.update', $order), ['status' => $newStatus])
            ->assertRedirect(route('admin.orders'))
            ->assertSessionHasErrors([
                'status' => 'Choose the current status or a later delivery step. Orders cannot move backwards.',
            ]);

        $this->assertSame($currentStatus, $order->fresh()->status);
        $this->assertSame(0, $customer->notifications()->count());
    }

    public static function backwardStatusChanges(): array
    {
        return [
            'confirmed to pending' => ['confirmed', 'pending'],
            'processing to pending' => ['processing', 'pending'],
            'processing to confirmed' => ['processing', 'confirmed'],
            'out for delivery to pending' => ['out_for_delivery', 'pending'],
            'out for delivery to confirmed' => ['out_for_delivery', 'confirmed'],
            'out for delivery to processing' => ['out_for_delivery', 'processing'],
            'delivered to pending' => ['delivered', 'pending'],
            'delivered to confirmed' => ['delivered', 'confirmed'],
            'delivered to processing' => ['delivered', 'processing'],
            'delivered to out for delivery' => ['delivered', 'out_for_delivery'],
        ];
    }

    public function test_repeating_delivery_does_not_change_the_order_or_notify_the_customer_again(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $customer->orders()->create([
            'quantity' => 2,
            'delivery_address' => '12 River Street',
            'status' => 'out_for_delivery',
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.orders.update', $order), ['status' => 'delivered'])
            ->assertRedirect();

        $deliveredAt = $order->fresh()->updated_at;
        $this->travel(5)->minutes();

        $this->patch(route('admin.orders.update', $order), ['status' => 'delivered'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertTrue($order->fresh()->updated_at->equalTo($deliveredAt));
        $this->assertSame(1, $customer->notifications()->count());
    }

    public function test_an_archived_order_cannot_change_status(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $customer->orders()->create([
            'quantity' => 2,
            'delivery_address' => '12 River Street',
            'status' => 'delivered',
            'archived_at' => now(),
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.orders.update', $order), ['status' => 'processing'])
            ->assertForbidden();

        $this->assertSame('delivered', $order->fresh()->status);
        $this->assertSame([], $order->availableStatusOptions());
    }

    public function test_invalid_statuses_are_rejected_without_changing_the_order(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $customer->orders()->create([
            'quantity' => 2,
            'delivery_address' => '12 River Street',
            'status' => 'pending',
        ]);

        foreach (['cancelled', 'unknown', null, ['delivered']] as $status) {
            $this->actingAs($admin)
                ->patchJson(route('admin.orders.update', $order), ['status' => $status])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('status');

            $this->assertSame('pending', $order->fresh()->status);
        }

        $this->assertSame(0, $customer->notifications()->count());
    }

    public function test_both_admin_screens_offer_only_the_current_and_later_statuses(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $customer->orders()->create([
            'quantity' => 2,
            'delivery_address' => '12 River Street',
            'status' => 'processing',
        ]);

        foreach ([route('admin.orders'), route('dashboard')] as $url) {
            $response = $this->actingAs($admin)->get($url)->assertOk();
            $document = new \DOMDocument;
            $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
            $buttons = new \DOMXPath($document)->query(
                '//div[@aria-label="Update order #'.$order->display_number.' status"]//button[@name="status"]'
            );

            $values = [];
            foreach ($buttons as $button) {
                $values[] = $button->getAttribute('value');
            }

            $this->assertSame(['processing', 'out_for_delivery', 'delivered'], $values);
            $this->assertTrue($buttons->item(0)->hasAttribute('disabled'));
            $this->assertFalse($buttons->item(1)->hasAttribute('disabled'));
            $this->assertFalse($buttons->item(2)->hasAttribute('disabled'));
        }
    }
}
