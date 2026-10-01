<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AddSyncMailjetPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create the sync-mailjet permission (idempotent)
        $permission = Permission::firstOrCreate(['name' => 'sync-mailjet', 'guard_name' => 'web']);

        foreach (['Super-admin', 'Admin'] as $roleName) {
            $role = Role::where('name', $roleName)->first();

            if ($role && ! $role->hasPermissionTo('sync-mailjet')) {
                $role->givePermissionTo($permission);
                $this->command->info("Permission \"sync-mailjet\" assignée au rôle {$roleName}!");
            }
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
