<?php

namespace Tests\Feature\Feedback;

use App\Models\Feedback;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_must_sign_in_to_submit_or_read_feedback(): void
    {
        $this->get('/feedback')->assertRedirect('/login');
        $this->post('/feedback', ['rating' => 5, 'message' => 'Good service.'])->assertRedirect('/login');
        $this->get('/admin/feedback')->assertRedirect('/login');
        $this->assertDatabaseCount('feedback', 0);
    }

    public function test_feedback_navigation_matches_the_signed_in_role(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $rider = User::factory()->create(['role' => 'rider']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($customer)->get('/dashboard')->assertOk()
            ->assertSee('href="'.route('feedback.index').'"', false)
            ->assertDontSee('href="'.route('admin.feedback.index').'"', false);
        $this->get('/feedback')->assertOk()
            ->assertSee('href="'.route('feedback.index').'"', false)
            ->assertDontSee('href="'.route('admin.feedback.index').'"', false);

        $this->actingAs($rider)->get('/dashboard')->assertOk()
            ->assertDontSee('href="'.route('feedback.index').'"', false)
            ->assertDontSee('href="'.route('admin.feedback.index').'"', false);

        $this->actingAs($admin)->get('/dashboard')->assertOk()
            ->assertSee('href="'.route('admin.feedback.index').'"', false)
            ->assertDontSee('href="'.route('feedback.index').'"', false);
        $this->get('/admin/feedback')->assertOk()
            ->assertSee('href="'.route('admin.feedback.index').'"', false)
            ->assertDontSee('href="'.route('feedback.index').'"', false);
    }

    #[DataProvider('staffRoles')]
    public function test_staff_cannot_use_the_customer_feedback_form(string $role): void
    {
        $staff = User::factory()->create(['role' => $role]);

        $this->actingAs($staff)->get('/feedback')->assertForbidden();
        $this->post('/feedback', ['rating' => 5, 'message' => 'Good service.'])->assertForbidden();
        $this->assertDatabaseCount('feedback', 0);
    }

    public static function staffRoles(): array
    {
        return [['admin'], ['rider']];
    }

    public function test_only_administrators_can_read_all_feedback(): void
    {
        foreach (['customer', 'rider'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get('/admin/feedback')->assertForbidden();
        }

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/admin/feedback')->assertOk()->assertSee('No customer feedback yet.');
    }

    #[DataProvider('validRatings')]
    public function test_customer_can_submit_feedback_only_under_their_own_account(int $rating): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'phone' => '09171234567']);
        $otherCustomer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)->post('/feedback', [
            'user_id' => $otherCustomer->id,
            'rating' => (string) $rating,
            'message' => "Delivery was on time.\nThank you for your help.",
        ])->assertRedirect('/feedback')->assertSessionHas('success');

        $entry = Feedback::sole();
        $this->assertSame($customer->id, $entry->user_id);
        $this->assertSame($rating, $entry->rating);
        $this->assertSame("Delivery was on time.\nThank you for your help.", $entry->message);
        $this->get('/feedback')->assertOk()->assertSee($rating.' out of 5')->assertSee('Delivery was on time.');
    }

    public static function validRatings(): array
    {
        return [[1], [2], [3], [4], [5]];
    }

    #[DataProvider('invalidFeedback')]
    public function test_invalid_feedback_is_rejected(array $input, string $field): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)->from('/feedback')->post('/feedback', $input + [
            'rating' => 5,
            'message' => 'Good service.',
        ])->assertRedirect('/feedback')->assertSessionHasErrors($field);

        $this->get('/feedback')->assertOk();
        $this->assertDatabaseCount('feedback', 0);
    }

    public static function invalidFeedback(): array
    {
        return [
            'missing rating' => [['rating' => null], 'rating'],
            'rating below range' => [['rating' => 0], 'rating'],
            'rating above range' => [['rating' => 6], 'rating'],
            'decimal rating' => [['rating' => 2.5], 'rating'],
            'text rating' => [['rating' => 'excellent'], 'rating'],
            'array rating' => [['rating' => [5]], 'rating'],
            'missing message' => [['message' => null], 'message'],
            'blank message' => [['message' => '   '], 'message'],
            'long message' => [['message' => str_repeat('a', 2001)], 'message'],
            'array message' => [['message' => ['Good service.']], 'message'],
        ];
    }

    public function test_customer_history_is_private_and_paginated_newest_first(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $otherCustomer = User::factory()->create(['role' => 'customer']);
        $otherCustomer->feedback()->create(['rating' => 1, 'message' => 'Another customer private feedback']);

        foreach (range(1, 7) as $number) {
            $customer->feedback()->create(['rating' => 4, 'message' => 'My feedback '.$number]);
        }

        $this->actingAs($customer)->get('/feedback')->assertOk()
            ->assertSeeInOrder(['My feedback 7', 'My feedback 6', 'My feedback 5'])
            ->assertDontSee('My feedback 1')
            ->assertDontSee('Another customer private feedback');
        $this->get('/feedback?page=2')->assertOk()
            ->assertSee('My feedback 1')
            ->assertDontSee('My feedback 7')
            ->assertDontSee('Another customer private feedback');
    }

    public function test_administrator_can_read_customer_details_and_full_escaped_feedback(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'phone' => '09171234567']);
        $withEmail = User::factory()->create(['role' => 'customer', 'email' => 'customer@example.com', 'phone' => '09171234568']);
        $message = '<script>alert("feedback")</script> Delivery was late.';
        $entry = $customer->feedback()->create(['rating' => 2, 'message' => $message]);
        $withEmail->feedback()->create(['rating' => 5, 'message' => 'A helpful team.']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin/feedback')->assertOk()
            ->assertSee($customer->name)
            ->assertSee('09171234567')
            ->assertSee('customer@example.com')
            ->assertSee('2 out of 5')
            ->assertSee($entry->created_at->format('M j, Y · g:i A'))
            ->assertSee($message)
            ->assertDontSee($message, false);

        $this->actingAs($customer)->get('/feedback')->assertOk()
            ->assertSee($message)
            ->assertDontSee($message, false)
            ->assertDontSee('A helpful team.');
    }

    public function test_administrator_feedback_is_paginated_newest_first(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        foreach (range(1, 13) as $number) {
            $customer->feedback()->create(['rating' => 4, 'message' => 'Submission number '.$number.'.']);
        }
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin/feedback')->assertOk()
            ->assertSeeInOrder(['Submission number 13.', 'Submission number 12.'])
            ->assertDontSee('Submission number 1.');
        $this->get('/admin/feedback?page=2')->assertOk()
            ->assertSee('Submission number 1.')
            ->assertDontSee('Submission number 13.');
    }

    public function test_deleting_a_customer_removes_only_their_feedback(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $otherCustomer = User::factory()->create(['role' => 'customer']);
        $removed = $customer->feedback()->create(['rating' => 4, 'message' => 'First customer feedback']);
        $retained = $otherCustomer->feedback()->create(['rating' => 5, 'message' => 'Other customer feedback']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->delete('/admin/customers/'.$customer->id)->assertRedirect();

        $this->assertModelMissing($removed);
        $this->assertModelExists($retained);
    }
}
