<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Rôle Traducteur et permissions de vérification des traductions.
 * À lancer en prod : php artisan db:seed --class=AddTranslatorRoleSeeder (idempotent).
 */
class AddTranslatorRoleSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $verify = Permission::firstOrCreate(['name' => 'verify-translations', 'guard_name' => 'web']);
        $manage = Permission::firstOrCreate(['name' => 'manage-translations', 'guard_name' => 'web']);

        $traducteur = Role::firstOrCreate(['name' => 'Traducteur', 'guard_name' => 'web']);
        $traducteur->givePermissionTo($verify);

        // Le super-admin vérifie et valide les corrections.
        $superAdmin = Role::where('name', 'Super-admin')->first();
        $superAdmin?->givePermissionTo([$verify, $manage]);

        $this->command?->info('Rôle Traducteur et permissions de traduction prêts.');
    }
}
