<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissions extends Component
{
    public $roleId;
    public $role;
    public $allPermissions = [];
    public $selectedPermissions = [];

    public function mount($roleId)
    {
        $this->roleId = $roleId;
        $this->role = Role::with('permissions')->findOrFail($roleId);
        $this->allPermissions = Permission::orderBy('name')->get();
        $this->selectedPermissions = $this->role->permissions->pluck('name')->toArray();
    }

    public function togglePermission($permissionName)
    {
        if (in_array($permissionName, $this->selectedPermissions)) {
            $this->selectedPermissions = array_values(
                array_diff($this->selectedPermissions, [$permissionName])
            );
        } else {
            $this->selectedPermissions[] = $permissionName;
        }
    }

    public function save()
    {
        // Authorization check - Super-admin only
        if (!auth()->user()->hasRole('Super-admin')) {
            session()->flash('error', 'Vous n\'avez pas la permission d\'effectuer cette action.');
            return;
        }

        // Sync permissions using Spatie method
        $permissions = Permission::whereIn('name', $this->selectedPermissions)->get();
        $this->role->syncPermissions($permissions);

        session()->flash('success', "Les permissions du rôle {$this->role->name} ont été mises à jour.");

        $this->dispatch('permissionsUpdated');
        $this->dispatch('closePermissionsModal');
    }

    public function getPermissionDescription($permissionName): string
    {
        return match($permissionName) {
            'manage-users' => 'Gérer les utilisateurs et leurs rôles',
            'manage-images' => 'Uploader et gérer les images',
            'manage-orders' => 'Gérer les commandes d\'images',
            'manage-pdf-files' => 'Gérer les fichiers PDF',
            'manage-sftp-config' => 'Configurer les paramètres SFTP',
            'upload-sftp-pdf' => 'Uploader des PDF via SFTP',
            'view-qualification' => 'Voir les données de qualification',
            'edit-qualification' => 'Modifier les données de qualification',
            'view-disponibilites' => 'Voir les disponibilités hébergement',
            'edit-disponibilites' => 'Modifier les disponibilités',
            'fill-forms' => 'Remplir les formulaires',
            default => $permissionName,
        };
    }

    public function render()
    {
        return view('livewire.admin.role-permissions');
    }
}
