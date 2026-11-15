<?php

namespace Tests\Feature\Orders;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ContainerSelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_order_form_offers_three_container_pictures_stored_in_the_project(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $response = $this->actingAs($customer)->get(route('orders.create'))->assertOk();

        foreach (Order::CONTAINER_TYPES as $container) {
            $response->assertSee($container['label'])->assertSee(asset($container['image']), false);
            $this->assertFileExists(public_path($container['image']));
        }

        $this->assertSame(3, substr_count($response->getContent(), 'name="container_type"'));
    }

    #[DataProvider('containerTypes')]
    public function test_the_selected_type_is_saved_and_visible_to_the_customer_admin_and_rider(string $type, string $label): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $rider = User::factory()->create(['role' => 'rider']);

        $response = $this->actingAs($customer)->post(route('orders.store'), [
            'container_type' => $type,
            'quantity' => 3,
            'delivery_address' => '12 River Street',
        ]);

        $order = $customer->orders()->firstOrFail();
        $response->assertRedirect(route('orders.show', $order));
        $this->assertSame($type, $order->container_type);
        $this->assertSame('pending', $order->status);

        $this->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee($label)
            ->assertSee(asset(Order::CONTAINER_TYPES[$type]['image']), false);
        $this->get(route('dashboard'))->assertOk()->assertSee($label);
        $this->actingAs($admin)->get(route('admin.orders'))->assertOk()->assertSee($label);

        $order->update(['status' => 'processing', 'rider_id' => $rider->id]);
        $this->actingAs($rider)->get(route('dashboard'))->assertOk()->assertSee($label);
    }

    #[DataProvider('invalidContainerTypes')]
    public function test_a_missing_or_unknown_container_type_cannot_create_an_order(mixed $type): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)->postJson(route('orders.store'), [
            'container_type' => $type,
            'quantity' => 2,
            'delivery_address' => '12 River Street',
        ])->assertUnprocessable()->assertJsonValidationErrors('container_type');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_a_validation_error_keeps_the_customers_container_choice(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)->from(route('orders.create'))->post(route('orders.store'), [
            'container_type' => 'small_jug',
            'quantity' => 0,
            'delivery_address' => '12 River Street',
        ])->assertRedirect(route('orders.create'))->assertSessionHasErrors('quantity');

        $response = $this->get(route('orders.create'))->assertOk();
        $document = new \DOMDocument;
        $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $selected = new \DOMXPath($document)->query('//input[@name="container_type" and @value="small_jug" and @checked]');

        $this->assertSame(1, $selected->length);
    }

    public function test_an_administrator_can_update_the_container_type_without_changing_the_status(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $customer->orders()->create([
            'container_type' => 'round_gallon',
            'quantity' => 2,
            'delivery_address' => '12 River Street',
            'status' => 'processing',
        ]);

        $this->actingAs($admin)->patch(route('admin.orders.save', $order), [
            'container_type' => 'slim_gallon',
            'quantity' => 2,
            'delivery_address' => '12 River Street',
        ])->assertRedirect(route('admin.orders'))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'container_type' => 'slim_gallon',
            'status' => 'processing',
        ]);

        $this->actingAs($customer)->get(route('orders.show', $order))->assertOk()->assertSee('Slim gallon');
    }

    public function test_existing_orders_survive_the_migration_without_an_invented_container_type(): void
    {
        $migration = require database_path('migrations/2026_11_15_000000_add_container_type_to_orders_table.php');
        $migration->down();

        $customer = User::factory()->create(['role' => 'customer']);
        $order = $customer->orders()->create([
            'quantity' => 2,
            'delivery_address' => '12 River Street',
        ]);

        $migration->up();

        $this->assertNull($order->fresh()->container_type);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'quantity' => 2]);
        $this->actingAs($customer)->get(route('orders.show', $order))->assertOk()->assertSee('Not specified');
    }

    public static function containerTypes(): array
    {
        return [
            'round' => ['round_gallon', 'Round gallon'],
            'slim' => ['slim_gallon', 'Slim gallon'],
            'small' => ['small_jug', 'Small jug'],
        ];
    }

    public static function invalidContainerTypes(): array
    {
        return [
            'missing' => [null],
            'empty' => [''],
            'unknown' => ['unknown_container'],
            'array' => [['round_gallon']],
        ];
    }
}
