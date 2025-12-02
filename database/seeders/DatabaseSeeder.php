<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create roles and permissions first
        $this->call(RolePermissionSeeder::class);

        // 2. Create test user (idempotent)
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // 3. Assign Super-admin role to test user
        if (!$user->hasRole('Super-admin')) {
            $user->assignRole('Super-admin');
        }

        $this->command->info('User test@example.com ready (password: password)');
    }
}
