<?php

namespace App\Livewire\Admin;

use App\Models\BrochureMenuItem;
use Livewire\Attributes\Rule;
use Livewire\Component;

class BrochureMenuManager extends Component
{
    // Modal d'ajout/édition
    public bool $showModal = false;
    public ?int $editingId = null;
    public ?int $editingParentId = null;

    #[Rule('required|string|max:255')]
    public string $itemTitle = '';

    #[Rule('required|string|max:2000')]
    public string $itemUrl = '';

    public bool $itemIsActive = true;
    public bool $itemAuthOnly = false;

    // Modal de suppression
    public bool $showDeleteModal = false;
    public ?int $deletingId = null;
    public ?string $deletingTitle = null;

    /**
     * Ouvrir le modal pour créer un item parent
     */
    public function createItem(): void
    {
        $this->resetForm();
        $this->editingParentId = null;
        $this->showModal = true;
    }

    /**
     * Ouvrir le modal pour créer un sous-item
     */
    public function createSubItem(int $parentId): void
    {
        $this->resetForm();
        $this->editingParentId = $parentId;
        $this->showModal = true;
    }

    /**
     * Ouvrir le modal pour éditer un item
     */
    public function editItem(int $id): void
    {
        $item = BrochureMenuItem::findOrFail($id);
        $this->resetForm();
        $this->editingId = $item->id;
        $this->editingParentId = $item->parent_id;
        $this->itemTitle = $item->title;
        $this->itemUrl = $item->url;
        $this->itemIsActive = $item->is_active;
        $this->itemAuthOnly = $item->auth_only;
        $this->showModal = true;
    }

    /**
     * Sauvegarder (créer ou mettre à jour)
     */
    public function saveItem(): void
    {
        $this->validate();

        if ($this->editingId) {
            $item = BrochureMenuItem::findOrFail($this->editingId);
            $item->update([
                'title' => $this->itemTitle,
                'url' => $this->itemUrl,
                'is_active' => $this->itemIsActive,
                'auth_only' => $this->itemAuthOnly,
            ]);
            session()->flash('success', 'Élément de menu mis à jour.');
        } else {
            $maxOrder = BrochureMenuItem::where('parent_id', $this->editingParentId)
                ->max('sort_order') ?? -1;

            BrochureMenuItem::create([
                'parent_id' => $this->editingParentId,
                'title' => $this->itemTitle,
                'url' => $this->itemUrl,
                'sort_order' => $maxOrder + 1,
                'is_active' => $this->itemIsActive,
                'auth_only' => $this->itemAuthOnly,
            ]);
            session()->flash('success', 'Élément de menu ajouté.');
        }

        $this->closeModal();
    }

    /**
     * Fermer le modal
     */
    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    /**
     * Ouvrir le modal de confirmation de suppression
     */
    public function confirmDelete(int $id): void
    {
        $item = BrochureMenuItem::findOrFail($id);
        $this->deletingId = $id;
        $this->deletingTitle = $item->title;
        $this->showDeleteModal = true;
    }

    /**
     * Supprimer un item (et ses enfants via cascade)
     */
    public function deleteItem(): void
    {
        if ($this->deletingId) {
            BrochureMenuItem::destroy($this->deletingId);
            session()->flash('success', 'Élément de menu supprimé.');
        }
        $this->showDeleteModal = false;
        $this->deletingId = null;
        $this->deletingTitle = null;
    }

    /**
     * Annuler la suppression
     */
    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->deletingId = null;
        $this->deletingTitle = null;
    }

    /**
     * Monter un item (sort_order - 1)
     */
    public function moveUp(int $id): void
    {
        $item = BrochureMenuItem::findOrFail($id);
        $sibling = BrochureMenuItem::where('parent_id', $item->parent_id)
            ->where('sort_order', '<', $item->sort_order)
            ->orderByDesc('sort_order')
            ->first();

        if ($sibling) {
            $tempOrder = $item->sort_order;
            $item->update(['sort_order' => $sibling->sort_order]);
            $sibling->update(['sort_order' => $tempOrder]);
        }
    }

    /**
     * Descendre un item (sort_order + 1)
     */
    public function moveDown(int $id): void
    {
        $item = BrochureMenuItem::findOrFail($id);
        $sibling = BrochureMenuItem::where('parent_id', $item->parent_id)
            ->where('sort_order', '>', $item->sort_order)
            ->orderBy('sort_order')
            ->first();

        if ($sibling) {
            $tempOrder = $item->sort_order;
            $item->update(['sort_order' => $sibling->sort_order]);
            $sibling->update(['sort_order' => $tempOrder]);
        }
    }

    /**
     * Activer/désactiver un item
     */
    public function toggleActive(int $id): void
    {
        $item = BrochureMenuItem::findOrFail($id);
        $item->update(['is_active' => !$item->is_active]);
    }

    /**
     * Reset le formulaire
     */
    private function resetForm(): void
    {
        $this->editingId = null;
        $this->editingParentId = null;
        $this->itemTitle = '';
        $this->itemUrl = '';
        $this->itemIsActive = true;
        $this->itemAuthOnly = false;
        $this->resetValidation();
    }

    public function render()
    {
        $menuItems = BrochureMenuItem::topLevel()
            ->with(['children' => fn($q) => $q->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        return view('livewire.admin.brochure-menu-manager', [
            'menuItems' => $menuItems,
        ])->layout('components.layouts.app');
    }
}
