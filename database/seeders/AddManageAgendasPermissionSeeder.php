<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AddManageAgendasPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create manage-agendas permission if it doesn't exist
        $permission = Permission::firstOrCreate([
            'name' => 'manage-agendas',
            'guard_name' => 'web'
        ]);

        // Give permission to Super-admin role
        $superAdmin = Role::findByName('Super-admin', 'web');
        if ($superAdmin && !$superAdmin->hasPermissionTo('manage-agendas')) {
            $superAdmin->givePermissionTo($permission);
        }

        // Give permission to Admin role
        $admin = Role::findByName('Admin', 'web');
        if ($admin && !$admin->hasPermissionTo('manage-agendas')) {
            $admin->givePermissionTo($permission);
        }

        $this->command->info('Permission "manage-agendas" created and assigned to Super-admin and Admin roles!');
    }
}
