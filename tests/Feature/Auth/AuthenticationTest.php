<?php

namespace Tests\Feature\Auth;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_can_authenticate(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk()->assertJsonPath('data.id', $user->id);

        $this->assertAuthenticatedAs($user);
    }

    public function test_the_login_response_carries_roles_and_permissions(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $manager = User::factory()->withRole(Role::MANAGER)->create();

        // The SPA decides what to render from this payload; if it arrives without
        // permissions the UI is permission-blind until the next full page load.
        $this->postJson('/api/login', [
            'email' => $manager->email,
            'password' => 'password',
        ])->assertOk()
            ->assertJsonPath('data.roles', [Role::MANAGER])
            ->assertJsonPath('data.permissions', [
                Permission::USERS_CREATE,
                Permission::USERS_UPDATE,
                Permission::USERS_VIEW,
            ]);
    }

    public function test_users_cannot_authenticate_with_an_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertUnprocessable()->assertJsonValidationErrorFor('email');

        $this->assertGuest();
    }

    public function test_login_is_rate_limited_after_five_attempts(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 5) as $ignored) {
            $this->postJson('/api/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ])->assertUnprocessable();
        }

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertUnprocessable();
        $this->assertStringContainsString(
            'Too many login attempts',
            $response->json('errors.email.0')
        );
        $this->assertGuest();

        RateLimiter::clear(mb_strtolower($user->email).'|127.0.0.1');
    }

    public function test_authenticated_users_can_read_their_own_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_guests_cannot_read_the_profile_endpoint(): void
    {
        $this->getJson('/api/user')->assertUnauthorized();
    }

    public function test_users_can_log_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/logout')->assertOk();

        // Specifically the "web" guard: the auth:sanctum middleware promotes
        // "sanctum" to the default guard for the request, and that guard keeps
        // the resolved user in memory even after the session is invalidated.
        // The session is what actually carries the login across requests.
        $this->assertGuest('web');
    }
}
