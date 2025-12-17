<?php

namespace App\Livewire\Admin;

use App\Models\Sector;
use App\Models\User;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class UserRoles extends Component
{
    public $userId;
    public $user;
    public $selectedRoles = [];
    public $availableRoles = [];
    public $selectedSectors = [];
    public $availableSectors = [];

    public function mount($userId)
    {
        $this->userId = $userId;
        $this->user = User::with(['roles', 'sectors'])->findOrFail($userId);
        $this->availableRoles = Role::with('permissions')->get();
        $this->selectedRoles = $this->user->roles->pluck('name')->toArray();
        $this->availableSectors = Sector::orderBy('name')->get();
        $this->selectedSectors = $this->user->sectors->pluck('id')->toArray();
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

    public function toggleSector($sectorId)
    {
        $sectorId = (int) $sectorId;
        if (in_array($sectorId, $this->selectedSectors)) {
            $this->selectedSectors = array_values(array_diff($this->selectedSectors, [$sectorId]));
        } else {
            $this->selectedSectors[] = $sectorId;
        }
    }

    public function save()
    {
        // Sync roles
        $roles = Role::whereIn('name', $this->selectedRoles)->get();
        $this->user->syncRoles($roles);

        // Sync sectors
        $this->user->sectors()->sync($this->selectedSectors);

        session()->flash('success', "Les rôles et secteurs de {$this->user->name} ont été mis à jour.");

        $this->dispatch('rolesUpdated');
        $this->dispatch('closeModal');
    }

    public function render()
    {
        return view('livewire.admin.user-roles');
    }
}
