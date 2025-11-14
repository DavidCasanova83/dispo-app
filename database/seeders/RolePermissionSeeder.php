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

        // Create permissions
        $permissions = [
            'manage-users',           // Exclusive to Super-admin
            'view-qualification',     // View qualification section
            'edit-qualification',     // Edit qualification data
            'view-disponibilites',    // View accommodation availability
            'edit-disponibilites',    // Edit accommodation data
            'fill-forms',             // Fill and validate forms
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create roles and assign permissions

        // 1. Super-admin: Full access to everything including user management
        $superAdmin = Role::create(['name' => 'Super-admin', 'guard_name' => 'web']);
        $superAdmin->givePermissionTo(Permission::all());

        // 2. Admin: Full access except user management (inherits Qualification + Disponibilites)
        $admin = Role::create(['name' => 'Admin', 'guard_name' => 'web']);
        $admin->givePermissionTo([
            'view-qualification',
            'edit-qualification',
            'view-disponibilites',
            'edit-disponibilites',
            'fill-forms',
        ]);

        // 3. Qualification: Access to qualification section
        $qualification = Role::create(['name' => 'Qualification', 'guard_name' => 'web']);
        $qualification->givePermissionTo([
            'view-qualification',
            'edit-qualification',
        ]);

        // 4. Disponibilites: Access to accommodation availability
        $disponibilites = Role::create(['name' => 'Disponibilites', 'guard_name' => 'web']);
        $disponibilites->givePermissionTo([
            'view-disponibilites',
            'edit-disponibilites',
        ]);

        // 5. Utilisateurs: Base level - can view and fill forms
        $utilisateurs = Role::create(['name' => 'Utilisateurs', 'guard_name' => 'web']);
        $utilisateurs->givePermissionTo(['fill-forms', 'view-qualification']);

        $this->command->info('Roles and permissions created successfully!');
        $this->command->info('Created 5 roles: Super-admin, Admin, Qualification, Disponibilites, Utilisateurs');
        $this->command->info('Created 6 permissions: manage-users, view-qualification, edit-qualification, view-disponibilites, edit-disponibilites, fill-forms');
    }
}
