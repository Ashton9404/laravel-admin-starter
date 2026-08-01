<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guests_are_rejected(): void
    {
        $this->getJson('/api/dashboard')->assertUnauthorized();
    }

    public function test_a_user_without_users_view_is_forbidden(): void
    {
        $this->actingAs(User::factory()->withRole(Role::USER)->create())
            ->getJson('/api/dashboard')
            ->assertForbidden();
    }

    public function test_a_manager_receives_the_full_payload(): void
    {
        $manager = User::factory()->withRole(Role::MANAGER)->create();

        $this->actingAs($manager)
            ->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonStructure([
                'totals' => ['users', 'verified', 'unverified', 'new_this_week'],
                'registrations' => [['date', 'total']],
                'users_by_role' => [['name', 'label', 'total']],
                'recent_users' => [['id', 'name', 'email', 'roles', 'verified', 'created_at']],
            ]);
    }

    /**
     * The panel is the same panel either way, so the key is always present and
     * null is the answer for "you may not see this" — the SPA never has to work
     * out why something is missing.
     */
    public function test_the_activity_panel_follows_the_permission(): void
    {
        $this->actingAs(User::factory()->withRole(Role::MANAGER)->create())
            ->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('recent_activity', null);

        $this->actingAs(User::factory()->withRole(Role::ADMIN)->create())
            ->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonStructure(['recent_activity' => [['id', 'event', 'causer', 'created_at']]]);
    }

    public function test_the_totals_are_accurate(): void
    {
        $manager = User::factory()->withRole(Role::MANAGER)->create();
        User::factory()->count(3)->create();
        User::factory()->count(2)->unverified()->create();

        $response = $this->actingAs($manager)->getJson('/api/dashboard')->assertOk();

        $response->assertJsonPath('totals.users', 6)
            ->assertJsonPath('totals.verified', 4)
            ->assertJsonPath('totals.unverified', 2);
    }

    public function test_the_trend_covers_thirty_days_including_quiet_ones(): void
    {
        $manager = User::factory()->withRole(Role::MANAGER)->create();

        $response = $this->actingAs($manager)->getJson('/api/dashboard')->assertOk();

        // Zero-filled: a sparse series would make the line skip quiet days.
        $this->assertCount(30, $response->json('registrations'));
        $this->assertSame(
            0,
            $response->json('registrations.0.total'),
            'The oldest day in the window has no sign-ups and must still be present.'
        );
    }

    public function test_users_without_a_role_are_bucketed_separately(): void
    {
        $manager = User::factory()->withRole(Role::MANAGER)->create();
        User::factory()->count(2)->create();

        $roles = collect(
            $this->actingAs($manager)->getJson('/api/dashboard')->json('users_by_role')
        )->keyBy('name');

        $this->assertSame(1, $roles['manager']['total']);
        $this->assertSame(2, $roles['none']['total']);
        $this->assertSame(0, $roles['admin']['total']);
    }
}
