<?php

namespace App\Services;

use App\Models\User;
use Spatie\Permission\Models\Role;

class RoleService
{
    /**
     * Check if user has at least one role
     */
    public function userHasAnyRole(User $user): bool
    {
        return $user->roles()->count() > 0;
    }

    /**
     * Get all available roles
     */
    public function getAllRoles()
    {
        return Role::with('permissions')->get();
    }

    /**
     * Assign default role to user (Utilisateurs - base level)
     */
    public function assignDefaultRole(User $user): void
    {
        $defaultRole = Role::where('name', 'Utilisateurs')->first();

        if ($defaultRole && !$user->hasRole('Utilisateurs')) {
            $user->assignRole($defaultRole);
        }
    }

    /**
     * Check if user can access the system (approved + has at least one role)
     */
    public function canAccessSystem(User $user): bool
    {
        return $user->isApproved() && $this->userHasAnyRole($user);
    }

    /**
     * Get user's highest role in hierarchy
     * Hierarchy: Super-admin > Admin > Qualification/Disponibilites > Utilisateurs
     */
    public function getUserHighestRole(User $user): ?string
    {
        $roleHierarchy = [
            'Super-admin' => 5,
            'Admin' => 4,
            'Qualification' => 3,
            'Disponibilites' => 3,
            'Utilisateurs' => 1,
        ];

        $userRoles = $user->roles->pluck('name')->toArray();
        $highestRole = null;
        $highestLevel = 0;

        foreach ($userRoles as $roleName) {
            $level = $roleHierarchy[$roleName] ?? 0;
            if ($level > $highestLevel) {
                $highestLevel = $level;
                $highestRole = $roleName;
            }
        }

        return $highestRole;
    }

    /**
     * Get role description
     */
    public function getRoleDescription(string $roleName): string
    {
        return match ($roleName) {
            'Super-admin' => 'Accès complet au système + gestion des utilisateurs',
            'Admin' => 'Accès complet au système sauf gestion des utilisateurs',
            'Qualification' => 'Accès à la section qualification et statistiques',
            'Disponibilites' => 'Accès aux informations d\'hébergement et disponibilités',
            'Utilisateurs' => 'Accès aux formulaires des villes uniquement',
            default => 'Rôle inconnu',
        };
    }

    /**
     * Sync user roles - remove all and assign new ones
     */
    public function syncUserRoles(User $user, array $roleNames): void
    {
        $roles = Role::whereIn('name', $roleNames)->get();
        $user->syncRoles($roles);
    }

    /**
     * Check if user needs role assignment
     * (approved but has no roles)
     */
    public function needsRoleAssignment(User $user): bool
    {
        return $user->isApproved() && !$this->userHasAnyRole($user);
    }
}
