<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CustomerRegistrationTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('optionalEmailValues')]
    public function test_customers_can_register_without_email(array $emailField): void
    {
        foreach (['09171234567', '09171234568'] as $phone) {
            $this->post('/register', $emailField + [
                'first_name' => 'Jamie',
                'last_name' => 'Rivera',
                'phone' => $phone,
                'address' => '12 River Street',
                'password' => 'water1234',
                'password_confirmation' => 'water1234',
            ])->assertRedirect('/login')->assertSessionHasNoErrors();

            $customer = User::where('phone', $phone)->firstOrFail();

            $this->assertNull($customer->email);
            $this->assertSame('customer', $customer->role);
            $this->assertFalse($customer->is_approved);
            $this->assertTrue(Hash::check('water1234', $customer->password));
        }

        $this->assertDatabaseCount('users', 2);
        $this->assertGuest();
    }

    public static function optionalEmailValues(): array
    {
        return [
            'omitted' => [[]],
            'blank' => [['email' => '']],
            'null' => [['email' => null]],
        ];
    }

    #[DataProvider('invalidContactValues')]
    public function test_invalid_or_duplicate_contact_details_are_rejected(array $contact, string $field): void
    {
        User::factory()->create(['email' => 'taken@example.com', 'phone' => '09170000001']);

        $this->from('/register')->post('/register', $contact + [
            'first_name' => 'Jamie',
            'last_name' => 'Rivera',
            'phone' => '09171234567',
            'address' => '12 River Street',
            'password' => 'water1234',
            'password_confirmation' => 'water1234',
        ])->assertRedirect('/register')->assertSessionHasErrors($field);

        $this->assertDatabaseCount('users', 1);
        $this->assertGuest();
    }

    public static function invalidContactValues(): array
    {
        return [
            'missing mobile' => [['phone' => null], 'phone'],
            'blank mobile' => [['phone' => ''], 'phone'],
            'duplicate mobile' => [['phone' => '09170000001'], 'phone'],
            'mobile array' => [['phone' => ['09171234567']], 'phone'],
            'invalid email' => [['email' => 'not-an-email'], 'email'],
            'duplicate email' => [['email' => 'taken@example.com'], 'email'],
            'email array' => [['email' => ['jamie@example.com']], 'email'],
        ];
    }
}
