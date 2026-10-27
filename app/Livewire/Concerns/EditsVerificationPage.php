<?php

namespace App\Livewire\Concerns;

use App\Models\VerificationPage;
use App\Services\VerificationPageService;

/**
 * Formulaire « Ajouter / Modifier une page » partagé par l'onglet Pages et la
 * fiche d'une page. Les relecteurs ne se choisissent qu'à la création ; ensuite
 * ils se gèrent depuis la liste ou la fiche.
 */
trait EditsVerificationPage
{
    public bool $showFormModal = false;

    public ?int $editingId = null;

    public string $title = '';

    public string $url = '';

    public ?string $urlEn = null;

    public ?string $urlIt = null;

    public ?string $category = null;

    public string $priority = 'medium';

    public ?string $deadline = null;

    /** @var int[] Relecteurs choisis à la création */
    public array $assigneeIds = [];

    protected function pageFormRules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'url' => 'required|url|max:500',
            'urlEn' => 'nullable|url|max:500',
            'urlIt' => 'nullable|url|max:500',
            'category' => 'nullable|in:'.implode(',', array_keys(VerificationPage::CATEGORIES)),
            'priority' => 'required|in:low,medium,high',
            'deadline' => 'nullable|date',
            'assigneeIds' => 'array',
            'assigneeIds.*' => 'exists:users,id',
        ];
    }

    protected function pageFormMessages(): array
    {
        return [
            'title.required' => 'Le titre est obligatoire.',
            'title.max' => 'Le titre ne doit pas dépasser 255 caractères.',
            'url.required' => 'L\'adresse FR est obligatoire.',
            'url.url' => 'L\'adresse FR doit être une adresse web valide (http:// ou https://).',
            'url.max' => 'L\'adresse FR ne doit pas dépasser 500 caractères.',
            'urlEn.url' => 'L\'adresse EN doit être une adresse web valide.',
            'urlEn.max' => 'L\'adresse EN ne doit pas dépasser 500 caractères.',
            'urlIt.url' => 'L\'adresse IT doit être une adresse web valide.',
            'urlIt.max' => 'L\'adresse IT ne doit pas dépasser 500 caractères.',
            'priority.in' => 'La priorité doit être basse, moyenne ou haute.',
            'deadline.date' => 'L\'échéance n\'est pas une date valide.',
            'category.in' => 'Catégorie invalide.',
        ];
    }

    public function openCreateModal(): void
    {
        $this->resetPageForm();
        $this->showFormModal = true;
    }

    public function openEditModal(int $pageId): void
    {
        $page = VerificationPage::withTrashed()->findOrFail($pageId);
        $this->resetPageForm();
        $this->editingId = $page->id;
        $this->title = $page->title;
        $this->url = $page->url;
        $this->urlEn = $page->url_en;
        $this->urlIt = $page->url_it;
        $this->category = $page->category;
        $this->priority = $page->priority;
        $this->deadline = $page->deadline?->toDateString();
        $this->showFormModal = true;
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->resetPageForm();
    }

    public function savePage(): void
    {
        $data = $this->validate($this->pageFormRules(), $this->pageFormMessages());

        $payload = [
            'title' => $data['title'],
            'url' => $data['url'],
            'url_en' => $data['urlEn'] ?: null,
            'url_it' => $data['urlIt'] ?: null,
            'category' => $data['category'] ?: null,
            'priority' => $data['priority'],
            'deadline' => $data['deadline'] ?: null,
        ];

        if ($this->editingId) {
            VerificationPage::withTrashed()->findOrFail($this->editingId)->update($payload);
            session()->flash('success', 'Page mise à jour.');
        } else {
            app(VerificationPageService::class)->create($payload, $data['assigneeIds'] ?? []);
            session()->flash('success', empty($data['assigneeIds'])
                ? 'Page ajoutée. Pensez à lui assigner des relecteurs.'
                : 'Page ajoutée. Elle sera distribuée aux relecteurs au fil des places libres.');
        }

        $this->closeFormModal();
    }

    private function resetPageForm(): void
    {
        $this->editingId = null;
        $this->title = '';
        $this->url = '';
        $this->urlEn = null;
        $this->urlIt = null;
        $this->category = null;
        $this->priority = 'medium';
        $this->deadline = null;
        $this->assigneeIds = [];
        $this->resetErrorBag();
    }
}
