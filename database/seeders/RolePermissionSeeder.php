<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions (idempotent)
        $permissions = [
            'manage-users',
            'manage-images',
            'manage-orders',
            'view-qualification',
            'edit-qualification',
            'view-disponibilites',
            'edit-disponibilites',
            'fill-forms',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create roles and sync permissions (idempotent)

        // 1. Super-admin: Full access to everything including user management
        $superAdmin = Role::firstOrCreate(['name' => 'Super-admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::all());

        // 2. Admin: Full access except user management
        $admin = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $admin->syncPermissions([
            'manage-images',
            'manage-orders',
            'view-qualification',
            'edit-qualification',
            'view-disponibilites',
            'edit-disponibilites',
            'fill-forms',
        ]);

        // 3. Qualification: Access to qualification section
        $qualification = Role::firstOrCreate(['name' => 'Qualification', 'guard_name' => 'web']);
        $qualification->syncPermissions(['view-qualification', 'edit-qualification']);

        // 4. Disponibilites: Access to accommodation availability
        $disponibilites = Role::firstOrCreate(['name' => 'Disponibilites', 'guard_name' => 'web']);
        $disponibilites->syncPermissions(['view-disponibilites', 'edit-disponibilites']);

        // 5. Utilisateurs: Base level - can view and fill forms
        $utilisateurs = Role::firstOrCreate(['name' => 'Utilisateurs', 'guard_name' => 'web']);
        $utilisateurs->syncPermissions(['fill-forms', 'view-qualification']);

        $this->command->info('Roles and permissions synchronized!');
    }
}
