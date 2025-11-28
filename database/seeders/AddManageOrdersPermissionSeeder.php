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
        // Créer la permission manage-orders
        $permission = Permission::firstOrCreate(['name' => 'manage-orders']);

        // Assigner la permission au rôle Super-admin
        $superAdminRole = Role::where('name', 'Super-admin')->first();

        if ($superAdminRole) {
            $superAdminRole->givePermissionTo($permission);
            $this->command->info('Permission "manage-orders" créée et assignée au rôle Super-admin!');
        } else {
            $this->command->warn('Le rôle Super-admin n\'existe pas.');
        }
    }
}
