<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AddExportApidaePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create the export-apidae permission (idempotent)
        $permission = Permission::firstOrCreate(['name' => 'export-apidae', 'guard_name' => 'web']);

        foreach (['Super-admin', 'Admin'] as $roleName) {
            $role = Role::where('name', $roleName)->first();

            if ($role && ! $role->hasPermissionTo('export-apidae')) {
                $role->givePermissionTo($permission);
                $this->command->info("Permission \"export-apidae\" assignée au rôle {$roleName}!");
            }
        }
    }
}
