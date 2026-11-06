<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_login_screen(): void
    {
        $response = $this->get(route('login'));

        $response->assertSeeText('Log in to your account')
            ->assertSee('aria-label="Show password"', false)
            ->assertSee('<button class="login-button" type="submit">Log in', false)
            ->assertDontSeeText('Continue to dashboard');
    }

    public function test_user_can_log_in_and_is_redirected_to_dashboard(): void
    {
        $user = User::factory()->create([
            'email' => 'member@example.com',
        ]);

        $response = $this->post(route('login.store'), [
            'email' => 'member@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirectToRoute('dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_is_not_redirected_to_logout_after_logging_in(): void
    {
        $user = User::factory()->create([
            'email' => 'member@example.com',
        ]);

        $response = $this->withSession([
            'url.intended' => route('logout'),
        ])->post(route('login.store'), [
            'email' => 'member@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirectToRoute('dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_cannot_log_in_with_an_invalid_password(): void
    {
        User::factory()->create([
            'email' => 'member@example.com',
        ]);

        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => 'member@example.com',
            'password' => 'not-the-password',
        ]);

        $response->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'email' => 'The email or password you entered is incorrect.',
            ]);
        $this->assertGuest();
    }

    public function test_login_requires_an_email_and_password(): void
    {
        $response = $this->from(route('login'))->post(route('login.store'));

        $response->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'email' => 'The email field is required.',
                'password' => 'The password field is required.',
            ]);
        $this->assertGuest();
    }

    public function test_authenticated_user_is_redirected_away_from_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('login'));

        $response->assertRedirectToRoute('dashboard');
    }

    public function test_user_can_log_out(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(url('/'))
            ->assertSessionHas('status', 'You have been logged out securely.');
        $this->assertGuest();
    }

    public function test_login_attempts_are_rate_limited(): void
    {
        User::factory()->create([
            'email' => 'member@example.com',
        ]);

        foreach (range(1, 5) as $attempt) {
            $this->post(route('login.store'), [
                'email' => 'member@example.com',
                'password' => 'incorrect-'.$attempt,
            ]);
        }

        $this->post(route('login.store'), [
            'email' => 'member@example.com',
            'password' => 'incorrect-again',
        ])->assertTooManyRequests();
    }
}
