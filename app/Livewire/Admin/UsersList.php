<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class UsersList extends Component
{
    use WithPagination;

    public $search = '';
    public $filterStatus = 'all'; // all, approved, pending
    public $filterRole = 'all'; // all, or specific role name
    public $selectedUser = null;
    public $showApprovalModal = false;
    public $showRolesModal = false;
    public $showPermissionsModal = false;
    public $selectedRoleId = null;
    public $showCreateRoleModal = false;
    public $newRoleName = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'filterStatus' => ['except' => 'all'],
        'filterRole' => ['except' => 'all'],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterStatus()
    {
        $this->resetPage();
    }

    public function updatingFilterRole()
    {
        $this->resetPage();
    }

    public function openApprovalModal($userId)
    {
        $this->selectedUser = User::findOrFail($userId);
        $this->showApprovalModal = true;
    }

    public function closeApprovalModal()
    {
        $this->showApprovalModal = false;
        $this->selectedUser = null;
    }

    public function openRolesModal($userId)
    {
        $this->selectedUser = User::with('roles')->findOrFail($userId);
        $this->showRolesModal = true;
    }

    public function closeRolesModal()
    {
        $this->showRolesModal = false;
        $this->selectedUser = null;
    }

    #[On('closeModal')]
    public function handleCloseModal()
    {
        $this->closeRolesModal();
    }

    #[On('rolesUpdated')]
    public function handleRolesUpdated()
    {
        $this->closeRolesModal();
    }

    #[On('openPermissionsModal')]
    public function openPermissionsModal($roleId)
    {
        // Only Super-admin can access this
        if (!auth()->user()->hasRole('Super-admin')) {
            return;
        }

        $this->selectedRoleId = $roleId;
        $this->showPermissionsModal = true;
    }

    public function closePermissionsModal()
    {
        $this->showPermissionsModal = false;
        $this->selectedRoleId = null;
    }

    #[On('closePermissionsModal')]
    public function handleClosePermissionsModal()
    {
        $this->closePermissionsModal();
    }

    #[On('permissionsUpdated')]
    public function handlePermissionsUpdated()
    {
        $this->closePermissionsModal();
    }

    public function openCreateRoleModal()
    {
        // Only Super-admin can create roles
        if (!auth()->user()->hasRole('Super-admin')) {
            return;
        }

        $this->newRoleName = '';
        $this->showCreateRoleModal = true;
    }

    public function closeCreateRoleModal()
    {
        $this->showCreateRoleModal = false;
        $this->newRoleName = '';
    }

    public function createRole()
    {
        // Only Super-admin can create roles
        if (!auth()->user()->hasRole('Super-admin')) {
            session()->flash('error', 'Vous n\'avez pas la permission d\'effectuer cette action.');
            return;
        }

        $this->validate([
            'newRoleName' => 'required|string|min:2|max:50|unique:roles,name',
        ], [
            'newRoleName.required' => 'Le nom du rôle est requis.',
            'newRoleName.min' => 'Le nom du rôle doit contenir au moins 2 caractères.',
            'newRoleName.max' => 'Le nom du rôle ne peut pas dépasser 50 caractères.',
            'newRoleName.unique' => 'Ce nom de rôle existe déjà.',
        ]);

        Role::create(['name' => $this->newRoleName, 'guard_name' => 'web']);

        session()->flash('success', "Le rôle '{$this->newRoleName}' a été créé avec succès.");
        $this->closeCreateRoleModal();
    }

    public function approveUser($userId)
    {
        $user = User::findOrFail($userId);
        $user->approve();

        session()->flash('success', "L'utilisateur {$user->name} a été approuvé.");
        $this->closeApprovalModal();
    }

    public function disapproveUser($userId)
    {
        $user = User::findOrFail($userId);
        $user->disapprove();

        session()->flash('success', "L'approbation de {$user->name} a été révoquée.");
        $this->closeApprovalModal();
    }

    public function render()
    {
        $query = User::query()->with('roles');

        // Search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        // Status filter
        if ($this->filterStatus === 'approved') {
            $query->where('approved', true);
        } elseif ($this->filterStatus === 'pending') {
            $query->where('approved', false);
        }

        // Role filter
        if ($this->filterRole !== 'all') {
            $query->whereHas('roles', function ($q) {
                $q->where('name', $this->filterRole);
            });
        }

        $users = $query->latest()->paginate(15);
        $roles = Role::all();

        $stats = [
            'total' => User::count(),
            'approved' => User::where('approved', true)->count(),
            'pending' => User::where('approved', false)->count(),
            'with_roles' => User::has('roles')->count(),
        ];

        return view('livewire.admin.users-list', [
            'users' => $users,
            'roles' => $roles,
            'stats' => $stats,
        ])->layout('components.layouts.app');
    }
}
