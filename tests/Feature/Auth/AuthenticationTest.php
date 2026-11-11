<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
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

    private function captchaChoices(): array
    {
        return $this->app['session.store']->get('login_captcha_correct_choices');
    }

    private function verifyLoginCaptcha(): void
    {
        $this->postJson(route('login.captcha.verify'), [
            'captcha_choices' => $this->captchaChoices(),
        ])->assertOk();
    }
}
