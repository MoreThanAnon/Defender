<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_render(): void
    {
        $this->get('/')->assertOk()->assertDontSee('invest', false);
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
        $this->get('/password/reset')->assertOk();
    }

    public function test_register_then_sign_in_with_username_or_email(): void
    {
        $this->post('/register', [
            'username' => 'aoife', 'email' => 'aoife@example.com', 'password' => 'correct horse 1', 'password_confirmation' => 'correct horse 1',
            'role' => 'admin',
        ])->assertRedirect('/businesses');

        $user = User::firstOrFail();
        $this->assertSame('user', $user->fresh()->role, 'role must not be settable from the form');
        $this->assertFalse($user->isPlatformAdmin());

        $this->post('/logout')->assertRedirect('/login');
        $this->post('/login', ['username' => 'aoife', 'password' => 'correct horse 1'])->assertRedirect('/businesses');
        $this->post('/logout');
        $this->post('/login', ['username' => 'aoife@example.com', 'password' => 'correct horse 1'])->assertRedirect('/businesses');
    }

    public function test_weak_passwords_and_wrong_credentials_are_refused_and_login_is_throttled(): void
    {
        $this->post('/register', ['username' => 'bob', 'email' => 'bob@example.com', 'password' => 'short', 'password_confirmation' => 'short'])
            ->assertSessionHasErrors('password');

        User::factory()->create(['username' => 'carol']);
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['username' => 'carol', 'password' => 'wrong'])->assertSessionHasErrors('username');
        }
        $this->post('/login', ['username' => 'carol', 'password' => 'password'])->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_account_page_changes_password_only_with_the_current_one(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/account')->assertOk();
        $this->actingAs($user)->put('/account', ['email' => $user->email, 'current_password' => 'nope',
            'password' => 'a new long password', 'password_confirmation' => 'a new long password'])->assertSessionHasErrors('current_password');
        $this->actingAs($user)->put('/account', ['email' => $user->email, 'current_password' => 'password',
            'password' => 'a new long password', 'password_confirmation' => 'a new long password'])->assertSessionHasNoErrors();
    }

    /** The legacy fundraising surface must stay gone. */
    public function test_no_investment_or_payment_routes_exist(): void
    {
        $uris = collect(Route::getRoutes())->map(fn ($route) => $route->uri())->implode(' ');

        foreach (['properties', 'bid', 'deposit', 'withdraw', 'stripe', 'card', 'offering', 'test-runner', 'moderator', 'oauth', 'decidim', 'act-as'] as $gone) {
            $this->assertStringNotContainsString($gone, $uris, "Legacy route containing \"$gone\" is still registered.");
        }
    }
}
