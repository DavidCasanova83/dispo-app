<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AddManageBrochureMenuPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Créer la permission
        $permission = Permission::firstOrCreate(
            ['name' => 'manage-brochure-menu'],
            ['guard_name' => 'web']
        );

        // Assigner aux rôles Super-admin et Admin
        $superAdmin = Role::where('name', 'Super-admin')->first();
        if ($superAdmin && !$superAdmin->hasPermissionTo('manage-brochure-menu')) {
            $superAdmin->givePermissionTo($permission);
        }

        $admin = Role::where('name', 'Admin')->first();
        if ($admin && !$admin->hasPermissionTo('manage-brochure-menu')) {
            $admin->givePermissionTo($permission);
        }
    }
}
