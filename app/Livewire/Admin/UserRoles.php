<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class UserRoles extends Component
{
    public $userId;
    public $user;
    public $selectedRoles = [];
    public $availableRoles = [];

    public function mount($userId)
    {
        $this->userId = $userId;
        $this->user = User::with('roles')->findOrFail($userId);
        $this->availableRoles = Role::with('permissions')->get();
        $this->selectedRoles = $this->user->roles->pluck('name')->toArray();
    }

    public function toggleRole($roleName)
    {
        if (in_array($roleName, $this->selectedRoles)) {
            // Remove role
            $this->selectedRoles = array_diff($this->selectedRoles, [$roleName]);
        } else {
            // Add role
            $this->selectedRoles[] = $roleName;
        }
    }

    public function save()
    {
        // Sync roles
        $roles = Role::whereIn('name', $this->selectedRoles)->get();
        $this->user->syncRoles($roles);

        session()->flash('success', "Les rôles de {$this->user->name} ont été mis à jour.");

        $this->dispatch('rolesUpdated');
        $this->dispatch('closeModal');
    }

    public function render()
    {
        return view('livewire.admin.user-roles');
    }
}
