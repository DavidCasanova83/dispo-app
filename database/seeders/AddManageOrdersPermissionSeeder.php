<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AddManageOrdersPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create the manage-orders permission (idempotent)
        $permission = Permission::firstOrCreate(['name' => 'manage-orders', 'guard_name' => 'web']);

        // Assign to Super-admin role if not already assigned
        $superAdminRole = Role::where('name', 'Super-admin')->first();

        if ($superAdminRole && !$superAdminRole->hasPermissionTo('manage-orders')) {
            $superAdminRole->givePermissionTo($permission);
            $this->command->info('Permission "manage-orders" assignée au rôle Super-admin!');
        }
    }
}
