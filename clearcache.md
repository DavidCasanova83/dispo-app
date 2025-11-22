php artisan config:clear
php artisan cache:clear
php artisan view:clear


##Rebuild assets (css,js, etc.)
npm run build


##Voir les schedule actif
php artisan schedule:list


##Configuration Super-admin pour utilisateur test

### 1. Seeder les rôles et permissions
php artisan db:seed --class=RolePermissionSeeder

### 2. Assigner le rôle Super-admin via Tinker
php artisan tinker --execute="
\$user = App\Models\User::where('email', 'test@example.com')->first();
if (\$user) {
    \$user->update(['approved' => true, 'approved_at' => now()]);
    \$user->assignRole('Super-admin');
    echo 'User test assigned Super-admin role successfully!' . PHP_EOL;
    echo 'Roles: ' . \$user->getRoleNames()->implode(', ') . PHP_EOL;
} else {
    echo 'User test@example.com not found!' . PHP_EOL;
}
"

### 3. Vérifier les permissions
php artisan tinker --execute="
\$user = App\Models\User::where('email', 'test@example.com')->first();
if (\$user) {
    echo 'Roles: ' . \$user->getRoleNames()->implode(', ') . PHP_EOL;
    echo 'Permissions: ' . \$user->getAllPermissions()->pluck('name')->implode(', ') . PHP_EOL;
    echo 'Has manage-users: ' . (\$user->hasPermissionTo('manage-users') ? 'Yes' : 'No') . PHP_EOL;
}
"