<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_the_login_screen_is_available_to_guests(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Sign in');
    }

    public function test_a_user_can_sign_in_with_valid_credentials(): void
    {
        $response = $this->post('/login', [
            'email' => 'ferdinand@arunika.test',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs(User::where('email', 'ferdinand@arunika.test')->firstOrFail());

        $this->assertNotNull(User::where('email', 'ferdinand@arunika.test')->value('last_login_at'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.login']);
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        $this->from('/login')->post('/login', [
            'email' => 'ferdinand@arunika.test',
            'password' => 'wrong-password',
        ])->assertRedirect('/login')->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_an_inactive_account_cannot_sign_in(): void
    {
        $user = User::where('email', 'nadia@arunika.test')->firstOrFail();
        $user->forceFill(['is_active' => false])->save();

        $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/login')->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_a_deactivated_user_is_signed_out_on_the_next_request(): void
    {
        $user = User::where('email', 'ferdinand@arunika.test')->firstOrFail();

        $this->actingAs($user);

        $user->forceFill(['is_active' => false])->save();

        $this->get('/overview')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_a_user_can_sign_out(): void
    {
        $user = User::where('email', 'ferdinand@arunika.test')->firstOrFail();

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect('/login');

        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.logout']);
    }

    public function test_repeated_failed_sign_ins_are_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/login', [
                'email' => 'ferdinand@arunika.test',
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->from('/login')->post('/login', [
            'email' => 'ferdinand@arunika.test',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString(
            'Too many sign-in attempts',
            session('errors')->first('email'),
        );
        $this->assertGuest();
    }

    public function test_the_password_is_hashed_in_the_database(): void
    {
        $user = User::where('email', 'ferdinand@arunika.test')->firstOrFail();

        $this->assertNotSame('password', $user->password);
        $this->assertTrue(Hash::check('password', $user->password));
    }
}
