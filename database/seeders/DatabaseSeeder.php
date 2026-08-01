<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Demo accounts documented in the README. All three share the password
     * "password" — this seeder is for local development only.
     *
     * @var array<int, array{name: string, email: string, role: string}>
     */
    private const DEMO_USERS = [
        ['name' => 'Admin User', 'email' => 'admin@example.com', 'role' => Role::ADMIN],
        ['name' => 'Manager User', 'email' => 'manager@example.com', 'role' => Role::MANAGER],
        ['name' => 'Regular User', 'email' => 'user@example.com', 'role' => Role::USER],
    ];

    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        foreach (self::DEMO_USERS as $demo) {
            $user = User::firstOrCreate(
                ['email' => $demo['email']],
                ['name' => $demo['name'], 'password' => 'password'],
            );

            $user->forceFill(['email_verified_at' => now()])->save();
            $user->roles()->sync(Role::where('name', $demo['role'])->pluck('id'));
        }

        $this->call(DemoContentSeeder::class);
    }
}
