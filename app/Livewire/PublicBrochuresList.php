<?php

namespace App\Livewire;

use App\Models\Author;
use App\Models\BrochureReport;
use App\Models\Category;
use App\Models\Image;
use App\Models\Sector;
use App\Services\MailjetService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Rule;
use Livewire\Component;

class PublicBrochuresList extends Component
{
    // Filtres
    public ?int $categoryId = null;
    public ?int $authorId = null;
    public ?int $sectorId = null;

    // Modal de signalement
    public bool $showReportModal = false;
    public ?int $selectedBrochureId = null;
    public ?string $selectedBrochureTitle = null;

    #[Rule('required|string|min:10|max:1000')]
    public string $reportComment = '';

    public function mount(): void
    {
        // Définir "Verdon Tourisme" comme auteur par défaut
        $defaultAuthor = Author::where('name', 'Verdon Tourisme')->first();
        if ($defaultAuthor) {
            $this->authorId = $defaultAuthor->id;
        }
    }

    public function openReportModal(int $brochureId): void
    {
        $brochure = Image::find($brochureId);
        if (!$brochure) {
            return;
        }

        $this->selectedBrochureId = $brochureId;
        $this->selectedBrochureTitle = $brochure->title ?? $brochure->name;
        $this->reportComment = '';
        $this->resetValidation();
        $this->showReportModal = true;
    }

    public function closeReportModal(): void
    {
        $this->showReportModal = false;
        $this->selectedBrochureId = null;
        $this->selectedBrochureTitle = null;
        $this->reportComment = '';
        $this->resetValidation();
    }

    public function submitReport(): void
    {
        if (!Auth::check()) {
            session()->flash('error', 'Vous devez être connecté pour signaler un problème.');
            $this->closeReportModal();
            return;
        }

        $this->validate();

        $report = BrochureReport::create([
            'image_id' => $this->selectedBrochureId,
            'user_id' => Auth::id(),
            'comment' => $this->reportComment,
        ]);

        // Envoyer l'email de notification
        try {
            $mailjetService = app(MailjetService::class);
            $mailjetService->sendBrochureReportNotification($report);
        } catch (\Exception $e) {
            // On log l'erreur mais on ne bloque pas l'utilisateur
            \Log::error('Failed to send brochure report email: ' . $e->getMessage());
        }

        $this->closeReportModal();
        session()->flash('success', 'Votre signalement a été envoyé. Merci pour votre contribution !');
    }

    public function resetFilters(): void
    {
        $this->categoryId = null;
        $this->authorId = null;
        $this->sectorId = null;
    }

    public function render()
    {
        // Récupérer toutes les brochures avec filtres
        $brochures = Image::query()
            ->when($this->categoryId, fn($q) => $q->where('category_id', $this->categoryId))
            ->when($this->authorId, fn($q) => $q->where('author_id', $this->authorId))
            ->when($this->sectorId, fn($q) => $q->where('sector_id', $this->sectorId))
            ->orderByRaw('display_order IS NULL, display_order ASC')
            ->orderBy('title')
            ->get();

        // Récupérer les IDs de toutes les brochures pour les filtres
        $availableBrochureIds = Image::pluck('id');

        return view('livewire.public-brochures-list', [
            'brochures' => $brochures,
            'categories' => Category::whereHas('images', fn($q) => $q->whereIn('id', $availableBrochureIds))->orderBy('name')->get(),
            'authors' => Author::whereHas('images', fn($q) => $q->whereIn('id', $availableBrochureIds))->orderBy('name')->get(),
            'sectors' => Sector::whereHas('images', fn($q) => $q->whereIn('id', $availableBrochureIds))->orderBy('name')->get(),
        ])->layout('components.layouts.guest');
    }
}
